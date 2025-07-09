<?php
/**
 * Thai Language File (th.php)
 * DPTI Rocket System - Thai Localization
 * 
 * Structure: Key-value pairs where keys are English identifiers
 * and values are Thai translations
 * 
 * @charset UTF-8
 * @language Thai (ไทย)
 * @version 1.0
 */

return [
    // === NAVIGATION & GENERAL ===
    'dashboard' => 'แดชบอร์ด',
    'logout' => 'ออกจากระบบ',
    
    // === NAVIGATION LINKS ===
    'nav_dashboard' => 'แดชบอร์ด',
    'nav_production' => 'การผลิต',
    'nav_templates' => 'เทมเพลต',
    'nav_approvals' => 'การอนุมัติ',
    'nav_admin' => 'ผู้ดูแลระบบ',
    'nav_logout' => 'ออกจากระบบ',
    
    // === ROLE TRANSLATIONS ===
    'role_admin' => 'ผู้ดูแลระบบ',
    'role_engineer' => 'วิศวกร',
    'role_staff' => 'พนักงาน',
    'welcome' => 'ยินดีต้อนรับ',
    'home' => 'หน้าหลัก',
    'profile' => 'โปรไฟล์',
    'settings' => 'การตั้งค่า',
    'search' => 'ค้นหา',
    'filter' => 'กรอง',
    'clear' => 'ล้าง',
    'save' => 'บันทึก',
    'cancel' => 'ยกเลิก',
    'edit' => 'แก้ไข',
    'delete' => 'ลบ',
    'view' => 'ดู',
    'back' => 'กลับ',
    'next' => 'ถัดไป',
    'previous' => 'ก่อนหน้า',
    'submit' => 'ส่ง',
    'confirm' => 'ยืนยัน',
    'loading' => 'กำลังโหลด...',

    // === DASHBOARD SECTION ===
    'dashboard_title' => 'แดชบอร์ด - ระบบจรวด DPTI',
    'dashboard_main_title' => 'แดชบอร์ด ระบบจรวด DPTI',
    'dashboard_description' => 'ติดตามและจัดการไปป์ไลน์การผลิตจรวดของคุณ',
    'dashboard_welcome_message' => 'ยินดีต้อนรับสู่ระบบจรวด DPTI',
    'dashboard_overview' => 'ภาพรวมระบบ',
    'dashboard_statistics' => 'สถิติ',
    'dashboard_quick_actions' => 'การดำเนินการด่วน',
    'dashboard_recent_activity' => 'กิจกรรมล่าสุด',

    // === DASHBOARD STATISTICS ===
    'stat_total_rockets' => 'จรวดทั้งหมด',
    'stat_in_production' => 'อยู่ในการผลิต',
    'stat_completed' => 'เสร็จสิ้นแล้ว',
    'stat_pending_approvals' => 'รอการอนุมัติ',

    // === DASHBOARD SECTIONS ===
    'section_rockets_overview' => 'ภาพรวมจรวด',
    'rockets_count_display' => '{count} จรวดในระบบ',

    // === DASHBOARD BUTTONS ===
    'btn_add_new_rocket' => 'เพิ่มจรวดใหม่',
    'btn_review_approvals' => 'ตรวจสอบการอนุมัติ',
    'btn_view' => 'ดู',
    'btn_steps' => 'ขั้นตอน',
    'btn_edit' => 'แก้ไข',
    'btn_add_first_rocket' => 'เพิ่มจรวดแรก',

    // === TABLE HEADERS ===
    'table_header_serial_number' => 'หมายเลขซีเรียล',
    'table_header_project_name' => 'ชื่อโครงการ',
    'table_header_current_status' => 'สถานะปัจจุบัน',
    'table_header_created_date' => 'วันที่สร้าง',
    'table_header_actions' => 'การดำเนินการ',

    // === EMPTY STATES ===
    'empty_no_rockets_title' => 'ไม่พบจรวด',
    'empty_no_rockets_description' => 'เริ่มต้นโดยการเพิ่มจรวดแรกของคุณในระบบ',

    // === ROCKET MANAGEMENT ===
    'rockets' => 'จรวด',
    'rockets_overview' => 'ภาพรวมจรวด',
    'add_new_rocket' => 'เพิ่มจรวดใหม่',
    'rocket_details' => 'รายละเอียดจรวด',
    'serial_number' => 'หมายเลขซีเรียล',
    'project_name' => 'ชื่อโครงการ',
    'current_status' => 'สถานะปัจจุบัน',
    'created_date' => 'วันที่สร้าง',
    'actions' => 'การดำเนินการ',
    'rocket_id' => 'รหัสจรวด',
    'created_at' => 'สร้างเมื่อ',

    // Rocket Status Options
    'status_new' => 'ใหม่',
    'status_planning' => 'วางแผน',
    'status_design' => 'ออกแบบ',
    'status_in_production' => 'อยู่ในการผลิต',
    'status_testing' => 'ทดสอบ',
    'status_completed' => 'เสร็จสิ้น',
    'status_on_hold' => 'หยุดชั่วคราว',
    
    // Additional status variants (with different casing/spacing)
    'status_in production' => 'อยู่ในการผลิต',
    'status_on hold' => 'หยุดชั่วคราว',

    // === PRODUCTION STEPS ===
    'production_history' => 'ประวัติการผลิต',
    'add_production_step' => 'เพิ่มขั้นตอนการผลิต',
    'production_steps_overview' => 'ภาพรวมขั้นตอนการผลิต',
    'step_name' => 'ชื่อขั้นตอน',
    'step_details' => 'รายละเอียดขั้นตอน',
    'recorded_by' => 'บันทึกโดย',
    'recorded_date' => 'วันที่บันทึก',
    'step_data' => 'ข้อมูลขั้นตอน',
    'step_timestamp' => 'เวลาบันทึก',
    'total_steps' => 'ขั้นตอนทั้งหมด',
    'latest_step' => 'ขั้นตอนล่าสุด',
    'last_updated' => 'อัปเดตล่าสุด',

    // === APPROVALS ===
    'approvals' => 'การอนุมัติ',
    'pending_approvals_title' => 'รอการอนุมัติ',
    'approval_status' => 'สถานะการอนุมัติ',
    'approve' => 'อนุมัติ',
    'reject' => 'ปฏิเสธ',
    'approved' => 'อนุมัติแล้ว',
    'rejected' => 'ปฏิเสธแล้ว',
    'review_approve' => 'ตรวจสอบและอนุมัติ',
    'approval_comments' => 'ความเห็นการอนุมัติ',
    'approval_decision' => 'การตัดสินใจอนุมัติ',
    'submit_approval' => 'ส่งการอนุมัติ',

    // === TEMPLATES ===
    'templates' => 'เทมเพลต',
    'step_templates' => 'เทมเพลตขั้นตอน',
    'template_management' => 'จัดการเทมเพลต',
    'add_template' => 'เพิ่มเทมเพลต',
    'template_name' => 'ชื่อเทมเพลต',
    'template_description' => 'คำอธิบายเทมเพลต',
    'template_fields' => 'ฟิลด์เทมเพลต',
    'manage_templates' => 'จัดการเทมเพลต',

    // === USER ROLES ===
    'admin' => 'ผู้ดูแลระบบ',
    'engineer' => 'วิศวกร',
    'staff' => 'พนักงาน',
    'role' => 'บทบาท',
    'permissions' => 'สิทธิ์',

    // === FORMS & VALIDATION ===
    'required_field' => 'ฟิลด์ที่จำเป็น',
    'optional_field' => 'ฟิลด์เสริม',
    'field_help' => 'คำแนะนำ',
    'guidelines' => 'แนวทาง',
    'examples' => 'ตัวอย่าง',
    'validation_error' => 'ข้อผิดพลาดในการตรวจสอบ',
    'missing_fields' => 'กรุณากรอกข้อมูลให้ครบถ้วน',
    'invalid_format' => 'รูปแบบข้อมูลไม่ถูกต้อง',

    // === SUCCESS & ERROR MESSAGES ===
    'success_message' => 'ดำเนินการเรียบร้อยแล้ว',
    'error_message' => 'เกิดข้อผิดพลาด',
    'rocket_created' => 'สร้างจรวดเรียบร้อยแล้ว',
    'rocket_updated' => 'อัปเดตจรวดเรียบร้อยแล้ว',
    'rocket_deleted' => 'ลบจรวดเรียบร้อยแล้ว',
    'step_added' => 'เพิ่มขั้นตอนเรียบร้อยแล้ว',
    'step_updated' => 'อัปเดตขั้นตอนเรียบร้อยแล้ว',
    'approval_submitted' => 'ส่งการอนุมัติเรียบร้อยแล้ว',

    // === ERROR MESSAGES ===
    'error_invalid_action' => 'การดำเนินการไม่ถูกต้อง',
    'error_insufficient_permissions' => 'คุณไม่มีสิทธิ์ในการดำเนินการนี้',
    'error_rocket_not_found' => 'ไม่พบจรวด',
    'error_delete_failed' => 'ลบจรวดไม่สำเร็จ',
    'error_status_update_failed' => 'อัปเดตสถานะจรวดไม่สำเร็จ',

    // === NAVIGATION BREADCRUMBS ===
    'breadcrumb_separator' => '→',
    'breadcrumb_dashboard' => 'แดชบอร์ด',
    'breadcrumb_rockets' => 'จรวด',
    'breadcrumb_approvals' => 'การอนุมัติ',
    'breadcrumb_templates' => 'เทมเพลต',
    'breadcrumb_production_steps' => 'ขั้นตอนการผลิต',

    // === EMPTY STATES ===
    'no_rockets_found' => 'ไม่พบจรวด',
    'no_steps_recorded' => 'ยังไม่มีขั้นตอนที่บันทึก',
    'no_pending_approvals' => 'ไม่มีรายการรอการอนุมัติ',
    'no_templates_found' => 'ไม่พบเทมเพลต',
    'empty_state_message' => 'เริ่มต้นโดยการเพิ่มรายการแรกของคุณ',

    // === DATE & TIME ===
    'date_format' => 'd/m/Y',
    'datetime_format' => 'd/m/Y H:i',
    'time_format' => 'H:i',
    'today' => 'วันนี้',
    'yesterday' => 'เมื่อวาน',
    'days_ago' => 'วันที่แล้ว',
    'hours_ago' => 'ชั่วโมงที่แล้ว',
    'minutes_ago' => 'นาทีที่แล้ว',

    // === COMMON PHRASES ===
    'get_started' => 'เริ่มต้นใช้งาน',
    'learn_more' => 'เรียนรู้เพิ่มเติม',
    'contact_support' => 'ติดต่อฝ่ายสนับสนุน',
    'system_status' => 'สถานะระบบ',
    'all_systems_operational' => 'ระบบทำงานปกติ',
    'maintenance_mode' => 'โหมดบำรุงรักษา',
    'coming_soon' => 'เร็วๆ นี้',

    // === LOGIN & AUTHENTICATION ===
    'login' => 'เข้าสู่ระบบ',
    'username' => 'ชื่อผู้ใช้',
    'password' => 'รหัสผ่าน',
    'remember_me' => 'จดจำฉัน',
    'forgot_password' => 'ลืมรหัสผ่าน',
    'login_failed' => 'เข้าสู่ระบบไม่สำเร็จ',
    'invalid_credentials' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง',
    'session_expired' => 'เซสชันหมดอายุ',
    'please_login_again' => 'กรุณาเข้าสู่ระบบอีกครั้ง',

    // === ACCESSIBILITY ===
    'skip_to_content' => 'ข้ามไปยังเนื้อหา',
    'keyboard_navigation' => 'การนำทางด้วยคีย์บอร์ด',
    'screen_reader_text' => 'ข้อความสำหรับโปรแกรมอ่านหน้าจอ',

    // === FOOTER ===
    'footer_copyright' => '© 2025 ระบบจรวด DPTI. สงวนลิขสิทธิ์',
    'footer_version' => 'เวอร์ชัน',
    'footer_support' => 'ฝ่ายสนับสนุน',
    'footer_documentation' => 'เอกสารประกอบ',

    // === PRODUCTION STEP TYPES (Common Templates) ===
    'step_material_preparation' => 'การเตรียมวัสดุ',
    'step_assembly' => 'การประกอบ',
    'step_quality_control' => 'การควบคุมคุณภาพ',
    'step_testing' => 'การทดสอบ',
    'step_inspection' => 'การตรวจสอบ',
    'step_documentation' => 'การจัดทำเอกสาร',
    'step_safety_check' => 'การตรวจสอบความปลอดภัย',
    'step_compliance_verification' => 'การตรวจสอบการปฏิบัติตามกฎระเบียบ',
    'step_final_assembly' => 'การประกอบขั้นสุดท้าย',
    'step_launch_preparation' => 'การเตรียมการปล่อย',
    'step_post_production' => 'หลังการผลิต',
    'step_packaging' => 'การบรรจุหีบห่อ',
];
