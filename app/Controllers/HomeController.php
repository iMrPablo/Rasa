<?php
/**
 * کنترلر اصلی (Home Controller)
 * 
 * @author آقای پابلو
 * @version 1.0
 * @since خرداد ۱۴۰۵
 */

namespace App\Controllers;

class HomeController extends Controller
{
    /**
     * نمایش صفحه اصلی
     * 
     * @return void
     */
    public function index()
    {
        $data = [
            'title' => 'صفحه اصلی - پروژه MVC',
            'heading' => 'به پروژه MVC خوش آمدید',
            'content' => '
                <p>این یک پروژه نمونه با معماری MVC است که توسط <strong>آقای پابلو</strong> طراحی و توسعه یافته است.</p>
                <ul>
                    <li>نسخه اولیه: خرداد ۱۴۰۵</li>
                    <li>نسخه نهایی: ۲۸ مرداد ۱۴۰۵</li>
                </ul>
            '
        ];
        
        $this->view('home', $data);
    }
}
