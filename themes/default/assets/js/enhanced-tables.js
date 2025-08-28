/**
 * Enhanced Table Functionality
 * Includes: Filtering, Sorting, Printing, Excel Export
 * Persian/RTL Support
 */

class EnhancedTable {
    constructor(tableSelector, options = {}) {
        this.table = document.querySelector(tableSelector);
        this.options = {
            printTitle: options.printTitle || 'گزارش سیستم انبارداری',
            companyName: options.companyName || 'شرکت نمونه',
            developerName: options.developerName || 'توسعه‌دهنده: m-alizadeh7',
            showSearch: options.showSearch !== false,
            showExport: options.showExport !== false,
            showPrint: options.showPrint !== false,
            ...options
        };
        
        this.init();
    }
    
    init() {
        if (!this.table) return;
        
        this.createControls();
        this.enableSorting();
        this.enableFiltering();
        this.addResponsive();
    }
    
    createControls() {
        const controlsHtml = `
            <div class="table-controls mb-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <div class="table-search-group d-flex gap-2 align-items-center" style="display: ${this.options.showSearch ? 'flex' : 'none'} !important;">
                    <label class="form-label mb-0 text-muted small">جستجو:</label>
                    <input type="text" class="form-control form-control-sm table-search" 
                           placeholder="جستجو در جدول..." style="max-width: 250px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm clear-search" title="پاک کردن جستجو">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                
                <div class="table-actions d-flex gap-2">
                    <button type="button" class="btn btn-outline-success btn-sm export-excel" 
                            style="display: ${this.options.showExport ? 'inline-block' : 'none'};" title="دانلود Excel">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm print-table" 
                            style="display: ${this.options.showPrint ? 'inline-block' : 'none'};" title="چاپ جدول">
                        <i class="bi bi-printer me-1"></i>چاپ
                    </button>
                </div>
            </div>
        `;
        
        // Insert controls before table
        this.table.insertAdjacentHTML('beforebegin', controlsHtml);
        
        // Bind events
        this.bindControlEvents();
    }
    
