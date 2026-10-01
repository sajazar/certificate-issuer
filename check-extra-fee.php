<?php
/**
 * بررسی مقدار extra_fee در دیتابیس
 * آدرس: https://isahfn.ir/wp-content/plugins/certificate-issuer/check-extra-fee.php
 */

require_once __DIR__ . '/../../../wp-load.php';

if (!current_user_can('manage_options')) {
    die('دسترسی غیرمجاز');
}

$data_manager = CI_Data_Manager::get_instance();
$months = $data_manager->get_available_months();

echo '<h2>🔍 بررسی مقدار extra_fee در دیتابیس</h2>';

foreach ($months as $month) {
    echo '<h3>📁 ماه: ' . $month . '</h3>';
    $monthly_data = $data_manager->load_monthly_data($month);
    
    if (empty($monthly_data['requests'])) {
        echo 'هیچ درخواستی در این ماه وجود ندارد.<br>';
        continue;
    }
    
    echo '<table border="1" cellpadding="8" style="border-collapse:collapse;">';
    echo '<tr><th>ID</th><th>نام</th><th>extra_fee (خام)</th><th>نوع</th><th>وضعیت</th></tr>';
    
    foreach ($monthly_data['requests'] as $request) {
        $extra_fee = isset($request['extra_fee']) ? $request['extra_fee'] : 'undefined';
        $type = gettype($extra_fee);
        
        echo '<tr>';
        echo '<td>#' . $request['id'] . '</td>';
        echo '<td>' . $request['first_name'] . ' ' . $request['last_name'] . '</td>';
        echo '<td><strong>' . var_export($extra_fee, true) . '</strong></td>';
        echo '<td>' . $type . '</td>';
        echo '<td>' . $request['status'] . '</td>';
        echo '</tr>';
    }
    
    echo '</table><br>';
}
?>