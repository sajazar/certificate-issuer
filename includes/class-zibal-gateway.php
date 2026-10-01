<?php
if (!defined('ABSPATH')) exit;

class CI_Zibal_Gateway {
    private $settings;
    private $data_manager;
    private $api_urls = [
        'request' => 'https://gateway.zibal.ir/v1/request',
        'verify' => 'https://gateway.zibal.ir/v1/verify',
        'request_fallback' => 'https://gateway.zibal.io/v1/request',
        'verify_fallback' => 'https://gateway.zibal.io/v1/verify'
    ];
    
    public function __construct() {
        $this->data_manager = CI_Data_Manager::get_instance();
        $this->settings = $this->data_manager->get_settings();
        add_action('init', [$this, 'handle_callback']);
    }
    
    public function request_payment($request_id, $amount) {
    // ============================================
    // ✅ دریافت مرچنت از تنظیمات
    // ============================================
    $merchant = isset($this->settings['zibal_merchant']) && !empty($this->settings['zibal_merchant']) 
        ? $this->settings['zibal_merchant'] 
        : 'zibal';
    
    $sandbox = isset($this->settings['zibal_sandbox']) && $this->settings['zibal_sandbox'] === true;
    
    if ($sandbox) {
        $merchant = 'zibal';
    }
    
    // ============================================
    // ✅ تبدیل مبلغ به ریال (ضرب در 10)
    // ============================================
    $amount_in_rial = intval($amount) * 10;
    
    // ============================================
    // ✅ ساخت Callback URL
    // ============================================
    $callback_url = add_query_arg([
        'zibal_callback' => 'true',
        'request_id' => $request_id
    ], home_url());
    
    $data = [
        'merchant' => $merchant,
        'amount' => $amount_in_rial,  // ✅ ریال
        'callbackUrl' => $callback_url,
        'orderId' => $request_id,
        'description' => 'صدور گواهی - شماره درخواست: ' . $request_id
    ];
    
    // ============================================
    // ✅ ارسال درخواست با timeout بیشتر
    // ============================================
    $ch = curl_init('https://gateway.zibal.ir/v1/request');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (WordPress)'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    // ============================================
    // ✅ لاگ‌گیری خطاهای درگاه زیبال
    // ============================================
    if (curl_errno($ch) || $http_code >= 500) {
        error_log('[Zibal Gateway Error] HTTP Code: ' . $http_code . ' | cURL Error: ' . $error . ' | Response: ' . $response);
        error_log('[Zibal Gateway Error] Request Data: ' . json_encode($data));
        error_log('[Zibal Gateway Error] Request ID: ' . $request_id . ' | Amount: ' . $amount);
    }
    
    curl_close($ch);
    
    // ============================================
    // ✅ لاگ برای دیباگ
    // ============================================
    error_log('[Zibal] Request Data: ' . json_encode($data));
    error_log('[Zibal] Response HTTP: ' . $http_code);
    error_log('[Zibal] Response Body: ' . $response);
    if ($error) {
        error_log('[Zibal] CURL Error: ' . $error);
    }
    
    if ($http_code != 200 || !$response) {
        return [
            'status' => 'error',
            'message' => 'خطا در اتصال به درگاه پرداخت (کد: ' . $http_code . ')'
        ];
    }
    
    $result = json_decode($response, true);
    
    if ($result && isset($result['result']) && $result['result'] == 100) {
        return [
            'status' => 'success',
            'track_id' => $result['trackId'],
            'redirect_url' => 'https://gateway.zibal.ir/start/' . $result['trackId']
        ];
    } else {
        $error_msg = isset($result['result']) ? $result['result'] : 'اتصال ناموفق';
        return [
            'status' => 'error',
            'message' => 'خطا: ' . $error_msg . ' - ' . ($result['message'] ?? '')
        ];
    }
}
    
    // ============================================
    // دریافت لینک پرداخت
    // ============================================
    private function get_payment_url($track_id) {
        $urls = [
            'https://gateway.zibal.ir/start/' . $track_id,
            'https://gateway.zibal.io/start/' . $track_id
        ];
        
        foreach ($urls as $url) {
            $headers = @get_headers($url);
            if ($headers && strpos($headers[0], '200') !== false) {
                return $url;
            }
        }
        return $urls[0];
    }
    
    // ============================================
    // تأیید پرداخت
    // ============================================
    public function verify_payment($track_id, $request_id) {
        $merchant = isset($this->settings['zibal_merchant']) && !empty($this->settings['zibal_merchant']) 
            ? $this->settings['zibal_merchant'] 
            : 'zibal';
        
        $sandbox = isset($this->settings['zibal_sandbox']) && $this->settings['zibal_sandbox'] === true;
        
        if ($sandbox) {
            $merchant = 'zibal';
        }
        
        $data = [
            'merchant' => $merchant,
            'trackId' => $track_id
        ];
        
        $response = $this->curl_request($this->api_urls['verify'], $data);
        
        if ($response === false && isset($this->api_urls['verify_fallback'])) {
            $response = $this->curl_request($this->api_urls['verify_fallback'], $data);
        }
        
        if ($response === false) {
            return [
                'status' => 'error',
                'message' => 'خطا در تأیید پرداخت'
            ];
        }
        
        $result = json_decode($response, true);
        
        if ($result && isset($result['result']) && $result['result'] == 100) {
            return [
                'status' => 'success',
                'amount' => $result['amount'] ?? 0,
                'card_number' => $result['cardNumber'] ?? '',
                'tracking_number' => $result['trackingNumber'] ?? ''
            ];
        } else {
            $error_msg = isset($result['result']) ? $result['result'] : 'نامشخص';
            return [
                'status' => 'error',
                'message' => 'خطا در تأیید: ' . $error_msg
            ];
        }
    }
    
