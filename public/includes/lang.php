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
    'back_to_list' => ['ar' => 'العودة للقائمة', 'en' => 'Back to List'],
    'pending' => ['ar' => 'دعوات معلقة', 'en' => 'pending'],
    'student' => ['ar' => 'الطالب', 'en' => 'Student'],
    'missing' => ['ar' => 'غير متوفرة', 'en' => 'Missing'],
    'invited_student' => ['ar' => 'الطالب المدعو', 'en' => 'Invited Student'],
    'code' => ['ar' => 'الكود', 'en' => 'Code'],
    'invited_by' => ['ar' => 'بواسطة', 'en' => 'Invited By'],
    'invited_at' => ['ar' => 'تاريخ الدعوة', 'en' => 'Invited At'],
    'expires' => ['ar' => 'تنتهي في', 'en' => 'Expires'],
    'general_invite_link' => ['ar' => 'رابط دعوة عام', 'en' => 'General invite link'],
    'resend' => ['ar' => 'إعادة إرسال', 'en' => 'Resend'],
    'review_project' => ['ar' => 'مراجعة المشروع', 'en' => 'Review Project'],
    'write_note_placeholder' => ['ar' => 'اكتب ملاحظتك هنا...', 'en' => 'Write your note here...'],
    'confirm_accept_project' => ['ar' => 'هل أنت متأكد من قبول هذا المشروع؟', 'en' => 'Are you sure you want to accept this project?'],
    'confirm_reject_project' => ['ar' => 'هل أنت متأكد من رفض هذا المشروع؟', 'en' => 'Are you sure you want to reject this project?'],

    // Professor — Settings Page
    'settings_saved' => ['ar' => 'تم حفظ الإعدادات بنجاح', 'en' => 'Settings saved successfully'],
    'registration_description' => ['ar' => 'التحكم في إمكانية تسجيل حسابات جديدة للطلاب', 'en' => 'Control whether new student accounts can be registered'],
    'email_verification_description' => ['ar' => 'عند التفعيل، يجب على الطلاب تأكيد بريدهم الإلكتروني قبل تسجيل الدخول', 'en' => 'When enabled, students must verify their email before logging in'],
    'team_size_description' => ['ar' => 'تحديد الحد الأدنى والأقصى لعدد أعضاء الفريق', 'en' => 'Set the minimum and maximum number of team members'],
    'toggle_student_project_creation' => ['ar' => 'إنشاء المشاريع بواسطة الطلاب', 'en' => 'Student Project Creation'],
    'student_project_creation_description' => ['ar' => 'السماح للطلاب بإنشاء مشاريع جديدة بأنفسهم', 'en' => 'Allow students to create new projects on their own'],
    'student_creation_on' => ['ar' => 'إنشاء المشاريع مفعل', 'en' => 'Student Creation On'],
    'student_creation_off' => ['ar' => 'إنشاء المشاريع معطل', 'en' => 'Student Creation Off'],
    'student_project_creation_disabled' => ['ar' => 'إنشاء المشاريع بواسطة الطلاب معطل حالياً', 'en' => 'Student project creation is currently disabled'],
    'email_verification_on' => ['ar' => 'تأكيد البريد مطلوب', 'en' => 'Email Verification On'],
    'email_verification_off' => ['ar' => 'تأكيد البريد معطل', 'en' => 'Email Verification Off'],

    // Student — Project Page
    'role' => ['ar' => 'الدور', 'en' => 'Role'],
    'invite_link_label' => ['ar' => 'رابط دعوة', 'en' => 'Invite link'],
    'pending_status' => ['ar' => 'في الانتظار', 'en' => 'Pending'],
    'invited' => ['ar' => 'مدعو', 'en' => 'Invited'],
    'resend_invitation' => ['ar' => 'إعادة إرسال الدعوة', 'en' => 'Resend Invitation'],
    'cancel_invitation' => ['ar' => 'إلغاء الدعوة', 'en' => 'Cancel Invitation'],
    'email_or_code_placeholder' => ['ar' => 'بريد إلكتروني أو كود طالب', 'en' => 'Email or student code'],
    'team_min_size_msg' => ['ar' => 'يجب أن يكون الفريق %d أعضاء على الأقل', 'en' => 'Team needs at least %d members'],
    'delete_project' => ['ar' => 'حذف المشروع', 'en' => 'Delete Project'],
    'project_description' => ['ar' => 'وصف المشروع', 'en' => 'Project Description'],
    'description_placeholder' => ['ar' => 'اكتب وصفاً تفصيلياً للمشروع...', 'en' => 'Write a detailed project description...'],
    'bold' => ['ar' => 'عريض', 'en' => 'Bold'],
    'italic' => ['ar' => 'مائل', 'en' => 'Italic'],
    'underline_text' => ['ar' => 'تحته خط', 'en' => 'Underline'],
    'bullet_list' => ['ar' => 'قائمة نقطية', 'en' => 'Bullet List'],
    'numbered_list' => ['ar' => 'قائمة مرقمة', 'en' => 'Numbered List'],
    'insert_link' => ['ar' => 'إدراج رابط', 'en' => 'Insert Link'],
    'upload_file' => ['ar' => 'رفع صورة', 'en' => 'Upload Image'],
    'uploading_file' => ['ar' => 'جاري الرفع...', 'en' => 'Uploading...'],
    'no_description' => ['ar' => 'لا يوجد وصف للمشروع بعد.', 'en' => 'No project description yet.'],
    'edit_project_info' => ['ar' => 'تعديل المشروع', 'en' => 'Edit Project'],
    'save_changes' => ['ar' => 'حفظ التغييرات', 'en' => 'Save Changes'],
    'confirm_remove_member' => ['ar' => 'هل تريد إزالة', 'en' => 'Remove'],
    'confirm_leave_project' => ['ar' => 'هل تريد مغادرة هذا المشروع؟', 'en' => 'Leave this project?'],
    'confirm_delete_project' => ['ar' => 'هل أنت متأكد من حذف هذا المشروع؟ سيتم حذفه نهائياً.', 'en' => 'Are you sure you want to delete this project? This is permanent.'],
    'confirm_submit_project' => ['ar' => 'تأكيد: سيتم تقديم المشروع للمراجعة. هل أنت متأكد؟', 'en' => 'Confirm: The project will be submitted for review. Are you sure?'],
    'confirm_cancel_invitation' => ['ar' => 'هل تريد إلغاء هذه الدعوة؟', 'en' => 'Cancel this invitation?'],

    // Student — Dashboard
    'profile_incomplete_msg' => ['ar' => 'يجب إكمال ملفك الشخصي قبل تقديم أي مشروع.', 'en' => 'You must complete your profile before submitting any project.'],
    'no_projects_msg' => ['ar' => 'أنشئ مشروعًا جديدًا أو انضم لمشروع عن طريق كود الانضمام.', 'en' => 'Create a new project or join one using a join code.'],
    'from' => ['ar' => 'من', 'en' => 'From'],
    'project_type_placeholder' => ['ar' => 'مثال: تطبيق ويب', 'en' => 'e.g., Web Application'],

    // Student — Profile
    'select_option_full' => ['ar' => '-- اختر --', 'en' => '-- Select --'],
    'fourteen_digits' => ['ar' => '14 رقم', 'en' => '14 digits'],
    'select_governorate_full' => ['ar' => '-- اختر المحافظة --', 'en' => '-- Select Governorate --'],
    'eleven_digits' => ['ar' => '11 رقم', 'en' => '11 digits'],
    'change' => ['ar' => 'تغيير', 'en' => 'Change'],

    // Join Page
    'invalid_invitation_link' => ['ar' => 'رابط الدعوة غير صالح أو منتهي الصلاحية', 'en' => 'Invalid or expired invitation link'],
    'project_not_accepting' => ['ar' => 'هذا المشروع لم يعد يقبل أعضاء جدد', 'en' => 'This project is no longer accepting new members'],
    'invalid_join_code' => ['ar' => 'كود الانضمام غير صالح', 'en' => 'Invalid join code'],
    'join_project_title' => ['ar' => 'الانضمام لمشروع', 'en' => 'Join Project'],
    'project_invitation' => ['ar' => 'دعوة للانضمام', 'en' => 'Project Invitation'],
    'already_in_project' => ['ar' => 'أنت بالفعل عضو في هذا المشروع', 'en' => 'You are already a member of this project'],
    'type' => ['ar' => 'النوع', 'en' => 'Type'],
    'invited_by_label' => ['ar' => 'دعوة من', 'en' => 'Invited by'],
    'join' => ['ar' => 'انضمام', 'en' => 'Join'],
    'decline' => ['ar' => 'تجاهل', 'en' => 'Decline'],

    // Register Page
    'invalid_email' => ['ar' => 'البريد الإلكتروني غير صالح', 'en' => 'Invalid email address'],
    'password_min_length' => ['ar' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل', 'en' => 'Password must be at least 6 characters'],
    'student_code_format' => ['ar' => 'كود الطالب يجب أن يكون أحرف وأرقام بحد أقصى 30 حرف', 'en' => 'Student code must be alphanumeric, max 30 characters'],
    'password_hint' => ['ar' => '6 أحرف على الأقل', 'en' => 'At least 6 characters'],
    'student_code_hint' => ['ar' => 'أحرف وأرقام، بحد أقصى 30 حرف', 'en' => 'Alphanumeric, max 30 characters'],

    // Verify Page
    'email_verification_title' => ['ar' => 'تأكيد البريد الإلكتروني', 'en' => 'Email Verification'],
    'verification_request_new_link' => ['ar' => 'يرجى تسجيل الدخول وطلب إعادة إرسال رابط التأكيد.', 'en' => 'Please login and request a new verification link.'],

    // Professor — Create & Assign
    'create_project_professor' => ['ar' => 'إنشاء مشروع جديد', 'en' => 'Create New Project'],
    'assign_students' => ['ar' => 'تعيين طلاب', 'en' => 'Assign Students'],
    'assign_student' => ['ar' => 'تعيين طالب', 'en' => 'Assign Student'],
    'search_students' => ['ar' => 'بحث عن طالب (بالاسم أو البريد أو الكود)', 'en' => 'Search student (by name, email or code)'],
    'search_add_students' => ['ar' => 'بحث وإضافة طلاب', 'en' => 'Search & Add Students'],
    'no_results' => ['ar' => 'لا توجد نتائج', 'en' => 'No results found'],
    'student_already_in_project' => ['ar' => 'الطالب عضو بالفعل في هذا المشروع', 'en' => 'Student is already a member of this project'],
    'student_added' => ['ar' => 'تم إضافة الطالب بنجاح', 'en' => 'Student added successfully'],
    'student_removed' => ['ar' => 'تم إزالة الطالب بنجاح', 'en' => 'Student removed successfully'],
    'set_as_leader' => ['ar' => 'تعيين كقائد', 'en' => 'Set as Leader'],
    'leader_changed' => ['ar' => 'تم تغيير قائد الفريق', 'en' => 'Team leader changed'],
    'remove_from_project' => ['ar' => 'إزالة من المشروع', 'en' => 'Remove from Project'],
    'confirm_remove_from_project' => ['ar' => 'هل تريد إزالة هذا الطالب من المشروع؟', 'en' => 'Remove this student from the project?'],
    'student_has_project' => ['ar' => 'هذا الطالب لديه مشروع بالفعل', 'en' => 'This student already has a project'],
    'select_leader_first' => ['ar' => 'يجب اختيار قائد للفريق أولاً', 'en' => 'A team leader must be selected first'],
    'project_created_by_professor' => ['ar' => 'تم إنشاء المشروع بنجاح', 'en' => 'Project created successfully'],
    'add' => ['ar' => 'إضافة', 'en' => 'Add'],
    'no_leader_assigned' => ['ar' => 'لم يتم تعيين قائد بعد', 'en' => 'No leader assigned yet'],
    'current_members' => ['ar' => 'الأعضاء الحاليون', 'en' => 'Current Members'],

    // Navbar
    'viewing_as' => ['ar' => 'أنت تتصفح كـ', 'en' => 'Viewing as'],
    'back_to_professor' => ['ar' => 'العودة للدكتور', 'en' => 'Back to Professor'],

    // Demo mode
    'demo_quick_login' => ['ar' => 'تسجيل دخول سريع (وضع تجريبي)', 'en' => 'Quick Login (Demo Mode)'],
    'demo_doctor' => ['ar' => 'دكتور', 'en' => 'Doctor'],
    'demo_student' => ['ar' => 'طالب', 'en' => 'Student'],
    'demo_resets_in' => ['ar' => 'إعادة تعيين العرض خلال', 'en' => 'Demo resets in'],
    'demo_credentials' => ['ar' => 'بيانات الدخول التجريبية', 'en' => 'Demo Credentials'],

    // Landing page
    'landing_hero_title' => ['ar' => 'نظام إدارة مشاريع التخرج', 'en' => 'Graduation Project Management System'],
    'landing_hero_subtitle' => ['ar' => 'منصة متكاملة لإدارة وتنظيم مشاريع التخرج الجامعية بكل سهولة ويسر', 'en' => 'A comprehensive platform for managing and organizing university graduation projects with ease'],
    'landing_feature_team' => ['ar' => 'تكوين الفرق', 'en' => 'Team Building'],
    'landing_feature_team_desc' => ['ar' => 'أنشئ فريقك وادعُ زملاءك عبر رابط أو كود انضمام أو رمز QR', 'en' => 'Create your team and invite classmates via link, join code, or QR code'],
    'landing_feature_submit' => ['ar' => 'تقديم المشاريع', 'en' => 'Project Submission'],
    'landing_feature_submit_desc' => ['ar' => 'أكمل ملفك الشخصي وقدّم مشروعك للمراجعة بخطوات بسيطة', 'en' => 'Complete your profile and submit your project for review in simple steps'],
    'landing_feature_review' => ['ar' => 'المراجعة والمتابعة', 'en' => 'Review & Track'],
    'landing_feature_review_desc' => ['ar' => 'تابع حالة مشروعك واحصل على ملاحظات الدكتور مباشرة', 'en' => 'Track your project status and get professor feedback directly'],
    'landing_feature_bilingual' => ['ar' => 'دعم ثنائي اللغة', 'en' => 'Bilingual Support'],
    'landing_feature_bilingual_desc' => ['ar' => 'واجهة كاملة باللغتين العربية والإنجليزية', 'en' => 'Full interface in both Arabic and English'],
    'landing_welcome_back' => ['ar' => 'مرحباً بعودتك', 'en' => 'Welcome Back'],
    'landing_sign_in_continue' => ['ar' => 'سجّل دخولك للمتابعة', 'en' => 'Sign in to continue'],
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
