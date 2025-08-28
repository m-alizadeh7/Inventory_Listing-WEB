/**
 * Navigation functionality for the theme
 */
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            const off = new bootstrap.Offcanvas(mobileMenu);
            off.show();
        });
    }
    
    // Set active class in navigation based on current URL
    const currentUrl = window.location.pathname;
    const navLinks = document.querySelectorAll('.offcanvas-body a, .mobile-footer a, .desktop-actions a');
    
    navLinks.forEach(function(link) {
        const href = link.getAttribute('href');
        
        if (href === currentUrl || (currentUrl.indexOf(href) !== -1 && href !== '/')) {
            link.classList.add('active');
        }
    });
});
