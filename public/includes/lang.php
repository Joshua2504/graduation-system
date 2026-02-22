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

    // Project
    'project_name' => ['ar' => 'اسم المشروع', 'en' => 'Project Name'],
    'project_type' => ['ar' => 'نوع المشروع', 'en' => 'Project Type'],
    'project_details' => ['ar' => 'تفاصيل المشروع', 'en' => 'Project Details'],
    'student_names' => ['ar' => 'أسماء الطلاب المشاركين', 'en' => 'Participating Student Names'],
    'student_data' => ['ar' => 'بيانات الطالب', 'en' => 'Student Data'],
    'confirmation' => ['ar' => 'التأكيد', 'en' => 'Confirmation'],
    'step' => ['ar' => 'الخطوة', 'en' => 'Step'],

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

    // Student dashboard
    'project_submitted' => ['ar' => 'تم تقديم مشروعك بنجاح وهو الآن قيد المراجعة. يرجى التحقق خلال 24 ساعة.', 'en' => 'Your project has been successfully submitted and is under review. Please check back within 24 hours.'],
    'project_accepted_msg' => ['ar' => 'تم قبول مشروعك. يرجى المتابعة مع مدرس المادة في الجامعة.', 'en' => 'Your project has been accepted. Please continue with the course instructor at the university.'],
    'project_rejected_msg' => ['ar' => 'تم رفض مشروعك. يمكنك تعديل البيانات وإعادة التقديم.', 'en' => 'Your project has been rejected. You can edit your data and resubmit.'],
    'group_number' => ['ar' => 'رقم المجموعة', 'en' => 'Group Number'],
    'doctor_note' => ['ar' => 'ملاحظة الدكتور', 'en' => "Professor's Note"],
    'resume_project' => ['ar' => 'متابعة المشروع', 'en' => 'Resume Project'],
    'new_project' => ['ar' => 'مشروع جديد', 'en' => 'New Project'],
    'edit_resubmit' => ['ar' => 'تعديل وإعادة التقديم', 'en' => 'Edit & Resubmit'],
    'auto_saved' => ['ar' => 'تم الحفظ تلقائياً', 'en' => 'Auto-saved'],

    // Step 9
    'confirm_warning' => ['ar' => 'تنبيه: في حالة قبول المشروع، لن يمكن تعديله بشكل دائم. في حالة الرفض، يمكن التعديل وإعادة التقديم.', 'en' => 'Warning: If the project is accepted, it cannot be edited permanently. If rejected, it can be edited and resubmitted.'],
    'confirm_checkbox' => ['ar' => 'أؤكد أن جميع البيانات المُدخلة صحيحة', 'en' => 'I confirm that all entered data is correct'],
    'submit_project' => ['ar' => 'تقديم المشروع', 'en' => 'Submit Project'],

    // Professor
    'projects_under_review' => ['ar' => 'مشاريع قيد المراجعة', 'en' => 'Projects Under Review'],
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
