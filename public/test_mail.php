<?php
require_once __DIR__ . '/includes/mailer.php';
$result = sendMail('test@treudler.net', 'Test from Grad System', '<h2>Hello</h2><p>This is a test email from the graduation system.</p>');
echo $result ? 'EMAIL_SENT_OK' : 'EMAIL_SEND_FAIL';
echo PHP_EOL;
// Clean up test user if exists
$pdo = getDB();
$pdo->exec("DELETE FROM users WHERE email = 'test@treudler.net'");
echo 'CLEANUP_DONE' . PHP_EOL;
