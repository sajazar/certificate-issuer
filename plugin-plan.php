<?php
/**
 * Debug - بررسی مسیر دیتا و فایل‌ها
 */

// بارگذاری وردپرس
require_once(dirname(__FILE__, 4) . '/wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('دسترسی غیرمجاز');
}

echo '<h2>🔍 بررسی مسیر دیتابیس افزونه</h2>';

$data_dir = WP_CONTENT_DIR . '/certificate-data';
echo '<p><strong>مسیر دیتا:</strong> ' . $data_dir . '</p>';

if (is_dir($data_dir)) {
    echo '<p style="color:green;">✅ پوشه دیتا وجود دارد</p>';
    echo '<h3>📁 فایل‌های موجود در پوشه دیتا:</h3>';
    echo '<ul>';
    $files = scandir($data_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $path = $data_dir . '/' . $file;
            if (is_file($path)) {
                echo '<li>📄 ' . $file . ' (اندازه: ' . round(filesize($path) / 1024, 2) . ' KB)</li>';
            } elseif (is_dir($path)) {
                echo '<li>📂 ' . $file . '/</li>';
            }
        }
    }
    echo '</ul>';
} else {
    echo '<p style="color:red;">❌ پوشه دیتا وجود ندارد! آیا باید خودکار ساخته شود؟</p>';
    // تلاش برای ایجاد پوشه
    if (mkdir($data_dir, 0755, true)) {
        echo '<p style="color:green;">✅ پوشه دیتا با موفقیت ساخته شد</p>';
    } else {
        echo '<p style="color:red;">❌ خطا در ایجاد پوشه دیتا. لطفاً دستی ایجاد کنید.</p>';
    }
}

// بررسی قابلیت نوشتن
if (is_dir($data_dir)) {
    if (is_writable($data_dir)) {
        echo '<p style="color:green;">✅ پوشه دیتا قابل نوشتن است</p>';
    } else {
        echo '<p style="color:red;">❌ پوشه دیتا قابل نوشتن نیست! لطفاً سطح دسترسی را به 755 یا 777 تغییر دهید.</p>';
    }
}

echo '<hr>';
echo '<h2>📋 اطلاعات اضافی</h2>';
echo '<p><strong>wp-content مسیر:</strong> ' . WP_CONTENT_DIR . '</p>';
echo '<p><strong>پلاگین مسیر:</strong> ' . __DIR__ . '</p>';

// چک کردن اینکه آیا کلاس DataManager وجود دارد
if (class_exists('DataManager')) {
    echo '<p style="color:green;">✅ کلاس DataManager وجود دارد</p>';
} else {
    echo '<p style="color:orange;">⚠️ کلاس DataManager تعریف نشده است (احتمالاً در فایل class-data-manager.php تعریف می‌شود)</p>';
}