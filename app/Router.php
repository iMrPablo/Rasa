<?php
/**
 * Router اصلی برنامه
 * هدایت درخواست‌ها به کنترلرهای مناسب
 */

// تنظیمات خطاگیری
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Autoloader ساده
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// دریافت اکشن از درخواست
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

// ایجاد کنترلر اصلی
$controller = new App\Controllers\MainController();

// نقشه‌دهی اکشن‌ها به متدها
$actionMap = [
    'index' => 'index',
    'saveSettings' => 'saveSettings',
    'getCategories' => 'getCategories',
    'addCategory' => 'addCategory',
    'updateCategory' => 'updateCategory',
    'deleteCategory' => 'deleteCategory',
    'getProducts' => 'getProducts',
    'addProduct' => 'addProduct',
    'updateProduct' => 'updateProduct',
    'deleteProduct' => 'deleteProduct',
    'getCurrentInvoice' => 'getCurrentInvoice',
    'addToInvoice' => 'addToInvoice',
    'removeFromInvoice' => 'removeFromInvoice',
    'setDiscount' => 'setDiscount',
    'finalizeInvoice' => 'finalizeInvoice',
    'getSalesStats' => 'getSalesStats',
    'getSalesByDate' => 'getSalesByDate',
    'getAllSales' => 'getAllSales'
];

if (isset($actionMap[$action]) && method_exists($controller, $actionMap[$action])) {
    $method = $actionMap[$action];
    $controller->$method();
} else {
    // نمایش صفحه اصلی به صورت پیش‌فرض
    $controller->index();
}
