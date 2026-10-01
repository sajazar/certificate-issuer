<?php
if (!defined('ABSPATH')) exit;

class CI_Helper {
    
    // ============================================
    // ✅ تبدیل اعداد فارسی به انگلیسی
    // ============================================
    public static function convert_to_english_number($string) {
        // اعداد فارسی و عربی
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        // تبدیل اعداد فارسی و عربی به انگلیسی
        $string = str_replace($persian, $english, $string);
        $string = str_replace($arabic, $english, $string);
        
        return $string;
    }
    // ============================================
// دریافت وضعیت گواهی برای نمایش
// ============================================
public static function get_certificate_status_label($request) {
    // اگر درخواست وجود نداشته باشد
    if (empty($request)) {
        return [
            'label' => 'بدون درخواست گواهی',
            'icon' => '📭',
            'color' => '#94a3b8',
            'bg' => '#f1f5f9'
        ];
    }
    
    // بررسی وضعیت درخواست
    $status = $request['status'] ?? '';
    
    // بررسی اینکه آیا گواهی درخواست شده است یا خیر
    $has_certificate = isset($request['extra_fee']) && 
                       ($request['extra_fee'] === true || 
                        $request['extra_fee'] === 'true' || 
                        $request['extra_fee'] === '1' || 
                        $request['extra_fee'] === 1);
    
    // وضعیت‌های مختلف
    $statuses = [
        'pending_payment' => [
            'label' => 'در انتظار پرداخت گواهی',
            'icon' => '💳',
            'color' => '#d97706',
            'bg' => '#fef3c7'
        ],
        'pending' => [
            'label' => 'در انتظار تایید گواهی',
            'icon' => '⏳',
            'color' => '#d97706',
            'bg' => '#fef3c7'
        ],
        'approved' => [
            'label' => 'گواهی تایید شده ✅',
            'icon' => '✅',
            'color' => '#059669',
            'bg' => '#d1fae5'
        ],
        'rejected' => [
            'label' => 'درخواست گواهی رد شده ❌',
            'icon' => '❌',
            'color' => '#dc2626',
            'bg' => '#fee2e2'
        ],
        'expired' => [
            'label' => 'گواهی منقضی شده ⏰',
            'icon' => '⏰',
            'color' => '#6b7280',
            'bg' => '#f3f4f6'
        ],
        'cancelled' => [
            'label' => 'درخواست گواهی لغو شده',
            'icon' => '🚫',
            'color' => '#6b7280',
            'bg' => '#f3f4f6'
        ]
    ];
    
    // اگر وضعیت در لیست باشد
    if (isset($statuses[$status])) {
        return $statuses[$status];
    }
    
    // اگر گواهی درخواست نشده باشد
    if (!$has_certificate && $status === 'approved') {
        return [
            'label' => 'بدون درخواست گواهی (فقط ثبت‌نام)',
            'icon' => '📋',
            'color' => '#64748b',
            'bg' => '#f1f5f9'
        ];
    }
    
    return [
        'label' => 'وضعیت نامشخص',
        'icon' => '❓',
        'color' => '#6b7280',
        'bg' => '#f3f4f6'
    ];
}
    // ============================================
    // تبدیل اعداد انگلیسی به فارسی (برای نمایش)
    // ============================================
    public static function convert_to_persian_number($string) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($english, $persian, $string);
    }
    
    // ============================================
    // اعتبارسنجی کد ملی
    // ============================================
    public static function validate_national_code($code) {
        $code = trim($code);
        $code = self::convert_to_english_number($code);
        
        if (!preg_match('/^\d{10}$/', $code)) {
            return false;
        }
        
        $check = intval($code[9]);
        $sum = 0;
        
        for ($i = 0; $i < 9; $i++) {
            $sum += intval($code[$i]) * (10 - $i);
        }
        
        $remainder = $sum % 11;
        
        if ($remainder < 2) {
            return $check == $remainder;
        } else {
            return $check == (11 - $remainder);
        }
    }
    
    // ============================================
    // اعتبارسنجی شماره تلفن
    // ============================================
    public static function validate_phone($phone) {
        $phone = trim($phone);
        $phone = self::convert_to_english_number($phone);
        return preg_match('/^09[0-9]{9}$/', $phone);
    }
    
    // ============================================
    // اعتبارسنجی تاریخ تولد (شمسی)
    // ============================================
    public static function validate_birth_date($day, $month, $year) {
        $day = intval(self::convert_to_english_number($day));
        $month = intval(self::convert_to_english_number($month));
        $year = intval(self::convert_to_english_number($year));
        
        // بررسی محدوده
        if ($day < 1 || $day > 31) return false;
        if ($month < 1 || $month > 12) return false;
        if ($year < 1300 || $year > 1500) return false;
        
        // بررسی روزهای ماه
        $days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        if ($day > $days_in_month[$month - 1]) {
            // بررسی سال کبیسه برای ماه دوم
            if ($month == 2) {
                // سال کبیسه شمسی: سال % 4 == 0 و سال % 100 != 0 یا سال % 400 == 0
                // معادل شمسی: (سال % 4 == 0) 
                if ($year % 4 == 0 && $day <= 29) {
                    return true;
                }
                return false;
            }
            return false;
        }
        
        return true;
    }
    
    // ============================================
    // تولید تاریخ انقضا
    // ============================================
    public static function calculate_expiry_date($months = 12) {
        return date('Y-m-d H:i:s', strtotime("+$months months"));
    }
    
    // ============================================
    // بررسی انقضای گواهی
    // ============================================
    public static function is_expired($expiry_date) {
        $now = current_time('timestamp');
        $expiry = strtotime($expiry_date);
        return $expiry < $now;
    }
    
    // ============================================
    // محاسبه روزهای باقی‌مانده تا انقضا
    // ============================================
    public static function days_until_expiry($expiry_date) {
        $now = current_time('timestamp');
        $expiry = strtotime($expiry_date);
        $diff = $expiry - $now;
        return ceil($diff / (60 * 60 * 24));
    }
    
    // ============================================
    // فرمت کردن مبلغ
    // ============================================
    public static function format_amount($amount) {
        return number_format($amount) . ' تومان';
    }
    
    // ============================================
    // تولید نام فایل یکتا
    // ============================================
    public static function generate_unique_filename($prefix, $extension) {
        return $prefix . '_' . time() . '_' . uniqid() . '.' . $extension;
    }
    
    // ============================================
    // دریافت آدرس IP کاربر
    // ============================================
    public static function get_user_ip() {
        $ip = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }
    
    // ============================================
    // پاکسازی محتوای HTML
    // ============================================
    public static function clean_html($content) {
        return wp_kses_post($content);
    }
    
    // ============================================
    // دریافت اطلاعات کاربر از وردپرس
    // ============================================
    public static function get_user_data($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return null;
        }
        
        return [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name
        ];
    }
    
    // ============================================
    // تولید لینک دانلود گواهی (با محدودیت دسترسی)
    // ============================================
    public static function generate_download_link($request_id) {
        $nonce = wp_create_nonce('ci_download_certificate_' . $request_id);
        return add_query_arg([
            'ci_download' => $request_id,
            'nonce' => $nonce
        ], home_url());
    }
    
    // ============================================
    // بررسی دسترسی به گواهی
    // ============================================
    public static function can_download_certificate($request_id) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return false;
        }
        
        $data_manager = CI_Data_Manager::get_instance();
        $request = $data_manager->get_request($request_id);
        
        if (!$request || $request['user_id'] != $user_id) {
            return false;
        }
        
        if ($request['status'] !== 'approved') {
            return false;
        }
        
        if (empty($request['certificate_file'])) {
            return false;
        }
        
        // بررسی انقضا
        if (!empty($request['expiry_date']) && self::is_expired($request['expiry_date'])) {
            return false;
        }
        
        return true;
    }
}