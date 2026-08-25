<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/JalaliCalendar.php';

/**
 * Sale Model
 */

class SaleModel {
    private Database $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getAll(): array {
        return $this->db->read('sales', []);
    }
    
    public function record(array $payload, array $settings): array {
        $items = $payload['items'] ?? [];
        $discount = isset($payload['discount']) && is_numeric($payload['discount']) ? (float)$payload['discount'] : 0;
        $discountType = trim((string)($payload['discount_type'] ?? 'amount'));
        $invoiceId = trim((string)($payload['invoice_id'] ?? ''));
        $invoiceNumber = trim((string)($payload['invoice_number'] ?? ''));
        $customerName = trim((string)($payload['customer_name'] ?? ''));
        $customerPhone = trim((string)($payload['customer_phone'] ?? ''));
        $note = trim((string)($payload['note'] ?? ''));
        
        if (!in_array($discountType, ['amount', 'percent'])) {
            $discountType = 'amount';
        }
        
        if ($invoiceId === '') {
            $invoiceId = 'RSA-ID-' . strtoupper(bin2hex(random_bytes(2)));
        }
        
        $sales = $this->getAll();
        if ($invoiceNumber === '') {
            $invoiceNumber = $this->generateInvoiceNumber($sales);
        }
        
        if ($discount < 0) $discount = 0;
        
        if (!is_array($items)) {
            return ['ok' => false, 'error' => 'لیست فاکتور معتبر نیست'];
        }
        
        $cleanItems = [];
        $subtotal = 0;
        
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
            
            $subtotal += $price * $quantity;
        }
        
        if (!$cleanItems) {
            return ['ok' => false, 'error' => 'فاکتور خالی است'];
        }
        
        // Calculate discount
        $discountAmount = 0;
        if ($discountType === 'percent') {
            if ($discount > 100) $discount = 100;
            $discountAmount = $subtotal * $discount / 100;
        } else {
            if ($discount > $subtotal) $discount = $subtotal;
            $discountAmount = $discount;
        }
        
        // Calculate VAT
        $vatRate = isset($settings['vat']) && is_numeric($settings['vat']) ? (float)$settings['vat'] : 0;
        if ($vatRate < 0) $vatRate = 0;
        $vatBase = $subtotal - $discountAmount;
        $vatAmount = $vatBase * $vatRate / 100;
        $total = $vatBase + $vatAmount;
        
        // Get current date
        $now = new DateTime();
        $gy = (int)$now->format('Y');
        $gm = (int)$now->format('n');
        $gd = (int)$now->format('j');
        $j = JalaliCalendar::gregorianToJalali($gy, $gm, $gd);
        
        $sale = [
            'id' => bin2hex(random_bytes(8)),
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoiceNumber,
            'datetime' => $now->format('Y-m-d H:i:s'),
            'date' => $now->format('Y-m-d'),
            'jy' => $j[0],
            'jm' => $j[1],
            'jd' => $j[2],
            'jalali' => JalaliCalendar::formatJalali($j[0], $j[1], $j[2]),
            'items' => $cleanItems,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'note' => $note,
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'discount_value' => $discount,
            'discount_type' => $discountType,
            'vat_rate' => $vatRate,
            'vat' => $vatAmount,
            'total' => $total
        ];
        
        $sales[] = $sale;
        
        if ($this->db->write('sales', array_values($sales))) {
            return ['ok' => true, 'invoice_id' => $invoiceId, 'invoice_number' => $invoiceNumber];
        }
        
        return ['ok' => false, 'error' => 'خطا در ثبت فروش'];
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
