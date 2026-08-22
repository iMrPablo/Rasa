<?php
namespace App\Models;

/**
 * مدل مدیریت محصولات
 */
class ProductModel extends BaseModel
{
    private $productsFile = 'products.json';

    /**
     * دریافت تمام محصولات
     * @param string|null $categoryId فیلتر بر اساس دسته‌بندی
     * @return array
     */
    public function getAll($categoryId = null)
    {
        $products = $this->readJsonFile($this->productsFile, []);
        
        if ($categoryId) {
            $products = array_filter($products, function($product) use ($categoryId) {
                return $product['category_id'] === $categoryId;
            });
        }
        
        return array_values($products);
    }

    /**
     * افزودن محصول جدید
     * @param string $name نام محصول
     * @param string $categoryId شناسه دسته‌بندی
     * @param int $price قیمت
     * @return array|null
     */
    public function add($name, $categoryId, $price)
    {
        $products = $this->getAll();

        $newProduct = [
            'id' => $this->createId(),
            'name' => $name,
            'category_id' => $categoryId,
            'price' => (int)$price,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $products[] = $newProduct;
        $this->writeJsonFile($this->productsFile, $products);
        
        return $newProduct;
    }

    /**
     * ویرایش محصول
     * @param string $id شناسه محصول
     * @param string $name نام محصول
     * @param string $categoryId شناسه دسته‌بندی
     * @param int $price قیمت
     * @return bool
     */
    public function update($id, $name, $categoryId, $price)
    {
        $products = $this->getAll();
        
        foreach ($products as &$product) {
            if ($product['id'] === $id) {
                $product['name'] = $name;
                $product['category_id'] = $categoryId;
                $product['price'] = (int)$price;
                $product['updated_at'] = date('Y-m-d H:i:s');
                $this->writeJsonFile($this->productsFile, $products);
                return true;
            }
        }
        
        return false;
    }

    /**
     * حذف محصول
     * @param string $id شناسه محصول
     * @return bool
     */
    public function delete($id)
    {
        $products = $this->getAll();
        $newProducts = array_filter($products, function($product) use ($id) {
            return $product['id'] !== $id;
        });
        
        $this->writeJsonFile($this->productsFile, array_values($newProducts));
        return true;
    }

    /**
     * یافتن محصول بر اساس شناسه
     * @param string $id شناسه محصول
     * @return array|null
     */
    public function findById($id)
    {
        $products = $this->getAll();
        
        foreach ($products as $product) {
            if ($product['id'] === $id) {
                return $product;
            }
        }
        
        return null;
    }
}
