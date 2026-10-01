<?php
// ============================================
// ✅ تابع جدید برای وضعیت گواهی
// ============================================
function ci_get_certificate_status($request) {
    $extra_fee = isset($request['extra_fee']) ? $request['extra_fee'] : false;
    $status = $request['status'] ?? '';
    
    $has_certificate = false;
    if ($extra_fee === true || $extra_fee === 'true' || $extra_fee === '1' || $extra_fee === 1 || $extra_fee === 'on' || $extra_fee === 'yes') {
        $has_certificate = true;
    }
    
    if (!$has_certificate) {
        return [
            'label' => 'فقط ثبت‌نام (بدون گواهی)',
            'icon' => '📋',
            'color' => '#64748b',
            'bg' => '#f1f5f9'
        ];
    }
    
    switch ($status) {
        case 'pending_payment':
            return ['label' => 'در انتظار پرداخت گواهی', 'icon' => '💳', 'color' => '#d97706', 'bg' => '#fef3c7'];
        case 'pending':
            return ['label' => 'در انتظار تایید گواهی', 'icon' => '⏳', 'color' => '#d97706', 'bg' => '#fef3c7'];
        case 'approved':
            return ['label' => 'گواهی تایید شده', 'icon' => '✅', 'color' => '#059669', 'bg' => '#d1fae5'];
        case 'rejected':
            return ['label' => 'درخواست گواهی رد شده', 'icon' => '❌', 'color' => '#dc2626', 'bg' => '#fee2e2'];
        case 'expired':
            return ['label' => 'گواهی منقضی شده', 'icon' => '⏰', 'color' => '#6b7280', 'bg' => '#f3f4f6'];
        case 'cancelled':
            return ['label' => 'درخواست گواهی لغو شده', 'icon' => '🚫', 'color' => '#6b7280', 'bg' => '#f3f4f6'];
        default:
            return ['label' => 'درخواست گواهی', 'icon' => '➕', 'color' => '#059669', 'bg' => '#d1fae5'];
    }
}
?>

