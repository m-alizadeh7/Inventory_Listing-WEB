# راهنمای نصب و پیاده‌سازی سیستم انبارداری

## پیش‌نیازها
- PHP 7.4 یا بالاتر
- MySQL 5.7 یا بالاتر
- Composer
- وب‌سرور Apache یا Nginx

## مراحل نصب

### 1. آماده‌سازی پروژه
```bash
# کلون کردن پروژه
git clone https://github.com/m-alizadeh7/Inventory_Listing-WEB.git
cd Inventory_Listing-WEB

# سوییچ به برنچ MVC
git checkout feature/mvc-structure

# نصب وابستگی‌ها
composer install
```

### 2. تنظیمات پایگاه داده
1. فایل `config.example.php` را به `config.php` کپی کنید
2. اطلاعات دیتابیس را در فایل `config.php` وارد کنید:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'your_database');
```

### 3. نصب دیتابیس
1. به آدرس `http://your-domain.com/setup.php` بروید
2. صفحه نصب، جداول مورد نیاز را ایجاد می‌کند
3. در صورت موفقیت، پیام تایید نمایش داده می‌شود

### 4. تنظیم وب‌سرور
مسیر root وب‌سرور را به پوشه `public` تنظیم کنید.

برای Apache (.htaccess در پوشه public):
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

## ساختار پروژه
```
app/
├── Controllers/         # کنترلرهای برنامه
├── Models/             # مدل‌های برنامه
├── Views/              # فایل‌های نمایشی
└── Core/               # کلاس‌های هسته برنامه
    ├── Database.php    # مدیریت دیتابیس
    ├── Model.php       # کلاس پایه مدل‌ها
    ├── Controller.php  # کلاس پایه کنترلرها
    └── Router.php      # مسیریاب برنامه
public/
└── index.php           # نقطه ورود برنامه
config.php              # تنظیمات برنامه
composer.json           # وابستگی‌های پروژه
```

## نحوه استفاده

### 1. ورود اطلاعات انبار
1. به صفحه اصلی بروید
2. روی "ورود اطلاعات جدید" کلیک کنید
3. فایل CSV حاوی اطلاعات انبار را آپلود کنید
4. اطلاعات به صورت خودکار در سیستم ثبت می‌شود

### 2. انبارگردانی
1. از منوی اصلی "انبارگردانی جدید" را انتخاب کنید
2. شماره جلسه به صورت خودکار ایجاد می‌شود
3. اقلام را شمارش و ثبت کنید
4. در پایان، دکمه "ثبت نهایی" را بزنید

### 3. گزارش‌گیری
1. به بخش "گزارش‌های انبارداری" بروید
2. لیست تمام جلسات انبارگردانی را مشاهده کنید
3. برای دریافت خروجی، روی دکمه "دانلود فایل" کلیک کنید

## مشارکت در توسعه
1. یک برنچ جدید ایجاد کنید
2. تغییرات خود را اعمال کنید
3. تست‌ها را اجرا کنید
4. Pull Request ایجاد کنید

## پشتیبانی
برای گزارش مشکلات یا پیشنهادات:
- ایمیل: m.alizadeh7@live.com
- گیت‌هاب: Issues بخش
