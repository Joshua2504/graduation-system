<?php
/**
 * Language / i18n support — Arabic (default) + English
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$currentLang = $_SESSION['lang'] ?? 'ar';

$translations = [
    // General
    'app_name' => ['ar' => 'نظام إدارة مشاريع التخرج', 'en' => 'Graduation Project Management System'],
    'login' => ['ar' => 'تسجيل الدخول', 'en' => 'Login'],
    'register' => ['ar' => 'إنشاء حساب', 'en' => 'Register'],
    'logout' => ['ar' => 'تسجيل الخروج', 'en' => 'Logout'],
    'dashboard' => ['ar' => 'لوحة التحكم', 'en' => 'Dashboard'],
    'submit' => ['ar' => 'إرسال', 'en' => 'Submit'],
    'save' => ['ar' => 'حفظ', 'en' => 'Save'],
    'next' => ['ar' => 'التالي', 'en' => 'Next'],
    'previous' => ['ar' => 'السابق', 'en' => 'Previous'],
    'cancel' => ['ar' => 'إلغاء', 'en' => 'Cancel'],
    'close' => ['ar' => 'إغلاق', 'en' => 'Close'],
    'yes' => ['ar' => 'نعم', 'en' => 'Yes'],
    'no' => ['ar' => 'لا', 'en' => 'No'],
    'loading' => ['ar' => 'جاري التحميل...', 'en' => 'Loading...'],
    'error' => ['ar' => 'خطأ', 'en' => 'Error'],
    'success' => ['ar' => 'نجاح', 'en' => 'Success'],
    'required_field' => ['ar' => 'هذا الحقل مطلوب', 'en' => 'This field is required'],
    'settings' => ['ar' => 'الإعدادات', 'en' => 'Settings'],
    'actions' => ['ar' => 'الإجراءات', 'en' => 'Actions'],
    'confirm' => ['ar' => 'تأكيد', 'en' => 'Confirm'],
    'delete' => ['ar' => 'حذف', 'en' => 'Delete'],
    'search' => ['ar' => 'بحث', 'en' => 'Search'],

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
