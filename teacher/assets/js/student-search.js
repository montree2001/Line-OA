/**
 * student-search.js
 * เพิ่มฟังก์ชันการค้นหานักเรียนที่มีประสิทธิภาพ
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('ระบบค้นหานักเรียนที่ปรับปรุงแล้วเริ่มทำงาน');
    
    // เพิ่มการทำงานให้กับอินพุตค้นหา
    enhanceSearchInput();
    
    // อัพเดทฟังก์ชันค้นหานักเรียน
    improveSearchFunction();
});

/**
 * ปรับปรุงช่องค้นหา
 */
function enhanceSearchInput() {
    // ค้นหาช่องค้นหาจากหลายรูปแบบ
    const searchInput = document.getElementById('searchInput') || document.getElementById('search-input') || document.getElementById('student-search');
    
    if (!searchInput) return;
    
    // ตรวจสอบว่ามีปุ่มล้างการค้นหาหรือไม่
    const searchContainer = searchInput.parentElement;
    if (!searchContainer) return;
    
    let clearButton = searchContainer.querySelector('.search-clear');
    if (!clearButton) {
        // เพิ่มปุ่มล้างการค้นหา
        clearButton = document.createElement('span');
        clearButton.className = 'search-clear';
        clearButton.innerHTML = '<i class="fas fa-times"></i>';
        clearButton.style.display = 'none';
        clearButton.style.position = 'absolute';
        clearButton.style.right = '10px';
        clearButton.style.top = '50%';
        clearButton.style.transform = 'translateY(-50%)';
        clearButton.style.cursor = 'pointer';
        clearButton.style.zIndex = '5';
        searchContainer.style.position = 'relative';
        searchContainer.appendChild(clearButton);
        
        // แสดง/ซ่อนปุ่มล้างการค้นหา
        searchInput.addEventListener('input', function() {
            clearButton.style.display = this.value ? 'block' : 'none';
        });
        
        // เพิ่มฟังก์ชันล้างการค้นหา
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            clearButton.style.display = 'none';
            if (typeof window.searchStudents === 'function') {
                window.searchStudents();
            }
            searchInput.focus();
        });
        
        // เพิ่มความสามารถล้างด้วยปุ่ม Escape
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (this.value) {
                    this.value = '';
                    clearButton.style.display = 'none';
                    if (typeof window.searchStudents === 'function') {
                        window.searchStudents();
                    }
                    e.preventDefault();
                }
            }
        });
    }
}

/**
 * ปรับปรุงฟังก์ชันค้นหานักเรียน
 */
function improveSearchFunction() {
    // บันทึกฟังก์ชันเดิมไว้ (ถ้ามี)
    const originalSearchFunction = window.searchStudents;
    
    // สร้างฟังก์ชันค้นหาใหม่
    window.searchStudents = function() {
        // หาอินพุตค้นหา
        const searchInput = document.getElementById('searchInput') || document.getElementById('search-input') || document.getElementById('student-search');
        if (!searchInput) return;
        
        const searchTerm = searchInput.value.toLowerCase().trim();
        
        // ค้นหาในแท็บต่างๆ (ครอบคลุมทุกรูปแบบแท็บที่อาจมี)
        const tabIds = ['waitingTab', 'checkedTab', 'unchecked-tab', 'checked-tab'];
        
        // ลบข้อความว่างจากการค้นหาก่อนหน้า
        document.querySelectorAll('.empty-search-result').forEach(el => el.remove());
        
        // เรียกใช้ฟังก์ชันค้นหาเดิมถ้ามี
        if (typeof originalSearchFunction === 'function') {
            originalSearchFunction();
        } else {
            // ถ้าไม่มีฟังก์ชันเดิม ให้ใช้ฟังก์ชันใหม่
            tabIds.forEach(tabId => {
                enhancedSearchInTab(tabId, searchTerm);
            });
            
            // แสดงข้อความเมื่อไม่พบผลการค้นหา
            if (searchTerm) {
                tabIds.forEach(tabId => {
                    showEmptySearchResult(tabId, searchTerm);
                });
            }
        }
    };
}

