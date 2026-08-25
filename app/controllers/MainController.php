<?php
/**
 * Main Controller
 * Handles API requests and returns JSON responses
 */

class MainController {
    private $settingsModel;
    private $categoryModel;
    private $productModel;
    private $invoiceModel;
    private $saleModel;
    
    public function __construct() {
        require_once __DIR__ . '/../models/Settings.php';
        require_once __DIR__ . '/../models/Category.php';
        require_once __DIR__ . '/../models/Product.php';
        require_once __DIR__ . '/../models/Invoice.php';
        require_once __DIR__ . '/../models/Sale.php';
        
        $this->settingsModel = new SettingsModel();
        $this->categoryModel = new CategoryModel();
        $this->productModel = new ProductModel();
        $this->invoiceModel = new InvoiceModel();
        $this->saleModel = new SaleModel();
    }
    
    public function handleRequest(string $action, array $payload): void {
        switch ($action) {
            case 'get':
                $this->getData();
                break;
            case 'install':
                $this->install($payload);
                break;
            case 'save_settings':
                $this->saveSettings($payload);
                break;
            case 'toggle_theme':
                $this->toggleTheme($payload);
                break;
            case 'add_category':
                $this->addCategory($payload);
                break;
            case 'delete_category':
                $this->deleteCategory($payload);
                break;
            case 'add_product':
                $this->addProduct($payload);
                break;
            case 'delete_product':
                $this->deleteProduct($payload);
                break;
            case 'save_invoice':
                $this->saveInvoice($payload);
                break;
            case 'record_sale':
                $this->recordSale($payload);
                break;
            case 'export_backup':
                $this->exportBackup();
                break;
            default:
                jsonResponse(['ok' => false, 'error' => 'درخواست نامعتبر است']);
        }
    }
    
    private function getData(): void {
        $settings = $this->settingsModel->get();
        $categories = $this->categoryModel->getAll();
        $products = $this->productModel->getAll();
        $sales = $this->saleModel->getAll();
        $invoice = $this->invoiceModel->get();
        
        // Calculate history stats
        $now = new DateTime();
        $todayJ = \JalaliCalendar::currentJalali();
        
        $weekStart = clone $now;
        $weekday = (int)$now->format('N');
        $daysSinceSaturday = ($weekday + 1) % 7;
        $weekStart->modify('-' . $daysSinceSaturday . ' days');
        $weekStart->setTime(0, 0, 0);
        
        $weekEnd = clone $weekStart;
        $weekEnd->modify('+6 days');
        $weekEnd->setTime(23, 59, 59);
        
        $weekStartDate = $weekStart->format('Y-m-d');
        $weekEndDate = $weekEnd->format('Y-m-d');
        $weekStartJ = \JalaliCalendar::gregorianToJalali(
            (int)$weekStart->format('Y'),
            (int)$weekStart->format('n'),
            (int)$weekStart->format('j')
        );
        $weekEndJ = \JalaliCalendar::gregorianToJalali(
            (int)$weekEnd->format('Y'),
            (int)$weekEnd->format('n'),
            (int)$weekEnd->format('j')
        );
        
        $weekTotal = 0;
        $weekCount = 0;
        $monthTotal = 0;
        $monthCount = 0;
        
        foreach ($sales as $sale) {
            $saleTotal = isset($sale['total']) && is_numeric($sale['total']) ? (float)$sale['total'] : 0;
            $saleDate = isset($sale['date']) ? (string)$sale['date'] : '';
            
            if ($saleDate !== '' && $saleDate >= $weekStartDate && $saleDate <= $weekEndDate) {
                $weekTotal += $saleTotal;
                $weekCount++;
            }
            
            if (isset($sale['jy'], $sale['jm']) && (int)$sale['jy'] === $todayJ[0] && (int)$sale['jm'] === $todayJ[1]) {
                $monthTotal += $saleTotal;
                $monthCount++;
            }
        }
        
        jsonResponse([
            'ok' => true,
            'settings' => $settings,
            'categories' => $categories,
            'products' => $products,
            'invoiceItems' => $invoice['items'],
            'invoiceDiscount' => $invoice['discount'],
            'invoiceDiscountType' => $invoice['discount_type'],
            'invoiceId' => $invoice['invoice_id'],
            'invoiceNumber' => $invoice['invoice_number'],
            'invoiceCustomerName' => $invoice['customer_name'],
            'invoiceCustomerPhone' => $invoice['customer_phone'],
            'invoiceNote' => $invoice['note'],
            'sales' => array_reverse($sales),
            'history' => [
                'today' => [
                    'jy' => $todayJ[0],
                    'jm' => $todayJ[1],
                    'jd' => $todayJ[2]
                ],
                'todayJalali' => \JalaliCalendar::formatJalali($todayJ[0], $todayJ[1], $todayJ[2]),
                'weekStartJalali' => \JalaliCalendar::formatJalali($weekStartJ[0], $weekStartJ[1], $weekStartJ[2]),
                'weekEndJalali' => \JalaliCalendar::formatJalali($weekEndJ[0], $weekEndJ[1], $weekEndJ[2]),
                'weekTotal' => $weekTotal,
                'weekCount' => $weekCount,
                'monthTotal' => $monthTotal,
                'monthCount' => $monthCount
            ]
        ]);
    }
    
