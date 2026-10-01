<div class="ci-admin-export">
    <h1>📤 خروجی کاربران</h1>
    
    <div class="ci-export-container">
        <div class="ci-export-box">
            <h2>📊 خروجی Excel</h2>
            <p>گرفتن خروجی از تمام کاربران به صورت فایل Excel (xlsx)</p>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="ci_export_excel">
                <?php wp_nonce_field('ci_export_excel', 'ci_export_nonce'); ?>
                
                <div class="ci-export-options">
                    <label>
                        <input type="checkbox" name="export_fields[]" value="all" checked> همه فیلدها
                    </label>
                    <label>
                        <input type="checkbox" name="export_fields[]" value="personal"> اطلاعات شخصی
                    </label>
                    <label>
                        <input type="checkbox" name="export_fields[]" value="education"> اطلاعات تحصیلی
                    </label>
                    <label>
                        <input type="checkbox" name="export_fields[]" value="certificate"> اطلاعات گواهی
                    </label>
                </div>
                
                <div class="ci-export-filters">
                    <select name="export_status">
                        <option value="all">همه وضعیت‌ها</option>
                        <option value="approved">تایید شده</option>
                        <option value="pending">در انتظار تایید</option>
                        <option value="pending_payment">در انتظار پرداخت</option>
                        <option value="rejected">رد شده</option>
                        <option value="expired">منقضی شده</option>
                    </select>
                    <select name="export_membership">
                        <option value="all">همه نوع عضویت</option>
                        <option value="affiliate">وابسته</option>
                        <option value="core">پیوسته</option>
                    </select>
                </div>
                
                <button type="submit" class="button button-primary">
                    📥 دانلود Excel
                </button>
            </form>
        </div>
        
        <div class="ci-export-box">
            <h2>📄 خروجی Word</h2>
            <p>گرفتن خروجی از تمام کاربران به صورت فایل Word (docx)</p>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="ci_export_word">
                <?php wp_nonce_field('ci_export_word', 'ci_export_nonce'); ?>
                
                <div class="ci-export-options">
                    <label>
                        <input type="checkbox" name="export_fields[]" value="all" checked> همه فیلدها
                    </label>
                    <label>
                        <input type="checkbox" name="export_fields[]" value="personal"> اطلاعات شخصی
                    </label>
                    <label>
                        <input type="checkbox" name="export_fields[]" value="education"> اطلاعات تحصیلی
                    </label>
                    <label>
                        <input type="checkbox" name="export_fields[]" value="certificate"> اطلاعات گواهی
                    </label>
                </div>
                
                <div class="ci-export-filters">
                    <select name="export_status">
                        <option value="all">همه وضعیت‌ها</option>
                        <option value="approved">تایید شده</option>
                        <option value="pending">در انتظار تایید</option>
                        <option value="pending_payment">در انتظار پرداخت</option>
                        <option value="rejected">رد شده</option>
                        <option value="expired">منقضی شده</option>
                    </select>
                    <select name="export_membership">
                        <option value="all">همه نوع عضویت</option>
                        <option value="affiliate">وابسته</option>
                        <option value="core">پیوسته</option>
                    </select>
                </div>
                
                <button type="submit" class="button button-primary">
                    📥 دانلود Word
                </button>
            </form>
        </div>
        
        <div class="ci-export-box">
            <h2>📋 خروجی CSV</h2>
            <p>گرفتن خروجی از تمام کاربران به صورت فایل CSV</p>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="ci_export_csv">
                <?php wp_nonce_field('ci_export_csv', 'ci_export_nonce'); ?>
                
                <div class="ci-export-options">
                    <label>
                        <input type="checkbox" name="export_fields[]" value="all" checked> همه فیلدها
                    </label>
                    <label>
                        <input type="checkbox" name="export_fields[]" value="personal"> اطلاعات شخصی
                    </label>
                    <label>
                        <input type="checkbox" name="export_fields[]" value="education"> اطلاعات تحصیلی
                    </label>
                    <label>
                        <input type="checkbox" name="export_fields[]" value="certificate"> اطلاعات گواهی
                    </label>
                </div>
                
                <div class="ci-export-filters">
                    <select name="export_status">
                        <option value="all">همه وضعیت‌ها</option>
                        <option value="approved">تایید شده</option>
                        <option value="pending">در انتظار تایید</option>
                        <option value="pending_payment">در انتظار پرداخت</option>
                        <option value="rejected">رد شده</option>
                        <option value="expired">منقضی شده</option>
                    </select>
                    <select name="export_membership">
                        <option value="all">همه نوع عضویت</option>
                        <option value="affiliate">وابسته</option>
                        <option value="core">پیوسته</option>
                    </select>
                </div>
                
                <button type="submit" class="button button-primary">
                    📥 دانلود CSV
                </button>
            </form>
        </div>
    </div>
    
    <style>
    .ci-admin-export {
        max-width: 1200px;
        margin: 20px 0;
        padding: 0 20px;
    }
    .ci-admin-export h1 {
        margin-bottom: 30px;
        color: #1d2327;
    }
    .ci-export-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 25px;
    }
    .ci-export-box {
        background: #fff;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        border: 1px solid #e8ecf1;
    }
    .ci-export-box h2 {
        margin: 0 0 8px 0;
        font-size: 18px;
        color: #1d2327;
    }
    .ci-export-box p {
        margin: 0 0 20px 0;
        color: #666;
        font-size: 14px;
    }
    .ci-export-options {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 15px;
    }
    .ci-export-options label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: #333;
        cursor: pointer;
    }
    .ci-export-filters {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 15px;
    }
    .ci-export-filters select {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 13px;
        background: #fff;
        min-width: 140px;
    }
    .ci-export-filters select:focus {
        border-color: #4CAF50;
        outline: none;
    }
    .ci-export-box .button-primary {
        padding: 10px 25px;
        height: auto;
        font-size: 14px;
    }
    @media (max-width: 600px) {
        .ci-export-container {
            grid-template-columns: 1fr;
        }
        .ci-export-filters {
            flex-direction: column;
        }
        .ci-export-filters select {
            width: 100%;
        }
    }
    </style>
</div>