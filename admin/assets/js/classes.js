/**
 * classes.js - JavaScript for Classroom Management
 * 
 * Handles interactions and dynamic functionality for the classroom management page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize classroom management functionality
    initClassManagement();
});

/**
 * Initialize classroom management page functionality
 */
function initClassManagement() {
    // Setup event listeners
    setupEventListeners();
    
    // Initialize filter functionality
    initializeFilters();
    
    // Setup modal interactions
    setupModalInteractions();
}

/**
 * Setup general event listeners
 */
function setupEventListeners() {
    // Add Class Button
    const addClassBtn = document.querySelector('[data-action="add-class"]');
    if (addClassBtn) {
        addClassBtn.addEventListener('click', showAddClassModal);
    }
    
    // Export buttons
    const exportButtons = document.querySelectorAll('[data-export]');
    exportButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const exportType = this.getAttribute('data-export');
            exportClassData(exportType);
        });
    });
}

/**
 * Initialize filtering functionality
 */
function initializeFilters() {
    const filterButton = document.querySelector('.filter-button');
    if (filterButton) {
        filterButton.addEventListener('click', applyClassFilter);
    }
}

/**
 * Apply filters to class list
 */
function applyClassFilter() {
    // Collect filter values
    const levelFilter = document.querySelector('select[name="level"]').value;
    const advisorFilter = document.querySelector('select[name="advisor"]').value;
    
    // Get all class rows
    const classRows = document.querySelectorAll('.class-row');
    
    classRows.forEach(row => {
        const level = row.getAttribute('data-level');
        const advisor = row.getAttribute('data-advisor');
        
        // Determine visibility based on filters
        const levelMatch = !levelFilter || level === levelFilter;
        const advisorMatch = !advisorFilter || advisor === advisorFilter;
        
        // Toggle row visibility
        row.style.display = (levelMatch && advisorMatch) ? '' : 'none';
    });
    
    // Update results count
    updateFilteredResultsCount();
}

/**
 * Update count of filtered results
 */
function updateFilteredResultsCount() {
    const visibleRows = document.querySelectorAll('.class-row:not([style*="display: none"])');
    const countElement = document.getElementById('filtered-results-count');
    
    if (countElement) {
        countElement.textContent = visibleRows.length;
    }
}

/**
 * Setup modal interactions
 */
function setupModalInteractions() {
    // Close modal buttons
    const closeModalButtons = document.querySelectorAll('[data-dismiss="modal"]');
    closeModalButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const modalId = this.closest('.modal').id;
            closeModal(modalId);
        });
    });
}

/**
 * Show Add Class Modal
 */
function showAddClassModal() {
    const modal = document.getElementById('addClassModal');
    if (modal) {
        // Reset form
        modal.querySelector('form').reset();
        
        // Show modal
        modal.style.display = 'flex';
    }
}

/**
 * Save new class
 */
function saveNewClass() {
    // Collect form data
    const form = document.getElementById('addClassForm');
    const formData = new FormData(form);
    
    // Validate form
    if (!validateClassForm(formData)) {
        return;
    }
    
    // Prepare data for submission
    const classData = {
        level: formData.get('level'),
        room: formData.get('room'),
        advisor: formData.get('advisor')
    };
    
    // Send data to server (simulated)
    sendClassData(classData);
}

/**
 * Validate class form data
 * @param {FormData} formData - Form data to validate
 * @returns {boolean} - Validation result
 */
function validateClassForm(formData) {
    const level = formData.get('level');
    const room = formData.get('room');
    const advisor = formData.get('advisor');
    
    // Basic validation
    if (!level) {
        showAlert('กรุณาเลือกระดับชั้น', 'warning');
        return false;
    }
    
    if (!room) {
        showAlert('กรุณากรอกห้องเรียน', 'warning');
        return false;
    }
    
    if (!advisor) {
        showAlert('กรุณาเลือกครูที่ปรึกษา', 'warning');
        return false;
    }
    
    return true;
}

/**
 * Send class data to server
 * @param {Object} classData - Class data to send
 */
function sendClassData(classData) {
    // In a real application, this would be an AJAX call
    console.log('Sending class data:', classData);
    
    // Simulate server response
    setTimeout(() => {
        // Close modal
        closeModal('addClassModal');
        
        // Show success message
        showAlert('เพิ่มชั้นเรียนสำเร็จ', 'success');
        
        // Optionally refresh the class list
        refreshClassList();
    }, 500);
}

/**
 * Refresh class list after adding/editing
 */
function refreshClassList() {
    // In a real app, this would fetch updated data from the server
    location.reload();
}

/**
 * Show class details modal
 * @param {string} classId - ID of the class to show
 */
function showClassDetails(classId) {
    const modal = document.getElementById('classDetailsModal');
    if (modal) {
        // Fetch class details (simulated)
        const classDetails = fetchClassDetails(classId);
        
        // Update modal content
        updateClassDetailsModal(classDetails);
        
        // Show modal
        modal.style.display = 'flex';
    }
}

/**
 * Fetch class details
 * @param {string} classId - ID of the class
 * @returns {Object} Class details
 */
