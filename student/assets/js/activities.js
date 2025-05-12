/**
 * activities.js - JavaScript สำหรับหน้ากิจกรรม
 */

document.addEventListener('DOMContentLoaded', function() {
    // แท็บตัวกรอง
    const filterTabs = document.querySelectorAll('.filter-tab');
    const activityCards = document.querySelectorAll('.activity-card');
    
    // ช่องค้นหา
    const searchInput = document.getElementById('activitySearch');
    
    // เพิ่ม event listener สำหรับแท็บตัวกรอง
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // ลบ active จากแท็บที่เคยเลือก
            filterTabs.forEach(t => t.classList.remove('active'));
            
            // เพิ่ม active ให้แท็บที่ถูกคลิก
            this.classList.add('active');
            
            // ประเภทตัวกรอง
            const filterType = this.getAttribute('data-filter');
            
            // กรองกิจกรรม
            filterActivities(filterType, searchInput ? searchInput.value : '');
        });
    });
    
    // เพิ่ม event listener สำหรับช่องค้นหา
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            // ดึงประเภทตัวกรองที่เลือกอยู่
            const selectedFilter = document.querySelector('.filter-tab.active');
            const filterType = selectedFilter ? selectedFilter.getAttribute('data-filter') : 'all';
            
            // กรองกิจกรรมด้วยตัวกรองและคำค้นหา
            filterActivities(filterType, this.value);
        });
    }
    
    /**
     * กรองกิจกรรมตามประเภทและคำค้นหา
     */
    function filterActivities(filterType, searchText) {
        activityCards.forEach(card => {
            // ตรวจสอบเงื่อนไขการกรอง
            let matchFilter = false;
            
            switch (filterType) {
                case 'upcoming':
                    matchFilter = card.classList.contains('upcoming-activity');
                    break;
                case 'past':
                    matchFilter = card.classList.contains('past-activity');
                    break;
                case 'required':
                    matchFilter = card.classList.contains('required-activity');
                    break;
                case 'all':
                default:
                    matchFilter = true;
                    break;
            }
            
            // ตรวจสอบคำค้นหา
            let matchSearch = true;
            if (searchText.trim() !== '') {
                // ค้นหาในชื่อกิจกรรม รายละเอียด และสถานที่
                const title = card.querySelector('.activity-title').textContent.toLowerCase();
                const location = card.querySelector('.activity-location span:last-child').textContent.toLowerCase();
                const description = card.querySelector('.activity-description') ? 
                    card.querySelector('.activity-description').textContent.toLowerCase() : '';
                
                const searchLower = searchText.toLowerCase();
                
                matchSearch = title.includes(searchLower) || 
                             location.includes(searchLower) || 
                             description.includes(searchLower);
            }
            
            // แสดงหรือซ่อนการ์ดกิจกรรม
            if (matchFilter && matchSearch) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
        
        // ตรวจสอบว่ามีกิจกรรมแสดงผลหรือไม่
        const visibleCards = document.querySelectorAll('.activity-card:not(.hidden)');
        const emptyMessage = document.querySelector('.empty-activities');
        
        if (visibleCards.length === 0 && !emptyMessage) {
            // สร้างข้อความแจ้งเตือนกรณีไม่พบกิจกรรมตามเงื่อนไข
            const activitiesList = document.querySelector('.activities-list');
            
            const noResultsElem = document.createElement('div');
            noResultsElem.className = 'empty-activities no-results';
            noResultsElem.innerHTML = `
                <span class="material-icons empty-icon">search_off</span>
                <div class="empty-message">ไม่พบกิจกรรมที่ตรงกับเงื่อนไข</div>
            `;
            
            activitiesList.after(noResultsElem);
        } else if (visibleCards.length > 0) {
            // กรณีมีกิจกรรมแสดงผล ให้ลบข้อความแจ้งเตือน
            const noResultsElem = document.querySelector('.no-results');
            if (noResultsElem) {
                noResultsElem.remove();
            }
        }
    }
});