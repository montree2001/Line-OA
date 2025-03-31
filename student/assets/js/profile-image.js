/**
 * profile-image.js - จัดการรูปโปรไฟล์ของผู้ใช้
 */
document.addEventListener('DOMContentLoaded', function() {
    /**
     * เริ่มทำงานทันทีเมื่อโหลดหน้าเว็บ
     */
    initProfileImages();

    /**
     * เริ่มต้นการจัดการรูปโปรไฟล์
     */
    function initProfileImages() {
        // ดึงรูปโปรไฟล์ทั้งหมดในหน้า
        const profileImages = document.querySelectorAll('.img-profile');
        
        // ดักจับเหตุการณ์การโหลดรูป
        profileImages.forEach(img => {
            // เพิ่มคลาส loading ขณะรอโหลด
            img.classList.add('loading');
            
            // ติดตามสถานะการโหลด
            img.addEventListener('load', function() {
                // เมื่อโหลดเสร็จ ลบคลาส loading
                img.classList.remove('loading');
            });
            
            // จัดการกรณีไม่สามารถโหลดรูปได้
            img.addEventListener('error', function() {
                handleImageError(img);
            });
        });
    }

    /**
     * จัดการกรณีไม่สามารถโหลดรูปได้
     * @param {HTMLImageElement} img - รูปภาพที่มีปัญหา
     */
    function handleImageError(img) {
        const parent = img.parentElement;
        const name = img.getAttribute('data-name') || '';
        const initial = name.charAt(0) || '?';
        
        // ลบรูปที่มีปัญหา
        img.remove();
        
        // สร้างอักษรย่อแทนรูป
        const textAvatar = document.createElement('div');
        textAvatar.className = 'text-avatar';
        textAvatar.textContent = initial;
        
        // เพิ่มอักษรย่อแทนรูป
        parent.appendChild(textAvatar);
    }
    
    /**
     * ปรับปรุงรูปโปรไฟล์ในหน้าเว็บเมื่อมีการอัปเดต
     * @param {string} imageUrl - URL ของรูปใหม่
     */
    window.updateAllProfileImages = function(imageUrl) {
        const profileImages = document.querySelectorAll('.img-profile');
        
        profileImages.forEach(img => {
            img.src = imageUrl;
            img.classList.add('loading');
        });
    };
}); 