<?php
require_once __DIR__ . '/../core/Database.php';

/**
 * Invoice Model
 */

class InvoiceModel {
    private Database $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function get(): array {
        $invoiceData = $this->db->read('invoice', [
            'items' => [],
            'discount' => 0,
            'discount_type' => 'amount',
            'invoice_id' => '',
            'invoice_number' => '',
            'customer_name' => '',
            'customer_phone' => '',
            'note' => ''
        ]);
        
        $result = [
            'items' => [],
            'discount' => 0,
            'discount_type' => 'amount',
            'invoice_id' => '',
            'invoice_number' => '',
            'customer_name' => '',
            'customer_phone' => '',
            'note' => ''
        ];
        
        if (is_array($invoiceData)) {
            if (isset($invoiceData['items']) && is_array($invoiceData['items'])) {
                $result['items'] = $invoiceData['items'];
            } else {
                $result['items'] = $invoiceData;
            }
            
            if (isset($invoiceData['discount']) && is_numeric($invoiceData['discount'])) {
                $result['discount'] = (float)$invoiceData['discount'];
            }
            if (isset($invoiceData['discount_type'])) {
                $result['discount_type'] = (string)$invoiceData['discount_type'];
            }
            if (isset($invoiceData['invoice_id'])) {
                $result['invoice_id'] = (string)$invoiceData['invoice_id'];
            }
            if (isset($invoiceData['invoice_number'])) {
                $result['invoice_number'] = (string)$invoiceData['invoice_number'];
            }
            if (isset($invoiceData['customer_name'])) {
                $result['customer_name'] = (string)$invoiceData['customer_name'];
            }
            if (isset($invoiceData['customer_phone'])) {
                $result['customer_phone'] = (string)$invoiceData['customer_phone'];
            }
            if (isset($invoiceData['note'])) {
                $result['note'] = (string)$invoiceData['note'];
            }
        }
        
        // Generate IDs if empty
        if ($result['invoice_id'] === '') {
            $result['invoice_id'] = 'RSA-ID-' . strtoupper(bin2hex(random_bytes(2)));
            $sales = $this->db->read('sales', []);
            $result['invoice_number'] = $this->generateInvoiceNumber($sales);
        }
        
        return $result;
    }
    
    public function save(array $data): array {
        $items = $data['items'] ?? [];
        $discount = isset($data['discount']) && is_numeric($data['discount']) ? (float)$data['discount'] : 0;
        $discountType = trim((string)($data['discount_type'] ?? 'amount'));
        $invoiceId = trim((string)($data['invoice_id'] ?? ''));
        $invoiceNumber = trim((string)($data['invoice_number'] ?? ''));
        $customerName = trim((string)($data['customer_name'] ?? ''));
        $customerPhone = trim((string)($data['customer_phone'] ?? ''));
        $note = trim((string)($data['note'] ?? ''));
        
        if (!in_array($discountType, ['amount', 'percent'])) {
            $discountType = 'amount';
        }
        
        if ($invoiceId === '') {
            $invoiceId = 'RSA-ID-' . strtoupper(bin2hex(random_bytes(2)));
        }
        
        $sales = $this->db->read('sales', []);
        if ($invoiceNumber === '') {
            $invoiceNumber = $this->generateInvoiceNumber($sales);
        }
        
        if ($discount < 0) $discount = 0;
        
        if (!is_array($items)) {
            return ['ok' => false, 'error' => 'لیست فاکتور معتبر نیست'];
        }
        
        $cleanItems = [];
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            
            $name = trim((string)($item['name'] ?? ''));
            $category = trim((string)($item['category'] ?? ''));
            $price = null;
            
            if (isset($item['price']) && is_numeric($item['price'])) {
                $price = (float)$item['price'];
            }
            
            $quantity = (int)($item['quantity'] ?? 0);
            
            if ($name === '' || $price === null || $price < 0 || $quantity < 1) continue;
            
            $cleanItems[] = [
                'name' => $name,
                'category' => $category,
                'price' => $price,
                'quantity' => $quantity
            ];
        }
        
        $invoiceData = [
            'items' => $cleanItems,
            'discount' => $discount,
            'discount_type' => $discountType,
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoiceNumber,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'note' => $note
        ];
        
        if ($this->db->write('invoice', $invoiceData)) {
            return ['ok' => true, 'invoice_id' => $invoiceId, 'invoice_number' => $invoiceNumber];
        }
        
        return ['ok' => false, 'error' => 'خطا در ذخیره فاکتور'];
    }
    
    private function generateInvoiceNumber(array $sales): string {
        $maxNum = 0;
        foreach ($sales as $sale) {
            if (isset($sale['invoice_number']) && preg_match('/^RSA-(\d+)$/', $sale['invoice_number'], $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxNum) $maxNum = $num;
            }
        }
        return 'RSA-' . str_pad($maxNum + 1, 8, '0', STR_PAD_LEFT);
    }
}
