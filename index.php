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
$returnsFile = $storageDirectory . '/returns.json';
$movesFile = $storageDirectory . '/stock_moves.json';
$htaccessFile = $storageDirectory . '/.htaccess';
if (!file_exists($htaccessFile)) {
@file_put_contents($htaccessFile, "Require all denied");
}
if (!file_exists($settingsFile)) {
@file_put_contents(
$settingsFile,
json_encode([
'name' => '',
'phone' => '',
'address' => '',
'currency' => 'IRT',
'language' => 'fa',
'calendar' => 'jalali',
'installed' => false,
'currency_locked' => false,
'vat' => 0,
'theme' => 'light',
'print_message' => ''
], JSON_UNESCAPED_UNICODE),
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
json_encode([
'items' => [],
'discount' => 0,
'discount_type' => 'amount',
'invoice_id' => '',
'invoice_number' => '',
'customer_name' => '',
'customer_phone' => '',
'note' => ''
], JSON_UNESCAPED_UNICODE),
LOCK_EX
);
}
if (!file_exists($salesFile)) {
@file_put_contents($salesFile, json_encode([], JSON_UNESCAPED_UNICODE), LOCK_EX);
}
if (!file_exists($returnsFile)) {
@file_put_contents($returnsFile, json_encode([], JSON_UNESCAPED_UNICODE), LOCK_EX);
}
if (!file_exists($movesFile)) {
@file_put_contents($movesFile, json_encode([], JSON_UNESCAPED_UNICODE), LOCK_EX);
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
function generateInvoiceId()
{
$suffix = function_exists('random_bytes') ? strtoupper(bin2hex(random_bytes(2))) : strtoupper(substr(uniqid('', true), -4));
return 'RSA-ID-' . $suffix;
}
function generateInvoiceNumber($sales)
{
$maxNum = 0;
foreach ($sales as $sale) {
if (isset($sale['invoice_number']) && preg_match('/^RSA-(\d+)$/', $sale['invoice_number'], $matches)) {
$num = (int)$matches[1];
if ($num > $maxNum) $maxNum = $num;
}
}
return 'RSA-' . str_pad($maxNum + 1, 8, '0', STR_PAD_LEFT);
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
$settings = readJsonFile($settingsFile, [
'name' => '',
'phone' => '',
'address' => '',
'currency' => 'IRT',
'language' => 'fa',
'calendar' => 'jalali',
'installed' => false,
'currency_locked' => false,
'vat' => 0,
'theme' => 'light',
'print_message' => ''
]);
if (is_array($settings) && !array_key_exists('installed', $settings)) {
$settings['installed'] = true;
$settings['currency_locked'] = true;
writeJsonFile($settingsFile, $settings);
}
if (!isset($settings['installed'])) {
$settings['installed'] = false;
}
if (!isset($settings['currency_locked'])) {
$settings['currency_locked'] = false;
}
if (!isset($settings['vat']) || !is_numeric($settings['vat'])) {
$settings['vat'] = 0;
}
if (!isset($settings['theme'])) {
$settings['theme'] = 'light';
}
if (!isset($settings['print_message'])) {
$settings['print_message'] = '';
}
$categories = readJsonFile($categoriesFile, []);
$products = readJsonFile($productsFile, []);
$sales = readJsonFile($salesFile, []);
$returns = readJsonFile($returnsFile, []);
$moves = readJsonFile($movesFile, []);
$invoiceData = readJsonFile($invoiceFile, [
'items' => [],
'discount' => 0,
'discount_type' => 'amount',
'invoice_id' => '',
'invoice_number' => '',
'customer_name' => '',
'customer_phone' => '',
'note' => ''
]);
$invoiceItems = [];
$invoiceDiscount = 0;
$invoiceDiscountType = 'amount';
$invoiceId = '';
$invoiceNumber = '';
$invoiceCustomerName = '';
$invoiceCustomerPhone = '';
$invoiceNote = '';
if (is_array($invoiceData)) {
if (isset($invoiceData['items']) && is_array($invoiceData['items'])) {
$invoiceItems = $invoiceData['items'];
} else {
$invoiceItems = $invoiceData;
}
if (isset($invoiceData['discount']) && is_numeric($invoiceData['discount'])) {
$invoiceDiscount = (float)$invoiceData['discount'];
}
if (isset($invoiceData['discount_type'])) {
$invoiceDiscountType = (string)$invoiceData['discount_type'];
}
if (isset($invoiceData['invoice_id'])) {
$invoiceId = (string)$invoiceData['invoice_id'];
}
if (isset($invoiceData['invoice_number'])) {
$invoiceNumber = (string)$invoiceData['invoice_number'];
}
if (isset($invoiceData['customer_name'])) {
$invoiceCustomerName = (string)$invoiceData['customer_name'];
}
if (isset($invoiceData['customer_phone'])) {
$invoiceCustomerPhone = (string)$invoiceData['customer_phone'];
}
if (isset($invoiceData['note'])) {
$invoiceNote = (string)$invoiceData['note'];
}
}
if ($invoiceId === '') {
$invoiceId = generateInvoiceId();
$invoiceNumber = generateInvoiceNumber($sales);
writeJsonFile($invoiceFile, [
'items' => $invoiceItems,
'discount' => $invoiceDiscount,
'discount_type' => $invoiceDiscountType,
'invoice_id' => $invoiceId,
'invoice_number' => $invoiceNumber,
'customer_name' => $invoiceCustomerName,
'customer_phone' => $invoiceCustomerPhone,
'note' => $invoiceNote
]);
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
'returns' => array_reverse($returns),
'stockMoves' => array_reverse(array_slice($moves, -150)),
'invoiceItems' => $invoiceItems,
'invoiceDiscount' => $invoiceDiscount,
'invoiceDiscountType' => $invoiceDiscountType,
'invoiceId' => $invoiceId,
'invoiceNumber' => $invoiceNumber,
'invoiceCustomerName' => $invoiceCustomerName,
'invoiceCustomerPhone' => $invoiceCustomerPhone,
'invoiceNote' => $invoiceNote,
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
if ($action === 'install') {
if (!empty($settings['installed'])) {
jsonResponse(['ok' => false, 'error' => 'قبلا نصب شده است']);
}
$allowedCurrencies = ['USD', 'EUR', 'OMR', 'IRT', 'IRR'];
$allowedLanguages = ['fa', 'en', 'fr', 'de', 'es', 'en_GB'];
$allowedCalendars = ['jalali', 'gregorian', 'both'];
$currency = trim((string)($payload['currency'] ?? 'IRT'));
$language = trim((string)($payload['language'] ?? 'fa'));
$calendar = trim((string)($payload['calendar'] ?? 'jalali'));
if (!in_array($currency, $allowedCurrencies)) $currency = 'IRT';
if (!in_array($language, $allowedLanguages)) $language = 'fa';
if (!in_array($calendar, $allowedCalendars)) $calendar = 'jalali';
$settings = [
'name' => isset($settings['name']) ? (string)$settings['name'] : '',
'phone' => isset($settings['phone']) ? (string)$settings['phone'] : '',
'address' => isset($settings['address']) ? (string)$settings['address'] : '',
'currency' => $currency,
'language' => $language,
'calendar' => $calendar,
'installed' => true,
'currency_locked' => true,
'vat' => isset($settings['vat']) && is_numeric($settings['vat']) ? (float)$settings['vat'] : 0,
'theme' => isset($settings['theme']) ? (string)$settings['theme'] : 'light',
'print_message' => isset($settings['print_message']) ? (string)$settings['print_message'] : ''
];
if (!writeJsonFile($settingsFile, $settings)) {
jsonResponse(['ok' => false, 'error' => 'خطا در نصب']);
}
jsonResponse(['ok' => true]);
}
if ($action === 'save_settings') {
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
'theme' => $theme,
'print_message' => trim((string)($payload['print_message'] ?? (isset($settings['print_message']) ? $settings['print_message'] : '')))
];
if (!writeJsonFile($settingsFile, $settings)) {
jsonResponse(['ok' => false, 'error' => 'خطا در ذخیره تنظیمات']);
}
jsonResponse(['ok' => true]);
}
if ($action === 'toggle_theme') {
$theme = trim((string)($payload['theme'] ?? 'light'));
if (!in_array($theme, ['light', 'dark'])) $theme = 'light';
$settings['theme'] = $theme;
if (!writeJsonFile($settingsFile, $settings)) {
jsonResponse(['ok' => false, 'error' => 'خطا در ذخیره تم']);
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
$categories[] = ['id' => createId(), 'name' => $name];
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
$barcode = trim((string)($payload['barcode'] ?? ''));
$price = null;
if (isset($payload['price']) && is_numeric($payload['price'])) {
$price = (float)$payload['price'];
}
$stock = isset($payload['stock']) && is_numeric($payload['stock']) ? (float)$payload['stock'] : 0;
$stockAlert = isset($payload['stock_alert']) && is_numeric($payload['stock_alert']) ? (float)$payload['stock_alert'] : 0;
if ($stock < 0) $stock = 0;
if ($stockAlert < 0) $stockAlert = 0;
if ($name === '' || $category === '' || $price === null || $price < 0) {
jsonResponse(['ok' => false, 'error' => 'نام، دسته‌بندی و قیمت معتبر وارد کنید']);
}
if ($barcode !== '') {
foreach ($products as $pItem) {
if ((string)($pItem['barcode'] ?? '') === $barcode) {
jsonResponse(['ok' => false, 'error' => 'این بارکد قبلا ثبت شده است']);
}
}
}
$products[] = ['id' => createId(), 'name' => $name, 'category' => $category, 'price' => $price, 'stock' => $stock, 'stock_alert' => $stockAlert, 'barcode' => $barcode];
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
if ($action === 'delete_sale') {
$id = (string)($payload['id'] ?? '');
if ($id === '') {
jsonResponse(['ok' => false, 'error' => 'شناسه فروش معتبر نیست']);
}
$before = count($sales);
$sales = array_values(array_filter($sales, function ($item) use ($id) {
return ($item['id'] ?? '') !== $id;
}));
if (count($sales) === $before) {
jsonResponse(['ok' => false, 'error' => 'فروش یافت نشد']);
}
if (!writeJsonFile($salesFile, $sales)) {
jsonResponse(['ok' => false, 'error' => 'خطا در حذف فروش']);
}
jsonResponse(['ok' => true]);
}
if ($action === 'stock_receive') {
$pid = (string)($payload['product_id'] ?? '');
$qty = isset($payload['quantity']) && is_numeric($payload['quantity']) ? (float)$payload['quantity'] : 0;
$note = trim((string)($payload['note'] ?? ''));
if ($qty < 1) {
jsonResponse(['ok' => false, 'error' => 'تعداد معتبر وارد کنید']);
}
$now = new DateTime();
$gy = (int)$now->format('Y');
$gm = (int)$now->format('n');
$gd = (int)$now->format('j');
$j = gregorianToJalali($gy, $gm, $gd);
$found = false;
foreach ($products as $pi => $p) {
if (($p['id'] ?? '') === $pid) {
$cur = isset($p['stock']) && is_numeric($p['stock']) ? (float)$p['stock'] : 0;
$new = $cur + $qty;
$products[$pi]['stock'] = $new;
$moves[] = [
'id' => createId(),
'product_id' => $pid,
'product_name' => (string)($p['name'] ?? ''),
'type' => 'in',
'quantity' => $qty,
'resulting_stock' => $new,
'note' => $note,
'datetime' => $now->format('Y-m-d H:i:s'),
'date' => $now->format('Y-m-d'),
'jy' => $j[0],
'jm' => $j[1],
'jd' => $j[2],
'jalali' => formatJalali($j[0], $j[1], $j[2])
];
$found = true;
break;
}
}
if (!$found) {
jsonResponse(['ok' => false, 'error' => 'محصول یافت نشد']);
}
if (!writeJsonFile($productsFile, array_values($products)) || !writeJsonFile($movesFile, array_values($moves))) {
jsonResponse(['ok' => false, 'error' => 'خطا در به‌روزرسانی موجودی']);
}
jsonResponse(['ok' => true]);
}
if ($action === 'stock_count') {
$pid = (string)($payload['product_id'] ?? '');
$counted = isset($payload['counted']) && is_numeric($payload['counted']) ? (float)$payload['counted'] : -1;
$note = trim((string)($payload['note'] ?? ''));
if ($counted < 0) {
jsonResponse(['ok' => false, 'error' => 'تعداد معتبر وارد کنید']);
}
$now = new DateTime();
$gy = (int)$now->format('Y');
$gm = (int)$now->format('n');
$gd = (int)$now->format('j');
$j = gregorianToJalali($gy, $gm, $gd);
$found = false;
foreach ($products as $pi => $p) {
if (($p['id'] ?? '') === $pid) {
$cur = isset($p['stock']) && is_numeric($p['stock']) ? (float)$p['stock'] : 0;
$diff = $counted - $cur;
$products[$pi]['stock'] = $counted;
if ($diff != 0) {
$moves[] = [
'id' => createId(),
'product_id' => $pid,
'product_name' => (string)($p['name'] ?? ''),
'type' => 'count',
'quantity' => $diff,
'resulting_stock' => $counted,
'note' => $note,
'datetime' => $now->format('Y-m-d H:i:s'),
'date' => $now->format('Y-m-d'),
'jy' => $j[0],
'jm' => $j[1],
'jd' => $j[2],
'jalali' => formatJalali($j[0], $j[1], $j[2])
];
}
$found = true;
break;
}
}
if (!$found) {
jsonResponse(['ok' => false, 'error' => 'محصول یافت نشد']);
}
if (!writeJsonFile($productsFile, array_values($products)) || !writeJsonFile($movesFile, array_values($moves))) {
jsonResponse(['ok' => false, 'error' => 'خطا در ثبت انبارگردانی']);
}
jsonResponse(['ok' => true]);
}
if ($action === 'record_return') {
$saleId = (string)($payload['sale_id'] ?? '');
$reqItems = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : [];
$note = trim((string)($payload['note'] ?? ''));
$sale = null;
foreach ($sales as $s) {
if (($s['id'] ?? '') === $saleId) {
$sale = $s;
break;
}
}
if ($sale === null) {
jsonResponse(['ok' => false, 'error' => 'فروش یافت نشد']);
}
$returnedMap = [];
foreach ($returns as $r) {
if (($r['sale_id'] ?? '') !== $saleId) continue;
$rItems = isset($r['items']) && is_array($r['items']) ? $r['items'] : [];
foreach ($rItems as $ri) {
$idx = (int)($ri['item_index'] ?? 0);
$returnedMap[$idx] = (isset($returnedMap[$idx]) ? $returnedMap[$idx] : 0) + (int)($ri['quantity'] ?? 0);
}
}
$saleItems = isset($sale['items']) && is_array($sale['items']) ? $sale['items'] : [];
$cleanItems = [];
$total = 0;
foreach ($reqItems as $req) {
if (!is_array($req)) continue;
$idx = (int)($req['index'] ?? -1);
$qty = (int)($req['quantity'] ?? 0);
if ($idx < 0 || !isset($saleItems[$idx])) continue;
$it = $saleItems[$idx];
$sold = (int)($it['quantity'] ?? 0);
$already = isset($returnedMap[$idx]) ? $returnedMap[$idx] : 0;
if ($qty < 1 || $qty > ($sold - $already)) continue;
$price = isset($it['price']) && is_numeric($it['price']) ? (float)$it['price'] : 0;
$cleanItems[] = [
'item_index' => $idx,
'name' => (string)($it['name'] ?? ''),
'category' => (string)($it['category'] ?? ''),
'price' => $price,
'quantity' => $qty,
'product_id' => isset($it['product_id']) ? (string)$it['product_id'] : ''
];
$total += $price * $qty;
$returnedMap[$idx] = $already + $qty;
}
if (!$cleanItems) {
jsonResponse(['ok' => false, 'error' => 'هیچ قلمی برای مرجوعی انتخاب نشده است']);
}
$now = new DateTime();
$gy = (int)$now->format('Y');
$gm = (int)$now->format('n');
$gd = (int)$now->format('j');
$j = gregorianToJalali($gy, $gm, $gd);
foreach ($cleanItems as $ci) {
if ($ci['product_id'] === '') continue;
foreach ($products as $pi => $p) {
if (($p['id'] ?? '') === $ci['product_id']) {
$cur = isset($p['stock']) && is_numeric($p['stock']) ? (float)$p['stock'] : 0;
$new = $cur + $ci['quantity'];
$products[$pi]['stock'] = $new;
$moves[] = [
'id' => createId(),
'product_id' => $ci['product_id'],
'product_name' => $ci['name'],
'type' => 'return',
'quantity' => $ci['quantity'],
'resulting_stock' => $new,
'note' => $note,
'datetime' => $now->format('Y-m-d H:i:s'),
'date' => $now->format('Y-m-d'),
'jy' => $j[0],
'jm' => $j[1],
'jd' => $j[2],
'jalali' => formatJalali($j[0], $j[1], $j[2])
];
break;
}
}
}
$returnNumber = 'RET-' . str_pad(count($returns) + 1, 6, '0', STR_PAD_LEFT);
$returns[] = [
'id' => createId(),
'return_number' => $returnNumber,
'sale_id' => $saleId,
'invoice_number' => isset($sale['invoice_number']) ? (string)$sale['invoice_number'] : '',
'items' => $cleanItems,
'total' => $total,
'note' => $note,
'datetime' => $now->format('Y-m-d H:i:s'),
'date' => $now->format('Y-m-d'),
'jy' => $j[0],
'jm' => $j[1],
'jd' => $j[2],
'jalali' => formatJalali($j[0], $j[1], $j[2])
];
if (!writeJsonFile($returnsFile, array_values($returns)) || !writeJsonFile($productsFile, array_values($products)) || !writeJsonFile($movesFile, array_values($moves))) {
jsonResponse(['ok' => false, 'error' => 'خطا در ثبت مرجوعی']);
}
jsonResponse(['ok' => true, 'return_number' => $returnNumber]);
}
if ($action === 'save_invoice') {
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
$invoiceId = generateInvoiceId();
}
if ($invoiceNumber === '') {
$invoiceNumber = generateInvoiceNumber($sales);
}
if ($discount < 0) $discount = 0;
if (!is_array($items)) {
jsonResponse(['ok' => false, 'error' => 'لیست فاکتور معتبر نیست']);
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
'quantity' => $quantity,
'product_id' => isset($item['product_id']) ? (string)$item['product_id'] : ''
];
}
if (!writeJsonFile($invoiceFile, [
'items' => $cleanItems,
'discount' => $discount,
'discount_type' => $discountType,
'invoice_id' => $invoiceId,
'invoice_number' => $invoiceNumber,
'customer_name' => $customerName,
'customer_phone' => $customerPhone,
'note' => $note
])) {
jsonResponse(['ok' => false, 'error' => 'خطا در ذخیره فاکتور']);
}
jsonResponse(['ok' => true, 'invoice_id' => $invoiceId, 'invoice_number' => $invoiceNumber]);
}
if ($action === 'record_sale') {
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
if ($invoiceId === '') $invoiceId = generateInvoiceId();
if ($invoiceNumber === '') $invoiceNumber = generateInvoiceNumber($sales);
if ($discount < 0) $discount = 0;
if (!is_array($items)) {
jsonResponse(['ok' => false, 'error' => 'لیست فاکتور معتبر نیست']);
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
'quantity' => $quantity,
'product_id' => isset($item['product_id']) ? (string)$item['product_id'] : ''
];
$subtotal += $price * $quantity;
}
if (!$cleanItems) {
jsonResponse(['ok' => false, 'error' => 'فاکتور خالی است']);
}
$discountAmount = 0;
if ($discountType === 'percent') {
if ($discount > 100) $discount = 100;
$discountAmount = $subtotal * $discount / 100;
} else {
if ($discount > $subtotal) $discount = $subtotal;
$discountAmount = $discount;
}
$vatRate = isset($settings['vat']) && is_numeric($settings['vat']) ? (float)$settings['vat'] : 0;
if ($vatRate < 0) $vatRate = 0;
$vatBase = $subtotal - $discountAmount;
$vatAmount = $vatBase * $vatRate / 100;
$total = $vatBase + $vatAmount;
$now = new DateTime();
$gy = (int)$now->format('Y');
$gm = (int)$now->format('n');
$gd = (int)$now->format('j');
$j = gregorianToJalali($gy, $gm, $gd);
foreach ($cleanItems as $ci) {
if (($ci['product_id'] ?? '') === '') continue;
foreach ($products as $pi => $p) {
if (($p['id'] ?? '') === $ci['product_id']) {
$cur = isset($p['stock']) && is_numeric($p['stock']) ? (float)$p['stock'] : 0;
$new = $cur - $ci['quantity'];
$products[$pi]['stock'] = $new;
$moves[] = [
'id' => createId(),
'product_id' => $ci['product_id'],
'product_name' => $ci['name'],
'type' => 'sale',
'quantity' => -$ci['quantity'],
'resulting_stock' => $new,
'note' => $invoiceNumber,
'datetime' => $now->format('Y-m-d H:i:s'),
'date' => $now->format('Y-m-d'),
'jy' => $j[0],
'jm' => $j[1],
'jd' => $j[2],
'jalali' => formatJalali($j[0], $j[1], $j[2])
];
break;
}
}
}
$sales[] = [
'id' => createId(),
'invoice_id' => $invoiceId,
'invoice_number' => $invoiceNumber,
'datetime' => $now->format('Y-m-d H:i:s'),
'date' => $now->format('Y-m-d'),
'jy' => $j[0],
'jm' => $j[1],
'jd' => $j[2],
'jalali' => formatJalali($j[0], $j[1], $j[2]),
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
if (!writeJsonFile($salesFile, array_values($sales)) || !writeJsonFile($productsFile, array_values($products)) || !writeJsonFile($movesFile, array_values($moves))) {
jsonResponse(['ok' => false, 'error' => 'خطا در ثبت فروش']);
}
jsonResponse(['ok' => true, 'invoice_id' => $invoiceId, 'invoice_number' => $invoiceNumber]);
}
if ($action === 'export_backup') {
jsonResponse([
'ok' => true,
'backup' => [
'settings' => $settings,
'categories' => $categories,
'products' => $products,
'sales' => $sales,
'returns' => $returns,
'exported_at' => date('Y-m-d H:i:s')
]
]);
}
if ($action === 'restore_backup') {
$backup = isset($payload['backup']) && is_array($payload['backup']) ? $payload['backup'] : null;
if ($backup === null) {
jsonResponse(['ok' => false, 'error' => 'فایل پشتیبان معتبر نیست']);
}
$restoreSettings = !empty($payload['restore_settings']);
$restoreCategories = !empty($payload['restore_categories']);
$restoreProducts = !empty($payload['restore_products']);
$restoreSales = !empty($payload['restore_sales']);
if (!$restoreSettings && !$restoreCategories && !$restoreProducts && !$restoreSales) {
jsonResponse(['ok' => false, 'error' => 'حداقل یک بخش را برای بازیابی انتخاب کنید']);
}
if ($restoreSettings) {
$bs = isset($backup['settings']) && is_array($backup['settings']) ? $backup['settings'] : null;
if ($bs === null) {
jsonResponse(['ok' => false, 'error' => 'بخش تنظیمات در فایل پشتیبان معتبر نیست']);
}
$allowedCurrencies = ['USD', 'EUR', 'OMR', 'IRT', 'IRR'];
$allowedLanguages = ['fa', 'en', 'fr', 'de', 'es', 'en_GB'];
$allowedCalendars = ['jalali', 'gregorian', 'both'];
$currency = trim((string)($bs['currency'] ?? 'IRT'));
$language = trim((string)($bs['language'] ?? 'fa'));
$calendar = trim((string)($bs['calendar'] ?? 'jalali'));
if (!in_array($currency, $allowedCurrencies)) $currency = 'IRT';
if (!in_array($language, $allowedLanguages)) $language = 'fa';
if (!in_array($calendar, $allowedCalendars)) $calendar = 'jalali';
$existingLocked = !empty($settings['currency_locked']);
if (!empty($settings['installed']) && $existingLocked) {
$currency = isset($settings['currency']) ? (string)$settings['currency'] : $currency;
}
$vat = isset($bs['vat']) && is_numeric($bs['vat']) ? (float)$bs['vat'] : 0;
if ($vat < 0) $vat = 0;
$theme = trim((string)($bs['theme'] ?? 'light'));
if (!in_array($theme, ['light', 'dark'])) $theme = 'light';
$newSettings = [
'name' => trim((string)($bs['name'] ?? '')),
'phone' => trim((string)($bs['phone'] ?? '')),
'address' => trim((string)($bs['address'] ?? '')),
'currency' => $currency,
'language' => $language,
'calendar' => $calendar,
'installed' => !empty($settings['installed']),
'currency_locked' => $existingLocked,
'vat' => $vat,
'theme' => $theme,
'print_message' => trim((string)($bs['print_message'] ?? ''))
];
if (!writeJsonFile($settingsFile, $newSettings)) {
jsonResponse(['ok' => false, 'error' => 'خطا در بازیابی تنظیمات']);
}
$settings = $newSettings;
}
if ($restoreCategories) {
$bc = isset($backup['categories']) && is_array($backup['categories']) ? $backup['categories'] : null;
if ($bc === null) {
jsonResponse(['ok' => false, 'error' => 'بخش دسته‌بندی‌ها در فایل پشتیبان معتبر نیست']);
}
$cleanCategories = [];
foreach ($bc as $item) {
if (!is_array($item)) continue;
$name = trim((string)($item['name'] ?? ''));
if ($name === '') continue;
$cleanCategories[] = [
'id' => isset($item['id']) && $item['id'] !== '' ? (string)$item['id'] : createId(),
'name' => $name
];
}
if (!writeJsonFile($categoriesFile, array_values($cleanCategories))) {
jsonResponse(['ok' => false, 'error' => 'خطا در بازیابی دسته‌بندی‌ها']);
}
}
if ($restoreProducts) {
$bp = isset($backup['products']) && is_array($backup['products']) ? $backup['products'] : null;
if ($bp === null) {
jsonResponse(['ok' => false, 'error' => 'بخش محصولات در فایل پشتیبان معتبر نیست']);
}
$cleanProducts = [];
foreach ($bp as $item) {
if (!is_array($item)) continue;
$name = trim((string)($item['name'] ?? ''));
$category = trim((string)($item['category'] ?? ''));
$price = isset($item['price']) && is_numeric($item['price']) ? (float)$item['price'] : null;
if ($name === '' || $price === null || $price < 0) continue;
$cleanProducts[] = [
'id' => isset($item['id']) && $item['id'] !== '' ? (string)$item['id'] : createId(),
'name' => $name,
'category' => $category,
'price' => $price,
'stock' => isset($item['stock']) && is_numeric($item['stock']) ? (float)$item['stock'] : 0,
'stock_alert' => isset($item['stock_alert']) && is_numeric($item['stock_alert']) ? (float)$item['stock_alert'] : 0,
'barcode' => isset($item['barcode']) ? (string)$item['barcode'] : ''
];
}
if (!writeJsonFile($productsFile, array_values($cleanProducts))) {
jsonResponse(['ok' => false, 'error' => 'خطا در بازیابی محصولات']);
}
}
if ($restoreSales) {
$bsales = isset($backup['sales']) && is_array($backup['sales']) ? $backup['sales'] : null;
if ($bsales === null) {
jsonResponse(['ok' => false, 'error' => 'بخش تاریخچه فروش در فایل پشتیبان معتبر نیست']);
}
$cleanSales = [];
foreach ($bsales as $item) {
if (!is_array($item)) continue;
$cleanSales[] = $item;
}
if (!writeJsonFile($salesFile, array_values($cleanSales))) {
jsonResponse(['ok' => false, 'error' => 'خطا در بازیابی تاریخچه فروش']);
}
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
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>پنل رسا</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Vazirmatn:wght@300;400;500;600;700;800&display=swap');
:root {
--bg: #f4f6fb;
--bg-gradient: linear-gradient(160deg, #f7f8fc 0%, #eef1f9 60%, #e9edf7 100%);
--surface: #ffffff;
--surface-alt: #f8fafc;
--surface-hover: #f1f5f9;
--border: #e2e8f0;
--border-light: #eef2f7;
--border-input: #cbd5e1;
--text: #0f172a;
--text-secondary: #334155;
--text-muted: #64748b;
--text-faint: #94a3b8;
--danger: #e11d48;
--danger-bg: #fff1f2;
--danger-border: #fecdd3;
--success: #10b981;
--success-soft: rgba(16, 185, 129, 0.12);
--primary: #4f46e5;
--primary-soft: rgba(79, 70, 229, 0.10);
--primary-text: #ffffff;
--accent: #7c3aed;
--shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.06);
--shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
--shadow-md: 0 8px 24px rgba(15, 23, 42, 0.10);
--shadow-lg: 0 16px 48px rgba(15, 23, 42, 0.12);
--shadow-island: 0 12px 40px rgba(15, 23, 42, 0.14), 0 2px 8px rgba(15, 23, 42, 0.08);
--radius-sm: 8px;
--radius: 12px;
--radius-lg: 16px;
--radius-xl: 20px;
--radius-island: 999px;
--transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
body.dark {
--bg: #0b1020;
--bg-gradient: linear-gradient(160deg, #0b1020 0%, #0d1428 60%, #101a33 100%);
--surface: #121826;
--surface-alt: #182032;
--surface-hover: #1d2740;
--border: #243049;
--border-light: #1c2740;
--border-input: #33415c;
--text: #e2e8f0;
--text-secondary: #cbd5e1;
--text-muted: #8fa3bf;
--text-faint: #5b6b85;
--danger: #fb7185;
--danger-bg: #331a22;
--danger-border: #5b2333;
--success: #34d399;
--success-soft: rgba(52, 211, 153, 0.12);
--primary: #818cf8;
--primary-soft: rgba(129, 140, 248, 0.14);
--primary-text: #0b1020;
--accent: #a78bfa;
--shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.3);
--shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
--shadow-md: 0 8px 24px rgba(0, 0, 0, 0.4);
--shadow-lg: 0 16px 48px rgba(0, 0, 0, 0.5);
--shadow-island: 0 12px 40px rgba(0, 0, 0, 0.55), 0 2px 8px rgba(0, 0, 0, 0.35);
}
* { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
html { scroll-behavior: smooth; overflow-x: hidden; }
body {
font-family: 'Vazirmatn', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
background: var(--bg-gradient);
color: var(--text);
min-height: 100vh;
transition: var(--transition);
-webkit-font-smoothing: antialiased;
-moz-osx-font-smoothing: grayscale;
overflow-x: hidden;
}
.island-nav {
position: fixed;
top: 20px;
left: 50%;
transform: translateX(-50%);
z-index: 1000;
background: rgba(255, 255, 255, 0.75);
backdrop-filter: blur(20px) saturate(180%);
-webkit-backdrop-filter: blur(20px) saturate(180%);
border: 1px solid rgba(255, 255, 255, 0.6);
border-radius: var(--radius-island);
padding: 6px;
display: flex;
align-items: center;
gap: 4px;
box-shadow: var(--shadow-island);
max-width: calc(100% - 32px);
overflow-x: auto;
scrollbar-width: none;
}
body.dark .island-nav {
background: rgba(18, 24, 38, 0.78);
border-color: rgba(129, 140, 248, 0.15);
}
.island-nav::-webkit-scrollbar { display: none; }
.island-nav .nav-item {
position: relative;
display: flex;
align-items: center;
gap: 6px;
padding: 10px 14px;
border: none;
background: transparent;
color: var(--text-muted);
font-size: 13px;
font-weight: 500;
font-family: inherit;
cursor: pointer;
border-radius: var(--radius-island);
transition: var(--transition);
white-space: nowrap;
flex-shrink: 0;
}
.island-nav .nav-item:hover { color: var(--text); background: var(--primary-soft); }
.island-nav .nav-item.active {
color: var(--primary-text);
background: linear-gradient(135deg, var(--primary), var(--accent));
font-weight: 600;
box-shadow: var(--shadow-sm);
}
.island-nav .nav-item .icon { width: 16px; height: 16px; flex-shrink: 0; }
.island-nav .theme-btn {
width: 36px; height: 36px; padding: 0;
display: flex; align-items: center; justify-content: center;
border-radius: 50%; flex-shrink: 0;
}
.side-island {
position: fixed;
top: 50%;
transform: translateY(-50%);
inset-inline-start: 16px;
z-index: 999;
display: flex;
flex-direction: column;
align-items: center;
gap: 4px;
background: rgba(255, 255, 255, 0.78);
backdrop-filter: blur(20px) saturate(180%);
-webkit-backdrop-filter: blur(20px) saturate(180%);
border: 1px solid rgba(255, 255, 255, 0.6);
border-radius: 26px;
padding: 12px 8px;
box-shadow: var(--shadow-island);
max-height: calc(100vh - 140px);
overflow-y: auto;
scrollbar-width: none;
}
body.dark .side-island {
background: rgba(18, 24, 38, 0.8);
border-color: rgba(129, 140, 248, 0.15);
}
.side-island::-webkit-scrollbar { display: none; }
.side-island-title {
font-size: 10px;
font-weight: 800;
color: var(--primary);
text-align: center;
padding: 0 0 6px;
letter-spacing: 0.06em;
}
.side-item {
display: flex;
flex-direction: column;
align-items: center;
gap: 4px;
border: none;
background: transparent;
color: var(--text-muted);
font-size: 10px;
font-weight: 600;
font-family: inherit;
cursor: pointer;
border-radius: 18px;
padding: 10px 6px;
width: 66px;
transition: var(--transition);
flex-shrink: 0;
}
.side-item .icon { width: 18px; height: 18px; flex-shrink: 0; }
.side-item:hover { color: var(--text); background: var(--primary-soft); }
.side-item.active {
color: var(--primary-text);
background: linear-gradient(135deg, var(--primary), var(--accent));
font-weight: 700;
box-shadow: var(--shadow-sm);
}
body.app-active .container { max-width: 1100px; }
@media (min-width: 769px) and (max-width: 1319px) {
body.app-active .container { max-width: calc(100vw - 220px); }
}
.container { max-width: 1100px; margin: 0 auto; padding: 100px 20px 120px; }
.header { text-align: center; padding: 40px 20px 32px; margin-bottom: 32px; }
.header h1 {
font-size: 32px;
font-weight: 800;
letter-spacing: -0.02em;
background: linear-gradient(135deg, var(--primary), var(--accent));
-webkit-background-clip: text;
background-clip: text;
color: transparent;
margin-bottom: 8px;
}
.header .subtitle { color: var(--text-muted); font-size: 13px; font-weight: 400; }
.live-clock {
margin-top: 16px;
display: inline-flex;
flex-direction: column;
gap: 4px;
align-items: center;
background: var(--surface);
border: 1px solid var(--border);
border-radius: var(--radius-lg);
padding: 10px 26px;
box-shadow: var(--shadow-sm);
}
.live-clock .clock-time {
font-family: 'Inter', monospace;
font-size: 24px;
font-weight: 800;
color: var(--primary);
letter-spacing: 0.05em;
font-variant-numeric: tabular-nums;
}
.live-clock .clock-date { font-size: 11px; color: var(--text-muted); }
.tab-content { display: none; animation: fadeIn 0.3s ease; }
.tab-content.active { display: block; }
.wtab { display: none; }
.wtab.active { display: block; animation: fadeIn 0.3s ease; }
@keyframes fadeIn {
from { opacity: 0; transform: translateY(8px); }
to { opacity: 1; transform: translateY(0); }
}
.card {
background: var(--surface);
border: 1px solid var(--border);
border-radius: var(--radius-lg);
padding: 24px;
margin-bottom: 16px;
transition: var(--transition);
}
.card:hover { box-shadow: var(--shadow-sm); }
.card.dragging { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }
.card-accent { position: relative; overflow: hidden; }
.card-accent::before {
content: '';
position: absolute;
top: 0; right: 0; left: 0;
height: 3px;
background: linear-gradient(90deg, var(--primary), var(--accent));
}
.card-title {
font-size: 18px;
font-weight: 700;
margin-bottom: 20px;
color: var(--text);
letter-spacing: -0.01em;
}
.form-group { margin-bottom: 16px; }
.form-group label {
display: block;
margin-bottom: 8px;
font-size: 13px;
font-weight: 600;
color: var(--text-secondary);
}
.form-group input,
.form-group select,
.form-group textarea {
width: 100%;
padding: 12px 14px;
border: 1px solid var(--border-input);
border-radius: var(--radius);
font-size: 14px;
font-family: inherit;
background: var(--surface);
color: var(--text);
transition: var(--transition);
outline: none;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
border-color: var(--primary);
box-shadow: 0 0 0 3px var(--primary-soft);
}
.form-group input:disabled,
.form-group select:disabled,
.form-group textarea:disabled { opacity: 0.5; cursor: not-allowed; }
.form-group input[readonly] {
background: var(--surface-alt);
color: var(--text-secondary);
font-family: 'Inter', monospace;
letter-spacing: 0.03em;
font-weight: 600;
cursor: default;
}
.form-group input[readonly]:focus { box-shadow: none; border-color: var(--border-input); }
.form-group textarea { resize: vertical; min-height: 80px; }
.barcode-row { display: flex; gap: 8px; flex-wrap: wrap; }
.barcode-row input {
flex: 1;
min-width: 140px;
font-family: 'Inter', monospace;
letter-spacing: 0.04em;
}
.scan-modal {
position: fixed;
inset: 0;
z-index: 3000;
background: rgba(0, 0, 0, 0.6);
display: flex;
align-items: center;
justify-content: center;
padding: 20px;
}
.scan-dialog {
background: var(--surface);
border: 1px solid var(--border);
border-radius: var(--radius-lg);
padding: 16px;
width: 100%;
max-width: 480px;
box-shadow: var(--shadow-lg);
}
.scan-header {
display: flex;
justify-content: space-between;
align-items: center;
gap: 10px;
margin-bottom: 12px;
}
.scan-header strong { font-size: 15px; color: var(--text); }
.scan-close {
border: 1px solid var(--border);
background: var(--surface-alt);
color: var(--text);
border-radius: 10px;
padding: 6px 12px;
cursor: pointer;
font-family: inherit;
font-size: 12px;
font-weight: 600;
}
.scan-close:hover { border-color: var(--danger); color: var(--danger); }
#scanReader {
width: 100%;
border-radius: var(--radius);
overflow: hidden;
background: #000;
min-height: 220px;
position: relative;
}
#scanReader video {
width: 100%;
height: auto;
max-height: 360px;
object-fit: cover;
border-radius: var(--radius);
display: block;
}
.scan-overlay {
position: absolute;
inset: 0;
pointer-events: none;
}
.scan-overlay::before {
content: '';
position: absolute;
top: 50%;
left: 50%;
transform: translate(-50%, -50%);
width: 70%;
height: 45%;
border: 2px solid rgba(255, 255, 255, 0.85);
border-radius: 10px;
box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.25);
}
.scan-overlay::after {
content: '';
position: absolute;
top: 50%;
left: 15%;
right: 15%;
height: 2px;
background: var(--primary);
box-shadow: 0 0 8px var(--primary);
animation: scanLine 1.6s ease-in-out infinite;
}
@keyframes scanLine {
0% { transform: translateY(-22px); }
50% { transform: translateY(22px); }
100% { transform: translateY(-22px); }
}
.scan-hint {
margin-top: 10px;
font-size: 12px;
color: var(--text-muted);
text-align: center;
}
.btn {
border: none;
background: linear-gradient(135deg, var(--primary), var(--accent));
color: var(--primary-text);
border-radius: var(--radius);
padding: 11px 18px;
font-size: 13px;
font-weight: 600;
font-family: inherit;
cursor: pointer;
display: inline-flex;
align-items: center;
gap: 8px;
transition: var(--transition);
box-shadow: var(--shadow-sm);
}
.btn:hover { transform: translateY(-1px); box-shadow: var(--shadow); }
.btn:active { transform: translateY(0); }
.btn-outline {
background: transparent;
color: var(--text);
border-color: var(--border);
box-shadow: none;
}
.btn-outline:hover { background: var(--surface-hover); border-color: var(--primary); color: var(--primary); }
.btn-danger {
background: var(--danger-bg);
color: var(--danger);
border: 1px solid var(--danger-border);
padding: 8px 10px;
box-shadow: none;
}
.btn-danger:hover { background: var(--danger); color: #fff; border-color: var(--danger); }
.btn .icon { width: 15px; height: 15px; }
.actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
.choice-grid { display: flex; gap: 8px; flex-wrap: wrap; }
.choice-btn {
display: inline-flex;
align-items: center;
gap: 7px;
border: 1px solid var(--border-input);
background: var(--surface);
border-radius: var(--radius);
padding: 10px 14px;
font-size: 13px;
font-weight: 500;
color: var(--text-secondary);
cursor: pointer;
font-family: inherit;
transition: var(--transition);
}
.choice-btn:hover { border-color: var(--primary); background: var(--surface-hover); }
.choice-btn.selected { border-color: var(--primary); background: var(--primary-soft); color: var(--primary); font-weight: 600; }
.choice-btn.disabled { opacity: 0.4; cursor: not-allowed; }
.flag { width: 22px; height: 15px; border-radius: 3px; display: inline-block; flex-shrink: 0; }
.icon { width: 16px; height: 16px; flex-shrink: 0; }
.list-item {
display: flex;
justify-content: space-between;
align-items: center;
gap: 12px;
background: var(--surface);
border: 1px solid var(--border);
border-radius: var(--radius);
padding: 14px 16px;
margin-bottom: 10px;
transition: var(--transition);
flex-wrap: wrap;
}
.list-item:hover { border-color: var(--primary); }
.list-item strong { font-size: 14px; font-weight: 600; color: var(--text); }
.muted { color: var(--text-muted); font-size: 12px; margin-top: 4px; }
.empty-state {
border: 2px dashed var(--border);
color: var(--text-faint);
text-align: center;
padding: 40px 24px;
border-radius: var(--radius-lg);
background: var(--surface);
font-size: 14px;
}
.invoice-table {
width: 100%;
border-collapse: separate;
border-spacing: 0;
margin-top: 16px;
background: var(--surface);
border-radius: var(--radius);
overflow: hidden;
border: 1px solid var(--border);
display: block;
overflow-x: auto;
}
.invoice-table th,
.invoice-table td {
padding: 12px 14px;
text-align: right;
font-size: 13px;
border-bottom: 1px solid var(--border-light);
white-space: nowrap;
}
.invoice-table th {
background: var(--primary-soft);
color: var(--primary);
font-weight: 700;
font-size: 12px;
letter-spacing: 0.02em;
}
.invoice-table tbody tr { transition: var(--transition); }
.invoice-table tbody tr:hover td { background: var(--surface-hover); }
.invoice-table tr:last-child td { border-bottom: none; }
html[dir="ltr"] .invoice-table th,
html[dir="ltr"] .invoice-table td { text-align: left; }
.inline-input {
width: 90px !important;
display: inline-block;
padding: 8px 10px !important;
font-size: 13px !important;
margin-inline-end: 6px;
}
.stock-low { color: var(--danger); font-weight: 800; }
.total-section {
margin-top: 20px;
background: var(--surface-alt);
border-radius: var(--radius-lg);
padding: 6px 20px;
border: 1px solid var(--border);
}
.total-row {
display: flex;
justify-content: space-between;
align-items: center;
padding: 12px 0;
font-size: 13px;
color: var(--text-secondary);
border-bottom: 1px dashed var(--border);
}
.total-row.final {
border-bottom: none;
margin: 0 -20px;
padding: 14px 20px;
font-weight: 700;
color: var(--primary-text);
font-size: 15px;
background: linear-gradient(135deg, var(--primary), var(--accent));
border-radius: 0 0 var(--radius-lg) var(--radius-lg);
}
.total-amount { font-weight: 700; color: var(--text); }
.total-row.final .total-amount { color: var(--primary-text); font-size: 17px; }
.stats {
display: grid;
grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
gap: 14px;
margin-bottom: 24px;
}
.stat-card {
background: var(--surface);
border: 1px solid var(--border);
border-radius: var(--radius-lg);
padding: 20px;
display: flex;
align-items: center;
gap: 14px;
transition: var(--transition);
}
.stat-card:hover { box-shadow: var(--shadow); transform: translateY(-2px); }
.stat-icon {
width: 44px; height: 44px;
border-radius: var(--radius);
background: var(--primary-soft);
color: var(--primary);
display: flex;
align-items: center;
justify-content: center;
flex-shrink: 0;
}
.stat-body { flex: 1; }
.stat-label {
font-size: 12px;
font-weight: 600;
color: var(--text-muted);
text-transform: uppercase;
letter-spacing: 0.03em;
margin-bottom: 4px;
}
.stat-value {
font-size: 22px;
font-weight: 800;
color: var(--text);
letter-spacing: -0.02em;
}
.bar-chart {
display: flex;
gap: 8px;
align-items: flex-end;
height: 170px;
padding-top: 24px;
}
.bar-col {
flex: 1;
display: flex;
flex-direction: column;
align-items: center;
gap: 6px;
height: 100%;
justify-content: flex-end;
min-width: 0;
}
.bar {
width: 100%;
max-width: 40px;
background: var(--surface-hover);
border: 1px solid var(--border);
border-radius: 8px 8px 0 0;
transition: height 0.5s cubic-bezier(0.4, 0, 0.2, 1);
min-height: 3px;
}
.bar.has-value { background: linear-gradient(180deg, var(--primary), var(--accent)); border: none; }
.bar-label { font-size: 11px; color: var(--text-muted); }
.bar-value {
font-size: 10px;
color: var(--text-secondary);
max-width: 100%;
overflow: hidden;
text-overflow: ellipsis;
white-space: nowrap;
}
.quick-grid { display: flex; flex-wrap: wrap; gap: 8px; }
.quick-chip {
display: flex;
flex-direction: column;
gap: 2px;
align-items: flex-start;
border: 1px solid var(--border-input);
background: var(--surface);
border-radius: var(--radius);
padding: 8px 12px;
cursor: pointer;
font-family: inherit;
transition: var(--transition);
}
.quick-chip:hover { border-color: var(--primary); background: var(--primary-soft); transform: translateY(-1px); }
.quick-chip strong { font-size: 12px; color: var(--text); font-weight: 600; }
.quick-chip span { font-size: 11px; color: var(--text-muted); }
.calendar-mode {
display: flex;
gap: 6px;
margin-bottom: 10px;
background: var(--surface-alt);
padding: 4px;
border-radius: var(--radius);
border: 1px solid var(--border);
}
.calendar-mode-btn {
flex: 1;
border: none;
background: transparent;
border-radius: var(--radius-sm);
padding: 8px 12px;
font-size: 12px;
font-weight: 500;
color: var(--text-muted);
cursor: pointer;
font-family: inherit;
transition: var(--transition);
}
.calendar-mode-btn.active {
background: linear-gradient(135deg, var(--primary), var(--accent));
color: var(--primary-text);
font-weight: 600;
box-shadow: var(--shadow-sm);
}
.calendar-header {
display: flex;
justify-content: space-between;
align-items: center;
gap: 12px;
margin-bottom: 16px;
}
.calendar-nav {
border: 1px solid var(--border);
background: var(--surface);
border-radius: var(--radius);
padding: 8px 14px;
cursor: pointer;
display: inline-flex;
align-items: center;
gap: 6px;
font-size: 13px;
font-weight: 500;
color: var(--text-secondary);
font-family: inherit;
transition: var(--transition);
}
.calendar-nav:hover { background: var(--surface-hover); border-color: var(--primary); color: var(--primary); }
.calendar-grid {
display: grid;
grid-template-columns: repeat(7, 1fr);
gap: 4px;
}
.calendar-day-name {
font-size: 11px;
font-weight: 600;
color: var(--text-muted);
padding: 8px 0;
text-align: center;
text-transform: uppercase;
}
.calendar-day {
aspect-ratio: 1;
border-radius: var(--radius-sm);
display: flex;
align-items: center;
justify-content: center;
font-size: 13px;
font-weight: 500;
background: var(--surface);
border: 1px solid var(--border);
position: relative;
color: var(--text);
transition: var(--transition);
}
.calendar-day:hover { background: var(--surface-hover); }
.calendar-day.empty { background: transparent; border: none; }
.calendar-day.today {
background: linear-gradient(135deg, var(--primary), var(--accent));
color: var(--primary-text);
border-color: transparent;
font-weight: 700;
}
.calendar-day.has-sale::after {
content: '';
position: absolute;
bottom: 6px;
left: 50%;
transform: translateX(-50%);
width: 4px;
height: 4px;
border-radius: 50%;
background: var(--success);
}
.calendar-day.today.has-sale::after { background: var(--primary-text); }
.report-toolbar {
display: flex;
justify-content: space-between;
align-items: flex-end;
gap: 16px;
flex-wrap: wrap;
margin-bottom: 20px;
}
.report-filters {
display: flex;
gap: 10px;
flex-wrap: wrap;
align-items: flex-end;
}
.report-filters .form-group { width: 130px; margin-bottom: 0; }
.report-summary {
background: var(--surface);
border: 1px solid var(--border);
border-radius: var(--radius-lg);
padding: 20px;
margin-bottom: 20px;
position: relative;
overflow: hidden;
}
.report-summary::before {
content: '';
position: absolute;
top: 0; right: 0; left: 0;
height: 3px;
background: linear-gradient(90deg, var(--primary), var(--accent));
}
.report-summary .muted { font-size: 13px; margin-bottom: 6px; }
.report-summary .stat-value { font-size: 28px; color: var(--primary); }
.switch-row {
display: flex;
align-items: center;
gap: 12px;
padding: 14px 16px;
background: var(--surface-alt);
border-radius: var(--radius);
margin-bottom: 16px;
font-size: 13px;
color: var(--text-secondary);
border: 1px solid var(--border);
flex-wrap: wrap;
}
.switch {
position: relative;
display: inline-block;
width: 40px;
height: 22px;
flex-shrink: 0;
}
.switch input { opacity: 0; width: 0; height: 0; }
.slider {
position: absolute;
cursor: pointer;
top: 0; left: 0; right: 0; bottom: 0;
background-color: var(--border-input);
transition: 0.25s;
border-radius: 22px;
}
.slider:before {
position: absolute;
content: "";
height: 16px;
width: 16px;
left: 3px;
bottom: 3px;
background-color: white;
transition: 0.25s;
border-radius: 50%;
box-shadow: var(--shadow-sm);
}
html[dir="rtl"] .slider:before { left: auto; right: 3px; }
input:checked + .slider { background: linear-gradient(135deg, var(--primary), var(--accent)); }
input:checked + .slider:before { transform: translateX(18px); }
html[dir="rtl"] input:checked + .slider:before { transform: translateX(-18px); }
.invoice-meta-grid {
display: grid;
grid-template-columns: 1fr 1fr;
gap: 12px;
margin-bottom: 16px;
}
.invoice-meta-grid .form-group { margin-bottom: 0; }
.search-box { position: relative; margin-bottom: 16px; }
.search-box input {
width: 100%;
padding: 12px 14px 12px 40px;
border: 1px solid var(--border-input);
border-radius: var(--radius);
font-size: 14px;
font-family: inherit;
background: var(--surface);
color: var(--text);
transition: var(--transition);
outline: none;
}
html[dir="rtl"] .search-box input { padding: 12px 40px 12px 14px; }
.search-box input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }
.search-box .search-icon {
position: absolute;
top: 50%;
transform: translateY(-50%);
right: 14px;
width: 16px;
height: 16px;
color: var(--text-muted);
}
html[dir="ltr"] .search-box .search-icon { right: auto; left: 14px; }
.invoice-history-item {
background: var(--surface);
border: 1px solid var(--border);
border-radius: var(--radius);
padding: 16px;
margin-bottom: 10px;
transition: var(--transition);
}
.invoice-history-item:hover { border-color: var(--primary); box-shadow: var(--shadow-sm); }
.invoice-history-header {
display: flex;
justify-content: space-between;
align-items: center;
gap: 12px;
margin-bottom: 10px;
flex-wrap: wrap;
}
.invoice-history-number {
font-size: 15px;
font-weight: 700;
color: var(--primary);
font-family: 'Inter', monospace;
}
.invoice-history-date { font-size: 12px; color: var(--text-muted); }
.invoice-history-details {
display: flex;
justify-content: space-between;
align-items: center;
gap: 12px;
flex-wrap: wrap;
}
.invoice-history-total { font-size: 16px; font-weight: 700; color: var(--text); }
.invoice-history-actions { display: flex; gap: 8px; flex-wrap: wrap; }
#toastWrap {
position: fixed;
bottom: 90px;
left: 50%;
transform: translateX(-50%);
z-index: 2000;
display: flex;
flex-direction: column;
gap: 8px;
align-items: center;
pointer-events: none;
width: max-content;
max-width: 92vw;
}
@media (min-width: 769px) { #toastWrap { bottom: 32px; } }
.toast {
background: var(--surface);
color: var(--text);
border: 1px solid var(--border);
border-radius: var(--radius-island);
padding: 10px 20px;
font-size: 13px;
font-weight: 600;
box-shadow: var(--shadow-md);
opacity: 0;
transform: translateY(10px);
transition: all 0.3s ease;
max-width: 92vw;
}
.toast.show { opacity: 1; transform: translateY(0); }
.toast.success { border-color: var(--success); color: var(--success); }
.toast.error { border-color: var(--danger); color: var(--danger); }
#installer { max-width: 560px; margin: 0 auto; padding: 40px 20px; }
#installer .card { padding: 32px; }
#printArea { display: none; }
@page { size: A4; margin: 0; }
@media print {
body { background: #fff; padding: 0; color: #000; }
#app, #installer, .island-nav, .side-island, #toastWrap, #scanModal { display: none !important; }
.container { padding: 0; max-width: 100% !important; }
#printArea { display: block !important; padding: 12mm; color: #000; }
.print-brand { height: 6px; background: #000; margin-bottom: 16px; }
.print-header {
text-align: center;
border-bottom: 1px solid #000;
padding-bottom: 12px;
margin-bottom: 16px;
}
.print-header h1 { font-size: 24px; margin-bottom: 7px; }
.print-header p { margin: 4px 0; font-size: 13px; }
.print-customer {
margin: 10px 0;
border: 1px solid #000;
padding: 10px;
display: grid;
grid-template-columns: 1fr 1fr;
gap: 6px;
}
.print-title { text-align: center; font-size: 18px; margin: 10px 0; font-weight: 800; }
.print-subtitle { text-align: center; font-size: 14px; margin-bottom: 12px; font-weight: 700; }
.print-note { margin-top: 14px; border: 1px dashed #000; padding: 10px; font-size: 13px; }
.print-message { margin-top: 14px; text-align: center; font-size: 13px; font-weight: 700; }
.print-summary {
border: 1px solid #000;
padding: 10px;
margin-bottom: 12px;
display: grid;
gap: 6px;
}
.print-summary div { font-size: 13px; }
.print-footer {
margin-top: 32px;
display: flex;
justify-content: space-between;
gap: 24px;
font-size: 12px;
}
.print-footer div {
width: 42%;
border-top: 1px dashed #000;
padding-top: 8px;
text-align: center;
}
.print-brand-footer {
margin-top: 18px;
padding-top: 8px;
border-top: 1px solid #000;
text-align: center;
font-size: 12px;
font-weight: 700;
}
#printArea .invoice-table { width: 100%; border-collapse: collapse; display: table; }
#printArea .invoice-table th,
#printArea .invoice-table td {
border: 1px solid #000;
padding: 8px;
text-align: right;
font-size: 13px;
color: #000;
white-space: normal;
}
html[dir="ltr"] #printArea .invoice-table th,
html[dir="ltr"] #printArea .invoice-table td { text-align: left; }
#printArea .invoice-table th { background: #000; color: #fff; }
#printArea .total-section {
margin-top: 16px;
border: 1px solid #000;
border-radius: 0;
padding: 10px 12px;
background: #fff;
}
#printArea .total-section .total-row {
border-bottom: none;
padding: 4px 0;
font-size: 13px;
color: #000;
margin: 0;
background: none;
border-radius: 0;
}
#printArea .total-section .total-row.final {
border-top: 2px solid #000;
margin-top: 6px;
padding-top: 8px;
font-weight: 800;
font-size: 15px;
color: #000;
background: none;
}
#printArea .total-amount { color: #000; font-size: 14px; }
#printArea .total-row.final .total-amount { color: #000; font-size: 16px; }
}
@media (max-width: 768px) {
.island-nav { top: auto; bottom: 16px; padding: 8px; gap: 2px; }
.island-nav .nav-item { padding: 10px; font-size: 11px; }
.island-nav .nav-item .label { display: none; }
.island-nav .theme-btn { width: 38px; height: 38px; }
.side-island {
top: auto;
transform: translateX(-50%);
left: 50%;
inset-inline-start: auto;
bottom: 82px;
flex-direction: row;
padding: 6px 8px;
border-radius: var(--radius-island);
max-height: none;
max-width: calc(100% - 24px);
overflow-x: auto;
overflow-y: hidden;
}
.side-island-title { display: none; }
.side-item { width: 58px; padding: 8px 4px; font-size: 9px; }
.side-item .icon { width: 16px; height: 16px; }
.container { padding: 24px 16px 170px; }
body.app-active .container { max-width: 1100px; }
.header { padding: 20px 16px 24px; }
.header h1 { font-size: 26px; }
.stats { grid-template-columns: 1fr; }
.invoice-meta-grid { grid-template-columns: 1fr; }
.report-toolbar { flex-direction: column; align-items: stretch; }
.report-filters { flex-direction: column; }
.report-filters .form-group { width: 100%; }
.actions { flex-direction: column; }
.actions .btn { width: 100%; justify-content: center; }
.card { padding: 18px; }
.invoice-history-header { flex-direction: column; align-items: flex-start; }
.invoice-history-details { flex-direction: column; align-items: flex-start; }
.invoice-history-actions { width: 100%; }
.invoice-history-actions .btn { flex: 1; justify-content: center; }
}
@media (max-width: 480px) {
.island-nav { left: 12px; right: 12px; transform: none; max-width: none; }
.invoice-table { font-size: 11px; }
.invoice-table th, .invoice-table td { padding: 8px 6px; }
}
</style>
</head>
<body>

<nav class="island-nav" id="islandNav" style="display: none;">
<button class="nav-item active" data-tab="settings" onclick="switchTab('settings')">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
<span class="label" data-i18n="tabSettings">تنظیمات</span>
</button>
<button class="nav-item" data-tab="categories" onclick="switchTab('categories')">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
<span class="label" data-i18n="tabCategories">دسته‌بندی</span>
</button>
<button class="nav-item" data-tab="products" onclick="switchTab('products')">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
<span class="label" data-i18n="tabProducts">محصولات</span>
</button>
<button class="nav-item" data-tab="invoice" onclick="switchTab('invoice')">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
<span class="label" data-i18n="tabInvoice">فاکتور</span>
</button>
<button class="nav-item" data-tab="history" onclick="switchTab('history')">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
<span class="label" data-i18n="tabHistory">تاریخچه</span>
</button>
<button class="nav-item" data-tab="calendar" onclick="switchTab('calendar')">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
<span class="label" data-i18n="tabCalendar">تقویم</span>
</button>
<button class="nav-item theme-btn" onclick="toggleTheme()" title="Theme">
<svg class="icon" id="appThemeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
</button>
</nav>

<nav class="side-island" id="sideIsland" style="display: none;">
<div class="side-island-title" data-i18n="tabWarehouse">انبار</div>
<button class="side-item active" data-wtab="inventory" onclick="switchWarehouse('inventory')">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="M3.3 7 12 12l8.7-5"/><path d="M12 22V12"/></svg>
<span data-i18n="whMenuStock">موجودی انبار</span>
</button>
<button class="side-item" data-wtab="return" onclick="switchWarehouse('return')">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
<span data-i18n="whMenuReturn">ثبت مرجوعی</span>
</button>
<button class="side-item" data-wtab="returns" onclick="switchWarehouse('returns')">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
<span data-i18n="whMenuReturns">مرجوعی‌ها</span>
</button>
<button class="side-item" data-wtab="moves" onclick="switchWarehouse('moves')">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
<span data-i18n="whMenuMoves">تراکنش‌ها</span>
</button>
</nav>

<div id="installer" class="container" style="display: none;">
<div class="card">
<div class="header" style="padding: 0 0 24px 0; margin-bottom: 0;">
<h1 data-i18n="installerTitle">نصب پنل رسا</h1>
<div class="subtitle" data-i18n="installerSubtitle">تنظیمات اولیه را انتخاب کنید</div>
</div>
<div class="form-group">
<label data-i18n="currencyLabel">واحد پول</label>
<div id="installerCurrencyChoices" class="choice-grid"></div>
</div>
<div class="form-group">
<label data-i18n="languageLabel">زبان</label>
<div id="installerLanguageChoices" class="choice-grid"></div>
</div>
<div class="form-group">
<label data-i18n="calendarLabel">تقویم</label>
<div id="installerCalendarChoices" class="choice-grid"></div>
</div>
<button class="btn" onclick="installPanel()" style="width: 100%; justify-content: center; margin-top: 8px;">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
<span data-i18n="installButton">نصب</span>
</button>
</div>
</div>

<div id="app" class="container" style="display: none;">
<div class="header">
<h1 data-i18n="appName">پنل رسا</h1>
<div class="subtitle" data-i18n="subtitle">طراحان</div>
<div class="live-clock" id="liveClock" style="display: none;">
<span class="clock-time" id="clockTime">--:--:--</span>
<span class="clock-date" id="clockDate"></span>
</div>
</div>

<div id="settings" class="tab-content active">
<div class="card">
<div class="card-title" data-i18n="settingsTitle">اطلاعات فروشگاه</div>
<div class="form-group">
<label data-i18n="storeNameLabel">نام فروشگاه</label>
<input type="text" id="storeName">
</div>
<div class="form-group">
<label data-i18n="phoneLabel">شماره تماس</label>
<input type="text" id="storePhone">
</div>
<div class="form-group">
<label data-i18n="addressLabel">آدرس</label>
<textarea id="storeAddress" rows="3"></textarea>
</div>
<div class="form-group">
<label data-i18n="vatPercentLabel">ارزش افزوده (٪)</label>
<input type="number" id="vatPercent" min="0" step="any" value="0">
</div>
<div class="form-group">
<label data-i18n="printMessageLabel">متن پاورقی چاپ فاکتور (اختیاری)</label>
<input type="text" id="printMessage">
</div>
<div class="form-group">
<label data-i18n="currencyLabel">واحد پول</label>
<div id="currencyChoices" class="choice-grid"></div>
<div class="muted" id="currencyLockedNote" data-i18n="currencyLockedNote" style="display: none;"></div>
</div>
<div class="form-group">
<label data-i18n="languageLabel">زبان</label>
<div id="languageChoices" class="choice-grid"></div>
</div>
<div class="form-group">
<label data-i18n="calendarLabel">تقویم</label>
<div id="calendarChoices" class="choice-grid"></div>
</div>
<div class="actions">
<button class="btn" onclick="saveSettings()">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
<span data-i18n="saveSettings">ذخیره تنظیمات</span>
</button>
<button class="btn btn-outline" onclick="exportBackup()">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
<span data-i18n="exportBackup">دانلود پشتیبان</span>
</button>
</div>
</div>

<div class="card card-accent" id="restoreCard">
<div class="card-title" data-i18n="restoreTitle">بازیابی پشتیبان</div>
<div class="switch-row">
<label class="switch">
<input type="checkbox" id="restoreSettingsChk" checked>
<span class="slider"></span>
</label>
<span data-i18n="restoreSettings">تنظیمات فروشگاه</span>
</div>
<div class="switch-row">
<label class="switch">
<input type="checkbox" id="restoreCategoriesChk" checked>
<span class="slider"></span>
</label>
<span data-i18n="restoreCategories">دسته‌بندی‌ها</span>
</div>
<div class="switch-row">
<label class="switch">
<input type="checkbox" id="restoreProductsChk" checked>
<span class="slider"></span>
</label>
<span data-i18n="restoreProducts">محصولات</span>
</div>
<div class="switch-row">
<label class="switch">
<input type="checkbox" id="restoreSalesChk" checked>
<span class="slider"></span>
</label>
<span data-i18n="restoreSales">تاریخچه فروش</span>
</div>
<div class="actions">
<button class="btn btn-outline" onclick="pickBackupFile()">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
<span data-i18n="restoreButton">بازیابی پشتیبان</span>
</button>
</div>
<div class="muted" data-i18n="dropHint" style="margin-top: 12px;">فایل پشتیبان را اینجا بکشید و رها کنید</div>
<input type="file" id="backupFileInput" accept=".json,application/json" style="display: none;" onchange="onBackupFileChange(this)">
</div>
</div>

<div id="categories" class="tab-content">
<div class="card">
<div class="card-title" data-i18n="categoriesTitle">مدیریت دسته‌بندی‌ها</div>
<div class="form-group">
<label data-i18n="categoryNameLabel">نام دسته‌بندی</label>
<input type="text" id="categoryName">
</div>
<button class="btn" onclick="addCategory()">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
<span data-i18n="addCategory">افزودن دسته‌بندی</span>
</button>
<div id="categoriesList" style="margin-top: 20px;"></div>
</div>
</div>

<div id="products" class="tab-content">
<div class="card">
<div class="card-title" data-i18n="productsTitle">مدیریت محصولات</div>
<div class="form-group">
<label data-i18n="productNameLabel">نام محصول</label>
<input type="text" id="productName">
</div>
<div class="form-group">
<label data-i18n="categoryLabel">دسته‌بندی</label>
<select id="productCategory"></select>
</div>
<div class="form-group">
<label data-i18n="priceLabel">قیمت</label>
<input type="number" id="productPrice" min="0" step="any">
</div>
<div class="form-group">
<label data-i18n="barcodeLabel">بارکد</label>
<div class="barcode-row">
<input type="text" id="productBarcode" onkeydown="if(event.key==='Enter'){event.preventDefault();addProduct();}">
<button class="btn btn-outline" onclick="openCameraScan('product')">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
<span data-i18n="scanWithCamera">اسکن با دوربین</span>
</button>
</div>
</div>
<div class="invoice-meta-grid">
<div class="form-group">
<label data-i18n="initialStockLabel">موجودی اولیه</label>
<input type="number" id="productStock" min="0" step="any" value="0">
</div>
<div class="form-group">
<label data-i18n="stockAlertLabel">حداقل موجودی (هشدار)</label>
<input type="number" id="productStockAlert" min="0" step="any" value="0">
</div>
</div>
<button class="btn" onclick="addProduct()">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
<span data-i18n="addProduct">افزودن محصول</span>
</button>
<div id="productsList" style="margin-top: 20px;"></div>
</div>
</div>

<div id="warehouse" class="tab-content">
<div id="wtab-inventory" class="wtab active">
<div class="card">
<div class="card-title" data-i18n="warehouseTitle">مدیریت انبار و موجودی</div>
<div id="lowStockBox"></div>
<div id="inventoryTable"></div>
</div>
</div>
<div id="wtab-return" class="wtab">
<div class="card card-accent">
<div class="card-title" data-i18n="returnTitle">ثبت مرجوعی (برگشت کالا)</div>
<div class="form-group">
<label data-i18n="selectSaleLabel">انتخاب فروش</label>
<select id="returnSaleSelect" onchange="onReturnSaleChange()"></select>
</div>
<div id="returnItemsBox"></div>
<div class="form-group">
<label data-i18n="noteLabel">یادداشت</label>
<textarea id="returnNote" rows="2"></textarea>
</div>
<button class="btn" onclick="recordReturn()">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
<span data-i18n="recordReturn">ثبت مرجوعی</span>
</button>
</div>
</div>
<div id="wtab-returns" class="wtab">
<div class="card">
<div class="card-title" data-i18n="returnsTitle">تاریخچه مرجوعی‌ها</div>
<div id="returnsList"></div>
</div>
</div>
<div id="wtab-moves" class="wtab">
<div class="card">
<div class="card-title" data-i18n="movesTitle">تراکنش‌های انبار</div>
<div id="movesList"></div>
</div>
</div>
</div>

<div id="invoice" class="tab-content">
<div class="card card-accent">
<div class="card-title" data-i18n="invoiceTitle">ساخت فاکتور</div>
<div class="form-group">
<label data-i18n="barcodeSaleTitle">فروش با بارکد</label>
<div class="barcode-row">
<input type="text" id="barcodeInput" onkeydown="if(event.key==='Enter'){event.preventDefault();addProductByBarcode();}">
<button class="btn btn-outline" onclick="openCameraScan('invoice')">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
<span data-i18n="scanWithCamera">اسکن با دوربین</span>
</button>
<button class="btn" onclick="addProductByBarcode()">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5v14"/><path d="M8 5v14"/><path d="M12 5v14"/><path d="M17 5v14"/><path d="M21 5v14"/></svg>
<span data-i18n="addToInvoice">افزودن به فاکتور</span>
</button>
</div>
<div class="muted" data-i18n="scanHint">بارکد را اسکن کنید یا وارد کنید و Enter بزنید</div>
</div>
<div class="invoice-meta-grid">
<div class="form-group">
<label data-i18n="invoiceIdLabel">آیدی فاکتور</label>
<input type="text" id="invoiceIdInput" readonly>
</div>
<div class="form-group">
<label data-i18n="invoiceNumberLabel">شماره فاکتور</label>
<input type="text" id="invoiceNumberInput" readonly>
</div>
</div>
<div class="invoice-meta-grid">
<div class="form-group">
<label data-i18n="customerNameLabel">نام مشتری</label>
<input type="text" id="customerName">
</div>
<div class="form-group">
<label data-i18n="customerPhoneLabel">شماره مشتری</label>
<input type="text" id="customerPhone">
</div>
</div>
<div class="switch-row">
<label class="switch">
<input type="checkbox" id="manualOnlyToggle" onchange="onManualOnlyToggle()">
<span class="slider"></span>
</label>
<span data-i18n="manualOnlyLabel">فقط محصول دستی</span>
</div>
<div class="form-group">
<label data-i18n="categoryFilterLabel">فیلتر دسته‌بندی</label>
<select id="invoiceCategory" onchange="renderInvoiceProductSelect()"></select>
</div>
<div class="form-group">
<label data-i18n="selectProduct">انتخاب محصول</label>
<select id="invoiceProduct"></select>
</div>
<div class="form-group">
<label data-i18n="quickAddLabel">افزودن سریع محصول</label>
<div id="quickProducts" class="quick-grid"></div>
</div>
<div class="form-group">
<label data-i18n="manualProductNameLabel">نام محصول دستی</label>
<input type="text" id="manualProductName">
</div>
<div class="form-group">
<label data-i18n="manualProductCategoryLabel">دسته‌بندی محصول دستی</label>
<select id="manualProductCategory"></select>
</div>
<div class="form-group">
<label data-i18n="manualPriceLabel">قیمت دستی</label>
<input type="number" id="manualProductPrice" min="0" step="any">
</div>
<div class="form-group">
<label data-i18n="quantityLabel">تعداد</label>
<input type="number" id="productQuantity" value="1" min="1">
</div>
<button class="btn" onclick="addToInvoice()" style="width: 100%; justify-content: center;">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
<span data-i18n="addToInvoice">افزودن به فاکتور</span>
</button>
<div id="invoiceItems"></div>
<div class="total-section" id="totalSection" style="display: none;">
<div class="total-row">
<span data-i18n="totalLabel">جمع کل:</span>
<span id="totalAmount" class="total-amount">0</span>
</div>
<div class="total-row">
<span data-i18n="discountLabel">تخفیف:</span>
<span id="discountAmount" class="total-amount">0</span>
</div>
<div class="total-row">
<span data-i18n="vatLabel">ارزش افزوده:</span>
<span id="vatAmount" class="total-amount">0</span>
</div>
<div class="total-row final">
<span data-i18n="payableLabel">قابل پرداخت:</span>
<span id="finalAmount" class="total-amount">0</span>
</div>
</div>
<div style="margin-top: 20px;">
<div class="form-group" style="margin-bottom: 12px;">
<label data-i18n="discountTypeLabel">نوع تخفیف</label>
<select id="discountType" onchange="onDiscountInput()">
<option value="amount" data-i18n="discountAmountType">مبلغی</option>
<option value="percent" data-i18n="discountPercentType">درصدی</option>
</select>
</div>
<div class="form-group" style="margin-bottom: 0;">
<label data-i18n="discountLabel">تخفیف</label>
<input type="number" id="invoiceDiscount" min="0" step="any" value="0" oninput="onDiscountInput()">
</div>
</div>
<div style="margin-top: 20px;">
<div class="form-group" style="margin-bottom: 0;">
<label data-i18n="invoiceNoteLabel">یادداشت فاکتور</label>
<textarea id="invoiceNote" rows="2" oninput="onNoteInput()"></textarea>
</div>
</div>
<div class="actions">
<button class="btn" onclick="recordSale()">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
<span data-i18n="recordSale">ثبت فروش</span>
</button>
<button class="btn btn-outline" onclick="saveInvoice(true)">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>
<span data-i18n="saveInvoiceList">ذخیره</span>
</button>
<button class="btn btn-outline" onclick="repeatLastInvoice()">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M8 16H3v5"/></svg>
<span data-i18n="repeatLast">تکرار</span>
</button>
<button class="btn btn-outline" onclick="newInvoice()">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="M9 15h6"/></svg>
<span data-i18n="newInvoice">جدید</span>
</button>
<button class="btn btn-outline" onclick="printInvoice()">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
<span data-i18n="printInvoice">چاپ</span>
</button>
</div>
</div>

<div class="card card-accent">
<div class="card-title" data-i18n="invoiceHistoryTitle">تاریخچه فاکتورها</div>
<div class="search-box">
<svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
<input type="text" id="invoiceSearch" placeholder="جستجو با شماره یا آیدی فاکتور..." oninput="filterInvoiceHistory()">
</div>
<div id="invoiceHistoryList"></div>
</div>
</div>

<div id="history" class="tab-content">
<div class="stats">
<div class="stat-card">
<div class="stat-icon">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
</div>
<div class="stat-body">
<div class="stat-label" data-i18n="todaySalesLabel">فروش امروز</div>
<div class="stat-value" id="todayTotal">0</div>
<div class="muted" id="todayCount">0</div>
</div>
</div>
<div class="stat-card">
<div class="stat-icon">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
</div>
<div class="stat-body">
<div class="stat-label" data-i18n="todayLabel">امروز</div>
<div class="stat-value" id="todayJalali">-</div>
</div>
</div>
<div class="stat-card">
<div class="stat-icon">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
</div>
<div class="stat-body">
<div class="stat-label" data-i18n="thisWeekLabel">این هفته</div>
<div class="muted" id="weekRange">-</div>
<div class="stat-value" id="weekTotal">0</div>
<div class="muted" id="weekCount">0</div>
</div>
</div>
<div class="stat-card">
<div class="stat-icon">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
</div>
<div class="stat-body">
<div class="stat-label" data-i18n="thisMonthLabel">این ماه</div>
<div class="stat-value" id="monthTotal">0</div>
<div class="muted" id="monthCount">0</div>
</div>
</div>
</div>

<div class="card">
<div class="card-title" data-i18n="weekChartTitle">نمودار فروش ۷ روز اخیر</div>
<div class="bar-chart" id="weekChart"></div>
</div>

<div class="card">
<div class="report-toolbar">
<div class="report-filters">
<div class="form-group">
<label data-i18n="reportTypeLabel">نوع گزارش</label>
<select id="reportFilterType" onchange="onReportTypeChange()">
<option value="day" data-i18n="dayReport">روز</option>
<option value="week" data-i18n="weekReport">هفته</option>
<option value="month" selected data-i18n="monthReport">ماه</option>
</select>
</div>
<div class="form-group">
<label data-i18n="reportCalendarLabel">تقویم گزارش</label>
<select id="reportCalendarType" onchange="onReportCalendarChange()">
<option value="jalali" data-i18n="jalaliCalendar">شمسی</option>
<option value="gregorian" data-i18n="gregorianCalendar">میلادی</option>
</select>
</div>
<div class="form-group">
<label data-i18n="yearLabel">سال</label>
<select id="reportYear" onchange="onReportDatePartChange()"></select>
</div>
<div class="form-group">
<label data-i18n="monthLabel">ماه</label>
<select id="reportMonth" onchange="onReportDatePartChange()"></select>
</div>
<div class="form-group" id="reportDayWrap" style="display: none;">
<label data-i18n="dayLabel">روز</label>
<select id="reportDay" onchange="renderSalesReport()"></select>
</div>
</div>
<div class="actions" style="margin-top: 0;">
<button class="btn btn-outline" onclick="printSalesReport()">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
<span data-i18n="printReport">چاپ گزارش</span>
</button>
<button class="btn btn-outline" onclick="exportReportCsv()">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>
<span data-i18n="exportReportCsv">خروجی اکسل (CSV)</span>
</button>
</div>
</div>
<div class="report-summary">
<div class="muted" id="reportPeriodLabel">-</div>
<div class="stat-value" id="reportTotal">0</div>
<div class="muted" id="reportMeta">0</div>
</div>
<div id="reportTable"></div>
</div>

<div id="salesTable"></div>
</div>

<div id="calendar" class="tab-content">
<div class="card">
<div class="calendar-mode">
<button class="calendar-mode-btn active" id="jalaliModeBtn" onclick="setCalendarMode('jalali')" data-i18n="jalaliCalendar">شمسی</button>
<button class="calendar-mode-btn" id="gregorianModeBtn" onclick="setCalendarMode('gregorian')" data-i18n="gregorianCalendar">میلادی</button>
</div>
<div class="calendar-header">
<button class="calendar-nav" onclick="changeCalendarMonth(-1)">
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
<span data-i18n="prev">قبلی</span>
</button>
<div id="calendarLabel" style="font-weight: 700;"></div>
<button class="calendar-nav" onclick="changeCalendarMonth(1)">
<span data-i18n="next">بعدی</span>
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
</button>
</div>
<div id="calendarGrid" class="calendar-grid"></div>
</div>
</div>
</div>

<div id="scanModal" class="scan-modal" style="display: none;">
<div class="scan-dialog">
<div class="scan-header">
<strong id="scanTitle">اسکن بارکد</strong>
<button class="scan-close" onclick="closeCameraScan()"><span data-i18n="scanClose">بستن</span></button>
</div>
<div id="scanReader"></div>
<div class="scan-hint" data-i18n="scanHintText">بارکد را مقابل دوربین بگیرید؛ پس از تشخیص خودکار اضافه می‌شود</div>
</div>
</div>

<div id="printArea"></div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let appData = {
settings: {
name: '', phone: '', address: '', currency: 'IRT', language: 'fa',
calendar: 'jalali', installed: false, currency_locked: false, vat: 0, theme: 'light', print_message: ''
},
categories: [], products: [], sales: [], returns: [], stockMoves: [], history: {}
};
let invoiceItems = [];
let invoiceDiscount = 0;
let invoiceId = '';
let invoiceNumber = '';
let invoiceCustomerName = '';
let invoiceCustomerPhone = '';
let invoiceNote = '';
let manualOnlyEnabled = false;
let invoiceDiscountType = 'amount';
let installerCurrency = 'IRT';
let installerLanguage = 'fa';
let installerCalendar = 'jalali';
let calendarInitialized = false;
let calendarMode = 'jalali';
let calendarSettingApplied = false;
let calendarJy = 0;
let calendarJm = 1;
const initialNow = new Date();
let calendarGy = initialNow.getFullYear();
let calendarGm = initialNow.getMonth() + 1;
let reportInitialized = false;
let clockStarted = false;
let returnSaleId = '';
let warehouseTab = 'inventory';
let html5QrCode = null;
let scanMode = 'invoice';
let scanCooldownCode = '';
let scanCooldownTime = 0;
let audioCtx = null;
let currentReport = { type: 'month', label: '', filtered: [], subtotal: 0, discount: 0, vat: 0, total: 0 };
const languageTimezones = {
fa: 'Asia/Tehran', en: 'America/New_York', en_GB: 'Europe/London',
fr: 'Europe/Paris', de: 'Europe/Berlin', es: 'Europe/Madrid'
};
const jalaliMonthNames = [
'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
];
const jalaliMonthNamesByLang = {
fa: jalaliMonthNames,
en: ['Farvardin', 'Ordibehesht', 'Khordad', 'Tir', 'Mordad', 'Shahrivar', 'Mehr', 'Aban', 'Azar', 'Dey', 'Bahman', 'Esfand'],
fr: ['Farvardin', 'Ordibehesht', 'Khordad', 'Tir', 'Mordad', 'Shahrivar', 'Mehr', 'Aban', 'Azar', 'Dey', 'Bahman', 'Esfand'],
de: ['Farvardin', 'Ordibehesht', 'Khordad', 'Tir', 'Mordad', 'Shahrivar', 'Mehr', 'Aban', 'Azar', 'Dey', 'Bahman', 'Esfand'],
es: ['Farvardin', 'Ordibehesht', 'Khordad', 'Tir', 'Mordad', 'Shahrivar', 'Mehr', 'Aban', 'Azar', 'Dey', 'Bahman', 'Esfand'],
en_GB: ['Farvardin', 'Ordibehesht', 'Khordad', 'Tir', 'Mordad', 'Shahrivar', 'Mehr', 'Aban', 'Azar', 'Dey', 'Bahman', 'Esfand']
};
const languageLocales = { fa: 'fa-IR', en: 'en-US', fr: 'fr-FR', de: 'de-DE', es: 'es-ES', en_GB: 'en-GB' };
const flagUS = '<svg class="flag" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg"><rect width="60" height="40" fill="#fff"/><rect y="0" width="60" height="6" fill="#b22234"/><rect y="12" width="60" height="6" fill="#b22234"/><rect y="24" width="60" height="6" fill="#b22234"/><rect y="36" width="60" height="4" fill="#b22234"/><rect width="26" height="22" fill="#3c3b6e"/></svg>';
const flagEU = '<svg class="flag" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg"><rect width="60" height="40" fill="#003399"/><circle cx="30" cy="20" r="10" fill="none" stroke="#ffcc00" stroke-width="3"/></svg>';
const flagOM = '<svg class="flag" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg"><rect width="60" height="40" fill="#fff"/><rect width="18" height="40" fill="#c8102e"/><rect y="0" width="60" height="13" fill="#007a3d"/><rect y="27" width="60" height="13" fill="#c8102e"/></svg>';
const flagIR = '<svg class="flag" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg"><rect width="60" height="40" fill="#fff"/><rect width="60" height="13" fill="#239f40"/><rect y="27" width="60" height="13" fill="#da0000"/><circle cx="30" cy="20" r="5" fill="#da0000"/></svg>';
const flagFR = '<svg class="flag" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg"><rect width="20" height="40" fill="#0055a4"/><rect x="20" width="20" height="40" fill="#fff"/><rect x="40" width="20" height="40" fill="#ef4135"/></svg>';
const flagDE = '<svg class="flag" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg"><rect width="60" height="13" fill="#000"/><rect y="13" width="60" height="13" fill="#dd0000"/><rect y="26" width="60" height="14" fill="#ffce00"/></svg>';
const flagES = '<svg class="flag" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg"><rect width="60" height="40" fill="#aa151b"/><rect y="10" width="60" height="20" fill="#f1bf00"/></svg>';
const flagGB = '<svg class="flag" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg"><rect width="60" height="40" fill="#012169"/><path d="M0 0L60 40M60 0L0 40" stroke="#fff" stroke-width="8"/><path d="M0 0L60 40M60 0L0 40" stroke="#c8102e" stroke-width="4"/><path d="M30 0V40M0 20H60" stroke="#fff" stroke-width="12"/><path d="M30 0V40M0 20H60" stroke="#c8102e" stroke-width="6"/></svg>';
const currencyDefinitions = [
{ id: 'USD', flag: flagUS }, { id: 'EUR', flag: flagEU }, { id: 'OMR', flag: flagOM },
{ id: 'IRT', flag: flagIR }, { id: 'IRR', flag: flagIR }
];
const languageDefinitions = [
{ id: 'fa', flag: flagIR }, { id: 'en', flag: flagUS }, { id: 'fr', flag: flagFR },
{ id: 'de', flag: flagDE }, { id: 'es', flag: flagES }, { id: 'en_GB', flag: flagGB }
];
const calendarDefinitions = [{ id: 'jalali' }, { id: 'gregorian' }, { id: 'both' }];
const translations = {
fa: {
appName: 'پنل رسا',
subtitle: 'Mrpablo - HST Dev - 2.4',
tabSettings: 'تنظیمات', tabCategories: 'دسته‌بندی', tabProducts: 'محصولات',
tabWarehouse: 'انبار', tabInvoice: 'فاکتور', tabHistory: 'تاریخچه', tabCalendar: 'تقویم',
whMenuStock: 'موجودی انبار', whMenuReturn: 'ثبت مرجوعی', whMenuReturns: 'مرجوعی‌ها', whMenuMoves: 'تراکنش‌ها',
barcodeLabel: 'بارکد', barcodeSaleTitle: 'فروش با بارکد',
barcodePlaceholder: 'بارکد را اسکن یا وارد کنید',
scanHint: 'بارکد را اسکن کنید یا وارد کنید و Enter بزنید',
barcodeNotFound: 'محصولی با این بارکد یافت نشد',
barcodeDuplicate: 'این بارکد قبلا ثبت شده است',
scanWithCamera: 'اسکن با دوربین', scanBarcodeTitle: 'اسکن بارکد',
scanHintText: 'بارکد را مقابل کادر قرار دهید؛ همه انواع بارکد و QR پشتیبانی می‌شود',
scanCameraError: 'دسترسی به دوربین ممکن نشد یا دوربین یافت نشد',
scanLibMissing: 'کتابخانه اسکن بارگذاری نشده است (اتصال اینترنت لازم است)',
scanClose: 'بستن', scanSuccess: 'اسکن موفق',
settingsTitle: 'اطلاعات فروشگاه', storeNameLabel: 'نام فروشگاه', phoneLabel: 'شماره تماس',
addressLabel: 'آدرس', saveSettings: 'ذخیره تنظیمات', exportBackup: 'دانلود پشتیبان',
restoreTitle: 'بازیابی پشتیبان', restoreSettings: 'تنظیمات فروشگاه',
restoreCategories: 'دسته‌بندی‌ها', restoreProducts: 'محصولات', restoreSales: 'تاریخچه فروش',
restoreButton: 'بازیابی پشتیبان',
confirmRestore: 'عملیات بازیابی انجام شود؟ داده‌های انتخابی جایگزین می‌شوند.',
restoreDone: 'بازیابی با موفقیت انجام شد',
invalidBackup: 'فایل پشتیبان معتبر نیست',
chooseRestoreSection: 'حداقل یک بخش را برای بازیابی انتخاب کنید',
dropHint: 'فایل پشتیبان را اینجا بکشید و رها کنید',
printMessageLabel: 'متن پاورقی چاپ فاکتور (اختیاری)',
exportReportCsv: 'خروجی اکسل (CSV)',
loadInvoice: 'بارگذاری در فاکتور',
confirmLoadInvoice: 'این فاکتور در ویرایشگر بارگذاری شود؟ محتوای فعلی جایگزین می‌شود.',
confirmDeleteSale: 'این فروش برای همیشه حذف شود؟',
todaySalesLabel: 'فروش امروز', weekChartTitle: 'نمودار فروش ۷ روز اخیر',
quickAddLabel: 'افزودن سریع محصول', addedToInvoice: 'به فاکتور اضافه شد',
warehouseTitle: 'مدیریت انبار و موجودی', stockLabel: 'موجودی',
stockAlertLabel: 'حداقل موجودی (هشدار)', initialStockLabel: 'موجودی اولیه',
receiveStock: 'ورود کالا', countStock: 'ثبت انبارگردانی', countedQtyLabel: 'موجودی شمارش‌شده',
lowStockTitle: 'هشدار موجودی کم', lowStockNone: 'موجودی همه کالاها کافی است',
returnTitle: 'ثبت مرجوعی (برگشت کالا)', selectSaleLabel: 'انتخاب فروش',
returnQtyLabel: 'تعداد مرجوعی', recordReturn: 'ثبت مرجوعی',
returnsTitle: 'تاریخچه مرجوعی‌ها', emptyReturns: 'مرجوعی‌ای ثبت نشده است',
movesTitle: 'تراکنش‌های انبار', emptyMoves: 'تراکنشی ثبت نشده است',
moveTypeLabel: 'نوع تراکنش', move_in: 'ورود کالا', move_out: 'خروج کالا',
move_count: 'انبارگردانی', move_sale: 'فروش', move_return: 'مرجوعی',
returnDone: 'مرجوعی با موفقیت ثبت شد', stockDone: 'موجودی به‌روزرسانی شد',
invalidQty: 'تعداد معتبر وارد کنید', noReturnItems: 'هیچ قلمی برای مرجوعی انتخاب نشده است',
returnNumberLabel: 'شماره مرجوعی', remainingLabel: 'قابل مرجوعی', returnShort: 'مرجوعی',
currencyLabel: 'واحد پول', languageLabel: 'زبان', calendarLabel: 'تقویم',
vatLabel: 'ارزش افزوده', vatPercentLabel: 'ارزش افزوده (٪)',
installerTitle: 'نصب پنل رسا', installerSubtitle: 'تنظیمات اولیه را انتخاب کنید', installButton: 'نصب',
currencyLockedNote: 'واحد پول پس از نصب قفل شده است',
categoriesTitle: 'مدیریت دسته‌بندی‌ها', categoryNameLabel: 'نام دسته‌بندی', addCategory: 'افزودن دسته‌بندی',
emptyCategories: 'هنوز دسته‌بندی‌ای ثبت نشده است',
productsTitle: 'مدیریت محصولات', productNameLabel: 'نام محصول', categoryLabel: 'دسته‌بندی',
priceLabel: 'قیمت', addProduct: 'افزودن محصول', emptyProducts: 'هنوز محصولی ثبت نشده است',
selectCategory: 'انتخاب دسته‌بندی', invoiceTitle: 'ساخت فاکتور',
invoiceIdLabel: 'آیدی فاکتور', invoiceNumberLabel: 'شماره فاکتور',
categoryFilterLabel: 'فیلتر دسته‌بندی', allCategories: 'همه دسته‌بندی‌ها',
selectProduct: 'انتخاب محصول', manualProductNameLabel: 'نام محصول دستی',
manualProductCategoryLabel: 'دسته‌بندی محصول دستی', noCategory: 'بدون دسته‌بندی',
manualPriceLabel: 'قیمت دستی', manualOnlyLabel: 'فقط محصول دستی',
quantityLabel: 'تعداد', addToInvoice: 'افزودن به فاکتور',
discountLabel: 'تخفیف', discountTypeLabel: 'نوع تخفیف',
discountAmountType: 'مبلغی', discountPercentType: 'درصدی',
totalLabel: 'جمع کل', payableLabel: 'قابل پرداخت',
recordSale: 'ثبت فروش', saveInvoiceList: 'ذخیره', newInvoice: 'جدید',
repeatLast: 'تکرار', printInvoice: 'چاپ',
customerNameLabel: 'نام مشتری', customerPhoneLabel: 'شماره مشتری',
invoiceNoteLabel: 'یادداشت فاکتور', deleteLabel: 'حذف',
historyTitle: 'تاریخچه فروش', todayLabel: 'امروز', thisWeekLabel: 'این هفته', thisMonthLabel: 'این ماه',
reportTypeLabel: 'نوع گزارش', reportCalendarLabel: 'تقویم گزارش',
dayReport: 'روز', weekReport: 'هفته', monthReport: 'ماه',
yearLabel: 'سال', monthLabel: 'ماه', dayLabel: 'روز', printReport: 'چاپ گزارش',
prev: 'قبلی', next: 'بعدی', jalaliCalendar: 'شمسی', gregorianCalendar: 'میلادی',
emptySales: 'هنوز فروشی ثبت نشده است', emptyReport: 'فروشی در این بازه ثبت نشده است',
row: 'ردیف', category: 'دسته‌بندی', productName: 'نام محصول', unitPrice: 'قیمت واحد',
quantity: 'تعداد', sum: 'جمع', operations: 'عملیات', date: 'تاریخ',
itemsCount: 'تعداد اقلام', total: 'جمع کل', discount: 'تخفیف', payable: 'قابل پرداخت',
reportTitle: 'گزارش فروش', invoiceTitlePrint: 'فاکتور فروش', printDate: 'تاریخ چاپ',
invoiceDate: 'تاریخ', customerLabel: 'مشتری', noteLabel: 'یادداشت',
settingsSaved: 'تنظیمات ذخیره شد', backupExported: 'پشتیبان دانلود شد',
noLastInvoice: 'فاکتور قبلی وجود ندارد',
categoryRequired: 'نام دسته‌بندی را وارد کنید',
productFields: 'نام، دسته‌بندی و قیمت محصول را وارد کنید',
quantityValid: 'تعداد معتبر وارد کنید',
chooseOrManual: 'یک محصول انتخاب کنید یا نام و قیمت دستی را وارد کنید',
invoiceEmpty: 'فاکتور خالی است', saleRecorded: 'فروش با موفقیت ثبت شد',
invoiceSaved: 'لیست فاکتور ذخیره شد', reportEmpty: 'گزارشی برای چاپ وجود ندارد',
confirmDeleteCategory: 'این دسته‌بندی حذف شود؟', confirmDeleteProduct: 'این محصول حذف شود؟',
confirmNewInvoice: 'فاکتور جدید شروع شود؟', confirmRecordSale: 'فاکتور ثبت شود؟',
error: 'خطا', salesWord: 'فروش', invoiceHistoryTitle: 'تاریخچه فاکتورها',
searchPlaceholder: 'جستجو با شماره یا آیدی فاکتور...', noInvoiceFound: 'فاکتوری یافت نشد',
printSale: 'چاپ', storeSignLabel: 'مهر و امضای فروشگاه', panelFooter: 'پنل مدیریت فروش رسا',
dateTimeLabel: 'تاریخ و ساعت',
calendar_jalali: 'تقویم شمسی', calendar_gregorian: 'تقویم میلادی', calendar_both: 'هر دو تقویم',
currency_USD: 'دلار', currency_EUR: 'یورو', currency_OMR: 'ریال عمان', currency_IRT: 'تومان', currency_IRR: 'ریال',
language_fa: 'فارسی', language_en: 'انگلیسی', language_fr: 'فرانسوی',
language_de: 'آلمانی', language_es: 'اسپانیایی', language_en_gb: 'انگلیسی (UK)'
},
en: {
appName: 'Panel Rasa',
subtitle: 'Version 2.5 - Designer & Developer: Mr. Pablo',
tabSettings: 'Settings', tabCategories: 'Categories', tabProducts: 'Products',
tabWarehouse: 'Warehouse', tabInvoice: 'Invoice', tabHistory: 'History', tabCalendar: 'Calendar',
whMenuStock: 'Stock', whMenuReturn: 'New return', whMenuReturns: 'Returns', whMenuMoves: 'Movements',
barcodeLabel: 'Barcode', barcodeSaleTitle: 'Sell by barcode',
barcodePlaceholder: 'Scan or enter barcode',
scanHint: 'Scan or type the barcode and press Enter',
barcodeNotFound: 'No product found with this barcode',
barcodeDuplicate: 'This barcode is already registered',
scanWithCamera: 'Scan with camera', scanBarcodeTitle: 'Scan barcode',
scanHintText: 'Point the camera at the code; all barcode & QR types are supported',
scanCameraError: 'Camera access failed or no camera found',
scanLibMissing: 'Scanner library not loaded (internet connection required)',
scanClose: 'Close', scanSuccess: 'Scan successful',
settingsTitle: 'Store Information', storeNameLabel: 'Store name', phoneLabel: 'Phone number',
addressLabel: 'Address', saveSettings: 'Save settings', exportBackup: 'Download backup',
restoreTitle: 'Restore Backup', restoreSettings: 'Store settings',
restoreCategories: 'Categories', restoreProducts: 'Products', restoreSales: 'Sales history',
restoreButton: 'Restore backup',
confirmRestore: 'Perform restore? Selected data will be replaced.',
restoreDone: 'Restore completed successfully',
invalidBackup: 'Invalid backup file',
chooseRestoreSection: 'Select at least one section to restore',
dropHint: 'Drag & drop the backup file here',
printMessageLabel: 'Invoice print footer message (optional)',
exportReportCsv: 'Excel export (CSV)',
loadInvoice: 'Load into invoice',
confirmLoadInvoice: 'Load this invoice into the editor? Current content will be replaced.',
confirmDeleteSale: 'Delete this sale permanently?',
todaySalesLabel: 'Today sales', weekChartTitle: 'Last 7 days sales chart',
quickAddLabel: 'Quick add product', addedToInvoice: 'Added to invoice',
warehouseTitle: 'Warehouse & Stock Management', stockLabel: 'Stock',
stockAlertLabel: 'Minimum stock (alert)', initialStockLabel: 'Initial stock',
receiveStock: 'Receive stock', countStock: 'Stocktake', countedQtyLabel: 'Counted stock',
lowStockTitle: 'Low stock alert', lowStockNone: 'All products have sufficient stock',
returnTitle: 'Record return (goods back)', selectSaleLabel: 'Select sale',
returnQtyLabel: 'Return quantity', recordReturn: 'Record return',
returnsTitle: 'Returns history', emptyReturns: 'No returns recorded',
movesTitle: 'Stock movements', emptyMoves: 'No movements recorded',
moveTypeLabel: 'Movement type', move_in: 'Stock in', move_out: 'Stock out',
move_count: 'Stocktake', move_sale: 'Sale', move_return: 'Return',
returnDone: 'Return recorded successfully', stockDone: 'Stock updated',
invalidQty: 'Enter a valid quantity', noReturnItems: 'No items selected for return',
returnNumberLabel: 'Return number', remainingLabel: 'Returnable', returnShort: 'Return',
currencyLabel: 'Currency', languageLabel: 'Language', calendarLabel: 'Calendar',
vatLabel: 'VAT', vatPercentLabel: 'VAT (%)',
installerTitle: 'Install Panel Rasa', installerSubtitle: 'Choose initial settings', installButton: 'Install',
currencyLockedNote: 'Currency is locked after installation',
categoriesTitle: 'Category Management', categoryNameLabel: 'Category name', addCategory: 'Add category',
emptyCategories: 'No categories have been added yet.',
productsTitle: 'Product Management', productNameLabel: 'Product name', categoryLabel: 'Category',
priceLabel: 'Price', addProduct: 'Add product', emptyProducts: 'No products have been added yet.',
selectCategory: 'Select category', invoiceTitle: 'Create Invoice',
invoiceIdLabel: 'Invoice ID', invoiceNumberLabel: 'Invoice Number',
categoryFilterLabel: 'Category filter', allCategories: 'All categories',
selectProduct: 'Select product', manualProductNameLabel: 'Manual product name',
manualProductCategoryLabel: 'Manual product category', noCategory: 'No category',
manualPriceLabel: 'Manual price', manualOnlyLabel: 'Manual only',
quantityLabel: 'Quantity', addToInvoice: 'Add to invoice',
discountLabel: 'Discount', discountTypeLabel: 'Discount type',
discountAmountType: 'Amount', discountPercentType: 'Percent',
totalLabel: 'Subtotal', payableLabel: 'Payable',
recordSale: 'Record sale', saveInvoiceList: 'Save', newInvoice: 'New',
repeatLast: 'Repeat', printInvoice: 'Print',
customerNameLabel: 'Customer name', customerPhoneLabel: 'Customer phone',
invoiceNoteLabel: 'Invoice note', deleteLabel: 'Delete',
historyTitle: 'Sales History', todayLabel: 'Today', thisWeekLabel: 'This week', thisMonthLabel: 'This month',
reportTypeLabel: 'Report type', reportCalendarLabel: 'Report calendar',
dayReport: 'Day', weekReport: 'Week', monthReport: 'Month',
yearLabel: 'Year', monthLabel: 'Month', dayLabel: 'Day', printReport: 'Print report',
prev: 'Previous', next: 'Next', jalaliCalendar: 'Jalali', gregorianCalendar: 'Gregorian',
emptySales: 'No sales have been recorded yet.', emptyReport: 'No sales found for this period.',
row: 'Row', category: 'Category', productName: 'Product name', unitPrice: 'Unit price',
quantity: 'Quantity', sum: 'Sum', operations: 'Actions', date: 'Date',
itemsCount: 'Items', total: 'Total', discount: 'Discount', payable: 'Payable',
reportTitle: 'Sales Report', invoiceTitlePrint: 'Sales Invoice', printDate: 'Print date',
invoiceDate: 'Date', customerLabel: 'Customer', noteLabel: 'Note',
settingsSaved: 'Settings saved.', backupExported: 'Backup downloaded.',
noLastInvoice: 'No previous invoice.',
categoryRequired: 'Please enter category name.',
productFields: 'Please enter product name, category and price.',
quantityValid: 'Enter a valid quantity.',
chooseOrManual: 'Select a product or enter manual product name and price.',
invoiceEmpty: 'Invoice is empty.', saleRecorded: 'Sale recorded successfully.',
invoiceSaved: 'Invoice list saved.', reportEmpty: 'No report to print.',
confirmDeleteCategory: 'Delete this category?', confirmDeleteProduct: 'Delete this product?',
confirmNewInvoice: 'Start a new invoice?', confirmRecordSale: 'Record this sale?',
error: 'Error', salesWord: 'sales', invoiceHistoryTitle: 'Invoice History',
searchPlaceholder: 'Search by invoice number or ID...', noInvoiceFound: 'No invoice found',
printSale: 'Print', storeSignLabel: 'Store stamp & signature', panelFooter: 'Rasa Sales Management Panel',
dateTimeLabel: 'Date & time',
calendar_jalali: 'Jalali calendar', calendar_gregorian: 'Gregorian calendar', calendar_both: 'Both calendars',
currency_USD: 'US Dollar', currency_EUR: 'Euro', currency_OMR: 'Omani Rial', currency_IRT: 'Iran Toman', currency_IRR: 'Iran Rial',
language_fa: 'Persian', language_en: 'English', language_fr: 'French',
language_de: 'German', language_es: 'Spanish', language_en_gb: 'English (UK)'
},
fr: {
appName: 'Panel Rasa',
subtitle: 'Version 2.5 - Concepteur et développeur : M. Pablo',
tabSettings: 'Paramètres', tabCategories: 'Catégories', tabProducts: 'Produits',
tabWarehouse: 'Entrepôt', tabInvoice: 'Facture', tabHistory: 'Historique', tabCalendar: 'Calendrier',
whMenuStock: 'Stock', whMenuReturn: 'Retour', whMenuReturns: 'Retours', whMenuMoves: 'Mouvements',
barcodeLabel: 'Code-barres', barcodeSaleTitle: 'Vente par code-barres',
barcodePlaceholder: 'Scannez ou saisissez le code-barres',
scanHint: 'Scannez ou saisissez le code puis appuyez sur Entrée',
barcodeNotFound: 'Aucun produit trouvé avec ce code-barres',
barcodeDuplicate: 'Ce code-barres est déjà enregistré',
scanWithCamera: 'Scanner avec la caméra', scanBarcodeTitle: 'Scanner le code-barres',
scanHintText: 'Dirigez la caméra vers le code ; tous les types de codes et QR sont pris en charge',
scanCameraError: 'Accès à la caméra impossible ou caméra introuvable',
scanLibMissing: 'Bibliothèque de scan non chargée (connexion Internet requise)',
scanClose: 'Fermer', scanSuccess: 'Scan réussi',
settingsTitle: 'Informations de la boutique', storeNameLabel: 'Nom de la boutique', phoneLabel: 'Numéro de téléphone',
addressLabel: 'Adresse', saveSettings: 'Enregistrer', exportBackup: 'Télécharger sauvegarde',
restoreTitle: 'Restaurer la sauvegarde', restoreSettings: 'Paramètres de la boutique',
restoreCategories: 'Catégories', restoreProducts: 'Produits', restoreSales: 'Historique des ventes',
restoreButton: 'Restaurer',
confirmRestore: 'Effectuer la restauration ? Les données sélectionnées seront remplacées.',
restoreDone: 'Restauration réussie', invalidBackup: 'Fichier de sauvegarde invalide',
chooseRestoreSection: 'Sélectionnez au moins une section à restaurer',
dropHint: 'Glissez-déposez le fichier ici',
printMessageLabel: 'Message de pied de page (optionnel)', exportReportCsv: 'Export Excel (CSV)',
loadInvoice: 'Charger dans la facture',
confirmLoadInvoice: 'Charger cette facture dans l\'éditeur ?',
confirmDeleteSale: 'Supprimer définitivement cette vente ?',
todaySalesLabel: 'Ventes du jour', weekChartTitle: 'Ventes des 7 derniers jours',
quickAddLabel: 'Ajout rapide', addedToInvoice: 'Ajouté à la facture',
warehouseTitle: 'Gestion de stock et entrepôt', stockLabel: 'Stock',
stockAlertLabel: 'Stock minimum (alerte)', initialStockLabel: 'Stock initial',
receiveStock: 'Réception', countStock: 'Inventaire', countedQtyLabel: 'Stock compté',
lowStockTitle: 'Alerte stock faible', lowStockNone: 'Stock suffisant partout',
returnTitle: 'Enregistrer un retour', selectSaleLabel: 'Sélectionner la vente',
returnQtyLabel: 'Quantité retournée', recordReturn: 'Enregistrer le retour',
returnsTitle: 'Historique des retours', emptyReturns: 'Aucun retour enregistré',
movesTitle: 'Mouvements de stock', emptyMoves: 'Aucun mouvement',
moveTypeLabel: 'Type de mouvement', move_in: 'Entrée', move_out: 'Sortie',
move_count: 'Inventaire', move_sale: 'Vente', move_return: 'Retour',
returnDone: 'Retour enregistré', stockDone: 'Stock mis à jour',
invalidQty: 'Quantité invalide', noReturnItems: 'Aucun article sélectionné',
returnNumberLabel: 'Numéro de retour', remainingLabel: 'Retournable', returnShort: 'Retour',
currencyLabel: 'Devise', languageLabel: 'Langue', calendarLabel: 'Calendrier',
vatLabel: 'TVA', vatPercentLabel: 'TVA (%)',
installerTitle: 'Installation de Panel Rasa', installerSubtitle: 'Choisissez les paramètres initiaux', installButton: 'Installer',
currencyLockedNote: 'La devise est verrouillée après installation',
categoriesTitle: 'Gestion des catégories', categoryNameLabel: 'Nom de la catégorie', addCategory: 'Ajouter une catégorie',
emptyCategories: 'Aucune catégorie enregistrée.',
productsTitle: 'Gestion des produits', productNameLabel: 'Nom du produit', categoryLabel: 'Catégorie',
priceLabel: 'Prix', addProduct: 'Ajouter un produit', emptyProducts: 'Aucun produit enregistré.',
selectCategory: 'Sélectionner une catégorie', invoiceTitle: 'Créer une facture',
invoiceIdLabel: 'N° ID', invoiceNumberLabel: 'N° Facture',
categoryFilterLabel: 'Filtre de catégorie', allCategories: 'Toutes les catégories',
selectProduct: 'Sélectionner un produit', manualProductNameLabel: 'Nom de produit manuel',
manualProductCategoryLabel: 'Catégorie du produit manuel', noCategory: 'Sans catégorie',
manualPriceLabel: 'Prix manuel', manualOnlyLabel: 'Manuel uniquement',
quantityLabel: 'Quantité', addToInvoice: 'Ajouter à la facture',
discountLabel: 'Remise', discountTypeLabel: 'Type de remise',
discountAmountType: 'Montant', discountPercentType: 'Pourcentage',
totalLabel: 'Total', payableLabel: 'À payer',
recordSale: 'Enregistrer', saveInvoiceList: 'Sauvegarder', newInvoice: 'Nouveau',
repeatLast: 'Répéter', printInvoice: 'Imprimer',
customerNameLabel: 'Nom du client', customerPhoneLabel: 'Téléphone du client',
invoiceNoteLabel: 'Note de facture', deleteLabel: 'Supprimer',
historyTitle: 'Historique', todayLabel: 'Aujourd\'hui', thisWeekLabel: 'Cette semaine', thisMonthLabel: 'Ce mois',
reportTypeLabel: 'Type', reportCalendarLabel: 'Calendrier du rapport',
dayReport: 'Jour', weekReport: 'Semaine', monthReport: 'Mois',
yearLabel: 'Année', monthLabel: 'Mois', dayLabel: 'Jour', printReport: 'Imprimer',
prev: 'Précédent', next: 'Suivant', jalaliCalendar: 'Solaire', gregorianCalendar: 'Grégorien',
emptySales: 'Aucune vente.', emptyReport: 'Aucune vente.',
row: 'Ligne', category: 'Catégorie', productName: 'Produit', unitPrice: 'Prix unit.',
quantity: 'Qté', sum: 'Somme', operations: 'Actions', date: 'Date',
itemsCount: 'Articles', total: 'Total', discount: 'Remise', payable: 'À payer',
reportTitle: 'Rapport', invoiceTitlePrint: 'Facture', printDate: 'Date',
invoiceDate: 'Date', customerLabel: 'Client', noteLabel: 'Note',
settingsSaved: 'Enregistré.', backupExported: 'Sauvegarde téléchargée.',
noLastInvoice: 'Aucune facture.', categoryRequired: 'Nom requis.', productFields: 'Champs requis.',
quantityValid: 'Quantité invalide.', chooseOrManual: 'Sélectionnez ou entrez.',
invoiceEmpty: 'Vide.', saleRecorded: 'Enregistré.', invoiceSaved: 'Sauvegardé.', reportEmpty: 'Vide.',
confirmDeleteCategory: 'Supprimer?', confirmDeleteProduct: 'Supprimer?',
confirmNewInvoice: 'Nouveau?', confirmRecordSale: 'Enregistrer?',
error: 'Erreur', salesWord: 'ventes', invoiceHistoryTitle: 'Historique des factures',
searchPlaceholder: 'Rechercher par numéro...', noInvoiceFound: 'Aucune facture trouvée',
printSale: 'Imprimer', storeSignLabel: 'Cachet et signature du magasin', panelFooter: 'Panneau de gestion des ventes Rasa',
dateTimeLabel: 'Date',
calendar_jalali: 'Solaire', calendar_gregorian: 'Grégorien', calendar_both: 'Les deux',
currency_USD: 'Dollar', currency_EUR: 'Euro', currency_OMR: 'Rial omanais', currency_IRT: 'Toman', currency_IRR: 'Rial',
language_fa: 'Persan', language_en: 'Anglais', language_fr: 'Français',
language_de: 'Allemand', language_es: 'Espagnol', language_en_gb: 'Anglais (UK)'
},
de: {
appName: 'Panel Rasa',
subtitle: 'Version 2.5 - Designer & Entwickler: Herr Pablo',
tabSettings: 'Einstellungen', tabCategories: 'Kategorien', tabProducts: 'Produkte',
tabWarehouse: 'Lager', tabInvoice: 'Rechnung', tabHistory: 'Verlauf', tabCalendar: 'Kalender',
whMenuStock: 'Bestand', whMenuReturn: 'Rückgabe', whMenuReturns: 'Rückgaben', whMenuMoves: 'Bewegungen',
barcodeLabel: 'Barcode', barcodeSaleTitle: 'Verkauf per Barcode',
barcodePlaceholder: 'Barcode scannen oder eingeben',
scanHint: 'Barcode scannen oder eingeben und Enter drücken',
barcodeNotFound: 'Kein Produkt mit diesem Barcode gefunden',
barcodeDuplicate: 'Dieser Barcode ist bereits registriert',
scanWithCamera: 'Mit Kamera scannen', scanBarcodeTitle: 'Barcode scannen',
scanHintText: 'Kamera auf den Code richten; alle Barcode- und QR-Typen werden unterstützt',
scanCameraError: 'Kamerazugriff fehlgeschlagen oder keine Kamera gefunden',
scanLibMissing: 'Scanner-Bibliothek nicht geladen (Internetverbindung erforderlich)',
scanClose: 'Schließen', scanSuccess: 'Scan erfolgreich',
settingsTitle: 'Shopinformationen', storeNameLabel: 'Shopname', phoneLabel: 'Telefonnummer',
addressLabel: 'Adresse', saveSettings: 'Speichern', exportBackup: 'Backup',
restoreTitle: 'Backup wiederherstellen', restoreSettings: 'Shop-Einstellungen',
restoreCategories: 'Kategorien', restoreProducts: 'Produkte', restoreSales: 'Verkaufshistorie',
restoreButton: 'Wiederherstellen',
confirmRestore: 'Wiederherstellung durchführen?', restoreDone: 'Wiederherstellung erfolgreich',
invalidBackup: 'Ungültige Backup-Datei', chooseRestoreSection: 'Mindestens einen Bereich auswählen',
dropHint: 'Backup-Datei hier ablegen',
printMessageLabel: 'Fußzeilentext (optional)', exportReportCsv: 'Excel-Export (CSV)',
loadInvoice: 'In Rechnung laden', confirmLoadInvoice: 'Diese Rechnung in den Editor laden?',
confirmDeleteSale: 'Diesen Verkauf dauerhaft löschen?',
todaySalesLabel: 'Heutige Verkäufe', weekChartTitle: 'Verkäufe der letzten 7 Tage',
quickAddLabel: 'Schnelles Hinzufügen', addedToInvoice: 'Zur Rechnung hinzugefügt',
warehouseTitle: 'Lager- & Bestandsverwaltung', stockLabel: 'Bestand',
stockAlertLabel: 'Mindestbestand (Warnung)', initialStockLabel: 'Anfangsbestand',
receiveStock: 'Wareneingang', countStock: 'Inventur', countedQtyLabel: 'Gezählter Bestand',
lowStockTitle: 'Warnung: niedriger Bestand', lowStockNone: 'Alle Bestände ausreichend',
returnTitle: 'Rückgabe erfassen', selectSaleLabel: 'Verkauf auswählen',
returnQtyLabel: 'Rückgabemenge', recordReturn: 'Rückgabe erfassen',
returnsTitle: 'Rückgabehistorie', emptyReturns: 'Keine Rückgaben',
movesTitle: 'Lagerbewegungen', emptyMoves: 'Keine Bewegungen',
moveTypeLabel: 'Bewegungsart', move_in: 'Wareneingang', move_out: 'Warenausgang',
move_count: 'Inventur', move_sale: 'Verkauf', move_return: 'Rückgabe',
returnDone: 'Rückgabe erfasst', stockDone: 'Bestand aktualisiert',
invalidQty: 'Gültige Menge eingeben', noReturnItems: 'Keine Artikel ausgewählt',
returnNumberLabel: 'Rückgabenummer', remainingLabel: 'Rückgabbar', returnShort: 'Rückgabe',
currencyLabel: 'Währung', languageLabel: 'Sprache', calendarLabel: 'Kalender',
vatLabel: 'MwSt.', vatPercentLabel: 'MwSt. (%)',
installerTitle: 'Installation', installerSubtitle: 'Einstellungen wählen', installButton: 'Installieren',
currencyLockedNote: 'Währung gesperrt',
categoriesTitle: 'Kategorien', categoryNameLabel: 'Name', addCategory: 'Hinzufügen',
emptyCategories: 'Keine.', productsTitle: 'Produkte', productNameLabel: 'Name', categoryLabel: 'Kategorie',
priceLabel: 'Preis', addProduct: 'Hinzufügen', emptyProducts: 'Keine.',
selectCategory: 'Kategorie', invoiceTitle: 'Rechnung',
invoiceIdLabel: 'ID', invoiceNumberLabel: 'Rechnungsnr.',
categoryFilterLabel: 'Filter', allCategories: 'Alle', selectProduct: 'Produkt',
manualProductNameLabel: 'Name', manualProductCategoryLabel: 'Kategorie', noCategory: 'Keine',
manualPriceLabel: 'Preis', manualOnlyLabel: 'Nur manuell',
quantityLabel: 'Menge', addToInvoice: 'Hinzufügen',
discountLabel: 'Rabatt', discountTypeLabel: 'Typ', discountAmountType: 'Betrag', discountPercentType: 'Prozent',
totalLabel: 'Summe', payableLabel: 'Zahlbar',
recordSale: 'Buchen', saveInvoiceList: 'Speichern', newInvoice: 'Neu', repeatLast: 'Wiederholen', printInvoice: 'Drucken',
customerNameLabel: 'Kundenname', customerPhoneLabel: 'Kundentelefon', invoiceNoteLabel: 'Notiz', deleteLabel: 'Löschen',
historyTitle: 'Verkaufshistorie', todayLabel: 'Heute', thisWeekLabel: 'Diese Woche', thisMonthLabel: 'Dieser Monat',
reportTypeLabel: 'Berichtstyp', reportCalendarLabel: 'Berichtskalender',
dayReport: 'Tag', weekReport: 'Woche', monthReport: 'Monat',
yearLabel: 'Jahr', monthLabel: 'Monat', dayLabel: 'Tag', printReport: 'Drucken',
prev: 'Zurück', next: 'Weiter', jalaliCalendar: 'Jalali', gregorianCalendar: 'Gregorianisch',
emptySales: 'Keine Verkäufe.', emptyReport: 'Keine Verkäufe.',
row: 'Zeile', category: 'Kategorie', productName: 'Produktname', unitPrice: 'Preis',
quantity: 'Menge', sum: 'Summe', operations: 'Aktionen', date: 'Datum',
itemsCount: 'Artikel', total: 'Gesamt', discount: 'Rabatt', payable: 'Zahlbar',
reportTitle: 'Bericht', invoiceTitlePrint: 'Rechnung', printDate: 'Datum',
invoiceDate: 'Datum', customerLabel: 'Kunde', noteLabel: 'Notiz',
settingsSaved: 'Gespeichert.', backupExported: 'Heruntergeladen.', noLastInvoice: 'Keine.',
categoryRequired: 'Name erforderlich.', productFields: 'Felder erforderlich.', quantityValid: 'Ungültig.',
chooseOrManual: 'Auswählen.', invoiceEmpty: 'Leer.', saleRecorded: 'Erfasst.', invoiceSaved: 'Gespeichert.', reportEmpty: 'Leer.',
confirmDeleteCategory: 'Löschen?', confirmDeleteProduct: 'Löschen?', confirmNewInvoice: 'Neu?', confirmRecordSale: 'Buchen?',
error: 'Fehler', salesWord: 'Verkäufe', invoiceHistoryTitle: 'Rechnungshistorie',
searchPlaceholder: 'Suchen...', noInvoiceFound: 'Nicht gefunden',
printSale: 'Drucken', storeSignLabel: 'Stempel & Unterschrift des Shops', panelFooter: 'Rasa Verkaufsverwaltungs-Panel',
dateTimeLabel: 'Datum',
calendar_jalali: 'Jalali', calendar_gregorian: 'Gregorianisch', calendar_both: 'Beide',
currency_USD: 'Dollar', currency_EUR: 'Euro', currency_OMR: 'Rial', currency_IRT: 'Toman', currency_IRR: 'Rial',
language_fa: 'Persisch', language_en: 'Englisch', language_fr: 'Französisch',
language_de: 'Deutsch', language_es: 'Spanisch', language_en_gb: 'Englisch (UK)'
},
es: {
appName: 'Panel Rasa',
subtitle: 'Versión 2.5 - Diseñador: Sr. Pablo',
tabSettings: 'Ajustes', tabCategories: 'Categorías', tabProducts: 'Productos',
tabWarehouse: 'Almacén', tabInvoice: 'Factura', tabHistory: 'Historial', tabCalendar: 'Calendario',
whMenuStock: 'Stock', whMenuReturn: 'Devolución', whMenuReturns: 'Devoluciones', whMenuMoves: 'Movimientos',
barcodeLabel: 'Código de barras', barcodeSaleTitle: 'Venta por código de barras',
barcodePlaceholder: 'Escanee o introduzca el código',
scanHint: 'Escanee o escriba el código y pulse Enter',
barcodeNotFound: 'No se encontró producto con este código',
barcodeDuplicate: 'Este código ya está registrado',
scanWithCamera: 'Escanear con cámara', scanBarcodeTitle: 'Escanear código',
scanHintText: 'Apunte la cámara al código; se admiten todos los tipos de código y QR',
scanCameraError: 'Falló el acceso a la cámara o no se encontró cámara',
scanLibMissing: 'Biblioteca de escaneo no cargada (se requiere conexión a Internet)',
scanClose: 'Cerrar', scanSuccess: 'Escaneo correcto',
settingsTitle: 'Información', storeNameLabel: 'Nombre', phoneLabel: 'Teléfono', addressLabel: 'Dirección',
saveSettings: 'Guardar', exportBackup: 'Descargar',
restoreTitle: 'Restaurar copia', restoreSettings: 'Ajustes de la tienda',
restoreCategories: 'Categorías', restoreProducts: 'Productos', restoreSales: 'Historial de ventas',
restoreButton: 'Restaurar', confirmRestore: '¿Realizar la restauración?', restoreDone: 'Restauración completada',
invalidBackup: 'Archivo inválido', chooseRestoreSection: 'Seleccione al menos una sección',
dropHint: 'Suelte el archivo aquí', printMessageLabel: 'Mensaje de pie (opcional)', exportReportCsv: 'Exportar Excel (CSV)',
loadInvoice: 'Cargar en factura', confirmLoadInvoice: '¿Cargar esta factura en el editor?', confirmDeleteSale: '¿Eliminar esta venta?',
todaySalesLabel: 'Ventas de hoy', weekChartTitle: 'Ventas de los últimos 7 días',
quickAddLabel: 'Añadir rápido', addedToInvoice: 'Añadido a la factura',
warehouseTitle: 'Gestión de almacén e inventario', stockLabel: 'Stock',
stockAlertLabel: 'Stock mínimo (alerta)', initialStockLabel: 'Stock inicial',
receiveStock: 'Entrada', countStock: 'Inventario', countedQtyLabel: 'Stock contado',
lowStockTitle: 'Alerta de stock bajo', lowStockNone: 'Stock suficiente en todo',
returnTitle: 'Registrar devolución', selectSaleLabel: 'Seleccionar venta',
returnQtyLabel: 'Cantidad a devolver', recordReturn: 'Registrar devolución',
returnsTitle: 'Historial de devoluciones', emptyReturns: 'Sin devoluciones',
movesTitle: 'Movimientos de almacén', emptyMoves: 'Sin movimientos',
moveTypeLabel: 'Tipo de movimiento', move_in: 'Entrada', move_out: 'Salida',
move_count: 'Inventario', move_sale: 'Venta', move_return: 'Devolución',
returnDone: 'Devolución registrada', stockDone: 'Stock actualizado',
invalidQty: 'Cantidad inválida', noReturnItems: 'No hay artículos seleccionados',
returnNumberLabel: 'Número de devolución', remainingLabel: 'Devoluble', returnShort: 'Devolución',
currencyLabel: 'Moneda', languageLabel: 'Idioma', calendarLabel: 'Calendario', vatLabel: 'IVA', vatPercentLabel: 'IVA (%)',
installerTitle: 'Instalación', installerSubtitle: 'Configuración inicial', installButton: 'Instalar',
currencyLockedNote: 'Moneda bloqueada',
categoriesTitle: 'Categorías', categoryNameLabel: 'Nombre', addCategory: 'Añadir', emptyCategories: 'Vacío.',
productsTitle: 'Productos', productNameLabel: 'Nombre', categoryLabel: 'Categoría', priceLabel: 'Precio',
addProduct: 'Añadir', emptyProducts: 'Vacío.', selectCategory: 'Categoría', invoiceTitle: 'Factura',
invoiceIdLabel: 'ID', invoiceNumberLabel: 'N° Factura', categoryFilterLabel: 'Filtro', allCategories: 'Todas',
selectProduct: 'Producto', manualProductNameLabel: 'Nombre', manualProductCategoryLabel: 'Categoría',
noCategory: 'Sin categoría', manualPriceLabel: 'Precio', manualOnlyLabel: 'Solo manual',
quantityLabel: 'Cantidad', addToInvoice: 'Añadir', discountLabel: 'Descuento', discountTypeLabel: 'Tipo',
discountAmountType: 'Monto', discountPercentType: 'Porcentaje', totalLabel: 'Total', payableLabel: 'A pagar',
recordSale: 'Registrar', saveInvoiceList: 'Guardar', newInvoice: 'Nuevo', repeatLast: 'Repetir', printInvoice: 'Imprimir',
customerNameLabel: 'Cliente', customerPhoneLabel: 'Teléfono', invoiceNoteLabel: 'Nota', deleteLabel: 'Eliminar',
historyTitle: 'Historial', todayLabel: 'Hoy', thisWeekLabel: 'Esta semana', thisMonthLabel: 'Este mes',
reportTypeLabel: 'Tipo', reportCalendarLabel: 'Calendario del informe',
dayReport: 'Día', weekReport: 'Semana', monthReport: 'Mes', yearLabel: 'Año', monthLabel: 'Mes', dayLabel: 'Día', printReport: 'Imprimir',
prev: 'Anterior', next: 'Siguiente', jalaliCalendar: 'Solar', gregorianCalendar: 'Gregoriano',
emptySales: 'Vacío.', emptyReport: 'Vacío.',
row: 'Fila', category: 'Categoría', productName: 'Producto', unitPrice: 'Precio', quantity: 'Cant.', sum: 'Suma',
operations: 'Acciones', date: 'Fecha', itemsCount: 'Artículos', total: 'Total', discount: 'Descuento', payable: 'A pagar',
reportTitle: 'Informe', invoiceTitlePrint: 'Factura', printDate: 'Fecha', invoiceDate: 'Fecha', customerLabel: 'Cliente', noteLabel: 'Nota',
settingsSaved: 'Guardado.', backupExported: 'Descargado.', noLastInvoice: 'Ninguna.',
categoryRequired: 'Requerido.', productFields: 'Requerido.', quantityValid: 'Inválido.', chooseOrManual: 'Seleccionar.',
invoiceEmpty: 'Vacío.', saleRecorded: 'Registrado.', invoiceSaved: 'Guardado.', reportEmpty: 'Vacío.',
confirmDeleteCategory: '¿Eliminar?', confirmDeleteProduct: '¿Eliminar?', confirmNewInvoice: '¿Nuevo?', confirmRecordSale: '¿Registrar?',
error: 'Error', salesWord: 'ventas', invoiceHistoryTitle: 'Historial de facturas',
searchPlaceholder: 'Buscar...', noInvoiceFound: 'No encontrado',
printSale: 'Imprimir', storeSignLabel: 'Sello y firma de la tienda', panelFooter: 'Panel de gestión de ventas Rasa',
dateTimeLabel: 'Fecha',
calendar_jalali: 'Solar', calendar_gregorian: 'Gregoriano', calendar_both: 'Ambos',
currency_USD: 'Dólar', currency_EUR: 'Euro', currency_OMR: 'Rial', currency_IRT: 'Toman', currency_IRR: 'Rial',
language_fa: 'Persa', language_en: 'Inglés', language_fr: 'Francés', language_de: 'Alemán', language_es: 'Español', language_en_gb: 'Inglés (RU)'
},
en_GB: {}
};
const sunIcon = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
const moonIcon = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
const iconTrash = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';
const iconPrint = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>';
const iconEdit = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
const iconReturn = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>';
const iconPlus = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>';
const iconCheck = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
function t(key) {
const lang = appData.settings.language || 'fa';
if (translations[lang] && translations[lang].hasOwnProperty(key)) return translations[lang][key];
if (lang === 'en_GB' && translations.en && translations.en.hasOwnProperty(key)) return translations.en[key];
if (translations.fa && translations.fa.hasOwnProperty(key)) return translations.fa[key];
return key;
}
function currentLocale() { return languageLocales[appData.settings.language || 'fa'] || 'fa-IR'; }
function gregorianCalendarLocale() { return appData.settings.language === 'fa' ? 'en-US' : currentLocale(); }
function escapeHtml(value) {
return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
function formatPrice(value) { return Number(value || 0).toLocaleString(currentLocale(), { maximumFractionDigits: 2 }); }
function formatNumber(value) { return Number(value || 0).toLocaleString(currentLocale(), { maximumFractionDigits: 0 }); }
function formatDayNumber(value) { return Number(value || 0).toLocaleString(currentLocale(), { useGrouping: false }); }
function persianDigits(value) {
if ((appData.settings.language || 'fa') !== 'fa') return String(value);
return String(value).replace(/[0-9]/g, function (digit) { return '۰۱۲۳۴۵۶۷۸۹'[digit]; });
}
function currencyLabel() { return t('currency_' + (appData.settings.currency || 'IRT')); }
function invoiceCurrencyLabel() {
if ((appData.settings.currency || 'IRT') === 'IRT') return 'تومان';
return currencyLabel();
}
function formatMoney(value) { return formatPrice(value) + ' ' + invoiceCurrencyLabel(); }
function getJalaliMonthName(jm) {
const lang = appData.settings.language || 'fa';
const arr = jalaliMonthNamesByLang[lang] || jalaliMonthNamesByLang.en;
return arr[jm - 1] || '';
}
function getVatRate() {
const rate = Number(appData.settings.vat || 0);
return isNaN(rate) || rate < 0 ? 0 : rate;
}
function getEffectiveDiscount() {
const subtotal = getSubtotal();
const discountType = document.getElementById('discountType') ? document.getElementById('discountType').value : invoiceDiscountType;
let discountValue = Number(invoiceDiscount || 0);
if (isNaN(discountValue) || discountValue < 0) discountValue = 0;
if (discountType === 'percent') {
if (discountValue > 100) discountValue = 100;
return subtotal * discountValue / 100;
} else {
if (discountValue > subtotal) discountValue = subtotal;
return discountValue;
}
}
function getVatAmount() {
const subtotal = getSubtotal();
const discount = getEffectiveDiscount();
const vatRate = getVatRate();
const base = subtotal - discount;
return base * vatRate / 100;
}
function toast(message, type) {
let wrap = document.getElementById('toastWrap');
if (!wrap) {
wrap = document.createElement('div');
wrap.id = 'toastWrap';
document.body.appendChild(wrap);
}
const el = document.createElement('div');
el.className = 'toast ' + (type || 'success');
el.textContent = message;
wrap.appendChild(el);
setTimeout(function () { el.classList.add('show'); }, 10);
setTimeout(function () {
el.classList.remove('show');
setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 300);
}, 3200);
}
function beep() {
try {
if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
if (audioCtx.state === 'suspended') audioCtx.resume();
const o = audioCtx.createOscillator();
const g = audioCtx.createGain();
o.type = 'sine';
o.frequency.value = 880;
o.connect(g);
g.connect(audioCtx.destination);
g.gain.setValueAtTime(0.001, audioCtx.currentTime);
g.gain.exponentialRampToValueAtTime(0.2, audioCtx.currentTime + 0.01);
g.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.15);
o.start();
o.stop(audioCtx.currentTime + 0.16);
} catch (e) {}
}
function updateLiveClock() {
const wrap = document.getElementById('liveClock');
if (!wrap || wrap.style.display === 'none') return;
const lang = appData.settings.language || 'fa';
const tz = languageTimezones[lang] || 'Asia/Tehran';
const locale = currentLocale();
const now = new Date();
let timeText;
try {
timeText = now.toLocaleTimeString(locale, { hour: '2-digit', minute: '2-digit', second: '2-digit', timeZone: tz });
} catch (e) {
timeText = now.toLocaleTimeString(locale, { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
let iso;
try {
iso = new Intl.DateTimeFormat('en-CA', { timeZone: tz, year: 'numeric', month: '2-digit', day: '2-digit' }).format(now);
} catch (e) {
iso = now.toISOString().slice(0, 10);
}
const p = iso.split('-');
const gy = Number(p[0]), gm = Number(p[1]), gd = Number(p[2]);
const j = gregorianToJalali(gy, gm, gd);
const jalaliText = formatDayNumber(j[2]) + ' ' + getJalaliMonthName(j[1]) + ' ' + formatDayNumber(j[0]);
document.getElementById('clockTime').textContent = timeText;
document.getElementById('clockDate').textContent = jalaliText + ' | ' + formatGregorianString(gy, gm, gd);
}
function startLiveClock() {
if (clockStarted) return;
clockStarted = true;
updateLiveClock();
setInterval(updateLiveClock, 1000);
}
function applyLanguage() {
const lang = appData.settings.language || 'fa';
document.documentElement.lang = lang === 'en_GB' ? 'en-GB' : lang;
document.documentElement.dir = lang === 'fa' ? 'rtl' : 'ltr';
document.title = t('appName');
document.querySelectorAll('[data-i18n]').forEach(function (element) {
element.textContent = t(element.getAttribute('data-i18n'));
});
const searchInput = document.getElementById('invoiceSearch');
if (searchInput) searchInput.placeholder = t('searchPlaceholder');
const barcodeInput = document.getElementById('barcodeInput');
if (barcodeInput) barcodeInput.placeholder = t('barcodePlaceholder');
}
function applyTheme(theme) {
if (theme === 'dark') { document.body.classList.add('dark'); }
else { document.body.classList.remove('dark'); }
updateThemeIcons();
}
function updateThemeIcons() {
const icon = document.body.classList.contains('dark') ? sunIcon : moonIcon;
const appIcon = document.getElementById('appThemeIcon');
const installerIcon = document.getElementById('installerThemeIcon');
if (appIcon) appIcon.outerHTML = icon.replace('class="icon"', 'class="icon" id="appThemeIcon"');
if (installerIcon) installerIcon.outerHTML = icon.replace('class="icon"', 'class="icon" id="installerThemeIcon"');
}
async function toggleTheme() {
const newTheme = document.body.classList.contains('dark') ? 'light' : 'dark';
appData.settings.theme = newTheme;
applyTheme(newTheme);
if (appData.settings.installed) { await api('toggle_theme', { theme: newTheme }); }
}
function jalDiv(a, b) { return ~~(a / b); }
function jalMod(a, b) { return a - ~~(a / b) * b; }
function jalCal(jy) {
const breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
let gy = jy + 621, leapJ = -14, jp = breaks[0], jump = 0, jm, n, leap, leapG, march, i;
for (i = 1; i < breaks.length; i += 1) {
jm = breaks[i]; jump = jm - jp;
if (jy < jm) break;
leapJ = leapJ + jalDiv(jump, 33) * 8 + jalDiv(jalMod(jump, 33), 4);
jp = jm;
}
n = jy - jp;
leapJ = leapJ + jalDiv(n, 33) * 8 + jalDiv(jalMod(n, 33) + 3, 4);
if (jalMod(jump, 33) === 4 && jump - n === 4) leapJ += 1;
leapG = jalDiv(gy, 4) - jalDiv((jalDiv(gy, 100) + 1) * 3, 4) - 150;
march = 20 + leapJ - leapG;
if (jump - n < 6) n = n - jump + jalDiv(jump + 4, 33) * 33;
leap = jalMod(jalMod(n + 1, 33) - 1, 4);
if (leap === -1) leap = 4;
return { leap: leap, gy: gy, march: march };
}
function g2d(gy, gm, gd) {
return jalDiv((gy + jalDiv(gm - 8, 6) + 100100) * 1461, 4) + jalDiv(153 * jalMod(gm + 9, 12) + 2, 5) + gd - 34840408 - jalDiv(jalDiv(gy + 100100 + jalDiv(gm - 8, 6), 100) * 3, 4) + 752;
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
let k = jdn - jdn1f, jm, jd;
if (k >= 0) {
if (k <= 185) { jm = 1 + jalDiv(k, 31); jd = jalMod(k, 31) + 1; return { jy: jy, jm: jm, jd: jd }; }
k -= 186;
} else {
jy -= 1; k += 179;
if (r.leap === 1) k += 1;
}
jm = 7 + jalDiv(k, 30);
jd = jalMod(k, 30) + 1;
return { jy: jy, jm: jm, jd: jd };
}
function gregorianToJalali(gy, gm, gd) { const d = d2j(g2d(gy, gm, gd)); return [d.jy, d.jm, d.jd]; }
function jalaliToGregorian(jy, jm, jd) { const d = d2g(j2d(jy, jm, jd)); return [d.gy, d.gm, d.gd]; }
function jalaliMonthLength(jy, jm) {
if (jm <= 6) return 31;
if (jm <= 11) return 30;
return jalCal(jy).leap === 0 ? 30 : 29;
}
function formatJalaliString(jy, jm, jd) {
return String(jy).padStart(4, '0') + '/' + String(jm).padStart(2, '0') + '/' + String(jd).padStart(2, '0');
}
function formatGregorianString(y, m, d) {
return String(y).padStart(4, '0') + '/' + String(m).padStart(2, '0') + '/' + String(d).padStart(2, '0');
}
function currentJalali() {
const now = new Date();
return gregorianToJalali(now.getFullYear(), now.getMonth() + 1, now.getDate());
}
function getTodayJalaliObj() {
if (appData.history && appData.history.today) return appData.history.today;
const c = currentJalali();
return { jy: c[0], jm: c[1], jd: c[2] };
}
function getWeekdayNames() {
const locale = currentLocale();
const names = [];
for (let i = 0; i < 7; i++) names.push(new Date(2024, 0, 6 + i).toLocaleDateString(locale, { weekday: 'narrow' }));
return names;
}
function getGregorianWeekdayNames() {
const locale = gregorianCalendarLocale();
const names = [];
for (let i = 0; i < 7; i++) names.push(new Date(2024, 0, 6 + i).toLocaleDateString(locale, { weekday: 'narrow' }));
return names;
}
function getSubtotal() {
return invoiceItems.reduce(function (sum, item) { return sum + (Number(item.price) * Number(item.quantity)); }, 0);
}
function getPanelDateTimeLines() {
const calendarSetting = appData.settings.calendar || 'jalali';
const now = new Date();
const gLocale = gregorianCalendarLocale();
const timeFa = now.toLocaleTimeString(currentLocale(), { hour: '2-digit', minute: '2-digit', second: '2-digit' });
const timeG = now.toLocaleTimeString(gLocale, { hour: '2-digit', minute: '2-digit', second: '2-digit' });
const sep = (appData.settings.language === 'fa') ? '، ' : ', ';
const lines = [];
const j = currentJalali();
const weekday = now.toLocaleDateString(currentLocale(), { weekday: 'long' });
const jalaliDate = weekday + sep + formatDayNumber(j[2]) + ' ' + getJalaliMonthName(j[1]) + ' ' + formatDayNumber(j[0]);
const gregorianDate = now.toLocaleDateString(gLocale, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
if (calendarSetting === 'jalali') lines.push(t('dateTimeLabel') + ': ' + jalaliDate + ' - ' + timeFa);
if (calendarSetting === 'gregorian') lines.push(t('dateTimeLabel') + ': ' + gregorianDate + ' - ' + timeG);
if (calendarSetting === 'both') {
lines.push(t('jalaliCalendar') + ': ' + jalaliDate + ' - ' + timeFa);
lines.push(t('gregorianCalendar') + ': ' + gregorianDate + ' - ' + timeG);
}
return lines;
}
function onDiscountInput() {
const discountType = document.getElementById('discountType') ? document.getElementById('discountType').value : 'amount';
invoiceDiscountType = discountType;
const value = parseFloat(document.getElementById('invoiceDiscount').value);
invoiceDiscount = (!isNaN(value) && value >= 0) ? value : 0;
renderInvoiceItems();
saveInvoice(false);
}
function onNoteInput() { invoiceNote = document.getElementById('invoiceNote').value; saveInvoice(false); }
function onCustomerInput() {
invoiceCustomerName = document.getElementById('customerName').value;
invoiceCustomerPhone = document.getElementById('customerPhone').value;
saveInvoice(false);
}
function onManualOnlyToggle() {
manualOnlyEnabled = document.getElementById('manualOnlyToggle').checked;
applyManualOnlyState();
}
function applyManualOnlyState() {
const filterEl = document.getElementById('invoiceCategory');
const productEl = document.getElementById('invoiceProduct');
if (filterEl) filterEl.disabled = manualOnlyEnabled;
if (productEl) productEl.disabled = manualOnlyEnabled;
renderQuickProducts();
}
async function api(action, payload) {
try {
const response = await fetch('?api=1&action=' + encodeURIComponent(action), {
method: 'POST',
headers: { 'Content-Type': 'application/json' },
body: JSON.stringify(payload || {})
});
return await response.json();
} catch (error) {
return { ok: false, error: t('error') };
}
}
function findProductByBarcode(code) {
const c = String(code || '').trim();
if (!c) return null;
for (let i = 0; i < appData.products.length; i++) {
const p = appData.products[i];
if ((p.barcode || '') === c) return p;
}
return null;
}
function addProductByCode(code) {
const c = String(code || '').trim();
if (!c) return false;
const product = findProductByBarcode(c);
if (!product) {
toast(t('barcodeNotFound'), 'error');
return false;
}
const existing = invoiceItems.find(function (it) { return it.product_id === product.id; });
if (existing) {
existing.quantity += 1;
} else {
invoiceItems.push({ name: product.name, category: product.category || '', price: Number(product.price), quantity: 1, product_id: product.id });
}
renderInvoiceItems();
saveInvoice(false);
toast(t('addedToInvoice') + ': ' + product.name, 'success');
return true;
}
function addProductByBarcode() {
const input = document.getElementById('barcodeInput');
if (!input) return;
const code = input.value.trim();
if (!code) { input.focus(); return; }
const ok = addProductByCode(code);
if (ok) { input.value = ''; input.focus(); }
else { input.select(); }
}
function allScannerFormats() {
try {
if (typeof Html5QrcodeSupportedFormats === 'undefined') return undefined;
const F = Html5QrcodeSupportedFormats;
return [
F.QR_CODE, F.AZTEC, F.CODABAR, F.CODE_39, F.CODE_93, F.CODE_128,
F.DATA_MATRIX, F.EAN_8, F.EAN_13, F.ITF, F.MAXICODE, F.PDF_417,
F.RSS_14, F.RSS_EXPANDED, F.UPC_A, F.UPC_E
];
} catch (e) { return undefined; }
}
async function stopScanner() {
if (html5QrCode) {
try { if (html5QrCode.isScanning) await html5QrCode.stop(); } catch (e) {}
try { html5QrCode.clear(); } catch (e) {}
html5QrCode = null;
}
}
async function openCameraScan(mode) {
scanMode = mode;
scanCooldownCode = '';
scanCooldownTime = 0;
const modal = document.getElementById('scanModal');
const reader = document.getElementById('scanReader');
const title = document.getElementById('scanTitle');
reader.innerHTML = '';
if (title) title.textContent = mode === 'product' ? t('scanBarcodeTitle') : t('barcodeSaleTitle');
modal.style.display = 'flex';
if (typeof Html5Qrcode === 'undefined') {
toast(t('scanLibMissing'), 'error');
closeCameraScan();
return;
}
await stopScanner();
html5QrCode = new Html5Qrcode('scanReader', { verbose: false });
const config = { fps: 10, rememberLastUsedCamera: true, disableFlip: false };
const formats = allScannerFormats();
if (formats) config.formatsToSupport = formats;
const videoConstraints = {
facingMode: { ideal: 'environment' },
width: { ideal: 1920 },
height: { ideal: 1080 },
advanced: [{ focusMode: 'continuous' }]
};
let started = false;
try {
await html5QrCode.start(videoConstraints, config, onScanSuccess, function () {});
started = true;
} catch (e) { started = false; }
if (!started) {
try {
await html5QrCode.start({ facingMode: 'environment' }, config, onScanSuccess, function () {});
started = true;
} catch (e2) { started = false; }
}
if (!started) {
try {
await html5QrCode.start(undefined, config, onScanSuccess, function () {});
started = true;
} catch (e3) { started = false; }
}
if (!started) {
toast(t('scanCameraError'), 'error');
closeCameraScan();
return;
}
const overlay = document.createElement('div');
overlay.className = 'scan-overlay';
reader.appendChild(overlay);
}
async function closeCameraScan() {
const modal = document.getElementById('scanModal');
if (modal) modal.style.display = 'none';
await stopScanner();
}
function onScanSuccess(decodedText) {
const code = String(decodedText || '').trim();
if (!code) return;
const now = Date.now();
if (scanCooldownCode === code && now - scanCooldownTime < 1500) return;
scanCooldownCode = code;
scanCooldownTime = now;
beep();
if (scanMode === 'product') {
const input = document.getElementById('productBarcode');
if (input) input.value = code;
toast(t('scanSuccess') + ': ' + code, 'success');
closeCameraScan();
return;
}
addProductByCode(code);
}
function switchWarehouse(id) {
warehouseTab = id;
document.querySelectorAll('.side-item').forEach(function (b) {
b.classList.toggle('active', b.getAttribute('data-wtab') === id);
});
document.querySelectorAll('.wtab').forEach(function (el) {
el.classList.toggle('active', el.id === 'wtab-' + id);
});
document.querySelectorAll('.nav-item').forEach(function (i) { i.classList.remove('active'); });
document.querySelectorAll('.tab-content').forEach(function (i) { i.classList.remove('active'); });
document.getElementById('warehouse').classList.add('active');
renderWarehouse();
}
function switchTab(tabId) {
document.querySelectorAll('.nav-item').forEach(function (item) { item.classList.remove('active'); });
document.querySelectorAll('.tab-content').forEach(function (item) { item.classList.remove('active'); });
const tabButton = document.querySelector('.nav-item[data-tab="' + tabId + '"]');
if (tabButton) tabButton.classList.add('active');
document.getElementById(tabId).classList.add('active');
if (tabId === 'categories') renderCategories();
if (tabId === 'products') renderProducts();
if (tabId === 'warehouse') renderWarehouse();
if (tabId === 'invoice') {
renderInvoiceCategorySelects();
renderInvoiceProductSelect();
renderInvoiceItems();
renderInvoiceHistory();
const barcodeInput = document.getElementById('barcodeInput');
if (barcodeInput && window.matchMedia('(min-width: 769px)').matches) barcodeInput.focus();
}
if (tabId === 'history') renderHistory();
if (tabId === 'calendar') renderCalendar();
}
function fillSettingsForm() {
document.getElementById('storeName').value = appData.settings.name || '';
document.getElementById('storePhone').value = appData.settings.phone || '';
document.getElementById('storeAddress').value = appData.settings.address || '';
document.getElementById('vatPercent').value = Number(appData.settings.vat || 0);
document.getElementById('printMessage').value = appData.settings.print_message || '';
}
function fillInvoiceForm() {
document.getElementById('invoiceIdInput').value = invoiceId || '';
document.getElementById('invoiceNumberInput').value = invoiceNumber || '';
document.getElementById('customerName').value = invoiceCustomerName || '';
document.getElementById('customerPhone').value = invoiceCustomerPhone || '';
document.getElementById('invoiceNote').value = invoiceNote || '';
['customerName', 'customerPhone'].forEach(function (id) {
const el = document.getElementById(id);
if (el) { el.oninput = onCustomerInput; }
});
}
async function persistSettings(showAlert) {
const payload = {
name: document.getElementById('storeName').value.trim(),
phone: document.getElementById('storePhone').value.trim(),
address: document.getElementById('storeAddress').value.trim(),
currency: appData.settings.currency || 'IRT',
language: appData.settings.language || 'fa',
calendar: appData.settings.calendar || 'jalali',
vat: parseFloat(document.getElementById('vatPercent').value) || 0,
theme: appData.settings.theme || 'light',
print_message: document.getElementById('printMessage').value.trim()
};
const result = await api('save_settings', payload);
if (result.ok) {
appData.settings = Object.assign({}, appData.settings, payload);
if (showAlert) toast(t('settingsSaved'), 'success');
} else if (showAlert) {
toast(result.error || t('error'), 'error');
}
}
async function saveSettings() { await persistSettings(true); refreshMoneyViews(); }
async function exportBackup() {
const result = await api('export_backup', {});
if (result.ok && result.backup) {
const blob = new Blob([JSON.stringify(result.backup, null, 2)], { type: 'application/json' });
const url = URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = 'rasa-backup-' + new Date().toISOString().slice(0, 10) + '.json';
document.body.appendChild(a);
a.click();
document.body.removeChild(a);
URL.revokeObjectURL(url);
toast(t('backupExported'), 'success');
} else {
toast((result && result.error) || t('error'), 'error');
}
}
function pickBackupFile() { document.getElementById('backupFileInput').click(); }
async function handleBackupFile(file) {
let backup = null;
try { backup = JSON.parse(await file.text()); } catch (error) { backup = null; }
if (!backup || typeof backup !== 'object' || Array.isArray(backup)) {
toast(t('invalidBackup'), 'error');
return;
}
const options = {
restore_settings: document.getElementById('restoreSettingsChk').checked,
restore_categories: document.getElementById('restoreCategoriesChk').checked,
restore_products: document.getElementById('restoreProductsChk').checked,
restore_sales: document.getElementById('restoreSalesChk').checked
};
if (!options.restore_settings && !options.restore_categories && !options.restore_products && !options.restore_sales) {
toast(t('chooseRestoreSection'), 'error');
return;
}
if (!confirm(t('confirmRestore'))) return;
options.backup = backup;
const result = await api('restore_backup', options);
if (result.ok) { await loadData(); toast(t('restoreDone'), 'success'); }
else { toast(result.error || t('error'), 'error'); }
}
function onBackupFileChange(input) {
const file = input.files && input.files[0] ? input.files[0] : null;
input.value = '';
if (file) handleBackupFile(file);
}
function initRestoreDrop() {
const card = document.getElementById('restoreCard');
if (!card) return;
card.addEventListener('dragover', function (e) { e.preventDefault(); card.classList.add('dragging'); });
card.addEventListener('dragleave', function () { card.classList.remove('dragging'); });
card.addEventListener('drop', function (e) {
e.preventDefault();
card.classList.remove('dragging');
const file = e.dataTransfer.files && e.dataTransfer.files[0];
if (file) handleBackupFile(file);
});
}
function csvEscape(value) {
let v = String(value);
if (/[",\n\r]/.test(v)) { v = '"' + v.replace(/"/g, '""') + '"'; }
return v;
}
function exportReportCsv() {
if (!currentReport.filtered.length) { toast(t('reportEmpty'), 'error'); return; }
const headers = [t('row'), t('invoiceNumberLabel'), t('invoiceIdLabel'), t('customerLabel'), t('date'), t('itemsCount'), t('total'), t('discount'), t('vatLabel'), t('payable')];
let csv = '\uFEFF';
csv += headers.map(csvEscape).join(',') + '\r\n';
currentReport.filtered.forEach(function (sale, index) {
let itemsCount = 0;
if (Array.isArray(sale.items)) {
sale.items.forEach(function (item) { itemsCount += Number(item.quantity || 0); });
}
const row = [index + 1, sale.invoice_number || '-', sale.invoice_id || '-', sale.customer_name || '-', saleDateText(sale), itemsCount, Number(sale.subtotal || 0), Number(sale.discount || 0), Number(sale.vat || 0), Number(sale.total || 0)];
csv += row.map(csvEscape).join(',') + '\r\n';
});
const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
const url = URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = 'rasa-report-' + new Date().toISOString().slice(0, 10) + '.csv';
document.body.appendChild(a);
a.click();
document.body.removeChild(a);
URL.revokeObjectURL(url);
}
function renderQuickProducts() {
const box = document.getElementById('quickProducts');
if (!box) return;
box.innerHTML = '';
if (manualOnlyEnabled || !appData.products.length) { box.style.display = 'none'; return; }
const selectedCategory = document.getElementById('invoiceCategory') ? document.getElementById('invoiceCategory').value : '';
const list = appData.products.filter(function (p) { return !selectedCategory || p.category === selectedCategory; }).slice(0, 12);
if (!list.length) { box.style.display = 'none'; return; }
box.style.display = 'flex';
list.forEach(function (product) {
const chip = document.createElement('button');
chip.type = 'button';
chip.className = 'quick-chip';
chip.innerHTML = '<strong>' + escapeHtml(product.name) + '</strong><span>' + escapeHtml(formatMoney(product.price)) + '</span>';
chip.onclick = function () {
invoiceItems.push({ name: product.name, category: product.category || '', price: Number(product.price), quantity: 1, product_id: product.id });
renderInvoiceItems();
saveInvoice(false);
toast(t('addedToInvoice'), 'success');
};
box.appendChild(chip);
});
}
async function loadInvoiceFromSale(sale) {
if (!confirm(t('confirmLoadInvoice'))) return;
loadInvoiceData(sale);
switchTab('invoice');
}
function loadInvoiceData(sale) {
invoiceItems = Array.isArray(sale.items) ? JSON.parse(JSON.stringify(sale.items)) : [];
invoiceDiscount = Number(sale.discount_value || sale.discount || 0);
invoiceDiscountType = sale.discount_type || 'amount';
invoiceCustomerName = sale.customer_name || '';
invoiceCustomerPhone = sale.customer_phone || '';
invoiceNote = sale.note || '';
invoiceId = generateInvoiceIdLocal();
invoiceNumber = generateInvoiceNumberLocal();
document.getElementById('invoiceDiscount').value = invoiceDiscount;
if (document.getElementById('discountType')) {
document.getElementById('discountType').value = invoiceDiscountType;
}
fillInvoiceForm();
renderInvoiceItems();
saveInvoice(false);
}
async function deleteSale(id) {
if (!confirm(t('confirmDeleteSale'))) return;
const result = await api('delete_sale', { id: id });
if (result.ok) { await loadData(); }
else { toast(result.error || t('error'), 'error'); }
}
function openReturnFor(saleId) { returnSaleId = saleId; switchWarehouse('return'); }
function renderWarehouse() {
renderLowStock();
renderInventoryTable();
renderReturnForm();
renderReturnsList();
renderMovesList();
}
function renderLowStock() {
const box = document.getElementById('lowStockBox');
if (!box) return;
box.innerHTML = '';
const lowList = appData.products.filter(function (p) { return isLowStock(p); });
const title = document.createElement('div');
title.className = 'card-title';
title.style.fontSize = '14px';
title.textContent = t('lowStockTitle');
box.appendChild(title);
if (!lowList.length) {
const ok = document.createElement('div');
ok.className = 'muted';
ok.style.marginBottom = '12px';
ok.textContent = t('lowStockNone');
box.appendChild(ok);
return;
}
lowList.forEach(function (p) {
const chip = document.createElement('div');
chip.className = 'muted';
chip.style.marginBottom = '6px';
chip.innerHTML = '<span class="stock-low">' + escapeHtml(p.name) + '</span> — ' + t('stockLabel') + ': ' + formatNumber(Number(p.stock || 0));
box.appendChild(chip);
});
}
function isLowStock(product) {
const s = Number(product.stock || 0);
const a = Number(product.stock_alert || 0);
return s <= a;
}
function renderInventoryTable() {
const box = document.getElementById('inventoryTable');
if (!box) return;
box.innerHTML = '';
if (!appData.products.length) {
box.innerHTML = '<div class="empty-state">' + escapeHtml(t('emptyProducts')) + '</div>';
return;
}
const table = document.createElement('table');
table.className = 'invoice-table';
const thead = document.createElement('thead');
const headRow = document.createElement('tr');
[t('productName'), t('category'), t('stockLabel'), t('stockAlertLabel'), t('receiveStock'), t('countStock')].forEach(function (text) {
const th = document.createElement('th');
th.textContent = text;
headRow.appendChild(th);
});
thead.appendChild(headRow);
table.appendChild(thead);
const tbody = document.createElement('tbody');
appData.products.forEach(function (product) {
const row = document.createElement('tr');
const nameCell = document.createElement('td');
nameCell.textContent = product.name;
row.appendChild(nameCell);
const catCell = document.createElement('td');
catCell.textContent = product.category || '-';
row.appendChild(catCell);
const stockCell = document.createElement('td');
const stockVal = document.createElement('strong');
stockVal.textContent = formatNumber(Number(product.stock || 0));
if (isLowStock(product)) stockVal.className = 'stock-low';
stockCell.appendChild(stockVal);
row.appendChild(stockCell);
const alertCell = document.createElement('td');
alertCell.textContent = formatNumber(Number(product.stock_alert || 0));
row.appendChild(alertCell);
const receiveCell = document.createElement('td');
const receiveInput = document.createElement('input');
receiveInput.type = 'number';
receiveInput.min = '1';
receiveInput.step = 'any';
receiveInput.className = 'inline-input';
receiveInput.placeholder = t('quantityLabel');
const receiveBtn = document.createElement('button');
receiveBtn.className = 'btn btn-outline';
receiveBtn.title = t('receiveStock');
receiveBtn.innerHTML = iconPlus;
receiveBtn.onclick = function () { receiveStock(product.id, receiveInput); };
receiveCell.appendChild(receiveInput);
receiveCell.appendChild(receiveBtn);
row.appendChild(receiveCell);
const countCell = document.createElement('td');
const countInput = document.createElement('input');
countInput.type = 'number';
countInput.min = '0';
countInput.step = 'any';
countInput.className = 'inline-input';
countInput.placeholder = t('countedQtyLabel');
const countBtn = document.createElement('button');
countBtn.className = 'btn btn-outline';
countBtn.title = t('countStock');
countBtn.innerHTML = iconCheck;
countBtn.onclick = function () { countStock(product.id, countInput); };
countCell.appendChild(countInput);
countCell.appendChild(countBtn);
row.appendChild(countCell);
tbody.appendChild(row);
});
table.appendChild(tbody);
box.appendChild(table);
}
async function receiveStock(id, inputEl) {
const qty = parseFloat(inputEl.value);
if (isNaN(qty) || qty < 1) { toast(t('invalidQty'), 'error'); return; }
const result = await api('stock_receive', { product_id: id, quantity: qty, note: '' });
if (result.ok) { toast(t('stockDone'), 'success'); await loadData(); }
else { toast(result.error || t('error'), 'error'); }
}
async function countStock(id, inputEl) {
const counted = parseFloat(inputEl.value);
if (isNaN(counted) || counted < 0) { toast(t('invalidQty'), 'error'); return; }
const result = await api('stock_count', { product_id: id, counted: counted, note: '' });
if (result.ok) { toast(t('stockDone'), 'success'); await loadData(); }
else { toast(result.error || t('error'), 'error'); }
}
function onReturnSaleChange() {
const select = document.getElementById('returnSaleSelect');
if (!select) return;
returnSaleId = select.value;
renderReturnItems();
}
function getReturnedQuantities(saleId) {
const map = {};
(appData.returns || []).forEach(function (r) {
if ((r.sale_id || '') !== saleId) return;
(r.items || []).forEach(function (it) {
const idx = Number(it.item_index || 0);
map[idx] = (map[idx] || 0) + Number(it.quantity || 0);
});
});
return map;
}
function renderReturnForm() {
const select = document.getElementById('returnSaleSelect');
if (!select) return;
select.innerHTML = '';
if (!appData.sales.length) { returnSaleId = ''; renderReturnItems(); return; }
let exists = false;
appData.sales.forEach(function (sale) {
const option = document.createElement('option');
option.value = sale.id;
option.textContent = (sale.invoice_number || '-') + ' | ' + (sale.jalali || sale.date || '') + ' | ' + formatMoney(sale.total || 0);
select.appendChild(option);
if (sale.id === returnSaleId) exists = true;
});
if (!exists) returnSaleId = appData.sales[0].id;
select.value = returnSaleId;
renderReturnItems();
}
function renderReturnItems() {
const box = document.getElementById('returnItemsBox');
if (!box) return;
box.innerHTML = '';
const sale = appData.sales.find(function (s) { return s.id === returnSaleId; });
if (!sale || !Array.isArray(sale.items)) {
box.innerHTML = '<div class="empty-state">' + escapeHtml(t('noReturnItems')) + '</div>';
return;
}
const returnedMap = getReturnedQuantities(sale.id);
let any = false;
sale.items.forEach(function (item, index) {
const sold = Number(item.quantity || 0);
const returned = returnedMap[index] || 0;
const remaining = sold - returned;
if (remaining <= 0) return;
any = true;
const row = document.createElement('div');
row.className = 'list-item';
const info = document.createElement('div');
const title = document.createElement('strong');
title.textContent = item.name;
const meta = document.createElement('div');
meta.className = 'muted';
meta.textContent = formatMoney(item.price) + ' | ' + t('remainingLabel') + ': ' + formatNumber(remaining);
info.appendChild(title);
info.appendChild(meta);
row.appendChild(info);
const input = document.createElement('input');
input.type = 'number';
input.min = '0';
input.max = String(remaining);
input.step = '1';
input.value = '0';
input.className = 'inline-input';
input.setAttribute('data-idx', String(index));
input.placeholder = t('returnQtyLabel');
row.appendChild(input);
box.appendChild(row);
});
if (!any) {
box.innerHTML = '<div class="empty-state">' + escapeHtml(t('noReturnItems')) + '</div>';
}
}
async function recordReturn() {
const sale = appData.sales.find(function (s) { return s.id === returnSaleId; });
if (!sale) { toast(t('error'), 'error'); return; }
const inputs = document.querySelectorAll('#returnItemsBox input[data-idx]');
const items = [];
inputs.forEach(function (inp) {
const q = parseInt(inp.value, 10);
if (q && q > 0) items.push({ index: Number(inp.getAttribute('data-idx')), quantity: q });
});
if (!items.length) { toast(t('noReturnItems'), 'error'); return; }
const note = document.getElementById('returnNote').value;
const result = await api('record_return', { sale_id: returnSaleId, items: items, note: note });
if (result.ok) {
document.getElementById('returnNote').value = '';
toast(t('returnDone'), 'success');
await loadData();
} else {
toast(result.error || t('error'), 'error');
}
}
function renderReturnsList() {
const box = document.getElementById('returnsList');
if (!box) return;
box.innerHTML = '';
const list = appData.returns || [];
if (!list.length) {
box.innerHTML = '<div class="empty-state">' + escapeHtml(t('emptyReturns')) + '</div>';
return;
}
list.forEach(function (r) {
const item = document.createElement('div');
item.className = 'invoice-history-item';
const header = document.createElement('div');
header.className = 'invoice-history-header';
const numberEl = document.createElement('div');
numberEl.className = 'invoice-history-number';
numberEl.textContent = r.return_number || '-';
const dateEl = document.createElement('div');
dateEl.className = 'invoice-history-date';
dateEl.textContent = (r.jalali || r.date || '') + ' | ' + (r.invoice_number || '');
header.appendChild(numberEl);
header.appendChild(dateEl);
const details = document.createElement('div');
details.className = 'invoice-history-details';
const totalEl = document.createElement('div');
totalEl.className = 'invoice-history-total';
totalEl.textContent = formatMoney(r.total || 0);
const metaEl = document.createElement('div');
metaEl.className = 'muted';
let itemsCount = 0;
(r.items || []).forEach(function (it) { itemsCount += Number(it.quantity || 0); });
metaEl.textContent = t('itemsCount') + ': ' + formatNumber(itemsCount) + (r.note ? ' | ' + r.note : '');
details.appendChild(totalEl);
details.appendChild(metaEl);
item.appendChild(header);
item.appendChild(details);
box.appendChild(item);
});
}
function renderMovesList() {
const box = document.getElementById('movesList');
if (!box) return;
box.innerHTML = '';
const list = (appData.stockMoves || []).slice(0, 50);
if (!list.length) {
box.innerHTML = '<div class="empty-state">' + escapeHtml(t('emptyMoves')) + '</div>';
return;
}
const table = document.createElement('table');
table.className = 'invoice-table';
const thead = document.createElement('thead');
const headRow = document.createElement('tr');
[t('date'), t('productName'), t('moveTypeLabel'), t('quantity'), t('stockLabel'), t('noteLabel')].forEach(function (text) {
const th = document.createElement('th');
th.textContent = text;
headRow.appendChild(th);
});
thead.appendChild(headRow);
table.appendChild(thead);
const tbody = document.createElement('tbody');
list.forEach(function (m) {
const row = document.createElement('tr');
const dateCell = document.createElement('td');
dateCell.textContent = persianDigits(m.jalali || m.date || '-');
row.appendChild(dateCell);
const nameCell = document.createElement('td');
nameCell.textContent = m.product_name || '-';
row.appendChild(nameCell);
const typeCell = document.createElement('td');
typeCell.textContent = t('move_' + (m.type || ''));
row.appendChild(typeCell);
const qtyCell = document.createElement('td');
const q = Number(m.quantity || 0);
qtyCell.textContent = (q > 0 ? '+' : '') + formatNumber(q);
if (q < 0) qtyCell.className = 'stock-low';
row.appendChild(qtyCell);
const resCell = document.createElement('td');
resCell.textContent = formatNumber(Number(m.resulting_stock || 0));
row.appendChild(resCell);
const noteCell = document.createElement('td');
noteCell.textContent = m.note || '-';
row.appendChild(noteCell);
tbody.appendChild(row);
});
table.appendChild(tbody);
box.appendChild(table);
}
function renderInstallerChoices() {
const currencyBox = document.getElementById('installerCurrencyChoices');
if (currencyBox) {
currencyBox.innerHTML = '';
currencyDefinitions.forEach(function (currency) {
const button = document.createElement('button');
button.type = 'button';
button.className = 'choice-btn' + (installerCurrency === currency.id ? ' selected' : '');
button.innerHTML = currency.flag + '<span>' + escapeHtml(t('currency_' + currency.id)) + '</span>';
button.onclick = function () { installerCurrency = currency.id; renderInstallerChoices(); };
currencyBox.appendChild(button);
});
}
const languageBox = document.getElementById('installerLanguageChoices');
if (languageBox) {
languageBox.innerHTML = '';
languageDefinitions.forEach(function (language) {
const button = document.createElement('button');
button.type = 'button';
button.className = 'choice-btn' + (installerLanguage === language.id ? ' selected' : '');
button.innerHTML = language.flag + '<span>' + escapeHtml(t('language_' + language.id)) + '</span>';
button.onclick = function () {
installerLanguage = language.id;
appData.settings.language = language.id;
applyLanguage();
renderInstallerChoices();
};
languageBox.appendChild(button);
});
}
const calendarBox = document.getElementById('installerCalendarChoices');
if (calendarBox) {
calendarBox.innerHTML = '';
calendarDefinitions.forEach(function (calendar) {
const button = document.createElement('button');
button.type = 'button';
button.className = 'choice-btn' + (installerCalendar === calendar.id ? ' selected' : '');
button.innerHTML = '<span>' + escapeHtml(t('calendar_' + calendar.id)) + '</span>';
button.onclick = function () { installerCalendar = calendar.id; renderInstallerChoices(); };
calendarBox.appendChild(button);
});
}
}
async function installPanel() {
const result = await api('install', { currency: installerCurrency, language: installerLanguage, calendar: installerCalendar });
if (result.ok) location.reload();
else toast(result.error || t('error'), 'error');
}
function renderSettingsChoices() {
const currencyLocked = !!(appData.settings.installed && appData.settings.currency_locked);
const currencyBox = document.getElementById('currencyChoices');
if (currencyBox) {
currencyBox.innerHTML = '';
currencyDefinitions.forEach(function (currency) {
const button = document.createElement('button');
button.type = 'button';
button.className = 'choice-btn' + ((appData.settings.currency || 'IRT') === currency.id ? ' selected' : '') + (currencyLocked ? ' disabled' : '');
button.innerHTML = currency.flag + '<span>' + escapeHtml(t('currency_' + currency.id)) + '</span>';
if (!currencyLocked) button.onclick = function () { setCurrency(currency.id); };
currencyBox.appendChild(button);
});
}
const currencyNote = document.getElementById('currencyLockedNote');
if (currencyNote) currencyNote.style.display = currencyLocked ? 'block' : 'none';
const languageBox = document.getElementById('languageChoices');
if (languageBox) {
languageBox.innerHTML = '';
languageDefinitions.forEach(function (language) {
const button = document.createElement('button');
button.type = 'button';
button.className = 'choice-btn' + ((appData.settings.language || 'fa') === language.id ? ' selected' : '');
button.innerHTML = language.flag + '<span>' + escapeHtml(t('language_' + language.id)) + '</span>';
button.onclick = function () { setLanguage(language.id); };
languageBox.appendChild(button);
});
}
const calendarBox = document.getElementById('calendarChoices');
if (calendarBox) {
calendarBox.innerHTML = '';
calendarDefinitions.forEach(function (calendar) {
const button = document.createElement('button');
button.type = 'button';
button.className = 'choice-btn' + ((appData.settings.calendar || 'jalali') === calendar.id ? ' selected' : '');
button.innerHTML = '<span>' + escapeHtml(t('calendar_' + calendar.id)) + '</span>';
button.onclick = function () { setCalendarSetting(calendar.id); };
calendarBox.appendChild(button);
});
}
}
function setCurrency(code) {
if (appData.settings.installed && appData.settings.currency_locked) return;
appData.settings.currency = code;
renderSettingsChoices();
persistSettings(false);
refreshMoneyViews();
}
function setLanguage(code) {
appData.settings.language = code;
applyLanguage();
renderSettingsChoices();
persistSettings(false);
refreshMoneyViews();
}
function setCalendarSetting(code) {
appData.settings.calendar = code;
renderSettingsChoices();
persistSettings(false);
calendarMode = code === 'gregorian' ? 'gregorian' : 'jalali';
calendarSettingApplied = true;
updateCalendarModeButtons();
renderCalendar();
syncCalendarDependents();
}
function syncCalendarDependents() { renderSalesTable(); refreshReportFilters(); }
function refreshMoneyViews() {
renderSettingsChoices();
renderCategories();
renderProducts();
renderWarehouse();
renderInvoiceCategorySelects();
renderInvoiceProductSelect();
renderInvoiceItems();
renderHistory();
refreshReportFilters();
}
async function loadData() {
const result = await api('get');
if (!result.ok) { alert(result.error || t('error')); return; }
appData.settings = Object.assign({
name: '', phone: '', address: '', currency: 'IRT', language: 'fa',
calendar: 'jalali', installed: false, currency_locked: false, vat: 0, theme: 'light', print_message: ''
}, result.settings);
applyTheme(appData.settings.theme || 'light');
if (!appData.settings.installed) {
installerCurrency = appData.settings.currency || 'IRT';
installerLanguage = appData.settings.language || 'fa';
installerCalendar = appData.settings.calendar || 'jalali';
document.getElementById('app').style.display = 'none';
document.getElementById('installer').style.display = 'block';
document.getElementById('sideIsland').style.display = 'none';
document.body.classList.remove('app-active');
appData.settings.language = installerLanguage;
applyLanguage();
renderInstallerChoices();
return;
}
document.getElementById('installer').style.display = 'none';
document.getElementById('app').style.display = 'block';
document.getElementById('islandNav').style.display = 'flex';
document.getElementById('sideIsland').style.display = 'flex';
document.body.classList.add('app-active');
document.getElementById('liveClock').style.display = 'inline-flex';
startLiveClock();
appData.categories = result.categories;
appData.products = result.products;
appData.sales = Array.isArray(result.sales) ? result.sales : [];
appData.returns = Array.isArray(result.returns) ? result.returns : [];
appData.stockMoves = Array.isArray(result.stockMoves) ? result.stockMoves : [];
appData.history = result.history || {};
invoiceItems = Array.isArray(result.invoiceItems) ? result.invoiceItems : [];
invoiceDiscount = Number(result.invoiceDiscount || 0);
invoiceDiscountType = result.invoiceDiscountType || 'amount';
invoiceId = result.invoiceId || generateInvoiceIdLocal();
invoiceNumber = result.invoiceNumber || generateInvoiceNumberLocal();
invoiceCustomerName = result.invoiceCustomerName || '';
invoiceCustomerPhone = result.invoiceCustomerPhone || '';
invoiceNote = result.invoiceNote || '';
document.getElementById('invoiceDiscount').value = invoiceDiscount;
if (document.getElementById('discountType')) {
document.getElementById('discountType').value = invoiceDiscountType;
}
applyLanguage();
fillSettingsForm();
fillInvoiceForm();
renderSettingsChoices();
renderCategories();
renderProducts();
renderWarehouse();
renderInvoiceCategorySelects();
renderInvoiceProductSelect();
renderInvoiceItems();
renderInvoiceHistory();
renderHistory();
refreshReportFilters();
if (!calendarSettingApplied) {
calendarMode = appData.settings.calendar === 'gregorian' ? 'gregorian' : 'jalali';
calendarSettingApplied = true;
}
updateCalendarModeButtons();
renderCalendar();
}
function generateInvoiceIdLocal() { return 'RSA-ID-' + Date.now().toString(36).toUpperCase(); }
function generateInvoiceNumberLocal() {
let maxNum = 0;
appData.sales.forEach(function (sale) {
if (sale.invoice_number && /^RSA-(\d+)$/.test(sale.invoice_number)) {
const num = parseInt(sale.invoice_number.replace('RSA-', ''), 10);
if (num > maxNum) maxNum = num;
}
});
return 'RSA-' + String(maxNum + 1).padStart(8, '0');
}
function renderCategories() {
const box = document.getElementById('categoriesList');
box.innerHTML = '';
if (!appData.categories.length) {
box.innerHTML = '<div class="empty-state">' + escapeHtml(t('emptyCategories')) + '</div>';
return;
}
appData.categories.forEach(function (category) {
const row = document.createElement('div');
row.className = 'list-item';
const name = document.createElement('span');
name.textContent = category.name;
const button = document.createElement('button');
button.className = 'btn btn-danger';
button.title = t('deleteLabel');
button.innerHTML = iconTrash;
button.onclick = function () { deleteCategory(category.id); };
row.appendChild(name);
row.appendChild(button);
box.appendChild(row);
});
}
async function addCategory() {
const name = document.getElementById('categoryName').value.trim();
if (!name) { toast(t('categoryRequired'), 'error'); return; }
const result = await api('add_category', { name: name });
if (result.ok) { document.getElementById('categoryName').value = ''; await loadData(); }
else { toast(result.error || t('error'), 'error'); }
}
async function deleteCategory(id) {
if (!confirm(t('confirmDeleteCategory'))) return;
const result = await api('delete_category', { id: id });
if (result.ok) await loadData();
else toast(result.error || t('error'), 'error');
}
function renderCategorySelect() {
const select = document.getElementById('productCategory');
select.innerHTML = '';
const emptyOption = document.createElement('option');
emptyOption.value = '';
emptyOption.textContent = t('selectCategory');
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
box.innerHTML = '<div class="empty-state">' + escapeHtml(t('emptyProducts')) + '</div>';
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
const stockText = t('stockLabel') + ': ' + formatNumber(Number(product.stock || 0));
let metaHtml = escapeHtml(product.category) + ' - ' + escapeHtml(formatMoney(product.price)) + ' | ' + (isLowStock(product) ? '<span class="stock-low">' + escapeHtml(stockText) + '</span>' : escapeHtml(stockText));
if (product.barcode) {
metaHtml += ' | ' + escapeHtml(t('barcodeLabel')) + ': ' + escapeHtml(product.barcode);
}
meta.innerHTML = metaHtml;
info.appendChild(title);
info.appendChild(meta);
const button = document.createElement('button');
button.className = 'btn btn-danger';
button.title = t('deleteLabel');
button.innerHTML = iconTrash;
button.onclick = function () { deleteProduct(product.id); };
row.appendChild(info);
row.appendChild(button);
box.appendChild(row);
});
}
async function addProduct() {
const barcode = document.getElementById('productBarcode').value.trim();
if (barcode && findProductByBarcode(barcode)) {
toast(t('barcodeDuplicate'), 'error');
return;
}
const payload = {
name: document.getElementById('productName').value.trim(),
category: document.getElementById('productCategory').value,
price: document.getElementById('productPrice').value,
barcode: barcode,
stock: parseFloat(document.getElementById('productStock').value) || 0,
stock_alert: parseFloat(document.getElementById('productStockAlert').value) || 0
};
if (!payload.name || !payload.category || payload.price === '') { toast(t('productFields'), 'error'); return; }
const result = await api('add_product', payload);
if (result.ok) {
document.getElementById('productName').value = '';
document.getElementById('productCategory').value = '';
document.getElementById('productPrice').value = '';
document.getElementById('productBarcode').value = '';
document.getElementById('productStock').value = '0';
document.getElementById('productStockAlert').value = '0';
await loadData();
} else {
toast(result.error || t('error'), 'error');
}
}
async function deleteProduct(id) {
if (!confirm(t('confirmDeleteProduct'))) return;
const result = await api('delete_product', { id: id });
if (result.ok) await loadData();
else toast(result.error || t('error'), 'error');
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
allOption.textContent = t('allCategories');
invoiceCategory.appendChild(allOption);
const noneOption = document.createElement('option');
noneOption.value = '';
noneOption.textContent = t('noCategory');
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
if (currentInvoiceCategory) invoiceCategory.value = currentInvoiceCategory;
if (currentManualCategory) manualProductCategory.value = currentManualCategory;
applyManualOnlyState();
}
function renderInvoiceProductSelect() {
const select = document.getElementById('invoiceProduct');
const selectedCategory = document.getElementById('invoiceCategory').value;
select.innerHTML = '';
const emptyOption = document.createElement('option');
emptyOption.value = '';
emptyOption.textContent = t('selectProduct');
select.appendChild(emptyOption);
appData.products
.filter(function (product) { return !selectedCategory || product.category === selectedCategory; })
.forEach(function (product) {
const option = document.createElement('option');
option.value = product.id;
option.textContent = product.name + ' - ' + formatMoney(product.price);
select.appendChild(option);
});
applyManualOnlyState();
renderQuickProducts();
}
async function addToInvoice() {
const selectedId = document.getElementById('invoiceProduct').value;
const manualName = document.getElementById('manualProductName').value.trim();
const manualPriceValue = document.getElementById('manualProductPrice').value;
const manualCategory = document.getElementById('manualProductCategory').value;
const quantity = parseInt(document.getElementById('productQuantity').value, 10);
if (!quantity || quantity < 1) { toast(t('quantityValid'), 'error'); return; }
let item = null;
if (!manualOnlyEnabled && selectedId) {
const product = appData.products.find(function (productItem) { return productItem.id === selectedId; });
if (product) {
item = { name: product.name, category: product.category || '', price: Number(product.price), quantity: quantity, product_id: product.id };
}
} else {
const manualPrice = parseFloat(manualPriceValue);
if (manualName && !isNaN(manualPrice) && manualPrice >= 0) {
item = { name: manualName, category: manualCategory || '', price: manualPrice, quantity: quantity, product_id: '' };
}
}
if (!item) { toast(t('chooseOrManual'), 'error'); return; }
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
if (!confirm(t('confirmNewInvoice'))) return;
await api('save_invoice', {
items: [], discount: 0, discount_type: 'amount', invoice_id: '', invoice_number: '',
customer_name: '', customer_phone: '', note: ''
});
await loadData();
}
async function repeatLastInvoice() {
if (!appData.sales || !appData.sales.length) { toast(t('noLastInvoice'), 'error'); return; }
loadInvoiceData(appData.sales[0]);
}
async function saveInvoice(showAlert) {
const result = await api('save_invoice', {
items: invoiceItems,
discount: Number(invoiceDiscount || 0),
discount_type: invoiceDiscountType,
invoice_id: invoiceId,
invoice_number: invoiceNumber,
customer_name: invoiceCustomerName,
customer_phone: invoiceCustomerPhone,
note: invoiceNote
});
if (result.ok) {
if (result.invoice_id) invoiceId = result.invoice_id;
if (result.invoice_number) invoiceNumber = result.invoice_number;
const idInput = document.getElementById('invoiceIdInput');
const numInput = document.getElementById('invoiceNumberInput');
if (idInput && idInput.value !== invoiceId) idInput.value = invoiceId;
if (numInput && numInput.value !== invoiceNumber) numInput.value = invoiceNumber;
}
if (showAlert) {
if (result.ok) toast(t('invoiceSaved'), 'success');
else toast(result.error || t('error'), 'error');
}
return result;
}
async function recordSale() {
if (!invoiceItems.length) { toast(t('invoiceEmpty'), 'error'); return; }
if (!confirm(t('confirmRecordSale'))) return;
const result = await api('record_sale', {
items: invoiceItems,
discount: Number(invoiceDiscount || 0),
discount_type: invoiceDiscountType,
invoice_id: invoiceId,
invoice_number: invoiceNumber,
customer_name: invoiceCustomerName,
customer_phone: invoiceCustomerPhone,
note: invoiceNote
});
if (result.ok) {
await api('save_invoice', {
items: [], discount: 0, discount_type: 'amount', invoice_id: '', invoice_number: '',
customer_name: '', customer_phone: '', note: ''
});
await loadData();
toast(t('saleRecorded'), 'success');
const barcodeInput = document.getElementById('barcodeInput');
if (barcodeInput && window.matchMedia('(min-width: 769px)').matches) barcodeInput.focus();
} else {
toast(result.error || t('error'), 'error');
}
}
function renderInvoiceItems() {
const box = document.getElementById('invoiceItems');
const totalSection = document.getElementById('totalSection');
box.innerHTML = '';
if (!invoiceItems.length) { totalSection.style.display = 'none'; return; }
const table = document.createElement('table');
table.className = 'invoice-table';
const thead = document.createElement('thead');
const headRow = document.createElement('tr');
[t('row'), t('category'), t('productName'), t('unitPrice'), t('quantity'), t('sum'), t('operations')].forEach(function (text) {
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
formatNumber(index + 1), item.category || '-', item.name,
formatMoney(item.price), formatNumber(item.quantity), formatMoney(subtotal)
];
cells.forEach(function (value) {
const td = document.createElement('td');
td.textContent = value;
row.appendChild(td);
});
const actionCell = document.createElement('td');
const button = document.createElement('button');
button.className = 'btn btn-danger';
button.title = t('deleteLabel');
button.innerHTML = iconTrash;
button.onclick = function () { removeFromInvoice(index); };
actionCell.appendChild(button);
row.appendChild(actionCell);
tbody.appendChild(row);
});
table.appendChild(tbody);
box.appendChild(table);
const discount = getEffectiveDiscount();
const vat = getVatAmount();
const finalTotal = total - discount + vat;
totalSection.style.display = 'block';
document.getElementById('totalAmount').textContent = formatMoney(total);
document.getElementById('discountAmount').textContent = formatMoney(discount);
document.getElementById('vatAmount').textContent = formatMoney(vat);
document.getElementById('finalAmount').textContent = formatMoney(finalTotal);
}
function renderInvoiceHistory() {
const box = document.getElementById('invoiceHistoryList');
const searchInput = document.getElementById('invoiceSearch');
box.innerHTML = '';
const searchTerm = searchInput ? searchInput.value.trim().toLowerCase() : '';
let filteredSales = appData.sales;
if (searchTerm) {
filteredSales = appData.sales.filter(function (sale) {
const invoiceNumber = (sale.invoice_number || '').toLowerCase();
const invoiceId = (sale.invoice_id || '').toLowerCase();
return invoiceNumber.includes(searchTerm) || invoiceId.includes(searchTerm);
});
}
if (!filteredSales.length) {
box.innerHTML = '<div class="empty-state">' + escapeHtml(t('noInvoiceFound')) + '</div>';
return;
}
filteredSales.forEach(function (sale) {
const item = document.createElement('div');
item.className = 'invoice-history-item';
const header = document.createElement('div');
header.className = 'invoice-history-header';
const numberEl = document.createElement('div');
numberEl.className = 'invoice-history-number';
numberEl.textContent = sale.invoice_number || '-';
const dateEl = document.createElement('div');
dateEl.className = 'invoice-history-date';
dateEl.textContent = saleDateText(sale) + ' | ' + (sale.datetime || '');
header.appendChild(numberEl);
header.appendChild(dateEl);
const details = document.createElement('div');
details.className = 'invoice-history-details';
const totalEl = document.createElement('div');
totalEl.className = 'invoice-history-total';
totalEl.textContent = formatMoney(sale.total || 0);
const actions = document.createElement('div');
actions.className = 'invoice-history-actions';
const loadBtn = document.createElement('button');
loadBtn.className = 'btn btn-outline';
loadBtn.title = t('loadInvoice');
loadBtn.innerHTML = iconEdit + ' <span>' + t('loadInvoice') + '</span>';
loadBtn.onclick = function () { loadInvoiceFromSale(sale); };
actions.appendChild(loadBtn);
const retBtn = document.createElement('button');
retBtn.className = 'btn btn-outline';
retBtn.title = t('returnShort');
retBtn.innerHTML = iconReturn + ' <span>' + t('returnShort') + '</span>';
retBtn.onclick = function () { openReturnFor(sale.id); };
actions.appendChild(retBtn);
const printBtn = document.createElement('button');
printBtn.className = 'btn btn-outline';
printBtn.title = t('printSale');
printBtn.innerHTML = iconPrint + ' <span>' + t('printSale') + '</span>';
printBtn.onclick = function () { printSale(sale); };
actions.appendChild(printBtn);
const delBtn = document.createElement('button');
delBtn.className = 'btn btn-danger';
delBtn.title = t('deleteLabel');
delBtn.innerHTML = iconTrash;
delBtn.onclick = function () { deleteSale(sale.id); };
actions.appendChild(delBtn);
details.appendChild(totalEl);
details.appendChild(actions);
item.appendChild(header);
item.appendChild(details);
box.appendChild(item);
});
}
function filterInvoiceHistory() { renderInvoiceHistory(); }
function saleDateText(sale) {
if (calendarMode === 'gregorian') {
if (!sale.date) return '-';
const p = sale.date.split('-');
return formatGregorianString(Number(p[0]), Number(p[1]), Number(p[2]));
}
return sale.jalali ? sale.jalali : formatJalaliString(Number(sale.jy || 0), Number(sale.jm || 0), Number(sale.jd || 0));
}
function renderWeekChart() {
const box = document.getElementById('weekChart');
if (!box) return;
box.innerHTML = '';
const days = [];
for (let i = 6; i >= 0; i--) {
const d = new Date();
d.setDate(d.getDate() - i);
d.setHours(0, 0, 0, 0);
const key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
days.push({ key: key, date: d, total: 0 });
}
appData.sales.forEach(function (sale) {
if (!sale.date) return;
for (let i = 0; i < days.length; i++) {
if (days[i].key === sale.date) { days[i].total += Number(sale.total || 0); break; }
}
});
let max = 1;
days.forEach(function (d) { if (d.total > max) max = d.total; });
days.forEach(function (day) {
const col = document.createElement('div');
col.className = 'bar-col';
const val = document.createElement('div');
val.className = 'bar-value';
val.textContent = day.total > 0 ? formatPrice(day.total) : '';
const bar = document.createElement('div');
bar.className = 'bar';
const pct = Math.round((day.total / max) * 100);
bar.style.height = Math.max(pct, 3) + '%';
if (day.total > 0) bar.classList.add('has-value');
bar.title = formatMoney(day.total);
const label = document.createElement('div');
label.className = 'bar-label';
label.textContent = day.date.toLocaleDateString(currentLocale(), { weekday: 'narrow' });
col.appendChild(val);
col.appendChild(bar);
col.appendChild(label);
box.appendChild(col);
});
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
const weekRangeText = history.weekStartJalali && history.weekEndJalali ? history.weekStartJalali + ' - ' + history.weekEndJalali : '-';
document.getElementById('weekRange').textContent = persianDigits(weekRangeText);
document.getElementById('weekTotal').textContent = formatMoney(history.weekTotal || 0);
document.getElementById('weekCount').textContent = formatNumber(history.weekCount || 0) + ' ' + t('salesWord');
document.getElementById('monthTotal').textContent = formatMoney(history.monthTotal || 0);
document.getElementById('monthCount').textContent = formatNumber(history.monthCount || 0) + ' ' + t('salesWord');
const nowD = new Date();
const todayKey = nowD.getFullYear() + '-' + String(nowD.getMonth() + 1).padStart(2, '0') + '-' + String(nowD.getDate()).padStart(2, '0');
let todayTotal = 0, todayCount = 0;
appData.sales.forEach(function (sale) {
if ((sale.date || '') === todayKey) { todayTotal += Number(sale.total || 0); todayCount++; }
});
document.getElementById('todayTotal').textContent = formatMoney(todayTotal);
document.getElementById('todayCount').textContent = formatNumber(todayCount) + ' ' + t('salesWord');
renderWeekChart();
renderSalesTable();
}
function renderSalesTable() {
const box = document.getElementById('salesTable');
box.innerHTML = '';
if (!appData.sales.length) {
box.innerHTML = '<div class="empty-state">' + escapeHtml(t('emptySales')) + '</div>';
return;
}
const table = document.createElement('table');
table.className = 'invoice-table';
const thead = document.createElement('thead');
const headRow = document.createElement('tr');
[t('row'), t('invoiceNumberLabel'), t('customerLabel'), t('date'), t('itemsCount'), t('total'), t('discount'), t('vatLabel'), t('payable'), t('operations')].forEach(function (text) {
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
sale.items.forEach(function (item) { itemsCount += Number(item.quantity || 0); });
}
const row = document.createElement('tr');
const cells = [
formatNumber(index + 1),
sale.invoice_number || '-',
sale.customer_name || '-',
persianDigits(saleDateText(sale)),
formatNumber(itemsCount),
formatMoney(sale.subtotal || 0),
formatMoney(sale.discount || 0),
formatMoney(sale.vat || 0),
formatMoney(sale.total || 0)
];
cells.forEach(function (value) {
const td = document.createElement('td');
td.textContent = value;
row.appendChild(td);
});
const actionCell = document.createElement('td');
const printBtn = document.createElement('button');
printBtn.className = 'btn btn-outline';
printBtn.title = t('printSale');
printBtn.innerHTML = iconPrint;
printBtn.onclick = function () { printSale(sale); };
actionCell.appendChild(printBtn);
const retBtn = document.createElement('button');
retBtn.className = 'btn btn-outline';
retBtn.title = t('returnShort');
retBtn.innerHTML = iconReturn;
retBtn.onclick = function () { openReturnFor(sale.id); };
actionCell.appendChild(retBtn);
const delBtn = document.createElement('button');
delBtn.className = 'btn btn-danger';
delBtn.title = t('deleteLabel');
delBtn.innerHTML = iconTrash;
delBtn.onclick = function () { deleteSale(sale.id); };
actionCell.appendChild(delBtn);
row.appendChild(actionCell);
tbody.appendChild(row);
});
table.appendChild(tbody);
box.appendChild(table);
}
function populateReportYears(selectedYear) {
const select = document.getElementById('reportYear');
if (!select) return;
const years = new Set();
if (calendarMode === 'gregorian') {
const now = new Date();
years.add(now.getFullYear());
appData.sales.forEach(function (sale) { if (sale.date) years.add(Number(sale.date.split('-')[0])); });
} else {
const today = getTodayJalaliObj();
years.add(Number(today.jy));
appData.sales.forEach(function (sale) { if (sale.jy) years.add(Number(sale.jy)); });
}
const sorted = Array.from(years).sort(function (a, b) { return b - a; });
select.innerHTML = '';
sorted.forEach(function (year) {
const option = document.createElement('option');
option.value = year;
option.textContent = formatDayNumber(year);
select.appendChild(option);
});
if (sorted.includes(Number(selectedYear))) select.value = Number(selectedYear);
else if (sorted.length) select.value = sorted[0];
}
function populateReportMonths(selectedMonth) {
const select = document.getElementById('reportMonth');
if (!select) return;
select.innerHTML = '';
for (let month = 1; month <= 12; month++) {
const option = document.createElement('option');
option.value = month;
option.textContent = calendarMode === 'gregorian' ? gregorianMonthName(month) : getJalaliMonthName(month);
select.appendChild(option);
}
if (selectedMonth >= 1 && selectedMonth <= 12) select.value = selectedMonth;
}
function gregorianMonthName(gm) {
const locale = gregorianCalendarLocale();
return new Date(2024, gm - 1, 1).toLocaleString(locale, { month: 'long' });
}
function updateReportDays(selectedDay) {
const yearEl = document.getElementById('reportYear');
const monthEl = document.getElementById('reportMonth');
const dayEl = document.getElementById('reportDay');
if (!yearEl || !monthEl || !dayEl) return;
const today = getTodayJalaliObj();
const jy = Number(yearEl.value) || Number(today.jy);
const jm = Number(monthEl.value) || Number(today.jm);
const monthLength = calendarMode === 'gregorian' ? new Date(jy, jm, 0).getDate() : jalaliMonthLength(jy, jm);
dayEl.innerHTML = '';
for (let day = 1; day <= monthLength; day++) {
const option = document.createElement('option');
option.value = day;
option.textContent = formatDayNumber(day);
dayEl.appendChild(option);
}
if (selectedDay >= 1 && selectedDay <= monthLength) dayEl.value = selectedDay;
else dayEl.value = monthLength;
}
function refreshReportFilters() {
const typeEl = document.getElementById('reportFilterType');
const yearEl = document.getElementById('reportYear');
const monthEl = document.getElementById('reportMonth');
const dayEl = document.getElementById('reportDay');
if (!typeEl || !yearEl || !monthEl || !dayEl) return;
const today = getTodayJalaliObj();
const now = new Date();
const defaultYear = calendarMode === 'gregorian' ? now.getFullYear() : Number(today.jy);
const defaultMonth = calendarMode === 'gregorian' ? now.getMonth() + 1 : Number(today.jm);
const defaultDay = calendarMode === 'gregorian' ? now.getDate() : Number(today.jd);
const currentYear = reportInitialized && yearEl.value ? Number(yearEl.value) : defaultYear;
const currentMonth = reportInitialized && monthEl.value ? Number(monthEl.value) : defaultMonth;
const currentDay = reportInitialized && dayEl.value ? Number(dayEl.value) : defaultDay;
populateReportYears(currentYear);
populateReportMonths(currentMonth);
updateReportDays(currentDay);
if (!reportInitialized) { typeEl.value = 'month'; reportInitialized = true; }
onReportTypeChange();
}
function onReportTypeChange() {
const typeEl = document.getElementById('reportFilterType');
const dayWrap = document.getElementById('reportDayWrap');
if (!typeEl || !dayWrap) return;
dayWrap.style.display = typeEl.value === 'month' ? 'none' : 'block';
renderSalesReport();
}
function onReportDatePartChange() {
const dayEl = document.getElementById('reportDay');
updateReportDays(dayEl ? Number(dayEl.value) : 1);
renderSalesReport();
}
function onReportCalendarChange() {
const select = document.getElementById('reportCalendarType');
if (!select) return;
setCalendarMode(select.value === 'gregorian' ? 'gregorian' : 'jalali');
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
if (!typeEl || !yearEl || !monthEl || !dayEl) return;
const type = typeEl.value;
const jy = Number(yearEl.value);
const jm = Number(monthEl.value);
const jd = Number(dayEl.value);
let filtered = [];
let label = '';
if (calendarMode === 'gregorian') {
if (type === 'day') {
const targetDash = String(jy).padStart(4, '0') + '-' + String(jm).padStart(2, '0') + '-' + String(jd).padStart(2, '0');
filtered = appData.sales.filter(function (sale) { return (sale.date || '') === targetDash; });
label = t('dayReport') + ': ' + formatGregorianString(jy, jm, jd);
} else if (type === 'week') {
const base = new Date(jy, jm - 1, jd, 0, 0, 0, 0);
const diffToSaturday = (base.getDay() + 1) % 7;
const start = new Date(base);
start.setDate(base.getDate() - diffToSaturday);
start.setHours(0, 0, 0, 0);
const end = new Date(start);
end.setDate(start.getDate() + 6);
end.setHours(23, 59, 59, 999);
filtered = appData.sales.filter(function (sale) {
if (!sale.date) return false;
const saleDate = new Date(sale.date + 'T00:00:00');
return saleDate >= start && saleDate <= end;
});
label = t('weekReport') + ': ' + formatGregorianString(start.getFullYear(), start.getMonth() + 1, start.getDate()) + ' - ' + formatGregorianString(end.getFullYear(), end.getMonth() + 1, end.getDate());
} else {
filtered = appData.sales.filter(function (sale) {
if (!sale.date) return false;
const p = sale.date.split('-');
return Number(p[0]) === jy && Number(p[1]) === jm;
});
label = t('monthReport') + ': ' + gregorianMonthName(jm) + ' ' + jy;
}
} else {
if (type === 'day') {
filtered = appData.sales.filter(function (sale) {
return Number(sale.jy) === jy && Number(sale.jm) === jm && Number(sale.jd) === jd;
});
label = t('dayReport') + ': ' + formatJalaliString(jy, jm, jd);
}
if (type === 'week') {
const range = getWeekRangeFromJalali(jy, jm, jd);
const startJ = gregorianToJalali(range.start.getFullYear(), range.start.getMonth() + 1, range.start.getDate());
const endJ = gregorianToJalali(range.end.getFullYear(), range.end.getMonth() + 1, range.end.getDate());
filtered = appData.sales.filter(function (sale) {
if (!sale.date) return false;
const saleDate = new Date(sale.date + 'T00:00:00');
return saleDate >= range.start && saleDate <= range.end;
});
label = t('weekReport') + ': ' + formatJalaliString(startJ[0], startJ[1], startJ[2]) + ' - ' + formatJalaliString(endJ[0], endJ[1], endJ[2]);
}
if (type === 'month') {
filtered = appData.sales.filter(function (sale) {
return Number(sale.jy) === jy && Number(sale.jm) === jm;
});
label = t('monthReport') + ': ' + getJalaliMonthName(jm) + ' ' + jy;
}
}
let subtotal = 0, discount = 0, vat = 0, total = 0;
filtered.forEach(function (sale) {
subtotal += Number(sale.subtotal || 0);
discount += Number(sale.discount || 0);
vat += Number(sale.vat || 0);
total += Number(sale.total || 0);
});
currentReport = { type: type, label: label, filtered: filtered, subtotal: subtotal, discount: discount, vat: vat, total: total };
document.getElementById('reportPeriodLabel').textContent = persianDigits(label);
document.getElementById('reportTotal').textContent = formatMoney(total);
document.getElementById('reportMeta').textContent = formatNumber(filtered.length) + ' ' + t('salesWord')
+ ' | ' + t('totalLabel') + ': ' + formatMoney(subtotal)
+ ' | ' + t('discountLabel') + ': ' + formatMoney(discount)
+ ' | ' + t('vatLabel') + ': ' + formatMoney(vat);
renderReportTable(filtered);
}
function renderReportTable(filtered) {
const box = document.getElementById('reportTable');
box.innerHTML = '';
if (!filtered.length) {
box.innerHTML = '<div class="empty-state">' + escapeHtml(t('emptyReport')) + '</div>';
return;
}
const table = document.createElement('table');
table.className = 'invoice-table';
const thead = document.createElement('thead');
const headRow = document.createElement('tr');
[t('row'), t('invoiceNumberLabel'), t('customerLabel'), t('date'), t('itemsCount'), t('total'), t('discount'), t('vatLabel'), t('payable')].forEach(function (text) {
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
sale.items.forEach(function (item) { itemsCount += Number(item.quantity || 0); });
}
const row = document.createElement('tr');
const cells = [
formatNumber(index + 1),
sale.invoice_number || '-',
sale.customer_name || '-',
persianDigits(saleDateText(sale)),
formatNumber(itemsCount),
formatMoney(sale.subtotal || 0),
formatMoney(sale.discount || 0),
formatMoney(sale.vat || 0),
formatMoney(sale.total || 0)
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
function buildInvoicePrintHtml(info) {
let rows = '';
info.items.forEach(function (item, index) {
const subtotal = Number(item.price) * Number(item.quantity);
rows += '<tr>'
+ '<td>' + escapeHtml(formatNumber(index + 1)) + '</td>'
+ '<td>' + escapeHtml(item.category || '-') + '</td>'
+ '<td>' + escapeHtml(item.name) + '</td>'
+ '<td>' + escapeHtml(formatMoney(item.price)) + '</td>'
+ '<td>' + escapeHtml(formatNumber(item.quantity)) + '</td>'
+ '<td>' + escapeHtml(formatMoney(subtotal)) + '</td>'
+ '</tr>';
});
const storeName = appData.settings.name ? escapeHtml(appData.settings.name) : escapeHtml(t('appName'));
const phone = appData.settings.phone ? '<p>' + escapeHtml(t('phoneLabel')) + ': ' + escapeHtml(appData.settings.phone) + '</p>' : '';
const address = appData.settings.address ? '<p>' + escapeHtml(t('addressLabel')) + ': ' + escapeHtml(appData.settings.address) + '</p>' : '';
let extraHeader = '';
(info.extraLines || []).forEach(function (line) { extraHeader += '<p>' + escapeHtml(line) + '</p>'; });
const metaHtml = '<div class="print-meta">'
+ '<div><span>' + escapeHtml(t('invoiceNumberLabel')) + ':</span> <strong>' + escapeHtml(info.invoiceNumber || '-') + '</strong></div>'
+ '<div><span>' + escapeHtml(t('invoiceIdLabel')) + ':</span> <strong>' + escapeHtml(info.invoiceId || '-') + '</strong></div>'
+ '<div><span>' + escapeHtml(t('invoiceDate')) + ':</span> <strong>' + escapeHtml(persianDigits(info.dateText || '-')) + '</strong></div>'
+ '<div><span>' + escapeHtml(t('customerLabel')) + ':</span> <strong>' + escapeHtml(info.customerName || '-') + (info.customerPhone ? ' | ' + escapeHtml(info.customerPhone) : '') + '</strong></div>'
+ '</div>';
const noteHtml = info.note ? '<div class="print-note"><strong>' + escapeHtml(t('noteLabel')) + ':</strong> ' + escapeHtml(info.note) + '</div>' : '';
const messageHtml = (appData.settings.print_message || '') ? '<div class="print-message">' + escapeHtml(appData.settings.print_message) + '</div>' : '';
return '<div class="print-brand"></div>'
+ '<div class="print-header">'
+ '<h1>' + storeName + '</h1>'
+ phone + address + extraHeader
+ '</div>'
+ metaHtml
+ '<div class="print-title">' + escapeHtml(t('invoiceTitlePrint')) + '</div>'
+ '<table class="invoice-table">'
+ '<thead><tr>'
+ '<th>' + escapeHtml(t('row')) + '</th>'
+ '<th>' + escapeHtml(t('category')) + '</th>'
+ '<th>' + escapeHtml(t('productName')) + '</th>'
+ '<th>' + escapeHtml(t('unitPrice')) + '</th>'
+ '<th>' + escapeHtml(t('quantity')) + '</th>'
+ '<th>' + escapeHtml(t('sum')) + '</th>'
+ '</tr></thead><tbody>' + rows + '</tbody></table>'
+ '<div class="total-section">'
+ '<div class="total-row"><span>' + escapeHtml(t('totalLabel')) + ':</span> <span class="total-amount">' + escapeHtml(formatMoney(info.subtotal)) + '</span></div>'
+ '<div class="total-row"><span>' + escapeHtml(t('discountLabel')) + ':</span> <span class="total-amount">' + escapeHtml(formatMoney(info.discount)) + '</span></div>'
+ '<div class="total-row"><span>' + escapeHtml(t('vatLabel')) + ':</span> <span class="total-amount">' + escapeHtml(formatMoney(info.vat)) + '</span></div>'
+ '<div class="total-row final"><span>' + escapeHtml(t('payableLabel')) + ':</span> <span class="total-amount">' + escapeHtml(formatMoney(info.total)) + '</span></div>'
+ '</div>'
+ noteHtml
+ messageHtml
+ '<div class="print-footer"><div>' + escapeHtml(t('storeSignLabel')) + '</div></div>'
+ '<div class="print-brand-footer">' + escapeHtml(t('panelFooter')) + '</div>';
}
function printSale(sale) {
document.getElementById('printArea').innerHTML = buildInvoicePrintHtml({
items: Array.isArray(sale.items) ? sale.items : [],
subtotal: sale.subtotal || 0,
discount: sale.discount || 0,
vat: sale.vat || 0,
total: sale.total || 0,
invoiceNumber: sale.invoice_number || '-',
invoiceId: sale.invoice_id || '-',
dateText: saleDateText(sale),
customerName: sale.customer_name || '',
customerPhone: sale.customer_phone || '',
note: sale.note || '',
extraLines: []
});
window.print();
}
function printSalesReport() {
if (!currentReport.filtered.length) { toast(t('reportEmpty'), 'error'); return; }
let rows = '';
currentReport.filtered.forEach(function (sale, index) {
let itemsCount = 0;
if (Array.isArray(sale.items)) {
sale.items.forEach(function (item) { itemsCount += Number(item.quantity || 0); });
}
rows += '<tr>'
+ '<td>' + escapeHtml(formatNumber(index + 1)) + '</td>'
+ '<td>' + escapeHtml(sale.invoice_number || '-') + '</td>'
+ '<td>' + escapeHtml(sale.customer_name || '-') + '</td>'
+ '<td>' + escapeHtml(persianDigits(saleDateText(sale))) + '</td>'
+ '<td>' + escapeHtml(formatNumber(itemsCount)) + '</td>'
+ '<td>' + escapeHtml(formatMoney(sale.subtotal || 0)) + '</td>'
+ '<td>' + escapeHtml(formatMoney(sale.discount || 0)) + '</td>'
+ '<td>' + escapeHtml(formatMoney(sale.vat || 0)) + '</td>'
+ '<td>' + escapeHtml(formatMoney(sale.total || 0)) + '</td>'
+ '</tr>';
});
const jNow = currentJalali();
const now = new Date();
const printDate = calendarMode === 'gregorian' ? formatGregorianString(now.getFullYear(), now.getMonth() + 1, now.getDate()) : formatJalaliString(jNow[0], jNow[1], jNow[2]);
const storeName = appData.settings.name ? escapeHtml(appData.settings.name) : escapeHtml(t('appName'));
const phone = appData.settings.phone ? '<p>' + escapeHtml(t('phoneLabel')) + ': ' + escapeHtml(appData.settings.phone) + '</p>' : '';
const address = appData.settings.address ? '<p>' + escapeHtml(t('addressLabel')) + ': ' + escapeHtml(appData.settings.address) + '</p>' : '';
document.getElementById('printArea').innerHTML =
'<div class="print-brand"></div>'
+ '<div class="print-header">'
+ '<h1>' + storeName + '</h1>'
+ phone + address
+ '<p>' + escapeHtml(t('printDate')) + ': ' + persianDigits(printDate) + '</p>'
+ '</div>'
+ '<div class="print-title">' + escapeHtml(t('reportTitle')) + '</div>'
+ '<div class="print-subtitle">' + escapeHtml(persianDigits(currentReport.label)) + '</div>'
+ '<div class="print-summary">'
+ '<div>' + escapeHtml(t('salesWord')) + ': ' + escapeHtml(formatNumber(currentReport.filtered.length)) + '</div>'
+ '<div>' + escapeHtml(t('totalLabel')) + ': ' + escapeHtml(formatMoney(currentReport.subtotal)) + '</div>'
+ '<div>' + escapeHtml(t('discountLabel')) + ': ' + escapeHtml(formatMoney(currentReport.discount)) + '</div>'
+ '<div>' + escapeHtml(t('vatLabel')) + ': ' + escapeHtml(formatMoney(currentReport.vat)) + '</div>'
+ '<div>' + escapeHtml(t('payableLabel')) + ': ' + escapeHtml(formatMoney(currentReport.total)) + '</div>'
+ '</div>'
+ '<table class="invoice-table">'
+ '<thead><tr>'
+ '<th>' + escapeHtml(t('row')) + '</th>'
+ '<th>' + escapeHtml(t('invoiceNumberLabel')) + '</th>'
+ '<th>' + escapeHtml(t('customerLabel')) + '</th>'
+ '<th>' + escapeHtml(t('date')) + '</th>'
+ '<th>' + escapeHtml(t('itemsCount')) + '</th>'
+ '<th>' + escapeHtml(t('total')) + '</th>'
+ '<th>' + escapeHtml(t('discount')) + '</th>'
+ '<th>' + escapeHtml(t('vatLabel')) + '</th>'
+ '<th>' + escapeHtml(t('payable')) + '</th>'
+ '</tr></thead><tbody>' + rows + '</tbody></table>'
+ '<div class="print-brand-footer">' + escapeHtml(t('panelFooter')) + '</div>';
window.print();
}
function updateCalendarModeButtons() {
const jalaliButton = document.getElementById('jalaliModeBtn');
const gregorianButton = document.getElementById('gregorianModeBtn');
if (jalaliButton && gregorianButton) {
jalaliButton.classList.toggle('active', calendarMode === 'jalali');
gregorianButton.classList.toggle('active', calendarMode === 'gregorian');
}
const reportCalendar = document.getElementById('reportCalendarType');
if (reportCalendar) reportCalendar.value = calendarMode;
}
function setCalendarMode(mode) {
calendarMode = mode;
updateCalendarModeButtons();
renderCalendar();
syncCalendarDependents();
}
function changeCalendarMonth(delta) {
if (calendarMode === 'gregorian') {
calendarGm += delta;
if (calendarGm < 1) { calendarGm = 12; calendarGy -= 1; }
if (calendarGm > 12) { calendarGm = 1; calendarGy += 1; }
} else {
calendarJm += delta;
if (calendarJm < 1) { calendarJm = 12; calendarJy -= 1; }
if (calendarJm > 12) { calendarJm = 1; calendarJy += 1; }
}
renderCalendar();
}
function renderCalendar() {
const container = document.getElementById('calendarGrid');
const label = document.getElementById('calendarLabel');
if (!container || !label) return;
const weekdayNames = calendarMode === 'gregorian' ? getGregorianWeekdayNames() : getWeekdayNames();
container.innerHTML = '';
weekdayNames.forEach(function (dayName) {
const div = document.createElement('div');
div.className = 'calendar-day-name';
div.textContent = dayName;
container.appendChild(div);
});
if (calendarMode === 'gregorian') {
const firstDate = new Date(calendarGy, calendarGm - 1, 1);
const offset = (firstDate.getDay() + 1) % 7;
const monthLength = new Date(calendarGy, calendarGm, 0).getDate();
const gLocale = gregorianCalendarLocale();
label.textContent = firstDate.toLocaleString(gLocale, { month: 'long' }) + ' ' + calendarGy.toLocaleString(gLocale, { useGrouping: false });
const saleDays = {};
appData.sales.forEach(function (sale) {
if (!sale.date) return;
const parts = sale.date.split('-');
if (Number(parts[0]) === calendarGy && Number(parts[1]) === calendarGm) saleDays[Number(parts[2])] = true;
});
for (let i = 0; i < offset; i++) {
const div = document.createElement('div');
div.className = 'calendar-day empty';
container.appendChild(div);
}
const now = new Date();
for (let day = 1; day <= monthLength; day++) {
const div = document.createElement('div');
div.className = 'calendar-day';
div.textContent = formatDayNumber(day);
if (day === now.getDate() && calendarGm === now.getMonth() + 1 && calendarGy === now.getFullYear()) div.classList.add('today');
if (saleDays[day]) div.classList.add('has-sale');
container.appendChild(div);
}
return;
}
if (!calendarInitialized || !calendarJy) {
const c = currentJalali();
calendarJy = c[0];
calendarJm = c[1];
calendarInitialized = true;
}
label.textContent = getJalaliMonthName(calendarJm) + ' ' + formatDayNumber(calendarJy);
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
if (Number(sale.jy) === calendarJy && Number(sale.jm) === calendarJm) saleDays[Number(sale.jd)] = true;
});
for (let i = 0; i < offset; i++) {
const div = document.createElement('div');
div.className = 'calendar-day empty';
container.appendChild(div);
}
for (let day = 1; day <= monthLength; day++) {
const div = document.createElement('div');
div.className = 'calendar-day';
div.textContent = formatDayNumber(day);
if (day === Number(today.jd) && calendarJm === Number(today.jm) && calendarJy === Number(today.jy)) div.classList.add('today');
if (saleDays[day]) div.classList.add('has-sale');
container.appendChild(div);
}
}
function printInvoice() {
if (!invoiceItems.length) { toast(t('invoiceEmpty'), 'error'); return; }
if (!invoiceId) invoiceId = generateInvoiceIdLocal();
if (!invoiceNumber) invoiceNumber = generateInvoiceNumberLocal();
const total = getSubtotal();
const discount = getEffectiveDiscount();
const vat = getVatAmount();
const finalTotal = total - discount + vat;
const dateTimeLines = getPanelDateTimeLines();
const now = new Date();
const j = currentJalali();
const dateText = calendarMode === 'gregorian' ? formatGregorianString(now.getFullYear(), now.getMonth() + 1, now.getDate()) : formatJalaliString(j[0], j[1], j[2]);
document.getElementById('printArea').innerHTML = buildInvoicePrintHtml({
items: invoiceItems,
subtotal: total,
discount: discount,
vat: vat,
total: finalTotal,
invoiceNumber: invoiceNumber,
invoiceId: invoiceId,
dateText: dateText,
customerName: invoiceCustomerName,
customerPhone: invoiceCustomerPhone,
note: invoiceNote,
extraLines: dateTimeLines
});
window.print();
}
document.addEventListener('keydown', function (e) {
if (e.key === 'Escape') {
const m = document.getElementById('scanModal');
if (m && m.style.display === 'flex') closeCameraScan();
}
});
initRestoreDrop();
loadData();
</script>
</body>
</html>