# آموزش کاربردی پروژه انبارداری

## مقدمه - مفاهیم پایه 🎓

### دیتابیس چیست؟
دیتابیس یک مخزن اطلاعاتی است که داده‌ها را به صورت ساختاریافته ذخیره می‌کند. در این پروژه، ما از MySQL استفاده می‌کنیم که یک دیتابیس رابطه‌ای است.

### جداول مهم در پروژه
1. `inventory_sessions`: جلسات انبارگردانی
   ```sql
   CREATE TABLE inventory_sessions (
       session_id VARCHAR(50) PRIMARY KEY,
       status VARCHAR(20),
       started_at DATETIME,
       completed_at DATETIME
   );
   ```

2. `devices`: اطلاعات دستگاه‌ها
   ```sql
   CREATE TABLE devices (
       device_id INT PRIMARY KEY,
       device_code VARCHAR(50),
       device_name VARCHAR(255)
   );
   ```

3. `device_bom`: لیست قطعات هر دستگاه
   ```sql
   CREATE TABLE device_bom (
       bom_id INT PRIMARY KEY,
       device_id INT,
       item_code VARCHAR(50),
       quantity_needed INT
   );
   ```

## ساختار پروژه 📁

### فایل‌های اصلی
1. `config.php`
   - تنظیمات اتصال به دیتابیس
   ```php
   $conn = new mysqli($host, $username, $password, $database);
   ```

2. `functions.php`
   - توابع کمکی مثل تبدیل تاریخ
   ```php
   function gregorianToJalali($date) {
       // تبدیل تاریخ میلادی به شمسی
   }
   ```

### صفحات اصلی
1. `index.php`: صفحه اصلی
2. `devices.php`: مدیریت دستگاه‌ها
3. `device_bom.php`: مدیریت قطعات

## مثال‌های کاربردی 💡

### 1. افزودن یک دستگاه جدید
```php
// در new_device.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // دریافت داده‌ها از فرم
    $device_code = clean($_POST['device_code']);
    $device_name = clean($_POST['device_name']);
    
    // ذخیره در دیتابیس
    $sql = "INSERT INTO devices (device_code, device_name) 
            VALUES ('$device_code', '$device_name')";
    $conn->query($sql);
}
```

### 2. نمایش لیست دستگاه‌ها
```php
// در devices.php
$result = $conn->query("SELECT * FROM devices");
while ($row = $result->fetch_assoc()) {
    echo $row['device_name']; // نمایش نام دستگاه
}
```

### 3. افزودن قطعات به BOM
```php
// در device_bom.php
foreach ($items as $item) {
    $sql = "INSERT INTO device_bom 
            (device_id, item_code, quantity_needed) 
            VALUES ($device_id, '$item[code]', $item[quantity])";
    $conn->query($sql);
}
```

## نکات مهم برای یادگیری 📌

### 1. دستورات SQL پایه
- SELECT: بازیابی داده‌ها
  ```sql
  SELECT * FROM devices WHERE device_code = 'ABC123';
  ```
- INSERT: افزودن داده جدید
  ```sql
  INSERT INTO devices (device_code, device_name) VALUES ('ABC123', 'دستگاه تست');
  ```
- UPDATE: بروزرسانی داده
  ```sql
  UPDATE devices SET device_name = 'نام جدید' WHERE device_id = 1;
  ```

### 2. ارتباط PHP با دیتابیس
```php
// اتصال به دیتابیس
$conn = new mysqli($host, $username, $password, $database);

// اجرای کوئری
$result = $conn->query("SELECT * FROM devices");

// دریافت نتایج
while ($row = $result->fetch_assoc()) {
    // پردازش هر ردیف
}
```

### 3. امنیت در PHP
```php
// تمیز کردن ورودی‌ها
function clean($string) {
    global $conn;
    return $conn->real_escape_string(trim($string));
}

// استفاده
$device_code = clean($_POST['device_code']);
```

## مثال عملی: محاسبه قطعات مورد نیاز 🔄

```php
// محاسبه قطعات مورد نیاز برای تولید یک دستگاه
function calculateNeededParts($device_id, $quantity) {
    global $conn;
    
    // دریافت BOM دستگاه
    $sql = "SELECT item_code, quantity_needed 
            FROM device_bom 
            WHERE device_id = $device_id";
    $result = $conn->query($sql);
    
    $needed_parts = [];
    while ($row = $result->fetch_assoc()) {
        $total_needed = $row['quantity_needed'] * $quantity;
        $needed_parts[$row['item_code']] = $total_needed;
    }
    
    return $needed_parts;
}

// استفاده از تابع
$parts = calculateNeededParts(1, 5); // قطعات مورد نیاز برای 5 دستگاه
```

## گام‌های بعدی یادگیری 🎯

1. **مطالعه بیشتر SQL**
   - JOIN‌ها
   - Group By و Having
   - Subqueries

2. **تمرین PHP**
   - کار با آرایه‌ها
   - توابع و کلاس‌ها
   - مدیریت خطاها

3. **مفاهیم وب**
   - HTTP Methods (GET, POST)
   - Sessions و Cookies
   - امنیت وب

## منابع مفید 📚

1. **آموزش SQL**
   - [W3Schools SQL Tutorial](https://www.w3schools.com/sql/)
   - [SQLBolt](https://sqlbolt.com/)

2. **آموزش PHP**
   - [PHP.net](https://www.php.net/manual/en/)
   - [PHP The Right Way](https://phptherightway.com/)

3. **امنیت وب**
   - [OWASP Top 10](https://owasp.org/www-project-top-ten/)
