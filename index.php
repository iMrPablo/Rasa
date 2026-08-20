<?php
$storageDirectory = __DIR__ . '/rasa_data';
if (!is_dir($storageDirectory)) {
    @mkdir($storageDirectory, 0775, true);
}

$settingsFile = $storageDirectory . '/settings.json';
$categoriesFile = $storageDirectory . '/categories.json';
$productsFile = $storageDirectory . '/products.json';
$invoiceFile = $storageDirectory . '/invoice.json';
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
    @file_put_contents($invoiceFile, json_encode([], JSON_UNESCAPED_UNICODE), LOCK_EX);
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
    $invoiceItems = readJsonFile($invoiceFile, []);

    if ($action === 'get') {
        jsonResponse([
            'ok' => true,
            'settings' => $settings,
            'categories' => $categories,
            'products' => $products,
            'invoiceItems' => $invoiceItems
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

        if (!writeJsonFile($invoiceFile, $cleanItems)) {
            jsonResponse(['ok' => false, 'error' => 'خطا در ذخیره فاکتور']);
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
    <title>پنل رسا - نسخه 1</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Tahoma, sans-serif;
            background: #f6f6f6;
            color: #222;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e2e2e2;
            border-radius: 12px;
            overflow: hidden;
        }

        .header {
            padding: 22px;
            text-align: center;
            border-bottom: 1px solid #e8e8e8;
        }

        .header h1 {
            font-size: 24px;
            color: #111;
        }

        .header div {
            margin-top: 5px;
            color: #777;
            font-size: 13px;
        }

        .tabs {
            display: flex;
            background: #fafafa;
            border-bottom: 1px solid #e8e8e8;
        }

        .tab {
            flex: 1;
            border: none;
            background: none;
            padding: 13px 8px;
            font-size: 14px;
            color: #666;
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
            padding: 22px;
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
            color: #444;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #dcdcdc;
            border-radius: 9px;
            font-size: 14px;
            font-family: inherit;
            background: #fff;
            color: #222;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #999;
        }

        .btn {
            border: 1px solid #111;
            background: #111;
            color: #fff;
            border-radius: 9px;
            padding: 10px 18px;
            font-size: 14px;
            cursor: pointer;
        }

        .btn:hover {
            opacity: 0.88;
        }

        .btn-danger {
            background: #fff;
            border-color: #d64545;
            color: #d64545;
            padding: 7px 12px;
            font-size: 12px;
        }

        .btn-danger:hover {
            background: #d64545;
            color: #fff;
        }

        .card {
            border: 1px solid #e6e6e6;
            background: #fcfcfc;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            border: 1px solid #e8e8e8;
            background: #fff;
            border-radius: 10px;
            padding: 11px 12px;
            margin-bottom: 9px;
        }

        .muted {
            color: #777;
            font-size: 12px;
            margin-top: 3px;
        }

        .empty-state {
            border: 1px dashed #d5d5d5;
            color: #777;
            text-align: center;
            padding: 26px;
            border-radius: 12px;
            background: #fafafa;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            background: #fff;
        }

        .invoice-table th,
        .invoice-table td {
            border: 1px solid #e3e3e3;
            padding: 9px;
            text-align: right;
            font-size: 14px;
        }

        .invoice-table th {
            background: #fafafa;
            color: #333;
        }

        .total-section {
            margin-top: 16px;
            border: 1px solid #e5e5e5;
            background: #fafafa;
            border-radius: 12px;
            padding: 14px;
            text-align: left;
        }

        .total-amount {
            font-size: 19px;
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
                margin-bottom: 14px;
                font-weight: 700;
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
            <div>نسخه 1 - طراح و توسعه دهنده : آقای پابلو</div>
        </div>

        <div class="tabs">
            <button class="tab active" data-tab="settings" onclick="switchTab('settings')">تنظیمات</button>
            <button class="tab" data-tab="categories" onclick="switchTab('categories')">دسته‌بندی‌ها</button>
            <button class="tab" data-tab="products" onclick="switchTab('products')">محصولات</button>
            <button class="tab" data-tab="invoice" onclick="switchTab('invoice')">ساخت فاکتور</button>
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

                <button class="btn" onclick="saveSettings()">ذخیره تنظیمات</button>
            </div>

            <div id="categories" class="tab-content">
                <h2>مدیریت دسته‌بندی‌ها</h2>

                <div class="form-group">
                    <label>نام دسته‌بندی</label>
                    <input type="text" id="categoryName">
                </div>

                <button class="btn" onclick="addCategory()">افزودن دسته‌بندی</button>

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

                <button class="btn" onclick="addProduct()">افزودن محصول</button>

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

                    <button class="btn" onclick="addToInvoice()">افزودن به فاکتور</button>
                </div>

                <div id="invoiceItems"></div>

                <div id="totalSection" class="total-section" style="display: none;">
                    <div>جمع کل:</div>
                    <div id="totalAmount" class="total-amount">0 تومان</div>
                </div>

                <div class="actions">
                    <button class="btn" onclick="saveInvoice(true)">ذخیره لیست فاکتور</button>
                    <button class="btn" onclick="printInvoice()">پرینت فاکتور</button>
                </div>
            </div>
        </div>
    </div>

    <div id="printArea"></div>

    <script>
        let appData = {
            settings: { name: '', phone: '', address: '' },
            categories: [],
            products: []
        };

        let invoiceItems = [];

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
                invoiceItems = Array.isArray(result.invoiceItems) ? result.invoiceItems : [];

                fillSettingsForm();
                renderCategories();
                renderProducts();
                renderInvoiceCategorySelects();
                renderInvoiceProductSelect();
                renderInvoiceItems();
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
                button.textContent = 'حذف';
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
                button.textContent = 'حذف';
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

        async function saveInvoice(showAlert) {
            const result = await api('save_invoice', { items: invoiceItems });

            if (showAlert) {
                if (result.ok) {
                    alert('لیست فاکتور ذخیره شد');
                } else {
                    alert(result.error || 'خطا در ذخیره فاکتور');
                }
            }

            return result;
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
                button.textContent = 'حذف';
                button.onclick = function () {
                    removeFromInvoice(index);
                };

                actionCell.appendChild(button);
                row.appendChild(actionCell);
                tbody.appendChild(row);
            });

            table.appendChild(tbody);
            box.appendChild(table);

            totalSection.style.display = 'block';
            document.getElementById('totalAmount').textContent = formatPrice(total) + ' تومان';
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

            const storeName = appData.settings.name ? escapeHtml(appData.settings.name) : 'پنل رسا';
            const phone = appData.settings.phone ? '<p>شماره تماس: ' + escapeHtml(appData.settings.phone) + '</p>' : '';
            const address = appData.settings.address ? '<p>آدرس: ' + escapeHtml(appData.settings.address) + '</p>' : '';

            document.getElementById('printArea').innerHTML =
                '<div class="print-header">'
                + '<h1>' + storeName + '</h1>'
                + phone
                + address
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
                + '<div>جمع کل:</div>'
                + '<div class="total-amount">' + escapeHtml(formatPrice(total)) + ' تومان</div>'
                + '</div>';

            window.print();
        }

        loadData();
    </script>
</body>
</html>