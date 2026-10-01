<?php
if (!defined('ABSPATH')) exit;

class CI_Admin_Menu {
    private $data_manager;
    
    public function __construct() {
        $this->data_manager = CI_Data_Manager::get_instance();
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'صدور گواهی',
            'صدور گواهی',
            'manage_options',
            'certificate-issuer',
            [$this, 'render_dashboard'],
            'dashicons-id',
            30
        );
        
        add_submenu_page(
            'certificate-issuer',
            'داشبورد',
            'داشبورد',
            'manage_options',
            'certificate-issuer',
            [$this, 'render_dashboard']
        );
        
        add_submenu_page(
            'certificate-issuer',
            'درخواست‌ها',
            'درخواست‌ها',
            'manage_options',
            'certificate-issuer-requests',
            [$this, 'render_requests']
        );
        
        add_submenu_page(
            'certificate-issuer',
            'پرداخت‌ها',
            'پرداخت‌ها',
            'manage_options',
            'certificate-issuer-payments',
            [$this, 'render_payments']
        );
        
        add_submenu_page(
            'certificate-issuer',
            'تنظیمات',
            'تنظیمات',
            'manage_options',
            'certificate-issuer-settings',
            [$this, 'render_settings']
        );
		add_submenu_page(
    'certificate-issuer',
    'خروجی کاربران',
    '📤 خروجی',
    'manage_options',
    'certificate-issuer-export',
    [$this, 'render_export']
);
    }
    public function render_export() {
    include_once CI_PLUGIN_DIR . 'templates/admin-export.php';
}
    public function render_dashboard() {
        $total_requests = $this->data_manager->get_request_count();
        $pending_requests = $this->data_manager->get_request_count('pending');
        $approved_requests = $this->data_manager->get_request_count('approved');
        $total_payments = $this->data_manager->get_total_payments();
        ?>
        <div class="ci-admin-dashboard">
            <h1>📊 داشبورد صدور گواهی</h1>
            
            <div class="ci-stats-grid">
                <div class="ci-stat-card">
                    <div class="ci-stat-icon"><i class="dashicons dashicons-format-aside"></i></div>
                    <div class="ci-stat-content">
                        <h3><?php echo $total_requests; ?></h3>
                        <p>کل درخواست‌ها</p>
                    </div>
                </div>
                
                <div class="ci-stat-card ci-stat-pending">
                    <div class="ci-stat-icon"><i class="dashicons dashicons-clock"></i></div>
                    <div class="ci-stat-content">
                        <h3><?php echo $pending_requests; ?></h3>
                        <p>در انتظار تایید</p>
                    </div>
                </div>
                
                <div class="ci-stat-card ci-stat-approved">
                    <div class="ci-stat-icon"><i class="dashicons dashicons-yes-alt"></i></div>
                    <div class="ci-stat-content">
                        <h3><?php echo $approved_requests; ?></h3>
                        <p>تایید شده</p>
                    </div>
                </div>
                
                <div class="ci-stat-card ci-stat-payment">
                    <div class="ci-stat-icon"><i class="dashicons dashicons-money-alt"></i></div>
                    <div class="ci-stat-content">
                       <h3><?php echo number_format($total_payments / 10); ?> تومان</h3>
                        <p>کل پرداخت‌ها</p>
                    </div>
                </div>
            </div>
            
            <div class="ci-quick-actions">
                <h2>اقدامات سریع</h2>
                <div class="ci-actions-grid">
                    <a href="admin.php?page=certificate-issuer-requests" class="ci-action-btn">
                        <i class="dashicons dashicons-list-view"></i>
                        مشاهده درخواست‌ها
                    </a>
                    <a href="admin.php?page=certificate-issuer-payments" class="ci-action-btn">
                        <i class="dashicons dashicons-money"></i>
                        مدیریت پرداخت‌ها
                    </a>
                    <a href="admin.php?page=certificate-issuer-settings" class="ci-action-btn">
                        <i class="dashicons dashicons-admin-settings"></i>
                        تنظیمات
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function render_requests() {
        include_once CI_PLUGIN_DIR . 'templates/admin-requests.php';
    }
    
    public function render_payments() {
        include_once CI_PLUGIN_DIR . 'templates/admin-payments.php';
    }
    
    public function render_settings() {
        if (isset($_POST['ci_save_settings'])) {
            $this->save_settings();
        }
        include_once CI_PLUGIN_DIR . 'templates/admin-settings.php';
    }
    
    private function save_settings() {
        if (!check_admin_referer('ci_save_settings', 'ci_settings_nonce')) {
            wp_die('اعتبار سنجی ناموفق');
        }
        
        $settings = [
            // تنظیمات مالی
            'fee_affiliate' => floatval($_POST['fee_affiliate'] ?? 100000),
            'fee_core' => floatval($_POST['fee_core'] ?? 300000),
            'certificate_fee' => floatval($_POST['certificate_fee'] ?? 30000),
			
        // ✅ تنظیمات کد تخفیف
        'discount_code' => sanitize_text_field($_POST['discount_code'] ?? ''),
        'discount_percent' => floatval($_POST['discount_percent'] ?? 0),
        
            // تنظیمات زیبال
            'zibal_merchant' => sanitize_text_field($_POST['zibal_merchant']),
            'zibal_sandbox' => isset($_POST['zibal_sandbox']) ? true : false,
            
            // تنظیمات پیامک
            'sms_username' => sanitize_text_field($_POST['sms_username'] ?? ''),
            'sms_password' => sanitize_text_field($_POST['sms_password'] ?? ''),
            'sms_api_key' => sanitize_text_field($_POST['sms_api_key'] ?? ''),
            'sms_sender' => sanitize_text_field($_POST['sms_sender'] ?? ''),
            
            // شماره مدیر و کد الگوی مدیر
            'admin_phone' => sanitize_text_field($_POST['admin_phone'] ?? ''),
            'admin_template_id' => sanitize_text_field($_POST['admin_template_id'] ?? ''),
            
            // کدهای الگو
 'sms_templates' => [
    'register' => sanitize_text_field($_POST['sms_register'] ?? ''),
    'approve_user' => sanitize_text_field($_POST['sms_approve_user'] ?? ''),
    'issue_certificate' => sanitize_text_field($_POST['sms_issue_certificate'] ?? ''),
    'reject' => sanitize_text_field($_POST['sms_reject'] ?? ''),
    'renew' => sanitize_text_field($_POST['sms_renew'] ?? '')
],
            
            'certificate_validity_months' => intval($_POST['certificate_validity_months'] ?? 12),
            
            // ============================================
            // ✅ تنظیمات تخفیف‌ها (اصلاح شده)
            // ============================================
            'discount_core' => floatval($_POST['discount_core'] ?? 0),
            'discount_affiliate' => floatval($_POST['discount_affiliate'] ?? 0),
            'discount_certificate' => floatval($_POST['discount_certificate'] ?? 0)
        ];
        
        $this->data_manager->update_settings($settings);
        
        echo '<div class="notice notice-success"><p>تنظیمات با موفقیت ذخیره شد.</p></div>';
    }
}