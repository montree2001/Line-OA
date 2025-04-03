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

        // ในสภาพแวดล้อมจริง ควรส่ง AJAX request ที่นี่
        // fetchMonthlyAttendance(params);
    }
}

/**
 * Get student ID from page (example implementation)
 * @returns {number} Student ID
 */
function getStudentId() {
    // ในระบบจริง อาจดึงจาก data attribute หรือตัวแปร global
    // หรือในกรณีนี้ใช้ตัวอย่างเป็น student_id = 1
    return 1;
}

/**
 * Fetch monthly attendance data (example implementation)
 * @param {string} params - Query parameters for AJAX request
 */
function fetchMonthlyAttendance(params) {
    // AJAX request example
    fetch(`api/get_attendance.php?${params}`)
        .then(response => response.json())
        .then(data => {
            updateCalendarData(data.calendar_dates);
            updateSummaryData(data.monthly_summary);
        })
        .catch(error => {
            console.error('Error fetching attendance data:', error);
        });
}

/**
 * Update calendar with new data (example implementation)
 * @param {Array} calendarDates - Calendar dates data
 */
function updateCalendarData(calendarDates) {
    const calendarDatesContainer = document.querySelector('.calendar-dates');

    // Clear existing dates
    calendarDatesContainer.innerHTML = '';

    // Add new dates
    calendarDates.forEach(date => {
        const dateCell = document.createElement('div');
        dateCell.className = `date-cell ${date.status} ${date.is_today ? 'today' : ''}`;
        dateCell.textContent = date.day;

        // Add status indicator if needed
        if (date.status === 'present' || date.status === 'absent') {
            const statusDot = document.createElement('div');
            statusDot.className = `status-dot ${date.status}`;
            dateCell.appendChild(statusDot);
        }

        calendarDatesContainer.appendChild(dateCell);
    });
}

/**
 * Update summary data (example implementation)
 * @param {Object} summary - Monthly summary data
 */
function updateSummaryData(summary) {
    // Update total days
    document.querySelector('.stat-value:nth-child(1)').textContent = summary.total_days;

    // Update absent days
    const absentElement = document.querySelector('.stat-value:nth-child(3)');
    absentElement.textContent = summary.absent_days;
    absentElement.className = `stat-value ${summary.absent_days > 0 ? 'warning' : ''}`;

    // Update attendance percentage
    const percentageElement = document.querySelector('.stat-value:nth-child(5)');
    percentageElement.textContent = `${summary.attendance_percentage}%`;

    let statusClass = '';
    if (summary.attendance_percentage >= 90) {
        statusClass = 'good';
    } else if (summary.attendance_percentage >= 80) {
        statusClass = 'warning';
    } else {
        statusClass = 'danger';
    }
    percentageElement.className = `stat-value ${statusClass}`;

    // Update regularity score
    const progressFill = document.querySelector('.progress-fill');
    progressFill.style.width = `${summary.regularity_score}%`;

    // Update regularity text
    const regularityText = document.querySelector('.progress-label span:last-child');
    let regularityStatus = 'พอใช้';
    if (summary.regularity_score >= 90) {
        regularityStatus = 'ดีเยี่ยม';
    } else if (summary.regularity_score >= 80) {
        regularityStatus = 'ดี';
    } else if (summary.regularity_score >= 70) {
        regularityStatus = 'พอใช้';
    } else {
        regularityStatus = 'ต้องปรับปรุง';
    }
    regularityText.textContent = regularityStatus;

    // Show/hide risk alert
    const riskAlert = document.querySelector('.risk-alert');
    if (riskAlert) {
        if (summary.is_at_risk) {
            riskAlert.style.display = 'flex';
        } else {
            riskAlert.style.display = 'none';
        }
    }
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
    filterBtn.addEventListener('click', () => {
        filterModal.style.display = 'block';
    });

    // Close filter modal when clicking close button
    closeFilterBtn.addEventListener('click', () => {
        filterModal.style.display = 'none';
    });

    // Close filter modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === filterModal) {
            filterModal.style.display = 'none';
        }
    });

    // Apply filter
    applyFilterBtn.addEventListener('click', () => {
        applyFilter();
    });

    // Reset filter
    resetFilterBtn.addEventListener('click', () => {
        resetFilter();
    });
}

/**
 * Apply filter to history list
 */
function applyFilter() {
    // Get selected filter values
    const status = document.querySelector('input[name="status"]:checked').value;
    const method = document.querySelector('input[name="method"]:checked').value;
    const period = document.querySelector('input[name="period"]:checked').value;

    // In a real application, send AJAX request with filter parameters
    const studentId = getStudentId();
    const params = `student_id=${studentId}&status=${status}&method=${method}&period=${period}`;

    console.log(`กำลังกรองข้อมูล: สถานะ=${status}, วิธีการ=${method}, ช่วงเวลา=${period}`);

    // In a real application, fetch filtered data and update the history list
    // fetchFilteredHistory(params);

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
 * Fetch filtered history data (example implementation)
 * @param {string} params - Query parameters for AJAX request
 */
function fetchFilteredHistory(params) {
    // AJAX request example
    fetch(`api/get_history.php?${params}`)
        .then(response => response.json())
        .then(data => {
            updateHistoryList(data.history);
        })
        .catch(error => {
            console.error('Error fetching history data:', error);
        });
}

/**
 * Update history list with new data (example implementation)
 * @param {Array} historyData - History data
 */
function updateHistoryList(historyData) {
    const historyList = document.querySelector('.history-list');

    // Clear existing history items
    historyList.innerHTML = '';

    if (historyData.length === 0) {
        // Show no data message
        const noHistory = document.createElement('div');
        noHistory.className = 'no-history';
        noHistory.innerHTML = `
            <div class="no-data-icon">
                <span class="material-icons">event_busy</span>
            </div>
            <div class="no-data-message">ไม่พบประวัติการเช็คชื่อที่ตรงกับเงื่อนไข</div>
        `;
        historyList.appendChild(noHistory);
        return;
    }

    // Add new history items
    historyData.forEach(entry => {
        const historyItem = document.createElement('div');
        historyItem.className = 'history-item';

        // Get date parts
        const day = entry.date.substring(0, 2);
        const monthYear = entry.date.substring(3);

        historyItem.innerHTML = `
            <div class="history-date">
                <div class="history-day">${day}</div>
                <div class="history-month">${monthYear}</div>
            </div>
            <div class="history-details">
                <div class="history-status">
                    <div class="status-indicator ${entry.status}"></div>
                    <div class="status-text ${entry.status}">
                        ${entry.status === 'present' ? 'มาเรียน' : 'ขาดเรียน'}
                    </div>
                </div>
                <div class="history-time">เช็คชื่อเวลา ${entry.time} น.</div>
                <div class="history-method">
                    <span class="material-icons">
                        ${getMethodIcon(entry.method)}
                    </span>
                    เช็คชื่อด้วย ${entry.method}
                </div>
            </div>
        `;

        historyList.appendChild(historyItem);
    });
}

/**
 * Get method icon based on method name
 * @param {string} method - Check-in method
 * @returns {string} - Icon name
 */
function getMethodIcon(method) {
    switch (method) {
        case 'GPS':
            return 'gps_fixed';
        case 'PIN':
            return 'pin';
        case 'QR Code':
            return 'qr_code_scanner';
        default:
            return 'check_circle';
    }
}

/**
 * Navigation function for going back
 */
function goBack() {
    window.history.back();
}