# Single Page Application (SPA) Implementation

## Overview
The inventory management system has been converted to a Single Page Application (SPA) with sidebar navigation for improved user experience and faster navigation.

## Features

### 🚀 SPA Navigation
- **No Page Refresh**: All navigation happens without full page reloads
- **Fast Loading**: Content loads via AJAX for instant navigation
- **Loading Indicators**: Visual feedback during content loading
- **Browser History**: Back/forward buttons work correctly
- **URL Hash Routing**: Direct links to specific pages work

### 📱 Collapsible Sidebar
- **Expandable Sections**: Click section titles to show/hide navigation items
- **Visual Indicators**: Icons show expand/collapse state
- **Responsive Design**: Works on all screen sizes
- **Mobile Friendly**: Touch-friendly interface

### 🔧 Technical Implementation

#### Files Modified:
- `templates/themes/default/template-parts/sidebar.php` - SPA-compatible navigation
- `templates/themes/default/templates/home.php` - Main SPA container and JavaScript
- `templates/themes/default/assets/style.css` - Collapsible navigation styles
- `public/bootstrap.php` - SPA mode detection and header/footer skipping
- `public/spa_loader.php` - AJAX content loading API

#### Key Components:

1. **Navigation Links**: Changed from `<a href="...">` to `<a href="#" data-page="..." data-url="...">`
2. **AJAX Loading**: Content loads via `spa_loader.php` API
3. **State Management**: Browser history and URL hash support
4. **Content Container**: `#spa-content` div for dynamic content
5. **Loading Overlay**: Visual feedback during AJAX requests

### 🎯 Usage

#### For Users:
1. **Navigation**: Click any sidebar link to navigate without page refresh
2. **Section Toggle**: Click section titles (e.g., "مدیریت انبار") to expand/collapse
3. **Quick Actions**: Dashboard cards also support SPA navigation
4. **Browser Controls**: Use back/forward buttons normally

#### For Developers:
1. **Add New Pages**: Add page info to `spa_loader.php` allowed pages array
2. **Custom JavaScript**: Use `initializeLoadedContent()` for dynamic components
3. **URL Structure**: Pages accessible via `#page-name` hash

### 🔒 Security
- **AJAX Validation**: Only allowed pages can be loaded
- **X-Requested-With**: Headers validated for AJAX requests
- **Path Sanitization**: File paths validated before inclusion

### 📊 Performance Benefits
- **Reduced Server Load**: No full page reloads
- **Faster Navigation**: Instant content switching
- **Better UX**: Smooth transitions and loading states
- **Mobile Optimized**: Reduced data usage on mobile devices

### 🐛 Troubleshooting

#### Common Issues:
1. **Page Not Loading**: Check browser console for JavaScript errors
2. **Styles Missing**: Ensure CSS files load correctly
3. **Links Not Working**: Verify data attributes on navigation links

#### Debug Mode:
- Open browser developer tools
- Check Network tab for AJAX requests
- Check Console tab for JavaScript errors

### 🔄 Future Enhancements
- **Preloading**: Load frequently used pages in background
- **Offline Support**: Service worker for offline functionality
- **Push Notifications**: Real-time updates without refresh
- **Advanced Routing**: More complex URL structures

---

**Developed by**: m-alizadeh7
**Website**: alizadehx.ir
