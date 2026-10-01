<div class="ci-admin-payments">
    <h1>💰 مدیریت پرداخت‌ها</h1>
    
    <?php
    $data_manager = CI_Data_Manager::get_instance();
    $payments = $data_manager->get_payments();
    $total_payments = $data_manager->get_total_payments();
    ?>
    
   <div class="ci-payments-stats">
    <div class="ci-stat-box">
        <h3>کل پرداخت‌ها</h3>
        <p><?php echo number_format($total_payments / 10); ?> تومان</p>
    </div>
    <div class="ci-stat-box">
        <h3>تعداد پرداخت‌ها</h3>
        <p><?php echo count($payments); ?></p>
    </div>
</div>
    
    <?php if (empty($payments)): ?>
        <div class="notice notice-warning">
            <p>⚠️ هیچ پرداختی یافت نشد.</p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>شناسه پرداخت</th>
                    <th>شماره درخواست</th>
                    <th>مبلغ (تومان)</th>
                    <th>شماره کارت</th>
                    <th>شماره پیگیری</th>
                    <th>وضعیت</th>
                    <th>تاریخ پرداخت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
    <?php foreach ($payments as $payment): 
        // دریافت اطلاعات درخواست برای نمایش نام کاربر
        $request = $data_manager->get_request($payment['request_id']);
        $user_name = '';
        if ($request && !empty($request['first_name']) && !empty($request['last_name'])) {
            $user_name = esc_html($request['first_name'] . ' ' . $request['last_name']);
        } elseif ($request && !empty($request['first_name'])) {
            $user_name = esc_html($request['first_name']);
        } else {
            $user_name = 'نامشخص';
        }
    ?>
        <tr>
            <td><code><?php echo esc_html($payment['payment_id']); ?></code></td>
            <td>
    <button class="button" 
            onclick="ciViewPaymentRequest(<?php echo $payment['request_id']; ?>);"
            style="background:transparent;border:none;color:#2271b1;cursor:pointer;text-decoration:underline;font-weight:600;padding:0;font-size:13px;">
        #<?php echo $payment['request_id']; ?>
    </button>
    <br>
    <span style="font-size:12px;color:#555;">
        <?php echo $user_name; ?>
    </span>
</td>
            <td>
                <strong><?php echo number_format($payment['amount'] / 10); ?></strong> تومان
                <small style="color:#999;font-size:11px;">
                    (<?php echo number_format($payment['amount']); ?> ریال)
                </small>
            </td>
            <td><?php echo esc_html($payment['card_number'] ?: '-'); ?></td>
            <td><?php echo esc_html($payment['tracking_number'] ?: '-'); ?></td>
            <td>
                <span class="ci-status <?php echo $payment['status']; ?>">
                    <?php echo $payment['status'] === 'success' ? 'موفق ✅' : 'در انتظار ⏳'; ?>
                </span>
            </td>
            <td><?php echo date_i18n('Y/m/d H:i', strtotime($payment['payment_date'])); ?></td>
            <td>
                <button class="button ci-delete-payment" 
                        data-payment-id="<?php echo esc_attr($payment['payment_id']); ?>">
                    🗑️ حذف
                </button>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>
        </table>
    <?php endif; ?>
	<!-- ============================================ -->
<!-- مودال مشاهده درخواست (برای صفحه پرداخت‌ها) -->
<!-- ============================================ -->
<div id="ci-request-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;overflow-y:auto;">
    <div style="background:#fff;max-width:800px;margin:50px auto;padding:30px;border-radius:12px;box-shadow:0 4px 30px rgba(0,0,0,0.2);">
        <div id="ci-modal-content"></div>
        <button onclick="jQuery('#ci-request-modal').hide();" class="button" style="margin-top:20px;">✕ بستن</button>
    </div>
</div>
</div>