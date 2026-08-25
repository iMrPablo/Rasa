<?php
/**
 * Database - Model Base Class
 * Handles JSON file storage operations
 */

class Database {
    private string $storageDirectory;
    
    public function __construct() {
        $this->storageDirectory = __DIR__ . '/../../storage';
        if (!is_dir($this->storageDirectory)) {
            @mkdir($this->storageDirectory, 0775, true);
        }
        
        // Protect storage directory
        $htaccessFile = $this->storageDirectory . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            @file_put_contents($htaccessFile, "Require all denied");
        }
    }
    
    /**
     * Get file path for a data type
     */
    private function getFilePath(string $type): string {
        return $this->storageDirectory . '/' . $type . '.json';
    }
    
    /**
     * Read data from JSON file
     */
    public function read(string $type, array $default = []): array {
        $file = $this->getFilePath($type);
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
     * Write data to JSON file
     */
    public function write(string $type, array $data): bool {
        $file = $this->getFilePath($type);
        return @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
    }
    
    /**
     * Initialize default data files
     */
    public function initialize(): void {
        $defaults = [
            'settings' => [
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
            ],
            'categories' => [],
            'products' => [],
            'invoice' => [
                'items' => [],
                'discount' => 0,
                'discount_type' => 'amount',
                'invoice_id' => '',
                'invoice_number' => '',
                'customer_name' => '',
                'customer_phone' => '',
                'note' => ''
            ],
            'sales' => []
        ];
        
        foreach ($defaults as $type => $defaultData) {
            $file = $this->getFilePath($type);
            if (!file_exists($file)) {
                $this->write($type, $defaultData);
            }
        }
    }
}
