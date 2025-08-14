<?php
require_once 'bootstrap.php';
// افزودن ستون supplier_code اگر وجود ندارد
$res = $conn->query("SHOW COLUMNS FROM suppliers LIKE 'supplier_code'");
if ($res && $res->num_rows === 0) {
    if (!$conn->query("ALTER TABLE suppliers ADD COLUMN supplier_code VARCHAR(50) NULL")) {
        die('خطا در افزودن ستون supplier_code: ' . $conn->error);
    }
}

// افزودن ستون contact_person اگر وجود ندارد
$res = $conn->query("SHOW COLUMNS FROM suppliers LIKE 'contact_person'");
if ($res && $res->num_rows === 0) {
    if (!$conn->query("ALTER TABLE suppliers ADD COLUMN contact_person VARCHAR(100) NULL")) {
        die('خطا در افزودن ستون contact_person: ' . $conn->error);
    }
}

// افزودن ستون phone اگر وجود ندارد
$res = $conn->query("SHOW COLUMNS FROM suppliers LIKE 'phone'");
if ($res && $res->num_rows === 0) {
    if (!$conn->query("ALTER TABLE suppliers ADD COLUMN phone VARCHAR(30) NULL")) {
        die('خطا در افزودن ستون phone: ' . $conn->error);
    }
}

// افزودن ستون email اگر وجود ندارد
$res = $conn->query("SHOW COLUMNS FROM suppliers LIKE 'email'");
if ($res && $res->num_rows === 0) {
    if (!$conn->query("ALTER TABLE suppliers ADD COLUMN email VARCHAR(100) NULL")) {
        die('خطا در افزودن ستون email: ' . $conn->error);
    }
}

// افزودن ستون address اگر وجود ندارد
$res = $conn->query("SHOW COLUMNS FROM suppliers LIKE 'address'");
if ($res && $res->num_rows === 0) {
    if (!$conn->query("ALTER TABLE suppliers ADD COLUMN address TEXT NULL")) {
        die('خطا در افزودن ستون address: ' . $conn->error);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_code = clean($_POST['supplier_code']);
    $supplier_name = clean($_POST['supplier_name']);
    $contact_person = clean($_POST['contact_person']);
    $phone = clean($_POST['phone']);
    $email = clean($_POST['email']);
    $address = clean($_POST['address']);

    // بررسی تکراری نبودن کد تامین‌کننده
    $check = $conn->query("SELECT supplier_id FROM suppliers WHERE supplier_code = '$supplier_code'");
    if ($check->num_rows > 0) {
        $error = 'کد تامین‌کننده تکراری است.';
    } else {
        // افزودن تامین‌کننده جدید
        $sql = "INSERT INTO suppliers (supplier_code, supplier_name, contact_person, phone, email, address) 
                VALUES ('$supplier_code', '$supplier_name', '$contact_person', '$phone', '$email', '$address')";
        
        if ($conn->query($sql)) {
            header('Location: suppliers.php?msg=added');
            exit;
        } else {
            $error = 'خطا در ثبت تامین‌کننده.';
        }
    }
}
?>

<?php get_template_part('header'); ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">➕ افزودن تامین‌کننده جدید</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="supplier_code" class="form-label">کد تامین‌کننده</label>
                                <input type="text" class="form-control" id="supplier_code" name="supplier_code" 
                                       required pattern="[A-Za-z0-9-_]+" 
                                       title="فقط حروف انگلیسی، اعداد، خط تیره و زیرخط مجاز است">
                                <div class="invalid-feedback">
                                    لطفاً کد معتبر وارد کنید (فقط حروف انگلیسی و اعداد)
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="supplier_name" class="form-label">نام شرکت</label>
                                <input type="text" class="form-control" id="supplier_name" name="supplier_name" required>
                                <div class="invalid-feedback">
                                    لطفاً نام شرکت را وارد کنید
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="contact_person" class="form-label">شخص رابط</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="phone" class="form-label">شماره تماس</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       pattern="[0-9-+]+" title="فقط اعداد و علامت‌های - و + مجاز است">
                                <div class="invalid-feedback">
                                    لطفاً شماره تماس معتبر وارد کنید
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">ایمیل</label>
                            <input type="email" class="form-control" id="email" name="email">
                            <div class="invalid-feedback">
                                لطفاً ایمیل معتبر وارد کنید
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">آدرس</label>
                            <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> ثبت تامین‌کننده
                            </button>
                            <a href="suppliers.php" class="btn btn-secondary">
                                <i class="bi bi-x-lg"></i> انصراف
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// اعتبارسنجی فرم
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php get_template_part('footer'); ?>
