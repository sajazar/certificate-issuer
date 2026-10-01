<?php
if (!defined('ABSPATH')) exit;

class CI_Data_Manager {
    private static $instance = null;
    private $settings = [];
    
    private function __construct() {
        $this->load_settings();
    }
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // ============================================
    // مدیریت تنظیمات
    // ============================================
    private function load_settings() {
        $settings_file = CI_DATA_DIR . 'settings.json';
        if (file_exists($settings_file)) {
            $content = file_get_contents($settings_file);
            $this->settings = json_decode($content, true);
        } else {
            $this->settings = $this->get_default_settings();
            $this->save_settings();
        }
    }
    
    private function get_default_settings() {
        return [
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
            'certificate_validity_months' => 12
        ];
    }
    
    public function get_settings() {
        return $this->settings;
    }
    
    public function update_settings($new_settings) {
        $this->settings = array_merge($this->settings, $new_settings);
        $this->save_settings();
        return true;
    }
    
    private function save_settings() {
        file_put_contents(
            CI_DATA_DIR . 'settings.json',
            json_encode($this->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
    
    // ============================================
    // مدیریت فایل‌های ماهانه
    // ============================================
    private function get_monthly_file($month = null) {
        if ($month === null) {
            $month = CI_CURRENT_MONTH;
        }
        return CI_DATA_DIR . $month . '.json';
    }
    
    public function load_monthly_data($month = null) {
        $file = $this->get_monthly_file($month);
        
        if (!file_exists($file)) {
            return [
                'requests' => [],
                'next_id' => 1,
                'payments' => []
            ];
        }
        
        $content = file_get_contents($file);
        return json_decode($content, true) ?: [
            'requests' => [],
            'next_id' => 1,
            'payments' => []
        ];
    }
    
    public function save_monthly_data($data, $month = null) {
        $file = $this->get_monthly_file($month);
        file_put_contents(
            $file,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
    
    // ============================================
    // مدیریت درخواست‌ها (با فیلدهای جدید)
    // ============================================
    public function create_request($user_id, $data) {
        $month = CI_CURRENT_MONTH;
        $monthly_data = $this->load_monthly_data($month);
        
        $request_id = $monthly_data['next_id']++;
        $extra_fee = isset($data['extra_fee']) ? (bool)$data['extra_fee'] : false;
        
        $request = [
            'id' => $request_id,
            'user_id' => $user_id,
            
            // اطلاعات شخصی
            'first_name' => sanitize_text_field($data['first_name']),
            'last_name' => sanitize_text_field($data['last_name']),
            'phone' => sanitize_text_field($data['phone']),
            'birth_date' => sanitize_text_field($data['birth_date']),
            'national_code' => sanitize_text_field($data['national_code']),
            'father_name' => sanitize_text_field($data['father_name']),
            'gender' => sanitize_text_field($data['gender'] ?? ''),
            'birth_place' => sanitize_text_field($data['birth_place'] ?? ''),
            'medical_id' => sanitize_text_field($data['medical_id'] ?? ''),
            'student_id' => sanitize_text_field($data['student_id'] ?? ''),
            
            // اطلاعات تحصیلی
            'field_of_study' => sanitize_text_field($data['field_of_study'] ?? ''),
            'last_degree' => sanitize_text_field($data['last_degree'] ?? ''),
            'last_university' => sanitize_text_field($data['last_university'] ?? ''),
            'activity_field' => sanitize_textarea_field($data['activity_field'] ?? ''),
            
            // نوع عضویت و فایل‌ها
            'membership_type' => sanitize_text_field($data['membership_type'] ?? 'affiliate'),
            'avatar' => $data['avatar'] ?? '',
            'card_image' => $data['card_image'] ?? '',
            'certificate_file' => '',
            
            // مبلغ و وضعیت
            'amount' => floatval($data['amount']),
            'extra_fee' => $extra_fee,
            'status' => 'pending_payment',
            'transaction_id' => '',
            'payment_date' => '',
            'issue_date' => '',
            'expiry_date' => '',
            'admin_notes' => '',
            'payment_attempts' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $monthly_data['requests'][] = $request;
        $this->save_monthly_data($monthly_data, $month);
        
        return $request_id;
    }
    
    public function get_request($request_id) {
        $months = $this->get_available_months();
        
        foreach ($months as $month) {
            $monthly_data = $this->load_monthly_data($month);
            foreach ($monthly_data['requests'] as $request) {
                if ($request['id'] == $request_id) {
                    return $request;
                }
            }
        }
        
        return null;
    }
    
    public function get_user_requests($user_id) {
        $all_requests = [];
        $months = $this->get_available_months();
        
        foreach ($months as $month) {
            $monthly_data = $this->load_monthly_data($month);
            foreach ($monthly_data['requests'] as $request) {
                if ($request['user_id'] == $user_id) {
                    $request['_month'] = $month;
                    $all_requests[] = $request;
                }
            }
        }
        
        usort($all_requests, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $all_requests;
    }
    
    public function update_request($request_id, $update_data) {
        $months = $this->get_available_months();
        
        foreach ($months as $month) {
            $monthly_data = $this->load_monthly_data($month);
            foreach ($monthly_data['requests'] as &$request) {
                if ($request['id'] == $request_id) {
                    foreach ($update_data as $key => $value) {
                        $request[$key] = $value;
                    }
                    $request['updated_at'] = current_time('mysql');
                    $this->save_monthly_data($monthly_data, $month);
                    return true;
                }
            }
        }
        
        return false;
    }
    
    public function delete_request($request_id) {
        $months = $this->get_available_months();
        
        foreach ($months as $month) {
            $monthly_data = $this->load_monthly_data($month);
            foreach ($monthly_data['requests'] as $key => $request) {
                if ($request['id'] == $request_id) {
                    if (!empty($request['avatar'])) {
                        $avatar_path = CI_UPLOADS_DIR . $request['avatar'];
                        if (file_exists($avatar_path)) {
                            unlink($avatar_path);
                        }
                    }
                    if (!empty($request['card_image'])) {
                        $card_path = CI_UPLOADS_DIR . $request['card_image'];
                        if (file_exists($card_path)) {
                            unlink($card_path);
                        }
                    }
                    if (!empty($request['certificate_file'])) {
                        $cert_path = CI_CERTIFICATES_DIR . $request['certificate_file'];
                        if (file_exists($cert_path)) {
                            unlink($cert_path);
                        }
                    }
                    
                    unset($monthly_data['requests'][$key]);
                    $monthly_data['requests'] = array_values($monthly_data['requests']);
                    $this->save_monthly_data($monthly_data, $month);
                    return true;
                }
            }
        }
        
        return false;
    }
    
    // ============================================
    // مدیریت پرداخت‌ها
    // ============================================
    public function add_payment($request_id, $payment_data) {
        $month = CI_CURRENT_MONTH;
        $monthly_data = $this->load_monthly_data($month);
        
        if (!isset($monthly_data['payments'])) {
            $monthly_data['payments'] = [];
        }
        
        $payment = [
            'payment_id' => uniqid('pay_'),
            'request_id' => $request_id,
            'track_id' => $payment_data['track_id'] ?? '',
            'amount' => $payment_data['amount'] ?? 0,
            'card_number' => $payment_data['card_number'] ?? '',
            'tracking_number' => $payment_data['tracking_number'] ?? '',
            'status' => $payment_data['status'] ?? 'pending',
            'payment_date' => current_time('mysql')
        ];
        
        $monthly_data['payments'][] = $payment;
        $this->save_monthly_data($monthly_data, $month);
        
        return $payment;
    }
    
    public function get_payments($filters = []) {
        $all_payments = [];
        $months = $this->get_available_months();
        
        foreach ($months as $month) {
            $monthly_data = $this->load_monthly_data($month);
            if (!empty($monthly_data['payments'])) {
                foreach ($monthly_data['payments'] as $payment) {
                    if (!empty($filters['request_id']) && $payment['request_id'] != $filters['request_id']) {
                        continue;
                    }
                    if (!empty($filters['status']) && $payment['status'] != $filters['status']) {
                        continue;
                    }
                    $all_payments[] = $payment;
                }
            }
        }
        
        usort($all_payments, function($a, $b) {
            return strtotime($b['payment_date']) - strtotime($a['payment_date']);
        });
        
        return $all_payments;
    }
    
    public function get_total_payments() {
        $total = 0;
        $months = $this->get_available_months();
        
        foreach ($months as $month) {
            $monthly_data = $this->load_monthly_data($month);
            if (!empty($monthly_data['payments'])) {
                foreach ($monthly_data['payments'] as $payment) {
                    if ($payment['status'] === 'success') {
                        $total += floatval($payment['amount']);
                    }
                }
            }
        }
        
        return $total;
    }
    
    // ============================================
    // جستجو و فیلتر
    // ============================================
    public function search_requests($search_term) {
        $results = [];
        $months = $this->get_available_months();
        $search_term = trim($search_term);
        
        foreach ($months as $month) {
            $monthly_data = $this->load_monthly_data($month);
            foreach ($monthly_data['requests'] as $request) {
                if (stripos($request['first_name'], $search_term) !== false ||
                    stripos($request['last_name'], $search_term) !== false ||
                    stripos($request['phone'], $search_term) !== false ||
                    stripos($request['national_code'], $search_term) !== false ||
                    stripos($request['email'] ?? '', $search_term) !== false ||
                    strpos((string)$request['id'], $search_term) !== false) {
                    $results[] = $request;
                }
            }
        }
        
        return $results;
    }
    
    public function get_requests_by_status($status) {
        $results = [];
        $months = $this->get_available_months();
        
        foreach ($months as $month) {
            $monthly_data = $this->load_monthly_data($month);
            foreach ($monthly_data['requests'] as $request) {
                if ($request['status'] === $status) {
                    $results[] = $request;
                }
            }
        }
        
        return $results;
    }
    
    public function get_expired_requests() {
        $expired = [];
        $months = $this->get_available_months();
        $now = current_time('timestamp');
        
        foreach ($months as $month) {
            $monthly_data = $this->load_monthly_data($month);
            foreach ($monthly_data['requests'] as $request) {
                if ($request['status'] === 'approved' && !empty($request['expiry_date'])) {
                    $expiry = strtotime($request['expiry_date']);
                    if ($expiry < $now) {
                        $expired[] = $request;
                    }
                }
            }
        }
        
        return $expired;
    }
    
    public function get_requests_near_expiry($days = 5) {
        $near_expiry = [];
        $months = $this->get_available_months();
        $now = current_time('timestamp');
        $threshold = $now + ($days * 24 * 60 * 60);
        
        foreach ($months as $month) {
            $monthly_data = $this->load_monthly_data($month);
            foreach ($monthly_data['requests'] as $request) {
                if ($request['status'] === 'approved' && !empty($request['expiry_date'])) {
                    $expiry = strtotime($request['expiry_date']);
                    if ($expiry > $now && $expiry <= $threshold) {
                        $near_expiry[] = $request;
                    }
                }
            }
        }
        
        return $near_expiry;
    }
    
    // ============================================
    // توابع کمکی
    // ============================================
    public function get_available_months() {
        $months = [];
        $files = glob(CI_DATA_DIR . '*.json');
        
        foreach ($files as $file) {
            $filename = basename($file);
            if ($filename === 'settings.json') continue;
            $month = str_replace('.json', '', $filename);
            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                $months[] = $month;
            }
        }
        
        if (!in_array(CI_CURRENT_MONTH, $months)) {
            $months[] = CI_CURRENT_MONTH;
        }
        
        sort($months);
        return $months;
    }
    
    public function get_request_count($status = null) {
        $count = 0;
        $months = $this->get_available_months();
        
        foreach ($months as $month) {
            $monthly_data = $this->load_monthly_data($month);
            if ($status === null) {
                $count += count($monthly_data['requests']);
            } else {
                foreach ($monthly_data['requests'] as $request) {
                    if ($request['status'] === $status) {
                        $count++;
                    }
                }
            }
        }
        
        return $count;
    }
}