<?php
/**
 * مدل پایه (Base Model)
 * 
 * @author آقای پابلو
 * @version 1.0
 * @since خرداد ۱۴۰۵
 */

namespace App\Models;

class Model
{
    protected $db;

    /**
     * سازنده کلاس - اتصال به پایگاه داده
     */
    public function __construct()
    {
        // امکان اضافه کردن اتصال به دیتابیس در آینده
        $this->db = null;
    }

    /**
     * دریافت تمام رکوردها
     * 
     * @return array آرایه‌ای از رکوردها
     */
    public function getAll()
    {
        // پیاده‌سازی در کلاس‌های فرزند
        return [];
    }

    /**
     * یافتن رکورد بر اساس ID
     * 
     * @param int $id شناسه رکورد
     * @return mixed|null رکورد یافت شده یا null
     */
    public function find($id)
    {
        // پیاده‌سازی در کلاس‌های فرزند
        return null;
    }

    /**
     * ذخیره رکورد جدید
     * 
     * @param array $data داده‌های رکورد
     * @return bool نتیجه عملیات
     */
    public function create($data)
    {
        // پیاده‌سازی در کلاس‌های فرزند
        return false;
    }

    /**
     * به‌روزرسانی رکورد
     * 
     * @param int $id شناسه رکورد
     * @param array $data داده‌های جدید
     * @return bool نتیجه عملیات
     */
    public function update($id, $data)
    {
        // پیاده‌سازی در کلاس‌های فرزند
        return false;
    }

    /**
     * حذف رکورد
     * 
     * @param int $id شناسه رکورد
     * @return bool نتیجه عملیات
     */
    public function delete($id)
    {
        // پیاده‌سازی در کلاس‌های فرزند
        return false;
    }
}
