<?php
if (!defined('ABSPATH')) exit;

class CI_Request_Actions {
    private $data_manager;
    
    public function __construct() {
        $this->data_manager = CI_Data_Manager::get_instance();
        
        add_action('wp_ajax_ci_get_request_details', [$this, 'ajax_get_request_details']);
        add_action('wp_ajax_ci_approve_request', [$this, 'ajax_approve_request']);
        add_action('wp_ajax_ci_reject_request', [$this, 'ajax_reject_request']);
        add_action('wp_ajax_ci_upload_certificate', [$this, 'ajax_upload_certificate']);
        add_action('wp_ajax_ci_renew_request', [$this, 'ajax_renew_request']);
        add_action('wp_ajax_ci_delete_request', [$this, 'ajax_delete_request']);
        add_action('wp_ajax_ci_delete_payment', [$this, 'ajax_delete_payment']);
	    add_action('wp_ajax_ci_remove_certificate', [$this, 'ajax_remove_certificate']);
		add_action('wp_ajax_ci_edit_request', [$this, 'ajax_edit_request']);
		add_action('wp_ajax_ci_get_request_raw', [$this, 'ajax_get_request_raw']);
    }
    
    // ============================================
    // دریافت جزئیات درخواست (نسخه کامل با فیلدهای جدید)
    // ============================================
    public function ajax_get_request_details() {
        check_ajax_referer('ci_admin_nonce', 'nonce');
        
        $request_id = intval($_POST['request_id']);
        $request = $this->data_manager->get_request($request_id);
        
        if (!$request) {
            wp_send_json_error(['message' => 'درخواست یافت نشد']);
        }
        
        ob_start();
        ?>
        <div class="ci-detail-modal">
            <h2 style="margin:0 0 20px 0;padding-bottom:15px;border-bottom:2px solid #f0f0f1;color:#1d2327;">
                📄 جزئیات درخواست #<?php echo $request['id']; ?>
            </h2>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                <!-- اطلاعات شخصی -->
                <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
                    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">نام</strong>
                    <?php echo esc_html($request['first_name'] . ' ' . $request['last_name']); ?>
                </div>
                <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
                    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">کد ملی</strong>
                    <?php echo esc_html($request['national_code']); ?>
                </div>
                <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
                    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">تلفن</strong>
                    <?php echo esc_html($request['phone']); ?>
                </div>
                <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
                    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">نام پدر</strong>
                    <?php echo esc_html($request['father_name']); ?>
                </div>
                <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
                    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">جنسیت</strong>
                    <?php echo esc_html($request['gender'] ?? '-'); ?>
                </div>
                <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
                    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">محل تولد</strong>
                    <?php echo esc_html($request['birth_place'] ?? '-'); ?>
                </div>
                <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
                    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">شماره نظام پزشکی</strong>
                    <?php echo esc_html($request['medical_id'] ?? '-'); ?>
                </div>
                <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
                    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">شماره دانشجویی</strong>
                    <?php echo esc_html($request['student_id'] ?? '-'); ?>
                </div>
                
                <!-- اطلاعات تحصیلی -->
                <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
                    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">رشته تحصیلی</strong>
                    <?php echo esc_html($request['field_of_study'] ?? '-'); ?>
                </div>
                <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
                    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">آخرین مدرک تحصیلی</strong>
                    <?php echo esc_html($request['last_degree'] ?? '-'); ?>
                </div>
                <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
                    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">آخرین دانشگاه</strong>
                    <?php echo esc_html($request['last_university'] ?? '-'); ?>
                </div>
				<div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">تاریخ تولد</strong>
    <?php 
    if (isset($request['birth_date']) && !empty($request['birth_date'])) {
        // تبدیل - به /
        $date = str_replace('-', '/', $request['birth_date']);
        echo esc_html($date);
    } else {
        echo '-';
    }
    ?>
</div>
                <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;grid-column:1/-1;">
                    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">زمینه فعالیت</strong>
                    <?php echo esc_html($request['activity_field'] ?? '-'); ?>
                </div>
                
                <!-- نوع عضویت و مبلغ -->
                <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
                    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">نوع عضویت</strong>
                    <?php 
                    $membership_labels = [
                        'affiliate' => 'وابسته',
                        'core' => 'پیوسته'
                    ];
                    echo $membership_labels[$request['membership_type'] ?? 'affiliate'] ?? '-';
                    ?>
                </div>
                <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
                    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">مبلغ</strong>
                    <?php echo number_format($request['amount']); ?> تومان
                </div>
                
                <!-- اضافه‌مبلغ -->
                <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
                    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">درخواست گواهی</strong>
                    <?php 
                    $extra_fee = isset($request['extra_fee']) ? $request['extra_fee'] : false;
                    $has_extra = false;
                    if ($extra_fee === true || $extra_fee === 'true' || $extra_fee === '1' || $extra_fee === 1 || $extra_fee === 'on' || $extra_fee === 'yes') {
                        $has_extra = true;
                    }
                    ?>
                    <?php if ($has_extra): ?>✅ بله<?php else: ?>❌ خیر<?php endif; ?>
                </div>
                
                <!-- وضعیت -->
                <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
                    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">وضعیت</strong>
                    <span class="ci-status <?php echo $request['status']; ?>">
                        <?php echo $this->get_status_label($request['status']); ?>
                    </span>
                </div>
                <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
                    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">تاریخ ثبت</strong>
                    <?php echo date_i18n('Y/m/d H:i', strtotime($request['created_at'])); ?>
                </div>
                
                <?php if ($request['status'] === 'approved' && !empty($request['issue_date'])): ?>
                    <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
                        <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">تاریخ صدور</strong>
                        <?php echo date_i18n('Y/m/d', strtotime($request['issue_date'])); ?>
                    </div>
                    <div style="background:#f8f9fa;padding:12px 16px;border-radius:8px;">
                        <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">تاریخ انقضا</strong>
                        <?php echo date_i18n('Y/m/d', strtotime($request['expiry_date'])); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- تصاویر -->
            <?php if (!empty($request['avatar'])): ?>
                <div style="margin-top:25px;padding-top:20px;border-top:2px solid #f0f0f1;">
                    <strong style="display:block;color:#646970;font-size:14px;margin-bottom:10px;">🖼️ تصویر پرسنلی</strong>
                    <img src="<?php echo CI_UPLOADS_URL . $request['avatar']; ?>" 
                         style="max-width:200px;max-height:200px;border-radius:10px;border:2px solid #e0e0e0;object-fit:cover;">
                </div>
            <?php endif; ?>
            
            <?php if (!empty($request['card_image'])): ?>
                <div style="margin-top:15px;">
                    <strong style="display:block;color:#646970;font-size:14px;margin-bottom:10px;">🪪 تصویر کارت</strong>
                    <img src="<?php echo CI_UPLOADS_URL . $request['card_image']; ?>" 
                         style="max-width:200px;max-height:200px;border-radius:10px;border:2px solid #e0e0e0;object-fit:cover;">
                </div>
            <?php endif; ?>
            
            <?php if (!empty($request['certificate_file']) && file_exists(CI_CERTIFICATES_DIR . $request['certificate_file'])): ?>
                <div style="margin-top:15px;">
                    <strong style="display:block;color:#646970;font-size:14px;margin-bottom:10px;">📄 گواهی صادر شده</strong>
                    <a href="<?php echo CI_CERTIFICATES_URL . $request['certificate_file']; ?>" 
                       target="_blank" class="button button-primary">📥 دانلود گواهی</a>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($request['admin_notes'])): ?>
                <div style="margin-top:15px;padding:12px 16px;background:#FFF3E0;border-radius:8px;border-right:4px solid #FF9800;">
                    <strong style="display:block;color:#646970;font-size:12px;text-transform:uppercase;margin-bottom:4px;">📝 یادداشت ادمین</strong>
                    <?php echo esc_html($request['admin_notes']); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html]);
    }
    
   // ============================================
