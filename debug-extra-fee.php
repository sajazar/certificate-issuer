<?php
/**
 * دیباگ کامل extra_fee
 * آدرس: https://isahfn.ir/wp-content/plugins/certificate-issuer/debug-extra-fee.php
 */

require_once __DIR__ . '/../../../wp-load.php';

if (!current_user_can('manage_options')) {
    die('⛔ دسترسی غیرمجاز');
}

echo '<h1>🔍 دیباگ کامل extra_fee</h1>';
echo '<hr>';

// ============================================
// 1. بررسی تنظیمات
// ============================================
echo '<h2>📋 1. تنظیمات فعلی:</h2>';
$data_manager = CI_Data_Manager::get_instance();
$settings = $data_manager->get_settings();
echo '<pre>';
echo 'مبلغ ثبت‌نام: ' . ($settings['registration_fee'] ?? 'تنظیم نشده') . "\n";
echo 'مبلغ گواهی: ' . ($settings['certificate_fee'] ?? 'تنظیم نشده') . "\n";
echo '</pre>';

// ============================================
// 2. بررسی دیتابیس
// ============================================
echo '<h2>📁 2. بررسی دیتابیس (همه درخواست‌ها):</h2>';

$months = $data_manager->get_available_months();
echo '<table border="1" cellpadding="8" style="border-collapse:collapse;width:100%;">';
echo '<tr style="background:#f0f0f0;">';
echo '<th>ID</th><th>نام</th><th>تلفن</th><th>extra_fee (خام)</th><th>نوع</th><th>وضعیت</th><th>تاریخ</th>';
echo '</tr>';

foreach ($months as $month) {
    $monthly_data = $data_manager->load_monthly_data($month);
    if (empty($monthly_data['requests'])) continue;
    
    foreach ($monthly_data['requests'] as $request) {
        $extra_fee = isset($request['extra_fee']) ? $request['extra_fee'] : 'undefined';
        $type = gettype($extra_fee);
        
        // رنگ‌بندی بر اساس مقدار
        $color = '#fff';
        $text = '#000';
        if ($extra_fee === true || $extra_fee === 'true' || $extra_fee === 1) {
            $color = '#d1fae5';
            $text = '#059669';
        } elseif ($extra_fee === false || $extra_fee === 'false' || $extra_fee === 0 || $extra_fee === '') {
            $color = '#fee2e2';
            $text = '#dc2626';
        } elseif ($extra_fee === 'undefined' || $extra_fee === null) {
            $color = '#fef3c7';
            $text = '#d97706';
        }
        
        echo '<tr style="background:' . $color . ';color:' . $text . ';">';
        echo '<td>#' . $request['id'] . '</td>';
        echo '<td>' . $request['first_name'] . ' ' . $request['last_name'] . '</td>';
        echo '<td>' . $request['phone'] . '</td>';
        echo '<td><strong>' . var_export($extra_fee, true) . '</strong></td>';
        echo '<td>' . $type . '</td>';
        echo '<td>' . $request['status'] . '</td>';
        echo '<td>' . date_i18n('Y/m/d H:i', strtotime($request['created_at'])) . '</td>';
        echo '</tr>';
    }
}
echo '</table>';

// ============================================
// 3. فرم شبیه‌سازی ثبت
// ============================================
echo '<h2>🧪 3. شبیه‌سازی ثبت فرم:</h2>';

if (isset($_POST['simulate_submit'])) {
    echo '<h3>📝 نتیجه شبیه‌سازی:</h3>';
    
    // نمایش تمام داده‌های POST
    echo '<pre>';
    echo 'POST data:' . "\n";
    print_r($_POST);
    echo '</pre>';
    
    // بررسی extra_fee
    $extra_fee = isset($_POST['extra_fee']) && $_POST['extra_fee'] == 'on' ? true : false;
    echo '<div style="padding:15px;border-radius:8px;background:' . ($extra_fee ? '#d1fae5' : '#fee2e2') . ';">';
    echo '<strong>✅ مقدار extra_fee نهایی:</strong> ' . ($extra_fee ? 'true (درخواست گواهی)' : 'false (بدون درخواست گواهی)') . '<br>';
    echo '<strong>📌 isset($_POST["extra_fee"]):</strong> ' . (isset($_POST['extra_fee']) ? 'true' : 'false') . '<br>';
    echo '<strong>📌 $_POST["extra_fee"]:</strong> ' . (isset($_POST['extra_fee']) ? $_POST['extra_fee'] : 'NOT SET') . '<br>';
    echo '</div>';
}

