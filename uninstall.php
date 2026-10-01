<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// حذف کامل دیتاها
function ci_uninstall() {
    $storage_dir = WP_CONTENT_DIR . '/certificate-issuer-storage/';
    
    if (file_exists($storage_dir)) {
        // حذف بازگشتی پوشه
        ci_delete_directory($storage_dir);
    }
    
    // پاک کردن کرون جاب
    wp_clear_scheduled_hook('ci_check_expired_certificates');
    wp_clear_scheduled_hook('ci_send_renewal_sms');
    
    // حذف گزینه‌های وردپرس (اگر وجود داشته باشند)
    delete_option('ci_plugin_version');
}

function ci_delete_directory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!ci_delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}

ci_uninstall();