<div class="ci-admin-requests">
    <h1>📋 درخواست‌های صدور گواهی</h1>
    
    <div class="ci-search-box">
        <form method="get">
            <input type="hidden" name="page" value="certificate-issuer-requests">
            <input type="text" name="search" placeholder="جستجو با نام، تلفن یا کد ملی..." 
                   value="<?php echo isset($_GET['search']) ? esc_attr($_GET['search']) : ''; ?>">
            <button type="submit" class="button">جستجو</button>
            <a href="admin.php?page=certificate-issuer-requests" class="button">پاک کردن</a>
        </form>
    </div>
    
    <div class="ci-filter-tabs">
        <a href="admin.php?page=certificate-issuer-requests" class="ci-tab <?php echo !isset($_GET['status']) ? 'active' : ''; ?>">
            همه (<?php echo $this->data_manager->get_request_count(); ?>)
        </a>
        <a href="admin.php?page=certificate-issuer-requests&status=pending_payment" 
           class="ci-tab <?php echo isset($_GET['status']) && $_GET['status'] === 'pending_payment' ? 'active' : ''; ?>">
            در انتظار پرداخت
        </a>
        <a href="admin.php?page=certificate-issuer-requests&status=pending" 
           class="ci-tab <?php echo isset($_GET['status']) && $_GET['status'] === 'pending' ? 'active' : ''; ?>">
            در انتظار تایید
        </a>
        <a href="admin.php?page=certificate-issuer-requests&status=approved" 
           class="ci-tab <?php echo isset($_GET['status']) && $_GET['status'] === 'approved' ? 'active' : ''; ?>">
            تایید شده
        </a>
        <a href="admin.php?page=certificate-issuer-requests&status=rejected" 
           class="ci-tab <?php echo isset($_GET['status']) && $_GET['status'] === 'rejected' ? 'active' : ''; ?>">
            رد شده
        </a>
    </div>
    
    <?php
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    
    $data_manager = CI_Data_Manager::get_instance();
    $requests = array();

    if (!empty($search)) {
        $requests = $data_manager->search_requests($search);
    } elseif (!empty($status)) {
        $requests = $data_manager->get_requests_by_status($status);
    } else {
        $months = $data_manager->get_available_months();
        foreach ($months as $month) {
            $monthly_data = $data_manager->load_monthly_data($month);
            if (!empty($monthly_data['requests'])) {
                $requests = array_merge($requests, $monthly_data['requests']);
            }
        }
        usort($requests, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
    }
    ?>
    
    <?php if (empty($requests)): ?>
        <div class="ci-empty-state"><p>هیچ درخواستی یافت نشد.</p></div>
    <?php else: ?>
        <div class="ci-requests-grid">
            <?php foreach ($requests as $request): ?>
                <?php 
                $cert_status = ci_get_certificate_status($request);
                $extra_fee = isset($request['extra_fee']) ? $request['extra_fee'] : false;
                $has_certificate = false;
                if ($extra_fee === true || $extra_fee === 'true' || $extra_fee === '1' || $extra_fee === 1 || $extra_fee === 'on' || $extra_fee === 'yes') {
                    $has_certificate = true;
                }
                ?>
                <div class="ci-request-card">
                    <div class="ci-card-header">
                        <div class="ci-card-id">
                            <span class="ci-badge-id">#<?php echo $request['id']; ?></span>
                            <span class="ci-card-date"><?php echo date_i18n('Y/m/d H:i', strtotime($request['created_at'])); ?></span>
                        </div>
                        <div class="ci-card-status">
                            <span class="ci-cert-status" style="background:<?php echo $cert_status['bg']; ?>;color:<?php echo $cert_status['color']; ?>;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px;">
                                <span style="font-size:16px;"><?php echo $cert_status['icon']; ?></span>
                                <?php echo $cert_status['label']; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="ci-card-body">
                        <div class="ci-card-info">
                            <div class="ci-card-avatar">
                                <?php if (!empty($request['avatar']) && file_exists(CI_UPLOADS_DIR . $request['avatar'])): ?>
                                    <img src="<?php echo CI_UPLOADS_URL . $request['avatar']; ?>" alt="تصویر کاربر">
                                <?php else: ?>
                                    <div class="ci-no-avatar">بدون تصویر</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="ci-card-details">
                                <div class="ci-card-name">
                                    <strong><?php echo esc_html($request['first_name'] . ' ' . $request['last_name']); ?></strong>
                                </div>
                                <div class="ci-card-meta">
                                    <span><strong>کد ملی:</strong> <?php echo esc_html($request['national_code']); ?></span>
                                    <span><strong>تلفن:</strong> <?php echo esc_html($request['phone']); ?></span>
                                    <span><strong>مبلغ:</strong> <?php echo number_format($request['amount']); ?> تومان</span>
                                    <span><strong>مدرک:</strong> <?php echo esc_html($request['last_degree'] ?? '-'); ?></span>
    <!-- ✅ نمایش تخفیف اعمال شده -->
    <?php if (!empty($request['discount_code']) && !empty($request['discount_percent'])): ?>
        <span style="background:#e8f5e9;color:#2e7d32;padding:2px 10px;border-radius:12px;font-size:11px;font-weight:600;">
            🎫 تخفیف <?php echo $request['discount_percent']; ?>% (کد: <?php echo esc_html($request['discount_code']); ?>)
        </span>
    <?php endif; ?>
                                    <?php if ($has_certificate): ?>
                                        <span style="background:#d1fae5;color:#059669;padding:2px 10px;border-radius:12px;font-size:11px;font-weight:600;">➕ شامل گواهی</span>
                                    <?php else: ?>
                                        <span style="background:#f1f5f9;color:#64748b;padding:2px 10px;border-radius:12px;font-size:11px;font-weight:600;">📋 فقط ثبت‌نام</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                  <div class="ci-card-actions">
    <!-- دکمه مشاهده - همیشه نمایش داده می‌شود -->
    <button class="button ci-view-request" data-id="<?php echo $request['id']; ?>">👁️ مشاهده</button>
    <button class="button ci-edit-request" data-id="<?php echo $request['id']; ?>" 
            style="background:#2196F3;color:#fff;border-color:#2196F3;">
        ✏️ ویرایش
    </button>
    <!-- ============================================ -->
    <!-- وضعیت: در انتظار تایید (pending) - قبل از تایید -->
    <!-- ============================================ -->
 <?php if ($request['status'] === 'pending'): ?>
    <button class="button button-primary ci-approve-request" data-id="<?php echo $request['id']; ?>">✅ تایید</button>
    <button class="button ci-reject-request" data-id="<?php echo $request['id']; ?>">❌ رد</button>
<?php endif; ?>
    
    <!-- ============================================ -->
    <!-- وضعیت: تایید شده و منتظر بارگذاری گواهی -->
    <!-- ============================================ -->
   <?php if ($request['status'] === 'pending_approved' && empty($request['certificate_file'])): ?>
    <button class="button ci-upload-certificate" data-id="<?php echo $request['id']; ?>">📤 بارگذاری گواهی</button>
<?php endif; ?>
    
    <!-- ============================================ -->
    <!-- وضعیت: گواهی بارگذاری شده (approved) -->
    <!-- ============================================ -->
    <?php if ($request['status'] === 'approved' && !empty($request['certificate_file']) && file_exists(CI_CERTIFICATES_DIR . $request['certificate_file'])): ?>
        <a href="<?php echo CI_CERTIFICATES_URL . $request['certificate_file']; ?>" target="_blank" class="button button-secondary">📥 دانلود</a>
        <button class="button ci-remove-certificate" data-id="<?php echo $request['id']; ?>" style="background:#ff9800;color:#fff;border-color:#ff9800;">
            🔄 حذف و صدور مجدد
        </button>
    <?php endif; ?>
    
    <?php if ($request['status'] === 'approved'): ?>
        <button class="button ci-renew-request" data-id="<?php echo $request['id']; ?>">🔄 تمدید</button>
    <?php endif; ?>
    
    <!-- دکمه حذف - همیشه نمایش داده می‌شود -->
    <button class="button ci-delete-request" data-id="<?php echo $request['id']; ?>">🗑️ حذف</button>
</div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- مودال‌ها -->
<div id="ci-request-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;overflow-y:auto;">
    <div style="background:#fff;max-width:800px;margin:50px auto;padding:30px;border-radius:12px;box-shadow:0 4px 30px rgba(0,0,0,0.2);">
        <div id="ci-modal-content"></div>
        <button onclick="jQuery('#ci-request-modal').hide();" class="button" style="margin-top:20px;">✕ بستن</button>
    </div>
</div>
<!-- مودال برای بارگذاری گواهی -->
<div id="ci-upload-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;">
    <div style="background:#fff;max-width:600px;margin:50px auto;padding:30px;border-radius:12px;box-shadow:0 4px 30px rgba(0,0,0,0.2);">
        <h2>📤 بارگذاری گواهی</h2>
        <form id="ci-upload-certificate-form" enctype="multipart/form-data">
            <input type="hidden" name="request_id" id="ci-upload-request-id">
            
            <div style="margin:20px 0;">
                <label style="display:block;font-weight:600;margin-bottom:8px;">فایل گواهی (تصویر):</label>
                <input type="file" name="certificate_file" accept="image/*" required style="width:100%;padding:10px;border:2px dashed #ccc;border-radius:8px;">
            </div>
            
            <!-- ============================================ -->
            <!-- تاریخ انقضا به صورت خودکار و غیرقابل ویرایش -->
            <!-- ============================================ -->
            <div style="margin:20px 0;padding:15px;background:#f8f9fa;border-radius:8px;border-right:4px solid #4CAF50;">
                <label style="display:block;font-weight:600;margin-bottom:5px;color:#1d2327;">📅 تاریخ انقضا</label>
                <p style="margin:0;font-size:16px;color:#333;" id="ci-expiry-display">
                    <span id="ci-expiry-date-text">در حال محاسبه...</span>
                </p>
                <input type="hidden" name="expiry_date" id="ci-expiry-date-hidden">
                <small style="display:block;margin-top:5px;color:#666;font-size:12px;">تاریخ انقضا بر اساس تنظیمات (<?php echo $settings['certificate_validity_months'] ?? 12; ?> ماه) به صورت خودکار محاسبه می‌شود</small>
            </div>
            <!-- ============================================ -->
            
            <div style="display:flex;gap:10px;margin-top:20px;padding-top:20px;border-top:2px solid #f0f0f1;">
                <button type="submit" class="button button-primary">📤 بارگذاری</button>
                <button type="button" onclick="jQuery('#ci-upload-modal').hide();" class="button">لغو</button>
            </div>
        </form>
    </div>
</div>
<!-- ============================================ -->
<!-- مودال ویرایش اطلاعات کاربر -->
<!-- ============================================ -->
<div id="ci-edit-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;overflow-y:auto;">
    <div style="background:#fff;max-width:800px;margin:50px auto;padding:35px;border-radius:16px;box-shadow:0 8px 40px rgba(0,0,0,0.25);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;padding-bottom:15px;border-bottom:2px solid #f0f0f1;">
            <h2 style="margin:0;color:#1d2327;">✏️ ویرایش اطلاعات کاربر</h2>
            <button onclick="jQuery('#ci-edit-modal').hide();" class="button" style="font-size:18px;">✕</button>
        </div>
        
        <form id="ci-edit-form" enctype="multipart/form-data">
            <input type="hidden" name="request_id" id="ci-edit-request-id">
            <?php wp_nonce_field('ci_edit_request', 'ci_edit_nonce'); ?>
            
            <!-- اطلاعات شخصی -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">نام <span style="color:red;">*</span></label>
                    <input type="text" name="first_name" id="edit_first_name" class="widefat" required>
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">نام خانوادگی <span style="color:red;">*</span></label>
                    <input type="text" name="last_name" id="edit_last_name" class="widefat" required>
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">کد ملی <span style="color:red;">*</span></label>
                    <input type="text" name="national_code" id="edit_national_code" class="widefat" maxlength="10" required>
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">تلفن همراه <span style="color:red;">*</span></label>
                    <input type="text" name="phone" id="edit_phone" class="widefat" required>
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">نام پدر <span style="color:red;">*</span></label>
                    <input type="text" name="father_name" id="edit_father_name" class="widefat" required>
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">تاریخ تولد <span style="color:red;">*</span></label>
                    <input type="text" name="birth_date" id="edit_birth_date" class="widefat" placeholder="مثلاً 1370/01/15" required>
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">جنسیت</label>
                    <select name="gender" id="edit_gender" class="widefat">
                        <option value="مرد">مرد</option>
                        <option value="زن">زن</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">محل تولد</label>
                    <input type="text" name="birth_place" id="edit_birth_place" class="widefat">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">شماره نظام پزشکی</label>
                    <input type="text" name="medical_id" id="edit_medical_id" class="widefat">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">شماره دانشجویی</label>
                    <input type="text" name="student_id" id="edit_student_id" class="widefat">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">رشته تحصیلی</label>
                    <input type="text" name="field_of_study" id="edit_field_of_study" class="widefat">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">آخرین مدرک تحصیلی</label>
                    <input type="text" name="last_degree" id="edit_last_degree" class="widefat">
                </div>
                <div style="grid-column:1/-1;">
                    <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">آخرین دانشگاه</label>
                    <input type="text" name="last_university" id="edit_last_university" class="widefat">
                </div>
                <div style="grid-column:1/-1;">
                    <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">زمینه فعالیت</label>
                    <textarea name="activity_field" id="edit_activity_field" rows="3" class="widefat"></textarea>
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">نوع عضویت</label>
                    <select name="membership_type" id="edit_membership_type" class="widefat">
                        <option value="affiliate">وابسته</option>
                        <option value="core">پیوسته</option>
                    </select>
                </div>
            <!-- ============================================ -->
<!-- ✅ وضعیت پرداخت -->
<!-- ============================================ -->
<div>
    <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">وضعیت پرداخت</label>
    <select name="status" id="edit_status" class="widefat">
        <option value="pending_payment">در انتظار پرداخت</option>
        <option value="pending">در انتظار تایید</option>
        <option value="pending_approved">تایید شده - منتظر گواهی</option>
        <option value="approved">تایید شده ✅</option>
        <option value="rejected">رد شده ❌</option>
        <option value="expired">منقضی شده ⏰</option>
        <option value="cancelled">لغو شده</option>
    </select>
</div>
            </div>
            
            <div style="margin-top:25px;padding-top:20px;border-top:2px solid #f0f0f1;display:flex;gap:10px;">
                <button type="submit" class="button button-primary" style="padding:10px 30px;height:auto;font-size:15px;">💾 ذخیره تغییرات</button>
                <button type="button" onclick="jQuery('#ci-edit-modal').hide();" class="button" style="padding:10px 20px;height:auto;">لغو</button>
            </div>
        </form>
    </div>
</div>
<script>
// ============================================
// محاسبه خودکار تاریخ انقضا (شمسی)
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('ci-upload-modal');
    if (!modal) return;
    
    // تابع محاسبه تاریخ انقضا
    function calculateExpiryDate() {
        var validityMonths = <?php echo intval($settings['certificate_validity_months'] ?? 12); ?>;
        var today = new Date();
        
        // اضافه کردن ماه‌ها به تاریخ امروز
        var expiryDate = new Date(today);
        expiryDate.setMonth(expiryDate.getMonth() + validityMonths);
        
        // فرمت تاریخ به YYYY-MM-DD
        var year = expiryDate.getFullYear();
        var month = String(expiryDate.getMonth() + 1).padStart(2, '0');
        var day = String(expiryDate.getDate()).padStart(2, '0');
        var formattedDate = year + '-' + month + '-' + day;
        
        // نمایش تاریخ شمسی (تبدیل میلادی به شمسی)
        var persianDate = convertToPersianDate(expiryDate);
        
        // نمایش در صفحه
        document.getElementById('ci-expiry-date-text').textContent = persianDate;
        document.getElementById('ci-expiry-date-hidden').value = formattedDate;
    }
    
    // ============================================
    // تبدیل تاریخ میلادی به شمسی
    // ============================================
    function convertToPersianDate(date) {
        var persianMonths = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 
                            'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
        
        // محاسبه روزهای سال
        var daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        
        // محاسبه سال شمسی (تقریبی)
        var persianYear = date.getFullYear() - 621;
        var persianMonth = 0;
        var persianDay = 0;
        
        // محاسبه روز سال میلادی
        var startOfYear = new Date(date.getFullYear(), 0, 1);
        var dayOfYear = Math.floor((date - startOfYear) / (24 * 60 * 60 * 1000));
        
        // الگوریتم تبدیل (ساده شده)
        if (dayOfYear < 79) {
            persianYear = date.getFullYear() - 622;
            var remaining = dayOfYear + 21;
            for (var i = 0; i < 12; i++) {
                if (remaining <= daysInMonth[i]) {
                    persianMonth = i + 1;
                    persianDay = remaining;
                    break;
                }
                remaining -= daysInMonth[i];
            }
        } else {
            persianYear = date.getFullYear() - 621;
            var remaining = dayOfYear - 78;
            for (var i = 0; i < 12; i++) {
                if (remaining <= daysInMonth[i]) {
                    persianMonth = i + 1;
                    persianDay = remaining;
                    break;
                }
                remaining -= daysInMonth[i];
            }
        }
        
        return persianYear + '/' + String(persianMonth).padStart(2, '0') + '/' + String(persianDay).padStart(2, '0');
    }
    
    // محاسبه تاریخ هنگام باز شدن مودال
    var observer = new MutationObserver(function() {
        if (modal.style.display !== 'none') {
            calculateExpiryDate();
        }
    });
    
    observer.observe(modal, { attributes: true, attributeFilter: ['style'] });
    
    // محاسبه اولیه
    calculateExpiryDate();
});
</script>