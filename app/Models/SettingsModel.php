<?php
namespace App\Models;

/**
 * مدل مدیریت تنظیمات فروشگاه
 */
class SettingsModel extends BaseModel
{
    private $settingsFile = 'settings.json';

    /**
     * دریافت تنظیمات فروشگاه
     * @return array
     */
    public function getSettings()
    {
        return $this->readJsonFile($this->settingsFile, ['name' => '', 'phone' => '', 'address' => '']);
    }

    /**
     * ذخیره تنظیمات فروشگاه
     * @param array $data داده‌های تنظیمات
     * @return bool
     */
    public function saveSettings($data)
    {
        return $this->writeJsonFile($this->settingsFile, $data);
    }
}
