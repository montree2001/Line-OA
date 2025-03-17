/**
 * student-report.js - JavaScript for Student Attendance Report Page
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initialize tab switching functionality
    initTabSwitching();

    // Initialize calendar navigation
    initCalendarNavigation();

    // Initialize filter button
    initFilterButton();
});

/**
 * Initialize tab switching functionality
 */
function initTabSwitching() {
    const tabItems = document.querySelectorAll('.tab-item');
    
    tabItems.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active class from all tabs
            tabItems.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            tab.classList.add('active');
            
            // Switch tab content
            const tabName = tab.textContent.trim().toLowerCase();
            switchTab(tabName === 'ปฏิทิน' ? 'calendar' : 'history');
        });
    });
}

/**
 * Switch between calendar and history tabs
 * @param {string} tabName - Name of the tab to switch to ('calendar' or 'history')
 */
function switchTab(tabName) {
    const calendarTab = document.getElementById('calendar-tab');
    const historyTab = document.getElementById('history-tab');

    if (tabName === 'calendar') {
        calendarTab.style.display = 'block';
        historyTab.style.display = 'none';
    } else {
        calendarTab.style.display = 'none';
        historyTab.style.display = 'block';
    }
}

/**
 * Initialize calendar navigation buttons
 */
function initCalendarNavigation() {
    const prevMonthBtn = document.querySelector('.nav-button:first-child');
    const nextMonthBtn = document.querySelector('.nav-button:last-child');
    const monthDisplay = document.querySelector('.calendar-month');

    // Current month state
    let currentMonth = new Date(2025, 2, 1); // March 2025

    prevMonthBtn.addEventListener('click', () => {
        currentMonth.setMonth(currentMonth.getMonth() - 1);
        updateCalendarView(currentMonth);
    });

    nextMonthBtn.addEventListener('click', () => {
        currentMonth.setMonth(currentMonth.getMonth() + 1);
        updateCalendarView(currentMonth);
    });

    /**
     * Update calendar view based on selected month
     * @param {Date} month - The month to display
     */
    function updateCalendarView(month) {
        const monthNames = [
            'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 
            'เมษายน', 'พฤษภาคม', 'มิถุนายน', 
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 
            'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
        ];

        // Update month display
        monthDisplay.textContent = `${monthNames[month.getMonth()]} ${month.getFullYear() + 543}`;

        // TODO: In a real application, you would fetch and render 
        // actual attendance data for the selected month
        console.log(`Fetching data for ${monthNames[month.getMonth()]} ${month.getFullYear()}`);
    }
}

/**
 * Initialize filter button functionality
 */
function initFilterButton() {
    const filterBtn = document.querySelector('.filter-button');
    
    filterBtn.addEventListener('click', () => {
        // Create filter modal or dropdown
        const filterOptions = [
            { label: 'ทั้งหมด', value: 'all' },
            { label: 'เข้าแถว', value: 'present' },
            { label: 'ขาดแถว', value: 'absent' },
            { label: 'วิธีการเช็คชื่อ', value: 'method' }
        ];

        // In a real app, you might use a more sophisticated modal or dropdown
        const selectedFilter = confirm(
            'เลือกตัวกรอง:\n' + 
            filterOptions.map((opt, index) => `${index + 1}. ${opt.label}`).join('\n')
        );

        if (selectedFilter) {
            // TODO: Implement actual filtering logic
            alert('กำลังกรองข้อมูล');
        }
    });
}

/**
 * Export report functionality
 */
function exportReport() {
    // In a real application, this would generate a PDF or Excel file
    alert('กำลังสร้างรายงาน...');
}

// Back navigation
function goBack() {
    window.history.back();
}