<?php
if (!defined('ABSPATH')) exit;

class CI_User_Endpoint {
    private $data_manager;
    
    public function __construct() {
        $this->data_manager = CI_Data_Manager::get_instance();
    }
    
    public function render_page() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $this->show_status_messages($status);
        
        if ($action === 'new') {
            $this->render_form();
        } elseif ($action === 'view') {
            $this->render_view();
        } else {
            $this->render_list();
        }
    }
    
    // ============================================
    // نمایش لیست درخواست‌ها
    // ============================================
    private function render_list() {
        $user_id = get_current_user_id();
        $requests = $this->data_manager->get_user_requests($user_id);
        ?>
        <div class="ci-user-dashboard">
            <div class="ci-header">
                <h2>📜 درخواست های عضویت </h2>
                <a href="?certificate-issuer=1&action=new" class="ci-btn ci-btn-primary">➕ درخواست جدید</a>
            </div>
            
            <?php if (empty($requests)): ?>
                <div class="ci-empty-state">
                    📜
                    <p>شما هیچ درخواستی برای عضویت ندارید.</p>
                    <a href="?certificate-issuer=1&action=new" class="ci-btn ci-btn-primary">➕ درخواست جدید</a>
                </div>
            <?php else: ?>
                <div class="ci-table-wrapper">
                    <table class="ci-table">
                        <thead>
                            <tr>
                                <th>شماره</th>
                                <th>نام و نام خانوادگی</th>
                                <th>کد ملی</th>
                                <th>تاریخ ثبت</th>
                                <th>مبلغ</th>
                                <th>وضعیت</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><span class="ci-badge ci-badge-id">#<?php echo $request['id']; ?></span></td>
                                    <td><?php echo esc_html($request['first_name'] . ' ' . $request['last_name']); ?></td>
                                    <td><?php echo esc_html($request['national_code']); ?></td>
                                    <td><?php echo date_i18n('Y/m/d H:i', strtotime($request['created_at'])); ?></td>
                                    <td><?php echo number_format($request['amount']); ?> تومان</td>
                                    <td>
                                        <span class="ci-status <?php echo $request['status']; ?>">
                                            <?php echo $this->get_status_label($request['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?certificate-issuer=1&action=view&id=<?php echo $request['id']; ?>" class="ci-btn ci-btn-sm">👁️ مشاهده</a>
                                        <?php if ($request['status'] === 'pending_payment'): ?>
                                            <button class="ci-btn ci-btn-sm ci-btn-pay" data-request-id="<?php echo $request['id']; ?>">💳 پرداخت</button>
                                        <?php endif; ?>
                                        <?php if ($request['status'] === 'approved' && !empty($request['certificate_file'])): ?>
                                            <a href="<?php echo $this->get_certificate_url($request['id']); ?>" class="ci-btn ci-btn-sm ci-btn-success" target="_blank">📥 دانلود گواهی</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    // ============================================
    // فرم ثبت درخواست (نسخه جدید با کد تخفیف)
    // ============================================
    private function render_form() {
        $settings = $this->data_manager->get_settings();
        $fee_affiliate = intval($settings['fee_affiliate'] ?? 100000);
        $fee_core = intval($settings['fee_core'] ?? 300000);
        $certificate_fee = intval($settings['certificate_fee'] ?? 30000);
        
        if (isset($_POST['ci_submit'])) {
            $this->handle_form_submission();
            return;
        }
        ?>
        <div class="ci-user-dashboard">
            <div class="ci-header">
                <h2>📝 فرم ثبت درخواست</h2>
                <a href="?certificate-issuer=1" class="ci-btn ci-btn-secondary">⬅️ بازگشت به لیست</a>
            </div>
            
            <div class="ci-form-wrapper">
                <form method="post" enctype="multipart/form-data" class="ci-form" id="ci-certificate-form">
                    <?php wp_nonce_field('ci_submit_request', 'ci_nonce'); ?>
                    
                    <!-- ============================================ -->
                    <!-- اطلاعات شخصی -->
                    <!-- ============================================ -->
                    <h3 style="margin:0 0 15px 0;padding-bottom:10px;border-bottom:2px solid #f0f0f1;color:#1d2327;">👤 اطلاعات شخصی</h3>
                    <div class="ci-form-row">
    <div class="ci-form-group">
        <label for="first_name">نام <span class="required">*</span></label>
        <input type="text" id="first_name" name="first_name" required 
               autocomplete="off" autofill="off"
               value="<?php echo isset($_POST['first_name']) ? esc_attr($_POST['first_name']) : ''; ?>">
    </div>
    <div class="ci-form-group">
        <label for="last_name">نام خانوادگی <span class="required">*</span></label>
        <input type="text" id="last_name" name="last_name" required 
               autocomplete="off" autofill="off"
               value="<?php echo isset($_POST['last_name']) ? esc_attr($_POST['last_name']) : ''; ?>">
    </div>
</div>

<div class="ci-form-row">
    <div class="ci-form-group">
        <label for="national_code">کد ملی <span class="required">*</span></label>
        <input type="text" id="national_code" name="national_code" required maxlength="10"
               autocomplete="off" autofill="off"
               value="<?php echo isset($_POST['national_code']) ? esc_attr($_POST['national_code']) : ''; ?>">
    </div>
    <div class="ci-form-group">
        <label for="father_name">نام پدر <span class="required">*</span></label>
        <input type="text" id="father_name" name="father_name" required 
               autocomplete="off" autofill="off"
               value="<?php echo isset($_POST['father_name']) ? esc_attr($_POST['father_name']) : ''; ?>">
    </div>
</div>

<div class="ci-form-row">
    <div class="ci-form-group">
        <label for="gender">جنسیت <span class="required">*</span></label>
        <select id="gender" name="gender" required autocomplete="off">
            <option value="">انتخاب کنید</option>
            <option value="مرد" <?php echo isset($_POST['gender']) && $_POST['gender'] == 'مرد' ? 'selected' : ''; ?>>مرد</option>
            <option value="زن" <?php echo isset($_POST['gender']) && $_POST['gender'] == 'زن' ? 'selected' : ''; ?>>زن</option>
        </select>
    </div>
    <div class="ci-form-group">
        <label for="birth_place">محل تولد <span class="required">*</span></label>
        <input type="text" id="birth_place" name="birth_place" required 
               autocomplete="off" autofill="off"
               value="<?php echo isset($_POST['birth_place']) ? esc_attr($_POST['birth_place']) : ''; ?>">
    </div>
</div>

<div class="ci-form-row">
    <div class="ci-form-group">
        <label for="phone">تلفن همراه <span class="required">*</span></label>
        <input type="tel" id="phone" name="phone" required 
               autocomplete="off" autofill="off"
               value="<?php echo isset($_POST['phone']) ? esc_attr($_POST['phone']) : ''; ?>">
    </div>
    <div class="ci-form-group">
        <label>تاریخ تولد <span class="required">*</span></label>
        <div class="ci-birthdate-group" style="display:flex;align-items:center;justify-content:center;direction:ltr;">
            <input type="text" id="birth_year" name="birth_year" required maxlength="4" 
                   placeholder="سال" style="width:30%;display:inline-block;text-align:center;"
                   autocomplete="off" autofill="off"
                   value="<?php echo isset($_POST['birth_year']) ? esc_attr($_POST['birth_year']) : ''; ?>">
            <span style="margin:0 5px;font-size:18px;color:#999;">/</span>
            <input type="text" id="birth_month" name="birth_month" required maxlength="2" 
                   placeholder="ماه" style="width:30%;display:inline-block;text-align:center;"
                   autocomplete="off" autofill="off"
                   value="<?php echo isset($_POST['birth_month']) ? esc_attr($_POST['birth_month']) : ''; ?>">
            <span style="margin:0 5px;font-size:18px;color:#999;">/</span>
            <input type="text" id="birth_day" name="birth_day" required maxlength="2" 
                   placeholder="روز" style="width:30%;display:inline-block;text-align:center;"
                   autocomplete="off" autofill="off"
                   value="<?php echo isset($_POST['birth_day']) ? esc_attr($_POST['birth_day']) : ''; ?>">
        </div>
        <small style="color:#666;font-size:12px;">اعداد فارسی و انگلیسی قابل قبول است</small>
    </div>
</div>

<div class="ci-form-row">
    <div class="ci-form-group">
        <label for="medical_id">شماره نظام پزشکی <small style="color:#999;">(اختیاری)</small></label>
        <input type="text" id="medical_id" name="medical_id" 
               autocomplete="off" autofill="off"
               value="<?php echo isset($_POST['medical_id']) ? esc_attr($_POST['medical_id']) : ''; ?>">
    </div>
    <div class="ci-form-group">
        <label for="student_id">شماره دانشجویی <small style="color:#999;">(اختیاری)</small></label>
        <input type="text" id="student_id" name="student_id" 
               autocomplete="off" autofill="off"
               value="<?php echo isset($_POST['student_id']) ? esc_attr($_POST['student_id']) : ''; ?>">
    </div>
</div>

<!-- ============================================ -->
<!-- اطلاعات تحصیلی -->
<!-- ============================================ -->
<h3 style="margin:25px 0 15px 0;padding-bottom:10px;border-bottom:2px solid #f0f0f1;color:#1d2327;">🎓 اطلاعات تحصیلی</h3>

<div class="ci-form-row">
    <div class="ci-form-group">
        <label for="field_of_study">رشته تحصیلی <span class="required">*</span></label>
        <select id="field_of_study" name="field_of_study" required autocomplete="off">
            <option value="">انتخاب کنید</option>
            <?php 
            $fields = ['علوم تغذیه', 'صنایع غذایی', 'پزشکی', 'داروسازی', 'سایر'];
            foreach ($fields as $field):
                $selected = isset($_POST['field_of_study']) && $_POST['field_of_study'] == $field ? 'selected' : '';
            ?>
                <option value="<?php echo $field; ?>" <?php echo $selected; ?>><?php echo $field; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="ci-form-group">
        <label for="last_degree">آخرین مدرک تحصیلی <span class="required">*</span></label>
        <select id="last_degree" name="last_degree" required autocomplete="off">
            <option value="">انتخاب کنید</option>
            <?php 
            $degrees = ['دانشجوی کارشناسی', 'کارشناسی', 'کارشناسی ارشد', 'دکتری تخصصی', 'دکتری عمومی', 'تخصص', 'فوق تخصص'];
            foreach ($degrees as $degree):
                $selected = isset($_POST['last_degree']) && $_POST['last_degree'] == $degree ? 'selected' : '';
            ?>
                <option value="<?php echo $degree; ?>" <?php echo $selected; ?>><?php echo $degree; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="ci-form-group">
    <label for="last_university">آخرین دانشگاه محل تحصیل <span class="required">*</span></label>
    <input type="text" id="last_university" name="last_university" required 
           autocomplete="off" autofill="off"
           value="<?php echo isset($_POST['last_university']) ? esc_attr($_POST['last_university']) : ''; ?>">
</div>

<div class="ci-form-group">
    <label for="activity_field">زمینه فعالیت در حوزه تغذیه <span class="required">*</span></label>
    <textarea id="activity_field" name="activity_field" rows="3" required
              autocomplete="off" autofill="off"><?php echo isset($_POST['activity_field']) ? esc_textarea($_POST['activity_field']) : ''; ?></textarea>
</div>
                    
                    <!-- ============================================ -->
                    <!-- آپلود فایل‌ها -->
                    <!-- ============================================ -->
                    <h3 style="margin:25px 0 15px 0;padding-bottom:10px;border-bottom:2px solid #f0f0f1;color:#1d2327;">📎 آپلود فایل‌ها</h3>
                    
                    <div class="ci-form-group">
                        <label for="avatar">تصویر پرسنلی <span class="required">*</span></label>
                        <div class="ci-upload-wrapper">
                            <input type="file" id="avatar" name="avatar" accept="image/*" required>
                            <div class="ci-upload-preview"></div>
                            <small>فرمت‌های مجاز: JPG, PNG, GIF - حداکثر 2MB</small>
                        </div>
                    </div>
                    
                    <div class="ci-form-group">
                        <label for="card_image">تصویر کارت نظام پزشکی یا کارت دانشجویی <span class="required">*</span></label>
                        <div class="ci-upload-wrapper">
                            <input type="file" id="card_image" name="card_image" accept="image/*" required>
                            <div class="ci-upload-preview-card"></div>
                            <small>فرمت‌های مجاز: JPG, PNG, GIF - حداکثر 2MB</small>
                        </div>
                    </div>
                    
                    <!-- ============================================ -->
                    <!-- مبلغ حق عضویت -->
                    <!-- ============================================ -->
<h3 style="margin:25px 0 15px 0;padding-bottom:10px;border-bottom:2px solid #f0f0f1;color:#1d2327;"> مبلغ حق عضویت</h3>

<div class="ci-form-group ci-checkbox-group">
    <label>
        <input type="radio" name="membership_type" value="core" required
               <?php echo (!isset($_POST['membership_type']) || $_POST['membership_type'] == 'core') ? 'checked' : ''; ?>
               onchange="toggleCertificateSection(); updateMembershipTotal();">
        <span>عضویت پیوسته (سایر افراد) - <?php echo number_format($fee_core); ?> تومان</span>
    </label>
</div>
<div class="ci-form-group ci-checkbox-group" id="certificate-section">
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;flex-wrap:wrap;background: #f9eded; padding: 5px; border-radius: 5px;">
        <input type="checkbox" id="extra_fee" name="extra_fee" value="on"
               <?php echo isset($_POST['extra_fee']) && $_POST['extra_fee'] == 'on' ? 'checked' : ''; ?>
               onchange="updateMembershipTotal()">
        <span>درخواست گواهی تایید شده (اضافه‌مبلغ <?php echo number_format($certificate_fee); ?> تومان)</span>
        <!-- ============================================ -->
        <!-- ✅ دکمه مشاهده نمونه گواهی - چسبیده به متن -->
        <!-- ============================================ -->
        <button type="button" id="ci-view-sample-certificate" 
                style="#ffffff9c;color:#197d1d;font-weight: 700;border-radius:4px;padding:2px 10px;font-size:12px;cursor:pointer;transition:all 0.3s ease;white-space:nowrap;display:inline-block;margin-right:5px;">
            🖼️ نمونه گواهی
        </button>
    </label>
</div>

<div class="ci-form-group ci-checkbox-group">
    <label>
        <input type="radio" name="membership_type" value="affiliate" required
               <?php echo isset($_POST['membership_type']) && $_POST['membership_type'] == 'affiliate' ? 'checked' : ''; ?>
               onchange="toggleCertificateSection(); updateMembershipTotal();">
        <span>عضویت وابسته (دانشجویان مقطع کارشناسی - افراد با مدرک دکتری عمومی) - <?php echo number_format($fee_affiliate); ?> تومان</span>
    </label>
</div>


                    <!-- ============================================ -->
                    <!-- 🎫 کد تخفیف -->
                    <!-- ============================================ -->
                    <div class="ci-form-group" id="ci-discount-section">
                        <label for="discount_code">🎫 کد تخفیف</label>
                        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                            <input type="text" id="discount_code" name="discount_code" 
                                   placeholder="کد تخفیف خود را وارد کنید..." 
                                   style="flex:1;min-width:200px;padding:10px 14px;border:2px solid #e0e0e0;border-radius:8px;font-size:14px;"
                                   value="<?php echo isset($_POST['discount_code']) ? esc_attr($_POST['discount_code']) : ''; ?>">
                            <button type="button" id="ci-apply-discount-btn" 
                                    style="padding:10px 25px;background:#6c757d;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.3s ease;white-space:nowrap;">
                                اعمال کد
                            </button>
                        </div>
                        <small id="discount-message" style="display:none;margin-top:8px;font-size:13px;"></small>
                    </div>
                    
                   <div class="ci-form-group ci-total-amount">
    <h3>مبلغ قابل پرداخت: <span id="total-amount"><?php echo number_format($fee_affiliate); ?></span> تومان</h3>
    <p class="ci-price-breakdown">
        <span id="membership-price-display">عضویت وابسته: <?php echo number_format($fee_affiliate); ?> تومان</span>
        <span id="certificate-fee-display" style="display:none;">
            + هزینه گواهی: <?php echo number_format($certificate_fee); ?> تومان
        </span>
    </p>
</div>
<!-- ============================================ -->
<!-- مودال نمایش نمونه گواهی -->
<!-- ============================================ -->
<div id="ci-sample-certificate-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:99999;overflow-y:auto;align-items:center;justify-content:center;">
    <div style="display:flex;justify-content:center;align-items:center;min-height:100vh;padding:20px;margin:0 auto;">
        <div style="background:#fff;border-radius:16px;max-width:700px;width:100%;padding:25px;box-shadow:0 8px 40px rgba(0,0,0,0.3);position:relative;margin:20px;">
            <button onclick="document.getElementById('ci-sample-certificate-modal').style.display='none'; document.body.style.overflow='auto';" 
                    style="position:absolute;top:10px;right:15px;background:transparent;border:none;font-size:28px;cursor:pointer;color:#999;transition:color 0.3s ease;z-index:10;"
                    onmouseover="this.style.color='#333'" onmouseout="this.style.color='#999'">
                ✕
            </button>
            <h3 style="text-align:center;margin:0 0 15px 0;color:#1d2327;">🖼️ نمونه گواهی عضویت</h3>
            <div style="text-align:center;">
                <img src="https://isahfn.ir/wp-content/uploads/2026/09/sempool-scaled.jpg" 
                     alt="نمونه گواهی عضویت" 
                     style="max-width:100%;height:auto;border-radius:8px;border:1px solid #e0e0e0;box-shadow:0 2px 10px rgba(0,0,0,0.1);">
            </div>
            <p style="text-align:center;color:#666;font-size:13px;margin-top:15px;">
                این تصویر یک نمونه از گواهی عضویت است. پس از ثبت‌نام و تایید، گواهی اختصاصی شما صادر خواهد شد.
            </p>
        </div>
    </div>
</div>
<script type="text/javascript">
// ============================================
// تابع نمایش/مخفی کردن گزینه گواهی
// ============================================
function toggleCertificateSection() {
    var membershipRadios = document.querySelectorAll('input[name="membership_type"]');
    var certificateSection = document.getElementById('certificate-section');
    var extraCheckbox = document.getElementById('extra_fee');
    
    var selectedValue = 'affiliate';
    for (var i = 0; i < membershipRadios.length; i++) {
        if (membershipRadios[i].checked) {
            selectedValue = membershipRadios[i].value;
            break;
        }
    }
    
    if (selectedValue === 'core') {
        certificateSection.style.display = 'block';
    } else {
        certificateSection.style.display = 'none';
        if (extraCheckbox) {
            extraCheckbox.checked = false;
        }
    }
    
    if (typeof updateTotalWithAjax === 'function') {
        updateTotalWithAjax();
    }
}

// ============================================
// Ajax برای محاسبه مبلغ با اسپینر
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    var extraFee = document.getElementById('extra_fee');
    var totalSpan = document.getElementById('total-amount');
    var feeDisplay = document.getElementById('certificate-fee-display');
    var membershipRadios = document.querySelectorAll('input[name="membership_type"]');
    var membershipDisplay = document.getElementById('membership-price-display');
    
    // اسپینر
    var spinner = document.createElement('span');
    spinner.id = 'ci-spinner';
    spinner.style.cssText = 'display:none;margin-right:10px;width:20px;height:20px;border:3px solid #f3f3f3;border-top:3px solid #4CAF50;border-radius:50%;animation: ci-spin 0.8s linear infinite;';
    
    var style = document.createElement('style');
    style.textContent = `
        @keyframes ci-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
    totalSpan.parentNode.appendChild(spinner);
    
    function updateTotalWithAjax() {
        var isChecked = extraFee.checked ? 1 : 0;
        
        var membershipType = 'affiliate';
        for (var i = 0; i < membershipRadios.length; i++) {
            if (membershipRadios[i].checked) {
                membershipType = membershipRadios[i].value;
                break;
            }
        }
        
        spinner.style.display = 'inline-block';
        extraFee.disabled = true;
        totalSpan.textContent = '...';
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                spinner.style.display = 'none';
                extraFee.disabled = false;
                
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            totalSpan.textContent = response.data.total.toLocaleString();
                            feeDisplay.style.display = response.data.extra ? 'inline' : 'none';
                            if (membershipDisplay) {
                                var membershipLabel = response.data.membership_type === 'affiliate' ? 'عضویت وابسته' : 'عضویت پیوسته';
                                membershipDisplay.textContent = membershipLabel + ': ' + response.data.base.toLocaleString() + ' تومان';
                            }
                        } else {
                            totalSpan.textContent = 'خطا';
                        }
                    } catch(e) {
                        totalSpan.textContent = 'خطا';
                    }
                } else {
                    totalSpan.textContent = 'خطا';
                }
            }
        };
        
        xhr.send('action=ci_calculate_total&extra=' + isChecked + '&membership=' + membershipType + '&nonce=<?php echo wp_create_nonce('ci_ajax_nonce'); ?>');
    }
    
    // اتصال رویدادها
    if (extraFee) {
        extraFee.addEventListener('change', updateTotalWithAjax);
    }
    
    for (var i = 0; i < membershipRadios.length; i++) {
        membershipRadios[i].addEventListener('change', function() {
            toggleCertificateSection();
            updateTotalWithAjax();
        });
    }
    
    // اجرا در ابتدا
    toggleCertificateSection();
    updateTotalWithAjax();
});

// ============================================
// اعمال تخفیف
// ============================================
function applyDiscount() {
    var codeInput = document.getElementById('discount_code');
    var totalSpan = document.getElementById('total-amount');
    var messageSpan = document.getElementById('discount-message');
    var code = codeInput.value.trim();
    
    if (!code) {
        messageSpan.style.display = 'none';
        // محاسبه مجدد مبلغ اصلی
        updateTotalWithAjax();
        return;
    }
    
    // ارسال درخواست برای بررسی کد تخفیف
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        var discountPercent = response.data.discount_percent;
                        var originalAmount = response.data.original_amount;
                        var discountedAmount = originalAmount - (originalAmount * discountPercent / 100);
                        
                        totalSpan.textContent = discountedAmount.toLocaleString();
                        messageSpan.innerHTML = '✅ کد تخفیف معتبر است! ' + discountPercent + '% تخفیف';
                        messageSpan.style.color = '#2e7d32';
                        messageSpan.style.display = 'block';
                    } else {
                        // برگرداندن مبلغ اصلی
                        updateTotalWithAjax();
                        messageSpan.innerHTML = '❌ ' + response.data.message;
                        messageSpan.style.color = '#c62828';
                        messageSpan.style.display = 'block';
                    }
                } catch(e) {
                    console.log('خطا در پردازش پاسخ');
                }
            }
        }
    };
    
    // دریافت نوع عضویت
    var membershipType = 'affiliate';
    var radios = document.querySelectorAll('input[name="membership_type"]');
    for (var i = 0; i < radios.length; i++) {
        if (radios[i].checked) {
            membershipType = radios[i].value;
            break;
        }
    }
    
    var extraFee = document.getElementById('extra_fee');
    var extraValue = (extraFee && extraFee.checked) ? '1' : '0';
    
    xhr.send('action=ci_validate_discount&discount_code=' + encodeURIComponent(code) + 
             '&membership_type=' + membershipType +
             '&extra_fee=' + extraValue +
             '&nonce=<?php echo wp_create_nonce('ci_ajax_nonce'); ?>');
}

// ============================================
// دکمه اعمال کد تخفیف
// ============================================
(function() {
    var applyBtn = document.getElementById('ci-apply-discount-btn');
    var codeInput = document.getElementById('discount_code');
    
    if (applyBtn && codeInput) {
        // اعمال کد با کلیک روی دکمه
        applyBtn.addEventListener('click', function() {
            if (typeof applyDiscount === 'function') {
                applyDiscount();
            }
        });
        
        // اعمال کد با فشار Enter
        codeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (typeof applyDiscount === 'function') {
                    applyDiscount();
                }
            }
        });
    }
})();
</script>
       <!-- ============================================ -->
<!-- ✅ تیک تایید شرایط -->
<!-- ============================================ -->
<div class="ci-form-group ci-checkbox-group" style="margin:20px 0 15px 0;">
    <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;font-weight:normal;">
        <input type="checkbox" id="ci_terms_accept" name="ci_terms_accept" value="1" required style="margin-top:3px;width:18px;height:18px;flex-shrink:0;">
        <span style="font-size:14px;color:#1d2327;line-height:1.6;">
            ثبت نام شما همراه با تایید پذیرش شرایط و مطالعه اساسنامه خواهد بود 
            <a href="https://isahfn.ir/wp-content/uploads/2026/08/%D8%A7%D8%B3%D8%A7%D8%B3-%D9%86%D8%A7%D9%85%D9%87-98.pdf" 
               target="_blank" 
               style="color:#2196F3;text-decoration:underline;font-weight:600;">
               ( اساسنامه انجمن )
            </a>
            <span style="color:#f44336;margin-right:4px;">*</span>
        </span>
    </label>
    <small id="terms-error" style="display:none;color:#f44336;font-size:13px;margin-top:5px;">
        ⚠️ برای ثبت‌نام باید شرایط و اساسنامه را تایید کنید.
    </small>
</div>             
                    <div class="ci-form-actions">
                        <button type="submit" name="ci_submit" class="ci-btn ci-btn-primary ci-btn-large">✅ ثبت و پرداخت</button>
                        <a href="?certificate-issuer=1" class="ci-btn ci-btn-secondary">انصراف</a>
                    </div>
                </form>
            </div>
        </div>
        
        <script type="text/javascript">
        // ============================================
        // محاسبه مبلغ عضویت (بدون Ajax)
        // ============================================
        function updateMembershipTotal() {
            var affiliateFee = <?php echo $fee_affiliate; ?>;
            var coreFee = <?php echo $fee_core; ?>;
            var certificateFee = <?php echo $certificate_fee; ?>;
            
            var membershipRadios = document.querySelectorAll('input[name="membership_type"]');
            var extraCheckbox = document.getElementById('extra_fee');
            var totalSpan = document.getElementById('total-amount');
            var membershipDisplay = document.getElementById('membership-price-display');
            var feeDisplay = document.getElementById('certificate-fee-display');
            
            var baseAmount = affiliateFee;
            var membershipLabel = 'عضویت وابسته';
            
            // پیدا کردن رادیوی انتخاب شده
            for (var i = 0; i < membershipRadios.length; i++) {
                if (membershipRadios[i].checked) {
                    if (membershipRadios[i].value === 'affiliate') {
                        baseAmount = affiliateFee;
                        membershipLabel = 'عضویت وابسته';
                    } else {
                        baseAmount = coreFee;
                        membershipLabel = 'عضویت پیوسته';
                    }
                    break;
                }
            }
            
            var total = baseAmount;
            var extraText = '';
            
            if (extraCheckbox && extraCheckbox.checked) {
                total += certificateFee;
                feeDisplay.style.display = 'inline';
            } else {
                feeDisplay.style.display = 'none';
            }
            
            totalSpan.textContent = total.toLocaleString();
            membershipDisplay.textContent = membershipLabel + ': ' + baseAmount.toLocaleString() + ' تومان';
        }
        
        // اجرا در ابتدا
        document.addEventListener('DOMContentLoaded', function() {
            updateMembershipTotal();
            
            // پیش‌نمایش تصویر پرسنلی
            document.getElementById('avatar').addEventListener('change', function() {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    var preview = document.querySelector('.ci-upload-preview');
                    reader.onload = function(e) {
                        preview.innerHTML = '<img src="' + e.target.result + '" alt="پیش‌نمایش تصویر" style="max-width:150px;border-radius:8px;border:2px solid #eee;margin-top:10px;">';
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // پیش‌نمایش تصویر کارت
            document.getElementById('card_image').addEventListener('change', function() {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    var preview = document.querySelector('.ci-upload-preview-card');
                    reader.onload = function(e) {
                        preview.innerHTML = '<img src="' + e.target.result + '" alt="پیش‌نمایش کارت" style="max-width:150px;border-radius:8px;border:2px solid #eee;margin-top:10px;">';
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
	        // ============================================
        // نمایش نمونه گواهی
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            var sampleBtn = document.getElementById('ci-view-sample-certificate');
            var sampleModal = document.getElementById('ci-sample-certificate-modal');
            
            if (sampleBtn && sampleModal) {
                sampleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    sampleModal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                });
                
                // بستن با کلیک روی پس‌زمینه
                sampleModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        sampleModal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }
                });
                
                // بستن با کلید ESC
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && sampleModal.style.display === 'flex') {
                        sampleModal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }
                });
            }
        });
        </script>
        <?php
    } // پایان تابع render_form()
    
    // ============================================
    // نمایش جزئیات درخواست
    // ============================================
    private function render_view() {
        $request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $user_id = get_current_user_id();
        $request = $this->data_manager->get_request($request_id);
        
        if (!$request || $request['user_id'] != $user_id) {
            ?>
            <div class="ci-user-dashboard">
                <div class="ci-error">
                    ❌
                    <p>درخواست مورد نظر یافت نشد.</p>
                    <a href="?certificate-issuer=1" class="ci-btn ci-btn-secondary">⬅️ بازگشت به لیست</a>
                </div>
            </div>
            <?php
            return;
        }
        ?>
        <div class="ci-user-dashboard">
            <div class="ci-header">
                <h2>📄 جزئیات درخواست #<?php echo $request['id']; ?></h2>
                <a href="?certificate-issuer=1" class="ci-btn ci-btn-secondary">⬅️ بازگشت به لیست</a>
            </div>
            
            <div class="ci-detail-card">
                <div class="ci-detail-header">
                    <div class="ci-detail-avatar">
                        <?php if (!empty($request['avatar'])): ?>
                            <img src="<?php echo CI_UPLOADS_URL . $request['avatar']; ?>" alt="تصویر کاربر">
                        <?php else: ?>
                            <div class="ci-no-avatar">بدون تصویر</div>
                        <?php endif; ?>
                    </div>
                    <div class="ci-detail-info">
                        <h3><?php echo esc_html($request['first_name'] . ' ' . $request['last_name']); ?></h3>
                        <p><strong>کد ملی:</strong> <?php echo esc_html($request['national_code']); ?></p>
                        <p><strong>وضعیت:</strong> <span class="ci-status <?php echo $request['status']; ?>"><?php echo $this->get_status_label($request['status']); ?></span></p>
                    </div>
                </div>
                
                <div class="ci-detail-body">
                    <div class="ci-detail-row">
                        <div class="ci-detail-item">
                            <label>شماره تماس</label>
                            <p><?php echo esc_html($request['phone']); ?></p>
                        </div>
                      <div class="ci-detail-item">
    <label>تاریخ تولد</label>
    <p>
        <?php 
        if (isset($request['birth_date']) && !empty($request['birth_date'])) {
            $date = str_replace('-', '/', $request['birth_date']);
            echo esc_html($date);
        } else {
            echo '-';
        }
        ?>
    </p>
</div>
                    </div>
                    
                    <div class="ci-detail-row">
                        <div class="ci-detail-item">
                            <label>نام پدر</label>
                            <p><?php echo esc_html($request['father_name']); ?></p>
                        </div>
                        <div class="ci-detail-item">
                            <label>جنسیت</label>
                            <p><?php echo esc_html($request['gender'] ?? '-'); ?></p>
                        </div>
                    </div>
                    
                    <div class="ci-detail-row">
                        <div class="ci-detail-item">
                            <label>محل تولد</label>
                            <p><?php echo esc_html($request['birth_place'] ?? '-'); ?></p>
                        </div>
                        <div class="ci-detail-item">
                            <label>شماره نظام پزشکی</label>
                            <p><?php echo esc_html($request['medical_id'] ?? '-'); ?></p>
                        </div>
                    </div>
                    
                    <div class="ci-detail-row">
                        <div class="ci-detail-item">
                            <label>شماره دانشجویی</label>
                            <p><?php echo esc_html($request['student_id'] ?? '-'); ?></p>
                        </div>
                        <div class="ci-detail-item">
                            <label>رشته تحصیلی</label>
                            <p><?php echo esc_html($request['field_of_study'] ?? '-'); ?></p>
                        </div>
                    </div>
                    
                    <div class="ci-detail-row">
                        <div class="ci-detail-item">
                            <label>آخرین مدرک تحصیلی</label>
                            <p><?php echo esc_html($request['last_degree'] ?? '-'); ?></p>
                        </div>
                        <div class="ci-detail-item">
                            <label>آخرین دانشگاه</label>
                            <p><?php echo esc_html($request['last_university'] ?? '-'); ?></p>
                        </div>
                    </div>
                    
                    <div class="ci-detail-item ci-full-width">
                        <label>زمینه فعالیت</label>
                        <p><?php echo esc_html($request['activity_field'] ?? '-'); ?></p>
                    </div>
                    
                    <?php if (!empty($request['card_image'])): ?>
                        <div class="ci-detail-item ci-full-width">
                            <label>تصویر کارت</label>
                            <div>
                                <img src="<?php echo CI_UPLOADS_URL . $request['card_image']; ?>" style="max-width:200px;border-radius:8px;border:2px solid #e0e0e0;">
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="ci-detail-row">
                        <div class="ci-detail-item">
                            <label>مبلغ پرداختی</label>
                            <p><?php echo number_format($request['amount']); ?> تومان</p>
                        </div>
                        <div class="ci-detail-item">
                            <label>تاریخ ثبت</label>
                            <p><?php echo date_i18n('Y/m/d H:i', strtotime($request['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($request['status'] === 'approved'): ?>
                        <div class="ci-detail-row">
                            <div class="ci-detail-item">
                                <label>تاریخ صدور</label>
                                <p><?php echo date_i18n('Y/m/d', strtotime($request['issue_date'])); ?></p>
                            </div>
                            <div class="ci-detail-item">
                                <label>تاریخ انقضا</label>
                                <p><?php echo date_i18n('Y/m/d', strtotime($request['expiry_date'])); ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($request['certificate_file'])): ?>
                            <div class="ci-detail-item ci-full-width">
                                <label>گواهی صادر شده</label>
                                <div class="ci-certificate-preview">
                                    <a href="<?php echo $this->get_certificate_url($request['id']); ?>" class="ci-btn ci-btn-sm ci-btn-success" target="_blank">📥 دانلود گواهی</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if ($request['status'] === 'pending_payment'): ?>
                        <div class="ci-detail-item ci-full-width ci-payment-section">
                            <button class="ci-btn ci-btn-primary ci-btn-large ci-btn-pay" data-request-id="<?php echo $request['id']; ?>">💳 پرداخت مجدد</button>
                            <p class="ci-note">پرداخت شما ناموفق بوده است. لطفا مجددا اقدام به پرداخت نمایید.</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($request['admin_notes'])): ?>
                        <div class="ci-detail-item ci-full-width">
                            <label>یادداشت ادمین</label>
                            <p class="ci-admin-note"><?php echo esc_html($request['admin_notes']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    // ============================================
    // پردازش فرم (نسخه جدید)
    // ============================================
   private function handle_form_submission() {
    // ============================================
    // ✅ جلوگیری از ارسال همزمان (با Session)
    // ============================================
   // ============================================
// ✅ جلوگیری از ارسال همزمان (با Transient وردپرس)
// ============================================
$user_id = get_current_user_id();
$transient_key = 'ci_last_submit_' . $user_id;
$last_submit = get_transient($transient_key);

if ($last_submit && (time() - $last_submit) < 3) {
    error_log('[CI DEBUG] ❌ ارسال همزمان - جلوگیری شد');
    wp_die('لطفاً چند لحظه صبر کنید و دوباره تلاش کنید.');
}

set_transient($transient_key, time(), 5);
    
    error_log('[CI DEBUG] ====== START handle_form_submission ======');
    error_log('[CI DEBUG] POST data: ' . print_r($_POST, true));
    
    if (!wp_verify_nonce($_POST['ci_nonce'], 'ci_submit_request')) {
        wp_die('اعتبار سنجی ناموفق');
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_die('شما باید وارد سیستم شوید');
    }
    
    // ============================================
    // ترکیب تاریخ تولد
    // ============================================
    $birth_day = isset($_POST['birth_day']) ? trim($_POST['birth_day']) : '';
    $birth_month = isset($_POST['birth_month']) ? trim($_POST['birth_month']) : '';
    $birth_year = isset($_POST['birth_year']) ? trim($_POST['birth_year']) : '';
    
    $birth_day = $this->convert_to_english_number($birth_day);
    $birth_month = $this->convert_to_english_number($birth_month);
    $birth_year = $this->convert_to_english_number($birth_year);
    
    $errors = [];
    $birth_date = $birth_year . '-' . str_pad($birth_month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($birth_day, 2, '0', STR_PAD_LEFT);
    // ============================================
    // اعتبارسنجی فیلدهای جدید
    // ============================================  

    
    if (!empty($errors)) {
        echo '<div class="ci-error-box">';
        foreach ($errors as $error) {
            echo '<p>❌ ' . esc_html($error) . '</p>';
        }
        echo '</div>';
        $this->render_form();
        return;
    }
    
    $birth_date = $birth_year . '-' . str_pad($birth_month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($birth_day, 2, '0', STR_PAD_LEFT);
    
    // آپلود تصاویر
    $upload_result = $this->upload_user_image($_FILES['avatar'], $user_id, 'avatar');
    if (!$upload_result['success']) {
        echo '<div class="ci-error-box"><p>❌ ' . esc_html($upload_result['message']) . '</p></div>';
        $this->render_form();
        return;
    }
    
    $card_upload_result = $this->upload_user_image($_FILES['card_image'], $user_id, 'card');
    if (!$card_upload_result['success']) {
        echo '<div class="ci-error-box"><p>❌ ' . esc_html($card_upload_result['message']) . '</p></div>';
        $this->render_form();
        return;
    }
    
    // ============================================
    // محاسبه مبلغ بر اساس نوع عضویت
    // ============================================
    $settings = $this->data_manager->get_settings();
    $membership_type = $_POST['membership_type'] ?? 'affiliate';
    
    if ($membership_type === 'affiliate') {
        $amount = intval($settings['fee_affiliate'] ?? 100000);
    } else {
        $amount = intval($settings['fee_core'] ?? 300000);
    }
    
    // اضافه کردن مبلغ گواهی
    $extra_fee_value = isset($_POST['extra_fee']) && $_POST['extra_fee'] === 'on';
    if ($extra_fee_value) {
        $amount += intval($settings['certificate_fee'] ?? 30000);
    }
    
    // ============================================
    // ✅ اعمال کد تخفیف (قسمت جدید)
    // ============================================
    $discount_code = sanitize_text_field($_POST['discount_code'] ?? '');
    $saved_code = $settings['discount_code'] ?? '';
    $discount_percent = floatval($settings['discount_percent'] ?? 0);
    
    $discount_applied = false;
    if (!empty($discount_code) && !empty($saved_code) && 
        strcasecmp($discount_code, $saved_code) === 0 && $discount_percent > 0) {
        $discount_amount = $amount * ($discount_percent / 100);
        $amount = $amount - $discount_amount;
        $discount_applied = true;
    }
    
    error_log('[CI DEBUG] 📌 discount_code: ' . $discount_code);
    error_log('[CI DEBUG] 📌 discount_applied: ' . ($discount_applied ? 'true' : 'false'));
    error_log('[CI DEBUG] 📌 final amount: ' . $amount);
    
    // ============================================
    // ایجاد درخواست با فیلدهای جدید
    // ============================================
    $request_data = [
        'first_name' => sanitize_text_field($_POST['first_name']),
        'last_name' => sanitize_text_field($_POST['last_name']),
        'phone' => sanitize_text_field($_POST['phone']),
        'birth_date' => $birth_date,
        'national_code' => sanitize_text_field($_POST['national_code']),
        'father_name' => sanitize_text_field($_POST['father_name']),
        'gender' => sanitize_text_field($_POST['gender']),
        'birth_place' => sanitize_text_field($_POST['birth_place']),
        'medical_id' => sanitize_text_field($_POST['medical_id'] ?? ''),
        'student_id' => sanitize_text_field($_POST['student_id'] ?? ''),
        'field_of_study' => sanitize_text_field($_POST['field_of_study']),
        'last_degree' => sanitize_text_field($_POST['last_degree']),
        'last_university' => sanitize_text_field($_POST['last_university']),
        'activity_field' => sanitize_textarea_field($_POST['activity_field']),
        'membership_type' => sanitize_text_field($membership_type),
        'avatar' => $upload_result['filename'],
        'card_image' => $card_upload_result['filename'],
        'amount' => $amount,
        'extra_fee' => $extra_fee_value,
        // ============================================
        // ✅ ذخیره کد تخفیف و درصد (قسمت جدید)
        // ============================================
        'discount_code' => $discount_applied ? $discount_code : '',
        'discount_percent' => $discount_applied ? $discount_percent : 0
    ];
    
    error_log('[CI DEBUG] 📌 request_data: ' . print_r($request_data, true));
    error_log('[CI DEBUG] ====== END handle_form_submission ======');
    
    $request_id = $this->data_manager->create_request($user_id, $request_data);

    if (!$request_id) {
        echo '<div class="ci-error-box"><p>❌ خطا در ثبت درخواست</p></div>';
        $this->render_form();
        return;
    }
    
    // ============================================
    // بررسی مبلغ نهایی
    // ============================================
    if ($amount > 0) {
        // ارسال به درگاه پرداخت
        $gateway = new CI_Zibal_Gateway();
        $result = $gateway->request_payment($request_id, $amount);
        if ($result['status'] === 'success') {
            // ذخیره track_id
            $this->data_manager->update_request($request_id, [
                'transaction_id' => $result['track_id']
            ]);
            
            // ریدایرکت به درگاه
            echo '<script>window.location.href = "' . esc_url($result['redirect_url']) . '";</script>';
            exit;
        } else {
            echo '<div class="ci-error-box"><p>❌ ' . esc_html($result['message']) . '</p></div>';
            $this->render_form();
        }
    } else {
        // ============================================
        // ✅ مبلغ 0 است (تخفیف 100%) - پرداخت رایگان
        // ============================================
        $this->data_manager->update_request($request_id, [
            'status' => 'pending',
            'payment_date' => current_time('mysql'),
            'payment_method' => 'free'
        ]);
        
        // ارسال پیامک به کاربر و مدیر
        if (class_exists('CI_SMS_Handler')) {
            $sms = new CI_SMS_Handler();
            $sms->send_registration_sms_full($request['phone'], $request_id);
        }
        
        echo '<div class="ci-success-box">';
        echo '<p>✅ درخواست شما با موفقیت ثبت شد. به دلیل تخفیف 100%، نیازی به پرداخت نیست.</p>';
        echo '<p>درخواست شما در انتظار تایید ادمین می‌باشد.</p>';
        echo '</div>';
        echo '<script>setTimeout(function(){ window.location.href = "' . wc_get_account_endpoint_url('certificate-issuer') . '"; }, 3000);</script>';
        exit;
    }

}
    
    // ============================================
    // توابع کمکی
    // ============================================
    private function convert_to_english_number($string) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($persian, $english, $string);
    }
    
    private function upload_user_image($file, $user_id, $prefix = 'avatar') {
        $filename = $prefix . '_' . $user_id . '_' . time() . '_' . sanitize_file_name($file['name']);
        $target_path = CI_UPLOADS_DIR . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            return [
                'success' => true,
                'filename' => $filename
            ];
        } else {
            return [
                'success' => false,
                'message' => 'خطا در آپلود تصویر'
            ];
        }
    }
    
    private function get_status_label($status) {
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
    
    private function get_field_label($field) {
        $labels = [
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'phone' => 'شماره تماس',
            'birth_date' => 'تاریخ تولد',
            'national_code' => 'کد ملی',
            'father_name' => 'نام پدر',
            'gender' => 'جنسیت',
            'birth_place' => 'محل تولد',
            'field_of_study' => 'رشته تحصیلی',
            'last_degree' => 'آخرین مدرک تحصیلی',
            'last_university' => 'آخرین دانشگاه',
            'activity_field' => 'زمینه فعالیت',
            'membership_type' => 'نوع عضویت'
        ];
        return isset($labels[$field]) ? $labels[$field] : $field;
    }
    
    private function show_status_messages($status) {
        if ($status === 'payment_success') {
            echo '<div class="ci-success-box">✅ <p>پرداخت شما با موفقیت انجام شد...</p></div>';
        } elseif ($status === 'payment_failed') {
            echo '<div class="ci-error-box">❌ <p>پرداخت ناموفق بود...</p></div>';
        } elseif ($status === 'payment_cancelled') {
            echo '<div class="ci-warning-box">⚠️ <p>پرداخت توسط شما لغو شد...</p></div>';
        }
    }
    
    private function get_certificate_url($request_id) {
        $request = $this->data_manager->get_request($request_id);
        if ($request && !empty($request['certificate_file'])) {
            return CI_CERTIFICATES_URL . $request['certificate_file'];
        }
        return '#';
    }
}