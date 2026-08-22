<?php
namespace App\Controllers;

use App\Models\SettingsModel;
use App\Models\CategoryModel;
use App\Models\ProductModel;
use App\Models\InvoiceModel;

/**
 * کنترلر اصلی برنامه
 */
class MainController extends BaseController
{
    private $settingsModel;
    private $categoryModel;
    private $productModel;
    private $invoiceModel;

    public function __construct()
    {
        $this->settingsModel = new SettingsModel();
        $this->categoryModel = new CategoryModel();
        $this->productModel = new ProductModel();
        $this->invoiceModel = new InvoiceModel();
    }

    /**
     * نمایش صفحه اصلی
     */
    public function index()
    {
        $settings = $this->settingsModel->getSettings();
        $stats = $this->invoiceModel->getSalesStats();
        
        $this->render('main', [
            'settings' => $settings,
            'stats' => $stats
        ]);
    }

    /**
     * مدیریت تنظیمات فروشگاه
     */
    public function saveSettings()
    {
        $data = [
            'name' => $this->postInput('name', ''),
            'phone' => $this->postInput('phone', ''),
            'address' => $this->postInput('address', '')
        ];
        
        if ($this->settingsModel->saveSettings($data)) {
            $this->jsonResponse(['success' => true, 'message' => 'تنظیمات با موفقیت ذخیره شد']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'خطا در ذخیره تنظیمات']);
        }
    }

    /**
     * مدیریت دسته‌بندی‌ها
     */
    public function getCategories()
    {
        $categories = $this->categoryModel->getAll();
        $this->jsonResponse(['success' => true, 'categories' => $categories]);
    }

    public function addCategory()
    {
        $name = $this->postInput('name', '');
        
        if (empty($name)) {
            $this->jsonResponse(['success' => false, 'message' => 'نام دسته‌بندی الزامی است']);
            return;
        }
        
        $category = $this->categoryModel->add($name);
        
        if ($category) {
            $this->jsonResponse(['success' => true, 'category' => $category, 'message' => 'دسته‌بندی با موفقیت افزوده شد']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'این دسته‌بندی قبلاً وجود دارد']);
        }
    }

    public function updateCategory()
    {
        $id = $this->postInput('id', '');
        $name = $this->postInput('name', '');
        
        if (empty($id) || empty($name)) {
            $this->jsonResponse(['success' => false, 'message' => 'اطلاعات ناقص است']);
            return;
        }
        
        if ($this->categoryModel->update($id, $name)) {
            $this->jsonResponse(['success' => true, 'message' => 'دسته‌بندی با موفقیت ویرایش شد']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'خطا در ویرایش دسته‌بندی یا نام تکراری است']);
        }
    }

    public function deleteCategory()
    {
        $id = $this->postInput('id', '');
        
        if ($this->categoryModel->delete($id)) {
            $this->jsonResponse(['success' => true, 'message' => 'دسته‌بندی با موفقیت حذف شد']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'خطا در حذف دسته‌بندی']);
        }
    }

    /**
     * مدیریت محصولات
     */
    public function getProducts()
    {
        $categoryId = $this->getInput('category_id');
        $products = $this->productModel->getAll($categoryId);
        $this->jsonResponse(['success' => true, 'products' => $products]);
    }

    public function addProduct()
    {
        $name = $this->postInput('name', '');
        $categoryId = $this->postInput('category_id', '');
        $price = $this->postInput('price', 0);
        
        if (empty($name) || empty($categoryId)) {
            $this->jsonResponse(['success' => false, 'message' => 'نام محصول و دسته‌بندی الزامی است']);
            return;
        }
        
        $product = $this->productModel->add($name, $categoryId, $price);
        
        if ($product) {
            $this->jsonResponse(['success' => true, 'product' => $product, 'message' => 'محصول با موفقیت افزوده شد']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'خطا در افزودن محصول']);
        }
    }

    public function updateProduct()
    {
        $id = $this->postInput('id', '');
        $name = $this->postInput('name', '');
        $categoryId = $this->postInput('category_id', '');
        $price = $this->postInput('price', 0);
        
        if (empty($id) || empty($name) || empty($categoryId)) {
            $this->jsonResponse(['success' => false, 'message' => 'اطلاعات ناقص است']);
            return;
        }
        
        if ($this->productModel->update($id, $name, $categoryId, $price)) {
            $this->jsonResponse(['success' => true, 'message' => 'محصول با موفقیت ویرایش شد']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'خطا در ویرایش محصول']);
        }
    }

    public function deleteProduct()
    {
        $id = $this->postInput('id', '');
        
        if ($this->productModel->delete($id)) {
            $this->jsonResponse(['success' => true, 'message' => 'محصول با موفقیت حذف شد']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'خطا در حذف محصول']);
        }
    }

    /**
     * مدیریت فاکتور
     */
    public function getCurrentInvoice()
    {
        $invoice = $this->invoiceModel->getCurrentInvoice();
        $total = $this->invoiceModel->calculateTotal();
        $this->jsonResponse(['success' => true, 'invoice' => $invoice, 'total' => $total]);
    }

    public function addToInvoice()
    {
        $item = [
            'id' => $this->postInput('id'),
            'name' => $this->postInput('name', 'محصول دستی'),
            'price' => (int)$this->postInput('price', 0),
            'quantity' => (int)$this->postInput('quantity', 1)
        ];
        
        if ($this->invoiceModel->addItem($item)) {
            $total = $this->invoiceModel->calculateTotal();
            $this->jsonResponse(['success' => true, 'total' => $total, 'message' => 'محصول به فاکتور اضافه شد']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'خطا در افزودن به فاکتور']);
        }
    }

    public function removeFromInvoice()
    {
        $index = (int)$this->postInput('index', 0);
        
        if ($this->invoiceModel->removeItem($index)) {
            $total = $this->invoiceModel->calculateTotal();
            $this->jsonResponse(['success' => true, 'total' => $total, 'message' => 'محصول از فاکتور حذف شد']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'خطا در حذف از فاکتور']);
        }
    }

    public function setDiscount()
    {
        $discount = (int)$this->postInput('discount', 0);
        
        if ($this->invoiceModel->setDiscount($discount)) {
            $total = $this->invoiceModel->calculateTotal();
            $this->jsonResponse(['success' => true, 'total' => $total, 'message' => 'تخفیف اعمال شد']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'خطا در اعمال تخفیف']);
        }
    }

    public function finalizeInvoice()
    {
        $invoiceNumber = $this->postInput('invoice_number', $this->invoiceModel->createId());
        
        if ($this->invoiceModel->finalizeInvoice($invoiceNumber)) {
            $this->jsonResponse(['success' => true, 'message' => 'فاکتور با موفقیت ثبت شد', 'invoice_number' => $invoiceNumber]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'خطا در ثبت فاکتور']);
        }
    }

    /**
     * گزارش‌گیری فروش
     */
    public function getSalesStats()
    {
        $stats = $this->invoiceModel->getSalesStats();
        $this->jsonResponse(['success' => true, 'stats' => $stats]);
    }

    public function getSalesByDate()
    {
        $date = $this->postInput('date', '');
        
        if (empty($date)) {
            $this->jsonResponse(['success' => false, 'message' => 'تاریخ الزامی است']);
            return;
        }
        
        $sales = $this->invoiceModel->getSalesByDate($date);
        $this->jsonResponse(['success' => true, 'sales' => array_values($sales)]);
    }

    public function getAllSales()
    {
        $sales = $this->invoiceModel->getAllSales();
        $this->jsonResponse(['success' => true, 'sales' => $sales]);
    }
}
