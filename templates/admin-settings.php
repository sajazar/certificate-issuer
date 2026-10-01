<div class="ci-admin-settings">
    <h1>⚙️ تنظیمات صدور گواهی</h1>
    
    <?php $settings = $this->data_manager->get_settings(); ?>
    
    <form method="post" class="ci-settings-form">
        <?php wp_nonce_field('ci_save_settings', 'ci_settings_nonce'); ?>
        
        <!-- ============================================ -->
        <!-- 💰 تنظیمات مالی (جدید) -->
        <!-- ============================================ -->
        <div class="ci-settings-section">
            <h2>💰 تنظیمات مالی</h2>
            
            <div class="ci-form-row">
                <label>مبلغ عضویت وابسته (تومان)</label>
                <input type="number" name="fee_affiliate" 
                       value="<?php echo $settings['fee_affiliate'] ?? 100000; ?>" required>
                <small>دانشجویان مقطع کارشناسی - افراد با مدرک دکتری عمومی</small>
            </div>
            
            <div class="ci-form-row">
                <label>مبلغ عضویت پیوسته (تومان)</label>
                <input type="number" name="fee_core" 
                       value="<?php echo $settings['fee_core'] ?? 300000; ?>" required>
                <small>سایر افراد</small>
            </div>
            
            <div class="ci-form-row">
                <label>مبلغ صدور گواهی (تومان)</label>
                <input type="number" name="certificate_fee" 
                       value="<?php echo $settings['certificate_fee'] ?? 30000; ?>" required>
                <small>مبلغی که در صورت انتخاب گزینه اضافه‌مبلغ به کاربر اضافه می‌شود</small>
            </div>
        </div>
<!-- ============================================ -->
<!-- 🎫 تنظیمات کد تخفیف -->
<!-- ============================================ -->
<div class="ci-settings-section">
    <h2>🎫 تنظیمات کد تخفیف</h2>
    
    <div style="background:#fff3cd;padding:12px 16px;border-radius:8px;margin-bottom:15px;border-right:4px solid #ffc107;">
        <p style="margin:0;font-size:13px;color:#856404;">
            💡 کاربران با وارد کردن کد تخفیف در فرم ثبت‌نام، از تخفیف درصدی برخوردار می‌شوند.
        </p>
    </div>
    
    <div class="ci-form-row">
        <label>کد تخفیف</label>
        <input type="text" name="discount_code" 
               value="<?php echo esc_attr($settings['discount_code'] ?? ''); ?>" 
               placeholder="مثلاً SALE10">
        <small>کدی که کاربران باید در فرم وارد کنند (حساس به حروف بزرگ و کوچک نیست)</small>
    </div>
    
    <div class="ci-form-row">
        <label>درصد تخفیف (%)</label>
        <input type="number" name="discount_percent" 
               value="<?php echo $settings['discount_percent'] ?? 0; ?>" 
               min="0" max="100" step="1">
        <small>درصد تخفیفی که از مبلغ نهایی کسر می‌شود (مثلاً 10 = 10%)</small>
    </div>
</div>
 <!-- ============================================ -->
<!-- 🎁 تنظیمات تخفیف‌ها -->
<!-- ============================================ -->
<div class="ci-settings-section">
    <h2>🎁 تنظیمات تخفیف‌ها</h2>
    
    <div style="background:#f0f7ff;padding:12px 16px;border-radius:8px;margin-bottom:15px;border-right:4px solid #2196F3;">
        <p style="margin:0;font-size:13px;color:#555;">
            💡 <strong>تخفیف‌ها به صورت خودکار در سبد خرید اعمال می‌شوند.</strong>
            کاربران با عضویت تایید شده از تخفیف مربوطه برخوردار می‌شوند.
        </p>
    </div>
    
    <div class="ci-form-row">
        <label>تخفیف اعضای پیوسته (%)</label>
        <input type="number" name="discount_core" 
               value="<?php echo $settings['discount_core'] ?? 0; ?>" 
               min="0" max="100">
        <small>درصد تخفیف برای کاربرانی که عضویت پیوسته دارند</small>
    </div>
    
    <div class="ci-form-row">
        <label>تخفیف اعضای وابسته (%)</label>
        <input type="number" name="discount_affiliate" 
               value="<?php echo $settings['discount_affiliate'] ?? 0; ?>" 
               min="0" max="100">
        <small>درصد تخفیف برای کاربرانی که عضویت وابسته دارند</small>
    </div>
    
    <div class="ci-form-row">
        <label>تخفیف دارندگان گواهی (%)</label>
        <input type="number" name="discount_certificate" 
               value="<?php echo $settings['discount_certificate'] ?? 0; ?>" 
               min="0" max="100">
        <small>درصد تخفیف برای کاربرانی که گواهی تایید شده دارند</small>
    </div>