// تایید درخواست (فقط پیامک تایید کاربر - بدون صدور گواهی)
// ============================================
public function ajax_approve_request() {
    check_ajax_referer('ci_admin_nonce', 'nonce');
    
    $request_id = intval($_POST['request_id']);
    $request = $this->data_manager->get_request($request_id);
    
    if (!$request) {
        wp_send_json_error(['message' => 'درخواست یافت نشد']);
    }
    
    $this->data_manager->update_request($request_id, [
    'status' => 'pending_approved'  // ← فقط این خط تغییر کرد
]);
    
    // ============================================
    // ✅ این خط را به این شکل تغییر دهید
    // ============================================
    $sms = new CI_SMS_Handler();
    $sms->send_approval_sms($request['phone'], $request_id);  // ← تغییر اصلی
    
    wp_send_json_success(['message' => '✅ درخواست تایید شد. لطفاً گواهی را بارگذاری کنید.']);
}
    
    // ============================================
    // رد درخواست
    // ============================================
    public function ajax_reject_request() {
        check_ajax_referer('ci_admin_nonce', 'nonce');
        
        $request_id = intval($_POST['request_id']);
        $reason = sanitize_text_field($_POST['reason'] ?? '');
        $request = $this->data_manager->get_request($request_id);
        
        if (!$request) {
            wp_send_json_error(['message' => 'درخواست یافت نشد']);
        }
        
        $this->data_manager->update_request($request_id, [
            'status' => 'rejected',
            'admin_notes' => $reason
        ]);
        
        $sms = new CI_SMS_Handler();
        $sms->send_rejection_sms($request['phone'], $request_id, $reason);
        
        wp_send_json_success(['message' => 'درخواست رد شد']);
    }
    
    // ============================================
    // بارگذاری گواهی
    // ============================================
    public function ajax_upload_certificate() {
        check_ajax_referer('ci_admin_nonce', 'nonce');
        
        $request_id = intval($_POST['request_id']);
        $expiry_date = sanitize_text_field($_POST['expiry_date'] ?? '');
        $request = $this->data_manager->get_request($request_id);
        
        if (!$request) {
            wp_send_json_error(['message' => 'درخواست یافت نشد']);
        }
        
        if (empty($_FILES['certificate_file']) || $_FILES['certificate_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => 'فایل گواهی الزامی است']);
        }
        
        $file = $_FILES['certificate_file'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(['message' => 'فرمت فایل مجاز نیست (فقط JPG, PNG, GIF)']);
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_send_json_error(['message' => 'حجم فایل باید کمتر از 5MB باشد']);
        }
        
        $filename = 'cert_' . $request_id . '_' . time() . '_' . sanitize_file_name($file['name']);
        $target_path = CI_CERTIFICATES_DIR . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            wp_send_json_error(['message' => 'خطا در آپلود فایل']);
        }
        
        $update_data = [
            'certificate_file' => $filename,
            'status' => 'approved'
        ];
        
        if (!empty($expiry_date)) {
            $update_data['expiry_date'] = $expiry_date . ' 23:59:59';
        }
        
        if (empty($request['issue_date'])) {
            $update_data['issue_date'] = current_time('mysql');
        }
        
        $this->data_manager->update_request($request_id, $update_data);
        
        $sms = new CI_SMS_Handler();
        $sms->send_issue_certificate_sms($request['phone'], $request_id);
        
        wp_send_json_success(['message' => 'گواهی با موفقیت بارگذاری شد']);
    }
    
    // ============================================
    // تمدید گواهی
    // ============================================
    public function ajax_renew_request() {
        check_ajax_referer('ci_admin_nonce', 'nonce');
        
        $request_id = intval($_POST['request_id']);
        $request = $this->data_manager->get_request($request_id);
        
        if (!$request) {
            wp_send_json_error(['message' => 'درخواست یافت نشد']);
        }
        
        $settings = $this->data_manager->get_settings();
        $validity_months = $settings['certificate_validity_months'] ?? 12;
        $new_expiry = date('Y-m-d H:i:s', strtotime("+$validity_months months"));
        
        $this->data_manager->update_request($request_id, [
            'expiry_date' => $new_expiry,
            'status' => 'approved',
            'renewal_sent' => false
        ]);
        
        $sms = new CI_SMS_Handler();
        $sms->send_renewal_sms($request['phone'], $request_id, $new_expiry);
        
        wp_send_json_success(['message' => 'گواهی با موفقیت تمدید شد']);
    }
    
    // ============================================
    // حذف درخواست
    // ============================================
    public function ajax_delete_request() {
        check_ajax_referer('ci_admin_nonce', 'nonce');
        
        $request_id = intval($_POST['request_id']);
        $result = $this->data_manager->delete_request($request_id);
        
        if ($result) {
            wp_send_json_success(['message' => 'درخواست حذف شد']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف درخواست']);
        }
    }
    
    // ============================================
    // حذف پرداخت
    // ============================================
    public function ajax_delete_payment() {
        check_ajax_referer('ci_admin_nonce', 'nonce');
        
        $payment_id = sanitize_text_field($_POST['payment_id']);
        $data_manager = CI_Data_Manager::get_instance();
        $months = $data_manager->get_available_months();
        $found = false;
        
        foreach ($months as $month) {
            $monthly_data = $data_manager->load_monthly_data($month);
            if (empty($monthly_data['payments'])) continue;
            
            foreach ($monthly_data['payments'] as $key => $payment) {
                if ($payment['payment_id'] === $payment_id) {
                    unset($monthly_data['payments'][$key]);
                    $monthly_data['payments'] = array_values($monthly_data['payments']);
                    $data_manager->save_monthly_data($monthly_data, $month);
                    $found = true;
                    break 2;
                }
            }
        }
        
        if ($found) {
            wp_send_json_success(['message' => 'پرداخت با موفقیت حذف شد']);
        } else {
            wp_send_json_error(['message' => 'پرداخت یافت نشد']);
        }
    }

	// ============================================
// حذف گواهی (برای صدور مجدد)
// ============================================
public function ajax_remove_certificate() {
    check_ajax_referer('ci_admin_nonce', 'nonce');
    
    $request_id = intval($_POST['request_id']);
    $request = $this->data_manager->get_request($request_id);
    
    if (!$request) {
        wp_send_json_error(['message' => 'درخواست یافت نشد']);
    }
    
    // حذف فایل گواهی
    $deleted = false;
    if (!empty($request['certificate_file'])) {
        $cert_path = CI_CERTIFICATES_DIR . $request['certificate_file'];
        if (file_exists($cert_path)) {
            $deleted = unlink($cert_path);
        }
    }
    
    // آپدیت درخواست (حذف فایل گواهی و تغییر وضعیت به pending)
    $this->data_manager->update_request($request_id, [
        'certificate_file' => '',
        'status' => 'pending'  // برگشت به وضعیت در انتظار تایید
    ]);
    
    if ($deleted) {
        wp_send_json_success(['message' => 'گواهی با موفقیت حذف شد. می‌توانید مجدداً بارگذاری کنید.']);
    } else {
        wp_send_json_success(['message' => 'گواهی حذف شد (فایل یافت نشد).']);
    }
}
    
   // ============================================
// تابع کمکی وضعیت
// ============================================
private function get_status_label($status) {
    $labels = [
        'pending_payment' => 'در انتظار پرداخت',
        'pending' => 'در انتظار تایید',
        'approved' => 'تایید شده',
        'rejected' => 'رد شده',
        'expired' => 'منقضی شده',
        'cancelled' => 'لغو شده'
    ];
    return $labels[$status] ?? $status;
}

// ============================================
// ✅ تابع ویرایش درخواست - داخل کلاس
// ============================================
public function ajax_edit_request() {
    check_ajax_referer('ci_admin_nonce', 'nonce');
    
    $request_id = intval($_POST['request_id']);
    $request = $this->data_manager->get_request($request_id);
    
    if (!$request) {
        wp_send_json_error(['message' => 'درخواست یافت نشد']);
    }
    
    // ============================================
    // دریافت و sanitize کردن داده‌ها
    // ============================================
    $update_data = [
        'first_name' => sanitize_text_field($_POST['first_name']),
        'last_name' => sanitize_text_field($_POST['last_name']),
        'national_code' => sanitize_text_field($_POST['national_code']),
        'phone' => sanitize_text_field($_POST['phone']),
        'father_name' => sanitize_text_field($_POST['father_name']),
        'birth_date' => sanitize_text_field($_POST['birth_date']),
        'gender' => sanitize_text_field($_POST['gender']),
        'birth_place' => sanitize_text_field($_POST['birth_place']),
        'medical_id' => sanitize_text_field($_POST['medical_id'] ?? ''),
        'student_id' => sanitize_text_field($_POST['student_id'] ?? ''),
        'field_of_study' => sanitize_text_field($_POST['field_of_study'] ?? ''),
        'last_degree' => sanitize_text_field($_POST['last_degree'] ?? ''),
        'last_university' => sanitize_text_field($_POST['last_university'] ?? ''),
        'activity_field' => sanitize_textarea_field($_POST['activity_field'] ?? ''),
        'membership_type' => sanitize_text_field($_POST['membership_type']),
		'status' => sanitize_text_field($_POST['status'] ?? $request['status']),
        'updated_at' => current_time('mysql')
    ];
    
    // ============================================
    // ذخیره تغییرات
    // ============================================
    $result = $this->data_manager->update_request($request_id, $update_data);
    
    if ($result) {
        wp_send_json_success(['message' => 'اطلاعات با موفقیت ویرایش شد']);
    } else {
        wp_send_json_error(['message' => 'خطا در ذخیره تغییرات']);
    }

}

// ============================================
// دریافت اطلاعات خام درخواست (برای ویرایش)
// ============================================
public function ajax_get_request_raw() {
    check_ajax_referer('ci_admin_nonce', 'nonce');
    
    $request_id = intval($_POST['request_id']);
    $request = $this->data_manager->get_request($request_id);
    
    if (!$request) {
        wp_send_json_error(['message' => 'درخواست یافت نشد']);
    }
    
    // ارسال داده‌های خام
    wp_send_json_success([
        'request' => $request
    ]);
}
} 

new CI_Request_Actions();