# 📁 ساختار دیتابیس / Database Structure

## 📋 نمای کلی / Overview

این پوشه شامل تمام فایل‌های مربوط به ساختار دیتابیس سیستم مدیریت انبار است.

This folder contains all database-related files for the Inventory Management System.

## 📂 ساختار پوشه‌ها / Directory Structure

```
database/
├── schema.sql              # فایل اصلی اسکیما (شامل همه جداول)
├── schema/                 # فایل‌های جداگانه اسکیما
│   ├── 01_core_inventory.sql      # هسته اصلی سیستم انبار
│   ├── 02_users_security.sql      # سیستم کاربران و امنیت
│   ├── 03_enhanced_features.sql   # ویژگی‌های پیشرفته
│   └── 04_devices_bom.sql         # دستگاه‌ها و لیست قطعات
└── migrations/             # فایل‌های مهاجرت (برای به‌روزرسانی)
    ├── 20250805_create_migrations_table.sql
    ├── 20250806_create_production_tables.sql
    └── 20250808_create_inv_inventory.sql
```

## 🚀 نحوه استفاده / Usage

### نصب اولیه / Initial Setup
```bash
# اجرای اسکیمای کامل
mysql -u username -p database_name < database/schema.sql
```

### اجرای جداگانه / Individual Schema Files
```bash
# اجرای هسته اصلی
mysql -u username -p database_name < database/schema/01_core_inventory.sql

# اجرای سیستم کاربران
mysql -u username -p database_name < database/schema/02_users_security.sql

# سایر فایل‌ها...
```

### مهاجرت‌ها / Migrations
```bash
# اجرای مهاجرت‌های جدید
mysql -u username -p database_name < database/migrations/[migration_file].sql
```

## 📊 جداول موجود / Available Tables

### هسته اصلی / Core Tables
- `inventory` - موجودی انبار
- `inventory_records` - سوابق انبارگردانی
- `inventory_sessions` - جلسات شمارش
- `suppliers` - تامین‌کنندگان

### سیستم کاربران / User System
- `users` - کاربران سیستم
- `user_roles` - نقش‌های کاربری
- `user_sessions` - جلسات کاربری
- `permissions` - مجوزها
- `role_permissions` - مجوزهای نقش‌ها

### ویژگی‌های پیشرفته / Enhanced Features
- `inventory_categories` - دسته‌بندی کالاها
- `emergency_notes` - یادداشت‌های اضطراری
- `manual_withdrawals` - خروج‌های دستی

### دستگاه‌ها و تولید / Devices & Production
- `devices` - دستگاه‌ها
- `device_bom` - لیست قطعات دستگاه‌ها
- `production_orders` - سفارشات تولید
- `production_order_items` - آیتم‌های سفارش تولید

### سیستم مدیریتی / Management
- `settings` - تنظیمات سیستم
- `migrations` - جدول مهاجرت‌ها
- `security_logs` - لاگ‌های امنیتی

## 🔧 نکات مهم / Important Notes

1. **ترتیب اجرای فایل‌ها:** فایل‌های اسکیما باید به ترتیب عددی اجرا شوند
2. **Foreign Key Checks:** در فایل اصلی `schema.sql` غیرفعال و مجدداً فعال می‌شود
3. **Character Set:** تمام جداول از `utf8mb4` استفاده می‌کنند
4. **Engine:** تمام جداول از `InnoDB` استفاده می‌کنند

## 📝 یادداشت‌های توسعه / Development Notes

- برای اضافه کردن جداول جدید، فایل جدید در `schema/` ایجاد کنید
- نام فایل‌ها باید با عدد شروع شود (برای ترتیب اجرا)
- تغییرات ساختار در `migrations/` اعمال شود
- قبل از هر تغییر، از دیتابیس پشتیبان تهیه کنید

---

**تاریخ آخرین به‌روزرسانی:** ۱۴۰۳/۰۶/۰۸
**مسئول ساختار:** تیم توسعه
