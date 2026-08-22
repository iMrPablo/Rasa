<?php
$storageDirectory = __DIR__ . '/rasa_data';
if (!is_dir($storageDirectory)) {
    @mkdir($storageDirectory, 0775, true);
}

$settingsFile = $storageDirectory . '/settings.json';
$categoriesFile = $storageDirectory . '/categories.json';
$productsFile = $storageDirectory . '/products.json';
$invoiceFile = $storageDirectory . '/invoice.json';
$salesFile = $storageDirectory . '/sales.json';
$htaccessFile = $storageDirectory . '/.htaccess';

if (!file_exists($htaccessFile)) {
    @file_put_contents($htaccessFile, "Require all denied");
}

if (!file_exists($settingsFile)) {
    @file_put_contents(
        $settingsFile,
        json_encode(['name' => '', 'phone' => '', 'address' => ''], JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

if (!file_exists($categoriesFile)) {
    @file_put_contents($categoriesFile, json_encode([], JSON_UNESCAPED_UNICODE), LOCK_EX);
}

if (!file_exists($productsFile)) {
    @file_put_contents($productsFile, json_encode([], JSON_UNESCAPED_UNICODE), LOCK_EX);
}

if (!file_exists($invoiceFile)) {
    @file_put_contents(
        $invoiceFile,
        json_encode(['items' => [], 'discount' => 0], JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

if (!file_exists($salesFile)) {
    @file_put_contents($salesFile, json_encode([], JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function readJsonFile($file, $default)
{
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

function writeJsonFile($file, $data)
{
    return @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
}

function createId()
{
    return function_exists('random_bytes') ? bin2hex(random_bytes(8)) : uniqid('', true);
}

function jsonResponse($data)
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jalDiv($a, $b)
{
    return intdiv((int)$a, (int)$b);
}

function jalMod($a, $b)
{
    $a = (int)$a;
    $b = (int)$b;
    return $a - intdiv($a, $b) * $b;
}

function jalCal($jy)
{
    $breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
    $gy = $jy + 621;
    $leapJ = -14;
    $jp = $breaks[0];
    $jump = 0;
    $bl = count($breaks);

    for ($i = 1; $i < $bl; $i++) {
        $jm = $breaks[$i];
        $jump = $jm - $jp;

        if ($jy < $jm) {
            break;
        }

        $leapJ = $leapJ + jalDiv($jump, 33) * 8 + jalDiv(jalMod($jump, 33), 4);
        $jp = $jm;
    }

    $n = $jy - $jp;
    $leapJ = $leapJ + jalDiv($n, 33) * 8 + jalDiv(jalMod($n, 33) + 3, 4);

    if (jalMod($jump, 33) === 4 && $jump - $n === 4) {
        $leapJ += 1;
    }

    $leapG = jalDiv($gy, 4) - jalDiv((jalDiv($gy, 100) + 1) * 3, 4) - 150;
    $march = 20 + $leapJ - $leapG;

    if ($jump - $n < 6) {
        $n = $n - $jump + jalDiv($jump + 4, 33) * 33;
    }

    $leap = jalMod(jalMod($n + 1, 33) - 1, 4);

    if ($leap === -1) {
        $leap = 4;
    }

    return ['leap' => $leap, 'gy' => $gy, 'march' => $march];
}

function g2d($gy, $gm, $gd)
{
    return jalDiv(($gy + jalDiv($gm - 8, 6) + 100100) * 1461, 4)
        + jalDiv(153 * jalMod($gm + 9, 12) + 2, 5)
        + $gd - 34840408
        - jalDiv(jalDiv($gy + 100100 + jalDiv($gm - 8, 6), 100) * 3, 4)
        + 752;
}

function d2g($jdn)
{
    $j = 4 * $jdn + 139361631;
    $j = $j + jalDiv(jalDiv(4 * $jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
    $i = jalDiv(jalMod($j, 1461), 4) * 5 + 308;
    $gd = jalDiv(jalMod($i, 153), 5) + 1;
    $gm = jalMod(jalDiv($i, 153), 12) + 1;
    $gy = jalDiv($j, 1461) - 100100 + jalDiv(8 - $gm, 6);

    return ['gy' => $gy, 'gm' => $gm, 'gd' => $gd];
}

function j2d($jy, $jm, $jd)
{
    $r = jalCal($jy);
    return g2d($r['gy'], 3, $r['march']) + ($jm - 1) * 31 - jalDiv($jm, 7) * ($jm - 7) + $jd - 1;
}

function d2j($jdn)
{
    $g = d2g($jdn);
    $gy = $g['gy'];
    $jy = $gy - 621;
    $r = jalCal($jy);
    $jdn1f = g2d($gy, 3, $r['march']);
    $k = $jdn - $jdn1f;

    if ($k >= 0) {
        if ($k <= 185) {
            return [
                'jy' => $jy,
                'jm' => 1 + jalDiv($k, 31),
                'jd' => jalMod($k, 31) + 1
            ];
        }

        $k -= 186;
    } else {
        $jy -= 1;
        $k += 179;

        if ($r['leap'] === 1) {
            $k += 1;
        }
    }

    return [
        'jy' => $jy,
        'jm' => 7 + jalDiv($k, 30),
        'jd' => jalMod($k, 30) + 1
    ];
}

function gregorianToJalali($gy, $gm, $gd)
{
    $d = d2j(g2d($gy, $gm, $gd));
    return [$d['jy'], $d['jm'], $d['jd']];
}

function formatJalali($jy, $jm, $jd)
{
    return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
}

if (isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_GET['action'] ?? '';
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        $payload = [];
    }

    $settings = readJsonFile($settingsFile, ['name' => '', 'phone' => '', 'address' => '']);
    $categories = readJsonFile($categoriesFile, []);
    $products = readJsonFile($productsFile, []);
    $sales = readJsonFile($salesFile, []);

    $invoiceData = readJsonFile($invoiceFile, ['items' => [], 'discount' => 0]);
    $invoiceItems = [];
    $invoiceDiscount = 0;

    if (is_array($invoiceData)) {
        if (isset($invoiceData['items']) && is_array($invoiceData['items'])) {
            $invoiceItems = $invoiceData['items'];
        } else {
            $invoiceItems = $invoiceData;
        }

        if (isset($invoiceData['discount']) && is_numeric($invoiceData['discount'])) {
            $invoiceDiscount = (float)$invoiceData['discount'];
        }
    }

    if ($action === 'get') {
        $now = new DateTime();
        $todayGy = (int)$now->format('Y');
        $todayGm = (int)$now->format('n');
        $todayGd = (int)$now->format('j');
        $todayJ = gregorianToJalali($todayGy, $todayGm, $todayGd);

        $weekday = (int)$now->format('N');
        $daysSinceSaturday = ($weekday + 1) % 7;

        $weekStart = clone $now;
        $weekStart->setTime(0, 0, 0);
        if ($daysSinceSaturday > 0) {
            $weekStart->modify('-' . $daysSinceSaturday . ' days');
        }

        $weekEnd = clone $weekStart;
        $weekEnd->modify('+6 days');
        $weekEnd->setTime(23, 59, 59);

        $weekStartDate = $weekStart->format('Y-m-d');
        $weekEndDate = $weekEnd->format('Y-m-d');

        $weekStartJ = gregorianToJalali((int)$weekStart->format('Y'), (int)$weekStart->format('n'), (int)$weekStart->format('j'));
        $weekEndJ = gregorianToJalali((int)$weekEnd->format('Y'), (int)$weekEnd->format('n'), (int)$weekEnd->format('j'));

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
            'invoiceItems' => $invoiceItems,
            'invoiceDiscount' => $invoiceDiscount,
            'sales' => array_reverse($sales),
            'history' => [
                'today' => [
                    'jy' => $todayJ[0],
                    'jm' => $todayJ[1],
                    'jd' => $todayJ[2]
                ],
                'todayJalali' => formatJalali($todayJ[0], $todayJ[1], $todayJ[2]),
                'weekStartJalali' => formatJalali($weekStartJ[0], $weekStartJ[1], $weekStartJ[2]),
                'weekEndJalali' => formatJalali($weekEndJ[0], $weekEndJ[1], $weekEndJ[2]),
                'weekTotal' => $weekTotal,
                'weekCount' => $weekCount,
                'monthTotal' => $monthTotal,
                'monthCount' => $monthCount
            ]
        ]);
    }

    if ($action === 'save_settings') {
        $settings = [
            'name' => trim((string)($payload['name'] ?? '')),
            'phone' => trim((string)($payload['phone'] ?? '')),
            'address' => trim((string)($payload['address'] ?? ''))
        ];

        if (!writeJsonFile($settingsFile, $settings)) {
            jsonResponse(['ok' => false, 'error' => 'خطا در ذخیره تنظیمات']);
        }

        jsonResponse(['ok' => true]);
    }

    if ($action === 'add_category') {
        $name = trim((string)($payload['name'] ?? ''));

        if ($name === '') {
            jsonResponse(['ok' => false, 'error' => 'نام دسته‌بندی را وارد کنید']);
        }

        foreach ($categories as $categoryItem) {
            if (trim((string)($categoryItem['name'] ?? '')) === $name) {
                jsonResponse(['ok' => false, 'error' => 'این دسته‌بندی قبلا ثبت شده است']);
            }
        }

        $categories[] = [
            'id' => createId(),
            'name' => $name
        ];

        if (!writeJsonFile($categoriesFile, array_values($categories))) {
            jsonResponse(['ok' => false, 'error' => 'خطا در ذخیره دسته‌بندی']);
        }

        jsonResponse(['ok' => true]);
    }

    if ($action === 'delete_category') {
        $id = (string)($payload['id'] ?? '');

        $categories = array_values(array_filter($categories, function ($item) use ($id) {
            return ($item['id'] ?? '') !== $id;
        }));

        if (!writeJsonFile($categoriesFile, $categories)) {
            jsonResponse(['ok' => false, 'error' => 'خطا در حذف دسته‌بندی']);
        }

        jsonResponse(['ok' => true]);
    }

    if ($action === 'add_product') {
        $name = trim((string)($payload['name'] ?? ''));
        $category = trim((string)($payload['category'] ?? ''));
        $price = null;

        if (isset($payload['price']) && is_numeric($payload['price'])) {
            $price = (float)$payload['price'];
        }

        if ($name === '' || $category === '' || $price === null || $price < 0) {
            jsonResponse(['ok' => false, 'error' => 'نام، دسته‌بندی و قیمت معتبر وارد کنید']);
        }

        $products[] = [
            'id' => createId(),
            'name' => $name,
            'category' => $category,
            'price' => $price
        ];

        if (!writeJsonFile($productsFile, array_values($products))) {
            jsonResponse(['ok' => false, 'error' => 'خطا در ذخیره محصول']);
        }

        jsonResponse(['ok' => true]);
    }

    if ($action === 'delete_product') {
        $id = (string)($payload['id'] ?? '');

        $products = array_values(array_filter($products, function ($item) use ($id) {
            return ($item['id'] ?? '') !== $id;
        }));

        if (!writeJsonFile($productsFile, $products)) {
            jsonResponse(['ok' => false, 'error' => 'خطا در حذف محصول']);
        }

        jsonResponse(['ok' => true]);
    }

    if ($action === 'save_invoice') {
        $items = $payload['items'] ?? [];
        $discount = isset($payload['discount']) && is_numeric($payload['discount']) ? (float)$payload['discount'] : 0;

        if ($discount < 0) {
            $discount = 0;
        }

        if (!is_array($items)) {
            jsonResponse(['ok' => false, 'error' => 'لیست فاکتور معتبر نیست']);
        }

        $cleanItems = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $name = trim((string)($item['name'] ?? ''));
            $category = trim((string)($item['category'] ?? ''));
            $price = null;

            if (isset($item['price']) && is_numeric($item['price'])) {
                $price = (float)$item['price'];
            }

            $quantity = (int)($item['quantity'] ?? 0);

            if ($name === '' || $price === null || $price < 0 || $quantity < 1) {
                continue;
            }

            $cleanItems[] = [
                'name' => $name,
                'category' => $category,
                'price' => $price,
                'quantity' => $quantity
            ];
        }

        if (!writeJsonFile($invoiceFile, ['items' => $cleanItems, 'discount' => $discount])) {
            jsonResponse(['ok' => false, 'error' => 'خطا در ذخیره فاکتور']);
        }

        jsonResponse(['ok' => true]);
    }

    if ($action === 'record_sale') {
        $items = $payload['items'] ?? [];
        $discount = isset($payload['discount']) && is_numeric($payload['discount']) ? (float)$payload['discount'] : 0;

        if ($discount < 0) {
            $discount = 0;
        }

        if (!is_array($items)) {
            jsonResponse(['ok' => false, 'error' => 'لیست فاکتور معتبر نیست']);
        }

        $cleanItems = [];
        $subtotal = 0;

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $name = trim((string)($item['name'] ?? ''));
            $category = trim((string)($item['category'] ?? ''));
            $price = null;

            if (isset($item['price']) && is_numeric($item['price'])) {
                $price = (float)$item['price'];
            }

            $quantity = (int)($item['quantity'] ?? 0);

            if ($name === '' || $price === null || $price < 0 || $quantity < 1) {
                continue;
            }

            $cleanItems[] = [
                'name' => $name,
                'category' => $category,
                'price' => $price,
                'quantity' => $quantity
            ];

            $subtotal += $price * $quantity;
        }

        if (!$cleanItems) {
            jsonResponse(['ok' => false, 'error' => 'فاکتور خالی است']);
        }

        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        $total = $subtotal - $discount;

        $now = new DateTime();
        $gy = (int)$now->format('Y');
        $gm = (int)$now->format('n');
        $gd = (int)$now->format('j');
        $j = gregorianToJalali($gy, $gm, $gd);

        $sales[] = [
            'id' => createId(),
            'datetime' => $now->format('Y-m-d H:i:s'),
            'date' => $now->format('Y-m-d'),
            'jy' => $j[0],
            'jm' => $j[1],
            'jd' => $j[2],
            'jalali' => formatJalali($j[0], $j[1], $j[2]),
            'items' => $cleanItems,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total
        ];

        if (!writeJsonFile($salesFile, array_values($sales))) {
            jsonResponse(['ok' => false, 'error' => 'خطا در ثبت فروش']);
        }

        jsonResponse(['ok' => true]);
    }

    jsonResponse(['ok' => false, 'error' => 'درخواست نامعتبر است']);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل رسا - نسخه 1.7</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Tahoma, sans-serif;
            background: #fafafa;
            color: #1f1f1f;
            padding: 20px;
        }

        .container {
            max-width: 1080px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e8e8e8;
            border-radius: 16px;
            overflow: hidden;
        }

        .header {
            padding: 24px;
            text-align: center;
            border-bottom: 1px solid #efefef;
            background: #fff;
        }

        .header h1 {
            font-size: 26px;
            color: #111;
        }

        .header div {
            margin-top: 6px;
            color: #8a8a8a;
            font-size: 12px;
        }

        .tabs {
            display: flex;
            background: #fff;
            border-bottom: 1px solid #efefef;
            padding: 0 8px;
        }

        .tab {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            border: none;
            background: none;
            padding: 14px 6px;
            font-size: 13px;
            color: #777;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }

        .tab:hover {
            color: #111;
        }

        .tab.active {
            background: #fff;
            color: #111;
            font-weight: 700;
            border-bottom-color: #111;
        }

        .content {
            padding: 24px;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        h2 {
            font-size: 18px;
            margin-bottom: 16px;
            color: #111;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 700;
            color: #555;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            background: #fff;
            color: #222;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #b5b5b5;
        }

        .btn {
            border: 1px solid #111;
            background: #111;
            color: #fff;
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .btn:hover {
            opacity: 0.88;
        }

        .btn-danger {
            background: #fff;
            border-color: #ffc9c9;
            color: #d64545;
            padding: 8px;
        }

        .btn-danger:hover {
            background: #fff5f5;
            border-color: #d64545;
            color: #d64545;
            opacity: 1;
        }

        .card {
            border: 1px solid #efefef;
            background: #fff;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            border: 1px solid #f0f0f0;
            background: #fff;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 9px;
        }

        .muted {
            color: #8a8a8a;
            font-size: 12px;
            margin-top: 3px;
        }

        .empty-state {
            border: 1px dashed #e0e0e0;
            color: #999;
            text-align: center;
            padding: 26px;
            border-radius: 14px;
            background: #fff;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            background: #fff;
        }

        .invoice-table th,
        .invoice-table td {
            border: 1px solid #eee;
            padding: 9px;
            text-align: right;
            font-size: 14px;
        }

        .invoice-table th {
            background: #fafafa;
            color: #555;
        }

        .total-section {
            margin-top: 16px;
            border: 1px solid #efefef;
            background: #fff;
            border-radius: 14px;
            padding: 14px;
            text-align: left;
        }

        .total-amount {
            font-size: 18px;
            font-weight: 800;
            color: #111;
            margin-top: 4px;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .stat-card {
            border: 1px solid #efefef;
            background: #fff;
            border-radius: 14px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            border: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #555;
            background: #fafafa;
            flex: none;
        }

        .stat-body {
            flex: 1;
        }

        .stat-label {
            font-size: 13px;
            font-weight: 700;
            color: #555;
        }

        .stat-value {
            font-size: 18px;
            font-weight: 800;
            color: #111;
            margin-top: 6px;
        }

        .report-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .report-filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .report-filters .form-group {
            width: 130px;
            margin-bottom: 0;
        }

        .report-summary {
            margin: 14px 0;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .calendar-nav {
            border: 1px solid #e8e8e8;
            background: #fff;
            border-radius: 9px;
            padding: 8px 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #444;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
            text-align: center;
        }

        .calendar-day-name {
            font-size: 12px;
            font-weight: 700;
            color: #8a8a8a;
            padding: 6px 0;
        }

        .calendar-day {
            border: 1px solid #f2f2f2;
            border-radius: 10px;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            background: #fff;
            position: relative;
        }

        .calendar-day.empty {
            background: transparent;
            border: none;
        }

        .calendar-day.today {
            border-color: #111;
            font-weight: 800;
        }

        .calendar-day.has-sale::after {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 50%;
            transform: translateX(-50%);
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #28a745;
        }

        .icon {
            width: 16px;
            height: 16px;
            flex: none;
        }

        .btn-danger .icon {
            width: 15px;
            height: 15px;
        }

        #printArea {
            display: none;
        }

        @page {
            size: A4;
            margin: 0;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            #app {
                display: none !important;
            }

            #printArea {
                display: block !important;
                padding: 12mm;
                color: #000;
            }

            .print-header {
                text-align: center;
                border-bottom: 1px solid #000;
                padding-bottom: 12px;
                margin-bottom: 16px;
            }

            .print-header h1 {
                font-size: 24px;
                margin-bottom: 7px;
            }

            .print-header p {
                margin: 4px 0;
                font-size: 13px;
            }

            .print-title {
                text-align: center;
                font-size: 18px;
                margin-bottom: 10px;
                font-weight: 700;
            }

            .print-subtitle {
                text-align: center;
                font-size: 14px;
                margin-bottom: 12px;
                font-weight: 700;
            }

            .print-summary {
                border: 1px solid #000;
                padding: 10px;
                margin-bottom: 12px;
                display: grid;
                gap: 6px;
            }

            .print-summary div {
                font-size: 13px;
            }

            #printArea .invoice-table {
                width: 100%;
                border-collapse: collapse;
            }

            #printArea .invoice-table th,
            #printArea .invoice-table td {
                border: 1px solid #000;
                padding: 8px;
                text-align: right;
                font-size: 13px;
                color: #000;
            }

            #printArea .invoice-table th {
                background: #f3f3f3;
            }

            #printArea .total-section {
                margin-top: 16px;
                border: 1px solid #000;
                border-radius: 0;
                padding: 12px;
                text-align: left;
                background: #fff;
            }

            #printArea .total-amount {
                color: #000;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div id="app" class="container">
        <div class="header">
            <h1>پنل رسا</h1>
            <div>نسخه 1.7 - طراح و توسعه دهنده : آقای پابلو</div>
        </div>

        <div class="tabs">
            <button class="tab active" data-tab="settings" onclick="switchTab('settings')">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21v-7"/><path d="M4 10V3"/><path d="M12 21v-9"/><path d="M12 8V3"/><path d="M20 21v-5"/><path d="M20 12V3"/><path d="M1 14h6"/><path d="M9 8h6"/><path d="M17 16h6"/></svg>
                تنظیمات
            </button>

            <button class="tab" data-tab="categories" onclick="switchTab('categories')">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                دسته‌بندی‌ها
            </button>

            <button class="tab" data-tab="products" onclick="switchTab('products')">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v8a2 2 0 0 1-1 1.73l-7 4a2 2 0 0 1-2 0l-7-4A2 2 0 0 1 3 16V8a2 2 0 0 1 1-1.73l7-4a2 2 0 0 1 2 0l7 4A2 2 0 0 1 21 8Z"/><path d="M3.3 7 12 12l8.7-5"/><path d="M12 22V12"/></svg>
                محصولات
            </button>

            <button class="tab" data-tab="invoice" onclick="switchTab('invoice')">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
                ساخت فاکتور
            </button>

            <button class="tab" data-tab="history" onclick="switchTab('history')">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/></svg>
                فروش و تقویم
            </button>
        </div>

        <div class="content">
            <div id="settings" class="tab-content active">
                <h2>اطلاعات فروشگاه</h2>

                <div class="form-group">
                    <label>نام فروشگاه</label>
                    <input type="text" id="storeName">
                </div>

                <div class="form-group">
                    <label>شماره تماس</label>
                    <input type="text" id="storePhone">
                </div>

                <div class="form-group">
                    <label>آدرس</label>
                    <textarea id="storeAddress" rows="3"></textarea>
                </div>

                <button class="btn" onclick="saveSettings()">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    ذخیره تنظیمات
                </button>
            </div>

            <div id="categories" class="tab-content">
                <h2>مدیریت دسته‌بندی‌ها</h2>

                <div class="form-group">
                    <label>نام دسته‌بندی</label>
                    <input type="text" id="categoryName">
                </div>

                <button class="btn" onclick="addCategory()">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                    افزودن دسته‌بندی
                </button>

                <div id="categoriesList" style="margin-top: 20px;"></div>
            </div>

            <div id="products" class="tab-content">
                <h2>مدیریت محصولات</h2>

                <div class="form-group">
                    <label>نام محصول</label>
                    <input type="text" id="productName">
                </div>

                <div class="form-group">
                    <label>دسته‌بندی</label>
                    <select id="productCategory"></select>
                </div>

                <div class="form-group">
                    <label>قیمت (تومان)</label>
                    <input type="number" id="productPrice" min="0" step="any">
                </div>

                <button class="btn" onclick="addProduct()">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                    افزودن محصول
                </button>

                <div id="productsList" style="margin-top: 20px;"></div>
            </div>

            <div id="invoice" class="tab-content">
                <h2>ساخت فاکتور</h2>

                <div class="card">
                    <div class="form-group">
                        <label>فیلتر دسته‌بندی</label>
                        <select id="invoiceCategory" onchange="renderInvoiceProductSelect()"></select>
                    </div>

                    <div class="form-group">
                        <label>انتخاب محصول</label>
                        <select id="invoiceProduct"></select>
                    </div>

                    <div class="form-group">
                        <label>یا نام محصول دستی</label>
                        <input type="text" id="manualProductName">
                    </div>

                    <div class="form-group">
                        <label>دسته‌بندی محصول دستی</label>
                        <select id="manualProductCategory"></select>
                    </div>

                    <div class="form-group">
                        <label>قیمت دستی (تومان)</label>
                        <input type="number" id="manualProductPrice" min="0" step="any">
                    </div>

                    <div class="form-group">
                        <label>تعداد</label>
                        <input type="number" id="productQuantity" value="1" min="1">
                    </div>

                    <button class="btn" onclick="addToInvoice()">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                        افزودن به فاکتور
                    </button>
                </div>

                <div id="invoiceItems"></div>

                <div class="card" style="margin-top: 16px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>تخفیف (تومان)</label>
                        <input type="number" id="invoiceDiscount" min="0" step="any" value="0" oninput="onDiscountInput()">
                    </div>
                </div>

                <div id="totalSection" class="total-section" style="display: none;">
                    <div>جمع کل:</div>
                    <div id="totalAmount" class="total-amount">0 تومان</div>

                    <div style="margin-top: 8px;">تخفیف:</div>
                    <div id="discountAmount" class="total-amount">0 تومان</div>

                    <div style="margin-top: 8px;">قابل پرداخت:</div>
                    <div id="finalAmount" class="total-amount">0 تومان</div>
                </div>

                <div class="actions">
                    <button class="btn" onclick="recordSale()">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        ثبت فروش
                    </button>

                    <button class="btn" onclick="saveInvoice(true)">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>
                        ذخیره لیست فاکتور
                    </button>

                    <button class="btn" onclick="newInvoice()">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="M9 15h6"/></svg>
                        فاکتور جدید
                    </button>

                    <button class="btn" onclick="printInvoice()">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        پرینت فاکتور
                    </button>
                </div>
            </div>

            <div id="history" class="tab-content">
                <h2>تاریخچه فروش</h2>

                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                        </div>
                        <div class="stat-body">
                            <div class="stat-label">امروز</div>
                            <div class="stat-value" id="todayJalali">-</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                        </div>
                        <div class="stat-body">
                            <div class="stat-label">این هفته</div>
                            <div class="muted" id="weekRange">-</div>
                            <div class="stat-value" id="weekTotal">0 تومان</div>
                            <div class="muted" id="weekCount">0 فروش</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/></svg>
                        </div>
                        <div class="stat-body">
                            <div class="stat-label">این ماه شمسی</div>
                            <div class="stat-value" id="monthTotal">0 تومان</div>
                            <div class="muted" id="monthCount">0 فروش</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="report-toolbar">
                        <div class="report-filters">
                            <div class="form-group">
                                <label>نوع گزارش</label>
                                <select id="reportFilterType" onchange="onReportTypeChange()">
                                    <option value="day">روز فروش</option>
                                    <option value="week">هفته فروش</option>
                                    <option value="month" selected>ماه فروش</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>سال</label>
                                <select id="reportYear" onchange="onReportDatePartChange()"></select>
                            </div>

                            <div class="form-group">
                                <label>ماه</label>
                                <select id="reportMonth" onchange="onReportDatePartChange()"></select>
                            </div>

                            <div class="form-group" id="reportDayWrap" style="display: none;">
                                <label>روز</label>
                                <select id="reportDay" onchange="renderSalesReport()"></select>
                            </div>
                        </div>

                        <button class="btn" onclick="printSalesReport()">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                            چاپ گزارش فروش
                        </button>
                    </div>

                    <div class="report-summary">
                        <div class="muted" id="reportPeriodLabel">-</div>
                        <div class="stat-value" id="reportTotal">0 تومان</div>
                        <div class="muted" id="reportMeta">0 فروش</div>
                    </div>

                    <div id="reportTable"></div>
                </div>

                <div class="card">
                    <div class="calendar-header">
                        <button class="calendar-nav" onclick="changeCalendarMonth(-1)">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            قبلی
                        </button>

                        <div id="calendarLabel" style="font-weight: 700;"></div>

                        <button class="calendar-nav" onclick="changeCalendarMonth(1)">
                            بعدی
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        </button>
                    </div>

                    <div id="calendarGrid" class="calendar-grid"></div>
                </div>

                <div id="salesTable"></div>
            </div>
        </div>
    </div>

    <div id="printArea"></div>

    <script>
        let appData = {
            settings: { name: '', phone: '', address: '' },
            categories: [],
            products: [],
            sales: [],
            history: {}
        };

        let invoiceItems = [];
        let invoiceDiscount = 0;

        let calendarInitialized = false;
        let calendarJy = 0;
        let calendarJm = 1;

        let reportInitialized = false;
        let currentReport = {
            type: 'month',
            label: '',
            filtered: [],
            subtotal: 0,
            discount: 0,
            total: 0
        };

        const jalaliMonthNames = [
            'فروردین',
            'اردیبهشت',
            'خرداد',
            'تیر',
            'مرداد',
            'شهریور',
            'مهر',
            'آبان',
            'آذر',
            'دی',
            'بهمن',
            'اسفند'
        ];

        const iconTrash = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function formatPrice(value) {
            return Number(value || 0).toLocaleString('fa-IR');
        }

        function persianDigits(value) {
            return String(value).replace(/[0-9]/g, function (digit) {
                return '۰۱۲۳۴۵۶۷۸۹'[digit];
            });
        }

        function jalDiv(a, b) {
            return ~~(a / b);
        }

        function jalMod(a, b) {
            return a - ~~(a / b) * b;
        }

        function jalCal(jy) {
            const breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
            let gy = jy + 621;
            let leapJ = -14;
            let jp = breaks[0];
            let jump = 0;
            let jm;
            let n;
            let leap;
            let leapG;
            let march;
            let i;

            for (i = 1; i < breaks.length; i += 1) {
                jm = breaks[i];
                jump = jm - jp;

                if (jy < jm) {
                    break;
                }

                leapJ = leapJ + jalDiv(jump, 33) * 8 + jalDiv(jalMod(jump, 33), 4);
                jp = jm;
            }

            n = jy - jp;
            leapJ = leapJ + jalDiv(n, 33) * 8 + jalDiv(jalMod(n, 33) + 3, 4);

            if (jalMod(jump, 33) === 4 && jump - n === 4) {
                leapJ += 1;
            }

            leapG = jalDiv(gy, 4) - jalDiv((jalDiv(gy, 100) + 1) * 3, 4) - 150;
            march = 20 + leapJ - leapG;

            if (jump - n < 6) {
                n = n - jump + jalDiv(jump + 4, 33) * 33;
            }

            leap = jalMod(jalMod(n + 1, 33) - 1, 4);

            if (leap === -1) {
                leap = 4;
            }

            return { leap: leap, gy: gy, march: march };
        }

        function g2d(gy, gm, gd) {
            return jalDiv((gy + jalDiv(gm - 8, 6) + 100100) * 1461, 4)
                + jalDiv(153 * jalMod(gm + 9, 12) + 2, 5)
                + gd - 34840408
                - jalDiv(jalDiv(gy + 100100 + jalDiv(gm - 8, 6), 100) * 3, 4)
                + 752;
        }

        function d2g(jdn) {
            let j = 4 * jdn + 139361631;
            j = j + jalDiv(jalDiv(4 * jdn + 183187720, 146097) * 3, 4) * 4 - 3908;

            const i = jalDiv(jalMod(j, 1461), 4) * 5 + 308;
            const gd = jalDiv(jalMod(i, 153), 5) + 1;
            const gm = jalMod(jalDiv(i, 153), 12) + 1;
            const gy = jalDiv(j, 1461) - 100100 + jalDiv(8 - gm, 6);

            return { gy: gy, gm: gm, gd: gd };
        }

        function j2d(jy, jm, jd) {
            const r = jalCal(jy);
            return g2d(r.gy, 3, r.march) + (jm - 1) * 31 - jalDiv(jm, 7) * (jm - 7) + jd - 1;
        }

        function d2j(jdn) {
            const gy = d2g(jdn).gy;
            let jy = gy - 621;
            const r = jalCal(jy);
            const jdn1f = g2d(gy, 3, r.march);
            let k = jdn - jdn1f;
            let jm;
            let jd;

            if (k >= 0) {
                if (k <= 185) {
                    jm = 1 + jalDiv(k, 31);
                    jd = jalMod(k, 31) + 1;
                    return { jy: jy, jm: jm, jd: jd };
                }

                k -= 186;
            } else {
                jy -= 1;
                k += 179;

                if (r.leap === 1) {
                    k += 1;
                }
            }

            jm = 7 + jalDiv(k, 30);
            jd = jalMod(k, 30) + 1;

            return { jy: jy, jm: jm, jd: jd };
        }

        function gregorianToJalali(gy, gm, gd) {
            const d = d2j(g2d(gy, gm, gd));
            return [d.jy, d.jm, d.jd];
        }

        function jalaliToGregorian(jy, jm, jd) {
            const d = d2g(j2d(jy, jm, jd));
            return [d.gy, d.gm, d.gd];
        }

        function jalaliMonthLength(jy, jm) {
            if (jm <= 6) {
                return 31;
            }

            if (jm <= 11) {
                return 30;
            }

            return jalCal(jy).leap === 0 ? 30 : 29;
        }

        function formatJalaliString(jy, jm, jd) {
            return String(jy).padStart(4, '0') + '/' + String(jm).padStart(2, '0') + '/' + String(jd).padStart(2, '0');
        }

        function currentJalali() {
            const now = new Date();
            return gregorianToJalali(now.getFullYear(), now.getMonth() + 1, now.getDate());
        }

        function getTodayJalaliObj() {
            if (appData.history && appData.history.today) {
                return appData.history.today;
            }

            const c = currentJalali();
            return { jy: c[0], jm: c[1], jd: c[2] };
        }

        function getSubtotal() {
            return invoiceItems.reduce(function (sum, item) {
                return sum + (Number(item.price) * Number(item.quantity));
            }, 0);
        }

        function getEffectiveDiscount() {
            const subtotal = getSubtotal();
            let discount = Number(invoiceDiscount || 0);

            if (isNaN(discount) || discount < 0) {
                discount = 0;
            }

            if (discount > subtotal) {
                discount = subtotal;
            }

            return discount;
        }

        function onDiscountInput() {
            const value = parseFloat(document.getElementById('invoiceDiscount').value);
            invoiceDiscount = (!isNaN(value) && value >= 0) ? value : 0;

            renderInvoiceItems();
            saveInvoice(false);
        }

        async function api(action, payload) {
            try {
                const response = await fetch('?api=1&action=' + encodeURIComponent(action), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload || {})
                });

                return await response.json();
            } catch (error) {
                return { ok: false, error: 'خطا در ارتباط با سرور' };
            }
        }

        function switchTab(tabId) {
            document.querySelectorAll('.tab').forEach(function (item) {
                item.classList.remove('active');
            });

            document.querySelectorAll('.tab-content').forEach(function (item) {
                item.classList.remove('active');
            });

            const tabButton = document.querySelector('.tab[data-tab="' + tabId + '"]');
            if (tabButton) {
                tabButton.classList.add('active');
            }

            document.getElementById(tabId).classList.add('active');

            if (tabId === 'categories') {
                renderCategories();
            }

            if (tabId === 'products') {
                renderProducts();
            }

            if (tabId === 'invoice') {
                renderInvoiceCategorySelects();
                renderInvoiceProductSelect();
                renderInvoiceItems();
            }

            if (tabId === 'history') {
                renderHistory();
            }
        }

        function fillSettingsForm() {
            document.getElementById('storeName').value = appData.settings.name || '';
            document.getElementById('storePhone').value = appData.settings.phone || '';
            document.getElementById('storeAddress').value = appData.settings.address || '';
        }

        async function saveSettings() {
            const payload = {
                name: document.getElementById('storeName').value.trim(),
                phone: document.getElementById('storePhone').value.trim(),
                address: document.getElementById('storeAddress').value.trim()
            };

            const result = await api('save_settings', payload);

            if (result.ok) {
                appData.settings = payload;
                alert('تنظیمات ذخیره شد');
            } else {
                alert(result.error || 'خطا در ذخیره تنظیمات');
            }
        }

        async function loadData() {
            const result = await api('get');

            if (result.ok) {
                appData.settings = result.settings;
                appData.categories = result.categories;
                appData.products = result.products;
                appData.sales = Array.isArray(result.sales) ? result.sales : [];
                appData.history = result.history || {};

                invoiceItems = Array.isArray(result.invoiceItems) ? result.invoiceItems : [];
                invoiceDiscount = Number(result.invoiceDiscount || 0);

                document.getElementById('invoiceDiscount').value = invoiceDiscount;

                fillSettingsForm();
                renderCategories();
                renderProducts();
                renderInvoiceCategorySelects();
                renderInvoiceProductSelect();
                renderInvoiceItems();
                renderHistory();
                refreshReportFilters();
            } else {
                alert(result.error || 'خطا در بارگذاری اطلاعات');
            }
        }

        function renderCategories() {
            const box = document.getElementById('categoriesList');
            box.innerHTML = '';

            if (!appData.categories.length) {
                box.innerHTML = '<div class="empty-state">هنوز دسته‌بندی‌ای ثبت نشده است</div>';
                return;
            }

            appData.categories.forEach(function (category) {
                const row = document.createElement('div');
                row.className = 'list-item';

                const name = document.createElement('span');
                name.textContent = category.name;

                const button = document.createElement('button');
                button.className = 'btn btn-danger';
                button.title = 'حذف';
                button.innerHTML = iconTrash;
                button.onclick = function () {
                    deleteCategory(category.id);
                };

                row.appendChild(name);
                row.appendChild(button);
                box.appendChild(row);
            });
        }

        async function addCategory() {
            const name = document.getElementById('categoryName').value.trim();

            if (!name) {
                alert('نام دسته‌بندی را وارد کنید');
                return;
            }

            const result = await api('add_category', { name: name });

            if (result.ok) {
                document.getElementById('categoryName').value = '';
                await loadData();
            } else {
                alert(result.error || 'خطا در افزودن دسته‌بندی');
            }
        }

        async function deleteCategory(id) {
            if (!confirm('این دسته‌بندی حذف شود؟')) {
                return;
            }

            const result = await api('delete_category', { id: id });

            if (result.ok) {
                await loadData();
            } else {
                alert(result.error || 'خطا در حذف دسته‌بندی');
            }
        }

        function renderCategorySelect() {
            const select = document.getElementById('productCategory');
            select.innerHTML = '';

            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = 'انتخاب دسته‌بندی';
            select.appendChild(emptyOption);

            appData.categories.forEach(function (category) {
                const option = document.createElement('option');
                option.value = category.name;
                option.textContent = category.name;
                select.appendChild(option);
            });
        }

        function renderProducts() {
            renderCategorySelect();

            const box = document.getElementById('productsList');
            box.innerHTML = '';

            if (!appData.products.length) {
                box.innerHTML = '<div class="empty-state">هنوز محصولی ثبت نشده است</div>';
                return;
            }

            appData.products.forEach(function (product) {
                const row = document.createElement('div');
                row.className = 'list-item';

                const info = document.createElement('div');

                const title = document.createElement('strong');
                title.textContent = product.name;

                const meta = document.createElement('div');
                meta.className = 'muted';
                meta.textContent = product.category + ' - ' + formatPrice(product.price) + ' تومان';

                info.appendChild(title);
                info.appendChild(meta);

                const button = document.createElement('button');
                button.className = 'btn btn-danger';
                button.title = 'حذف';
                button.innerHTML = iconTrash;
                button.onclick = function () {
                    deleteProduct(product.id);
                };

                row.appendChild(info);
                row.appendChild(button);
                box.appendChild(row);
            });
        }

        async function addProduct() {
            const payload = {
                name: document.getElementById('productName').value.trim(),
                category: document.getElementById('productCategory').value,
                price: document.getElementById('productPrice').value
            };

            if (!payload.name || !payload.category || payload.price === '') {
                alert('نام، دسته‌بندی و قیمت محصول را وارد کنید');
                return;
            }

            const result = await api('add_product', payload);

            if (result.ok) {
                document.getElementById('productName').value = '';
                document.getElementById('productCategory').value = '';
                document.getElementById('productPrice').value = '';
                await loadData();
            } else {
                alert(result.error || 'خطا در افزودن محصول');
            }
        }

        async function deleteProduct(id) {
            if (!confirm('این محصول حذف شود؟')) {
                return;
            }

            const result = await api('delete_product', { id: id });

            if (result.ok) {
                await loadData();
            } else {
                alert(result.error || 'خطا در حذف محصول');
            }
        }

        function renderInvoiceCategorySelects() {
            const invoiceCategory = document.getElementById('invoiceCategory');
            const manualProductCategory = document.getElementById('manualProductCategory');

            const currentInvoiceCategory = invoiceCategory.value;
            const currentManualCategory = manualProductCategory.value;

            invoiceCategory.innerHTML = '';
            manualProductCategory.innerHTML = '';

            const allOption = document.createElement('option');
            allOption.value = '';
            allOption.textContent = 'همه دسته‌بندی‌ها';
            invoiceCategory.appendChild(allOption);

            const noneOption = document.createElement('option');
            noneOption.value = '';
            noneOption.textContent = 'بدون دسته‌بندی';
            manualProductCategory.appendChild(noneOption);

            appData.categories.forEach(function (category) {
                const option1 = document.createElement('option');
                option1.value = category.name;
                option1.textContent = category.name;
                invoiceCategory.appendChild(option1);

                const option2 = document.createElement('option');
                option2.value = category.name;
                option2.textContent = category.name;
                manualProductCategory.appendChild(option2);
            });

            if (currentInvoiceCategory) {
                invoiceCategory.value = currentInvoiceCategory;
            }

            if (currentManualCategory) {
                manualProductCategory.value = currentManualCategory;
            }
        }

        function renderInvoiceProductSelect() {
            const select = document.getElementById('invoiceProduct');
            const selectedCategory = document.getElementById('invoiceCategory').value;

            select.innerHTML = '';

            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = 'انتخاب محصول';
            select.appendChild(emptyOption);

            appData.products
                .filter(function (product) {
                    return !selectedCategory || product.category === selectedCategory;
                })
                .forEach(function (product) {
                    const option = document.createElement('option');
                    option.value = product.id;
                    option.textContent = product.name + ' - ' + formatPrice(product.price) + ' تومان';
                    select.appendChild(option);
                });
        }

        async function addToInvoice() {
            const selectedId = document.getElementById('invoiceProduct').value;
            const manualName = document.getElementById('manualProductName').value.trim();
            const manualPriceValue = document.getElementById('manualProductPrice').value;
            const manualCategory = document.getElementById('manualProductCategory').value;
            const quantity = parseInt(document.getElementById('productQuantity').value, 10);

            if (!quantity || quantity < 1) {
                alert('تعداد معتبر وارد کنید');
                return;
            }

            let item = null;

            if (selectedId) {
                const product = appData.products.find(function (productItem) {
                    return productItem.id === selectedId;
                });

                if (product) {
                    item = {
                        name: product.name,
                        category: product.category || '',
                        price: Number(product.price),
                        quantity: quantity
                    };
                }
            } else {
                const manualPrice = parseFloat(manualPriceValue);

                if (manualName && !isNaN(manualPrice) && manualPrice >= 0) {
                    item = {
                        name: manualName,
                        category: manualCategory || '',
                        price: manualPrice,
                        quantity: quantity
                    };
                }
            }

            if (!item) {
                alert('یک محصول انتخاب کنید یا نام و قیمت دستی را وارد کنید');
                return;
            }

            invoiceItems.push(item);
            renderInvoiceItems();

            document.getElementById('invoiceProduct').value = '';
            document.getElementById('manualProductName').value = '';
            document.getElementById('manualProductPrice').value = '';
            document.getElementById('manualProductCategory').value = '';
            document.getElementById('productQuantity').value = '1';

            await saveInvoice(false);
        }

        async function removeFromInvoice(index) {
            invoiceItems.splice(index, 1);
            renderInvoiceItems();
            await saveInvoice(false);
        }

        async function newInvoice() {
            if (!confirm('فاکتور جدید شروع شود؟ لیست محصولات و تخفیف پاک می‌شود.')) {
                return;
            }

            invoiceItems = [];
            invoiceDiscount = 0;
            document.getElementById('invoiceDiscount').value = '0';

            renderInvoiceItems();
            await saveInvoice(false);
        }

        async function saveInvoice(showAlert) {
            const result = await api('save_invoice', {
                items: invoiceItems,
                discount: Number(invoiceDiscount || 0)
            });

            if (showAlert) {
                if (result.ok) {
                    alert('لیست فاکتور ذخیره شد');
                } else {
                    alert(result.error || 'خطا در ذخیره فاکتور');
                }
            }

            return result;
        }

        async function recordSale() {
            if (!invoiceItems.length) {
                alert('فاکتور خالی است');
                return;
            }

            if (!confirm('فاکتور در تاریخچه فروش ثبت شود و فاکتور فعلی خالی شود؟')) {
                return;
            }

            const result = await api('record_sale', {
                items: invoiceItems,
                discount: Number(invoiceDiscount || 0)
            });

            if (result.ok) {
                invoiceItems = [];
                invoiceDiscount = 0;
                document.getElementById('invoiceDiscount').value = '0';

                renderInvoiceItems();
                await saveInvoice(false);
                await loadData();

                alert('فروش با موفقیت ثبت شد');
            } else {
                alert(result.error || 'خطا در ثبت فروش');
            }
        }

        function renderInvoiceItems() {
            const box = document.getElementById('invoiceItems');
            const totalSection = document.getElementById('totalSection');

            box.innerHTML = '';

            if (!invoiceItems.length) {
                totalSection.style.display = 'none';
                return;
            }

            const table = document.createElement('table');
            table.className = 'invoice-table';

            const thead = document.createElement('thead');
            const headRow = document.createElement('tr');

            ['ردیف', 'دسته‌بندی', 'نام محصول', 'قیمت واحد', 'تعداد', 'جمع', 'عملیات'].forEach(function (text) {
                const th = document.createElement('th');
                th.textContent = text;
                headRow.appendChild(th);
            });

            thead.appendChild(headRow);
            table.appendChild(thead);

            const tbody = document.createElement('tbody');
            let total = 0;

            invoiceItems.forEach(function (item, index) {
                const subtotal = Number(item.price) * Number(item.quantity);
                total += subtotal;

                const row = document.createElement('tr');

                const cells = [
                    index + 1,
                    item.category || '-',
                    item.name,
                    formatPrice(item.price) + ' تومان',
                    item.quantity,
                    formatPrice(subtotal) + ' تومان'
                ];

                cells.forEach(function (value) {
                    const td = document.createElement('td');
                    td.textContent = value;
                    row.appendChild(td);
                });

                const actionCell = document.createElement('td');
                const button = document.createElement('button');
                button.className = 'btn btn-danger';
                button.title = 'حذف';
                button.innerHTML = iconTrash;
                button.onclick = function () {
                    removeFromInvoice(index);
                };

                actionCell.appendChild(button);
                row.appendChild(actionCell);
                tbody.appendChild(row);
            });

            table.appendChild(tbody);
            box.appendChild(table);

            const discount = getEffectiveDiscount();
            const finalTotal = total - discount;

            totalSection.style.display = 'block';
            document.getElementById('totalAmount').textContent = formatPrice(total) + ' تومان';
            document.getElementById('discountAmount').textContent = formatPrice(discount) + ' تومان';
            document.getElementById('finalAmount').textContent = formatPrice(finalTotal) + ' تومان';
        }

        function renderHistory() {
            const history = appData.history || {};

            if (!calendarInitialized) {
                if (history.today) {
                    calendarJy = Number(history.today.jy);
                    calendarJm = Number(history.today.jm);
                } else {
                    const c = currentJalali();
                    calendarJy = c[0];
                    calendarJm = c[1];
                }

                calendarInitialized = true;
            }

            const c = currentJalali();
            const todayDisplay = history.todayJalali ? history.todayJalali : formatJalaliString(c[0], c[1], c[2]);

            document.getElementById('todayJalali').textContent = persianDigits(todayDisplay);

            const weekRangeText = history.weekStartJalali && history.weekEndJalali
                ? history.weekStartJalali + ' تا ' + history.weekEndJalali
                : '-';

            document.getElementById('weekRange').textContent = persianDigits(weekRangeText);
            document.getElementById('weekTotal').textContent = formatPrice(history.weekTotal || 0) + ' تومان';
            document.getElementById('weekCount').textContent = persianDigits(history.weekCount || 0) + ' فروش';
            document.getElementById('monthTotal').textContent = formatPrice(history.monthTotal || 0) + ' تومان';
            document.getElementById('monthCount').textContent = persianDigits(history.monthCount || 0) + ' فروش';

            renderSalesTable();
            renderCalendar();
        }

        function renderSalesTable() {
            const box = document.getElementById('salesTable');
            box.innerHTML = '';

            if (!appData.sales.length) {
                box.innerHTML = '<div class="empty-state">هنوز فروشی ثبت نشده است</div>';
                return;
            }

            const table = document.createElement('table');
            table.className = 'invoice-table';

            const thead = document.createElement('thead');
            const headRow = document.createElement('tr');

            ['ردیف', 'تاریخ شمسی', 'تعداد اقلام', 'جمع کل', 'تخفیف', 'قابل پرداخت'].forEach(function (text) {
                const th = document.createElement('th');
                th.textContent = text;
                headRow.appendChild(th);
            });

            thead.appendChild(headRow);
            table.appendChild(thead);

            const tbody = document.createElement('tbody');

            appData.sales.forEach(function (sale, index) {
                let itemsCount = 0;

                if (Array.isArray(sale.items)) {
                    sale.items.forEach(function (item) {
                        itemsCount += Number(item.quantity || 0);
                    });
                }

                const row = document.createElement('tr');

                const cells = [
                    persianDigits(index + 1),
                    sale.jalali ? persianDigits(sale.jalali) : '-',
                    persianDigits(itemsCount),
                    formatPrice(sale.subtotal || 0) + ' تومان',
                    formatPrice(sale.discount || 0) + ' تومان',
                    formatPrice(sale.total || 0) + ' تومان'
                ];

                cells.forEach(function (value) {
                    const td = document.createElement('td');
                    td.textContent = value;
                    row.appendChild(td);
                });

                tbody.appendChild(row);
            });

            table.appendChild(tbody);
            box.appendChild(table);
        }

        function populateReportYears(selectedYear) {
            const select = document.getElementById('reportYear');
            if (!select) {
                return;
            }

            const years = new Set();
            const today = getTodayJalaliObj();
            years.add(Number(today.jy));

            appData.sales.forEach(function (sale) {
                if (sale.jy) {
                    years.add(Number(sale.jy));
                }
            });

            const sorted = Array.from(years).sort(function (a, b) {
                return b - a;
            });

            select.innerHTML = '';

            sorted.forEach(function (year) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = persianDigits(year);
                select.appendChild(option);
            });

            if (sorted.includes(Number(selectedYear))) {
                select.value = Number(selectedYear);
            } else if (sorted.length) {
                select.value = sorted[0];
            }
        }

        function populateReportMonths(selectedMonth) {
            const select = document.getElementById('reportMonth');
            if (!select) {
                return;
            }

            select.innerHTML = '';

            for (let month = 1; month <= 12; month++) {
                const option = document.createElement('option');
                option.value = month;
                option.textContent = jalaliMonthNames[month - 1];
                select.appendChild(option);
            }

            if (selectedMonth >= 1 && selectedMonth <= 12) {
                select.value = selectedMonth;
            }
        }

        function updateReportDays(selectedDay) {
            const yearEl = document.getElementById('reportYear');
            const monthEl = document.getElementById('reportMonth');
            const dayEl = document.getElementById('reportDay');

            if (!yearEl || !monthEl || !dayEl) {
                return;
            }

            const today = getTodayJalaliObj();
            const jy = Number(yearEl.value) || Number(today.jy);
            const jm = Number(monthEl.value) || Number(today.jm);
            const monthLength = jalaliMonthLength(jy, jm);

            dayEl.innerHTML = '';

            for (let day = 1; day <= monthLength; day++) {
                const option = document.createElement('option');
                option.value = day;
                option.textContent = persianDigits(day);
                dayEl.appendChild(option);
            }

            if (selectedDay >= 1 && selectedDay <= monthLength) {
                dayEl.value = selectedDay;
            } else {
                dayEl.value = monthLength;
            }
        }

        function refreshReportFilters() {
            const typeEl = document.getElementById('reportFilterType');
            const yearEl = document.getElementById('reportYear');
            const monthEl = document.getElementById('reportMonth');
            const dayEl = document.getElementById('reportDay');

            if (!typeEl || !yearEl || !monthEl || !dayEl) {
                return;
            }

            const today = getTodayJalaliObj();

            const currentYear = reportInitialized && yearEl.value ? Number(yearEl.value) : Number(today.jy);
            const currentMonth = reportInitialized && monthEl.value ? Number(monthEl.value) : Number(today.jm);
            const currentDay = reportInitialized && dayEl.value ? Number(dayEl.value) : Number(today.jd);

            populateReportYears(currentYear);
            populateReportMonths(currentMonth);
            updateReportDays(currentDay);

            if (!reportInitialized) {
                typeEl.value = 'month';
                reportInitialized = true;
            }

            onReportTypeChange();
        }

        function onReportTypeChange() {
            const typeEl = document.getElementById('reportFilterType');
            const dayWrap = document.getElementById('reportDayWrap');

            if (!typeEl || !dayWrap) {
                return;
            }

            dayWrap.style.display = typeEl.value === 'month' ? 'none' : 'block';
            renderSalesReport();
        }

        function onReportDatePartChange() {
            const dayEl = document.getElementById('reportDay');
            updateReportDays(dayEl ? Number(dayEl.value) : 1);
            renderSalesReport();
        }

        function getWeekRangeFromJalali(jy, jm, jd) {
            const g = jalaliToGregorian(jy, jm, jd);
            const base = new Date(g[0], g[1] - 1, g[2], 0, 0, 0, 0);
            const diffToSaturday = (base.getDay() + 1) % 7;

            const start = new Date(base);
            start.setDate(base.getDate() - diffToSaturday);
            start.setHours(0, 0, 0, 0);

            const end = new Date(start);
            end.setDate(start.getDate() + 6);
            end.setHours(23, 59, 59, 999);

            return { start: start, end: end };
        }

        function renderSalesReport() {
            const typeEl = document.getElementById('reportFilterType');
            const yearEl = document.getElementById('reportYear');
            const monthEl = document.getElementById('reportMonth');
            const dayEl = document.getElementById('reportDay');

            if (!typeEl || !yearEl || !monthEl || !dayEl) {
                return;
            }

            const type = typeEl.value;
            const jy = Number(yearEl.value);
            const jm = Number(monthEl.value);
            const jd = Number(dayEl.value);

            let filtered = [];
            let label = '';

            if (type === 'day') {
                filtered = appData.sales.filter(function (sale) {
                    return Number(sale.jy) === jy && Number(sale.jm) === jm && Number(sale.jd) === jd;
                });

                label = 'گزارش روز فروش: ' + formatJalaliString(jy, jm, jd);
            }

            if (type === 'week') {
                const range = getWeekRangeFromJalali(jy, jm, jd);

                const startJ = gregorianToJalali(range.start.getFullYear(), range.start.getMonth() + 1, range.start.getDate());
                const endJ = gregorianToJalali(range.end.getFullYear(), range.end.getMonth() + 1, range.end.getDate());

                filtered = appData.sales.filter(function (sale) {
                    if (!sale.date) {
                        return false;
                    }

                    const saleDate = new Date(sale.date + 'T00:00:00');
                    return saleDate >= range.start && saleDate <= range.end;
                });

                label = 'گزارش هفته فروش: ' + formatJalaliString(startJ[0], startJ[1], startJ[2]) + ' تا ' + formatJalaliString(endJ[0], endJ[1], endJ[2]);
            }

            if (type === 'month') {
                filtered = appData.sales.filter(function (sale) {
                    return Number(sale.jy) === jy && Number(sale.jm) === jm;
                });

                label = 'گزارش ماه فروش: ' + jalaliMonthNames[jm - 1] + ' ' + jy;
            }

            let subtotal = 0;
            let discount = 0;
            let total = 0;

            filtered.forEach(function (sale) {
                subtotal += Number(sale.subtotal || 0);
                discount += Number(sale.discount || 0);
                total += Number(sale.total || 0);
            });

            currentReport = {
                type: type,
                label: label,
                filtered: filtered,
                subtotal: subtotal,
                discount: discount,
                total: total
            };

            document.getElementById('reportPeriodLabel').textContent = persianDigits(label);
            document.getElementById('reportTotal').textContent = formatPrice(total) + ' تومان';
            document.getElementById('reportMeta').textContent = persianDigits(filtered.length) + ' فروش | جمع کل: ' + formatPrice(subtotal) + ' تومان | تخفیف: ' + formatPrice(discount) + ' تومان';

            renderReportTable(filtered);
        }

        function renderReportTable(filtered) {
            const box = document.getElementById('reportTable');
            box.innerHTML = '';

            if (!filtered.length) {
                box.innerHTML = '<div class="empty-state">فروشی در این بازه ثبت نشده است</div>';
                return;
            }

            const table = document.createElement('table');
            table.className = 'invoice-table';

            const thead = document.createElement('thead');
            const headRow = document.createElement('tr');

            ['ردیف', 'تاریخ شمسی', 'تعداد اقلام', 'جمع کل', 'تخفیف', 'قابل پرداخت'].forEach(function (text) {
                const th = document.createElement('th');
                th.textContent = text;
                headRow.appendChild(th);
            });

            thead.appendChild(headRow);
            table.appendChild(thead);

            const tbody = document.createElement('tbody');

            filtered.forEach(function (sale, index) {
                let itemsCount = 0;

                if (Array.isArray(sale.items)) {
                    sale.items.forEach(function (item) {
                        itemsCount += Number(item.quantity || 0);
                    });
                }

                const dateText = sale.jalali ? sale.jalali : formatJalaliString(Number(sale.jy || 0), Number(sale.jm || 0), Number(sale.jd || 0));

                const row = document.createElement('tr');

                const cells = [
                    persianDigits(index + 1),
                    persianDigits(dateText),
                    persianDigits(itemsCount),
                    formatPrice(sale.subtotal || 0) + ' تومان',
                    formatPrice(sale.discount || 0) + ' تومان',
                    formatPrice(sale.total || 0) + ' تومان'
                ];

                cells.forEach(function (value) {
                    const td = document.createElement('td');
                    td.textContent = value;
                    row.appendChild(td);
                });

                tbody.appendChild(row);
            });

            table.appendChild(tbody);
            box.appendChild(table);
        }

        function printSalesReport() {
            if (!currentReport.filtered.length) {
                alert('گزارشی برای چاپ وجود ندارد');
                return;
            }

            let rows = '';

            currentReport.filtered.forEach(function (sale, index) {
                let itemsCount = 0;

                if (Array.isArray(sale.items)) {
                    sale.items.forEach(function (item) {
                        itemsCount += Number(item.quantity || 0);
                    });
                }

                const dateText = sale.jalali ? sale.jalali : formatJalaliString(Number(sale.jy || 0), Number(sale.jm || 0), Number(sale.jd || 0));

                rows += '<tr>'
                    + '<td>' + escapeHtml(persianDigits(index + 1)) + '</td>'
                    + '<td>' + escapeHtml(persianDigits(dateText)) + '</td>'
                    + '<td>' + escapeHtml(persianDigits(itemsCount)) + '</td>'
                    + '<td>' + escapeHtml(formatPrice(sale.subtotal || 0)) + ' تومان</td>'
                    + '<td>' + escapeHtml(formatPrice(sale.discount || 0)) + ' تومان</td>'
                    + '<td>' + escapeHtml(formatPrice(sale.total || 0)) + ' تومان</td>'
                    + '</tr>';
            });

            const jNow = currentJalali();
            const printDate = formatJalaliString(jNow[0], jNow[1], jNow[2]);

            const storeName = appData.settings.name ? escapeHtml(appData.settings.name) : 'پنل رسا';
            const phone = appData.settings.phone ? '<p>شماره تماس: ' + escapeHtml(appData.settings.phone) + '</p>' : '';
            const address = appData.settings.address ? '<p>آدرس: ' + escapeHtml(appData.settings.address) + '</p>' : '';

            document.getElementById('printArea').innerHTML =
                '<div class="print-header">'
                + '<h1>' + storeName + '</h1>'
                + phone
                + address
                + '<p>تاریخ چاپ گزارش: ' + persianDigits(printDate) + '</p>'
                + '</div>'
                + '<div class="print-title">گزارش فروش</div>'
                + '<div class="print-subtitle">' + escapeHtml(persianDigits(currentReport.label)) + '</div>'
                + '<div class="print-summary">'
                + '<div>تعداد فروش: ' + escapeHtml(persianDigits(currentReport.filtered.length)) + '</div>'
                + '<div>جمع کل: ' + escapeHtml(formatPrice(currentReport.subtotal)) + ' تومان</div>'
                + '<div>تخفیف: ' + escapeHtml(formatPrice(currentReport.discount)) + ' تومان</div>'
                + '<div>قابل پرداخت: ' + escapeHtml(formatPrice(currentReport.total)) + ' تومان</div>'
                + '</div>'
                + '<table class="invoice-table">'
                + '<thead>'
                + '<tr>'
                + '<th>ردیف</th>'
                + '<th>تاریخ شمسی</th>'
                + '<th>تعداد اقلام</th>'
                + '<th>جمع کل</th>'
                + '<th>تخفیف</th>'
                + '<th>قابل پرداخت</th>'
                + '</tr>'
                + '</thead>'
                + '<tbody>'
                + rows
                + '</tbody>'
                + '</table>';

            window.print();
        }

        function changeCalendarMonth(delta) {
            calendarJm += delta;

            if (calendarJm < 1) {
                calendarJm = 12;
                calendarJy -= 1;
            }

            if (calendarJm > 12) {
                calendarJm = 1;
                calendarJy += 1;
            }

            renderCalendar();
        }

        function renderCalendar() {
            const container = document.getElementById('calendarGrid');
            const label = document.getElementById('calendarLabel');

            if (!container || !label) {
                return;
            }

            if (!calendarInitialized || !calendarJy) {
                const c = currentJalali();
                calendarJy = c[0];
                calendarJm = c[1];
                calendarInitialized = true;
            }

            label.textContent = jalaliMonthNames[calendarJm - 1] + ' ' + persianDigits(calendarJy);
            container.innerHTML = '';

            ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'].forEach(function (dayName) {
                const div = document.createElement('div');
                div.className = 'calendar-day-name';
                div.textContent = dayName;
                container.appendChild(div);
            });

            const firstGregorian = jalaliToGregorian(calendarJy, calendarJm, 1);
            const firstDate = new Date(firstGregorian[0], firstGregorian[1] - 1, firstGregorian[2]);
            const offset = (firstDate.getDay() + 1) % 7;
            const monthLength = jalaliMonthLength(calendarJy, calendarJm);

            const today = appData.history && appData.history.today ? appData.history.today : (function () {
                const c = currentJalali();
                return { jy: c[0], jm: c[1], jd: c[2] };
            })();

            const saleDays = {};

            appData.sales.forEach(function (sale) {
                if (Number(sale.jy) === calendarJy && Number(sale.jm) === calendarJm) {
                    saleDays[Number(sale.jd)] = true;
                }
            });

            for (let i = 0; i < offset; i++) {
                const div = document.createElement('div');
                div.className = 'calendar-day empty';
                container.appendChild(div);
            }

            for (let day = 1; day <= monthLength; day++) {
                const div = document.createElement('div');
                div.className = 'calendar-day';
                div.textContent = persianDigits(day);

                if (day === Number(today.jd) && calendarJm === Number(today.jm) && calendarJy === Number(today.jy)) {
                    div.classList.add('today');
                }

                if (saleDays[day]) {
                    div.classList.add('has-sale');
                }

                container.appendChild(div);
            }
        }

        function printInvoice() {
            if (!invoiceItems.length) {
                alert('فاکتور خالی است');
                return;
            }

            let rows = '';
            let total = 0;

            invoiceItems.forEach(function (item, index) {
                const subtotal = Number(item.price) * Number(item.quantity);
                total += subtotal;

                rows += '<tr>'
                    + '<td>' + escapeHtml(index + 1) + '</td>'
                    + '<td>' + escapeHtml(item.category || '-') + '</td>'
                    + '<td>' + escapeHtml(item.name) + '</td>'
                    + '<td>' + escapeHtml(formatPrice(item.price)) + ' تومان</td>'
                    + '<td>' + escapeHtml(item.quantity) + '</td>'
                    + '<td>' + escapeHtml(formatPrice(subtotal)) + ' تومان</td>'
                    + '</tr>';
            });

            const discount = getEffectiveDiscount();
            const finalTotal = total - discount;

            const jNow = currentJalali();
            const invoiceDate = formatJalaliString(jNow[0], jNow[1], jNow[2]);

            const storeName = appData.settings.name ? escapeHtml(appData.settings.name) : 'پنل رسا';
            const phone = appData.settings.phone ? '<p>شماره تماس: ' + escapeHtml(appData.settings.phone) + '</p>' : '';
            const address = appData.settings.address ? '<p>آدرس: ' + escapeHtml(appData.settings.address) + '</p>' : '';

            document.getElementById('printArea').innerHTML =
                '<div class="print-header">'
                + '<h1>' + storeName + '</h1>'
                + phone
                + address
                + '<p>تاریخ: ' + persianDigits(invoiceDate) + '</p>'
                + '</div>'
                + '<div class="print-title">فاکتور فروش</div>'
                + '<table class="invoice-table">'
                + '<thead>'
                + '<tr>'
                + '<th>ردیف</th>'
                + '<th>دسته‌بندی</th>'
                + '<th>نام محصول</th>'
                + '<th>قیمت واحد</th>'
                + '<th>تعداد</th>'
                + '<th>جمع</th>'
                + '</tr>'
                + '</thead>'
                + '<tbody>'
                + rows
                + '</tbody>'
                + '</table>'
                + '<div class="total-section">'
                + '<div>جمع کل: ' + escapeHtml(formatPrice(total)) + ' تومان</div>'
                + '<div>تخفیف: ' + escapeHtml(formatPrice(discount)) + ' تومان</div>'
                + '<div style="margin-top: 5px; font-weight: 800;">قابل پرداخت: ' + escapeHtml(formatPrice(finalTotal)) + ' تومان</div>'
                + '</div>';

            window.print();
        }

        loadData();
    </script>
</body>
</html>
