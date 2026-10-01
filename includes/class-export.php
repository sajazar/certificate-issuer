<?php
if (!defined('ABSPATH')) exit;

class CI_Export {
    private $data_manager;
    
    public function __construct() {
        $this->data_manager = CI_Data_Manager::get_instance();
        
        // ثبت اکشن‌های خروجی
        add_action('admin_post_ci_export_excel', [$this, 'export_excel']);
        add_action('admin_post_ci_export_word', [$this, 'export_word']);
        add_action('admin_post_ci_export_csv', [$this, 'export_csv']);
    }
    
    // ============================================
    // دریافت داده‌های خروجی
    // ============================================
    private function get_export_data() {
        $status = isset($_POST['export_status']) ? sanitize_text_field($_POST['export_status']) : 'all';
        $membership = isset($_POST['export_membership']) ? sanitize_text_field($_POST['export_membership']) : 'all';
        $fields = isset($_POST['export_fields']) ? array_map('sanitize_text_field', $_POST['export_fields']) : ['all'];
        
        $months = $this->data_manager->get_available_months();
        $requests = [];
        
        foreach ($months as $month) {
            $monthly_data = $this->data_manager->load_monthly_data($month);
            foreach ($monthly_data['requests'] as $request) {
                // فیلتر وضعیت
                if ($status !== 'all' && $request['status'] !== $status) continue;
                
                // فیلتر نوع عضویت
                if ($membership !== 'all') {
                    $member_type = $request['membership_type'] ?? 'affiliate';
                    if ($member_type !== $membership) continue;
                }
                
                $requests[] = $request;
            }
        }
        
        usort($requests, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $requests;
    }
    
    // ============================================
    // دریافت فیلدهای خروجی
    // ============================================
    private function get_export_fields($fields) {
        $all_fields = [
            'id' => 'شماره درخواست',
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'national_code' => 'کد ملی',
            'phone' => 'تلفن',
            'birth_date' => 'تاریخ تولد',
            'father_name' => 'نام پدر',
            'gender' => 'جنسیت',
            'birth_place' => 'محل تولد',
            'medical_id' => 'شماره نظام پزشکی',
            'student_id' => 'شماره دانشجویی',
            'field_of_study' => 'رشته تحصیلی',
            'last_degree' => 'آخرین مدرک تحصیلی',
            'last_university' => 'آخرین دانشگاه',
            'activity_field' => 'زمینه فعالیت',
            'membership_type' => 'نوع عضویت',
            'extra_fee' => 'درخواست گواهی',
            'amount' => 'مبلغ (تومان)',
            'status' => 'وضعیت',
            'created_at' => 'تاریخ ثبت',
            'issue_date' => 'تاریخ صدور',
            'expiry_date' => 'تاریخ انقضا'
        ];
        
        // اگر همه فیلدها انتخاب شده
        if (in_array('all', $fields)) {
            return $all_fields;
        }
        
        $selected = [];
        $groups = [
            'personal' => ['first_name', 'last_name', 'national_code', 'phone', 'birth_date', 'father_name', 'gender', 'birth_place', 'medical_id', 'student_id'],
            'education' => ['field_of_study', 'last_degree', 'last_university', 'activity_field'],
            'certificate' => ['membership_type', 'extra_fee', 'amount', 'status', 'issue_date', 'expiry_date']
        ];
        
        $selected_keys = [];
        foreach ($fields as $group) {
            if (isset($groups[$group])) {
                $selected_keys = array_merge($selected_keys, $groups[$group]);
            }
        }
        
        $selected_keys[] = 'id';
        $selected_keys[] = 'created_at';
        
        foreach ($all_fields as $key => $label) {
            if (in_array($key, $selected_keys)) {
                $selected[$key] = $label;
            }
        }
        
        return $selected;
    }
    
    // ============================================
    // خروجی Excel
    // ============================================
    public function export_excel() {
        $this->check_permission('ci_export_excel');
        
        $requests = $this->get_export_data();
        $fields = $this->get_export_fields($_POST['export_fields'] ?? ['all']);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="users-export-' . date('Y-m-d') . '.xlsx"');
        
        $output = fopen('php://output', 'w');
        
        // هدر ستون‌ها
        fputcsv($output, array_values($fields), "\t");
        
        // داده‌ها
        foreach ($requests as $request) {
            $row = [];
            foreach (array_keys($fields) as $key) {
                $value = $request[$key] ?? '';
                if ($key === 'membership_type') {
                    $value = $value === 'core' ? 'پیوسته' : 'وابسته';
                } elseif ($key === 'extra_fee') {
                    $value = $value ? 'بله' : 'خیر';
                } elseif ($key === 'status') {
                    $value = ci_get_status_label($value);
                } elseif ($key === 'amount') {
                    $value = number_format($value);
                }
                $row[] = $value;
            }
            fputcsv($output, $row, "\t");
        }
        
        fclose($output);
        exit;
    }
    
    // ============================================
    // خروجی Word
    // ============================================
    public function export_word() {
        $this->check_permission('ci_export_word');
        
        $requests = $this->get_export_data();
        $fields = $this->get_export_fields($_POST['export_fields'] ?? ['all']);
        
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>گزارش کاربران</title>
            <style>
                body { font-family: Tahoma, Arial, sans-serif; font-size: 12px; direction: rtl; }
                h1 { text-align: center; color: #1d2327; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th { background: #4CAF50; color: #fff; padding: 10px; border: 1px solid #ddd; }
                td { padding: 8px; border: 1px solid #ddd; }
                tr:nth-child(even) { background: #f9f9f9; }
                .footer { text-align: center; margin-top: 30px; color: #999; font-size: 11px; }
            </style>
        </head>
        <body>
            <h1>📋 گزارش کاربران</h1>
            <p>تعداد: ' . count($requests) . ' کاربر</p>
            <p>تاریخ: ' . date_i18n('Y/m/d H:i') . '</p>
            <table>
                <thead>
                    <tr>';
        foreach ($fields as $label) {
            $html .= '<th>' . $label . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        
        foreach ($requests as $request) {
            $html .= '<tr>';
            foreach (array_keys($fields) as $key) {
                $value = $request[$key] ?? '';
                if ($key === 'membership_type') {
                    $value = $value === 'core' ? 'پیوسته' : 'وابسته';
                } elseif ($key === 'extra_fee') {
                    $value = $value ? 'بله' : 'خیر';
                } elseif ($key === 'status') {
                    $value = ci_get_status_label($value);
                } elseif ($key === 'amount') {
                    $value = number_format($value);
                }
                $html .= '<td>' . esc_html($value) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>
            <div class="footer">گزارش تهیه شده توسط افزونه صدور گواهی</div>
        </body></html>';
        
        header('Content-Type: application/msword');
        header('Content-Disposition: attachment; filename="users-export-' . date('Y-m-d') . '.doc"');
        echo $html;
        exit;
    }
    
    // ============================================
    // خروجی CSV
    // ============================================
    public function export_csv() {
        $this->check_permission('ci_export_csv');
        
        $requests = $this->get_export_data();
        $fields = $this->get_export_fields($_POST['export_fields'] ?? ['all']);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="users-export-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF"); // BOM for UTF-8
        
        // هدر ستون‌ها
        fputcsv($output, array_values($fields));
        
        // داده‌ها
        foreach ($requests as $request) {
            $row = [];
            foreach (array_keys($fields) as $key) {
                $value = $request[$key] ?? '';
                if ($key === 'membership_type') {
                    $value = $value === 'core' ? 'پیوسته' : 'وابسته';
                } elseif ($key === 'extra_fee') {
                    $value = $value ? 'بله' : 'خیر';
                } elseif ($key === 'status') {
                    $value = ci_get_status_label($value);
                } elseif ($key === 'amount') {
                    $value = number_format($value);
                }
                $row[] = $value;
            }
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    // ============================================
    // بررسی دسترسی
    // ============================================
    private function check_permission($nonce_action) {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        if (!isset($_POST['ci_export_nonce']) || !wp_verify_nonce($_POST['ci_export_nonce'], $nonce_action)) {
            wp_die('اعتبار سنجی ناموفق');
        }
    }
}

// راه‌اندازی کلاس
new CI_Export();