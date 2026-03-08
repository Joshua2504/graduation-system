<?php
/**
 * Lightweight SMTP mailer — sends emails via SMTP using raw sockets.
 * Reads config from .env: MAIL_HOST, MAIL_PORT, MAIL_USER, MAIL_PASS
 * 
 * Supports STARTTLS (port 587) and implicit TLS (port 465).
 */

require_once __DIR__ . '/db.php'; // ensures .env is loaded

/**
 * Send an email via SMTP
 *
 * @param string $to      Recipient email
 * @param string $subject Email subject
 * @param string $body    HTML email body
 * @return bool           True if sent successfully
 */
function sendMail(string $to, string $subject, string $body): bool {
    // Reject email addresses containing CRLF to prevent SMTP header injection
    if (preg_match('/[\r\n]/', $to)) {
        error_log('[Mailer] Rejected email with CRLF characters: ' . addcslashes($to, "\r\n"));
        return false;
    }

    $host = $_ENV['MAIL_HOST'] ?? getenv('MAIL_HOST') ?: '';
    $port = (int)($_ENV['MAIL_PORT'] ?? getenv('MAIL_PORT') ?: 587);
    $user = $_ENV['MAIL_USER'] ?? getenv('MAIL_USER') ?: '';
    $pass = $_ENV['MAIL_PASS'] ?? getenv('MAIL_PASS') ?: '';

    if (empty($host) || empty($user) || empty($pass)) {
        error_log('[Mailer] SMTP configuration missing in .env');
        return false;
    }

    $from = $user; // Send from the configured mail user
    $fromName = 'Graduation Project System';

    try {
        // Connect
        $socket = @fsockopen($host, $port, $errno, $errstr, 10);
        if (!$socket) {
            error_log("[Mailer] Cannot connect to $host:$port — $errstr ($errno)");
            return false;
        }

        stream_set_timeout($socket, 10);
        $response = smtpRead($socket);
        if (substr($response, 0, 3) !== '220') {
            error_log("[Mailer] Unexpected greeting: $response");
            fclose($socket);
            return false;
        }

        // EHLO
        smtpCmd($socket, "EHLO localhost", '250');

        // STARTTLS for port 587
        if ($port === 587) {
            smtpCmd($socket, "STARTTLS", '220');
            $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
            if (!$crypto) {
                error_log("[Mailer] STARTTLS failed");
                fclose($socket);
                return false;
            }
            // Re-EHLO after STARTTLS
            smtpCmd($socket, "EHLO localhost", '250');
        }

        // AUTH LOGIN
        smtpCmd($socket, "AUTH LOGIN", '334');
        smtpCmd($socket, base64_encode($user), '334');
        smtpCmd($socket, base64_encode($pass), '235');

        // MAIL FROM
        smtpCmd($socket, "MAIL FROM:<$from>", '250');

        // RCPT TO
        smtpCmd($socket, "RCPT TO:<$to>", '250');

        // DATA
        smtpCmd($socket, "DATA", '354');

        // Build RFC 2822 message
        $boundary = md5(uniqid(time()));
        $headers  = "From: $fromName <$from>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "Message-ID: <" . uniqid('grad_') . "@" . gethostname() . ">\r\n";

        $message = $headers . "\r\n" . chunk_split(base64_encode($body));

        // Escape leading dots (RFC 5321)
        $message = str_replace("\r\n.", "\r\n..", $message);

        fwrite($socket, $message . "\r\n.\r\n");
        $response = smtpRead($socket);
        if (substr($response, 0, 3) !== '250') {
            error_log("[Mailer] DATA rejected: $response");
            fclose($socket);
            return false;
        }

        // QUIT
        smtpCmd($socket, "QUIT", '221');
        fclose($socket);

        return true;

    } catch (\Exception $e) {
        error_log("[Mailer] Exception: " . $e->getMessage());
        if (isset($socket) && is_resource($socket)) fclose($socket);
        return false;
    }
}

/**
 * Read SMTP response (multi-line aware)
 */
function smtpRead($socket): string {
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        // Last line has a space after the code (e.g. "250 OK")
        if (isset($line[3]) && $line[3] === ' ') break;
    }
    return trim($response);
}

/**
 * Send an SMTP command and verify the expected response code
 */
