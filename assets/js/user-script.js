/**
 * Certificate Issuer - User Script
 * بخش کاربری افزونه صدور گواهی
 */

(function($) {
    'use strict';

    // ============================================
    // بارگذاری Font Awesome (در صورت عدم وجود)
    // ============================================
    function loadFontAwesome() {
        if (!document.querySelector('link[href*="font-awesome"]')) {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
            link.crossOrigin = 'anonymous';
            document.head.appendChild(link);
        }
    }

    // ============================================
    // جایگزینی آیکن‌ها با ایموجی در صورت عدم نمایش
    // ============================================
    function fixIcons() {
        // آیکن‌های دکمه‌ها
        $('.ci-btn .fa-plus').each(function() {
            if ($(this).css('font-family').indexOf('Font Awesome') === -1) {
                $(this).html('➕');
            }
        });

        $('.ci-btn .fa-arrow-right').each(function() {
            if ($(this).css('font-family').indexOf('Font Awesome') === -1) {
                $(this).html('➡️');
            }
        });

        $('.ci-btn .fa-eye').each(function() {
            if ($(this).css('font-family').indexOf('Font Awesome') === -1) {
                $(this).html('👁️');
            }
        });

        $('.ci-btn .fa-download').each(function() {
            if ($(this).css('font-family').indexOf('Font Awesome') === -1) {
                $(this).html('📥');
            }
        });

        $('.ci-btn .fa-credit-card').each(function() {
            if ($(this).css('font-family').indexOf('Font Awesome') === -1) {
                $(this).html('💳');
            }
        });

        $('.ci-btn .fa-check').each(function() {
            if ($(this).css('font-family').indexOf('Font Awesome') === -1) {
                $(this).html('✅');
            }
        });

        // آیکن‌های وضعیت
        $('.ci-status.approved').each(function() {
            if (!$(this).text().includes('✅')) {
                $(this).prepend('✅ ');
            }
        });

        $('.ci-status.pending').each(function() {
            if (!$(this).text().includes('⏳')) {
                $(this).prepend('⏳ ');
            }
        });

        $('.ci-status.pending_payment').each(function() {
            if (!$(this).text().includes('💳')) {
                $(this).prepend('💳 ');
            }
        });

        $('.ci-status.rejected').each(function() {
            if (!$(this).text().includes('❌')) {
                $(this).prepend('❌ ');
            }
        });

        $('.ci-status.expired').each(function() {
            if (!$(this).text().includes('⏰')) {
                $(this).prepend('⏰ ');
            }
        });

        $('.ci-status.cancelled').each(function() {
            if (!$(this).text().includes('🚫')) {
                $(this).prepend('🚫 ');
            }
        });

        // آیکن‌های پیام‌ها
        $('.ci-success-box .fa-check-circle').each(function() {
            if ($(this).css('font-family').indexOf('Font Awesome') === -1) {
                $(this).html('✅');
            }
        });

        $('.ci-error-box .fa-circle-xmark').each(function() {
            if ($(this).css('font-family').indexOf('Font Awesome') === -1) {
                $(this).html('❌');
            }
        });

        $('.ci-warning-box .fa-triangle-exclamation').each(function() {
            if ($(this).css('font-family').indexOf('Font Awesome') === -1) {
                $(this).html('⚠️');
            }
        });

        // آیکن حالت خالی
        $('.ci-empty-state .fa-file-certificate').each(function() {
            if ($(this).css('font-family').indexOf('Font Awesome') === -1) {
                $(this).html('📜');
            }
        });

        // آیکن بازگشت
        $('.ci-btn-secondary .fa-arrow-right').each(function() {
            if ($(this).css('font-family').indexOf('Font Awesome') === -1) {
                $(this).html('➡️');
            }
        });
    }

    // ============================================
    // اجرا در زمان بارگذاری
    // ============================================
    $(document).ready(function() {
        loadFontAwesome();
        // تاخیر برای اطمینان از بارگذاری فونت
        setTimeout(fixIcons, 500);
        setTimeout(fixIcons, 1000);
    });

    // ============================================
    // اجرا بعد از تغییرات DOM
    // ============================================
    $(document).on('ajaxComplete', function() {
        setTimeout(fixIcons, 300);
    });

    // ============================================
    // پیش‌نمایش تصویر آپلود شده
    // ============================================
    $(document).on('change', '#avatar', function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            var preview = $('.ci-upload-preview');
            
            reader.onload = function(e) {
                preview.html('<img src="' + e.target.result + '" alt="پیش‌نمایش تصویر" style="max-width:150px;border-radius:8px;border:2px solid #eee;margin-top:10px;">');
            };
            reader.readAsDataURL(file);
        }
    });

    // ============================================
    // محاسبه مبلغ با تیک اضافه‌مبلغ
    // ============================================
    $(document).on('change', '#extra_fee', function() {
        var baseAmount = parseInt($('#total-amount').data('base')) || 0;
        var extraFee = parseInt($('#total-amount').data('extra')) || 0;
        var total = baseAmount;
        
        if (this.checked) {
            total += extraFee;
            $('#certificate-fee-display').show();
        } else {
            $('#certificate-fee-display').hide();
        }
        
        $('#total-amount').text(total.toLocaleString());
    });

    // ============================================
    // پرداخت مجدد
    // ============================================
    $(document).on('click', '.ci-btn-pay', function() {
        var button = $(this);
        var requestId = button.data('request-id');
        
        button.prop('disabled', true).html('⏳ در حال پردازش...');
        
        $.ajax({
            url: ci_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ci_retry_payment',
                request_id: requestId,
                nonce: ci_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect_url;
                } else {
                    alert('❌ خطا: ' + response.data.message);
                    button.prop('disabled', false).html('💳 پرداخت مجدد');
                }
            },
            error: function() {
                alert('❌ خطا در ارتباط با سرور');
                button.prop('disabled', false).html('💳 پرداخت مجدد');
            }
        });
    });

    // ============================================
    // اعتبارسنجی فرم
    // ============================================
    $('#ci-certificate-form').on('submit', function(e) {
        var isValid = true;
        var errors = [];
        
        // اعتبارسنجی کد ملی
        var nationalCode = $('#national_code').val();
        if (!validateNationalCode(nationalCode)) {
            errors.push('❌ کد ملی نامعتبر است');
            isValid = false;
        }
        
        // اعتبارسنجی شماره تلفن
        var phone = $('#phone').val();
        if (!validatePhone(phone)) {
            errors.push('❌ شماره تلفن نامعتبر است (باید با 09 شروع شود)');
            isValid = false;
        }
        
        // اعتبارسنجی ایمیل
        var email = $('#email').val();
        if (!validateEmail(email)) {
            errors.push('❌ ایمیل نامعتبر است');
            isValid = false;
        }
        
        // اعتبارسنجی تاریخ تولد
        var birthDay = $('#birth_day').val();
        var birthMonth = $('#birth_month').val();
        var birthYear = $('#birth_year').val();
        
        if (!birthDay || !birthMonth || !birthYear) {
            errors.push('❌ تاریخ تولد الزامی است');
            isValid = false;
        } else {
            var day = parseInt(birthDay);
            var month = parseInt(birthMonth);
            var year = parseInt(birthYear);
            
            if (day < 1 || day > 31) {
                errors.push('❌ روز تولد باید بین 1 تا 31 باشد');
                isValid = false;
            }
            if (month < 1 || month > 12) {
                errors.push('❌ ماه تولد باید بین 1 تا 12 باشد');
                isValid = false;
            }
            if (year < 1300 || year > 1500) {
                errors.push('❌ سال تولد باید بین 1300 تا 1500 باشد');
                isValid = false;
            }
        }
        
        if (!isValid) {
            e.preventDefault();
            alert('❌ لطفا خطاهای زیر را برطرف کنید:\n\n' + errors.join('\n'));
        }
    });

    // ============================================
    // توابع اعتبارسنجی
    // ============================================
    function validateNationalCode(code) {
        code = code.trim();
        if (!/^\d{10}$/.test(code)) return false;
        
        var check = parseInt(code[9]);
        var sum = 0;
        for (var i = 0; i < 9; i++) {
            sum += parseInt(code[i]) * (10 - i);
        }
        var remainder = sum % 11;
        
        if (remainder < 2) {
            return check === remainder;
        } else {
            return check === (11 - remainder);
        }
    }
    
    function validatePhone(phone) {
        return /^09[0-9]{9}$/.test(phone.trim());
    }
    
    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim());
    }

})(jQuery);