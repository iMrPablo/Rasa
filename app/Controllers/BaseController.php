<?php
namespace App\Controllers;

/**
 * کلاس پایه کنترلر
 * تمام کنترلرها باید از این کلاس ارث‌بری کنند
 */
class BaseController
{
    /**
     * رندر کردن ویو با داده‌های مشخص
     * @param string $view نام ویو
     * @param array $data داده‌های ارسالی به ویو
     * @return void
     */
    protected function render($view, $data = [])
    {
        extract($data);
        $viewPath = __DIR__ . "/../Views/{$view}.php";
        
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            throw new \Exception("ویوی {$view} یافت نشد.");
        }
    }

    /**
     * بازگشت پاسخ JSON
     * @param array $data داده‌ها
     * @return void
     */
    protected function jsonResponse($data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * دریافت ورودی‌های POST
     * @param string $key کلید ورودی
     * @param mixed $default مقدار پیش‌فرض
     * @return mixed
     */
    protected function postInput($key, $default = null)
    {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }

    /**
     * دریافت ورودی‌های GET
     * @param string $key کلید ورودی
     * @param mixed $default مقدار پیش‌فرض
     * @return mixed
     */
    protected function getInput($key, $default = null)
    {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }
}
