/**
 * main.js - JavaScript หลักสำหรับระบบ STUDENT-Prasat
 * 
 * ไฟล์นี้มีฟังก์ชันทั่วไปสำหรับใช้งานในทุกหน้าของระบบ
 */

// เมื่อโหลด DOM เสร็จแล้ว
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่า sidebar และเมนูบนมือถือ
    initSidebar();
    
    // ตั้งค่า dropdown ของผู้ดูแลระบบ
    initAdminDropdown();
    
    // ตั้งค่า modal ต่างๆ
    initModals();
    
    // ตั้งค่าระบบแจ้งเตือน
    initAlerts();
    
    // ตั้งค่าการส่งฟอร์ม
    initForms();
    
    // กำหนดค่า data-page สำหรับการใช้งานในฟังก์ชันอื่นๆ
    const currentPage = document.querySelector('.menu-item.active');
    if (currentPage) {
        const pageId = currentPage.getAttribute('href').replace('.php', '').replace('/', '');
        document.body.setAttribute('data-page', pageId);
    }
});

/**
 * ตั้งค่า sidebar และเมนูบนมือถือ
 */
function initSidebar() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const menuItems = document.querySelectorAll('.menu-item');
    
    if (menuToggle && sidebar && overlay) {
        // Toggle sidebar on mobile
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.classList.toggle('sidebar-open');
        });
        
        // Close sidebar when clicking overlay
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        });
        
        // Close sidebar when clicking a menu item on mobile
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.classList.remove('sidebar-open');
            }
        });
    }
}

/**
 * ตั้งค่า dropdown ของผู้ดูแลระบบ
 */
function initAdminDropdown() {
    const adminMenuToggle = document.getElementById('adminMenuToggle');
    const adminDropdown = document.getElementById('adminDropdown');
    
    if (adminMenuToggle && adminDropdown) {
        // Toggle admin dropdown
        adminMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            adminDropdown.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            adminDropdown.classList.remove('active');
        });
        
        // Prevent dropdown from closing when clicking inside
        adminDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
}

/**
 * ตั้งค่า modal ต่างๆ
 */
function initModals() {
    // Close modal when clicking outside
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });
    
    // Close with ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            modals.forEach(modal => {
                if (modal.classList.contains('active')) {
                    closeModal(modal.id);
                }
            });
        }
    });
}

/**
 * แสดง modal
 * 
 * @param {string} modalId - ID ของ modal ที่ต้องการแสดง
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // ป้องกันการเลื่อนหน้าเว็บ
    }
}

/**
 * ซ่อน modal
 * 
 * @param {string} modalId - ID ของ modal ที่ต้องการซ่อน
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * ตั้งค่าระบบแจ้งเตือน
 */
function initAlerts() {
    // สร้าง container สำหรับ alert
    const alertContainer = document.createElement('div');
    alertContainer.className = 'alert-container';
    document.body.appendChild(alertContainer);
    
    // ปิด alert ที่มีอยู่แล้ว
    const alertCloseButtons = document.querySelectorAll('.alert-close');
    alertCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const alert = this.closest('.alert');
            alert.classList.add('alert-closing');
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 300);
        });
    });
}

/**
 * แสดงข้อความแจ้งเตือน
 * 
 * @param {string} message - ข้อความที่ต้องการแสดง
 * @param {string} type - ประเภทของการแจ้งเตือน (success, info, warning, danger)
 */
