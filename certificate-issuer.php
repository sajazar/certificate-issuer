<?php
/**
 * Plugin Name: Certificate Issuer for Woodmart Plus
 * Plugin URI: https://your-site.com
 * Description: افزونه صدور گواهی با درگاه زیبال و مدیریت ماهانه
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: certificate-issuer
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================
// ثابت‌های اصلی افزونه
// ============================================
define('CI_VERSION', '1.0.0');
define('CI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CI_STORAGE_DIR', WP_CONTENT_DIR . '/certificate-issuer-storage/');
define('CI_DATA_DIR', CI_STORAGE_DIR . 'data/');
define('CI_UPLOADS_DIR', CI_STORAGE_DIR . 'uploads/');
define('CI_CERTIFICATES_DIR', CI_STORAGE_DIR . 'certificates/');
define('CI_STORAGE_URL', WP_CONTENT_URL . '/certificate-issuer-storage/');
define('CI_UPLOADS_URL', CI_STORAGE_URL . 'uploads/');
define('CI_CERTIFICATES_URL', CI_STORAGE_URL . 'certificates/');
define('CI_CURRENT_MONTH', date('Y-m'));

// ============================================
// بارگذاری کلاس‌ها
// ============================================
require_once CI_PLUGIN_DIR . 'includes/class-data-manager.php';
require_once CI_PLUGIN_DIR . 'includes/class-user-endpoint.php';
require_once CI_PLUGIN_DIR . 'includes/class-zibal-gateway.php';
require_once CI_PLUGIN_DIR . 'includes/class-sms-handler.php';
require_once CI_PLUGIN_DIR . 'includes/class-admin-menu.php';
require_once CI_PLUGIN_DIR . 'includes/class-helper.php';
require_once CI_PLUGIN_DIR . 'includes/class-request-actions.php';
require_once CI_PLUGIN_DIR . 'includes/class-export.php';

// ============================================
// فعال‌سازی و غیرفعال‌سازی افزونه
// ============================================
register_activation_hook(__FILE__, 'ci_activate_plugin');
register_deactivation_hook(__FILE__, 'ci_deactivate_plugin');

function ci_activate_plugin() {
    $dirs = [
        CI_STORAGE_DIR,
        CI_DATA_DIR,
        CI_UPLOADS_DIR,
        CI_CERTIFICATES_DIR
    ];
    
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
            file_put_contents($dir . 'index.html', '');
        }
    }
    
    $settings_file = CI_DATA_DIR . 'settings.json';
    if (!file_exists($settings_file)) {
        $default_settings = [
            'fee_affiliate' => 100000,
            'fee_core' => 300000,
            'certificate_fee' => 30000,
            'zibal_merchant' => 'zibal',
            'zibal_sandbox' => false,
            'sms_username' => '',
            'sms_password' => '',
            'sms_api_key' => '',
            'sms_sender' => '',
            'admin_phone' => '',
            'admin_template_id' => '',
            'sms_templates' => [
                'register' => '',
                'approve' => '',
                'reject' => '',
                'renew' => ''
            ],
            'certificate_validity_months' => 12,
            'discount_core' => 0,
            'discount_affiliate' => 0,
            'discount_certificate' => 0
        ];
        file_put_contents($settings_file, json_encode($default_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    if (!wp_next_scheduled('ci_check_expired_certificates')) {
        wp_schedule_event(time(), 'daily', 'ci_check_expired_certificates');
    }
}

function ci_deactivate_plugin() {
    wp_clear_scheduled_hook('ci_check_expired_certificates');
}

// ============================================
// هوک‌های اصلی
// ============================================
add_filter('woocommerce_account_menu_items', 'ci_add_account_menu_item', 40);
function ci_add_account_menu_item($items) {
    $new_items = [];
    foreach ($items as $key => $value) {
        $new_items[$key] = $value;
        if ($key === 'downloads') {
            $new_items['certificate-issuer'] = 'درخواست عضویت';
        }
    }
    return $new_items;
}

add_action('init', 'ci_add_endpoint');
function ci_add_endpoint() {
    add_rewrite_endpoint('certificate-issuer', EP_ROOT | EP_PAGES);
}

add_action('woocommerce_account_certificate-issuer_endpoint', 'ci_account_content');
function ci_account_content() {
    $user_endpoint = new CI_User_Endpoint();
    $user_endpoint->render_page();
}

add_action('after_switch_theme', 'ci_flush_rewrite_rules');
function ci_flush_rewrite_rules() {
    ci_add_endpoint();
    flush_rewrite_rules();
}

add_action('wp_enqueue_scripts', 'ci_enqueue_frontend_assets');
function ci_enqueue_frontend_assets() {
    if (is_account_page()) {
        wp_enqueue_style('ci-user-style', CI_PLUGIN_URL . 'assets/css/user-style.css', [], CI_VERSION);
        wp_enqueue_script('ci-user-script', CI_PLUGIN_URL . 'assets/js/user-script.js', ['jquery'], CI_VERSION, true);
        wp_localize_script('ci-user-script', 'ci_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ci_user_nonce')
        ]);
    }
}

add_action('admin_enqueue_scripts', 'ci_enqueue_admin_assets');
function ci_enqueue_admin_assets($hook) {
    if (strpos($hook, 'certificate-issuer') !== false) {
        wp_enqueue_style('ci-admin-style', CI_PLUGIN_URL . 'assets/css/admin-style.css', [], CI_VERSION);
        wp_enqueue_script('ci-admin-script', CI_PLUGIN_URL . 'assets/js/admin-script.js', ['jquery'], CI_VERSION, true);
        wp_localize_script('ci-admin-script', 'ci_admin_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ci_admin_nonce')
        ]);
    }
}

add_action('ci_check_expired_certificates', 'ci_check_expired_certificates');
function ci_check_expired_certificates() {
    $data_manager = CI_Data_Manager::get_instance();
    $expired_requests = $data_manager->get_expired_requests();
    foreach ($expired_requests as $request) {
        $data_manager->update_request($request['id'], ['status' => 'expired']);
        if (!empty($request['certificate_file'])) {
            $cert_path = CI_CERTIFICATES_DIR . $request['certificate_file'];
            if (file_exists($cert_path)) {
                unlink($cert_path);
            }
        }
    }
}

add_action('wp_ajax_ci_retry_payment', 'ci_retry_payment');
function ci_retry_payment() {
    check_ajax_referer('ci_user_nonce', 'nonce');
    $request_id = intval($_POST['request_id']);
    $user_id = get_current_user_id();
    $data_manager = CI_Data_Manager::get_instance();
    $request = $data_manager->get_request($request_id);
    if (!$request || $request['user_id'] != $user_id) {
        wp_send_json_error(['message' => 'درخواست نامعتبر']);
    }
    $data_manager->update_request($request_id, ['status' => 'pending_payment']);
    $gateway = new CI_Zibal_Gateway();
    $result = $gateway->request_payment($request_id, $request['amount']);
    if ($result['status'] === 'success') {
        wp_send_json_success(['redirect_url' => $result['redirect_url']]);
    } else {
        wp_send_json_error(['message' => $result['message']]);
    }
}

// ============================================
// Ajax handler برای محاسبه مبلغ
// ============================================
add_action('wp_ajax_ci_calculate_total', 'ci_calculate_total');
add_action('wp_ajax_nopriv_ci_calculate_total', 'ci_calculate_total');

function ci_calculate_total() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ci_ajax_nonce')) {
        wp_send_json_error(['message' => 'اعتبار سنجی ناموفق']);
    }
    
    $extra = isset($_POST['extra']) && $_POST['extra'] == '1';
    $membership_type = isset($_POST['membership']) ? sanitize_text_field($_POST['membership']) : 'affiliate';
    
    $data_manager = CI_Data_Manager::get_instance();
    $settings = $data_manager->get_settings();
    
    if ($membership_type === 'affiliate') {
        $base_amount = intval($settings['fee_affiliate'] ?? 100000);
    } else {
        $base_amount = intval($settings['fee_core'] ?? 300000);
    }
    
    $certificate_fee = intval($settings['certificate_fee'] ?? 30000);
    
    $total = $base_amount;
    if ($extra) {
        $total += $certificate_fee;
    }
    
    wp_send_json_success([
        'total' => $total,
        'extra' => $extra,
        'base' => $base_amount,
        'cert_fee' => $certificate_fee,
        'membership_type' => $membership_type
    ]);
}
// ============================================
// Ajax handler برای اعتبارسنجی کد تخفیف
// ============================================
add_action('wp_ajax_ci_validate_discount', 'ci_validate_discount');
add_action('wp_ajax_nopriv_ci_validate_discount', 'ci_validate_discount');

function ci_validate_discount() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ci_ajax_nonce')) {
        wp_send_json_error(['message' => 'اعتبار سنجی ناموفق']);
    }
    
    $discount_code = sanitize_text_field($_POST['discount_code'] ?? '');
    $membership_type = sanitize_text_field($_POST['membership_type'] ?? 'affiliate');
    $extra_fee = isset($_POST['extra_fee']) && $_POST['extra_fee'] == '1';
    
    if (empty($discount_code)) {
        wp_send_json_error(['message' => 'کد تخفیف را وارد کنید']);
    }
    
    $data_manager = CI_Data_Manager::get_instance();
    $settings = $data_manager->get_settings();
    
    $saved_code = $settings['discount_code'] ?? '';
    $discount_percent = floatval($settings['discount_percent'] ?? 0);
    
    // محاسبه مبلغ اصلی
    if ($membership_type === 'affiliate') {
        $base_amount = intval($settings['fee_affiliate'] ?? 100000);
    } else {
        $base_amount = intval($settings['fee_core'] ?? 300000);
    }
    
    $original_amount = $base_amount;
    if ($extra_fee) {
        $original_amount += intval($settings['certificate_fee'] ?? 30000);
    }
    
    // بررسی کد تخفیف (حساس به حروف بزرگ و کوچک نیست)
    if (!empty($saved_code) && strcasecmp($discount_code, $saved_code) === 0 && $discount_percent > 0) {
        wp_send_json_success([
            'discount_percent' => $discount_percent,
            'original_amount' => $original_amount,
            'discounted_amount' => $original_amount - ($original_amount * $discount_percent / 100)
        ]);
    } else {
        wp_send_json_error([
            'message' => 'کد تخفیف نامعتبر است',
            'original_amount' => $original_amount
        ]);
    }
}
// ============================================
// بارگذاری افزونه
// ============================================
add_action('plugins_loaded', 'ci_load_plugin');
function ci_load_plugin() {
    load_plugin_textdomain('certificate-issuer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    new CI_Admin_Menu();
    new CI_Zibal_Gateway();
    new CI_SMS_Handler();
    new CI_Request_Actions();
}

// ============================================
// ✅ اضافه کردن باکس وضعیت گواهی
// ============================================
add_action('wp_footer', 'ci_add_certificate_box_with_js');
function ci_add_certificate_box_with_js() {
    if (!is_account_page()) return;
    if (is_wc_endpoint_url('orders')) return;
    if (is_wc_endpoint_url('downloads')) return;
    if (is_wc_endpoint_url('edit-account')) return;
    if (is_wc_endpoint_url('edit-address')) return;
    if (is_wc_endpoint_url('tickets')) return;
    if (is_wc_endpoint_url('notifications')) return;
    if (is_wc_endpoint_url('payment-methods')) return;
    
    $user_id = get_current_user_id();
    if (!$user_id) return;
    
    $data_manager = CI_Data_Manager::get_instance();
    $requests = $data_manager->get_user_requests($user_id);
    
    $latest_cert = null;
    $pending_request = null;
    $rejected_request = null;
    $has_approved = false;
    $user_discount = 0;
    
    foreach ($requests as $request) {
        if ($request['status'] === 'approved') {
            $has_approved = true;
            if (!$latest_cert || strtotime($request['created_at']) > strtotime($latest_cert['created_at'])) {
                $latest_cert = $request;
            }
        } elseif ($request['status'] === 'pending' || $request['status'] === 'pending_payment') {
            $pending_request = $request;
        } elseif ($request['status'] === 'rejected') {
            $rejected_request = $request;
        }
    }
    
    // دریافت تخفیف کاربر
    $user_discount = ci_get_user_discount();
    
    ob_start();
    ?>
    <div id="ci-certificate-status-box" class="white_card" style="margin-bottom:25px;display:none;">
        <div class="card_header">
            <div class="title">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5"/>
                    <path d="M2 12l10 5 10-5"/>
                </svg>
                <p>وضعیت گواهی</p>
            </div>
            <hr>
            <a href="?certificate-issuer=1" class="btn link_primary">
                <p>مشاهده همه</p>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M6.37967 3.95337L2.33301 8.00004L6.37967 12.0467" stroke="currentColor" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M13.6663 8H2.44629" stroke="currentColor" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </a>
        </div>
        
        <div class="outline_card--border_2" style="padding:15px 20px;">
            
            <?php if ($latest_cert): ?>
                <!-- ✅ گواهی تایید شده -->
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <span style="background:#E8F5E9;padding:4px 14px;border-radius:20px;font-size:13px;color:#2E7D32;font-weight:600;">✅ تایید شده</span>
                        <span style="font-size:13px;color:#555;"><strong>#<?php echo $latest_cert['id']; ?></strong></span>
                        <span style="font-size:13px;color:#555;"><?php echo esc_html($latest_cert['first_name'] . ' ' . $latest_cert['last_name']); ?></span>
                        <span style="font-size:13px;color:#555;">صدور: <?php echo date_i18n('Y/m/d', strtotime($latest_cert['issue_date'])); ?></span>
                        <span style="font-size:13px;color:#555;">انقضا: <?php echo date_i18n('Y/m/d', strtotime($latest_cert['expiry_date'])); ?></span>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <?php 
                        $days_left = CI_Helper::days_until_expiry($latest_cert['expiry_date']);
                        if ($days_left <= 30 && $days_left > 0): 
                        ?>
                            <span style="background:#FFF9C4;padding:4px 12px;border-radius:12px;font-size:12px;color:#F57F17;">⏳ <?php echo $days_left; ?> روز تا انقضا</span>
                        <?php elseif ($days_left <= 0): ?>
                            <span style="background:#FFEBEE;padding:4px 12px;border-radius:12px;font-size:12px;color:#C62828;">⏰ منقضی شده</span>
                        <?php endif; ?>
                        <?php if (!empty($latest_cert['certificate_file'])): ?>
                            <a href="<?php echo CI_CERTIFICATES_URL . $latest_cert['certificate_file']; ?>" target="_blank" style="background:#2196F3;color:#fff;padding:5px 14px;border-radius:6px;font-size:12px;text-decoration:none;">📥 دانلود</a>
                        <?php endif; ?>
                        <a href="?certificate-issuer=1&action=view&id=<?php echo $latest_cert['id']; ?>" style="color:#666;font-size:12px;text-decoration:none;">مشاهده جزئیات →</a>
                    </div>
                    <!-- ============================================ -->
                    <!-- 🎁 نمایش پیام تخفیف برای کاربران تایید شده -->
                    <!-- ============================================ -->
                    <?php if ($user_discount > 0): ?>
                        <div style="margin-top:8px;padding:10px 15px;background:#e8f5e9;border-radius:8px;border-right:4px solid #4CAF50;">
                            <span style="font-size:14px;color:#2e7d32;">
                                🎉 شما به دلیل عضویت در انجمن، <strong><?php echo $user_discount; ?>%</strong> تخفیف در سبد خرید دارید!
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($pending_request): ?>
                <!-- ⏳ در انتظار تایید -->
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <span style="background:#FFF9C4;padding:4px 14px;border-radius:20px;font-size:13px;color:#F57F17;font-weight:600;">⏳ در انتظار تایید</span>
                        <span style="font-size:13px;color:#555;"><strong>#<?php echo $pending_request['id']; ?></strong></span>
                        <span style="font-size:13px;color:#555;">ثبت: <?php echo date_i18n('Y/m/d', strtotime($pending_request['created_at'])); ?></span>
                    </div>
                    <?php if ($pending_request['status'] === 'pending_payment'): ?>
                        <a href="?certificate-issuer=1&action=view&id=<?php echo $pending_request['id']; ?>" style="background:#FF9800;color:#fff;padding:5px 14px;border-radius:6px;font-size:12px;text-decoration:none;">💳 پرداخت</a>
                    <?php else: ?>
                        <span style="font-size:12px;color:#999;">در حال بررسی توسط مدیر</span>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($rejected_request): ?>
                <!-- ❌ رد شده -->
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <span style="background:#FFEBEE;padding:4px 14px;border-radius:20px;font-size:13px;color:#C62828;font-weight:600;">❌ رد شده</span>
                        <span style="font-size:13px;color:#555;"><strong>#<?php echo $rejected_request['id']; ?></strong></span>
                        <span style="font-size:13px;color:#555;"><?php echo date_i18n('Y/m/d', strtotime($rejected_request['created_at'])); ?></span>
                    </div>
                    <a href="?certificate-issuer=1&action=new" style="background:#4CAF50;color:#fff;padding:5px 14px;border-radius:6px;font-size:12px;text-decoration:none;">➕ ثبت مجدد</a>
                </div>
                
            <?php else: ?>
                <!-- 📜 هیچ درخواستی -->
                <div style="display:flex;align-items:center;gap:15px;flex-wrap:wrap;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="font-size:24px;">📜</span>
                        <span style="font-size:14px;color:#666;">شما درخواست عضویت ندارید </span>
                    </div>
                    <a href="?certificate-issuer=1&action=new" style="background:#4CAF50;color:#fff;padding:8px 20px;border-radius:6px;font-size:13px;text-decoration:none;font-weight:500;">➕ درخواست گواهی</a>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var isDashboard = window.location.href.indexOf('/my-account/') !== -1;
        var isNotOther = window.location.href.indexOf('/orders') === -1 &&
                         window.location.href.indexOf('/downloads') === -1 &&
                         window.location.href.indexOf('/edit-account') === -1 &&
                         window.location.href.indexOf('/edit-address') === -1 &&
                         window.location.href.indexOf('/tickets') === -1 &&
                         window.location.href.indexOf('/notifications') === -1 &&
                         window.location.href.indexOf('/payment-methods') === -1;
        
        if (isDashboard && isNotOther) {
            var target = $('.items_container--2');
            if (target.length) {
                $('#ci-certificate-status-box').insertBefore(target);
                $('#ci-certificate-status-box').show();
            } else {
                var target2 = $('.items_container--3');
                if (target2.length) {
                    $('#ci-certificate-status-box').insertAfter(target2);
                    $('#ci-certificate-status-box').show();
                }
            }
        }
    });
    </script>
    <?php
    echo ob_get_clean();
}
// ============================================
// ✅ توابع تخفیف (کامل)
// ============================================

// 1. دریافت تخفیف کاربر
function ci_get_user_discount() {
    $user_id = get_current_user_id();
    if (!$user_id) return 0;
    
    $data_manager = CI_Data_Manager::get_instance();
    $settings = $data_manager->get_settings();
    $requests = $data_manager->get_user_requests($user_id);
    
    // بررسی وجود درخواست تایید شده
    $has_approved = false;
    $discount = 0;
    $has_certificate = false;
    $membership_type = 'affiliate';
    
    foreach ($requests as $request) {
        if ($request['status'] === 'approved') {
            $has_approved = true;
            if (isset($request['membership_type'])) {
                $membership_type = $request['membership_type'];
            }
            if (!empty($request['extra_fee']) && $request['extra_fee'] === true) {
                $has_certificate = true;
            }
        }
    }
    
    // اگر هیچ درخواست تایید شده‌ای ندارد، تخفیف ۰
    if (!$has_approved) {
        return 0;
    }
    
    // محاسبه بهترین تخفیف (بیشترین درصد)
    if ($has_certificate) {
        $discount = max($discount, floatval($settings['discount_certificate'] ?? 0));
    }
    
    if ($membership_type === 'core') {
        $discount = max($discount, floatval($settings['discount_core'] ?? 0));
    } else {
        $discount = max($discount, floatval($settings['discount_affiliate'] ?? 0));
    }
    
    return $discount;
}

// 2. اعمال تخفیف در سبد خرید
add_action('woocommerce_before_cart', 'ci_apply_cart_discount');
add_action('woocommerce_before_checkout_form', 'ci_apply_cart_discount');

function ci_apply_cart_discount() {
    if (is_admin() && !defined('DOING_AJAX')) return;
    
    $discount = ci_get_user_discount();
    
    // اگر تخفیف نداره، کوپن رو حذف کن
    if ($discount == 0) {
        $coupon_code = 'CI_MEMBERSHIP_DISCOUNT';
        if (WC()->cart && WC()->cart->has_discount($coupon_code)) {
            WC()->cart->remove_coupon($coupon_code);
            WC()->session->__unset('ci_discount_applied');
        }
        return;
    }
    
    // اگر تخفیف داره، کوپن رو اعمال کن
    $coupon_code = 'CI_MEMBERSHIP_DISCOUNT';
    
    $coupon = new WC_Coupon($coupon_code);
    if (!$coupon->get_id()) {
        $coupon_data = [
            'code' => $coupon_code,
            'discount_type' => 'percent',
            'amount' => $discount,
            'individual_use' => true,
        ];
        $new_coupon = new WC_Coupon();
        $new_coupon->set_props($coupon_data);
        $new_coupon->save();
    } else {
        $coupon->set_amount($discount);
        $coupon->save();
    }
    
    if (!WC()->cart->has_discount($coupon_code)) {
        WC()->cart->apply_coupon($coupon_code);
        WC()->session->set('ci_discount_applied', true);
    }
}

// 3. حذف کوپن بعد از پرداخت
add_action('woocommerce_thankyou', 'ci_remove_discount_session');
function ci_remove_discount_session() {
    WC()->session->__unset('ci_discount_applied');
}

// ============================================
// ✅ پاک‌سازی کوپن هنگام خروج کاربر از سایت
// ============================================
add_action('wp_logout', 'ci_clear_discount_session');
function ci_clear_discount_session() {
    WC()->session->__unset('ci_discount_applied');
}

// ============================================
// ✅ پاک‌سازی کوپن در صورت عدم وجود تخفیف (هر بار چک)
// ============================================
add_action('woocommerce_before_calculate_totals', 'ci_check_and_remove_discount');
function ci_check_and_remove_discount() {
    if (is_admin() && !defined('DOING_AJAX')) return;
    
    $discount = ci_get_user_discount();
    $coupon_code = 'CI_MEMBERSHIP_DISCOUNT';
    
    if ($discount == 0 && WC()->cart && WC()->cart->has_discount($coupon_code)) {
        WC()->cart->remove_coupon($coupon_code);
        WC()->session->__unset('ci_discount_applied');
    }
}

// 4. شورت کد نمایش تخفیف کاربر
add_shortcode('ci_discount_message', 'ci_discount_message_shortcode');
function ci_discount_message_shortcode() {
    $user_id = get_current_user_id();
    if (!$user_id) return '';
    
    $discount = ci_get_user_discount();
    
    if ($discount > 0) {
        return '<div class="ci-discount-badge" style="background:#e8f5e9;padding:10px 15px;border-radius:8px;border-right:4px solid #4CAF50;margin:10px 0;font-size:14px;color:#2e7d32;">
            🎉 شما به دلیل عضویت در انجمن، <strong>' . $discount . '%</strong> تخفیف دارید که در سبد خرید اعمال می شود!
        </div>';
    }
    
    return '';
}
add_filter('woocommerce_cart_totals_coupon_label', 'ci_change_coupon_label', 10, 2);
function ci_change_coupon_label($label, $coupon) {
    if ($coupon->get_code() === 'ci_membership_discount') {
        return 'کد تخفیف عضویت در انجمن';
    }
    return $label;
}
// ============================================
// ✅ تابع کمکی وضعیت (با بررسی وجود)
// ============================================
if (!function_exists('ci_get_status_label')) {
    function ci_get_status_label($status) {
        $labels = [
            'pending_payment' => 'در انتظار پرداخت',
            'pending' => 'در انتظار تایید',
            'approved' => 'تایید شده ✅',
            'rejected' => 'رد شده ❌',
            'expired' => 'منقضی شده ⏰',
            'cancelled' => 'لغو شده'
        ];
        return isset($labels[$status]) ? $labels[$status] : $status;
    }
}
// ============================================
// بارگذاری استایل استعلام
// ============================================
add_action('wp_enqueue_scripts', 'ci_enqueue_inquiry_assets');
function ci_enqueue_inquiry_assets() {
    // فقط در صفحاتی که شورت کد استعلام وجود دارد
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'ci_certificate_inquiry')) {
        wp_enqueue_style('ci-inquiry-style', CI_PLUGIN_URL . 'assets/css/ci-inquiry.css', [], CI_VERSION);
    }
}
// ============================================
// ✅ شورت کد استعلام گواهی (نسخه با استایل جدید)
// ============================================
add_shortcode('ci_certificate_inquiry', 'ci_certificate_inquiry_shortcode');
function ci_certificate_inquiry_shortcode() {
    ob_start();
    ?>
    <div class="ci-inquiry-container">
        <div class="ci-inquiry-header">
            <h2>🔍 استعلام وضعیت <span>گواهی</span></h2>
            <p>برای استعلام، کد ملی فرد را وارد کنید.</p>
        </div>
        
        <form class="ci-inquiry-form" id="ci-inquiry-form">
            <div class="ci-inquiry-input-group">
                <div class="ci-inquiry-input-wrapper">
                    <input type="text" id="ci_inquiry_national_code" 
                           placeholder="کد ملی را وارد کنید..." 
                           maxlength="10"
                           class="ci-inquiry-input">
                    <span class="ci-inquiry-input-icon">🔍</span>
                </div>
                <button type="submit" class="ci-inquiry-btn" id="ci-inquiry-btn">
                    استعلام
                </button>
            </div>
            <div class="ci-inquiry-loading" id="ci-inquiry-loading">
                <span class="spinner"></span>
                <p>در حال جستجو...</p>
            </div>
        </form>
        
        <div class="ci-inquiry-result" id="ci-inquiry-result"></div>
    </div>

    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('ci-inquiry-form');
        var input = document.getElementById('ci_inquiry_national_code');
        var btn = document.getElementById('ci-inquiry-btn');
        var resultDiv = document.getElementById('ci-inquiry-result');
        var loadingDiv = document.getElementById('ci-inquiry-loading');

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            var nationalCode = input.value.trim();
            if (!nationalCode) {
                resultDiv.innerHTML = '<div class="ci-inquiry-error"><p>⚠️ لطفاً کد ملی را وارد کنید.</p></div>';
                resultDiv.style.display = 'block';
                return;
            }

            loadingDiv.style.display = 'block';
            btn.disabled = true;
            resultDiv.style.display = 'none';

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    loadingDiv.style.display = 'none';
                    btn.disabled = false;
                    
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                resultDiv.innerHTML = response.data.html;
                                resultDiv.style.display = 'block';
                            } else {
                                resultDiv.innerHTML = '<div class="ci-inquiry-error"><p>❌ ' + response.data.message + '</p></div>';
                                resultDiv.style.display = 'block';
                            }
                        } catch(e) {
                            resultDiv.innerHTML = '<div class="ci-inquiry-error"><p>❌ خطا در پردازش درخواست</p></div>';
                            resultDiv.style.display = 'block';
                        }
                    } else {
                        resultDiv.innerHTML = '<div class="ci-inquiry-error"><p>❌ خطا در ارتباط با سرور</p></div>';
                        resultDiv.style.display = 'block';
                    }
                }
            };
            xhr.send('action=ci_inquiry_search&national_code=' + encodeURIComponent(nationalCode) + '&nonce=<?php echo wp_create_nonce('ci_inquiry_nonce'); ?>');
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
// ============================================
// ✅ Ajax handler برای استعلام گواهی
// ============================================
add_action('wp_ajax_ci_inquiry_search', 'ci_inquiry_search');
add_action('wp_ajax_nopriv_ci_inquiry_search', 'ci_inquiry_search');

function ci_inquiry_search() {
    // بررسی Nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ci_inquiry_nonce')) {
        wp_send_json_error(['message' => 'اعتبار سنجی ناموفق']);
    }
    
    $national_code = sanitize_text_field($_POST['national_code']);
    $national_code = CI_Helper::convert_to_english_number($national_code);
    
    if (empty($national_code) || !preg_match('/^\d{10}$/', $national_code)) {
        wp_send_json_error(['message' => 'کد ملی نامعتبر است']);
    }
    
    $data_manager = CI_Data_Manager::get_instance();
    $months = $data_manager->get_available_months();
    $found_requests = [];
    
    // جستجو در تمام درخواست‌ها
    foreach ($months as $month) {
        $monthly_data = $data_manager->load_monthly_data($month);
        foreach ($monthly_data['requests'] as $request) {
            if ($request['national_code'] == $national_code) {
                $found_requests[] = $request;
            }
        }
    }
    
    // مرتب‌سازی بر اساس تاریخ (جدیدترین اول)
    usort($found_requests, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    if (empty($found_requests)) {
        wp_send_json_error(['message' => 'هیچ فردی با این کد ملی یافت نشد']);
    }
    
    $request = $found_requests[0]; // آخرین درخواست
    
    // تولید HTML
    ob_start();
    ?>
    <div class="ci-inquiry-result-card">
        <div class="ci-inquiry-result-header">
            <div class="title">📋 نتیجه استعلام</div>
            <div class="date">آخرین به‌روزرسانی: <?php echo date_i18n('Y/m/d H:i', strtotime($request['updated_at'] ?? $request['created_at'])); ?></div>
        </div>
        
        <div class="ci-inquiry-user">
            <div class="ci-inquiry-avatar">
                <?php if (!empty($request['avatar']) && file_exists(CI_UPLOADS_DIR . $request['avatar'])): ?>
                    <img src="<?php echo CI_UPLOADS_URL . $request['avatar']; ?>" alt="تصویر کاربر">
                <?php else: ?>
                    <div class="no-avatar">👤</div>
                <?php endif; ?>
            </div>
            <div class="ci-inquiry-user-info">
                <h3><?php echo esc_html($request['first_name'] . ' ' . $request['last_name']); ?></h3>
                <div class="meta">
                    <span><strong>کد ملی:</strong> <?php echo esc_html($request['national_code']); ?></span>
                    <span><strong>نوع عضویت:</strong> <?php echo isset($request['membership_type']) && $request['membership_type'] === 'core' ? 'پیوسته' : 'وابسته'; ?></span>
                    <span><strong>تاریخ ثبت:</strong> <?php echo date_i18n('Y/m/d', strtotime($request['created_at'])); ?></span>
                </div>
            </div>
        </div>
        
        <div class="ci-inquiry-table">
            <table>
                <thead>
                    <tr>
                        <th>وضعیت ثبت‌نام</th>
                        <th>وضعیت گواهی</th>
                        <th>تاریخ انقضا</th>
                        <th>شماره عضویت</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <span class="ci-inquiry-status-badge <?php echo $request['status']; ?>">
                                <?php echo ci_get_status_label($request['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $has_cert = isset($request['extra_fee']) && $request['extra_fee'] === true;
                            ?>
                            <span class="<?php echo $has_cert ? 'ci-inquiry-cert-yes' : 'ci-inquiry-cert-no'; ?>">
                                <?php echo $has_cert ? '✅ دارد' : '❌ ندارد'; ?>
                            </span>
                        </td>
                        <td>
                            <?php echo !empty($request['expiry_date']) ? date_i18n('Y/m/d', strtotime($request['expiry_date'])) : '—'; ?>
                        </td>
                        <td>
                            <span class="ci-inquiry-badge-id">#<?php echo $request['id']; ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($request['issue_date'])): ?>
            <div style="padding:0 24px 20px 24px;font-size:13px;color:#666;display:flex;gap:20px;flex-wrap:wrap;border-top:1px solid #f0f0f1;padding-top:16px;">
                <span><strong>تاریخ صدور:</strong> <?php echo date_i18n('Y/m/d', strtotime($request['issue_date'])); ?></span>
                <span><strong>تاریخ انقضا:</strong> <?php echo !empty($request['expiry_date']) ? date_i18n('Y/m/d', strtotime($request['expiry_date'])) : '—'; ?></span>
                <?php if (!empty($request['certificate_file'])): ?>
                    <span><strong>گواهی:</strong> ✅ صادر شده</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    $html = ob_get_clean();
    wp_send_json_success(['html' => $html]);
}
// ============================================
// تست ارسال پیامک
// ============================================
add_action('wp_ajax_ci_test_sms', 'ci_test_sms');
add_action('wp_ajax_nopriv_ci_test_sms', 'ci_test_sms');

function ci_test_sms() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ci_test_sms_nonce')) {
        wp_send_json_error(['message' => 'اعتبار سنجی ناموفق']);
    }
    
    $phone = sanitize_text_field($_POST['phone']);
    $message = sanitize_text_field($_POST['message']);
    
    if (empty($phone) || empty($message)) {
        wp_send_json_error(['message' => 'شماره و متن پیام الزامی است']);
    }
    
    $data_manager = CI_Data_Manager::get_instance();
    $settings = $data_manager->get_settings();
    
    error_log('[CI SMS Test] Phone: ' . $phone);
    error_log('[CI SMS Test] Message: ' . $message);
    
    $sms = new CI_SMS_Handler();
    $result = $sms->send_sms($phone, $message);
    
    if ($result) {
        wp_send_json_success(['message' => '✅ پیامک تست با موفقیت ارسال شد']);
    } else {
        wp_send_json_error(['message' => '❌ ارسال پیامک ناموفق بود. تنظیمات را بررسی کنید.']);
    }
}

// ============================================
// تابع دیباگ
// ============================================
function ci_debug_log($message) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Certificate Issuer] ' . print_r($message, true));
    }
}