/**
 * ค้นหานักเรียนในแท็บที่ระบุด้วยฟังก์ชันที่ปรับปรุงแล้ว
 * @param {string} tabId - ID ของแท็บ
 * @param {string} searchTerm - คำค้นหา
 */
function enhancedSearchInTab(tabId, searchTerm) {
    const tab = document.getElementById(tabId);
    if (!tab) return;
    
    // แสดง student-list (ในกรณีที่ถูกซ่อนไว้จากการค้นหาครั้งก่อน)
    const studentList = tab.querySelector('.student-list');
    if (studentList) {
        studentList.style.display = '';
    }
    
    // ค้นหาทุกการ์ดนักเรียน (รองรับทั้ง student-card และ student-item)
    const studentCards = tab.querySelectorAll('.student-card, .student-item');
    let found = false;
    
    studentCards.forEach(card => {
        // ค้นหาจากหลายแหล่งข้อมูล
        const nameAttr = card.getAttribute('data-name');
        const name = nameAttr ? nameAttr.toLowerCase() : '';
        
        // ค้นหาจากชื่อใน .student-name
        const nameElement = card.querySelector('.student-name');
        const nameText = nameElement ? nameElement.textContent.toLowerCase() : '';
        
        // ค้นหาจากรหัสนักเรียน
        const codeElement = card.querySelector('.student-code');
        const codeText = codeElement ? codeElement.textContent.toLowerCase() : '';
        
        // ค้นหาจากเลขที่นักเรียน
        const numberElement = card.querySelector('.student-number');
        const numberText = numberElement ? numberElement.textContent.toLowerCase() : '';
        
        if (searchTerm === '' || 
            name.includes(searchTerm) || 
            nameText.includes(searchTerm) || 
            codeText.includes(searchTerm) || 
            numberText.includes(searchTerm)) {
            card.style.display = '';
            found = true;
        } else {
            card.style.display = 'none';
        }
    });
    
    return found;
}

/**
 * แสดงข้อความเมื่อไม่พบผลการค้นหา
 * @param {string} tabId - ID ของแท็บ
 * @param {string} searchTerm - คำค้นหา
 */
function showEmptySearchResult(tabId, searchTerm) {
    const tab = document.getElementById(tabId);
    if (!tab) return;
    
    // ตรวจสอบว่าเป็นแท็บที่กำลังแสดงอยู่หรือไม่
    if (!tab.classList.contains('active') && !tab.classList.contains('tab-pane') && tab.style.display === 'none') {
        return;
    }
    
    // นับจำนวนการ์ดที่แสดงอยู่
    const visibleCards = Array.from(tab.querySelectorAll('.student-card, .student-item')).filter(
        card => card.style.display !== 'none'
    );
    
    // ถ้าไม่พบการ์ดใดๆ ที่ตรงกับการค้นหา
    if (visibleCards.length === 0) {
        // ซ่อน student-list (ถ้ามี)
        const studentList = tab.querySelector('.student-list');
        if (studentList) {
            studentList.style.display = 'none';
        }
        
        // ตรวจสอบว่ามีข้อความว่างอยู่แล้วหรือไม่
        if (tab.querySelector('.empty-search-result')) {
            return;
        }
        
        // สร้างข้อความว่าง
        const emptyMessage = document.createElement('div');
        emptyMessage.className = 'empty-state empty-search-result';
        emptyMessage.innerHTML = `
            <div class="empty-icon"><i class="fas fa-search"></i></div>
            <h3>ไม่พบนักเรียนที่ค้นหา</h3>
            <p>ไม่พบข้อมูลที่ตรงกับ "<span class="search-term">${searchTerm}</span>"</p>
        `;
        
        // เพิ่มข้อความว่างเข้าไปในแท็บ
        tab.appendChild(emptyMessage);
    }
} 