function fetchClassDetails(classId) {
    // In a real application, this would be an AJAX call
    return {
        level: 'ม.6',
        room: '1',
        advisor: 'อาจารย์ใจดี มากเมตตา',
        totalStudents: 35,
        maleStudents: 20,
        femaleStudents: 15,
        students: [
            { 
                id: 1, 
                name: 'นายอภิสิทธิ์ สงวนสิทธิ์', 
                attendanceRate: 95,
                status: 'ปกติ'
            },
            { 
                id: 2, 
                name: 'นายธนกฤต สุขใจ', 
                attendanceRate: 65,
                status: 'เสี่ยงตกกิจกรรม'
            }
        ]
    };
}

/**
 * Update class details modal content
 * @param {Object} classDetails - Details of the class
 */
function updateClassDetailsModal(classDetails) {
    const modal = document.getElementById('classDetailsModal');
    if (!modal) return;
    
    // Update basic info
    modal.querySelector('.class-level').textContent = `${classDetails.level}/${classDetails.room}`;
    modal.querySelector('.advisor-name').textContent = classDetails.advisor;
    modal.querySelector('.total-students').textContent = classDetails.totalStudents;
    modal.querySelector('.male-students').textContent = classDetails.maleStudents;
    modal.querySelector('.female-students').textContent = classDetails.femaleStudents;
    
    // Update students table
    const studentsTableBody = modal.querySelector('.students-table tbody');
    if (studentsTableBody) {
        studentsTableBody.innerHTML = ''; // Clear existing rows
        
        classDetails.students.forEach((student, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${student.name}</td>
                <td>${student.attendanceRate}/40 วัน (${student.attendanceRate}%)</td>
                <td>
                    <span class="status-badge ${
                        student.status === 'เสี่ยงตกกิจกรรม' ? 'danger' : 
                        (student.status === 'ต้องระวัง' ? 'warning' : 'success')
                    }">
                        ${student.status}
                    </span>
                </td>
            `;
            studentsTableBody.appendChild(row);
        });
    }
}

/**
 * Export class data
 * @param {string} exportType - Type of export (csv, excel, pdf)
 */
function exportClassData(exportType) {
    // Collect current filter values
    const levelFilter = document.querySelector('select[name="level"]').value;
    const advisorFilter = document.querySelector('select[name="advisor"]').value;
    
    // In a real application, this would trigger a server-side export
    console.log(`Exporting class data: Type=${exportType}, Level=${levelFilter}, Advisor=${advisorFilter}`);
    
    showAlert(`กำลังส่งออกข้อมูลชั้นเรียน (${exportType.toUpperCase()})`, 'info');
}

/**
 * Show alert message
 * @param {string} message - Alert message
 * @param {string} type - Alert type (success, warning, info, danger)
 */
function showAlert(message, type = 'info') {
    // Create alert container if it doesn't exist
    let alertContainer = document.querySelector('.alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container';
        document.body.appendChild(alertContainer);
    }
    
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <div class="alert-content">${message}</div>
        <button class="alert-close">&times;</button>
    `;
    
    // Add to container
    alertContainer.appendChild(alert);
    
    // Setup close button
    const closeButton = alert.querySelector('.alert-close');
    closeButton.addEventListener('click', () => {
        alert.classList.add('alert-closing');
        setTimeout(() => {
            alertContainer.removeChild(alert);
        }, 300);
    });
    
    // Auto-close after 5 seconds
    setTimeout(() => {
        if (alertContainer.contains(alert)) {
            alert.classList.add('alert-closing');
            setTimeout(() => {
                alertContainer.removeChild(alert);
            }, 300);
        }
    }, 5000);
}

/**
 * Close a modal
 * @param {string} modalId - ID of the modal to close
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * Delete a class
 * @param {string} classId - ID of the class to delete
 */
function deleteClass(classId) {
    if (confirm('คุณต้องการลบชั้นเรียนนี้ใช่หรือไม่?')) {
        // In a real application, this would be an AJAX call
        console.log(`Deleting class: ${classId}`);
        
        // Simulate server response
        setTimeout(() => {
            showAlert('ลบชั้นเรียนสำเร็จ', 'success');
            refreshClassList();
        }, 500);
    }
}

/**
 * Edit a class
 * @param {string} classId - ID of the class to edit
 */
function editClass(classId) {
    const modal = document.getElementById('editClassModal');
    if (modal) {
        // Fetch current class data
        const classData = fetchClassDetails(classId);
        
        // Populate edit form
        modal.querySelector('select[name="level"]').value = classData.level;
        modal.querySelector('input[name="room"]').value = classData.room;
        modal.querySelector('select[name="advisor"]').value = classData.advisor;
        
        // Show modal
        modal.style.display = 'flex';
    }
}

/**
 * Save edited class
 */
function saveEditedClass() {
    const modal = document.getElementById('editClassModal');
    const form = modal.querySelector('form');
    const formData = new FormData(form);
    
    // Validate form
    if (!validateClassForm(formData)) {
        return;
    }
    
    // Prepare data for submission
    const classData = {
        level: formData.get('level'),
        room: formData.get('room'),
        advisor: formData.get('advisor')
    };
    
    // Send data to server (simulated)
    sendEditedClassData(classData);
}

/**
 * Send edited class data to server
 * @param {Object} classData - Edited class data
 */
function sendEditedClassData(classData) {
    // In a real application, this would be an AJAX call
    console.log('Sending edited class data:', classData);
    
    // Simulate server response
    setTimeout(() => {
        // Close modal
        closeModal('editClassModal');
        
        // Show success message
        showAlert('แก้ไขชั้นเรียนสำเร็จ', 'success');
        
        // Refresh class list
        refreshClassList();
    }, 500);
}