function showAlert(message, type = 'info') {
    // สร้าง alert container ถ้ายังไม่มี
    let alertContainer = document.querySelector('.alert-container');
    
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container';
        document.body.appendChild(alertContainer);
    }
    
    // สร้าง alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <div class="alert-content">${message}</div>
        <button class="alert-close">&times;</button>
    `;
    
    // เพิ่ม alert ไปยัง container
    alertContainer.appendChild(alert);
    
    // ปุ่มปิด alert
    const closeButton = alert.querySelector('.alert-close');
    closeButton.addEventListener('click', function() {
        alert.classList.add('alert-closing');
        setTimeout(() => {
            alertContainer.removeChild(alert);
        }, 300);
    });
    
    // ให้ alert ปิดโดยอัตโนมัติหลังจาก 5 วินาที
    setTimeout(() => {
        if (alertContainer.contains(alert)) {
            alert.classList.add('alert-closing');
            setTimeout(() => {
                if (alertContainer.contains(alert)) {
                    alertContainer.removeChild(alert);
                }
            }, 300);
        }
    }, 5000);
}

/**
 * ตั้งค่าการส่งฟอร์ม
 */
function initForms() {
    // ดักการส่งฟอร์มและใช้ AJAX แทน
    const forms = document.querySelectorAll('form[data-ajax="true"]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const url = this.getAttribute('action') || window.location.href;
            const method = this.getAttribute('method') || 'POST';
            
            // ปิดปุ่มส่งฟอร์มระหว่างประมวลผล
            const submitButton = this.querySelector('[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner"></span> กำลังประมวลผล...';
            }
            
            // ส่งข้อมูลผ่าน AJAX
            fetch(url, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // แสดงข้อความสำเร็จ
                    showAlert(data.message || 'ดำเนินการเรียบร้อยแล้ว', 'success');
                    
                    // รีเซ็ตฟอร์มหากต้องการ
                    if (data.reset) {
                        form.reset();
                    }
                    
                    // รีโหลดหน้าหากต้องการ
                    if (data.reload) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                    
                    // เปลี่ยนหน้าหากต้องการ
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    }
                } else {
                    // แสดงข้อความผิดพลาด
                    showAlert(data.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 'danger');
                }
                
                // คืนค่าปุ่มส่งฟอร์ม
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = submitButton.getAttribute('data-original-text') || 'บันทึก';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง', 'danger');
                
                // คืนค่าปุ่มส่งฟอร์ม
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = submitButton.getAttribute('data-original-text') || 'บันทึก';
                }
            });
        });
        
        // เก็บข้อความเดิมของปุ่มส่งฟอร์ม
        const submitButton = form.querySelector('[type="submit"]');
        if (submitButton) {
            submitButton.setAttribute('data-original-text', submitButton.innerHTML);
        }
    });
}

/**
 * ตั้งค่าแท็บต่างๆ ในหน้า
 * 
 * @param {string} containerSelector - CSS selector ของ container ที่มีแท็บ
 */
function initTabs(containerSelector = '.tabs-container') {
    const tabsContainers = document.querySelectorAll(containerSelector);
    
    tabsContainers.forEach(container => {
        const tabs = container.querySelectorAll('.tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                showTab(tabId, container);
            });
        });
    });
}

/**
 * แสดงแท็บที่ต้องการและซ่อนแท็บอื่นๆ
 * 
 * @param {string} tabId - ID ของแท็บที่ต้องการแสดง
 * @param {Element} container - Element ของ container ที่มีแท็บ
 */
function showTab(tabId, container = document) {
    // ซ่อนแท็บทั้งหมด
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(tab => {
        tab.classList.remove('active');
    });
    
    // ยกเลิกการเลือกแท็บทั้งหมด
    const tabs = container.querySelectorAll('.tab');
    tabs.forEach(tab => {
        tab.classList.remove('active');
    });
    
    // แสดงแท็บที่ต้องการและเลือกแท็บนั้น
    const tabContent = document.getElementById(tabId + '-tab');
    const selectedTab = container.querySelector(`.tab[data-tab="${tabId}"]`);
    
    if (tabContent) {
        tabContent.classList.add('active');
    }
    
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
}

// Function to toggle sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    
    if (sidebar && overlay) {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        
        // Prevent body scrolling when sidebar is open
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    }
}

// Event listener for menu toggle button
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', toggleSidebar);
    }

    // Close sidebar when clicking overlay
    const overlay = document.getElementById('overlay');
    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }
    
    // Close sidebar when clicking a menu item on mobile
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                toggleSidebar();
            }
        });
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        
        if (window.innerWidth > 992 && sidebar && overlay) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const adminMenuToggle = document.getElementById('adminMenuToggle');
    const adminDropdown = document.getElementById('adminDropdown');
    
    if (adminMenuToggle && adminDropdown) {
        adminMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            adminDropdown.classList.toggle('active');
        });
        
        document.addEventListener('click', function() {
            adminDropdown.classList.remove('active');
        });
        
        adminDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});