function smtpCmd($socket, string $cmd, string $expectedCode): string {
    fwrite($socket, $cmd . "\r\n");
    $response = smtpRead($socket);
    if (substr($response, 0, strlen($expectedCode)) !== $expectedCode) {
        throw new \RuntimeException("SMTP error for '$cmd': $response");
    }
    return $response;
}

/**
 * Generate a secure verification token and store it in the DB
 *
 * @param int $userId User ID
 * @return string The generated token
 */
function generateVerificationToken(int $userId): string {
    $token = bin2hex(random_bytes(32)); // 64 hex chars
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE users SET verification_token = ?, token_expires_at = ? WHERE id = ?");
    $stmt->execute([$token, $expires, $userId]);

    return $token;
}

/**
 * Send verification email to a user
 *
 * @param string $email     User email
 * @param string $name      User name
 * @param string $token     Verification token
 * @param string $lang      Language code (ar/en)
 * @return bool
 */
function sendVerificationEmail(string $email, string $name, string $token, string $lang = 'ar'): bool {
    // Build verification URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8642';
    $verifyUrl = "$protocol://$host/verify?token=$token";

    if ($lang === 'ar') {
        $subject = 'تأكيد البريد الإلكتروني - نظام مشاريع التخرج';
        $heading = 'تأكيد البريد الإلكتروني';
        $greeting = "مرحباً $name،";
        $message = 'شكراً لتسجيلك في نظام إدارة مشاريع التخرج. يرجى الضغط على الزر أدناه لتأكيد بريدك الإلكتروني.';
        $btnText = 'تأكيد البريد الإلكتروني';
        $expiry = 'هذا الرابط صالح لمدة 24 ساعة.';
        $ignore = 'إذا لم تقم بإنشاء هذا الحساب، يرجى تجاهل هذا البريد.';
        $dir = 'rtl';
    } else {
        $subject = 'Email Verification - Graduation Project System';
        $heading = 'Verify Your Email';
        $greeting = "Hello $name,";
        $message = 'Thank you for registering with the Graduation Project Management System. Please click the button below to verify your email address.';
        $btnText = 'Verify Email';
        $expiry = 'This link is valid for 24 hours.';
        $ignore = 'If you did not create this account, please ignore this email.';
        $dir = 'ltr';
    }

    $body = <<<HTML
<!DOCTYPE html>
<html dir="$dir" lang="$lang">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background:#f4f6f9;">
    <div style="max-width:520px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">
        <div style="background:#0d6efd;padding:24px;text-align:center;">
            <h1 style="margin:0;color:#fff;font-size:22px;">🎓 $heading</h1>
        </div>
        <div style="padding:28px 32px;">
            <p style="font-size:16px;color:#333;">$greeting</p>
            <p style="font-size:15px;color:#555;line-height:1.6;">$message</p>
            <div style="text-align:center;margin:32px 0;">
                <a href="$verifyUrl" style="display:inline-block;background:#0d6efd;color:#fff;padding:14px 36px;border-radius:8px;text-decoration:none;font-size:16px;font-weight:bold;">$btnText</a>
            </div>
            <p style="font-size:13px;color:#888;text-align:center;">$expiry</p>
            <hr style="border:none;border-top:1px solid #eee;margin:20px 0;">
            <p style="font-size:12px;color:#999;text-align:center;">$ignore</p>
        </div>
    </div>
</body>
</html>
HTML;

    return sendMail($email, $subject, $body);
}

/**
 * Send project invitation email to a student
 *
 * @param string $email       Invitee email
 * @param string $inviteeName Invitee name
 * @param string $inviterName Name of the person who sent the invite
 * @param string $projectTitle Project title
 * @param string $token       Invitation token
 * @param string $lang        Language code (ar/en)
 * @return bool
 */