    private function install(array $payload): void {
        $result = $this->settingsModel->install($payload);
        jsonResponse($result);
    }
    
    private function saveSettings(array $payload): void {
        $settings = $this->settingsModel->get();
        $existingLocked = !empty($settings['currency_locked']);
        $existingCurrency = isset($settings['currency']) ? (string)$settings['currency'] : 'IRT';
        $submittedCurrency = trim((string)($payload['currency'] ?? $existingCurrency));
        
        if (!empty($settings['installed']) && $existingLocked) {
            $currency = $existingCurrency;
        } else {
            $currency = $submittedCurrency;
        }
        
        $vat = isset($payload['vat']) && is_numeric($payload['vat']) ? (float)$payload['vat'] : 0;
        if ($vat < 0) $vat = 0;
        
        $theme = trim((string)($payload['theme'] ?? 'light'));
        if (!in_array($theme, ['light', 'dark'])) $theme = 'light';
        
        $settings = [
            'name' => trim((string)($payload['name'] ?? '')),
            'phone' => trim((string)($payload['phone'] ?? '')),
            'address' => trim((string)($payload['address'] ?? '')),
            'currency' => $currency,
            'language' => trim((string)($payload['language'] ?? 'fa')),
            'calendar' => trim((string)($payload['calendar'] ?? 'jalali')),
            'installed' => !empty($settings['installed']),
            'currency_locked' => $existingLocked,
            'vat' => $vat,
            'theme' => $theme
        ];
        
        if ($this->settingsModel->save($settings)) {
            jsonResponse(['ok' => true]);
        } else {
            jsonResponse(['ok' => false, 'error' => 'خطا در ذخیره تنظیمات']);
        }
    }
    
    private function toggleTheme(array $payload): void {
        $settings = $this->settingsModel->get();
        $theme = trim((string)($payload['theme'] ?? 'light'));
        if (!in_array($theme, ['light', 'dark'])) $theme = 'light';
        
        $settings['theme'] = $theme;
        
        if ($this->settingsModel->save($settings)) {
            jsonResponse(['ok' => true]);
        } else {
            jsonResponse(['ok' => false, 'error' => 'خطا در ذخیره تم']);
        }
    }
    
    private function addCategory(array $payload): void {
        $name = trim((string)($payload['name'] ?? ''));
        $result = $this->categoryModel->add($name);
        jsonResponse($result);
    }
    
    private function deleteCategory(array $payload): void {
        $id = (string)($payload['id'] ?? '');
        $result = $this->categoryModel->delete($id);
        jsonResponse($result);
    }
    
    private function addProduct(array $payload): void {
        $name = trim((string)($payload['name'] ?? ''));
        $category = trim((string)($payload['category'] ?? ''));
        $price = null;
        
        if (isset($payload['price']) && is_numeric($payload['price'])) {
            $price = (float)$payload['price'];
        }
        
        $result = $this->productModel->add($name, $category, $price);
        jsonResponse($result);
    }
    
    private function deleteProduct(array $payload): void {
        $id = (string)($payload['id'] ?? '');
        $result = $this->productModel->delete($id);
        jsonResponse($result);
    }
    
    private function saveInvoice(array $payload): void {
        $result = $this->invoiceModel->save($payload);
        jsonResponse($result);
    }
    
    private function recordSale(array $payload): void {
        $settings = $this->settingsModel->get();
        $result = $this->saleModel->record($payload, $settings);
        jsonResponse($result);
    }
    
    private function exportBackup(): void {
        $settings = $this->settingsModel->get();
        $categories = $this->categoryModel->getAll();
        $products = $this->productModel->getAll();
        $sales = $this->saleModel->getAll();
        
        jsonResponse([
            'ok' => true,
            'backup' => [
                'settings' => $settings,
                'categories' => $categories,
                'products' => $products,
                'sales' => $sales,
                'exported_at' => date('Y-m-d H:i:s')
            ]
        ]);
    }
}
