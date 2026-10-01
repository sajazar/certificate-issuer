<?php
if (!defined('ABSPATH')) exit;

class CI_SMS_Handler {
    private $settings;
    private $data_manager;
    private $username;
    private $password;
    private $from;
    
    public function __construct() {
        $this->data_manager = CI_Data_Manager::get_instance();
        $this->settings = $this->data_manager->get_settings();
        
        $this->username = $this->settings['sms_username'] ?? '';
        $this->password = $this->settings['sms_password'] ?? '';
        $this->from = $this->settings['sms_sender'] ?? '';
        
        add_action('ci_send_renewal_sms', [$this, 'send_renewal_sms_cron']);
    }
    
    // ============================================
    // ارسال پیامک با SOAP
    // ============================================
    public function send_sms($phone, $message, $template_id = '') {
        $phone = $this->format_phone($phone);
        
        if (empty($phone)) {
            $this->log_error('شماره تلفن نامعتبر است');
            return false;
        }
        
        if (!extension_loaded('soap')) {
            $this->log_error('SOAP روی هاست فعال نیست');
            return false;
        }
        
        if (empty($this->username) || empty($this->password)) {
            $this->log_error('نام کاربری یا رمز عبور وارد نشده است');
            return false;
        }
        
        try {
            $client = new SoapClient("http://api.payamak-panel.com/post/send.asmx?wsdl", [
                'trace' => 1,
                'exceptions' => 1,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'connection_timeout' => 15,
                'stream_context' => stream_context_create([
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
                ])
            ]);
            
            // ============================================
            // ارسال با الگو
            // ============================================
            if (!empty($template_id) && is_numeric($template_id)) {
                // تبدیل پیام به آرایه متغیرها با جداکننده |
                $variables = explode('|', $message);
                
                // اگر فقط یک متغیر داریم، آن را به صورت آرایه ارسال کن
                if (count($variables) === 1) {
                    $text_array = [$variables[0]];
                } else {
                    $text_array = $variables;
                }
                
                $this->log_debug('Template ID: ' . $template_id);
                $this->log_debug('Variables: ' . print_r($text_array, true));
                
                $data = [
                    'username' => $this->username,
                    'password' => $this->password,
                    'text' => $text_array,
                    'to' => $phone,
                    'bodyId' => intval($template_id)
                ];
                
                $result = $client->SendByBaseNumber($data)->SendByBaseNumberResult;
                
                if ($result > 0) {
                    $this->log_success('✅ پیامک با الگو ارسال شد: ' . $phone);
                    return true;
                } else {
                    $this->log_error('❌ خطا در ارسال با الگو: ' . $result);
                    return false;
                }
            }
            
            // ============================================
            // ارسال عادی (بدون الگو)
            // ============================================
            $parameters = [
                'username' => $this->username,
                'password' => $this->password,
                'from' => $this->from,
                'to' => [$phone],
                'text' => $message,
                'isflash' => false,
                'udh' => '',
                'recId' => [0],
                'status' => 0x0
            ];
            
            $result = $client->SendSms($parameters)->SendSmsResult;
            
            if ($result > 0) {
                $this->log_success('✅ پیامک ارسال شد: ' . $phone);
                return true;
            } else {
                $this->log_error('❌ خطا در ارسال: ' . $this->get_soap_error($result));
                return false;
            }
            
        } catch (SoapFault $e) {
            $this->log_error('SOAP Fault: ' . $e->faultstring);
            return false;
        } catch (Exception $e) {
            $this->log_error('Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    // ============================================
    // فرمت شماره تلفن
    // ============================================
    private function format_phone($phone) {
        $phone = trim($phone);
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (substr($phone, 0, 1) === '0') {
            $phone = substr($phone, 1);
        }
        
        if (substr($phone, 0, 2) !== '98') {
            $phone = '98' . $phone;
        }
        
        return $phone;
    }
    
    // ============================================
    // دریافت نام کاربر بر اساس شماره تلفن
    // ============================================
    private function get_user_name_by_phone($phone) {
        $data_manager = CI_Data_Manager::get_instance();
        $all_requests = [];
        $months = $data_manager->get_available_months();
        
        foreach ($months as $month) {
            $monthly_data = $data_manager->load_monthly_data($month);
            foreach ($monthly_data['requests'] as $request) {
                if ($request['phone'] == $phone) {
                    $all_requests[] = $request;
                }
            }
        }
        
        usort($all_requests, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        if (!empty($all_requests)) {
            $request = $all_requests[0];
            return $request['first_name'] . ' ' . $request['last_name'];
        }
        
        return 'کاربر';
    }
    
    // ============================================
    // 1. پیامک ثبت‌نام کاربر
    // ============================================
    public function send_registration_sms($phone, $request_id) {
        $user_name = $this->get_user_name_by_phone($phone);
        
        // ✅ متغیرها: {0}=نام, {1}=شماره درخواست
        $message = $user_name . '|' . $request_id;
        $template_id = $this->settings['sms_templates']['register'] ?? '';
        
        return $this->send_sms($phone, $message, $template_id);
    }
    
// ============================================
// پیامک تایید کاربر (جداگانه) - جدید
// ============================================
public function send_approval_sms($phone, $request_id) {
    $user_name = $this->get_user_name_by_phone($phone);
    $message = $user_name . '|' . $request_id;
    $template_id = $this->settings['sms_templates']['approve_user'] ?? '';
    return $this->send_sms($phone, $message, $template_id);
}
// ============================================
// 7. پیامک صدور گواهی (جداگانه)
// ============================================
public function send_issue_certificate_sms($phone, $request_id) {
    $user_name = $this->get_user_name_by_phone($phone);
    $expiry_date = $this->get_expiry_date_by_request($request_id);
    $message = $user_name . '|' . $request_id . '|' . $expiry_date;
    $template_id = $this->settings['sms_templates']['issue_certificate'] ?? '';  // ✅ تغییر
    return $this->send_sms($phone, $message, $template_id);
}
    // ============================================
    // 3. پیامک رد گواهی
    // ============================================
    public function send_rejection_sms($phone, $request_id, $reason = '') {
        $user_name = $this->get_user_name_by_phone($phone);
        $reason = !empty($reason) ? $reason : 'نامشخص';
        
        // ✅ متغیرها: {0}=نام, {1}=شماره درخواست, {2}=دلیل
        $message = $user_name . '|' . $request_id . '|' . $reason;
        $template_id = $this->settings['sms_templates']['reject'] ?? '';
        
        return $this->send_sms($phone, $message, $template_id);
    }
    
    // ============================================
    // 4. پیامک تمدید گواهی
    // ============================================
    public function send_renewal_sms($phone, $request_id, $expiry_date) {
        $user_name = $this->get_user_name_by_phone($phone);
        $days_left = CI_Helper::days_until_expiry($expiry_date);
        $expiry_date_persian = date_i18n('Y/m/d', strtotime($expiry_date));
        
        // ✅ متغیرها: {0}=نام, {1}=شماره, {2}=تاریخ, {3}=روز
        $message = $user_name . '|' . $request_id . '|' . $expiry_date_persian . '|' . $days_left;
        $template_id = $this->settings['sms_templates']['renew'] ?? '';
        
        return $this->send_sms($phone, $message, $template_id);
    }
    
    // ============================================
    // 5. پیامک تایید پرداخت
    // ============================================
    public function send_payment_confirmation($phone, $request_id) {
        $user_name = $this->get_user_name_by_phone($phone);
        
        // ✅ متغیرها: {0}=نام, {1}=شماره درخواست
        $message = $user_name . '|' . $request_id;
        $template_id = $this->settings['sms_templates']['approve'] ?? '';
        
        return $this->send_sms($phone, $message, $template_id);
    }
    
    // ============================================
    // 6. پیامک به مدیر
    // ============================================
   public function send_admin_notification($phone, $user_name, $request_id) {
    $admin_phone = $this->settings['admin_phone'] ?? '';
    $template_id = $this->settings['admin_template_id'] ?? '';
    
    // ============================================
    // ✅ بررسی لاگ برای دیباگ
    // ============================================
    $this->log_debug('Admin Notification - Phone: ' . $admin_phone);
    $this->log_debug('Admin Notification - Template ID: ' . $template_id);
    $this->log_debug('Admin Notification - User: ' . $user_name);
    $this->log_debug('Admin Notification - Request ID: ' . $request_id);
    
    if (empty($admin_phone)) {
        $this->log_error('شماره مدیر تنظیم نشده است');
        return false;
    }
    
    // ✅ متغیرها: {0}=نام کاربر, {1}=شماره تلفن کاربر
    $message = $user_name . '|' . $phone;
    
    // اگر کد الگو تنظیم نشده، پیام ساده ارسال کن
    if (empty($template_id) || !is_numeric($template_id)) {
        $this->log_error('کد الگوی مدیر تنظیم نشده است');
        $text = "مدیر گرامی درخواست جدید از کاربر {$user_name} با شماره {$phone} برای صدور گواهی ثبت شد. انجمن علمی غذا و تغذیه حامی سلامت ایران";
        return $this->send_sms($admin_phone, $text);
    }
    
    return $this->send_sms($admin_phone, $message, $template_id);
}
    
    // ============================================
    // ارسال پیامک ثبت‌نام (کاربر + مدیر)
    // ============================================
    public function send_registration_sms_full($phone, $request_id) {
        $user_name = $this->get_user_name_by_phone($phone);
        
        // 1. ارسال به کاربر
        $user_result = $this->send_registration_sms($phone, $request_id);
        
        // 2. ارسال به مدیر
        $admin_result = $this->send_admin_notification($phone, $user_name, $request_id);
        
        return $user_result && $admin_result;
    }
    
    // ============================================
    // کرون جاب برای ارسال پیامک تمدید
    // ============================================
    public function send_renewal_sms_cron() {
        $data_manager = CI_Data_Manager::get_instance();
        $near_expiry = $data_manager->get_requests_near_expiry(5);
        
        foreach ($near_expiry as $request) {
            if (!isset($request['renewal_sent']) || !$request['renewal_sent']) {
                $this->send_renewal_sms(
                    $request['phone'],
                    $request['id'],
                    $request['expiry_date']
                );
                $data_manager->update_request($request['id'], ['renewal_sent' => true]);
            }
        }
    }

// ============================================
// دریافت تاریخ انقضا بر اساس شماره درخواست
// ============================================
private function get_expiry_date_by_request($request_id) {
    $data_manager = CI_Data_Manager::get_instance();
    $request = $data_manager->get_request($request_id);
    if ($request && !empty($request['expiry_date'])) {
        return date_i18n('Y/m/d', strtotime($request['expiry_date']));
    }
    return 'نامشخص';
} 
    // ============================================
    // خطاهای SOAP
    // ============================================
    private function get_soap_error($code) {
        $errors = [
            -110 => 'حساب کاربری غیرفعال است',
            -5 => 'متغیرها با متن پیشفرض همخوانی ندارد',
            -4 => 'کد متن ارسالی صحیح نمیباشد',
            0 => 'نام کاربری یا کلمه عبور اشتباه است',
            2 => 'اعتبار کافی نیست',
            3 => 'محدودیت در ارسال روزانه',
            4 => 'محدودیت در حجم ارسال',
            5 => 'شماره فرستنده معتبر نیست',
            6 => 'سامانه در حال بروز رسانی است',
            7 => 'متن حاوی کلمات فیلتر شده میباشد',
            9 => 'ارسال از خطوط عمومی امکان پذیر نیست',
            10 => 'کاربر مورد نظر فعال نیست',
            11 => 'پیامک ارسال نشده است',
            12 => 'مدارک کاربر کامل نشده است'
        ];
        
        return $errors[$code] ?? 'خطای ناشناخته (کد: ' . $code . ')';
    }
    
    // ============================================
    // لاگ‌ها
    // ============================================
    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[CI SMS] ❌ ' . $message);
        }
    }
    
    private function log_success($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[CI SMS] ✅ ' . $message);
        }
    }
    
    private function log_debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[CI SMS] 🔍 ' . $message);
        }
    }
}