// فرم شبیه‌سازی
?>
<form method="post" style="padding:20px;background:#f5f5f5;border-radius:10px;margin:20px 0;">
    <h3>📝 فرم شبیه‌سازی ثبت درخواست</h3>
    
    <div style="margin:10px 0;">
        <label>
            <input type="checkbox" id="sim_extra_fee" name="extra_fee" value="on">
            <strong>درخواست گواهی (اضافه‌مبلغ)</strong>
        </label>
    </div>
    
    <div style="margin:10px 0;">
        <label>
            <input type="text" name="sim_name" placeholder="نام تست" value="کاربر تست">
        </label>
    </div>
    
    <button type="submit" name="simulate_submit" style="padding:10px 20px;background:#4CAF50;color:#fff;border:none;border-radius:5px;cursor:pointer;">
        🧪 شبیه‌سازی ثبت
    </button>
</form>

<?php
// ============================================
// 4. بررسی فایل JSON
// ============================================
echo '<h2>📄 4. بررسی فایل‌های JSON:</h2>';

$months = $data_manager->get_available_months();
foreach ($months as $month) {
    $file = CI_DATA_DIR . $month . '.json';
    if (file_exists($file)) {
        echo '<h3>📁 ' . $month . '</h3>';
        echo '<pre style="background:#1a1a1a;color:#0f0;padding:15px;border-radius:8px;max-height:300px;overflow:auto;font-size:12px;">';
        
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        
        if ($data && isset($data['requests'])) {
            foreach ($data['requests'] as $request) {
                echo 'ID: ' . $request['id'] . ' | ';
                echo 'نام: ' . $request['first_name'] . ' ' . $request['last_name'] . ' | ';
                echo 'extra_fee: ' . (isset($request['extra_fee']) ? var_export($request['extra_fee'], true) : 'undefined') . "\n";
            }
        }
        echo '</pre>';
    }
}

// ============================================
// 5. راهنمای عیب‌یابی
// ============================================
echo '<h2>🛠️ 5. راهنمای عیب‌یابی:</h2>';
echo '<div style="padding:15px;background:#f0f7ff;border-radius:8px;border-right:4px solid #2196F3;">';
echo '<ul>';
echo '<li><strong>🔴 اگر extra_fee = true</strong> → کاربر درخواست گواهی داده است</li>';
echo '<li><strong>🔵 اگر extra_fee = false</strong> → کاربر درخواست گواهی نداده است</li>';
echo '<li><strong>🟡 اگر extra_fee = undefined</strong> → مشکل در ثبت وجود دارد</li>';
echo '</ul>';
echo '<p><strong>💡 برای تست:</strong> یک درخواست جدید ثبت کنید و سپس این صفحه را رفرش کنید.</p>';
echo '</div>';

// ============================================
// 6. نمایش لاگ‌ها
// ============================================
$log_file = WP_CONTENT_DIR . '/debug.log';
if (file_exists($log_file)) {
    echo '<h2>📋 6. آخرین لاگ‌ها:</h2>';
    echo '<pre style="background:#1a1a1a;color:#0f0;padding:15px;border-radius:8px;max-height:200px;overflow:auto;font-size:12px;">';
    $content = file_get_contents($log_file);
    $lines = explode("\n", $content);
    $ci_lines = array_filter($lines, function($line) {
        return strpos($line, '[CI') !== false || strpos($line, 'extra_fee') !== false;
    });
    echo implode("\n", array_slice($ci_lines, -20)) ?: 'هیچ لاگ مرتبطی یافت نشد';
    echo '</pre>';
}
?>