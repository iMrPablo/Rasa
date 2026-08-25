<?php
require_once __DIR__ . '/../core/Database.php';

/**
 * Product Model
 */

class ProductModel {
    private Database $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getAll(): array {
        return $this->db->read('products', []);
    }
    
    public function add(string $name, string $category, float $price): array {
        if ($name === '' || $category === '' || $price < 0) {
            return ['ok' => false, 'error' => 'نام، دسته‌بندی و قیمت معتبر وارد کنید'];
        }
        
        $products = $this->getAll();
        $products[] = [
            'id' => bin2hex(random_bytes(8)),
            'name' => $name,
            'category' => $category,
            'price' => $price
        ];
        
        if ($this->db->write('products', array_values($products))) {
            return ['ok' => true];
        }
        
        return ['ok' => false, 'error' => 'خطا در ذخیره محصول'];
    }
    
    public function delete(string $id): array {
        $products = $this->getAll();
        $products = array_values(array_filter($products, function ($item) use ($id) {
            return ($item['id'] ?? '') !== $id;
        }));
        
        if ($this->db->write('products', $products)) {
            return ['ok' => true];
        }
        
        return ['ok' => false, 'error' => 'خطا در حذف محصول'];
    }
}
