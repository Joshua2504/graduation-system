<?php
/**
 * Language / i18n support — Arabic + English + German (Deutsch)
 */

$allLangs = ['ar', 'en', 'de'];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if demo mode is active (inline check to avoid dependency on demo.php)
$_isDemoMode = filter_var($_ENV['DEMO_MODE'] ?? getenv('DEMO_MODE') ?: 'false', FILTER_VALIDATE_BOOLEAN);

// Load enabled languages from settings (DB)
$enabledLangsStr = null;
$defaultLangFromDB = null;
try {
    if (function_exists('getDB')) {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT enabled_languages, default_language FROM settings WHERE id = 1");
        $row = $stmt->fetch();
        if ($row && !empty($row['enabled_languages'])) {
            $enabledLangsStr = $row['enabled_languages'];
        }
        if ($row && !empty($row['default_language'])) {
            $defaultLangFromDB = $row['default_language'];
        }
    }
} catch (Exception $e) {
    // DB not ready yet, use fallback
}

// When demo mode is enabled, fall back to all languages; otherwise only Arabic
$defaultLangs = $_isDemoMode ? $allLangs : ['ar'];
$supportedLangs = $enabledLangsStr
    ? array_values(array_filter(explode(',', $enabledLangsStr), fn($l) => in_array($l, $allLangs)))
    : $defaultLangs;
if (empty($supportedLangs)) $supportedLangs = ['ar'];

// Determine default language: use DB setting if enabled, otherwise first enabled language
$defaultLang = ($defaultLangFromDB && in_array($defaultLangFromDB, $supportedLangs))
    ? $defaultLangFromDB
    : $supportedLangs[0];

// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLangs)) {
    $_SESSION['lang'] = $_GET['lang'];
}

// If current session language is no longer enabled, reset it
if (isset($_SESSION['lang']) && !in_array($_SESSION['lang'], $supportedLangs)) {
    unset($_SESSION['lang']);
}

// Detect browser language if no session language is set
if (!isset($_SESSION['lang'])) {
    $browserLang = $defaultLang; // fallback to default language from settings
    if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $acceptLang = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $bestPos = PHP_INT_MAX;
        foreach ($supportedLangs as $lang) {
            $pos = strpos($acceptLang, $lang);
            if ($pos !== false && $pos < $bestPos) {
                $bestPos = $pos;
                $browserLang = $lang;
            }
        }
    }
    $_SESSION['lang'] = $browserLang;
}

$currentLang = $_SESSION['lang'];

