<?php
/**
 * نقطه شروع برنامه (Entry Point)
 * 
 * @author آقای پابلو
 * @version 1.0
 * @since نسخه نهایی: ۲۸ مرداد ۱۴۰۵
 */

// تنظیم منطقه زمانی
date_default_timezone_set('Asia/Tehran');

// نمایش خطاها در حالت توسعه
error_reporting(E_ALL);
ini_set('display_errors', 1);

// تعریف ثابت‌های مسیر
define('ROOT_PATH', __DIR__);
define('APP_PATH', __DIR__ . '/app');
define('CONFIG_PATH', __DIR__ . '/config');
define('PUBLIC_PATH', __DIR__ . '/public');

// بارگذاری فایل پیکربندی
$config = require CONFIG_PATH . '/config.php';

// تابع Autoloader برای بارگذاری خودکار کلاس‌ها
spl_autoload_register(function ($class) {
    // تبدیل namespace به مسیر فایل
    $prefix = 'App\\';
    $base_dir = APP_PATH . '/';
    
    // بررسی اینکه کلاس در namespace ما باشد
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // دریافت نام کلاس نسبی
    $relative_class = substr($class, $len);
    
    // تبدیل به مسیر فایل
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // اگر فایل وجود داشت، آن را شامل کن
    if (file_exists($file)) {
        require $file;
    }
});

// دریافت کنترلر و اکشن از URL (ساده‌سازی شده)
$controller = isset($_GET['controller']) ? $_GET['controller'] : 'home';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

// ساخت نام کلاس کنترلر
$controllerClass = 'App\\Controllers\\' . ucfirst($controller) . 'Controller';

// بررسی وجود کلاس کنترلر
if (class_exists($controllerClass)) {
    $controllerObj = new $controllerClass();
    
    // بررسی وجود متد اکشن
    if (method_exists($controllerObj, $action)) {
        $controllerObj->$action();
    } else {
        echo "متد {$action} یافت نشد.";
    }
} else {
    echo "کنترلر {$controller} یافت نشد.";
}