</div>
        <!-- ============================================ -->
        <!-- 💳 تنظیمات درگاه زیبال -->
        <!-- ============================================ -->
        <div class="ci-settings-section">
            <h2>💳 تنظیمات درگاه زیبال</h2>
            
            <div class="ci-form-row">
                <label>مرچنت کد</label>
                <input type="text" name="zibal_merchant" 
                       value="<?php echo esc_attr($settings['zibal_merchant']); ?>" 
                       placeholder="zibal">
            </div>
            
            <div class="ci-form-row">
                <label>
                    <input type="checkbox" name="zibal_sandbox" 
                           <?php echo isset($settings['zibal_sandbox']) && $settings['zibal_sandbox'] ? 'checked' : ''; ?>>
                    حالت تست (Sandbox)
                </label>
            </div>
        </div>
        
        <!-- ============================================ -->
        <!-- 📱 تنظیمات پیامک (ملی پیامک) -->
        <!-- ============================================ -->
        <div class="ci-settings-section">
            <h2>📱 تنظیمات پیامک (ملی پیامک)</h2>
            
            <h3 style="margin:15px 0 10px 0;font-size:15px;color:#1d2327;">
                🔐 روش SOAP (Web Service)
                <small style="font-weight:normal;color:#666;font-size:12px;">— روش اصلی و تست‌شده</small>
            </h3>
            
            <div class="ci-form-row">
                <label>نام کاربری</label>
                <input type="text" name="sms_username" 
                       value="<?php echo esc_attr($settings['sms_username'] ?? ''); ?>">
            </div>
            
            <div class="ci-form-row">
                <label>رمز عبور</label>
                <input type="password" name="sms_password" 
                       value="<?php echo esc_attr($settings['sms_password'] ?? ''); ?>">
            </div>
            
            <div class="ci-form-row">
                <label>شماره فرستنده</label>
                <input type="text" name="sms_sender" 
                       value="<?php echo esc_attr($settings['sms_sender'] ?? ''); ?>">
            </div>
            
            <hr style="margin:20px 0;border:0;border-top:1px dashed #ddd;">
            
            <h3 style="margin:15px 0 10px 0;font-size:15px;color:#1d2327;">
                🔑 روش REST API
                <small style="font-weight:normal;color:#666;font-size:12px;">— روش جدید و سریع‌تر</small>
            </h3>
            
            <div class="ci-form-row">
                <label>API Key</label>
                <input type="text" name="sms_api_key" 
                       value="<?php echo esc_attr($settings['sms_api_key'] ?? ''); ?>">
            </div>
            
            <hr style="margin:20px 0;border:0;border-top:1px dashed #ddd;">
            
            <h3 style="margin:15px 0 10px 0;font-size:15px;color:#1d2327;">
                📝 کدهای الگوی پیامک
            </h3>
            
           <div class="ci-form-row">
    <label>کد الگوی تایید کاربر</label>
    <input type="text" name="sms_approve_user" 
           value="<?php echo esc_attr($settings['sms_templates']['approve_user'] ?? ''); ?>"
           placeholder="مثلاً 12346">
    <small>زمانی که درخواست کاربر تایید می‌شود</small>
</div>

<div class="ci-form-row">
    <label>کد الگوی صدور گواهی</label>
    <input type="text" name="sms_issue_certificate" 
           value="<?php echo esc_attr($settings['sms_templates']['issue_certificate'] ?? ''); ?>"
           placeholder="مثلاً 12349">
    <small>زمانی که گواهی صادر و بارگذاری می‌شود</small>
</div>

<div class="ci-form-row">
    <label>کد الگوی صدور گواهی</label>
    <input type="text" name="sms_issue_certificate" 
           value="<?php echo esc_attr($settings['sms_templates']['issue_certificate'] ?? ''); ?>"
           placeholder="مثلاً 12349">
    <small>زمانی که گواهی صادر و بارگذاری می‌شود</small>
</div>
            
            <div class="ci-form-row">
                <label>کد الگوی رد گواهی</label>
                <input type="text" name="sms_reject" 
                       value="<?php echo esc_attr($settings['sms_templates']['reject'] ?? ''); ?>">
            </div>
            
            <div class="ci-form-row">
                <label>کد الگوی تمدید گواهی</label>
                <input type="text" name="sms_renew" 
                       value="<?php echo esc_attr($settings['sms_templates']['renew'] ?? ''); ?>">
            </div>
            
            <!-- ============================================ -->
            <!-- 👤 تنظیمات پیامک به مدیر -->
            <!-- ============================================ -->
            <h3 style="margin:25px 0 15px 0;font-size:15px;color:#1d2327;">👤 تنظیمات پیامک به مدیر</h3>
            
            <div class="ci-form-row">
                <label>شماره مدیر (دریافت پیامک)</label>
                <input type="text" name="admin_phone" 
                       value="<?php echo esc_attr($settings['admin_phone'] ?? ''); ?>">
            </div>
            
            <div class="ci-form-row">
                <label>کد الگوی پیامک به مدیر</label>
                <input type="text" name="admin_template_id" 
                       value="<?php echo esc_attr($settings['admin_template_id'] ?? ''); ?>">
            </div>
        </div>
        
        <!-- ============================================ -->
        <!-- 📅 تنظیمات اعتبار گواهی -->
        <!-- ============================================ -->
        <div class="ci-settings-section">
            <h2>📅 تنظیمات اعتبار گواهی</h2>
            
            <div class="ci-form-row">
                <label>مدت اعتبار (ماه)</label>
                <input type="number" name="certificate_validity_months" 
                       value="<?php echo $settings['certificate_validity_months'] ?? 12; ?>" 
                       min="1" max="60">
            </div>
        </div>
        
        <!-- ============================================ -->
        <!-- دکمه ذخیره -->
        <!-- ============================================ -->
        <div class="ci-form-actions">
            <button type="submit" name="ci_save_settings" class="button button-primary">
                💾 ذخیره تنظیمات
            </button>
        </div>
    </form>
</div>