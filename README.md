# سیستم مدیریت انبار

## 📋 درباره پروژه
این پروژه یک سیستم مدیریت انبار ساده است که با PHP نوشته شده است.

### 🚀 امکانات
- مدیریت موجودی کالا
- ورود و خروج اطلاعات با CSV
- گزارش‌گیری
- ارسال ایمیل گزارشات

### 🔧 نصب و راه‌اندازی
```bash
# کلون کردن مخزن
git clone https://github.com/your-username/inventory-system.git

# نصب وابستگی‌ها
composer install

# تنظیم فایل .env
cp .env.example .env
php artisan key:generate

# اجرای مهاجرت‌های پایگاه داده
php artisan migrate

# اجرای سرور محلی
php -S localhost:8000
```

### 📝 ساختار پروژه
```
├── src/
│   ├── controllers/
│   ├── models/
│   └── views/
├── public/
│   ├── css/
│   └── js/
└── tests/
```

### 👥 مشارکت
برای مشارکت در پروژه، لطفاً موارد زیر را رعایت کنید:
1. یک شاخه جدید ایجاد کنید
2. تغییرات خود را اعمال کنید
3. یک درخواست pull ایجاد کنید

### 📄 مجوز
MIT License