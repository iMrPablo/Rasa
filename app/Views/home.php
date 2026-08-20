<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'پروژه MVC'; ?></title>
    <style>
        :root {
            --bg-primary: #f4f4f4;
            --bg-container: #ffffff;
            --text-primary: #333333;
            --text-secondary: #666666;
            --accent-color: #4CAF50;
            --info-bg: #e7f3ff;
            --info-border: #2196F3;
            --shadow: rgba(0, 0, 0, 0.1);
        }

        [data-theme="dark"] {
            --bg-primary: #1a1a2e;
            --bg-container: #16213e;
            --text-primary: #eaeaea;
            --text-secondary: #b0b0b0;
            --accent-color: #00d9a5;
            --info-bg: #0f3460;
            --info-border: #00d9a5;
            --shadow: rgba(0, 0, 0, 0.3);
        }

        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            font-family: Tahoma, Arial, sans-serif;
            background-color: var(--bg-primary);
            margin: 0;
            padding: 20px;
            direction: rtl;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--bg-container);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px var(--shadow);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        h1 {
            color: var(--text-primary);
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 10px;
            margin: 0;
        }

        .theme-toggle {
            background: var(--bg-primary);
            border: 2px solid var(--accent-color);
            color: var(--text-primary);
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 5px var(--shadow);
        }

        .theme-toggle:hover {
            background: var(--accent-color);
            color: var(--bg-container);
            transform: translateY(-2px);
        }

        .theme-icon {
            font-size: 18px;
        }

        .content {
            color: var(--text-primary);
            line-height: 1.8;
        }

        .info {
            background: var(--info-bg);
            padding: 15px;
            border-right: 4px solid var(--info-border);
            margin: 20px 0;
            border-radius: 4px;
        }

        .info p {
            color: var(--text-primary);
            margin: 8px 0;
        }

        .version {
            color: var(--text-secondary);
            font-size: 0.9em;
            margin-top: 10px;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--info-border);
            color: var(--text-secondary);
            font-size: 0.85em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo isset($heading) ? $heading : 'خوش آمدید'; ?></h1>
            <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()">
                <span class="theme-icon" id="themeIcon">🌙</span>
                <span id="themeText">حالت تاریک</span>
            </button>
        </div>
        
        <?php if (isset($content)): ?>
            <div class="content">
                <?php echo $content; ?>
            </div>
        <?php endif; ?>
        
        <div class="info">
            <p><strong>طراح و توسعه‌دهنده:</strong> آقای پابلو</p>
            <p class="version">
                <strong>نسخه اولیه:</strong> خرداد ۱۴۰۵ | 
                <strong>نسخه نهایی:</strong> ۲۸ مرداد ۱۴۰۵
            </p>
        </div>

        <div class="footer">
            <p>© ۱۴۰۵ - تمامی حقوق متعلق به آقای پابلو می‌باشد</p>
            <p>طراحی و توسعه با ❤️ توسط آقای پابلو</p>
        </div>
    </div>

    <script>
        // بررسی تم ذخیره شده در LocalStorage
        function initTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            updateThemeButton(savedTheme);
        }

        // تغییر تم
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeButton(newTheme);
        }

        // به‌روزرسانی دکمه تم
        function updateThemeButton(theme) {
            const themeIcon = document.getElementById('themeIcon');
            const themeText = document.getElementById('themeText');
            
            if (theme === 'dark') {
                themeIcon.textContent = '🌞';
                themeText.textContent = 'حالت روشن';
            } else {
                themeIcon.textContent = '🌙';
                themeText.textContent = 'حالت تاریک';
            }
        }

        // اجرای اولیه هنگام لود صفحه
        initTheme();
    </script>
</body>
</html>
