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
    let currentMonth = new Date();

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

        const shortMonthNames = [
            'ม.ค.', 'ก.พ.', 'มี.ค.',
            'เม.ย.', 'พ.ค.', 'มิ.ย.',
            'ก.ค.', 'ส.ค.', 'ก.ย.',
            'ต.ค.', 'พ.ย.', 'ธ.ค.'
        ];

        // Update month display (เป็นปี พ.ศ.)
        const thaiYear = month.getFullYear() + 543;
        const monthName = shortMonthNames[month.getMonth()];
        monthDisplay.textContent = `${monthName} ${thaiYear}`;

        // ในระบบจริงควรส่ง AJAX request เพื่อดึงข้อมูลเดือนที่เลือก
        const monthNumber = month.getMonth() + 1;
        const year = month.getFullYear();

        // เตรียมพารามิเตอร์สำหรับ AJAX request
        const studentId = getStudentId();
        const params = `student_id=${studentId}&month=${monthNumber}&year=${year}`;

        console.log(`จะดึงข้อมูล: ${monthNames[month.getMonth()]} ${thaiYear} (${monthNumber}/${year})`);

        // เมื่อมีการเปลี่ยนเดือน ให้โหลดข้อมูลใหม่
        window.location.href = `history.php?month=${monthNumber}&year=${year}`;
    }
}

/**
 * Get student ID from page (example implementation)
 * @returns {number} Student ID
 */
function getStudentId() {
    // ในระบบจริง อาจดึงจาก data attribute หรือตัวแปร global
    // หรือในกรณีนี้ใช้ตัวอย่างเป็น student_id = 1
    const studentIdElement = document.querySelector('[data-student-id]');
    return studentIdElement ? studentIdElement.dataset.studentId : 1;
}

/**
 * Initialize filter button functionality
 */
function initFilterButton() {
    const filterBtn = document.querySelector('.filter-button');
    const filterModal = document.getElementById('filterModal');
    const closeFilterBtn = document.querySelector('.close-filter');
    const applyFilterBtn = document.querySelector('.apply-btn');
    const resetFilterBtn = document.querySelector('.reset-btn');

    // Show filter modal
    if (filterBtn) {
        filterBtn.addEventListener('click', () => {
            filterModal.style.display = 'block';
        });
    }

    // Close filter modal when clicking close button
    if (closeFilterBtn) {
        closeFilterBtn.addEventListener('click', () => {
            filterModal.style.display = 'none';
        });
    }

    // Close filter modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === filterModal) {
            filterModal.style.display = 'none';
        }
    });

    // Apply filter
    if (applyFilterBtn) {
        applyFilterBtn.addEventListener('click', () => {
            applyFilter();
        });
    }

    // Reset filter
    if (resetFilterBtn) {
        resetFilterBtn.addEventListener('click', () => {
            resetFilter();
        });
    }
}

/**
 * Apply filter to history list
 */
function applyFilter() {
    // Get selected filter values
    const status = document.querySelector('input[name="status"]:checked').value;
    const method = document.querySelector('input[name="method"]:checked').value;
    const period = document.querySelector('input[name="period"]:checked').value;

    // Get current URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const month = urlParams.get('month') || '';
    const year = urlParams.get('year') || '';
    
    // Build query string with all parameters
    let queryString = '';
    if (status !== 'all') queryString += `&status=${status}`;
    if (method !== 'all') queryString += `&method=${method}`;
    if (period !== 'all') queryString += `&period=${period}`;
    if (month) queryString += `&month=${month}`;
    if (year) queryString += `&year=${year}`;
    
    // Remove leading & if present
    if (queryString.startsWith('&')) {
        queryString = queryString.substring(1);
    }

    console.log(`กำลังกรองข้อมูล: สถานะ=${status}, วิธีการ=${method}, ช่วงเวลา=${period}`);

    // Navigate to filtered view
    window.location.href = queryString ? `history.php?${queryString}` : 'history.php';

    // Close filter modal
    document.getElementById('filterModal').style.display = 'none';
}

/**
 * Reset filter to default values
 */
function resetFilter() {
    document.querySelector('input[name="status"][value="all"]').checked = true;
    document.querySelector('input[name="method"][value="all"]').checked = true;
    document.querySelector('input[name="period"][value="all"]').checked = true;
}

/**
 * Navigation function for going back
 */
function goBack() {
    window.history.back();
}