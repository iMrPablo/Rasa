<?php
/**
 * کنترلر پایه (Base Controller)
 * 
 * @author آقای پابلو
 * @version 1.0
 * @since خرداد ۱۴۰۵
 */

namespace App\Controllers;

class Controller
{
    /**
     * رندر کردن ویو
     * 
     * @param string $view نام ویو
     * @param array $data داده‌های ارسالی به ویو
     * @return void
     */
    protected function view($view, $data = [])
    {
        extract($data);
        include __DIR__ . '/../Views/' . $view . '.php';
    }

    /**
     * ریدایرکت به آدرس دیگر
     * 
     * @param string $url آدرس مقصد
     * @return void
     */
    protected function redirect($url)
    {
        header("Location: " . $url);
        exit;
    }
}
