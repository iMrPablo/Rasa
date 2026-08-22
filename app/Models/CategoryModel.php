<?php
namespace App\Models;

/**
 * مدل مدیریت دسته‌بندی‌ها
 */
class CategoryModel extends BaseModel
{
    private $categoriesFile = 'categories.json';

    /**
     * دریافت تمام دسته‌بندی‌ها
     * @return array
     */
    public function getAll()
    {
        return $this->readJsonFile($this->categoriesFile, []);
    }

    /**
     * افزودن دسته‌بندی جدید
     * @param string $name نام دسته‌بندی
     * @return array|null
     */
    public function add($name)
    {
        $categories = $this->getAll();
        
        // بررسی تکراری بودن
        foreach ($categories as $category) {
            if ($category['name'] === $name) {
                return null;
            }
        }

        $newCategory = [
            'id' => $this->createId(),
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $categories[] = $newCategory;
        $this->writeJsonFile($this->categoriesFile, $categories);
        
        return $newCategory;
    }

    /**
     * ویرایش دسته‌بندی
     * @param string $id شناسه دسته‌بندی
     * @param string $name نام جدید
     * @return bool
     */
    public function update($id, $name)
    {
        $categories = $this->getAll();
        
        foreach ($categories as &$category) {
            if ($category['id'] === $id) {
                // بررسی تکراری بودن نام (به جز خودش)
                foreach ($categories as $cat) {
                    if ($cat['id'] !== $id && $cat['name'] === $name) {
                        return false;
                    }
                }
                
                $category['name'] = $name;
                $category['updated_at'] = date('Y-m-d H:i:s');
                $this->writeJsonFile($this->categoriesFile, $categories);
                return true;
            }
        }
        
        return false;
    }

    /**
     * حذف دسته‌بندی
     * @param string $id شناسه دسته‌بندی
     * @return bool
     */
    public function delete($id)
    {
        $categories = $this->getAll();
        $newCategories = array_filter($categories, function($cat) use ($id) {
            return $cat['id'] !== $id;
        });
        
        $this->writeJsonFile($this->categoriesFile, array_values($newCategories));
        return true;
    }

    /**
     * یافتن دسته‌بندی بر اساس شناسه
     * @param string $id شناسه دسته‌بندی
     * @return array|null
     */
    public function findById($id)
    {
        $categories = $this->getAll();
        
        foreach ($categories as $category) {
            if ($category['id'] === $id) {
                return $category;
            }
        }
        
        return null;
    }
}
