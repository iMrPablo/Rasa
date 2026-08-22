<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سیستم مدیریت فروشگاه - نسخه 1.7</title>
    <style>
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f5f5f5;
            --text-primary: #333333;
            --text-secondary: #666666;
            --accent-color: #4CAF50;
            --border-color: #e0e0e0;
            --card-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        [data-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --accent-color: #66BB6A;
            --border-color: #404040;
            --card-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tahoma', 'Segoe UI', sans-serif;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.6;
            transition: all 0.3s ease;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: var(--bg-primary);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .theme-toggle {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab-btn {
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .tab-btn.active {
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
        }

        .tab-content {
            display: none;
            background: var(--bg-primary);
            padding: 24px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
        }

        .tab-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-size: 14px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--accent-color);
            color: white;
        }

        .btn-danger {
            background: #f44336;
            color: white;
        }

        .btn-success {
            background: #4CAF50;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: var(--bg-secondary);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: var(--accent-color);
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background: var(--bg-secondary);
            color: var(--text-secondary);
            font-size: 14px;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .tab-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header no-print">
            <h1>🏪 سیستم مدیریت فروشگاه - نسخه 1.7</h1>
            <button class="theme-toggle" onclick="toggleTheme()">🌙 تغییر تم</button>
        </div>

        <div class="tabs no-print">
            <button class="tab-btn active" onclick="showTab('dashboard')">📊 داشبورد</button>
            <button class="tab-btn" onclick="showTab('settings')">⚙️ تنظیمات فروشگاه</button>
            <button class="tab-btn" onclick="showTab('categories')">📂 دسته‌بندی‌ها</button>
            <button class="tab-btn" onclick="showTab('products')">🛍️ محصولات</button>
            <button class="tab-btn" onclick="showTab('invoice')">📄 فاکتور فروش</button>
            <button class="tab-btn" onclick="showTab('reports')">📈 گزارشات فروش</button>
        </div>

        <!-- Dashboard -->
        <div id="dashboard" class="tab-content active">
            <h2>📊 آمار فروش</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div>فروش امروز</div>
                    <div class="stat-value" id="todaySales">0 تومان</div>
                </div>
                <div class="stat-card">
                    <div>فروش هفته</div>
                    <div class="stat-value" id="weekSales">0 تومان</div>
                </div>
                <div class="stat-card">
                    <div>فروش ماه</div>
                    <div class="stat-value" id="monthSales">0 تومان</div>
                </div>
                <div class="stat-card">
                    <div>تعداد کل فروش‌ها</div>
                    <div class="stat-value" id="totalSales">0</div>
                </div>
            </div>
        </div>

        <!-- Settings -->
        <div id="settings" class="tab-content">
            <h2>⚙️ تنظیمات فروشگاه</h2>
            <form id="settingsForm" onsubmit="saveSettings(event)">
                <div class="form-group">
                    <label>نام فروشگاه:</label>
                    <input type="text" class="form-control" id="storeName" name="name">
                </div>
                <div class="form-group">
                    <label>شماره تماس:</label>
                    <input type="text" class="form-control" id="storePhone" name="phone">
                </div>
                <div class="form-group">
                    <label>آدرس:</label>
                    <textarea class="form-control" id="storeAddress" name="address" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">💾 ذخیره تنظیمات</button>
            </form>
        </div>

        <!-- Categories -->
        <div id="categories" class="tab-content">
            <h2>📂 مدیریت دسته‌بندی‌ها</h2>
            <form onsubmit="addCategory(event)" style="margin-bottom: 20px;">
                <div class="form-group">
                    <label>نام دسته‌بندی جدید:</label>
                    <input type="text" class="form-control" id="categoryName" required>
                </div>
                <button type="submit" class="btn btn-primary">➕ افزودن دسته‌بندی</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>نام دسته‌بندی</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody id="categoriesTable"></tbody>
            </table>
        </div>

        <!-- Products -->
        <div id="products" class="tab-content">
            <h2>🛍️ مدیریت محصولات</h2>
            <form onsubmit="addProduct(event)" style="margin-bottom: 20px;">
                <div class="form-group">
                    <label>نام محصول:</label>
                    <input type="text" class="form-control" id="productName" required>
                </div>
                <div class="form-group">
                    <label>دسته‌بندی:</label>
                    <select class="form-control" id="productCategory" required>
                        <option value="">انتخاب کنید</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>قیمت (تومان):</label>
                    <input type="number" class="form-control" id="productPrice" required>
                </div>
                <button type="submit" class="btn btn-primary">➕ افزودن محصول</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>نام محصول</th>
                        <th>دسته‌بندی</th>
                        <th>قیمت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody id="productsTable"></tbody>
            </table>
        </div>

        <!-- Invoice -->
        <div id="invoice" class="tab-content">
            <h2>📄 فاکتور فروش</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <h3>افزودن به فاکتور</h3>
                    <form onsubmit="addToInvoice(event)" style="margin-top: 10px;">
                        <div class="form-group">
                            <label>نام محصول:</label>
                            <select class="form-control" id="invoiceProduct">
                                <option value="">انتخاب از لیست محصولات</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>یا محصول دستی:</label>
                            <input type="text" class="form-control" id="manualProductName" placeholder="نام محصول">
                        </div>
                        <div class="form-group">
                            <label>قیمت (تومان):</label>
                            <input type="number" class="form-control" id="invoicePrice" required>
                        </div>
                        <div class="form-group">
                            <label>تعداد:</label>
                            <input type="number" class="form-control" id="invoiceQuantity" value="1" min="1">
                        </div>
                        <button type="submit" class="btn btn-primary">➕ افزودن به فاکتور</button>
                    </form>
                </div>
                <div>
                    <h3>خلاصه فاکتور</h3>
                    <div style="margin-top: 10px;">
                        <p>تعداد آیتم‌ها: <span id="invoiceItemsCount">0</span></p>
                        <p>جمع کل: <span id="invoiceSubtotal">0</span> تومان</p>
                        <p>تخفیف: <input type="number" id="invoiceDiscount" value="0" onchange="updateDiscount()" style="width: 100px;"></p>
                        <p><strong>مبلغ قابل پرداخت: <span id="invoiceTotal">0</span> تومان</strong></p>
                    </div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>محصول</th>
                        <th>قیمت</th>
                        <th>تعداد</th>
                        <th>جمع</th>
                        <th class="no-print">عملیات</th>
                    </tr>
                </thead>
                <tbody id="invoiceTable"></tbody>
            </table>
            <div style="margin-top: 20px;" class="no-print">
                <button class="btn btn-success" onclick="finalizeInvoice()">✅ ثبت نهایی فاکتور</button>
                <button class="btn btn-primary" onclick="printInvoice()">🖨️ چاپ فاکتور</button>
            </div>
        </div>

        <!-- Reports -->
        <div id="reports" class="tab-content">
            <h2>📈 گزارشات فروش</h2>
            <div class="form-group" style="max-width: 300px;">
                <label>انتخاب تاریخ (شمسی):</label>
                <input type="text" class="form-control" id="reportDate" placeholder="1405-05-28">
            </div>
            <button class="btn btn-primary" onclick="getReportByDate()">🔍 جستجو</button>
            <button class="btn btn-success" onclick="getAllSales()" style="margin-right: 10px;">📋 همه فروش‌ها</button>
            <table id="salesTable">
                <thead>
                    <tr>
                        <th>شماره فاکتور</th>
                        <th>تاریخ</th>
                        <th>مبلغ کل</th>
                        <th>تخفیف</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody id="salesTableBody"></tbody>
            </table>
        </div>
    </div>

    <script>
        // Theme Toggle
        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme');
            document.documentElement.setAttribute('data-theme', current === 'dark' ? 'light' : 'dark');
            localStorage.setItem('theme', current === 'dark' ? 'light' : 'dark');
        }

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);

        // Tab Navigation
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            event.target.classList.add('active');
            
            if (tabId === 'dashboard') loadStats();
            if (tabId === 'categories') loadCategories();
            if (tabId === 'products') { loadProducts(); loadCategoriesForSelect(); }
            if (tabId === 'invoice') { loadInvoice(); loadProductsForInvoice(); }
        }

        // Format Price
        function formatPrice(price) {
            return parseInt(price).toLocaleString('fa-IR');
        }

        // API Helper
        async function api(endpoint, method = 'GET', data = null) {
            const options = {
                method,
                headers: { 'Content-Type': 'application/json' }
            };
            if (data) options.body = JSON.stringify(data);
            
            const response = await fetch('?action=' + endpoint, options);
            return await response.json();
        }

        // Load Stats
        async function loadStats() {
            const result = await api('getSalesStats');
            if (result.success) {
                document.getElementById('todaySales').textContent = formatPrice(result.stats.today) + ' تومان';
                document.getElementById('weekSales').textContent = formatPrice(result.stats.week) + ' تومان';
                document.getElementById('monthSales').textContent = formatPrice(result.stats.month) + ' تومان';
                document.getElementById('totalSales').textContent = result.stats.total_sales.toLocaleString('fa-IR');
            }
        }

        // Settings
        async function saveSettings(e) {
            e.preventDefault();
            const data = {
                name: document.getElementById('storeName').value,
                phone: document.getElementById('storePhone').value,
                address: document.getElementById('storeAddress').value
            };
            const result = await api('saveSettings', 'POST', data);
            alert(result.message);
        }

        // Categories
        async function loadCategories() {
            const result = await api('getCategories');
            if (result.success) {
                const tbody = document.getElementById('categoriesTable');
                tbody.innerHTML = result.categories.map(cat => `
                    <tr>
                        <td>${cat.name}</td>
                        <td class="actions">
                            <button class="btn btn-sm btn-primary" onclick="editCategory('${cat.id}', '${cat.name}')">✏️</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteCategory('${cat.id}')">🗑️</button>
                        </td>
                    </tr>
                `).join('');
            }
        }

        async function addCategory(e) {
            e.preventDefault();
            const name = document.getElementById('categoryName').value;
            const result = await api('addCategory', 'POST', { name });
            alert(result.message);
            if (result.success) {
                document.getElementById('categoryName').value = '';
                loadCategories();
            }
        }

        async function editCategory(id, name) {
            const newName = prompt('نام جدید دسته‌بندی:', name);
            if (newName) {
                const result = await api('updateCategory', 'POST', { id, name: newName });
                alert(result.message);
                loadCategories();
            }
        }

        async function deleteCategory(id) {
            if (confirm('آیا مطمئن هستید؟')) {
                const result = await api('deleteCategory', 'POST', { id });
                alert(result.message);
                loadCategories();
            }
        }

        // Products
        async function loadCategoriesForSelect() {
            const result = await api('getCategories');
            const select = document.getElementById('productCategory');
            select.innerHTML = '<option value="">انتخاب کنید</option>' + 
                result.categories.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('');
        }

        async function loadProducts() {
            const result = await api('getProducts');
            if (result.success) {
                const categories = await api('getCategories');
                const catMap = Object.fromEntries(categories.categories.map(c => [c.id, c.name]));
                
                const tbody = document.getElementById('productsTable');
                tbody.innerHTML = result.products.map(prod => `
                    <tr>
                        <td>${prod.name}</td>
                        <td>${catMap[prod.category_id] || '-'}</td>
                        <td>${formatPrice(prod.price)}</td>
                        <td class="actions">
                            <button class="btn btn-sm btn-primary" onclick="editProduct('${prod.id}')">✏️</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteProduct('${prod.id}')">🗑️</button>
                        </td>
                    </tr>
                `).join('');
            }
        }

        async function addProduct(e) {
            e.preventDefault();
            const data = {
                name: document.getElementById('productName').value,
                category_id: document.getElementById('productCategory').value,
                price: document.getElementById('productPrice').value
            };
            const result = await api('addProduct', 'POST', data);
            alert(result.message);
            if (result.success) {
                e.target.reset();
                loadProducts();
            }
        }

        async function loadProductsForInvoice() {
            const result = await api('getProducts');
            const select = document.getElementById('invoiceProduct');
            select.innerHTML = '<option value="">انتخاب از لیست محصولات</option>' + 
                result.products.map(prod => `<option value="${prod.id}" data-price="${prod.price}">${prod.name} - ${formatPrice(prod.price)} تومان</option>`).join('');
        }

        // Invoice
        let currentInvoice = { items: [], discount: 0 };

        async function loadInvoice() {
            const result = await api('getCurrentInvoice');
            if (result.success) {
                currentInvoice = result.invoice;
                currentInvoice.discount = result.total.discount;
                updateInvoiceDisplay(result.total);
            }
        }

        function updateInvoiceDisplay(total) {
            document.getElementById('invoiceItemsCount').textContent = total.items_count.toLocaleString('fa-IR');
            document.getElementById('invoiceSubtotal').textContent = formatPrice(total.subtotal);
            document.getElementById('invoiceDiscount').value = total.discount;
            document.getElementById('invoiceTotal').textContent = formatPrice(total.total);

            const tbody = document.getElementById('invoiceTable');
            tbody.innerHTML = currentInvoice.items.map((item, index) => `
                <tr>
                    <td>${item.name}</td>
                    <td>${formatPrice(item.price)}</td>
                    <td>${item.quantity}</td>
                    <td>${formatPrice(item.price * item.quantity)}</td>
                    <td class="no-print">
                        <button class="btn btn-sm btn-danger" onclick="removeFromInvoice(${index})">🗑️</button>
                    </td>
                </tr>
            `).join('');
        }

        async function addToInvoice(e) {
            e.preventDefault();
            const productSelect = document.getElementById('invoiceProduct');
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            
            const data = {
                id: productSelect.value || null,
                name: document.getElementById('manualProductName').value || selectedOption.text.split(' - ')[0],
                price: document.getElementById('invoicePrice').value || selectedOption.dataset?.price || 0,
                quantity: document.getElementById('invoiceQuantity').value
            };

            const result = await api('addToInvoice', 'POST', data);
            if (result.success) {
                loadInvoice();
                document.getElementById('invoicePrice').value = '';
                document.getElementById('manualProductName').value = '';
            }
            alert(result.message);
        }

        async function removeFromInvoice(index) {
            const result = await api('removeFromInvoice', 'POST', { index });
            if (result.success) loadInvoice();
            alert(result.message);
        }

        async function updateDiscount() {
            const discount = document.getElementById('invoiceDiscount').value;
            const result = await api('setDiscount', 'POST', { discount });
            if (result.success) loadInvoice();
        }

        async function finalizeInvoice() {
            if (!confirm('آیا از ثبت نهایی فاکتور مطمئن هستید؟')) return;
            const result = await api('finalizeInvoice', 'POST', {});
            alert(result.message);
            if (result.success) {
                loadInvoice();
                loadStats();
            }
        }

        function printInvoice() {
            window.print();
        }

        // Reports
        async function getAllSales() {
            const result = await api('getAllSales');
            if (result.success) {
                const tbody = document.getElementById('salesTableBody');
                tbody.innerHTML = result.sales.map(sale => `
                    <tr>
                        <td>${sale.invoice_number}</td>
                        <td>${sale.jalali_date}</td>
                        <td>${formatPrice(sale.total)}</td>
                        <td>${formatPrice(sale.discount)}</td>
                        <td><button class="btn btn-sm btn-primary" onclick="alert('جزئیات: ${JSON.stringify(sale.items)}')">📋</button></td>
                    </tr>
                `).join('');
            }
        }

        async function getReportByDate() {
            const date = document.getElementById('reportDate').value;
            const result = await api('getSalesByDate', 'POST', { date });
            if (result.success) {
                const tbody = document.getElementById('salesTableBody');
                tbody.innerHTML = result.sales.map(sale => `
                    <tr>
                        <td>${sale.invoice_number}</td>
                        <td>${sale.jalali_date}</td>
                        <td>${formatPrice(sale.total)}</td>
                        <td>${formatPrice(sale.discount)}</td>
                        <td><button class="btn btn-sm btn-primary" onclick="alert('جزئیات: ${JSON.stringify(sale.items)}')">📋</button></td>
                    </tr>
                `).join('');
            }
        }

        // Initial load
        loadStats();
    </script>
</body>
</html>
