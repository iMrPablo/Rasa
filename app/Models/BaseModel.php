<?php
namespace App\Models;

/**
 * کلاس پایه مدل
 * مدیریت فایل‌های JSON و عملیات CRUD
 */
class BaseModel
{
    protected $storageDirectory;
    
    public function __construct()
    {
        $this->storageDirectory = __DIR__ . '/../../rasa_data';
        if (!is_dir($this->storageDirectory)) {
            @mkdir($this->storageDirectory, 0775, true);
        }
    }

    /**
     * خواندن فایل JSON
     * @param string $filename نام فایل
     * @param mixed $default مقدار پیش‌فرض
     * @return mixed
     */
    protected function readJsonFile($filename, $default = [])
    {
        $file = $this->storageDirectory . '/' . $filename;
        
        if (!file_exists($file)) {
            return $default;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return $default;
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : $default;
    }

    /**
     * نوشتن در فایل JSON
     * @param string $filename نام فایل
     * @param mixed $data داده‌ها
     * @return bool
     */
    protected function writeJsonFile($filename, $data)
    {
        $file = $this->storageDirectory . '/' . $filename;
        return @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
    }

    /**
     * ایجاد شناسه یکتا
     * @return string
     */
    protected function createId()
    {
        return function_exists('random_bytes') ? bin2hex(random_bytes(8)) : uniqid('', true);
    }
}
