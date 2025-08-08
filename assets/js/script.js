/**
 * اسکریپت‌های اصلی سیستم
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

/**
 * تنظیم رویدادها بعد از بارگذاری صفحه
 */
document.addEventListener('DOMContentLoaded', function() {
    // اضافه کردن کلاس active به لینک فعال در منو
    setActiveMenuItem();
    
    // مدیریت فرم‌های با تأییدیه
    setupConfirmForms();
    
    // مدیریت دکمه‌های چاپ
    setupPrintButtons();
});

/**
 * تنظیم آیتم فعال منو
 */
function setActiveMenuItem() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage) {
            link.classList.add('active');
        }
    });
}

/**
 * تنظیم فرم‌های با تأییدیه
 */
function setupConfirmForms() {
    const confirmForms = document.querySelectorAll('form[data-confirm]');
    
    confirmForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const confirmMessage = this.getAttribute('data-confirm');
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

/**
 * تنظیم دکمه‌های چاپ
 */
function setupPrintButtons() {
    const printButtons = document.querySelectorAll('.btn-print');
    
    printButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            window.print();
        });
    });
}

/**
 * راهنمای نمادها در هنگام چاپ
 */
window.onbeforeprint = function() {
    const printHeader = document.querySelector('.print-header');
    
    if (printHeader) {
        const legendHtml = `
            <div class="mt-3 mb-3 d-none d-print-block">
                <hr>
                <p><strong>راهنمای نمادها:</strong></p>
                <p>⚠️ اتمام موجودی | ⚡ موجودی کم | ✅ موجودی کافی</p>
                <hr>
            </div>
        `;
        printHeader.insertAdjacentHTML('afterend', legendHtml);
    }
};
