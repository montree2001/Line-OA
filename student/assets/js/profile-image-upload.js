/**
 * profile-image-upload.js - จัดการอัพโหลดรูปโปรไฟล์พร้อมปรับขนาด
 */
document.addEventListener('DOMContentLoaded', function() {
    // ตัวแปรสำหรับจัดการรูปภาพ
    let uploadedImage = null;
    let zoomLevel = 100;
    
    // เลือก DOM elements
    const uploadPhotoInput = document.getElementById('upload-photo');
    const uploadModal = document.getElementById('upload-modal');
    const imagePreview = document.getElementById('image-preview');
    const zoomSlider = document.getElementById('zoom-slider');
    const closeModal = document.querySelector('.close-modal');
    const cancelButton = document.getElementById('cancel-upload');
    const confirmButton = document.getElementById('confirm-upload');
    const loadingOverlay = document.getElementById('loading-overlay');
    const progressFill = document.querySelector('.progress-fill');
    const progressText = document.querySelector('.progress-text');
    const uploadProgress = document.querySelector('.upload-progress');
    const profileImage = document.querySelector('.profile-image');
    
    // เพิ่ม event listeners
    uploadPhotoInput.addEventListener('change', handleImageSelect);
    closeModal.addEventListener('click', closeImageModal);
    cancelButton.addEventListener('click', closeImageModal);
    confirmButton.addEventListener('click', uploadImage);
    zoomSlider.addEventListener('input', handleZoomChange);
    
    /**
     * จัดการการเลือกรูปภาพ
     * @param {Event} e - event จากการเลือกไฟล์
     */
    function handleImageSelect(e) {
        const file = e.target.files[0];
        
        if (!file) return;
        
        // ตรวจสอบว่าเป็นไฟล์รูปภาพหรือไม่
        if (!file.type.match('image.*')) {
            alert('กรุณาเลือกไฟล์รูปภาพเท่านั้น');
            return;
        }
        
        // ตรวจสอบขนาดไฟล์ (ไม่เกิน 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('ขนาดไฟล์ใหญ่เกินไป กรุณาเลือกไฟล์ขนาดไม่เกิน 5MB');
            return;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            // เก็บรูปที่อัพโหลดไว้ในตัวแปร
            uploadedImage = e.target.result;
            
            // แสดงตัวอย่างรูปภาพ
            imagePreview.src = uploadedImage;
            
            // เปิด modal
            uploadModal.style.display = 'block';
            
            // รีเซ็ตค่า zoom
            zoomLevel = 100;
            zoomSlider.value = 100;
            applyZoom();
            
            // ซ่อนการแสดงผลความคืบหน้า
            uploadProgress.style.display = 'none';
            progressFill.style.width = '0%';
            progressText.textContent = '0%';
        };
        
        reader.readAsDataURL(file);
    }
    
    /**
     * จัดการการเปลี่ยนแปลงค่า zoom
     */
    function handleZoomChange() {
        zoomLevel = zoomSlider.value;
        applyZoom();
    }
    
    /**
     * ปรับขนาดรูปตามค่า zoom
     */
    function applyZoom() {
        imagePreview.style.transform = `scale(${zoomLevel / 100})`;
    }
    
    /**
     * ปิด modal การอัพโหลดรูป
     */
    function closeImageModal() {
        uploadModal.style.display = 'none';
        uploadPhotoInput.value = ''; // รีเซ็ตค่า input
    }
    
    /**
     * อัพโหลดรูปภาพ
     */
    function uploadImage() {
        if (!uploadedImage) {
            alert('ไม่พบรูปภาพที่จะอัพโหลด');
            return;
        }
        
        // แสดงการโหลด
        showLoading();
        
        // จำลองการอัพโหลดด้วยการแสดงความคืบหน้า
        uploadProgress.style.display = 'block';
        
        // ปรับขนาดรูปและเตรียมข้อมูลสำหรับส่ง
        const resizedImage = resizeImage(uploadedImage, zoomLevel);
        
        // สร้าง FormData สำหรับส่งไปยัง server
        const formData = new FormData();
        formData.append('profile_image', dataURLtoBlob(resizedImage), 'profile.jpg');
        formData.append('user_id', getUserId());
        
        // จำลองการอัพโหลดด้วย setTimeout
        simulateUpload(formData);
    }
    
    /**
     * จำลองการอัพโหลดและแสดงความคืบหน้า
     * @param {FormData} formData - ข้อมูลที่จะส่ง
     */
    function simulateUpload(formData) {
        let progress = 0;
        const interval = setInterval(function() {
            progress += 10;
            
            if (progress <= 100) {
                updateProgress(progress);
            } else {
                clearInterval(interval);
                
                // ในระบบจริงควรใช้ fetch หรือ XMLHttpRequest ส่งข้อมูลไปยัง server
                // fetch('api/upload_profile_image.php', {
                //     method: 'POST',
                //     body: formData
                // })
                // .then(response => response.json())
                // .then(data => {
                //     if (data.success) {
                //         updateProfileImage(data.image_url);
                //         showMessage('อัพโหลดรูปโปรไฟล์สำเร็จ');
                //     } else {
                //         showError(data.message || 'เกิดข้อผิดพลาดในการอัพโหลด');
                //     }
                //     hideLoading();
                //     closeImageModal();
                // })
                // .catch(error => {
                //     showError('เกิดข้อผิดพลาดในการอัพโหลด: ' + error);
                //     hideLoading();
                //     closeImageModal();
                // });
                
                // จำลองการอัพโหลดสำเร็จ
                setTimeout(function() {
                    // อัพเดทรูปโปรไฟล์
                    updateProfileImage(uploadedImage);
                    
                    // ปิดการโหลดและ modal
                    hideLoading();
                    closeImageModal();
                    
                    // แสดงข้อความแจ้งเตือน
                    showMessage('อัพโหลดรูปโปรไฟล์สำเร็จ');
                }, 500);
            }
        }, 200);
    }
    
    /**
     * อัพเดทรูปโปรไฟล์ในหน้าเว็บ
     * @param {string} imageUrl - URL ของรูปที่อัพโหลด
     */
    function updateProfileImage(imageUrl) {
        // ตรวจสอบว่ามีรูปภาพอยู่แล้วหรือไม่
        if (profileImage.querySelector('img')) {
            // อัพเดทรูปที่มีอยู่
            profileImage.querySelector('img').src = imageUrl;
        } else {
            // สร้าง element รูปใหม่
            profileImage.innerHTML = '';
            const image = document.createElement('img');
            image.src = imageUrl;
            image.id = 'profile-img';
            image.alt = 'รูปโปรไฟล์';
            profileImage.appendChild(image);
        }
    }
    
    /**
     * อัพเดทแสดงความคืบหน้าการอัพโหลด
     * @param {number} percentage - เปอร์เซ็นต์ความคืบหน้า
     */
    function updateProgress(percentage) {
        progressFill.style.width = `${percentage}%`;
        progressText.textContent = `${percentage}%`;
    }
    
    /**
     * แสดงการโหลด
     */
    function showLoading() {
        loadingOverlay.style.display = 'flex';
    }
    
    /**
     * ซ่อนการโหลด
     */
    function hideLoading() {
        loadingOverlay.style.display = 'none';
    }
    
    /**
     * แสดงข้อความแจ้งเตือน
     * @param {string} message - ข้อความที่ต้องการแจ้ง
     */
    function showMessage(message) {
        // สร้าง element แจ้งเตือน
        const alert = document.createElement('div');
        alert.className = 'alert-message';
        alert.textContent = message;
        document.body.appendChild(alert);
        
        // ลบข้อความแจ้งเตือนหลังจาก 3 วินาที
        setTimeout(() => {
            alert.classList.add('hide');
            setTimeout(() => {
                document.body.removeChild(alert);
            }, 300);
        }, 3000);
    }
    
    /**
     * แสดงข้อความแจ้งเตือนข้อผิดพลาด
     * @param {string} message - ข้อความที่ต้องการแจ้ง
     */
    function showError(message) {
        // สร้าง element แจ้งเตือนข้อผิดพลาด
        const alert = document.createElement('div');
        alert.className = 'alert-message error';
        alert.textContent = message;
        document.body.appendChild(alert);
        
        // ลบข้อความแจ้งเตือนหลังจาก 3 วินาที
        setTimeout(() => {
            alert.classList.add('hide');
            setTimeout(() => {
                document.body.removeChild(alert);
            }, 300);
        }, 3000);
    }
    
    /**
     * ดึง user ID จากหน้าเว็บหรือ data attribute
     * @returns {string} user ID
     */
    function getUserId() {
        // ในระบบจริง ควรดึงจาก data attribute หรือ hidden input
        return document.querySelector('[data-user-id]')?.dataset.userId || '1';
    }
    
    /**
     * ปรับขนาดรูปภาพ
     * @param {string} dataUrl - รูปภาพในรูปแบบ Data URL
     * @param {number} zoom - ระดับการซูม (เปอร์เซ็นต์)
     * @returns {string} รูปภาพที่ปรับขนาดแล้ว
     */
    function resizeImage(dataUrl, zoom) {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();
        
        // กำหนดขนาดสูงสุดของรูปที่ต้องการ
        const maxSize = 500;
        
        img.src = dataUrl;
        
        // กำหนดขนาด canvas
        let width = img.width;
        let height = img.height;
        
        // ปรับขนาดตามอัตราส่วน
        if (width > height) {
            if (width > maxSize) {
                height = Math.round(height * (maxSize / width));
                width = maxSize;
            }
        } else {
            if (height > maxSize) {
                width = Math.round(width * (maxSize / height));
                height = maxSize;
            }
        }
        
        // ปรับขนาดตามค่า zoom
        const zoomFactor = zoom / 100;
        const zoomedWidth = width * zoomFactor;
        const zoomedHeight = height * zoomFactor;
        
        // กำหนดขนาด canvas ให้เท่ากับขนาดรูปที่ต้องการ
        canvas.width = width;
        canvas.height = height;
        
        // คำนวณตำแหน่งเริ่มต้นเพื่อให้รูปอยู่กึ่งกลาง
        const offsetX = (width - zoomedWidth) / 2;
        const offsetY = (height - zoomedHeight) / 2;
        
        // วาดรูปลงบน canvas
        ctx.drawImage(img, offsetX, offsetY, zoomedWidth, zoomedHeight);
        
        // แปลง canvas เป็น Data URL
        return canvas.toDataURL('image/jpeg', 0.85);
    }
    
    /**
     * แปลง Data URL เป็น Blob สำหรับส่งไปยัง server
     * @param {string} dataURL - รูปภาพในรูปแบบ Data URL
     * @returns {Blob} ข้อมูลในรูปแบบ Blob
     */
    function dataURLtoBlob(dataURL) {
        const arr = dataURL.split(',');
        const mime = arr[0].match(/:(.*?);/)[1];
        const bstr = atob(arr[1]);
        let n = bstr.length;
        const u8arr = new Uint8Array(n);
        
        while (n--) {
            u8arr[n] = bstr.charCodeAt(n);
        }
        
        return new Blob([u8arr], { type: mime });
    }
    
    /**
     * เพิ่ม CSS สำหรับ alert message
     */
    function addAlertStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .alert-message {
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                background-color: #06c755;
                color: white;
                padding: 10px 20px;
                border-radius: 5px;
                z-index: 5000;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                opacity: 1;
                transition: opacity 0.3s;
            }
            
            .alert-message.error {
                background-color: #f44336;
            }
            
            .alert-message.hide {
                opacity: 0;
            }
        `;
        document.head.appendChild(style);
    }
    
    // เพิ่ม CSS สำหรับ alert
    addAlertStyles();
});