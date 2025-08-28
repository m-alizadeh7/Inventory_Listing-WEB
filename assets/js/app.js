/**
 * فایل اصلی جاوااسکریپت برنامه
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('سیستم مدیریت انبار بارگذاری شد');
    
    // تنظیم فرم‌ها
    setupForms();
    
    // راه‌اندازی تولتیپ‌ها و پاپ‌اورها
    setupTooltips();
    
    // راه‌اندازی دیتاتیبل‌ها
    setupDataTables();
    
    // راه‌اندازی تاریخ شمسی
    setupPersianDatepickers();
    
    // تنظیم منوی موبایل
    setupMobileMenu();
});

/**
 * تنظیم فرم‌ها
 */
function setupForms() {
    // فعال‌سازی اعتبارسنجی Bootstrap
    var forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // تغییر نمایش رمز عبور
    var passwordToggles = document.querySelectorAll('.password-toggle');
    
    passwordToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            var passwordField = document.querySelector(this.dataset.target);
            var icon = this.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    });
}

/**
 * راه‌اندازی تولتیپ‌ها و پاپ‌اورها
 */
function setupTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    });
}

/**
 * راه‌اندازی DataTables
 */
function setupDataTables() {
    if (typeof $.fn.dataTable !== 'undefined') {
        $('.datatable').each(function() {
            $(this).DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.10.24/i18n/Persian.json'
                },
                responsive: true,
                ordering: true,
                pageLength: 25
            });
        });
    }
}

/**
 * راه‌اندازی انتخاب‌گر تاریخ شمسی
 */
function setupPersianDatepickers() {
    if (typeof persianDatepicker !== 'undefined') {
        $('.persian-datepicker').persianDatepicker({
            format: 'YYYY/MM/DD',
            autoClose: true,
            initialValue: false
        });
    }
}

/**
 * تنظیم منوی موبایل
 */
function setupMobileMenu() {
    var mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            document.body.classList.toggle('mobile-menu-open');
        });
    }
}

/**
 * نمایش پیام به کاربر
 * @param {string} message متن پیام
 * @param {string} type نوع پیام (success, danger, warning, info)
 * @param {number} duration مدت زمان نمایش به میلی‌ثانیه
 */
function showAlert(message, type = 'info', duration = 3000) {
    const alertContainer = document.getElementById('alert-container') || createAlertContainer();
    const alert = document.createElement('div');
    
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    alertContainer.appendChild(alert);
    
    // حذف خودکار بعد از زمان مشخص
    if (duration > 0) {
        setTimeout(() => {
            if (alert) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            }
        }, duration);
    }
    
    return alert;
}

/**
 * ایجاد کانتینر برای نمایش پیام‌ها
 */
function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alert-container';
    container.className = 'alert-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1050';
    document.body.appendChild(container);
    return container;
}

/**
 * تایید قبل از حذف
 * @param {string} url آدرس حذف
 * @param {string} message پیام تایید
 */
function confirmDelete(url, message = 'آیا از حذف این مورد اطمینان دارید؟') {
    if (confirm(message)) {
        window.location.href = url;
    }
}