function sendInvitationEmail(string $email, string $inviteeName, string $inviterName, string $projectTitle, string $token, string $lang = 'ar'): bool {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8642';
    $joinUrl = "$protocol://$host/join?token=$token";

    if ($lang === 'ar') {
        $subject = 'دعوة للانضمام لمشروع تخرج - ' . $projectTitle;
        $heading = 'دعوة للانضمام لمشروع تخرج';
        $greeting = "مرحباً $inviteeName،";
        $message = "قام <strong>$inviterName</strong> بدعوتك للانضمام لمشروع التخرج <strong>$projectTitle</strong>. اضغط على الزر أدناه لقبول الدعوة.";
        $btnText = 'قبول الدعوة';
        $expiry = 'هذه الدعوة صالحة لمدة 7 أيام.';
        $ignore = 'إذا لم تكن تعرف هذا الشخص، يمكنك تجاهل هذا البريد.';
        $dir = 'rtl';
    } else {
        $subject = 'Project Invitation - ' . $projectTitle;
        $heading = 'Project Team Invitation';
        $greeting = "Hello $inviteeName,";
        $message = "<strong>$inviterName</strong> has invited you to join the graduation project <strong>$projectTitle</strong>. Click the button below to accept the invitation.";
        $btnText = 'Accept Invitation';
        $expiry = 'This invitation is valid for 7 days.';
        $ignore = 'If you don\'t know this person, you can ignore this email.';
        $dir = 'ltr';
    }

    $body = <<<HTML
<!DOCTYPE html>
<html dir="$dir" lang="$lang">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background:#f4f6f9;">
    <div style="max-width:520px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">
        <div style="background:#198754;padding:24px;text-align:center;">
            <h1 style="margin:0;color:#fff;font-size:22px;">🎓 $heading</h1>
        </div>
        <div style="padding:28px 32px;">
            <p style="font-size:16px;color:#333;">$greeting</p>
            <p style="font-size:15px;color:#555;line-height:1.6;">$message</p>
            <div style="text-align:center;margin:32px 0;">
                <a href="$joinUrl" style="display:inline-block;background:#198754;color:#fff;padding:14px 36px;border-radius:8px;text-decoration:none;font-size:16px;font-weight:bold;">$btnText</a>
            </div>
            <p style="font-size:13px;color:#888;text-align:center;">$expiry</p>
            <hr style="border:none;border-top:1px solid #eee;margin:20px 0;">
            <p style="font-size:12px;color:#999;text-align:center;">$ignore</p>
        </div>
    </div>
</body>
</html>
HTML;

    return sendMail($email, $subject, $body);
}

/**
 * Send welcome email with login credentials (used when doctor creates a student account)
 *
 * @param string $email    Student email
 * @param string $name     Student name
 * @param string $password Plain-text password (sent once)
 * @param string $lang     Language code (ar/en)
 * @return bool
 */
function sendWelcomeEmail(string $email, string $name, string $password, string $lang = 'ar'): bool {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8642';
    $loginUrl = "$protocol://$host/login";

    if ($lang === 'ar') {
        $subject = 'حساب جديد - نظام مشاريع التخرج';
        $heading = 'مرحباً بك في نظام مشاريع التخرج';
        $greeting = "مرحباً $name،";
        $message = 'تم إنشاء حساب لك في نظام إدارة مشاريع التخرج. يمكنك تسجيل الدخول باستخدام البيانات التالية:';
        $emailLabel = 'البريد الإلكتروني';
        $passLabel = 'كلمة المرور';
        $btnText = 'تسجيل الدخول';
        $changePass = 'يرجى تغيير كلمة المرور بعد تسجيل الدخول الأول.';
        $dir = 'rtl';
    } else {
        $subject = 'New Account - Graduation Project System';
        $heading = 'Welcome to the Graduation Project System';
        $greeting = "Hello $name,";
        $message = 'An account has been created for you in the Graduation Project Management System. You can log in using the following credentials:';
        $emailLabel = 'Email';
        $passLabel = 'Password';
        $btnText = 'Log In';
        $changePass = 'Please change your password after your first login.';
        $dir = 'ltr';
    }

    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safePass = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');

    $body = <<<HTML
<!DOCTYPE html>
<html dir="$dir" lang="$lang">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background:#f4f6f9;">
    <div style="max-width:520px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">
        <div style="background:#0d6efd;padding:24px;text-align:center;">
            <h1 style="margin:0;color:#fff;font-size:22px;">🎓 $heading</h1>
        </div>
        <div style="padding:28px 32px;">
            <p style="font-size:16px;color:#333;">$greeting</p>
            <p style="font-size:15px;color:#555;line-height:1.6;">$message</p>
            <div style="background:#f8f9fa;border-radius:8px;padding:16px;margin:20px 0;">
                <p style="margin:4px 0;font-size:14px;"><strong>$emailLabel:</strong> $safeEmail</p>
                <p style="margin:4px 0;font-size:14px;"><strong>$passLabel:</strong> <code style="background:#e9ecef;padding:2px 6px;border-radius:4px;">$safePass</code></p>
            </div>
            <div style="text-align:center;margin:32px 0;">
                <a href="$loginUrl" style="display:inline-block;background:#0d6efd;color:#fff;padding:14px 36px;border-radius:8px;text-decoration:none;font-size:16px;font-weight:bold;">$btnText</a>
            </div>
            <p style="font-size:13px;color:#888;text-align:center;">$changePass</p>
        </div>
    </div>
</body>
</html>
HTML;

    return sendMail($email, $subject, $body);
}

