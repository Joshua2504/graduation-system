<?php
/**
 * Language / i18n support — Arabic + English + German (Deutsch)
 */

$supportedLangs = ['ar', 'en', 'de'];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLangs)) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Detect browser language if no session language is set
if (!isset($_SESSION['lang'])) {
    $browserLang = 'ar'; // ultimate fallback
    if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $acceptLang = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        // Find which supported language appears first in the Accept-Language header
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
    'required_field' => ['ar' => 'هذا الحقل مطلوب', 'en' => 'This field is required', 'de' => 'Dieses Feld ist erforderlich'],
    'settings' => ['ar' => 'الإعدادات', 'en' => 'Settings', 'de' => 'Einstellungen'],
    'actions' => ['ar' => 'الإجراءات', 'en' => 'Actions', 'de' => 'Aktionen'],
    'confirm' => ['ar' => 'تأكيد', 'en' => 'Confirm', 'de' => 'Bestätigen'],
    'delete' => ['ar' => 'حذف', 'en' => 'Delete', 'de' => 'Löschen'],
    'search' => ['ar' => 'بحث', 'en' => 'Search', 'de' => 'Suchen'],

    // Auth
    'email' => ['ar' => 'البريد الإلكتروني', 'en' => 'Email Address'],
    'password' => ['ar' => 'كلمة المرور', 'en' => 'Password'],
    'name' => ['ar' => 'الاسم', 'en' => 'Name'],
    'student_code' => ['ar' => 'كود الطالب', 'en' => 'Student Code'],
    'login_title' => ['ar' => 'تسجيل الدخول', 'en' => 'Sign In'],
    'register_title' => ['ar' => 'تسجيل حساب جديد', 'en' => 'Create Account'],
    'no_account' => ['ar' => 'ليس لديك حساب؟', 'en' => "Don't have an account?"],
    'has_account' => ['ar' => 'لديك حساب بالفعل؟', 'en' => 'Already have an account?'],
    'registration_closed' => ['ar' => 'تسجيل مشاريع التخرج مغلق حالياً', 'en' => 'Graduation project registration is currently closed'],
    'invalid_credentials' => ['ar' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة', 'en' => 'Invalid email or password'],
    'email_exists' => ['ar' => 'البريد الإلكتروني مسجل بالفعل', 'en' => 'Email already registered'],
    'code_exists' => ['ar' => 'كود الطالب مسجل بالفعل', 'en' => 'Student code already registered'],
    'verification_sent' => ['ar' => 'تم إرسال رابط التأكيد إلى بريدك الإلكتروني. يرجى التحقق من صندوق الوارد لتفعيل حسابك.', 'en' => 'A verification link has been sent to your email. Please check your inbox to activate your account.'],
    'verification_sent_fallback' => ['ar' => 'تم إنشاء حسابك. يرجى تسجيل الدخول وطلب إعادة إرسال رابط التأكيد.', 'en' => 'Your account has been created. Please login and request a new verification link.'],
    'email_not_verified' => ['ar' => 'لم يتم تأكيد بريدك الإلكتروني بعد. يرجى التحقق من بريدك الإلكتروني.', 'en' => 'Your email has not been verified yet. Please check your email.'],
    'resend_verification' => ['ar' => 'إعادة إرسال رابط التأكيد', 'en' => 'Resend Verification Link'],
    'verification_resent' => ['ar' => 'تم إعادة إرسال رابط التأكيد إلى بريدك الإلكتروني.', 'en' => 'Verification link has been resent to your email.'],
    'email_verified_success' => ['ar' => 'تم تأكيد بريدك الإلكتروني بنجاح! يمكنك الآن تسجيل الدخول.', 'en' => 'Your email has been verified successfully! You can now login.'],
    'verification_invalid' => ['ar' => 'رابط التأكيد غير صالح أو منتهي الصلاحية.', 'en' => 'Verification link is invalid or has expired.'],
    'verification_already' => ['ar' => 'تم تأكيد بريدك الإلكتروني مسبقاً.', 'en' => 'Your email has already been verified.'],

    // Profile
    'my_profile' => ['ar' => 'ملفي الشخصي', 'en' => 'My Profile'],
    'profile_incomplete' => ['ar' => 'الملف الشخصي غير مكتمل', 'en' => 'Profile Incomplete'],
    'profile_complete' => ['ar' => 'الملف الشخصي مكتمل', 'en' => 'Profile Complete'],
    'complete_profile' => ['ar' => 'أكمل ملفك الشخصي', 'en' => 'Complete Your Profile'],
    'profile_info' => ['ar' => 'بيانات الملف الشخصي تُستخدم في جميع المشاريع التي تنضم إليها', 'en' => 'Profile data is used across all projects you join'],
    'personal_info' => ['ar' => 'البيانات الشخصية', 'en' => 'Personal Information'],
    'documents' => ['ar' => 'المستندات', 'en' => 'Documents'],

    // Project
    'project_name' => ['ar' => 'اسم المشروع', 'en' => 'Project Name'],
    'project_type' => ['ar' => 'نوع المشروع', 'en' => 'Project Type'],
    'project_details' => ['ar' => 'تفاصيل المشروع', 'en' => 'Project Details'],
    'create_project' => ['ar' => 'إنشاء مشروع', 'en' => 'Create Project'],
    'my_projects' => ['ar' => 'مشاريعي', 'en' => 'My Projects'],
    'no_projects_yet' => ['ar' => 'لا توجد مشاريع بعد', 'en' => 'No projects yet'],

    // Student fields
    'gender' => ['ar' => 'الجنس', 'en' => 'Gender'],
    'male' => ['ar' => 'ذكر', 'en' => 'Male'],
    'female' => ['ar' => 'أنثى', 'en' => 'Female'],
    'national_id' => ['ar' => 'الرقم القومي', 'en' => 'National ID Number'],
    'birth_date' => ['ar' => 'تاريخ الميلاد', 'en' => 'Date of Birth'],
    'governorate' => ['ar' => 'المحافظة', 'en' => 'Governorate'],
    'address' => ['ar' => 'العنوان', 'en' => 'Address'],
    'phone' => ['ar' => 'رقم الهاتف', 'en' => 'Phone Number'],
    'year' => ['ar' => 'السنة الدراسية', 'en' => 'Year'],
    'section' => ['ar' => 'القسم', 'en' => 'Department'],
    'fourth_year' => ['ar' => 'الرابعة', 'en' => '4th'],

    // Images
    'card_image' => ['ar' => 'صورة بطاقة المعهد', 'en' => 'Institute ID Card'],
    'national_id_image' => ['ar' => 'صورة البطاقة الشخصية', 'en' => 'National ID Card'],
    'receipt_image' => ['ar' => 'صورة إيصال دفع مشروع التخرج', 'en' => 'Graduation Project Payment Receipt'],
    'profile_picture' => ['ar' => 'صورة شخصية', 'en' => 'Profile Picture'],
    'profile_picture_hint' => ['ar' => 'صورة شخصية اختيارية تظهر في جميع أنحاء المنصة', 'en' => 'Optional profile picture displayed across the platform'],
    'upload_image' => ['ar' => 'رفع صورة', 'en' => 'Upload Image'],
    'image_requirements' => ['ar' => 'JPG أو PNG فقط - الحد الأقصى 5 ميجابايت', 'en' => 'JPG or PNG only - Max 5MB'],
    'uploading' => ['ar' => 'جاري رفع الملفات...', 'en' => 'Uploading files...'],

    // Status
    'status_draft' => ['ar' => 'مسودة', 'en' => 'Draft'],
    'status_under_review' => ['ar' => 'قيد المراجعة', 'en' => 'Under Review'],
    'status_accepted' => ['ar' => 'مقبول', 'en' => 'Accepted'],
    'status_rejected' => ['ar' => 'مرفوض', 'en' => 'Rejected'],

    // Team & Invitations
    'team_members' => ['ar' => 'أعضاء الفريق', 'en' => 'Team Members'],
    'member_count' => ['ar' => 'عدد الأعضاء', 'en' => 'Member Count'],
    'invite_members' => ['ar' => 'دعوة أعضاء', 'en' => 'Invite Members'],
    'join_project' => ['ar' => 'الانضمام لمشروع', 'en' => 'Join Project'],
    'join_code' => ['ar' => 'كود الانضمام', 'en' => 'Join Code'],
    'invite_link' => ['ar' => 'رابط الدعوة', 'en' => 'Invite Link'],
    'qr_code' => ['ar' => 'رمز QR', 'en' => 'QR Code'],
    'invite_by_search' => ['ar' => 'دعوة بالبريد أو الكود', 'en' => 'Invite by Email or Code'],
    'send_invite' => ['ar' => 'إرسال دعوة', 'en' => 'Send Invite'],
    'pending_invitations' => ['ar' => 'الدعوات المعلقة', 'en' => 'Pending Invitations'],
    'accept_invite' => ['ar' => 'قبول', 'en' => 'Accept'],
    'decline_invite' => ['ar' => 'رفض', 'en' => 'Decline'],
    'leave_project' => ['ar' => 'مغادرة المشروع', 'en' => 'Leave Project'],
    'remove_member' => ['ar' => 'إزالة العضو', 'en' => 'Remove Member'],
    'generate_link' => ['ar' => 'إنشاء رابط', 'en' => 'Generate Link'],
    'copy_link' => ['ar' => 'نسخ الرابط', 'en' => 'Copy Link'],
    'link_copied' => ['ar' => 'تم نسخ الرابط', 'en' => 'Link Copied!'],
    'enter_join_code' => ['ar' => 'أدخل كود الانضمام', 'en' => 'Enter Join Code'],
    'project_full' => ['ar' => 'الفريق مكتمل العدد', 'en' => 'Team is full'],
    'already_member' => ['ar' => 'أنت عضو بالفعل', 'en' => 'Already a member'],
    'invitation_expired' => ['ar' => 'الدعوة منتهية الصلاحية', 'en' => 'Invitation expired'],
    'invitation_sent' => ['ar' => 'تم إرسال الدعوة', 'en' => 'Invitation sent'],
    'min_members' => ['ar' => 'الحد الأدنى للأعضاء', 'en' => 'Minimum Members'],
    'max_members' => ['ar' => 'الحد الأقصى للأعضاء', 'en' => 'Maximum Members'],
    'team_size' => ['ar' => 'حجم الفريق', 'en' => 'Team Size'],
    'all_profiles_complete' => ['ar' => 'جميع الملفات مكتملة', 'en' => 'All profiles complete'],
    'profiles_incomplete' => ['ar' => 'بعض الملفات غير مكتملة', 'en' => 'Some profiles incomplete'],
    'leader' => ['ar' => 'قائد', 'en' => 'Leader'],
    'member' => ['ar' => 'عضو', 'en' => 'Member'],

    // Student dashboard
    'project_submitted' => ['ar' => 'تم تقديم مشروعك بنجاح وهو الآن قيد المراجعة. يرجى التحقق خلال 24 ساعة.', 'en' => 'Your project has been successfully submitted and is under review. Please check back within 24 hours.'],
    'project_accepted_msg' => ['ar' => 'تم قبول مشروعك. يرجى المتابعة مع مدرس المادة في الجامعة.', 'en' => 'Your project has been accepted. Please continue with the course instructor at the university.'],
    'project_rejected_msg' => ['ar' => 'تم رفض مشروعك. يمكنك تعديل البيانات وإعادة التقديم.', 'en' => 'Your project has been rejected. You can edit your data and resubmit.'],
    'group_number' => ['ar' => 'رقم المجموعة', 'en' => 'Group Number'],
    'doctor_note' => ['ar' => 'ملاحظة الدكتور', 'en' => "Professor's Note"],
    'submit_project' => ['ar' => 'تقديم المشروع', 'en' => 'Submit Project'],
    'confirm_submit' => ['ar' => 'تأكيد: سيتم تقديم المشروع للمراجعة. هل أنت متأكد؟', 'en' => 'Confirm: The project will be submitted for review. Are you sure?'],
    'edit_resubmit' => ['ar' => 'إعادة التقديم', 'en' => 'Resubmit'],

    // Professor
    'projects_under_review' => ['ar' => 'مشاريع قيد المراجعة', 'en' => 'Projects Under Review'],
    'draft_projects' => ['ar' => 'مشاريع مسودة', 'en' => 'Draft Projects'],
    'accepted_projects' => ['ar' => 'المشاريع المقبولة', 'en' => 'Accepted Projects'],
    'rejected_projects' => ['ar' => 'المشاريع المرفوضة', 'en' => 'Rejected Projects'],
    'sort_recent' => ['ar' => 'الأحدث', 'en' => 'Most Recent'],
    'sort_oldest' => ['ar' => 'الأقدم', 'en' => 'Oldest'],
    'accept' => ['ar' => 'قبول', 'en' => 'Accept'],
    'reject' => ['ar' => 'رفض', 'en' => 'Reject'],
    'write_note' => ['ar' => 'اكتب ملاحظة (اختياري)', 'en' => 'Write a note (optional)'],
    'view_project' => ['ar' => 'عرض المشروع', 'en' => 'View Project'],
    'duplicate_warning' => ['ar' => '⚠ تنبيه: يوجد مشروع آخر بنفس الاسم', 'en' => '⚠ Warning: Another project with the same name exists'],
    'view_similar' => ['ar' => 'عرض المشروع المشابه', 'en' => 'View Similar Project'],
    'registration_open' => ['ar' => 'التسجيل مفتوح', 'en' => 'Registration Open'],
    'registration_locked' => ['ar' => 'التسجيل مغلق', 'en' => 'Registration Locked'],
    'toggle_registration' => ['ar' => 'تبديل حالة التسجيل', 'en' => 'Toggle Registration'],
    'toggle_email_verification' => ['ar' => 'تأكيد البريد الإلكتروني', 'en' => 'Email Verification'],
    'student_accounts' => ['ar' => 'حسابات الطلاب', 'en' => 'Student Accounts'],
    'account_disabled' => ['ar' => 'تم تعطيل حسابك. يرجى التواصل مع المسؤول.', 'en' => 'Your account has been disabled. Please contact the administrator.'],
    'no_projects' => ['ar' => 'لا توجد مشاريع', 'en' => 'No projects found'],
    'team_leader' => ['ar' => 'قائد الفريق', 'en' => 'Team Leader'],
    'submission_date' => ['ar' => 'تاريخ التقديم', 'en' => 'Submission Date'],

    // Professor — Students Page
    'add_student' => ['ar' => 'إضافة طالب', 'en' => 'Add Student'],
    'students_count' => ['ar' => 'طالب', 'en' => 'students'],
    'search_placeholder' => ['ar' => 'بحث...', 'en' => 'Search...'],
    'no_registered_students' => ['ar' => 'لا يوجد طلاب مسجلين', 'en' => 'No registered students'],
    'profile' => ['ar' => 'الملف', 'en' => 'Profile'],
    'email_col' => ['ar' => 'البريد', 'en' => 'Email'],
    'account' => ['ar' => 'الحساب', 'en' => 'Account'],
    'registered' => ['ar' => 'تاريخ التسجيل', 'en' => 'Registered'],
    'verified' => ['ar' => 'مؤكد', 'en' => 'Verified'],
    'unverified' => ['ar' => 'غير مؤكد', 'en' => 'Unverified'],
    'active' => ['ar' => 'نشط', 'en' => 'Active'],
    'disabled' => ['ar' => 'معطل', 'en' => 'Disabled'],
    'edit_profile' => ['ar' => 'تعديل الملف الشخصي', 'en' => 'Edit Profile'],
    'verify_email' => ['ar' => 'تأكيد البريد', 'en' => 'Verify Email'],
    'resend_verification_email' => ['ar' => 'إعادة إرسال رابط التأكيد', 'en' => 'Resend Verification Email'],
    'unverify' => ['ar' => 'إلغاء التأكيد', 'en' => 'Unverify'],
    'disable_account' => ['ar' => 'تعطيل الحساب', 'en' => 'Disable Account'],
    'enable_account' => ['ar' => 'تفعيل الحساب', 'en' => 'Enable Account'],
    'login_as_student' => ['ar' => 'الدخول كطالب', 'en' => 'Login as Student'],
    'edit_student_profile' => ['ar' => 'تعديل بيانات الطالب', 'en' => 'Edit Student Profile'],
    'basic_info' => ['ar' => 'البيانات الأساسية', 'en' => 'Basic Info'],
    'select_option' => ['ar' => 'اختر', 'en' => 'Select'],
    'select_governorate' => ['ar' => 'اختر المحافظة', 'en' => 'Select Governorate'],
    'no_image' => ['ar' => 'لا توجد صورة', 'en' => 'No image'],
    'institute_id' => ['ar' => 'بطاقة المعهد', 'en' => 'Institute ID'],
    'national_id_card' => ['ar' => 'البطاقة الشخصية', 'en' => 'National ID'],
    'payment_receipt' => ['ar' => 'إيصال الدفع', 'en' => 'Payment Receipt'],
    'add_new_student' => ['ar' => 'إضافة طالب جديد', 'en' => 'Add New Student'],
    'generate_password' => ['ar' => 'توليد كلمة مرور', 'en' => 'Generate password'],
    'send_welcome_email' => ['ar' => 'إرسال بريد ترحيبي بالبيانات', 'en' => 'Send welcome email with credentials'],
    'create_account' => ['ar' => 'إنشاء الحساب', 'en' => 'Create Account'],
    'confirm_verify_email' => ['ar' => 'تأكيد بريد هذا الطالب؟', 'en' => "Verify this student's email?"],
    'confirm_unverify_email' => ['ar' => 'إلغاء تأكيد بريد هذا الطالب؟', 'en' => "Unverify this student's email?"],
    'confirm_enable_account' => ['ar' => 'تفعيل حساب هذا الطالب؟', 'en' => "Enable this student's account?"],
    'confirm_disable_account' => ['ar' => 'تعطيل حساب هذا الطالب؟', 'en' => "Disable this student's account?"],
    'confirm_delete_account' => ['ar' => 'حذف هذا الحساب نهائياً؟ سيتم حذف جميع بياناته.', 'en' => 'Permanently delete this account? All data will be removed.'],
    'confirm_resend_verification' => ['ar' => 'إعادة إرسال رابط التأكيد؟', 'en' => 'Resend verification email?'],
    'confirm_impersonate' => ['ar' => 'الدخول كهذا الطالب؟', 'en' => 'Login as this student?'],
    'name_email_password_required' => ['ar' => 'الاسم والبريد وكلمة المرور مطلوبين', 'en' => 'Name, email and password are required'],
    'email_sent' => ['ar' => 'تم إرسال البريد', 'en' => 'Email sent'],

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

    // Professor — Settings Page
    'settings_saved' => ['ar' => 'تم حفظ الإعدادات بنجاح', 'en' => 'Settings saved successfully', 'de' => 'Einstellungen erfolgreich gespeichert'],
    'registration_description' => ['ar' => 'التحكم في إمكانية تسجيل حسابات جديدة للطلاب', 'en' => 'Control whether new student accounts can be registered', 'de' => 'Steuern Sie, ob neue Studentenkonten registriert werden können'],
    'email_verification_description' => ['ar' => 'عند التفعيل، يجب على الطلاب تأكيد بريدهم الإلكتروني قبل تسجيل الدخول', 'en' => 'When enabled, students must verify their email before logging in', 'de' => 'Wenn aktiviert, müssen Studenten ihre E-Mail bestätigen, bevor sie sich anmelden können'],
    'team_size_description' => ['ar' => 'تحديد الحد الأدنى والأقصى لعدد أعضاء الفريق', 'en' => 'Set the minimum and maximum number of team members', 'de' => 'Minimale und maximale Anzahl der Teammitglieder festlegen'],
    'toggle_student_project_creation' => ['ar' => 'إنشاء المشاريع بواسطة الطلاب', 'en' => 'Student Project Creation', 'de' => 'Projekterstellung durch Studenten'],
    'student_project_creation_description' => ['ar' => 'السماح للطلاب بإنشاء مشاريع جديدة بأنفسهم', 'en' => 'Allow students to create new projects on their own', 'de' => 'Studenten erlauben, eigene Projekte zu erstellen'],
    'student_creation_on' => ['ar' => 'إنشاء المشاريع مفعل', 'en' => 'Student Creation On', 'de' => 'Studenten-Erstellung An'],
    'student_creation_off' => ['ar' => 'إنشاء المشاريع معطل', 'en' => 'Student Creation Off', 'de' => 'Studenten-Erstellung Aus'],
    'student_project_creation_disabled' => ['ar' => 'إنشاء المشاريع بواسطة الطلاب معطل حالياً', 'en' => 'Student project creation is currently disabled', 'de' => 'Die Projekterstellung durch Studenten ist derzeit deaktiviert'],
    'email_verification_on' => ['ar' => 'تأكيد البريد مطلوب', 'en' => 'Email Verification On', 'de' => 'E-Mail-Bestätigung An'],
    'email_verification_off' => ['ar' => 'تأكيد البريد معطل', 'en' => 'Email Verification Off', 'de' => 'E-Mail-Bestätigung Aus'],

    // Student — Project Page
    'role' => ['ar' => 'الدور', 'en' => 'Role', 'de' => 'Rolle'],
    'invite_link_label' => ['ar' => 'رابط دعوة', 'en' => 'Invite link', 'de' => 'Einladungslink'],
    'pending_status' => ['ar' => 'في الانتظار', 'en' => 'Pending', 'de' => 'Ausstehend'],
    'invited' => ['ar' => 'مدعو', 'en' => 'Invited', 'de' => 'Eingeladen'],
    'resend_invitation' => ['ar' => 'إعادة إرسال الدعوة', 'en' => 'Resend Invitation', 'de' => 'Einladung erneut senden'],
    'cancel_invitation' => ['ar' => 'إلغاء الدعوة', 'en' => 'Cancel Invitation', 'de' => 'Einladung abbrechen'],
    'email_or_code_placeholder' => ['ar' => 'بريد إلكتروني أو كود طالب', 'en' => 'Email or student code', 'de' => 'E-Mail oder Matrikelnummer'],
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
    'landing_feature_bilingual' => ['ar' => 'دعم ثنائي اللغة', 'en' => 'Bilingual Support', 'de' => 'Mehrsprachige Unterstützung'],
    'landing_feature_bilingual_desc' => ['ar' => 'واجهة كاملة باللغتين العربية والإنجليزية', 'en' => 'Full interface in both Arabic and English', 'de' => 'Vollständige Oberfläche in Arabisch, Englisch und Deutsch'],
    'landing_welcome_back' => ['ar' => 'مرحباً بعودتك', 'en' => 'Welcome Back', 'de' => 'Willkommen zurück'],
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

    // Landing — Stats
    'landing_stat_easy' => ['ar' => 'سهل الاستخدام', 'en' => 'Easy to Use', 'de' => 'Einfach zu bedienen'],
    'landing_stat_fast' => ['ar' => 'سريع وفعّال', 'en' => 'Fast & Efficient', 'de' => 'Schnell & Effizient'],
    'landing_stat_complete' => ['ar' => 'نظام متكامل', 'en' => 'All-in-One', 'de' => 'Alles-in-Einem'],
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
