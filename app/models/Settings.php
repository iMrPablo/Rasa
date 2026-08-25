<?php
require_once __DIR__ . '/../core/Database.php';

/**
 * Settings Model
 */

class SettingsModel {
    private Database $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function get(): array {
        $settings = $this->db->read('settings', [
            'name' => '',
            'phone' => '',
            'address' => '',
            'currency' => 'IRT',
            'language' => 'fa',
            'calendar' => 'jalali',
            'installed' => false,
            'currency_locked' => false,
            'vat' => 0,
            'theme' => 'light'
        ]);
        
        // Ensure required fields exist
        if (is_array($settings) && !array_key_exists('installed', $settings)) {
            $settings['installed'] = true;
            $settings['currency_locked'] = true;
            $this->save($settings);
        }
        
        if (!isset($settings['installed'])) {
            $settings['installed'] = false;
        }
        if (!isset($settings['currency_locked'])) {
            $settings['currency_locked'] = false;
        }
        if (!isset($settings['vat']) || !is_numeric($settings['vat'])) {
            $settings['vat'] = 0;
        }
        if (!isset($settings['theme'])) {
            $settings['theme'] = 'light';
        }
        
        return $settings;
    }
    
    public function save(array $data): bool {
        return $this->db->write('settings', $data);
    }
    
    public function install(array $payload): array {
        $allowedCurrencies = ['USD', 'EUR', 'OMR', 'IRT', 'IRR'];
        $allowedLanguages = ['fa', 'en', 'fr', 'de', 'es', 'en_GB'];
        $allowedCalendars = ['jalali', 'gregorian', 'both'];
        
        $currency = trim((string)($payload['currency'] ?? 'IRT'));
        $language = trim((string)($payload['language'] ?? 'fa'));
        $calendar = trim((string)($payload['calendar'] ?? 'jalali'));
        
        if (!in_array($currency, $allowedCurrencies)) $currency = 'IRT';
        if (!in_array($language, $allowedLanguages)) $language = 'fa';
        if (!in_array($calendar, $allowedCalendars)) $calendar = 'jalali';
        
        $settings = [
            'name' => '',
            'phone' => '',
            'address' => '',
            'currency' => $currency,
            'language' => $language,
            'calendar' => $calendar,
            'installed' => true,
            'currency_locked' => true,
            'vat' => 0,
            'theme' => 'light'
        ];
        
        if ($this->save($settings)) {
            return ['ok' => true];
        }
        
        return ['ok' => false, 'error' => 'خطا در نصب'];
    }
}