/**
 * Send welcome email to a professor with login credentials
 *
 * @param string $email    Professor email
 * @param string $name     Professor name
 * @param string $password Plain-text password (sent once)
 * @param string $lang     Language code (ar/en/de)
 * @return bool
 */
function sendProfessorWelcomeEmail(string $email, string $name, string $password, string $lang = 'ar'): bool {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8642';
    $loginUrl = "$protocol://$host/login";

    if ($lang === 'de') {
        $subject = 'Neues Professorenkonto - Abschlussprojekt-System';
        $heading = 'Willkommen im Abschlussprojekt-System';
        $greeting = "Hallo $name,";
        $message = 'Ein Professorenkonto wurde für Sie im Abschlussprojekt-Verwaltungssystem erstellt. Sie können sich mit den folgenden Zugangsdaten anmelden:';
        $emailLabel = 'E-Mail';
        $passLabel = 'Passwort';
        $btnText = 'Anmelden';
        $changePass = 'Bitte ändern Sie Ihr Passwort nach der ersten Anmeldung.';
        $dir = 'ltr';
    } elseif ($lang === 'en') {
        $subject = 'New Professor Account - Graduation Project System';
        $heading = 'Welcome to the Graduation Project System';
        $greeting = "Hello $name,";
        $message = 'A professor account has been created for you in the Graduation Project Management System. You can log in using the following credentials:';
        $emailLabel = 'Email';
        $passLabel = 'Password';
        $btnText = 'Log In';
        $changePass = 'Please change your password after your first login.';
        $dir = 'ltr';
    } else {
        $subject = 'حساب أستاذ جديد - نظام مشاريع التخرج';
        $heading = 'مرحباً بك في نظام مشاريع التخرج';
        $greeting = "مرحباً $name،";
        $message = 'تم إنشاء حساب أستاذ لك في نظام إدارة مشاريع التخرج. يمكنك تسجيل الدخول باستخدام البيانات التالية:';
        $emailLabel = 'البريد الإلكتروني';
        $passLabel = 'كلمة المرور';
        $btnText = 'تسجيل الدخول';
        $changePass = 'يرجى تغيير كلمة المرور بعد تسجيل الدخول الأول.';
        $dir = 'rtl';
    }

    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safePass = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');

    $body = <<<HTML
<!DOCTYPE html>
<html dir="$dir" lang="$lang">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background:#f4f6f9;">
    <div style="max-width:520px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">
        <div style="background:#6f42c1;padding:24px;text-align:center;">
            <h1 style="margin:0;color:#fff;font-size:22px;">🎓 $heading</h1>
        </div>
        <div style="padding:28px 32px;">
            <p style="font-size:16px;color:#333;">$greeting</p>
            <p style="font-size:15px;color:#555;line-height:1.6;">$message</p>
            <div style="background:#f8f9fa;border-radius:8px;padding:16px;margin:20px 0;">
                <p style="margin:4px 0;font-size:14px;"><strong>$emailLabel:</strong> $safeEmail</p>
                <p style="margin:4px 0;font-size:14px;"><strong>$passLabel:</strong> <code style="background:#e9ecef;padding:2px 6px;border-radius:4px;">$safePass</code></p>
            </div>
            <div style="text-align:center;margin:32px 0;">
                <a href="$loginUrl" style="display:inline-block;background:#6f42c1;color:#fff;padding:14px 36px;border-radius:8px;text-decoration:none;font-size:16px;font-weight:bold;">$btnText</a>
            </div>
            <p style="font-size:13px;color:#888;text-align:center;">$changePass</p>
        </div>
    </div>
</body>
</html>
HTML;

    return sendMail($email, $subject, $body);
}