    // ============================================
    // درخواست CURL
    // ============================================
    private function curl_request($url, $data) {
        $payload = json_encode($data);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload)
            ],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_errno($ch);
        curl_close($ch);
        
        // لاگ برای دیباگ
        error_log("Zibal CURL - URL: $url, HTTP: $http_code, Error: $error");
        error_log("Zibal Response: " . substr($response, 0, 200));
        
        if ($error || !$response || $http_code != 200) {
            return false;
        }
        
        $json_check = json_decode($response);
        if ($json_check === null && json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        return $response;
    }
    public function handle_callback() {
        if (!isset($_GET['zibal_callback']) || !isset($_GET['trackId']) || !isset($_GET['success'])) {
            return;
        }
        
        $request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;
        $track_id = sanitize_text_field($_GET['trackId']);
        $success = sanitize_text_field($_GET['success']);
        
        if (!$request_id) {
            wp_redirect(wc_get_account_endpoint_url('certificate-issuer'));
            exit;
        }
        
        $request = $this->data_manager->get_request($request_id);
        
        if (!$request) {
            wp_redirect(wc_get_account_endpoint_url('certificate-issuer'));
            exit;
        }
        
        // کال‌بک زیبال ممکن است بیش از یک بار اجرا شود. اگر درخواست قبلاً
        // پرداخت موفق داشته و به مرحله تأیید یا تأیید نهایی رسیده، هیچ کال‌بک
        // تکراری نباید وضعیت آن را به عقب برگرداند.
        if (in_array($request['status'], ['pending', 'approved'], true)) {
            error_log('[Zibal] Duplicate callback ignored for request ' . $request_id . ' with status ' . $request['status']);
            wp_redirect(add_query_arg('status', 'payment_success', wc_get_account_endpoint_url('certificate-issuer')));
            exit;
        }
        
        if ($success == '1') {
            $verify_result = $this->verify_payment($track_id, $request_id);
            
            if ($verify_result['status'] == 'success') {
                // فقط همین مرحله پرداخت موفق را ثبت می‌کنیم.
                $this->data_manager->add_payment($request_id, [
                    'track_id' => $track_id,
                    'amount' => $verify_result['amount'],
                    'card_number' => $verify_result['card_number'] ?? '',
                    'tracking_number' => $verify_result['tracking_number'] ?? '',
                    'status' => 'success'
                ]);
                
                // فقط درخواست در انتظار پرداخت می‌تواند به pending برود.
                // وضعیت‌های بالاتر هرگز با کال‌بک پرداخت پایین نمی‌آیند.
                $current_request = $this->data_manager->get_request($request_id);
                if ($current_request && $current_request['status'] === 'pending_payment') {
                    $this->data_manager->update_request($request_id, [
                        'status' => 'pending',
                        'transaction_id' => $track_id,
                        'payment_date' => current_time('mysql'),
                        'card_number' => $verify_result['card_number'] ?? '',
                        'tracking_number' => $verify_result['tracking_number'] ?? ''
                    ]);
                }
                
                // ارسال پیامک ثبت‌نام (فعلاً بدون تغییر در متن پیامک)
                if (class_exists('CI_SMS_Handler')) {
                    $sms = new CI_SMS_Handler();
                    $sms->send_registration_sms_full($request['phone'], $request_id);
                }
                
                wp_redirect(add_query_arg('status', 'payment_success', wc_get_account_endpoint_url('certificate-issuer')));
            } else {
                // اگر پرداخت قبلاً موفق ثبت شده، شکست یک verify تکراری
                // نباید آن را به pending_payment برگرداند.
                $current_request = $this->data_manager->get_request($request_id);
                $successful_payments = $this->data_manager->get_payments([
                    'request_id' => $request_id,
                    'status' => 'success'
                ]);
                
                if ($current_request && !empty($successful_payments)) {
                    error_log('[Zibal] Failed duplicate verification ignored for already-paid request ' . $request_id);
                    wp_redirect(add_query_arg('status', 'payment_success', wc_get_account_endpoint_url('certificate-issuer')));
                    exit;
                }
                
                if ($current_request && $current_request['status'] === 'pending_payment') {
                    $this->data_manager->update_request($request_id, [
                        'status' => 'pending_payment',
                        'transaction_id' => $track_id
                    ]);
                }
                
                wp_redirect(add_query_arg('status', 'payment_failed', wc_get_account_endpoint_url('certificate-issuer')));
            }
        } else {
            // لغو شدن یک کال‌بک تکراری نباید پرداخت موفق قبلی را خراب کند.
            $current_request = $this->data_manager->get_request($request_id);
            $successful_payments = $this->data_manager->get_payments([
                'request_id' => $request_id,
                'status' => 'success'
            ]);
            
            if ($current_request && !empty($successful_payments)) {
                error_log('[Zibal] Cancel callback ignored for already-paid request ' . $request_id);
                wp_redirect(add_query_arg('status', 'payment_success', wc_get_account_endpoint_url('certificate-issuer')));
                exit;
            }
            
            $this->data_manager->update_request($request_id, ['status' => 'cancelled']);
            wp_redirect(add_query_arg('status', 'payment_cancelled', wc_get_account_endpoint_url('certificate-issuer')));
        }
        exit;
    }
