<?php
namespace App\Models;

/**
 * مدل مدیریت فاکتور و فروش
 */
class InvoiceModel extends BaseModel
{
    private $invoiceFile = 'invoice.json';
    private $salesFile = 'sales.json';

    /**
     * دریافت فاکتور جاری
     * @return array
     */
    public function getCurrentInvoice()
    {
        return $this->readJsonFile($this->invoiceFile, ['items' => [], 'discount' => 0]);
    }

    /**
     * افزودن آیتم به فاکتور
     * @param array $item آیتم فاکتور
     * @return bool
     */
    public function addItem($item)
    {
        $invoice = $this->getCurrentInvoice();
        $invoice['items'][] = $item;
        return $this->writeJsonFile($this->invoiceFile, $invoice);
    }

    /**
     * حذف آیتم از فاکتور
     * @param int $index ایندکس آیتم
     * @return bool
     */
    public function removeItem($index)
    {
        $invoice = $this->getCurrentInvoice();
        
        if (isset($invoice['items'][$index])) {
            unset($invoice['items'][$index]);
            $invoice['items'] = array_values($invoice['items']);
            return $this->writeJsonFile($this->invoiceFile, $invoice);
        }
        
        return false;
    }

    /**
     * اعمال تخفیف
     * @param int $discount مبلغ تخفیف
     * @return bool
     */
    public function setDiscount($discount)
    {
        $invoice = $this->getCurrentInvoice();
        $invoice['discount'] = (int)$discount;
        return $this->writeJsonFile($this->invoiceFile, $invoice);
    }

    /**
     * محاسبه جمع کل فاکتور
     * @return array
     */
    public function calculateTotal()
    {
        $invoice = $this->getCurrentInvoice();
        $subtotal = 0;
        
        foreach ($invoice['items'] as $item) {
            $subtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
        }
        
        $total = $subtotal - ($invoice['discount'] ?? 0);
        
        return [
            'subtotal' => $subtotal,
            'discount' => $invoice['discount'] ?? 0,
            'total' => $total,
            'items_count' => count($invoice['items'])
        ];
    }

    /**
     * ثبت نهایی فاکتور و ذخیره در تاریخچه فروش
     * @param string $invoiceNumber شماره فاکتور
     * @return bool
     */
    public function finalizeInvoice($invoiceNumber)
    {
        $invoice = $this->getCurrentInvoice();
        $sales = $this->readJsonFile($this->salesFile, []);
        
        $sale = [
            'id' => $this->createId(),
            'invoice_number' => $invoiceNumber,
            'items' => $invoice['items'],
            'discount' => $invoice['discount'],
            'total' => $this->calculateTotal()['total'],
            'date' => date('Y-m-d'),
            'datetime' => date('Y-m-d H:i:s'),
            'jalali_date' => $this->toJalali(date('Y-m-d'))
        ];
        
        $sales[] = $sale;
        $this->writeJsonFile($this->salesFile, $sales);
        
        // پاک کردن فاکتور جاری
        $this->writeJsonFile($this->invoiceFile, ['items' => [], 'discount' => 0]);
        
        return true;
    }

    /**
     * دریافت تمام فروش‌ها
     * @return array
     */
    public function getAllSales()
    {
        return $this->readJsonFile($this->salesFile, []);
    }

    /**
     * دریافت فروش‌های یک روز خاص
     * @param string $date تاریخ شمسی
     * @return array
     */
    public function getSalesByDate($date)
    {
        $sales = $this->getAllSales();
        return array_filter($sales, function($sale) use ($date) {
            return $sale['jalali_date'] === $date;
        });
    }

    /**
     * تبدیل تاریخ میلادی به شمسی
     * @param string $gregorianDate تاریخ میلادی
     * @return string
     */
    private function toJalali($gregorianDate)
    {
        list($gy, $gm, $gd) = explode('-', $gregorianDate);
        
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $jy = ($gy <= 1600) ? 0 : 979;
        $gy -= ($gy <= 1600) ? 621 : 1600;
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = (365 * $gy) + intval(($gy2 + 3) / 4) - intval(($gy2 + 99) / 100) + intval(($gy2 + 399) / 400) - 80 + $gd + $g_d_m[$gm - 1];
        $jy += 33 * intval($days / 12053);
        $days %= 12053;
        $jy += 4 * intval($days / 1461);
        $days %= 1461;
        
        $jy += intval(($days - 1) / 365);
        if ($days > 365) $days = ($days - 1) % 365;
        
        $jm = ($days < 186) ? 1 + intval($days / 31) : 7 + intval(($days - 186) / 30);
        $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
        
        return sprintf('%04d-%02d-%02d', $jy, $jm, $jd);
    }

    /**
     * دریافت آمار فروش
     * @return array
     */
    public function getSalesStats()
    {
        $sales = $this->getAllSales();
        $today = date('Y-m-d');
        $todayJalali = $this->toJalali($today);
        
        $todaySales = 0;
        $weekSales = 0;
        $monthSales = 0;
        
        foreach ($sales as $sale) {
            // فروش امروز
            if ($sale['jalali_date'] === $todayJalali) {
                $todaySales += $sale['total'];
            }
            
            // فروش ماه جاری
            if (substr($sale['jalali_date'], 0, 7) === substr($todayJalali, 0, 7)) {
                $monthSales += $sale['total'];
            }
        }
        
        // فروش هفته (۷ روز آخر)
        $weekAgo = date('Y-m-d', strtotime('-7 days'));
        foreach ($sales as $sale) {
            if ($sale['date'] >= $weekAgo) {
                $weekSales += $sale['total'];
            }
        }
        
        return [
            'today' => $todaySales,
            'week' => $weekSales,
            'month' => $monthSales,
            'total_sales' => count($sales)
        ];
    }
}
