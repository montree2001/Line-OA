/**
 * test_attendance.js - JavaScript for test attendance page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize form validation
    const form = document.querySelector('form');
    
    if (form) {
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            // Validate class selection
            const classIdField = document.getElementById('class_id');
            if (classIdField && classIdField.value === '') {
                isValid = false;
                classIdField.classList.add('is-invalid');
                
                // Add invalid feedback if not exists
                if (!classIdField.nextElementSibling || !classIdField.nextElementSibling.classList.contains('invalid-feedback')) {
                    const feedback = document.createElement('div');
                    feedback.classList.add('invalid-feedback');
                    feedback.textContent = 'กรุณาเลือกชั้นเรียน';
                    classIdField.parentNode.appendChild(feedback);
                }
            } else if (classIdField) {
                classIdField.classList.remove('is-invalid');
            }
            
            // Validate date selection
            const dateField = document.getElementById('date');
            if (dateField && dateField.value === '') {
                isValid = false;
                dateField.classList.add('is-invalid');
                
                // Add invalid feedback if not exists
                if (!dateField.nextElementSibling || !dateField.nextElementSibling.classList.contains('invalid-feedback')) {
                    const feedback = document.createElement('div');
                    feedback.classList.add('invalid-feedback');
                    feedback.textContent = 'กรุณาเลือกวันที่';
                    dateField.parentNode.appendChild(feedback);
                }
            } else if (dateField) {
                dateField.classList.remove('is-invalid');
            }
            
            // Validate check method
            const checkMethodField = document.getElementById('check_method');
            if (checkMethodField && checkMethodField.value === '') {
                isValid = false;
                checkMethodField.classList.add('is-invalid');
                
                // Add invalid feedback if not exists
                if (!checkMethodField.nextElementSibling || !checkMethodField.nextElementSibling.classList.contains('invalid-feedback')) {
                    const feedback = document.createElement('div');
                    feedback.classList.add('invalid-feedback');
                    feedback.textContent = 'กรุณาเลือกวิธีการเช็คชื่อ';
                    checkMethodField.parentNode.appendChild(feedback);
                }
            } else if (checkMethodField) {
                checkMethodField.classList.remove('is-invalid');
            }
            
            // Validate percentage
            const percentageField = document.getElementById('percentage');
            if (percentageField) {
                const percentage = parseInt(percentageField.value);
                if (isNaN(percentage) || percentage < 0 || percentage > 100) {
                    isValid = false;
                    percentageField.classList.add('is-invalid');
                    
                    // Add invalid feedback if not exists
                    if (!percentageField.nextElementSibling || !percentageField.nextElementSibling.classList.contains('invalid-feedback')) {
                        const feedback = document.createElement('div');
                        feedback.classList.add('invalid-feedback');
                        feedback.textContent = 'กรุณาระบุเปอร์เซ็นต์ระหว่าง 0-100';
                        percentageField.parentNode.appendChild(feedback);
                    }
                } else {
                    percentageField.classList.remove('is-invalid');
                }
            }
            
            // Prevent form submission if not valid
            if (!isValid) {
                event.preventDefault();
            } else {
                // Show loading spinner or overlay
                showLoading();
            }
        });
    }
    
    // Add event listeners to remove validation styling on input
    const formFields = document.querySelectorAll('.form-control');
    formFields.forEach(field => {
        field.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    });
    
    // Initialize date picker if available
    if (typeof $.fn.datepicker !== 'undefined') {
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            language: 'th',
            todayHighlight: true,
            autoclose: true
        });
    }
    
    // Initialize tooltips
    if (typeof $.fn.tooltip !== 'undefined') {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-warning)');
    alerts.forEach(alert => {
        setTimeout(() => {
            $(alert).fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    });
    
    // Function to show loading overlay
    function showLoading() {
        // Create loading overlay if it doesn't exist
        if (!document.getElementById('loading-overlay')) {
            const overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">กำลังโหลด...</span>
                </div>
                <p class="mt-2">กำลังดำเนินการ...</p>
            `;
            document.body.appendChild(overlay);
            
            // Add style for the overlay
            const style = document.createElement('style');
            style.textContent = `
                #loading-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    z-index: 9999;
                    color: white;
                }
            `;
            document.head.appendChild(style);
        } else {
            document.getElementById('loading-overlay').style.display = 'flex';
        }
    }
    
    // Function to hide loading overlay
    function hideLoading() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }
    
    // Expose functions globally
    window.showLoading = showLoading;
    window.hideLoading = hideLoading;
});

// Format Thai date (DD/MM/YYYY)
function formatThaiDate(dateStr) {
    const date = new Date(dateStr);
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear() + 543; // Convert to Buddhist era
    return `${day}/${month}/${year}`;
}

// Generate random time between 7:30 - 8:00
function generateRandomTime() {
    const minutes = Math.floor(Math.random() * 30) + 30; // 30-59
    const seconds = Math.floor(Math.random() * 60); // 0-59
    return `07:${minutes}:${seconds.toString().padStart(2, '0')}`;
}

// Generate random GPS location near school
function generateRandomLocation(schoolLat, schoolLng, radius = 100) {
    // Convert radius from meters to degrees (approximate)
    const radiusInDegrees = radius / 111000; // 111000 meters = 1 degree
    
    // Random angle
    const angle = Math.random() * 2 * Math.PI;
    
    // Random distance within radius
    const distance = Math.random() * radiusInDegrees;
    
    // Calculate offset
    const latOffset = distance * Math.cos(angle);
    const lngOffset = distance * Math.sin(angle);
    
    return {
        lat: schoolLat + latOffset,
        lng: schoolLng + lngOffset
    };
}