    bindControlEvents() {
        // Search functionality
        const searchInput = document.querySelector('.table-search');
        const clearButton = document.querySelector('.clear-search');
        
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.filterTable(e.target.value));
        }
        
        if (clearButton) {
            clearButton.addEventListener('click', () => {
                searchInput.value = '';
                this.filterTable('');
            });
        }
        
        // Export and Print
        const exportBtn = document.querySelector('.export-excel');
        const printBtn = document.querySelector('.print-table');
        
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportToExcel());
        }
        
        if (printBtn) {
            printBtn.addEventListener('click', () => this.printTable());
        }
    }
    
    enableSorting() {
        const headers = this.table.querySelectorAll('thead th');
        
        headers.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.style.position = 'relative';
            header.title = 'کلیک کنید تا مرتب شود';
            
            // Add sort icon
            header.innerHTML += ' <i class="bi bi-arrow-down-up sort-icon ms-1 text-muted"></i>';
            
            header.addEventListener('click', () => this.sortTable(index));
        });
    }
    
    sortTable(columnIndex) {
        const tbody = this.table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const header = this.table.querySelectorAll('thead th')[columnIndex];
        const icon = header.querySelector('.sort-icon');
        
        // Clear other sort indicators
        this.table.querySelectorAll('.sort-icon').forEach(i => {
            i.className = 'bi bi-arrow-down-up sort-icon ms-1 text-muted';
        });
        
        const isAscending = !header.dataset.sortDirection || header.dataset.sortDirection === 'desc';
        header.dataset.sortDirection = isAscending ? 'asc' : 'desc';
        
        // Update icon
        icon.className = isAscending ? 
            'bi bi-sort-alpha-down sort-icon ms-1 text-primary' : 
            'bi bi-sort-alpha-up sort-icon ms-1 text-primary';
        
        rows.sort((a, b) => {
            const aVal = a.cells[columnIndex].textContent.trim();
            const bVal = b.cells[columnIndex].textContent.trim();
            
            // Try numeric comparison first
            const aNum = parseFloat(aVal.replace(/[^\d.-]/g, ''));
            const bNum = parseFloat(bVal.replace(/[^\d.-]/g, ''));
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAscending ? aNum - bNum : bNum - aNum;
            }
            
            // Fallback to string comparison
            return isAscending ? 
                aVal.localeCompare(bVal, 'fa') : 
                bVal.localeCompare(aVal, 'fa');
        });
        
        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
        
        // Add animation
        tbody.style.opacity = '0.7';
        setTimeout(() => tbody.style.opacity = '1', 150);
    }
    
    filterTable(searchTerm) {
        const tbody = this.table.querySelector('tbody');
        const rows = tbody.querySelectorAll('tr');
        const term = searchTerm.toLowerCase();
        
        let visibleCount = 0;
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const isVisible = text.includes(term);
            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });
        
        // Update search info
        this.updateSearchInfo(visibleCount, rows.length);
    }
    
    updateSearchInfo(visible, total) {
        let info = document.querySelector('.search-info');
        if (!info) {
            info = document.createElement('small');
            info.className = 'search-info text-muted';
            document.querySelector('.table-search-group').appendChild(info);
        }
        
        if (visible < total) {
            info.textContent = `نمایش ${visible} از ${total} ردیف`;
            info.style.display = 'inline';
        } else {
            info.style.display = 'none';
        }
    }
    
    exportToExcel() {
        // Create workbook
        const wb = XLSX.utils.book_new();
        
        // Get table data
        const tableData = this.getTableData();
        const ws = XLSX.utils.aoa_to_sheet(tableData);
        
        // Add to workbook
        XLSX.utils.book_append_sheet(wb, ws, 'گزارش');
        
        // Generate file name
        const fileName = `${this.options.printTitle}_${new Date().toLocaleDateString('fa-IR')}.xlsx`;
        
        // Save file
        XLSX.writeFile(wb, fileName);
    }
    
    getTableData() {
        const data = [];
        
        // Get headers
        const headers = Array.from(this.table.querySelectorAll('thead th')).map(th => 
            th.textContent.replace(/\s*\w+\s*$/, '').trim() // Remove sort icons
        );
        data.push(headers);
        
        // Get visible rows only
        const rows = this.table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const rowData = Array.from(row.cells).map(cell => {
                    // Extract text content, avoiding buttons and icons
                    const textNodes = Array.from(cell.childNodes)
                        .filter(node => node.nodeType === Node.TEXT_NODE || 
                               (node.nodeType === Node.ELEMENT_NODE && 
                                !node.matches('button, .btn, .badge, i, .bi')))
                        .map(node => node.textContent || node.innerText)
                        .join(' ');
                    return textNodes.trim();
                });
                data.push(rowData);
            }
        });
        
        return data;
    }
    
    printTable() {
        const printWindow = window.open('', '_blank');
        const tableHtml = this.generatePrintHtml();
        
        printWindow.document.write(tableHtml);
        printWindow.document.close();
        
        // Wait for content to load then print
        printWindow.onload = () => {
            printWindow.print();
            printWindow.close();
        };
    }
    
    generatePrintHtml() {
        const tableData = this.getTableData();
        const currentDate = new Date().toLocaleDateString('fa-IR');
        const currentTime = new Date().toLocaleTimeString('fa-IR');
        
        let tableRows = '';
        tableData.forEach((row, index) => {
            const cellTag = index === 0 ? 'th' : 'td';
            const rowClass = index === 0 ? 'header-row' : '';
            tableRows += `<tr class="${rowClass}">`;
            row.forEach(cell => {
                tableRows += `<${cellTag}>${cell}</${cellTag}>`;
            });
            tableRows += '</tr>';
        });
        
        return `
            <!DOCTYPE html>
            <html dir="rtl" lang="fa">
            <head>
                <meta charset="UTF-8">
                <title>${this.options.printTitle}</title>
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Vazir:wght@300;400;700&display=swap');
                    
                    * { box-sizing: border-box; }
                    
                    body {
                        font-family: 'Vazir', 'Tahoma', Arial, sans-serif;
                        margin: 0;
                        padding: 20px;
                        background: white;
                        color: #333;
                        direction: rtl;
                    }
                    
                    .print-header {
                        text-align: center;
                        margin-bottom: 30px;
                        border-bottom: 2px solid #333;
                        padding-bottom: 20px;
                    }
                    
                    .company-name {
                        font-size: 24px;
                        font-weight: bold;
                        color: #2c3e50;
                        margin-bottom: 10px;
                    }
                    
                    .report-title {
                        font-size: 18px;
                        color: #34495e;
                        margin-bottom: 15px;
                    }
                    
                    .print-info {
                        font-size: 12px;
                        color: #7f8c8d;
                        display: flex;
                        justify-content: space-between;
                    }
                    
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 20px 0;
                        font-size: 11px;
                    }
                    
                    th, td {
                        border: 1px solid #ddd;
                        padding: 8px 12px;
                        text-align: right;
                        vertical-align: middle;
                    }
                    
                    .header-row th {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        font-weight: bold;
                        text-align: center;
                    }
                    
                    tr:nth-child(even) {
                        background: #f8f9fa;
                    }
                    
                    .print-footer {
                        margin-top: 30px;
                        padding-top: 20px;
                        border-top: 1px solid #ddd;
                        text-align: center;
                        font-size: 10px;
                        color: #7f8c8d;
                    }
                    
                    @media print {
                        body { margin: 0; padding: 15px; }
                        .print-header { margin-bottom: 20px; }
                        table { font-size: 9px; }
                        th, td { padding: 6px 8px; }
                    }
                </style>
            </head>
            <body>
                <div class="print-header">
                    <div class="company-name">${this.options.companyName}</div>
                    <div class="report-title">${this.options.printTitle}</div>
                    <div class="print-info">
                        <span>تاریخ: ${currentDate}</span>
                        <span>ساعت: ${currentTime}</span>
                    </div>
                </div>
                
                <table>
                    ${tableRows}
                </table>
                
                <div class="print-footer">
                    <div>${this.options.developerName}</div>
                    <div>سیستم مدیریت انبار - نسخه حرفه‌ای</div>
                </div>
            </body>
            </html>
        `;
    }
    
    addResponsive() {
        // Add responsive wrapper if not exists
        if (!this.table.closest('.table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            this.table.parentNode.insertBefore(wrapper, this.table);
            wrapper.appendChild(this.table);
        }
        
        // Add Bootstrap table classes
        this.table.classList.add('table', 'table-hover', 'table-bordered');
    }
}

// Auto-initialize enhanced tables
document.addEventListener('DOMContentLoaded', function() {
    // Load XLSX library if not loaded
    if (typeof XLSX === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
        document.head.appendChild(script);
    }
    
    // Initialize all tables with .enhanced-table class
    document.querySelectorAll('.enhanced-table').forEach(table => {
        const options = {
            printTitle: table.dataset.printTitle || 'گزارش سیستم انبارداری',
            companyName: table.dataset.companyName || 'شرکت نمونه',
            showSearch: table.dataset.showSearch !== 'false',
            showExport: table.dataset.showExport !== 'false', 
            showPrint: table.dataset.showPrint !== 'false'
        };
        
        new EnhancedTable(`#${table.id}`, options);
    });
});

// Global function to manually initialize tables
window.initEnhancedTable = function(selector, options = {}) {
    return new EnhancedTable(selector, options);
};
