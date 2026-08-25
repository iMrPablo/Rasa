<?php
require_once __DIR__ . '/../core/Database.php';

/**
 * Category Model
 */

class CategoryModel {
    private Database $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getAll(): array {
        return $this->db->read('categories', []);
    }
    
    public function add(string $name): array {
        if ($name === '') {
            return ['ok' => false, 'error' => 'نام دسته‌بندی را وارد کنید'];
        }
        
        $categories = $this->getAll();
        foreach ($categories as $category) {
            if (trim((string)($category['name'] ?? '')) === $name) {
                return ['ok' => false, 'error' => 'این دسته‌بندی قبلا ثبت شده است'];
            }
        }
        
        $categories[] = ['id' => bin2hex(random_bytes(8)), 'name' => $name];
        
        if ($this->db->write('categories', array_values($categories))) {
            return ['ok' => true];
        }
        
        return ['ok' => false, 'error' => 'خطا در ذخیره دسته‌بندی'];
    }
    
    public function delete(string $id): array {
        $categories = $this->getAll();
        $categories = array_values(array_filter($categories, function ($item) use ($id) {
            return ($item['id'] ?? '') !== $id;
        }));
        
        if ($this->db->write('categories', $categories)) {
            return ['ok' => true];
        }
        
        return ['ok' => false, 'error' => 'خطا در حذف دسته‌بندی'];
    }
}
