<?php
require_once 'config.php';
require_once 'includes/functions.php';

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

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>افزودن تامین‌کننده جدید</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
    </style>
</head>
<body>
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
</body>
</html>