$translations = [
    // General
    'app_name' => ['ar' => 'نظام إدارة مشاريع التخرج', 'en' => 'Graduation Project Management System', 'de' => 'Abschlussprojekt-Verwaltungssystem'],
    'login' => ['ar' => 'تسجيل الدخول', 'en' => 'Login', 'de' => 'Anmelden'],
    'register' => ['ar' => 'إنشاء حساب', 'en' => 'Register', 'de' => 'Registrieren'],
    'logout' => ['ar' => 'تسجيل الخروج', 'en' => 'Logout', 'de' => 'Abmelden'],
    'dashboard' => ['ar' => 'لوحة التحكم', 'en' => 'Dashboard', 'de' => 'Dashboard'],
    'submit' => ['ar' => 'إرسال', 'en' => 'Submit', 'de' => 'Absenden'],
    'save' => ['ar' => 'حفظ', 'en' => 'Save', 'de' => 'Speichern'],
    'next' => ['ar' => 'التالي', 'en' => 'Next', 'de' => 'Weiter'],
    'previous' => ['ar' => 'السابق', 'en' => 'Previous', 'de' => 'Zurück'],
    'cancel' => ['ar' => 'إلغاء', 'en' => 'Cancel', 'de' => 'Abbrechen'],
    'close' => ['ar' => 'إغلاق', 'en' => 'Close', 'de' => 'Schließen'],
    'yes' => ['ar' => 'نعم', 'en' => 'Yes', 'de' => 'Ja'],
    'no' => ['ar' => 'لا', 'en' => 'No', 'de' => 'Nein'],
    'loading' => ['ar' => 'جاري التحميل...', 'en' => 'Loading...', 'de' => 'Wird geladen...'],
    'error' => ['ar' => 'خطأ', 'en' => 'Error', 'de' => 'Fehler'],
    'success' => ['ar' => 'نجاح', 'en' => 'Success', 'de' => 'Erfolg'],
    'internal_error' => ['ar' => 'حدث خطأ داخلي، يرجى المحاولة مرة أخرى', 'en' => 'An internal error occurred, please try again', 'de' => 'Ein interner Fehler ist aufgetreten, bitte versuchen Sie es erneut'],
    'required_field' => ['ar' => 'هذا الحقل مطلوب', 'en' => 'This field is required', 'de' => 'Dieses Feld ist erforderlich'],
    'settings' => ['ar' => 'الإعدادات', 'en' => 'Settings', 'de' => 'Einstellungen'],
    'actions' => ['ar' => 'الإجراءات', 'en' => 'Actions', 'de' => 'Aktionen'],
    'confirm' => ['ar' => 'تأكيد', 'en' => 'Confirm', 'de' => 'Bestätigen'],
    'delete' => ['ar' => 'حذف', 'en' => 'Delete', 'de' => 'Löschen'],
    'search' => ['ar' => 'بحث', 'en' => 'Search', 'de' => 'Suchen'],

    // Auth
    'email' => ['ar' => 'البريد الإلكتروني', 'en' => 'Email Address', 'de' => 'E-Mail-Adresse'],
    'password' => ['ar' => 'كلمة المرور', 'en' => 'Password', 'de' => 'Passwort'],
    'name' => ['ar' => 'الاسم', 'en' => 'Name', 'de' => 'Name'],
    'student_code' => ['ar' => 'كود الطالب', 'en' => 'Student Code', 'de' => 'Matrikelnummer'],
    'login_title' => ['ar' => 'تسجيل الدخول', 'en' => 'Sign In', 'de' => 'Anmelden'],
    'register_title' => ['ar' => 'تسجيل حساب جديد', 'en' => 'Create Account', 'de' => 'Konto erstellen'],
    'no_account' => ['ar' => 'ليس لديك حساب؟', 'en' => "Don't have an account?", 'de' => 'Noch kein Konto?'],
    'has_account' => ['ar' => 'لديك حساب بالفعل؟', 'en' => 'Already have an account?', 'de' => 'Bereits ein Konto?'],

    // Initial setup (first user becomes admin)
    'initial_setup_title' => ['ar' => 'إعداد النظام', 'en' => 'System Setup', 'de' => 'Systemeinrichtung'],
    'initial_setup_description' => ['ar' => 'أنشئ حساب مدير النظام الأول لبدء استخدام النظام', 'en' => 'Create the first admin account to start using the system', 'de' => 'Erstellen Sie das erste Administratorkonto, um das System zu nutzen'],
    'initial_setup_submit' => ['ar' => 'إنشاء حساب المدير', 'en' => 'Create Admin Account', 'de' => 'Administratorkonto erstellen'],
    'initial_setup_redirect' => ['ar' => 'يرجى إنشاء حساب المدير أولاً', 'en' => 'Please create the admin account first', 'de' => 'Bitte erstellen Sie zuerst das Administratorkonto'],

    'registration_closed' => ['ar' => 'تسجيل مشاريع التخرج مغلق حالياً', 'en' => 'Graduation project registration is currently closed', 'de' => 'Die Registrierung für Abschlussprojekte ist derzeit geschlossen'],
    'invalid_credentials' => ['ar' => 'بيانات الدخول غير صحيحة', 'en' => 'Invalid email/student code or password', 'de' => 'Ungültige E-Mail/Matrikelnummer oder Passwort'],
    'email_or_code' => ['ar' => 'البريد الإلكتروني أو كود الطالب', 'en' => 'Email or Student Code', 'de' => 'E-Mail oder Matrikelnummer'],
    'email_or_code_placeholder' => ['ar' => 'أدخل بريدك الإلكتروني أو كود الطالب', 'en' => 'Enter your email or student code', 'de' => 'E-Mail oder Matrikelnummer eingeben'],
    'email_exists' => ['ar' => 'البريد الإلكتروني مسجل بالفعل', 'en' => 'Email already registered', 'de' => 'E-Mail bereits registriert'],
    'code_exists' => ['ar' => 'كود الطالب مسجل بالفعل', 'en' => 'Student code already registered', 'de' => 'Matrikelnummer bereits registriert'],
    'verification_sent' => ['ar' => 'تم إرسال رابط التأكيد إلى بريدك الإلكتروني. يرجى التحقق من صندوق الوارد لتفعيل حسابك.', 'en' => 'A verification link has been sent to your email. Please check your inbox to activate your account.', 'de' => 'Ein Bestätigungslink wurde an Ihre E-Mail gesendet. Bitte überprüfen Sie Ihren Posteingang, um Ihr Konto zu aktivieren.'],
    'verification_sent_fallback' => ['ar' => 'تم إنشاء حسابك. يرجى تسجيل الدخول وطلب إعادة إرسال رابط التأكيد.', 'en' => 'Your account has been created. Please login and request a new verification link.', 'de' => 'Ihr Konto wurde erstellt. Bitte melden Sie sich an und fordern Sie einen neuen Bestätigungslink an.'],
    'email_not_verified' => ['ar' => 'لم يتم تأكيد بريدك الإلكتروني بعد. يرجى التحقق من بريدك الإلكتروني.', 'en' => 'Your email has not been verified yet. Please check your email.', 'de' => 'Ihre E-Mail wurde noch nicht bestätigt. Bitte überprüfen Sie Ihre E-Mail.'],
    'resend_verification' => ['ar' => 'إعادة إرسال رابط التأكيد', 'en' => 'Resend Verification Link', 'de' => 'Bestätigungslink erneut senden'],
    'verification_resent' => ['ar' => 'تم إعادة إرسال رابط التأكيد إلى بريدك الإلكتروني.', 'en' => 'Verification link has been resent to your email.', 'de' => 'Der Bestätigungslink wurde erneut an Ihre E-Mail gesendet.'],
    'email_verified_success' => ['ar' => 'تم تأكيد بريدك الإلكتروني بنجاح! يمكنك الآن تسجيل الدخول.', 'en' => 'Your email has been verified successfully! You can now login.', 'de' => 'Ihre E-Mail wurde erfolgreich bestätigt! Sie können sich jetzt anmelden.'],
    'verification_invalid' => ['ar' => 'رابط التأكيد غير صالح أو منتهي الصلاحية.', 'en' => 'Verification link is invalid or has expired.', 'de' => 'Der Bestätigungslink ist ungültig oder abgelaufen.'],
    'verification_already' => ['ar' => 'تم تأكيد بريدك الإلكتروني مسبقاً.', 'en' => 'Your email has already been verified.', 'de' => 'Ihre E-Mail wurde bereits bestätigt.'],

    // Password reset
    'forgot_password' => ['ar' => 'نسيت كلمة المرور؟', 'en' => 'Forgot password?', 'de' => 'Passwort vergessen?'],
    'forgot_password_title' => ['ar' => 'استعادة كلمة المرور', 'en' => 'Reset Password', 'de' => 'Passwort zurücksetzen'],
    'forgot_password_desc' => ['ar' => 'أدخل بريدك الإلكتروني وسنرسل لك رابطاً لإعادة تعيين كلمة المرور.', 'en' => 'Enter your email address and we will send you a link to reset your password.', 'de' => 'Geben Sie Ihre E-Mail-Adresse ein und wir senden Ihnen einen Link zum Zurücksetzen Ihres Passworts.'],
    'forgot_password_submit' => ['ar' => 'إرسال رابط الاسترداد', 'en' => 'Send Reset Link', 'de' => 'Link senden'],
    'invalid_email_format' => ['ar' => 'يرجى إدخال بريد إلكتروني صحيح.', 'en' => 'Please enter a valid email address.', 'de' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.'],
    'password_reset_sent' => ['ar' => 'إذا كان البريد الإلكتروني مسجلاً في النظام، ستتلقى رابط إعادة التعيين خلال دقائق.', 'en' => 'If this email is registered in the system, you will receive a reset link within a few minutes.', 'de' => 'Wenn diese E-Mail im System registriert ist, erhalten Sie in wenigen Minuten einen Link zum Zurücksetzen.'],
    'reset_password_title' => ['ar' => 'تعيين كلمة مرور جديدة', 'en' => 'Set New Password', 'de' => 'Neues Passwort festlegen'],
    'reset_password_desc' => ['ar' => 'أدخل كلمة المرور الجديدة أدناه.', 'en' => 'Enter your new password below.', 'de' => 'Geben Sie unten Ihr neues Passwort ein.'],
    'confirm_new_password' => ['ar' => 'تأكيد كلمة المرور الجديدة', 'en' => 'Confirm New Password', 'de' => 'Neues Passwort bestätigen'],
    'reset_password_submit' => ['ar' => 'تعيين كلمة المرور', 'en' => 'Set Password', 'de' => 'Passwort festlegen'],
    'password_reset_invalid' => ['ar' => 'رابط إعادة التعيين غير صالح أو منتهي الصلاحية.', 'en' => 'The reset link is invalid or has expired.', 'de' => 'Der Zurücksetzungslink ist ungültig oder abgelaufen.'],
    'passwords_do_not_match' => ['ar' => 'كلمتا المرور غير متطابقتين.', 'en' => 'Passwords do not match.', 'de' => 'Die Passwörter stimmen nicht überein.'],
    'password_too_short' => ['ar' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.', 'en' => 'Password must be at least 6 characters.', 'de' => 'Das Passwort muss mindestens 6 Zeichen lang sein.'],
    'back_to_login' => ['ar' => 'العودة لتسجيل الدخول', 'en' => 'Back to Login', 'de' => 'Zurück zur Anmeldung'],

    // Profile
    'my_profile' => ['ar' => 'ملفي الشخصي', 'en' => 'My Profile', 'de' => 'Mein Profil'],
    'profile_incomplete' => ['ar' => 'الملف الشخصي غير مكتمل', 'en' => 'Profile Incomplete', 'de' => 'Profil unvollständig'],
    'profile_complete' => ['ar' => 'الملف الشخصي مكتمل', 'en' => 'Profile Complete', 'de' => 'Profil vollständig'],
    'complete_profile' => ['ar' => 'أكمل ملفك الشخصي', 'en' => 'Complete Your Profile', 'de' => 'Profil vervollständigen'],
    'profile_info' => ['ar' => 'بيانات الملف الشخصي تُستخدم في جميع المشاريع التي تنضم إليها', 'en' => 'Profile data is used across all projects you join', 'de' => 'Profildaten werden in allen Projekten verwendet, denen Sie beitreten'],
    'professor_profile_info' => ['ar' => 'يمكنك تعديل بياناتك الشخصية وصورتك من هنا', 'en' => 'You can edit your personal information and photo here', 'de' => 'Hier können Sie Ihre persönlichen Daten und Ihr Foto bearbeiten'],
    'personal_info' => ['ar' => 'البيانات الشخصية', 'en' => 'Personal Information', 'de' => 'Persönliche Daten'],
    'documents' => ['ar' => 'المستندات', 'en' => 'Documents', 'de' => 'Dokumente'],

    // Project
    'project_name' => ['ar' => 'اسم المشروع', 'en' => 'Project Name', 'de' => 'Projektname'],
    'project_type' => ['ar' => 'نوع المشروع', 'en' => 'Project Type', 'de' => 'Projekttyp'],
    'project_details' => ['ar' => 'تفاصيل المشروع', 'en' => 'Project Details', 'de' => 'Projektdetails'],
    'create_project' => ['ar' => 'إنشاء مشروع', 'en' => 'Create Project', 'de' => 'Projekt erstellen'],
    'my_projects' => ['ar' => 'مشاريعي', 'en' => 'My Projects', 'de' => 'Meine Projekte'],
    'no_projects_yet' => ['ar' => 'لا توجد مشاريع بعد', 'en' => 'No projects yet', 'de' => 'Noch keine Projekte'],

    // Student fields
    'gender' => ['ar' => 'الجنس', 'en' => 'Gender', 'de' => 'Geschlecht'],
    'male' => ['ar' => 'ذكر', 'en' => 'Male', 'de' => 'Männlich'],
    'female' => ['ar' => 'أنثى', 'en' => 'Female', 'de' => 'Weiblich'],
    'national_id' => ['ar' => 'الرقم القومي', 'en' => 'National ID Number', 'de' => 'Personalausweisnummer'],
    'birth_date' => ['ar' => 'تاريخ الميلاد', 'en' => 'Date of Birth', 'de' => 'Geburtsdatum'],
    'governorate' => ['ar' => 'المحافظة', 'en' => 'Governorate', 'de' => 'Gouvernement'],
    'address' => ['ar' => 'العنوان', 'en' => 'Address', 'de' => 'Adresse'],
    'phone' => ['ar' => 'رقم الهاتف', 'en' => 'Phone Number', 'de' => 'Telefonnummer'],
    'year' => ['ar' => 'السنة الدراسية', 'en' => 'Year', 'de' => 'Studienjahr'],
    'section' => ['ar' => 'القسم', 'en' => 'Department', 'de' => 'Abteilung'],
    'first_year' => ['ar' => 'الأولى', 'en' => '1st', 'de' => '1.'],
    'second_year' => ['ar' => 'الثانية', 'en' => '2nd', 'de' => '2.'],
    'third_year' => ['ar' => 'الثالثة', 'en' => '3rd', 'de' => '3.'],
    'fourth_year' => ['ar' => 'الرابعة', 'en' => '4th', 'de' => '4.'],

    // Images
    'card_image' => ['ar' => 'صورة بطاقة المعهد', 'en' => 'Institute ID Card', 'de' => 'Institutsausweis'],
    'national_id_image' => ['ar' => 'صورة البطاقة الشخصية', 'en' => 'National ID Card', 'de' => 'Personalausweis'],
    'receipt_image' => ['ar' => 'صورة إيصال دفع مشروع التخرج', 'en' => 'Graduation Project Payment Receipt', 'de' => 'Zahlungsbeleg für Abschlussprojekt'],
    'profile_picture' => ['ar' => 'صورة شخصية', 'en' => 'Profile Picture', 'de' => 'Profilbild'],
    'profile_picture_hint' => ['ar' => 'صورة شخصية اختيارية تظهر في جميع أنحاء المنصة', 'en' => 'Optional profile picture displayed across the platform', 'de' => 'Optionales Profilbild, das auf der gesamten Plattform angezeigt wird'],
    'upload_image' => ['ar' => 'رفع صورة', 'en' => 'Upload Image', 'de' => 'Bild hochladen'],
    'image_requirements' => ['ar' => 'JPG أو PNG فقط - الحد الأقصى 5 ميجابايت', 'en' => 'JPG or PNG only - Max 5MB', 'de' => 'Nur JPG oder PNG – Max. 5 MB'],
    'uploading' => ['ar' => 'جاري رفع الملفات...', 'en' => 'Uploading files...', 'de' => 'Dateien werden hochgeladen...'],

    // Status
    'status_draft' => ['ar' => 'مسودة', 'en' => 'Draft', 'de' => 'Entwurf'],
    'status_under_review' => ['ar' => 'قيد المراجعة', 'en' => 'Under Review', 'de' => 'In Prüfung'],
    'status_accepted' => ['ar' => 'مقبول', 'en' => 'Accepted', 'de' => 'Angenommen'],
    'status_rejected' => ['ar' => 'مرفوض', 'en' => 'Rejected', 'de' => 'Abgelehnt'],

    // Team & Invitations
    'team_members' => ['ar' => 'أعضاء الفريق', 'en' => 'Team Members', 'de' => 'Teammitglieder'],
    'member_count' => ['ar' => 'عدد الأعضاء', 'en' => 'Member Count', 'de' => 'Mitgliederanzahl'],
    'invite_members' => ['ar' => 'دعوة أعضاء', 'en' => 'Invite Members', 'de' => 'Mitglieder einladen'],
    'join_project' => ['ar' => 'الانضمام لمشروع', 'en' => 'Join Project', 'de' => 'Projekt beitreten'],
    'join_code' => ['ar' => 'كود الانضمام', 'en' => 'Join Code', 'de' => 'Beitrittscode'],
    'invite_link' => ['ar' => 'رابط الدعوة', 'en' => 'Invite Link', 'de' => 'Einladungslink'],
    'qr_code' => ['ar' => 'رمز QR', 'en' => 'QR Code', 'de' => 'QR-Code'],
    'invite_by_search' => ['ar' => 'دعوة بالبريد أو الكود', 'en' => 'Invite by Email or Code', 'de' => 'Per E-Mail oder Code einladen'],
    'send_invite' => ['ar' => 'إرسال دعوة', 'en' => 'Send Invite', 'de' => 'Einladung senden'],
    'pending_invitations' => ['ar' => 'الدعوات المعلقة', 'en' => 'Pending Invitations', 'de' => 'Ausstehende Einladungen'],
    'accept_invite' => ['ar' => 'قبول', 'en' => 'Accept', 'de' => 'Annehmen'],
    'decline_invite' => ['ar' => 'رفض', 'en' => 'Decline', 'de' => 'Ablehnen'],
    'leave_project' => ['ar' => 'مغادرة المشروع', 'en' => 'Leave Project', 'de' => 'Projekt verlassen'],
    'remove_member' => ['ar' => 'إزالة العضو', 'en' => 'Remove Member', 'de' => 'Mitglied entfernen'],
    'generate_link' => ['ar' => 'إنشاء رابط', 'en' => 'Generate Link', 'de' => 'Link generieren'],
    'copy_link' => ['ar' => 'نسخ الرابط', 'en' => 'Copy Link', 'de' => 'Link kopieren'],
    'link_copied' => ['ar' => 'تم نسخ الرابط', 'en' => 'Link Copied!', 'de' => 'Link kopiert!'],
    'enter_join_code' => ['ar' => 'أدخل كود الانضمام', 'en' => 'Enter Join Code', 'de' => 'Beitrittscode eingeben'],
    'project_full' => ['ar' => 'الفريق مكتمل العدد', 'en' => 'Team is full', 'de' => 'Team ist voll'],
    'already_member' => ['ar' => 'أنت عضو بالفعل', 'en' => 'Already a member', 'de' => 'Bereits Mitglied'],
    'invitation_expired' => ['ar' => 'الدعوة منتهية الصلاحية', 'en' => 'Invitation expired', 'de' => 'Einladung abgelaufen'],
    'invitation_sent' => ['ar' => 'تم إرسال الدعوة', 'en' => 'Invitation sent', 'de' => 'Einladung gesendet'],
    'min_members' => ['ar' => 'الحد الأدنى للأعضاء', 'en' => 'Minimum Members', 'de' => 'Mindestmitglieder'],
    'max_members' => ['ar' => 'الحد الأقصى للأعضاء', 'en' => 'Maximum Members', 'de' => 'Maximale Mitglieder'],
    'team_size' => ['ar' => 'حجم الفريق', 'en' => 'Team Size', 'de' => 'Teamgröße'],
    'all_profiles_complete' => ['ar' => 'جميع الملفات مكتملة', 'en' => 'All profiles complete', 'de' => 'Alle Profile vollständig'],
    'profiles_incomplete' => ['ar' => 'بعض الملفات غير مكتملة', 'en' => 'Some profiles incomplete', 'de' => 'Einige Profile unvollständig'],
    'leader' => ['ar' => 'قائد', 'en' => 'Leader', 'de' => 'Leiter'],
    'member' => ['ar' => 'عضو', 'en' => 'Member', 'de' => 'Mitglied'],

    // Student dashboard
    'project_submitted' => ['ar' => 'تم تقديم مشروعك بنجاح وهو الآن قيد المراجعة. يرجى التحقق خلال 24 ساعة.', 'en' => 'Your project has been successfully submitted and is under review. Please check back within 24 hours.', 'de' => 'Ihr Projekt wurde erfolgreich eingereicht und wird geprüft. Bitte schauen Sie innerhalb von 24 Stunden wieder vorbei.'],
    'project_accepted_msg' => ['ar' => 'تم قبول مشروعك. يرجى المتابعة مع مدرس المادة في الجامعة.', 'en' => 'Your project has been accepted. Please continue with the course instructor at the university.', 'de' => 'Ihr Projekt wurde angenommen. Bitte fahren Sie mit dem Kursleiter an der Universität fort.'],
    'project_rejected_msg' => ['ar' => 'تم رفض مشروعك. يمكنك تعديل البيانات وإعادة التقديم.', 'en' => 'Your project has been rejected. You can edit your data and resubmit.', 'de' => 'Ihr Projekt wurde abgelehnt. Sie können Ihre Daten bearbeiten und erneut einreichen.'],
    'group_number' => ['ar' => 'رقم المجموعة', 'en' => 'Group Number', 'de' => 'Gruppennummer'],
    'doctor_note' => ['ar' => 'ملاحظة الدكتور', 'en' => "Professor's Note", 'de' => 'Anmerkung des Professors'],
    'submit_project' => ['ar' => 'تقديم المشروع', 'en' => 'Submit Project', 'de' => 'Projekt einreichen'],
    'confirm_submit' => ['ar' => 'تأكيد: سيتم تقديم المشروع للمراجعة. هل أنت متأكد؟', 'en' => 'Confirm: The project will be submitted for review. Are you sure?', 'de' => 'Bestätigung: Das Projekt wird zur Prüfung eingereicht. Sind Sie sicher?'],
    'edit_resubmit' => ['ar' => 'إعادة التقديم', 'en' => 'Resubmit', 'de' => 'Erneut einreichen'],

    // Professor
    'projects_under_review' => ['ar' => 'مشاريع قيد المراجعة', 'en' => 'Projects Under Review', 'de' => 'Projekte in Prüfung'],
    'draft_projects' => ['ar' => 'مشاريع مسودة', 'en' => 'Draft Projects', 'de' => 'Entwurfsprojekte'],
    'accepted_projects' => ['ar' => 'المشاريع المقبولة', 'en' => 'Accepted Projects', 'de' => 'Angenommene Projekte'],
    'rejected_projects' => ['ar' => 'المشاريع المرفوضة', 'en' => 'Rejected Projects', 'de' => 'Abgelehnte Projekte'],
    'sort_recent' => ['ar' => 'الأحدث', 'en' => 'Most Recent', 'de' => 'Neueste'],
    'sort_oldest' => ['ar' => 'الأقدم', 'en' => 'Oldest', 'de' => 'Älteste'],
    'accept' => ['ar' => 'قبول', 'en' => 'Accept', 'de' => 'Annehmen'],
    'reject' => ['ar' => 'رفض', 'en' => 'Reject', 'de' => 'Ablehnen'],
    'write_note' => ['ar' => 'اكتب ملاحظة (اختياري)', 'en' => 'Write a note (optional)', 'de' => 'Notiz schreiben (optional)'],
    'view_project' => ['ar' => 'عرض المشروع', 'en' => 'View Project', 'de' => 'Projekt anzeigen'],
    'duplicate_warning' => ['ar' => '⚠ تنبيه: يوجد مشروع آخر بنفس الاسم', 'en' => '⚠ Warning: Another project with the same name exists', 'de' => '⚠ Warnung: Ein anderes Projekt mit demselben Namen existiert bereits'],
    'view_similar' => ['ar' => 'عرض المشروع المشابه', 'en' => 'View Similar Project', 'de' => 'Ähnliches Projekt anzeigen'],

    'toggle_registration' => ['ar' => 'تبديل حالة التسجيل', 'en' => 'Toggle Registration', 'de' => 'Registrierung umschalten'],
    'toggle_email_verification' => ['ar' => 'تأكيد البريد الإلكتروني', 'en' => 'Email Verification', 'de' => 'E-Mail-Bestätigung'],
    'student_accounts' => ['ar' => 'الطلاب', 'en' => 'Students', 'de' => 'Studenten'],
    'account_disabled' => ['ar' => 'تم تعطيل حسابك. يرجى التواصل مع المسؤول.', 'en' => 'Your account has been disabled. Please contact the administrator.', 'de' => 'Ihr Konto wurde deaktiviert. Bitte kontaktieren Sie den Administrator.'],
    'no_projects' => ['ar' => 'لا توجد مشاريع', 'en' => 'No projects found', 'de' => 'Keine Projekte gefunden'],
    'team_leader' => ['ar' => 'قائد الفريق', 'en' => 'Team Leader', 'de' => 'Teamleiter'],
    'submission_date' => ['ar' => 'تاريخ التقديم', 'en' => 'Submission Date', 'de' => 'Einreichungsdatum'],

    // Professor — Students Page
    'add_student' => ['ar' => 'إضافة طالب', 'en' => 'Add Student', 'de' => 'Student hinzufügen'],
    'students_count' => ['ar' => 'طالب', 'en' => 'students', 'de' => 'Studenten'],
    'search_placeholder' => ['ar' => 'بحث...', 'en' => 'Search...', 'de' => 'Suchen...'],
    'no_registered_students' => ['ar' => 'لا يوجد طلاب مسجلين', 'en' => 'No registered students', 'de' => 'Keine registrierten Studenten'],
    'profile' => ['ar' => 'الملف الشخصي', 'en' => 'Profile', 'de' => 'Profil'],
    'email_col' => ['ar' => 'البريد', 'en' => 'Email', 'de' => 'E-Mail'],
    'account' => ['ar' => 'الحساب', 'en' => 'Account', 'de' => 'Konto'],
    'registered' => ['ar' => 'تاريخ التسجيل', 'en' => 'Registered', 'de' => 'Registriert'],
    'verified' => ['ar' => 'مؤكد', 'en' => 'Verified', 'de' => 'Bestätigt'],
    'unverified' => ['ar' => 'غير مؤكد', 'en' => 'Unverified', 'de' => 'Nicht bestätigt'],
    'active' => ['ar' => 'نشط', 'en' => 'Active', 'de' => 'Aktiv'],
    'disabled' => ['ar' => 'معطل', 'en' => 'Disabled', 'de' => 'Deaktiviert'],
    'edit_profile' => ['ar' => 'تعديل الملف الشخصي', 'en' => 'Edit Profile', 'de' => 'Profil bearbeiten'],
    'verify_email' => ['ar' => 'تأكيد البريد', 'en' => 'Verify Email', 'de' => 'E-Mail bestätigen'],
    'resend_verification_email' => ['ar' => 'إعادة إرسال رابط التأكيد', 'en' => 'Resend Verification Email', 'de' => 'Bestätigungs-E-Mail erneut senden'],
    'unverify' => ['ar' => 'إلغاء التأكيد', 'en' => 'Unverify', 'de' => 'Bestätigung aufheben'],
    'disable_account' => ['ar' => 'تعطيل الحساب', 'en' => 'Disable Account', 'de' => 'Konto deaktivieren'],
    'enable_account' => ['ar' => 'تفعيل الحساب', 'en' => 'Enable Account', 'de' => 'Konto aktivieren'],
    'login_as_student' => ['ar' => 'الدخول كطالب', 'en' => 'Login as Student', 'de' => 'Als Student anmelden'],
    'edit_student_profile' => ['ar' => 'تعديل بيانات الطالب', 'en' => 'Edit Student Profile', 'de' => 'Studentenprofil bearbeiten'],
    'basic_info' => ['ar' => 'البيانات الأساسية', 'en' => 'Basic Info', 'de' => 'Grunddaten'],
    'select_option' => ['ar' => 'اختر', 'en' => 'Select', 'de' => 'Auswählen'],
    'select_governorate' => ['ar' => 'اختر المحافظة', 'en' => 'Select Governorate', 'de' => 'Gouvernement auswählen'],
    'no_image' => ['ar' => 'لا توجد صورة', 'en' => 'No image', 'de' => 'Kein Bild'],
    'institute_id' => ['ar' => 'بطاقة المعهد', 'en' => 'Institute ID', 'de' => 'Institutsausweis'],
    'national_id_card' => ['ar' => 'البطاقة الشخصية', 'en' => 'National ID', 'de' => 'Personalausweis'],
    'payment_receipt' => ['ar' => 'إيصال الدفع', 'en' => 'Payment Receipt', 'de' => 'Zahlungsbeleg'],
    'add_new_student' => ['ar' => 'إضافة طالب جديد', 'en' => 'Add New Student', 'de' => 'Neuen Studenten hinzufügen'],
    'generate_password' => ['ar' => 'توليد كلمة مرور', 'en' => 'Generate password', 'de' => 'Passwort generieren'],
    'send_welcome_email' => ['ar' => 'إرسال بريد ترحيبي بالبيانات', 'en' => 'Send welcome email with credentials', 'de' => 'Willkommens-E-Mail mit Zugangsdaten senden'],
    'create_account' => ['ar' => 'إنشاء الحساب', 'en' => 'Create Account', 'de' => 'Konto erstellen'],
    'confirm_verify_email' => ['ar' => 'تأكيد بريد هذا الطالب؟', 'en' => "Verify this student's email?", 'de' => 'E-Mail dieses Studenten bestätigen?'],
    'confirm_unverify_email' => ['ar' => 'إلغاء تأكيد بريد هذا الطالب؟', 'en' => "Unverify this student's email?", 'de' => 'E-Mail-Bestätigung dieses Studenten aufheben?'],
    'confirm_enable_account' => ['ar' => 'تفعيل حساب هذا الطالب؟', 'en' => "Enable this student's account?", 'de' => 'Konto dieses Studenten aktivieren?'],
    'confirm_disable_account' => ['ar' => 'تعطيل حساب هذا الطالب؟', 'en' => "Disable this student's account?", 'de' => 'Konto dieses Studenten deaktivieren?'],
    'confirm_delete_account' => ['ar' => 'حذف هذا الحساب نهائياً؟ سيتم حذف جميع بياناته.', 'en' => 'Permanently delete this account? All data will be removed.', 'de' => 'Dieses Konto endgültig löschen? Alle Daten werden entfernt.'],
    'confirm_resend_verification' => ['ar' => 'إعادة إرسال رابط التأكيد؟', 'en' => 'Resend verification email?', 'de' => 'Bestätigungs-E-Mail erneut senden?'],
    'confirm_impersonate' => ['ar' => 'الدخول كهذا الطالب؟', 'en' => 'Login as this student?', 'de' => 'Als dieser Student anmelden?'],
    'name_email_password_required' => ['ar' => 'الاسم والبريد وكلمة المرور مطلوبين', 'en' => 'Name, email and password are required', 'de' => 'Name, E-Mail und Passwort sind erforderlich'],
    'email_sent' => ['ar' => 'تم إرسال البريد', 'en' => 'Email sent', 'de' => 'E-Mail gesendet'],

    // Professor — Project Page
    'back_to_list' => ['ar' => 'العودة للقائمة', 'en' => 'Back to List', 'de' => 'Zurück zur Liste'],
    'pending' => ['ar' => 'دعوات معلقة', 'en' => 'pending', 'de' => 'ausstehend'],
    'student' => ['ar' => 'الطالب', 'en' => 'Student', 'de' => 'Student'],
    'missing' => ['ar' => 'غير متوفرة', 'en' => 'Missing', 'de' => 'Fehlend'],
    'invited_student' => ['ar' => 'الطالب المدعو', 'en' => 'Invited Student', 'de' => 'Eingeladener Student'],
    'code' => ['ar' => 'الكود', 'en' => 'Code', 'de' => 'Code'],
    'invited_by' => ['ar' => 'بواسطة', 'en' => 'Invited By', 'de' => 'Eingeladen von'],
    'invited_at' => ['ar' => 'تاريخ الدعوة', 'en' => 'Invited At', 'de' => 'Eingeladen am'],
    'expires' => ['ar' => 'تنتهي في', 'en' => 'Expires', 'de' => 'Läuft ab'],
    'general_invite_link' => ['ar' => 'رابط دعوة عام', 'en' => 'General invite link', 'de' => 'Allgemeiner Einladungslink'],
    'resend' => ['ar' => 'إعادة إرسال', 'en' => 'Resend', 'de' => 'Erneut senden'],
    'review_project' => ['ar' => 'مراجعة المشروع', 'en' => 'Review Project', 'de' => 'Projekt prüfen'],
    'write_note_placeholder' => ['ar' => 'اكتب ملاحظتك هنا...', 'en' => 'Write your note here...', 'de' => 'Schreiben Sie Ihre Notiz hier...'],
    'confirm_accept_project' => ['ar' => 'هل أنت متأكد من قبول هذا المشروع؟', 'en' => 'Are you sure you want to accept this project?', 'de' => 'Sind Sie sicher, dass Sie dieses Projekt annehmen möchten?'],
    'confirm_reject_project' => ['ar' => 'هل أنت متأكد من رفض هذا المشروع؟', 'en' => 'Are you sure you want to reject this project?', 'de' => 'Sind Sie sicher, dass Sie dieses Projekt ablehnen möchten?'],
    'confirm_reject_accepted_project' => ['ar' => 'هل أنت متأكد من رفض هذا المشروع المقبول؟ سيتم إلغاء رقم المجموعة.', 'en' => 'Are you sure you want to reject this accepted project? The group number will be removed.', 'de' => 'Sind Sie sicher, dass Sie dieses angenommene Projekt ablehnen möchten? Die Gruppennummer wird entfernt.'],
    'reject_accepted' => ['ar' => 'رفض المشروع المقبول', 'en' => 'Reject Accepted Project', 'de' => 'Angenommenes Projekt ablehnen'],

    // Professor — Settings Page
    'settings_saved' => ['ar' => 'تم حفظ الإعدادات بنجاح', 'en' => 'Settings saved successfully', 'de' => 'Einstellungen erfolgreich gespeichert'],
    'registration_description' => ['ar' => 'التحكم في إمكانية تسجيل حسابات جديدة للطلاب', 'en' => 'Control whether new student accounts can be registered', 'de' => 'Steuern Sie, ob neue Studentenkonten registriert werden können'],
    'email_verification_description' => ['ar' => 'عند التفعيل، يجب على الطلاب تأكيد بريدهم الإلكتروني قبل تسجيل الدخول', 'en' => 'When enabled, students must verify their email before logging in', 'de' => 'Wenn aktiviert, müssen Studenten ihre E-Mail bestätigen, bevor sie sich anmelden können'],
    'team_size_description' => ['ar' => 'تحديد الحد الأدنى والأقصى لعدد أعضاء الفريق', 'en' => 'Set the minimum and maximum number of team members', 'de' => 'Minimale und maximale Anzahl der Teammitglieder festlegen'],
    'toggle_student_project_creation' => ['ar' => 'إنشاء المشاريع بواسطة الطلاب', 'en' => 'Student Project Creation', 'de' => 'Projekterstellung durch Studenten'],
    'student_project_creation_description' => ['ar' => 'السماح للطلاب بإنشاء مشاريع جديدة بأنفسهم', 'en' => 'Allow students to create new projects on their own', 'de' => 'Studenten erlauben, eigene Projekte zu erstellen'],

    'student_project_creation_disabled' => ['ar' => 'إنشاء المشاريع بواسطة الطلاب معطل حالياً', 'en' => 'Student project creation is currently disabled', 'de' => 'Die Projekterstellung durch Studenten ist derzeit deaktiviert'],
    'toggle_show_reviewer_name' => ['ar' => 'إظهار اسم المراجع', 'en' => 'Show Reviewer Name', 'de' => 'Prüfername anzeigen'],
    'show_reviewer_name_description' => ['ar' => 'عند التفعيل، سيظهر اسم الدكتور الذي قبل أو رفض المشروع للطلاب', 'en' => 'When enabled, the name of the professor who accepted or declined the project will be shown to students', 'de' => 'Wenn aktiviert, wird den Studenten der Name des Professors angezeigt, der das Projekt angenommen oder abgelehnt hat'],

    'toggle_leader_transfer' => ['ar' => 'نقل القيادة بواسطة القائد', 'en' => 'Leader Transfer by Team Leader', 'de' => 'Leiterübertragung durch Teamleiter'],
    'leader_transfer_description' => ['ar' => 'السماح لقائد الفريق بنقل القيادة لعضو آخر في الفريق', 'en' => 'Allow team leaders to transfer leadership to another team member', 'de' => 'Teamleitern erlauben, die Leitung an ein anderes Teammitglied zu übertragen'],

    'toggle_profile_pictures' => ['ar' => 'الصور الشخصية', 'en' => 'Profile Pictures', 'de' => 'Profilbilder'],
    'profile_pictures_description' => ['ar' => 'السماح للمستخدمين برفع وعرض الصور الشخصية', 'en' => 'Allow users to upload and display profile pictures', 'de' => 'Benutzern erlauben, Profilbilder hochzuladen und anzuzeigen'],

    'enabled_languages' => ['ar' => 'اللغات المتاحة', 'en' => 'Enabled Languages', 'de' => 'Aktivierte Sprachen'],
    'enabled_languages_description' => ['ar' => 'اختر اللغات التي يمكن للمستخدمين التبديل بينها في الواجهة', 'en' => 'Choose which languages users can switch between in the interface', 'de' => 'Wählen Sie, zwischen welchen Sprachen die Benutzer in der Oberfläche wechseln können'],
    'enabled_languages_hint' => ['ar' => 'يجب تفعيل لغة واحدة على الأقل. سيتم تحويل المستخدمين الذين يستخدمون لغة معطلة تلقائياً.', 'en' => 'At least one language must be enabled. Users on a disabled language will be switched automatically.', 'de' => 'Mindestens eine Sprache muss aktiviert sein. Benutzer mit einer deaktivierten Sprache werden automatisch umgeschaltet.'],

    'default_language' => ['ar' => 'اللغة الافتراضية', 'en' => 'Default Language', 'de' => 'Standardsprache'],
    'default_language_description' => ['ar' => 'اللغة التي تُعرض للمستخدمين الجدد عند زيارتهم الأولى', 'en' => 'The language shown to new users on their first visit', 'de' => 'Die Sprache, die neuen Benutzern beim ersten Besuch angezeigt wird'],

    'login_methods' => ['ar' => 'طرق تسجيل الدخول', 'en' => 'Login Methods', 'de' => 'Anmeldemethoden'],
    'login_methods_description' => ['ar' => 'اختر الطرق المتاحة لتسجيل دخول الطلاب', 'en' => 'Choose which methods students can use to log in', 'de' => 'Wählen Sie, welche Methoden Studenten zur Anmeldung verwenden können'],
    'login_method_both' => ['ar' => 'البريد الإلكتروني وكود الطالب', 'en' => 'Email and Student Code', 'de' => 'E-Mail und Matrikelnummer'],
    'login_method_email_only' => ['ar' => 'البريد الإلكتروني فقط', 'en' => 'Email Only', 'de' => 'Nur E-Mail'],
    'login_method_student_code_only' => ['ar' => 'كود الطالب فقط', 'en' => 'Student Code Only', 'de' => 'Nur Matrikelnummer'],
    'email_placeholder' => ['ar' => 'أدخل بريدك الإلكتروني', 'en' => 'Enter your email', 'de' => 'E-Mail eingeben'],
    'student_code_placeholder' => ['ar' => 'أدخل كود الطالب', 'en' => 'Enter your student code', 'de' => 'Matrikelnummer eingeben'],
    'login_method_not_allowed' => ['ar' => 'طريقة تسجيل الدخول هذه غير متاحة', 'en' => 'This login method is not available', 'de' => 'Diese Anmeldemethode ist nicht verfügbar'],

    'transfer_leadership' => ['ar' => 'نقل القيادة', 'en' => 'Transfer Leadership', 'de' => 'Leitung übertragen'],
    'confirm_transfer_leadership' => ['ar' => 'هل أنت متأكد من نقل قيادة الفريق إلى', 'en' => 'Are you sure you want to transfer leadership to', 'de' => 'Sind Sie sicher, dass Sie die Leitung übertragen möchten an'],
    'leader_transfer_disabled' => ['ar' => 'نقل القيادة معطل حالياً من قبل المشرف', 'en' => 'Leadership transfer is currently disabled by the administrator', 'de' => 'Die Leiterübertragung ist derzeit vom Administrator deaktiviert'],
    'reviewed_by' => ['ar' => 'تمت المراجعة بواسطة', 'en' => 'Reviewed by', 'de' => 'Geprüft von'],

    // Review — Resubmission
    'allow_resubmit' => ['ar' => 'السماح بإعادة التقديم', 'en' => 'Allow Resubmission', 'de' => 'Erneute Einreichung erlauben'],
    'allow_resubmit_desc' => ['ar' => 'السماح للطالب بتعديل المشروع وإعادة تقديمه بعد الرفض', 'en' => 'Allow the student to edit and resubmit the project after rejection', 'de' => 'Dem Studenten erlauben, das Projekt nach Ablehnung zu bearbeiten und erneut einzureichen'],
    'resubmit_not_allowed' => ['ar' => 'إعادة التقديم غير مسموح بها لهذا المشروع', 'en' => 'Resubmission is not allowed for this project', 'de' => 'Eine erneute Einreichung ist für dieses Projekt nicht erlaubt'],
    'project_rejected_final' => ['ar' => 'تم رفض مشروعك بشكل نهائي.', 'en' => 'Your project has been permanently rejected.', 'de' => 'Ihr Projekt wurde endgültig abgelehnt.'],
    'project_rejected_resubmit' => ['ar' => 'تم رفض مشروعك. يمكنك تعديل البيانات وإعادة التقديم.', 'en' => 'Your project has been rejected. You can edit and resubmit.', 'de' => 'Ihr Projekt wurde abgelehnt. Sie können es bearbeiten und erneut einreichen.'],
    'review_history' => ['ar' => 'سجل المراجعات', 'en' => 'Review History', 'de' => 'Prüfungsverlauf'],
    'rejected_final' => ['ar' => 'رفض نهائي', 'en' => 'Final Rejection', 'de' => 'Endgültige Ablehnung'],
    'rejected_resubmittable' => ['ar' => 'قابل لإعادة التقديم', 'en' => 'Resubmittable', 'de' => 'Erneut einreichbar'],
    'allow_resubmit_enabled' => ['ar' => 'تم السماح بإعادة التقديم', 'en' => 'Resubmission has been allowed', 'de' => 'Erneute Einreichung wurde erlaubt'],
    'allow_resubmit_disabled' => ['ar' => 'تم منع إعادة التقديم', 'en' => 'Resubmission has been disallowed', 'de' => 'Erneute Einreichung wurde gesperrt'],


    // Student — Project Page
    'role' => ['ar' => 'الدور', 'en' => 'Role', 'de' => 'Rolle'],
    'invite_link_label' => ['ar' => 'رابط دعوة', 'en' => 'Invite link', 'de' => 'Einladungslink'],
    'pending_status' => ['ar' => 'في الانتظار', 'en' => 'Pending', 'de' => 'Ausstehend'],
    'invited' => ['ar' => 'مدعو', 'en' => 'Invited', 'de' => 'Eingeladen'],
    'resend_invitation' => ['ar' => 'إعادة إرسال الدعوة', 'en' => 'Resend Invitation', 'de' => 'Einladung erneut senden'],
    'cancel_invitation' => ['ar' => 'إلغاء الدعوة', 'en' => 'Cancel Invitation', 'de' => 'Einladung abbrechen'],
    'team_min_size_msg' => ['ar' => 'يجب أن يكون الفريق %d أعضاء على الأقل', 'en' => 'Team needs at least %d members', 'de' => 'Das Team benötigt mindestens %d Mitglieder'],
    'delete_project' => ['ar' => 'حذف المشروع', 'en' => 'Delete Project', 'de' => 'Projekt löschen'],
    'project_description' => ['ar' => 'وصف المشروع', 'en' => 'Project Description', 'de' => 'Projektbeschreibung'],
    'description_placeholder' => ['ar' => 'اكتب وصفاً تفصيلياً للمشروع...', 'en' => 'Write a detailed project description...', 'de' => 'Schreiben Sie eine detaillierte Projektbeschreibung...'],
    'bold' => ['ar' => 'عريض', 'en' => 'Bold', 'de' => 'Fett'],
    'italic' => ['ar' => 'مائل', 'en' => 'Italic', 'de' => 'Kursiv'],
    'underline_text' => ['ar' => 'تحته خط', 'en' => 'Underline', 'de' => 'Unterstrichen'],
    'bullet_list' => ['ar' => 'قائمة نقطية', 'en' => 'Bullet List', 'de' => 'Aufzählung'],
    'numbered_list' => ['ar' => 'قائمة مرقمة', 'en' => 'Numbered List', 'de' => 'Nummerierte Liste'],
    'insert_link' => ['ar' => 'إدراج رابط', 'en' => 'Insert Link', 'de' => 'Link einfügen'],
    'upload_file' => ['ar' => 'رفع صورة', 'en' => 'Upload Image', 'de' => 'Bild hochladen'],
    'uploading_file' => ['ar' => 'جاري الرفع...', 'en' => 'Uploading...', 'de' => 'Wird hochgeladen...'],
    'no_description' => ['ar' => 'لا يوجد وصف للمشروع بعد.', 'en' => 'No project description yet.', 'de' => 'Noch keine Projektbeschreibung vorhanden.'],
    'edit_project_info' => ['ar' => 'تعديل المشروع', 'en' => 'Edit Project', 'de' => 'Projekt bearbeiten'],
    'save_changes' => ['ar' => 'حفظ التغييرات', 'en' => 'Save Changes', 'de' => 'Änderungen speichern'],
    'confirm_remove_member' => ['ar' => 'هل تريد إزالة', 'en' => 'Remove', 'de' => 'Entfernen'],
    'confirm_leave_project' => ['ar' => 'هل تريد مغادرة هذا المشروع؟', 'en' => 'Leave this project?', 'de' => 'Dieses Projekt verlassen?'],
    'confirm_delete_project' => ['ar' => 'هل أنت متأكد من حذف هذا المشروع؟ سيتم حذفه نهائياً.', 'en' => 'Are you sure you want to delete this project? This is permanent.', 'de' => 'Sind Sie sicher, dass Sie dieses Projekt löschen möchten? Dies ist endgültig.'],
    'confirm_submit_project' => ['ar' => 'تأكيد: سيتم تقديم المشروع للمراجعة. هل أنت متأكد؟', 'en' => 'Confirm: The project will be submitted for review. Are you sure?', 'de' => 'Bestätigung: Das Projekt wird zur Prüfung eingereicht. Sind Sie sicher?'],
    'submit_warning_title' => ['ar' => 'تأكيد تقديم المشروع', 'en' => 'Confirm Project Submission', 'de' => 'Projekteinreichung bestätigen'],
    'submit_warning_message' => ['ar' => 'بمجرد تقديم المشروع، لن تتمكن من إجراء أي تعديلات على بيانات المشروع أو أعضاء الفريق.', 'en' => 'Once the project is submitted, you will not be able to make any edits to the project details or team members.', 'de' => 'Sobald das Projekt eingereicht wurde, können Sie keine Änderungen mehr an den Projektdaten oder Teammitgliedern vornehmen.'],
    'submit_confirm_checkbox' => ['ar' => 'أفهم أنه لا يمكن التعديل بعد التقديم، وأنا متأكد من رغبتي في التقديم.', 'en' => 'I understand that no more edits can be made once the project is submitted, and I am sure I want to submit.', 'de' => 'Ich verstehe, dass nach der Einreichung keine Änderungen mehr möglich sind, und ich bin sicher, dass ich einreichen möchte.'],
    'confirm_cancel_invitation' => ['ar' => 'هل تريد إلغاء هذه الدعوة؟', 'en' => 'Cancel this invitation?', 'de' => 'Diese Einladung abbrechen?'],

    // Student — Dashboard
    'profile_incomplete_msg' => ['ar' => 'يجب إكمال ملفك الشخصي قبل تقديم أي مشروع.', 'en' => 'You must complete your profile before submitting any project.', 'de' => 'Sie müssen Ihr Profil vervollständigen, bevor Sie ein Projekt einreichen können.'],
    'no_projects_msg' => ['ar' => 'أنشئ مشروعًا جديدًا أو انضم لمشروع عن طريق كود الانضمام.', 'en' => 'Create a new project or join one using a join code.', 'de' => 'Erstellen Sie ein neues Projekt oder treten Sie einem bei mit einem Beitrittscode.'],
    'from' => ['ar' => 'من', 'en' => 'From', 'de' => 'Von'],
    'project_type_placeholder' => ['ar' => 'مثال: تطبيق ويب', 'en' => 'e.g., Web Application', 'de' => 'z.B. Webanwendung'],

    // Student — Profile
    'select_option_full' => ['ar' => '-- اختر --', 'en' => '-- Select --', 'de' => '-- Auswählen --'],
    'fourteen_digits' => ['ar' => '14 رقم', 'en' => '14 digits', 'de' => '14 Ziffern'],
    'select_governorate_full' => ['ar' => '-- اختر المحافظة --', 'en' => '-- Select Governorate --', 'de' => '-- Gouvernement auswählen --'],
    'eleven_digits' => ['ar' => '11 رقم', 'en' => '11 digits', 'de' => '11 Ziffern'],
    'change' => ['ar' => 'تغيير', 'en' => 'Change', 'de' => 'Ändern'],

    // Join Page
    'invalid_invitation_link' => ['ar' => 'رابط الدعوة غير صالح أو منتهي الصلاحية', 'en' => 'Invalid or expired invitation link', 'de' => 'Ungültiger oder abgelaufener Einladungslink'],
    'project_not_accepting' => ['ar' => 'هذا المشروع لم يعد يقبل أعضاء جدد', 'en' => 'This project is no longer accepting new members', 'de' => 'Dieses Projekt nimmt keine neuen Mitglieder mehr auf'],
    'invalid_join_code' => ['ar' => 'كود الانضمام غير صالح', 'en' => 'Invalid join code', 'de' => 'Ungültiger Beitrittscode'],
    'join_project_title' => ['ar' => 'الانضمام لمشروع', 'en' => 'Join Project', 'de' => 'Projekt beitreten'],
    'project_invitation' => ['ar' => 'دعوة للانضمام', 'en' => 'Project Invitation', 'de' => 'Projekteinladung'],
    'already_in_project' => ['ar' => 'أنت بالفعل عضو في هذا المشروع', 'en' => 'You are already a member of this project', 'de' => 'Sie sind bereits Mitglied dieses Projekts'],
    'type' => ['ar' => 'النوع', 'en' => 'Type', 'de' => 'Typ'],
    'invited_by_label' => ['ar' => 'دعوة من', 'en' => 'Invited by', 'de' => 'Eingeladen von'],
    'join' => ['ar' => 'انضمام', 'en' => 'Join', 'de' => 'Beitreten'],
    'decline' => ['ar' => 'تجاهل', 'en' => 'Decline', 'de' => 'Ablehnen'],

    // Register Page
    'invalid_email' => ['ar' => 'البريد الإلكتروني غير صالح', 'en' => 'Invalid email address', 'de' => 'Ungültige E-Mail-Adresse'],
    'password_min_length' => ['ar' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل', 'en' => 'Password must be at least 6 characters', 'de' => 'Passwort muss mindestens 6 Zeichen lang sein'],
    'student_code_format' => ['ar' => 'كود الطالب يجب أن يكون أحرف وأرقام بحد أقصى 30 حرف', 'en' => 'Student code must be alphanumeric, max 30 characters', 'de' => 'Matrikelnummer muss alphanumerisch sein, max. 30 Zeichen'],
    'password_hint' => ['ar' => '6 أحرف على الأقل', 'en' => 'At least 6 characters', 'de' => 'Mindestens 6 Zeichen'],
    'student_code_hint' => ['ar' => 'أحرف وأرقام، بحد أقصى 30 حرف', 'en' => 'Alphanumeric, max 30 characters', 'de' => 'Alphanumerisch, max. 30 Zeichen'],

    // Verify Page
    'email_verification_title' => ['ar' => 'تأكيد البريد الإلكتروني', 'en' => 'Email Verification', 'de' => 'E-Mail-Bestätigung'],
    'verification_request_new_link' => ['ar' => 'يرجى تسجيل الدخول وطلب إعادة إرسال رابط التأكيد.', 'en' => 'Please login and request a new verification link.', 'de' => 'Bitte melden Sie sich an und fordern Sie einen neuen Bestätigungslink an.'],

    // Professor — Create & Assign
    'create_project_professor' => ['ar' => 'إنشاء مشروع جديد', 'en' => 'Create New Project', 'de' => 'Neues Projekt erstellen'],
    'assign_students' => ['ar' => 'تعيين طلاب', 'en' => 'Assign Students', 'de' => 'Studenten zuweisen'],
    'assign_student' => ['ar' => 'تعيين طالب', 'en' => 'Assign Student', 'de' => 'Student zuweisen'],
    'search_students' => ['ar' => 'بحث عن طالب (بالاسم أو البريد أو الكود)', 'en' => 'Search student (by name, email or code)', 'de' => 'Student suchen (nach Name, E-Mail oder Code)'],
    'search_projects' => ['ar' => 'بحث عن مشروع...', 'en' => 'Search projects...', 'de' => 'Projekte suchen...'],
    'search_add_students' => ['ar' => 'بحث وإضافة طلاب', 'en' => 'Search & Add Students', 'de' => 'Studenten suchen & hinzufügen'],
    'no_results' => ['ar' => 'لا توجد نتائج', 'en' => 'No results found', 'de' => 'Keine Ergebnisse gefunden'],
    'student_already_in_project' => ['ar' => 'الطالب عضو بالفعل في هذا المشروع', 'en' => 'Student is already a member of this project', 'de' => 'Student ist bereits Mitglied dieses Projekts'],
    'student_added' => ['ar' => 'تم إضافة الطالب بنجاح', 'en' => 'Student added successfully', 'de' => 'Student erfolgreich hinzugefügt'],
    'student_removed' => ['ar' => 'تم إزالة الطالب بنجاح', 'en' => 'Student removed successfully', 'de' => 'Student erfolgreich entfernt'],
    'set_as_leader' => ['ar' => 'تعيين كقائد', 'en' => 'Set as Leader', 'de' => 'Als Leiter festlegen'],
    'leader_changed' => ['ar' => 'تم تغيير قائد الفريق', 'en' => 'Team leader changed', 'de' => 'Teamleiter geändert'],
    'remove_from_project' => ['ar' => 'إزالة من المشروع', 'en' => 'Remove from Project', 'de' => 'Aus Projekt entfernen'],
    'confirm_remove_from_project' => ['ar' => 'هل تريد إزالة هذا الطالب من المشروع؟', 'en' => 'Remove this student from the project?', 'de' => 'Diesen Studenten aus dem Projekt entfernen?'],
    'student_has_project' => ['ar' => 'هذا الطالب لديه مشروع بالفعل', 'en' => 'This student already has a project', 'de' => 'Dieser Student hat bereits ein Projekt'],
    'select_leader_first' => ['ar' => 'يجب اختيار قائد للفريق أولاً', 'en' => 'A team leader must be selected first', 'de' => 'Zunächst muss ein Teamleiter ausgewählt werden'],
    'project_created_by_professor' => ['ar' => 'تم إنشاء المشروع بنجاح', 'en' => 'Project created successfully', 'de' => 'Projekt erfolgreich erstellt'],
    'add' => ['ar' => 'إضافة', 'en' => 'Add', 'de' => 'Hinzufügen'],
    'no_leader_assigned' => ['ar' => 'لم يتم تعيين قائد بعد', 'en' => 'No leader assigned yet', 'de' => 'Noch kein Leiter zugewiesen'],
    'current_members' => ['ar' => 'الأعضاء الحاليون', 'en' => 'Current Members', 'de' => 'Aktuelle Mitglieder'],

    // Navbar
    'viewing_as' => ['ar' => 'أنت تتصفح كـ', 'en' => 'Viewing as', 'de' => 'Anzeige als'],
    'back_to_professor' => ['ar' => 'العودة للدكتور', 'en' => 'Back to Professor', 'de' => 'Zurück zum Professor'],

    // Demo mode
    'demo_quick_login' => ['ar' => 'تسجيل دخول سريع (وضع تجريبي)', 'en' => 'Quick Login (Demo Mode)', 'de' => 'Schnellanmeldung (Demo-Modus)'],
    'demo_doctor' => ['ar' => 'دكتور', 'en' => 'Doctor', 'de' => 'Professor'],
    'demo_student' => ['ar' => 'طالب', 'en' => 'Student', 'de' => 'Student'],
    'demo_resets_in' => ['ar' => 'إعادة تعيين العرض خلال', 'en' => 'Demo resets in', 'de' => 'Demo wird zurückgesetzt in'],
    'demo_credentials' => ['ar' => 'بيانات الدخول التجريبية', 'en' => 'Demo Credentials', 'de' => 'Demo-Zugangsdaten'],

    // Landing page
    'landing_hero_title' => ['ar' => 'نظام إدارة مشاريع التخرج', 'en' => 'Graduation Project Management System', 'de' => 'Abschlussprojekt-Verwaltungssystem'],
    'landing_hero_subtitle' => ['ar' => 'منصة متكاملة لإدارة وتنظيم مشاريع التخرج الجامعية بكل سهولة ويسر', 'en' => 'A comprehensive platform for managing and organizing university graduation projects with ease', 'de' => 'Eine umfassende Plattform zur einfachen Verwaltung und Organisation von universitären Abschlussprojekten'],
    'landing_feature_team' => ['ar' => 'تكوين الفرق', 'en' => 'Team Building', 'de' => 'Teambildung'],
    'landing_feature_team_desc' => ['ar' => 'أنشئ فريقك وادعُ زملاءك عبر رابط أو كود انضمام أو رمز QR', 'en' => 'Create your team and invite classmates via link, join code, or QR code', 'de' => 'Erstellen Sie Ihr Team und laden Sie Kommilitonen per Link, Beitrittscode oder QR-Code ein'],
    'landing_feature_submit' => ['ar' => 'تقديم المشاريع', 'en' => 'Project Submission', 'de' => 'Projekteinreichung'],
    'landing_feature_submit_desc' => ['ar' => 'أكمل ملفك الشخصي وقدّم مشروعك للمراجعة بخطوات بسيطة', 'en' => 'Complete your profile and submit your project for review in simple steps', 'de' => 'Vervollständigen Sie Ihr Profil und reichen Sie Ihr Projekt in einfachen Schritten zur Prüfung ein'],
    'landing_feature_review' => ['ar' => 'المراجعة والمتابعة', 'en' => 'Review & Track', 'de' => 'Prüfung & Verfolgung'],
    'landing_feature_review_desc' => ['ar' => 'تابع حالة مشروعك واحصل على ملاحظات الدكتور مباشرة', 'en' => 'Track your project status and get professor feedback directly', 'de' => 'Verfolgen Sie Ihren Projektstatus und erhalten Sie direkt Feedback vom Professor'],
    'landing_feature_bilingual' => ['ar' => 'دعم متعدد اللغات', 'en' => 'Multilingual Support', 'de' => 'Mehrsprachige Unterstützung'],
    'landing_feature_bilingual_desc' => ['ar' => 'واجهة كاملة بالعربية والإنجليزية والألمانية', 'en' => 'Full interface in Arabic, English, and German', 'de' => 'Vollständige Oberfläche in Arabisch, Englisch und Deutsch'],
    'landing_welcome_back' => ['ar' => 'مرحباً', 'en' => 'Welcome', 'de' => 'Willkommen'],
    'landing_sign_in_continue' => ['ar' => 'سجّل دخولك للمتابعة', 'en' => 'Sign in to continue', 'de' => 'Melden Sie sich an, um fortzufahren'],

    // Landing — How it works
    'landing_how_it_works' => ['ar' => 'كيف يعمل النظام', 'en' => 'How It Works', 'de' => 'So funktioniert es'],
    'landing_step1_title' => ['ar' => 'سجّل حسابك', 'en' => 'Create Account', 'de' => 'Konto erstellen'],
    'landing_step1_desc' => ['ar' => 'أنشئ حسابك باستخدام بريدك الإلكتروني وكود الطالب', 'en' => 'Sign up with your email and student code', 'de' => 'Registrieren Sie sich mit Ihrer E-Mail und Matrikelnummer'],
    'landing_step2_title' => ['ar' => 'أكمل ملفك', 'en' => 'Complete Profile', 'de' => 'Profil vervollständigen'],
    'landing_step2_desc' => ['ar' => 'أضف بياناتك الشخصية وارفع المستندات المطلوبة', 'en' => 'Add personal info and upload required documents', 'de' => 'Persönliche Daten hinzufügen und erforderliche Dokumente hochladen'],
    'landing_step3_title' => ['ar' => 'كوّن فريقك', 'en' => 'Build Your Team', 'de' => 'Team zusammenstellen'],
    'landing_step3_desc' => ['ar' => 'أنشئ مشروعاً وادعُ زملاءك للانضمام', 'en' => 'Create a project and invite classmates to join', 'de' => 'Erstellen Sie ein Projekt und laden Sie Kommilitonen ein'],
    'landing_step4_title' => ['ar' => 'قدّم مشروعك', 'en' => 'Submit Project', 'de' => 'Projekt einreichen'],
    'landing_step4_desc' => ['ar' => 'أرسل مشروعك للمراجعة واحصل على الموافقة', 'en' => 'Submit for review and get approved', 'de' => 'Zur Prüfung einreichen und Genehmigung erhalten'],

    // Landing — Extra features
    'landing_feature_secure' => ['ar' => 'آمن وموثوق', 'en' => 'Secure & Reliable', 'de' => 'Sicher & Zuverlässig'],
    'landing_feature_secure_desc' => ['ar' => 'تشفير كامل للبيانات وحماية الملفات المرفوعة', 'en' => 'Full data encryption and secure file uploads', 'de' => 'Vollständige Datenverschlüsselung und sichere Datei-Uploads'],
    'landing_feature_responsive' => ['ar' => 'متجاوب مع جميع الأجهزة', 'en' => 'Fully Responsive', 'de' => 'Vollständig responsiv'],
    'landing_feature_responsive_desc' => ['ar' => 'يعمل بسلاسة على الكمبيوتر والجوال والتابلت', 'en' => 'Works seamlessly on desktop, mobile, and tablet', 'de' => 'Funktioniert nahtlos auf Desktop, Mobilgerät und Tablet'],

    // Misc
    'details' => ['ar' => 'التفاصيل', 'en' => 'Details', 'de' => 'Details'],

    // Department management
    'departments' => ['ar' => 'الأقسام', 'en' => 'Departments', 'de' => 'Abteilungen'],
    'department_name' => ['ar' => 'اسم القسم', 'en' => 'Department Name', 'de' => 'Abteilungsname'],
    'add_department' => ['ar' => 'إضافة قسم', 'en' => 'Add Department', 'de' => 'Abteilung hinzufügen'],
    'edit_department' => ['ar' => 'تعديل القسم', 'en' => 'Edit Department', 'de' => 'Abteilung bearbeiten'],
    'delete_department' => ['ar' => 'حذف القسم', 'en' => 'Delete Department', 'de' => 'Abteilung löschen'],
    'select_department' => ['ar' => '-- اختر القسم --', 'en' => '-- Select Department --', 'de' => '-- Abteilung auswählen --'],
    'department_exists' => ['ar' => 'هذا القسم موجود بالفعل', 'en' => 'This department already exists', 'de' => 'Diese Abteilung existiert bereits'],
    'department_created' => ['ar' => 'تم إضافة القسم بنجاح', 'en' => 'Department created successfully', 'de' => 'Abteilung erfolgreich erstellt'],
    'department_updated' => ['ar' => 'تم تعديل القسم بنجاح', 'en' => 'Department updated successfully', 'de' => 'Abteilung erfolgreich aktualisiert'],
    'department_deleted' => ['ar' => 'تم حذف القسم بنجاح', 'en' => 'Department deleted successfully', 'de' => 'Abteilung erfolgreich gelöscht'],
    'department_name_required' => ['ar' => 'اسم القسم مطلوب', 'en' => 'Department name is required', 'de' => 'Abteilungsname ist erforderlich'],
    'confirm_delete_department' => ['ar' => 'هل أنت متأكد من حذف هذا القسم؟ سيتم إزالته من جميع المستخدمين.', 'en' => 'Are you sure you want to delete this department? It will be removed from all users.', 'de' => 'Sind Sie sicher, dass Sie diese Abteilung löschen möchten? Sie wird von allen Benutzern entfernt.'],
    'departments_description' => ['ar' => 'إدارة الأقسام المتاحة للاختيار في الملف الشخصي', 'en' => 'Manage departments available for selection in profiles', 'de' => 'Abteilungen verwalten, die in Profilen zur Auswahl stehen'],
    'no_departments' => ['ar' => 'لا توجد أقسام مضافة', 'en' => 'No departments added', 'de' => 'Keine Abteilungen hinzugefügt'],
    'leader_transfer_after_submit' => ['ar' => 'لا يمكن نقل القيادة بعد تقديم المشروع', 'en' => 'Cannot transfer leadership after project submission', 'de' => 'Leiterübertragung nach Projekteinreichung nicht möglich'],

    // Admin
    'admin_dashboard' => ['ar' => 'لوحة تحكم المدير', 'en' => 'Admin Dashboard', 'de' => 'Admin-Dashboard'],
    'admin_profile_info' => ['ar' => 'يمكنك تعديل بياناتك الشخصية وصورتك من هنا', 'en' => 'You can edit your personal information and photo here', 'de' => 'Hier können Sie Ihre persönlichen Daten und Ihr Foto bearbeiten'],
    'professor_accounts' => ['ar' => 'الدكاترة', 'en' => 'Professors', 'de' => 'Professoren'],
    'professors' => ['ar' => 'الدكاترة', 'en' => 'Professors', 'de' => 'Professoren'],
    'professors_count' => ['ar' => 'دكتور', 'en' => 'professors', 'de' => 'Professoren'],
    'students' => ['ar' => 'الطلاب', 'en' => 'Students', 'de' => 'Studenten'],
    'total_projects' => ['ar' => 'إجمالي المشاريع', 'en' => 'Total Projects', 'de' => 'Gesamtprojekte'],
    'all_projects' => ['ar' => 'المشاريع', 'en' => 'Projects', 'de' => 'Projekte'],
    'all' => ['ar' => 'الكل', 'en' => 'All', 'de' => 'Alle'],
    'recent_professors' => ['ar' => 'آخر الدكاترة', 'en' => 'Recent Professors', 'de' => 'Neueste Professoren'],
    'recent_projects' => ['ar' => 'آخر المشاريع', 'en' => 'Recent Projects', 'de' => 'Neueste Projekte'],
    'view_all' => ['ar' => 'عرض الكل', 'en' => 'View All', 'de' => 'Alle anzeigen'],
    'no_professors' => ['ar' => 'لا يوجد دكاترة مسجلين', 'en' => 'No professors registered', 'de' => 'Keine Professoren registriert'],
    'add_professor' => ['ar' => 'إضافة دكتور', 'en' => 'Add Professor', 'de' => 'Professor hinzufügen'],
    'add_new_professor' => ['ar' => 'إضافة دكتور جديد', 'en' => 'Add New Professor', 'de' => 'Neuen Professor hinzufügen'],
    'professor_created' => ['ar' => 'تم إنشاء حساب الدكتور بنجاح', 'en' => 'Professor account created successfully', 'de' => 'Professorenkonto erfolgreich erstellt'],
    'professor_deleted' => ['ar' => 'تم حذف حساب الدكتور', 'en' => 'Professor account deleted', 'de' => 'Professorenkonto gelöscht'],
    'account_enabled_msg' => ['ar' => 'تم تفعيل الحساب', 'en' => 'Account enabled', 'de' => 'Konto aktiviert'],
    'account_disabled_msg' => ['ar' => 'تم تعطيل الحساب', 'en' => 'Account disabled', 'de' => 'Konto deaktiviert'],
    'login_as_professor' => ['ar' => 'الدخول كدكتور', 'en' => 'Login as Professor', 'de' => 'Als Professor anmelden'],
    'impersonating_professor' => ['ar' => 'تم الدخول كدكتور', 'en' => 'Now viewing as professor', 'de' => 'Jetzt als Professor angemeldet'],
    'back_to_admin' => ['ar' => 'العودة للمدير', 'en' => 'Back to Admin', 'de' => 'Zurück zum Admin'],
    'confirm_disable_professor' => ['ar' => 'تعطيل حساب هذا الدكتور؟', 'en' => "Disable this professor's account?", 'de' => 'Konto dieses Professors deaktivieren?'],
    'confirm_enable_professor' => ['ar' => 'تفعيل حساب هذا الدكتور؟', 'en' => "Enable this professor's account?", 'de' => 'Konto dieses Professors aktivieren?'],
    'confirm_delete_professor' => ['ar' => 'حذف هذا الحساب نهائياً؟ سيتم حذف جميع بياناته.', 'en' => 'Permanently delete this account? All data will be removed.', 'de' => 'Dieses Konto endgültig löschen? Alle Daten werden entfernt.'],
    'confirm_impersonate_professor' => ['ar' => 'الدخول كهذا الدكتور؟', 'en' => 'Login as this professor?', 'de' => 'Als dieser Professor anmelden?'],
    'reset_password' => ['ar' => 'إعادة تعيين كلمة المرور', 'en' => 'Reset Password', 'de' => 'Passwort zurücksetzen'],
    'new_password' => ['ar' => 'كلمة المرور الجديدة', 'en' => 'New Password', 'de' => 'Neues Passwort'],
    'password_reset_success' => ['ar' => 'تم تغيير كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول.', 'en' => 'Your password has been changed successfully. You can now log in.', 'de' => 'Ihr Passwort wurde erfolgreich geändert. Sie können sich jetzt anmelden.'],
    'send_new_password_email' => ['ar' => 'إرسال كلمة المرور الجديدة بالبريد', 'en' => 'Send new password via email', 'de' => 'Neues Passwort per E-Mail senden'],
    'demo_admin' => ['ar' => 'مدير النظام', 'en' => 'Admin', 'de' => 'Administrator'],

    // Landing — Stats
    'landing_stat_easy' => ['ar' => 'سهل الاستخدام', 'en' => 'Easy to Use', 'de' => 'Einfach zu bedienen'],
    'landing_stat_fast' => ['ar' => 'سريع وفعّال', 'en' => 'Fast & Efficient', 'de' => 'Schnell & Effizient'],
    'landing_stat_complete' => ['ar' => 'نظام متكامل', 'en' => 'All-in-One', 'de' => 'Alles-in-Einem'],

    // Paper submission
    'paper' => ['ar' => 'الورقة', 'en' => 'Paper', 'de' => 'Arbeit'],
    'paper_submitted_label' => ['ar' => 'تم تقديم الورقة', 'en' => 'Paper submitted', 'de' => 'Arbeit eingereicht'],
    'paper_not_submitted' => ['ar' => 'لم تقدم الورقة بعد', 'en' => 'Paper not submitted', 'de' => 'Arbeit noch nicht eingereicht'],
    'submit_paper' => ['ar' => 'تأكيد تقديم ورقتي', 'en' => 'Mark my paper as submitted', 'de' => 'Meine Arbeit als eingereicht markieren'],
    'withdraw_paper' => ['ar' => 'إلغاء تقديم ورقتي', 'en' => 'Withdraw my paper submission', 'de' => 'Einreichung meiner Arbeit zurückziehen'],
    'all_papers_submitted' => ['ar' => 'جميع الأوراق مقدمة', 'en' => 'All papers submitted', 'de' => 'Alle Arbeiten eingereicht'],
    'papers_pending' => ['ar' => 'بعض الأوراق لم تقدم بعد', 'en' => 'Some papers not submitted yet', 'de' => 'Einige Arbeiten noch nicht eingereicht'],

    // Student project limit
    'project_limit_reached' => ['ar' => 'تم الوصول للحد الأقصى', 'en' => 'Project Limit Reached', 'de' => 'Projektlimit erreicht'],
    'project_limit_reached_msg' => ['ar' => 'أنت بالفعل عضو في مشروع. لا يمكنك الانضمام لمشروع آخر.', 'en' => 'You are already in a project. You cannot join another one.', 'de' => 'Sie sind bereits in einem Projekt. Sie können keinem anderen beitreten.'],
    'project_accepted_locked' => ['ar' => 'المشروع مقبول', 'en' => 'Project Accepted', 'de' => 'Projekt angenommen'],
    'project_accepted_locked_msg' => ['ar' => 'تم قبول مشروعك. لا يمكنك الانضمام لمشروع آخر.', 'en' => 'Your project has been accepted. You cannot join another project.', 'de' => 'Ihr Projekt wurde angenommen. Sie können keinem anderen Projekt beitreten.'],

    // Invitation API error/success messages
    'unauthorized' => ['ar' => 'غير مصرح', 'en' => 'Unauthorized', 'de' => 'Nicht autorisiert'],
    'project_id_required' => ['ar' => 'معرف المشروع مطلوب', 'en' => 'Project ID is required', 'de' => 'Projekt-ID ist erforderlich'],
    'leader_only_invite' => ['ar' => 'فقط قائد الفريق يمكنه إرسال الدعوات', 'en' => 'Only the team leader can send invitations', 'de' => 'Nur der Teamleiter kann Einladungen senden'],
    'project_status_no_invite' => ['ar' => 'لا يمكن إرسال دعوات للمشروع في حالته الحالية', 'en' => 'Cannot send invitations for a project in its current status', 'de' => 'Einladungen können für ein Projekt in diesem Status nicht gesendet werden'],
    'team_full' => ['ar' => 'الفريق مكتمل العدد', 'en' => 'The team is full', 'de' => 'Das Team ist voll'],
    'email_or_code_required' => ['ar' => 'البريد الإلكتروني أو كود الطالب مطلوب', 'en' => 'Email or student code is required', 'de' => 'E-Mail oder Studentencode ist erforderlich'],
    'student_not_found' => ['ar' => 'الطالب غير موجود', 'en' => 'Student not found', 'de' => 'Student nicht gefunden'],
    'pending_invitation_exists' => ['ar' => 'يوجد دعوة معلقة لهذا الطالب بالفعل', 'en' => 'A pending invitation already exists for this student', 'de' => 'Es gibt bereits eine ausstehende Einladung für diesen Studenten'],
    'invitation_sent_success' => ['ar' => 'تم إرسال الدعوة بنجاح', 'en' => 'Invitation sent successfully', 'de' => 'Einladung erfolgreich gesendet'],
    'invite_link_created' => ['ar' => 'تم إنشاء رابط الدعوة', 'en' => 'Invite link created', 'de' => 'Einladungslink erstellt'],
    'invalid_action' => ['ar' => 'الإجراء غير صالح', 'en' => 'Invalid action', 'de' => 'Ungültige Aktion'],
    'invalid_invitation_token' => ['ar' => 'رابط الدعوة غير صالح', 'en' => 'Invalid invitation link', 'de' => 'Ungültiger Einladungslink'],
    'invitation_already_used' => ['ar' => 'هذه الدعوة تم استخدامها بالفعل', 'en' => 'This invitation has already been used', 'de' => 'Diese Einladung wurde bereits verwendet'],
    'invitation_token_expired' => ['ar' => 'رابط الدعوة منتهي الصلاحية', 'en' => 'Invitation link has expired', 'de' => 'Einladungslink ist abgelaufen'],
    'invitation_not_for_you' => ['ar' => 'هذه الدعوة ليست موجهة لك', 'en' => 'This invitation is not meant for you', 'de' => 'Diese Einladung ist nicht für Sie bestimmt'],
    'cannot_decline_join_code' => ['ar' => 'لا يمكن رفض الانضمام بالكود', 'en' => 'Cannot decline a join-code membership', 'de' => 'Beitrittscode-Mitgliedschaft kann nicht abgelehnt werden'],
    'invitation_not_found' => ['ar' => 'الدعوة غير موجودة', 'en' => 'Invitation not found', 'de' => 'Einladung nicht gefunden'],
    'invitation_already_responded' => ['ar' => 'هذه الدعوة تم الرد عليها بالفعل', 'en' => 'This invitation has already been responded to', 'de' => 'Diese Einladung wurde bereits beantwortet'],
    'token_or_code_required' => ['ar' => 'يجب تحديد رمز الدعوة أو كود الانضمام', 'en' => 'A token or join code must be provided', 'de' => 'Ein Token oder Beitrittscode muss angegeben werden'],
    'invitation_declined' => ['ar' => 'تم رفض الدعوة', 'en' => 'Invitation declined', 'de' => 'Einladung abgelehnt'],
    'joined_project_success' => ['ar' => 'تم الانضمام للمشروع بنجاح', 'en' => 'Successfully joined the project', 'de' => 'Erfolgreich dem Projekt beigetreten'],
    'invitation_id_required' => ['ar' => 'معرف الدعوة مطلوب', 'en' => 'Invitation ID is required', 'de' => 'Einladungs-ID ist erforderlich'],
    'invitation_not_resendable' => ['ar' => 'الدعوة غير موجودة أو لا يمكن إعادة إرسالها', 'en' => 'Invitation not found or cannot be resent', 'de' => 'Einladung nicht gefunden oder kann nicht erneut gesendet werden'],
    'invitation_resent' => ['ar' => 'تم إعادة إرسال الدعوة بنجاح', 'en' => 'Invitation resent successfully', 'de' => 'Einladung erfolgreich erneut gesendet'],
    'invitation_not_cancellable' => ['ar' => 'الدعوة غير موجودة أو لا يمكن إلغاؤها', 'en' => 'Invitation not found or cannot be cancelled', 'de' => 'Einladung nicht gefunden oder kann nicht abgebrochen werden'],
    'invitation_cancelled' => ['ar' => 'تم إلغاء الدعوة', 'en' => 'Invitation cancelled', 'de' => 'Einladung abgebrochen'],

    // Review API messages
    'project_not_found' => ['ar' => 'المشروع غير موجود', 'en' => 'Project not found', 'de' => 'Projekt nicht gefunden'],
    'invalid_project_state' => ['ar' => 'لا يمكن تنفيذ هذا الإجراء على المشروع في حالته الحالية', 'en' => 'This action cannot be performed on the project in its current state', 'de' => 'Diese Aktion kann nicht für das Projekt in seinem aktuellen Status ausgeführt werden'],
    'review_project_accepted' => ['ar' => 'تم قبول المشروع وتعيين رقم المجموعة: %d', 'en' => 'Project accepted and assigned to group: %d', 'de' => 'Projekt angenommen und der Gruppe zugewiesen: %d'],
    'review_project_rejected' => ['ar' => 'تم رفض المشروع', 'en' => 'Project has been rejected', 'de' => 'Projekt wurde abgelehnt'],
];

/**
 * Get a translated string
 */
function __($key): string {
    global $translations, $currentLang;
    return $translations[$key][$currentLang] ?? $translations[$key]['ar'] ?? $key;
}

/**
 * Get current language
 */
function getLang(): string {
    global $currentLang;
    return $currentLang;
}

/**
 * Get text direction
 */
function getDir(): string {
    global $currentLang;
    return $currentLang === 'ar' ? 'rtl' : 'ltr';
}
