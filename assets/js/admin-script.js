jQuery(document).ready(function($) {
    'use strict';
    
    // ============================================
    // مشاهده درخواست
    // ============================================
    $('.ci-view-request').on('click', function() {
        const requestId = $(this).data('id');
        const modal = $('#ci-request-modal');
        const content = $('#ci-modal-content');
        
        content.html('<p>در حال بارگذاری...</p>');
        modal.show();
        
        $.ajax({
            url: ci_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ci_get_request_details',
                request_id: requestId,
                nonce: ci_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    content.html(response.data.html);
                } else {
                    content.html('<p class="error">خطا در بارگذاری اطلاعات</p>');
                }
            },
            error: function() {
                content.html('<p class="error">خطا در ارتباط با سرور</p>');
            }
        });
    });
    
    // ============================================
    // تایید درخواست
    // ============================================
    $('.ci-approve-request').on('click', function() {
        const button = $(this);
        const requestId = button.data('id');
        
        if (!confirm('آیا از تایید این درخواست مطمئن هستید؟')) {
            return;
        }
        
        button.prop('disabled', true).text('در حال تایید...');
        
        $.ajax({
            url: ci_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ci_approve_request',
                request_id: requestId,
                nonce: ci_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ درخواست با موفقیت تایید شد');
                    location.reload();
                } else {
                    alert('❌ خطا: ' + response.data.message);
                    button.prop('disabled', false).text('تایید');
                }
            },
            error: function() {
                alert('❌ خطا در ارتباط با سرور');
                button.prop('disabled', false).text('تایید');
            }
        });
    });
    
    // ============================================
    // حذف گواهی (صدور مجدد)
    // ============================================
    $(document).on('click', '.ci-remove-certificate', function() {
        var button = $(this);
        var requestId = button.data('id');
        
        if (!confirm('⚠️ آیا از حذف گواهی فعلی و بازگشت به وضعیت "در انتظار تایید" مطمئن هستید؟')) {
            return;
        }
        
        button.prop('disabled', true).text('⏳ در حال حذف...');
        
        $.ajax({
            url: ci_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ci_remove_certificate',
                request_id: requestId,
                nonce: ci_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ ' + response.data.message);
                    location.reload();
                } else {
                    alert('❌ خطا: ' + response.data.message);
                    button.prop('disabled', false).html('🔄 حذف و صدور مجدد');
                }
            },
            error: function() {
                alert('❌ خطا در ارتباط با سرور');
                button.prop('disabled', false).html('🔄 حذف و صدور مجدد');
            }
        });
    });
    
    // ============================================
    // رد درخواست
    // ============================================
    $('.ci-reject-request').on('click', function() {
        const button = $(this);
        const requestId = button.data('id');
        
        const reason = prompt('لطفا دلیل رد درخواست را وارد کنید:');
        if (reason === null) return;
        
        if (!confirm('آیا از رد این درخواست مطمئن هستید؟')) {
            return;
        }
        
        button.prop('disabled', true).text('در حال رد...');
        
        $.ajax({
            url: ci_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ci_reject_request',
                request_id: requestId,
                reason: reason,
                nonce: ci_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ درخواست با موفقیت رد شد');
                    location.reload();
                } else {
                    alert('❌ خطا: ' + response.data.message);
                    button.prop('disabled', false).text('رد');
                }
            },
            error: function() {
                alert('❌ خطا در ارتباط با سرور');
                button.prop('disabled', false).text('رد');
            }
        });
    });
    
    // ============================================
    // بارگذاری گواهی
    // ============================================
    $('.ci-upload-certificate').on('click', function() {
        const requestId = $(this).data('id');
        $('#ci-upload-request-id').val(requestId);
        $('#ci-upload-modal').show();
    });
    
    $('#ci-upload-certificate-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'ci_upload_certificate');
        formData.append('nonce', ci_admin_ajax.nonce);
        
        const button = $(this).find('button[type="submit"]');
        button.prop('disabled', true).text('در حال بارگذاری...');
        
        $.ajax({
            url: ci_admin_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('✅ گواهی با موفقیت بارگذاری شد');
                    location.reload();
                } else {
                    alert('❌ خطا: ' + response.data.message);
                    button.prop('disabled', false).text('بارگذاری');
                }
            },
            error: function() {
                alert('❌ خطا در ارتباط با سرور');
                button.prop('disabled', false).text('بارگذاری');
            }
        });
    });
    
    // ============================================
    // تمدید درخواست
    // ============================================
    $('.ci-renew-request').on('click', function() {
        const button = $(this);
        const requestId = button.data('id');
        
        if (!confirm('آیا از تمدید گواهی این کاربر مطمئن هستید؟')) {
            return;
        }
        
        button.prop('disabled', true).text('در حال تمدید...');
        
        $.ajax({
            url: ci_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ci_renew_request',
                request_id: requestId,
                nonce: ci_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ گواهی با موفقیت تمدید شد');
                    location.reload();
                } else {
                    alert('❌ خطا: ' + response.data.message);
                    button.prop('disabled', false).text('تمدید');
                }
            },
            error: function() {
                alert('❌ خطا در ارتباط با سرور');
                button.prop('disabled', false).text('تمدید');
            }
        });
    });
    
    // ============================================
    // حذف درخواست
    // ============================================
    $('.ci-delete-request').on('click', function() {
        const button = $(this);
        const requestId = button.data('id');
        
        if (!confirm('⚠️ آیا از حذف این درخواست مطمئن هستید؟ این اقدام غیرقابل بازگشت است.')) {
            return;
        }
        
        button.prop('disabled', true).text('در حال حذف...');
        
        $.ajax({
            url: ci_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ci_delete_request',
                request_id: requestId,
                nonce: ci_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ درخواست با موفقیت حذف شد');
                    location.reload();
                } else {
                    alert('❌ خطا: ' + response.data.message);
                    button.prop('disabled', false).text('حذف');
                }
            },
            error: function() {
                alert('❌ خطا در ارتباط با سرور');
                button.prop('disabled', false).text('حذف');
            }
        });
    });
    
    // ============================================
    // حذف پرداخت
    // ============================================
    $('.ci-delete-payment').on('click', function() {
        const button = $(this);
        const paymentId = button.data('payment-id');
        
        if (!confirm('⚠️ آیا از حذف این پرداخت مطمئن هستید؟')) {
            return;
        }
        
        button.prop('disabled', true).text('در حال حذف...');
        
        $.ajax({
            url: ci_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ci_delete_payment',
                payment_id: paymentId,
                nonce: ci_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ پرداخت با موفقیت حذف شد');
                    location.reload();
                } else {
                    alert('❌ خطا: ' + response.data.message);
                    button.prop('disabled', false).text('حذف');
                }
            },
            error: function() {
                alert('❌ خطا در ارتباط با سرور');
                button.prop('disabled', false).text('حذف');
            }
        });
    });
    
    // ============================================
    // بستن مودال‌ها با کلیک خارج
    // ============================================
    $('#ci-request-modal, #ci-upload-modal, #ci-edit-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // ============================================
    // باز کردن مودال ویرایش
    // ============================================
    $(document).on('click', '.ci-edit-request', function() {
        var requestId = $(this).data('id');
        var modal = $('#ci-edit-modal');
        var form = $('#ci-edit-form');
        
        // نمایش لودینگ
        modal.show();
        form.html('<p style="text-align:center;padding:40px;">⏳ در حال بارگذاری اطلاعات...</p>');
        
        $.ajax({
            url: ci_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ci_get_request_raw',
                request_id: requestId,
                nonce: ci_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // ============================================
                    // دریافت اطلاعات از دیتابیس
                    // ============================================
                    var data = response.data.request;
                    
                    // آماده‌سازی مقادیر
                    var first_name = data.first_name || '';
                    var last_name = data.last_name || '';
                    var national_code = data.national_code || '';
                    var phone = data.phone || '';
                    var father_name = data.father_name || '';
                    var birth_date = data.birth_date || '';
                    var gender = data.gender || '';
                    var birth_place = data.birth_place || '';
                    var medical_id = data.medical_id || '';
                    var student_id = data.student_id || '';
                    var field_of_study = data.field_of_study || '';
                    var last_degree = data.last_degree || '';
                    var last_university = data.last_university || '';
                    var activity_field = data.activity_field || '';
                    var membership_type = data.membership_type || 'affiliate';
					var status = data.status || '';
                    
                    // ============================================
                    // ساخت فرم با اطلاعات (بدون فیلد مبلغ)
                    // ============================================
                    var formHtml = `
                        <input type="hidden" name="request_id" id="ci-edit-request-id" value="` + requestId + `">
                        
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">نام <span style="color:red;">*</span></label>
                                <input type="text" name="first_name" id="edit_first_name" class="widefat" required value="` + first_name + `">
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">نام خانوادگی <span style="color:red;">*</span></label>
                                <input type="text" name="last_name" id="edit_last_name" class="widefat" required value="` + last_name + `">
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">کد ملی <span style="color:red;">*</span></label>
                                <input type="text" name="national_code" id="edit_national_code" class="widefat" maxlength="10" required value="` + national_code + `">
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">تلفن همراه <span style="color:red;">*</span></label>
                                <input type="text" name="phone" id="edit_phone" class="widefat" required value="` + phone + `">
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">نام پدر <span style="color:red;">*</span></label>
                                <input type="text" name="father_name" id="edit_father_name" class="widefat" required value="` + father_name + `">
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">تاریخ تولد <span style="color:red;">*</span></label>
                                <input type="text" name="birth_date" id="edit_birth_date" class="widefat" placeholder="مثلاً 1370/01/15" required value="` + birth_date + `">
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">جنسیت</label>
                                <select name="gender" id="edit_gender" class="widefat">
                                    <option value="مرد" ` + (gender === 'مرد' ? 'selected' : '') + `>مرد</option>
                                    <option value="زن" ` + (gender === 'زن' ? 'selected' : '') + `>زن</option>
                                </select>
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">محل تولد</label>
                                <input type="text" name="birth_place" id="edit_birth_place" class="widefat" value="` + birth_place + `">
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">شماره نظام پزشکی</label>
                                <input type="text" name="medical_id" id="edit_medical_id" class="widefat" value="` + medical_id + `">
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">شماره دانشجویی</label>
                                <input type="text" name="student_id" id="edit_student_id" class="widefat" value="` + student_id + `">
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">رشته تحصیلی</label>
                                <input type="text" name="field_of_study" id="edit_field_of_study" class="widefat" value="` + field_of_study + `">
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">آخرین مدرک تحصیلی</label>
                                <input type="text" name="last_degree" id="edit_last_degree" class="widefat" value="` + last_degree + `">
                            </div>
                            <div style="grid-column:1/-1;">
                                <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">آخرین دانشگاه</label>
                                <input type="text" name="last_university" id="edit_last_university" class="widefat" value="` + last_university + `">
                            </div>
                            <div style="grid-column:1/-1;">
                                <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">زمینه فعالیت</label>
                                <textarea name="activity_field" id="edit_activity_field" rows="3" class="widefat">` + activity_field + `</textarea>
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">نوع عضویت</label>
                                <select name="membership_type" id="edit_membership_type" class="widefat">
                                    <option value="affiliate" ` + (membership_type === 'affiliate' ? 'selected' : '') + `>وابسته</option>
                                    <option value="core" ` + (membership_type === 'core' ? 'selected' : '') + `>پیوسته</option>
                                </select>
                            </div>
<div>
            <label style="display:block;font-weight:600;margin-bottom:5px;font-size:13px;">وضعیت پرداخت</label>
            <select name="status" id="edit_status" class="widefat">
                <option value="pending_payment" ` + (status === 'pending_payment' ? 'selected' : '') + `>در انتظار پرداخت</option>
                <option value="pending" ` + (status === 'pending' ? 'selected' : '') + `>در انتظار تایید</option>
                <option value="pending_approved" ` + (status === 'pending_approved' ? 'selected' : '') + `>تایید شده - منتظر گواهی</option>
                <option value="approved" ` + (status === 'approved' ? 'selected' : '') + `>تایید شده ✅</option>
                <option value="rejected" ` + (status === 'rejected' ? 'selected' : '') + `>رد شده ❌</option>
                <option value="expired" ` + (status === 'expired' ? 'selected' : '') + `>منقضی شده ⏰</option>
                <option value="cancelled" ` + (status === 'cancelled' ? 'selected' : '') + `>لغو شده</option>
            </select>
        </div>
    </div>
                        
                        <div style="margin-top:25px;padding-top:20px;border-top:2px solid #f0f0f1;display:flex;gap:10px;">
                            <button type="submit" class="button button-primary" style="padding:10px 30px;height:auto;font-size:15px;">💾 ذخیره تغییرات</button>
                            <button type="button" onclick="jQuery('#ci-edit-modal').hide();" class="button" style="padding:10px 20px;height:auto;">لغو</button>
                        </div>
                    `;
                    
                    form.html(formHtml);
                    
                } else {
                    alert('❌ خطا در بارگذاری اطلاعات: ' + response.data.message);
                    modal.hide();
                }
            },
            error: function(xhr, status, error) {
                alert('❌ خطا در ارتباط با سرور: ' + error);
                modal.hide();
            }
        });
    });
    
    // ============================================
    // ذخیره ویرایش
    // ============================================
    $(document).on('submit', '#ci-edit-form', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var button = $(this).find('button[type="submit"]');
        
        button.prop('disabled', true).text('⏳ در حال ذخیره...');
        
        $.ajax({
            url: ci_admin_ajax.ajax_url,
            type: 'POST',
            data: formData + '&action=ci_edit_request&nonce=' + ci_admin_ajax.nonce,
            success: function(response) {
                if (response.success) {
                    alert('✅ اطلاعات با موفقیت ویرایش شد');
                    location.reload();
                } else {
                    alert('❌ خطا: ' + response.data.message);
                    button.prop('disabled', false).text('💾 ذخیره تغییرات');
                }
            },
            error: function() {
                alert('❌ خطا در ارتباط با سرور');
                button.prop('disabled', false).text('💾 ذخیره تغییرات');
            }
        });
    });
	    // ============================================
    // تابع مشاهده درخواست (برای onclick)
    // ============================================
    window.ciViewPaymentRequest = function(requestId) {
        var modal = jQuery('#ci-request-modal');
        var content = jQuery('#ci-modal-content');
        
        if (!modal.length) {
            alert('❌ مودال پیدا نشد!');
            return;
        }
        
        content.html('<p style="text-align:center;padding:20px;">⏳ در حال بارگذاری...</p>');
        modal.show();
        
        jQuery.ajax({
            url: ci_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ci_get_request_details',
                request_id: requestId,
                nonce: ci_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    content.html(response.data.html);
                } else {
                    content.html('<p style="color:#f44336;text-align:center;">❌ خطا در بارگذاری اطلاعات</p>');
                }
            },
            error: function() {
                content.html('<p style="color:#f44336;text-align:center;">❌ خطا در ارتباط با سرور</p>');
            }
        });
    };
});