/**
 * settings.js - JavaScript สำหรับการจัดการหน้าตั้งค่าระบบน้องชูใจ AI ดูแลผู้เรียน
 */

// ตัวแปรสำหรับเก็บอ็อบเจ็กต์แผนที่
let map;
let marker;
let additionalMarkers = [];

document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่าแท็บ
    initializeTabs();
    
    // ตั้งค่าปุ่มและฟอร์ม
    setupSettingsControls();
    
    // ตั้งค่าสถานะที่ซ่อน/แสดงตามเงื่อนไข
    setupConditionalElements();
    
    // ตั้งค่า Event Listener สำหรับการเพิ่ม/ลบรายการ
    setupDynamicListHandlers();
});

/**
 * เริ่มต้น Google Maps API
 * จะถูกเรียกโดย callback จาก Google Maps
 */
function initMapAPI() {
    // เมื่อมีการกดปุ่มแสดงแผนที่ จึงจะโหลดแผนที่
    // เนื่องจากการโหลดแผนที่ต้องใช้ทรัพยากรมาก
}

/**
 * เริ่มต้นการทำงานของแท็บ
 */
function initializeTabs() {
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            showTab(tabId);
        });
    });

    // แสดงแท็บเริ่มต้น (จากพารามิเตอร์ URL หรือแท็บแรก)
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    
    if (tabParam && document.querySelector(`.tab[data-tab="${tabParam}"]`)) {
        showTab(tabParam);
    } else {
        const firstTab = document.querySelector('.tab');
        if (firstTab) {
            const firstTabId = firstTab.getAttribute('data-tab');
            showTab(firstTabId);
        }
    }
}

/**
 * แสดงแท็บที่เลือก
 * @param {string} tabId - ID ของแท็บที่ต้องการแสดง
 */
function showTab(tabId) {
    // ซ่อนแท็บทั้งหมด
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // ยกเลิกการเลือกแท็บทั้งหมด
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // แสดงแท็บที่ต้องการและเลือกแท็บนั้น
    document.getElementById(tabId + '-tab').classList.add('active');
    document.querySelector(`.tab[data-tab="${tabId}"]`).classList.add('active');

    // เปลี่ยน URL โดยไม่ต้อง refresh
    const url = new URL(window.location);
    url.searchParams.set('tab', tabId);
    window.history.pushState({}, '', url);

    // ถ้าเป็นแท็บ GPS และมีการเปิดแผนที่ ให้ปรับขนาดแผนที่
    if (tabId === 'gps' && map) {
        google.maps.event.trigger(map, 'resize');
    }
}

/**
 * ตั้งค่าการควบคุมต่างๆ ในหน้าตั้งค่า
 */
function setupSettingsControls() {
    // เพิ่มการตรวจสอบการเปลี่ยนแปลงการตั้งค่า
    const settingsInputs = document.querySelectorAll('input, select, textarea');
    settingsInputs.forEach(input => {
        input.addEventListener('change', markSettingsModified);
    });

    // ปุ่มบันทึกการตั้งค่า
    const saveButton = document.querySelector('.save-button');
    if (saveButton) {
        saveButton.addEventListener('click', saveSettings);
    }
    
    // ปุ่มสำรองข้อมูลทันที
    const backupButton = document.getElementById('backup-now');
    if (backupButton) {
        backupButton.addEventListener('click', backupDatabase);
    }
    
    // ปุ่มกู้คืนข้อมูล
    const restoreButton = document.getElementById('restore-backup');
    if (restoreButton) {
        restoreButton.addEventListener('click', showRestoreDialog);
    }
    
    // ปุ่มทดสอบการส่ง SMS
    const testSmsButton = document.getElementById('send-test-sms');
    if (testSmsButton) {
        testSmsButton.addEventListener('click', sendTestSms);
    }
    
    // ปุ่มอัปเดต LIFF
    const updateLiffButton = document.getElementById('update-liff');
    if (updateLiffButton) {
        updateLiffButton.addEventListener('click', updateLiffSettings);
    }
    
    // ปุ่มอัปเดต Rich Menu
    const updateRichMenuButton = document.getElementById('update-rich-menu');
    if (updateRichMenuButton) {
        updateRichMenuButton.addEventListener('click', updateRichMenuSettings);
    }

    // ปุ่มใช้ตำแหน่งปัจจุบัน
    const getCurrentLocationButton = document.getElementById('get-current-location');
    if (getCurrentLocationButton) {
        getCurrentLocationButton.addEventListener('click', function(e) {
            e.preventDefault();
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById('school_latitude').value = position.coords.latitude.toFixed(7);
                    document.getElementById('school_longitude').value = position.coords.longitude.toFixed(7);
                    markSettingsModified();

                    // อัปเดตตำแหน่งบนแผนที่ถ้ามีการแสดงแผนที่อยู่
                    if (map && marker) {
                        const newLatLng = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                        marker.setPosition(newLatLng);
                        map.setCenter(newLatLng);
                    }
                }, function(error) {
                    showErrorMessage('ไม่สามารถรับตำแหน่งปัจจุบันได้: ' + error.message);
                });
            } else {
                showErrorMessage('เบราว์เซอร์ของคุณไม่รองรับการระบุตำแหน่ง');
            }
        });
    }
    
    // ปุ่มแสดงแผนที่
    const showMapButton = document.getElementById('show-map');
    if (showMapButton) {
        showMapButton.addEventListener('click', function(e) {
            e.preventDefault();
            const mapContainer = document.getElementById('map-container');
            if (mapContainer.style.display === 'none') {
                mapContainer.style.display = 'block';
                setTimeout(() => {
                    initializeMap();
                }, 100);
                this.innerHTML = '<span class="material-icons">close</span> ซ่อนแผนที่';
            } else {
                mapContainer.style.display = 'none';
                this.innerHTML = '<span class="material-icons">map</span> เลือกจากแผนที่';
            }
        });
    }
    
    // ปุ่มเพิ่มปีการศึกษาใหม่
    const addAcademicYearButton = document.getElementById('add-academic-year');
    if (addAcademicYearButton) {
        addAcademicYearButton.addEventListener('click', function(e) {
            e.preventDefault();
            showAcademicYearDialog();
        });
    }
}

/**
 * ตั้งค่าการแสดงผลของ elements ตามเงื่อนไข
 */
function setupConditionalElements() {
    // แสดง/ซ่อน elements ที่ขึ้นอยู่กับการเลือกในฟอร์ม

    // ตัวเลือกธีมสี
    const themeSelect = document.getElementById('system_theme');
    if (themeSelect) {
        themeSelect.addEventListener('change', function() {
            const customThemeColors = document.getElementById('custom-theme-colors');
            if (this.value === 'custom') {
                customThemeColors.style.display = 'flex';
            } else {
                customThemeColors.style.display = 'none';
            }
        });
        // ตรวจสอบค่าเริ่มต้น
        if (themeSelect.value === 'custom') {
            document.getElementById('custom-theme-colors').style.display = 'flex';
        }
    }
    
    // ตัวเลือกจำนวนครั้งที่ขาดแถวก่อนการแจ้งเตือน
    const absenceThresholdSelect = document.getElementById('absence_threshold');
    if (absenceThresholdSelect) {
        absenceThresholdSelect.addEventListener('change', function() {
            const customAbsenceThreshold = document.getElementById('custom-absence-threshold');
            if (this.value === 'custom') {
                customAbsenceThreshold.style.display = 'block';
            } else {
                customAbsenceThreshold.style.display = 'none';
            }
        });
        // ตรวจสอบค่าเริ่มต้น
        if (absenceThresholdSelect.value === 'custom') {
            document.getElementById('custom-absence-threshold').style.display = 'block';
        }
    }
    
    // ตัวเลือกรัศมี GPS
    const gpsRadiusSelect = document.getElementById('gps_radius');
    if (gpsRadiusSelect) {
        gpsRadiusSelect.addEventListener('change', function() {
            const customGpsRadius = document.getElementById('custom-gps-radius');
            if (this.value === 'custom') {
                customGpsRadius.style.display = 'block';
            } else {
                customGpsRadius.style.display = 'none';
            }
        });
        // ตรวจสอบค่าเริ่มต้น
        if (gpsRadiusSelect.value === 'custom') {
            document.getElementById('custom-gps-radius').style.display = 'block';
        }
    }
    
    // ตัวเลือกอัตราการเข้าแถวต่ำสุด
    const minAttendanceRateSelect = document.getElementById('min_attendance_rate');
    if (minAttendanceRateSelect) {
        minAttendanceRateSelect.addEventListener('change', function() {
            const customAttendanceRate = document.getElementById('custom-attendance-rate');
            if (this.value === 'custom') {
                customAttendanceRate.style.display = 'block';
            } else {
                customAttendanceRate.style.display = 'none';
            }
        });
        // ตรวจสอบค่าเริ่มต้น
        if (minAttendanceRateSelect.value === 'custom') {
            document.getElementById('custom-attendance-rate').style.display = 'block';
        }
    }
    
    // ตัวเลือกเวลาส่ง SMS
    const smsSendTimeSelect = document.getElementById('sms_send_time');
    if (smsSendTimeSelect) {
        smsSendTimeSelect.addEventListener('change', function() {
            const customSmsTime = document.getElementById('custom-sms-time');
            if (this.value === 'custom') {
                customSmsTime.style.display = 'flex';
            } else {
                customSmsTime.style.display = 'none';
            }
        });
        // ตรวจสอบค่าเริ่มต้น
        if (smsSendTimeSelect.value === 'custom') {
            document.getElementById('custom-sms-time').style.display = 'flex';
        }
    }
    
    // ตัวเลือกผู้ให้บริการ SMS
    const smsProviderSelect = document.getElementById('sms_provider');
    if (smsProviderSelect) {
        smsProviderSelect.addEventListener('change', function() {
            const customSmsProvider = document.getElementById('custom-sms-provider');
            if (this.value === 'custom') {
                customSmsProvider.style.display = 'block';
            } else {
                customSmsProvider.style.display = 'none';
            }
        });
        // ตรวจสอบค่าเริ่มต้น
        if (smsProviderSelect.value === 'custom') {
            document.getElementById('custom-sms-provider').style.display = 'block';
        }
    }
    
    // ตัวเลือกอนุญาตให้เช็คชื่อล่าช้า
    const lateCheckCheckbox = document.getElementById('late_check');
    if (lateCheckCheckbox) {
        lateCheckCheckbox.addEventListener('change', function() {
            const lateCheckOptions = document.getElementById('late-check-options');
            if (this.checked) {
                lateCheckOptions.style.display = 'flex';
            } else {
                lateCheckOptions.style.display = 'none';
            }
        });
        // ตรวจสอบค่าเริ่มต้น
        if (lateCheckCheckbox.checked) {
            document.getElementById('late-check-options').style.display = 'flex';
        }
    }
    
    // ตัวเลือกจุดเช็คชื่อหลายจุด
    const enableMultipleLocations = document.getElementById('enable_multiple_locations');
    if (enableMultipleLocations) {
        enableMultipleLocations.addEventListener('change', function() {
            const additionalLocations = document.getElementById('additional-locations');
            if (this.checked) {
                additionalLocations.style.display = 'block';
            } else {
                additionalLocations.style.display = 'none';
            }
        });
        // ตรวจสอบค่าเริ่มต้น
        if (enableMultipleLocations.checked) {
            document.getElementById('additional-locations').style.display = 'block';
        }
    }

    // ตัวเลือกการใช้งาน Line OA เดียวหรือหลาย Line OA
    const singleLineOACheckbox = document.getElementById('single_line_oa');
    if (singleLineOACheckbox) {
        singleLineOACheckbox.addEventListener('change', function() {
            const singleOASection = document.getElementById('single-oa-section');
            const multipleOASection = document.getElementById('multiple-oa-section');
            
            if (this.checked) {
                singleOASection.style.display = 'block';
                multipleOASection.style.display = 'none';
            } else {
                singleOASection.style.display = 'none';
                multipleOASection.style.display = 'block';
            }
        });
        // ตรวจสอบค่าเริ่มต้น
        if (singleLineOACheckbox.checked) {
            document.getElementById('single-oa-section').style.display = 'block';
            document.getElementById('multiple-oa-section').style.display = 'none';
        } else {
            document.getElementById('single-oa-section').style.display = 'none';
            document.getElementById('multiple-oa-section').style.display = 'block';
        }
    }
}

/**
 * ตั้งค่าการจัดการรายการแบบไดนามิก (เพิ่ม/ลบรายการ)
 */
function setupDynamicListHandlers() {
    // ปุ่มเพิ่มสถานที่
    const addLocationButton = document.getElementById('add-location');
    if (addLocationButton) {
        addLocationButton.addEventListener('click', function(e) {
            e.preventDefault();
            const additionalLocations = document.getElementById('additional-locations');
            const locationItems = document.querySelectorAll('.additional-location-item');
            
            // คัดลอกจากรายการแรก
            const template = locationItems[0].cloneNode(true);
            
            // เคลียร์ค่าใน inputs
            template.querySelectorAll('input').forEach(input => {
                input.value = '';
            });
            
            // ตั้งค่า listener สำหรับปุ่มลบ
            const removeButton = template.querySelector('.remove-location');
            if (removeButton) {
                removeButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    this.closest('.additional-location-item').remove();
                    markSettingsModified();
                });
            }
            
            // แทรกก่อนปุ่มเพิ่ม
            additionalLocations.insertBefore(template, addLocationButton);
            markSettingsModified();
        });
        
        // ตั้งค่า listener สำหรับปุ่มลบที่มีอยู่
        document.querySelectorAll('.remove-location').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                // ต้องมีอย่างน้อย 1 รายการเสมอ
                const locationItems = document.querySelectorAll('.additional-location-item');
                if (locationItems.length > 1) {
                    this.closest('.additional-location-item').remove();
                    markSettingsModified();
                } else {
                    showErrorMessage('ต้องมีอย่างน้อย 1 ตำแหน่ง');
                }
            });
        });
    }
    
    // ปุ่มเพิ่มคำสั่ง
    const addCommandButton = document.getElementById('add-command');
    if (addCommandButton) {
        addCommandButton.addEventListener('click', function(e) {
            e.preventDefault();
            const commandsContainer = document.getElementById('commands-container');
            const template = document.createElement('tr');
            template.innerHTML = `
                <td>
                    <input type="text" class="form-control" name="command_key[]" value="">
                    <small class="form-text text-muted">คั่นคำหลายคำด้วยเครื่องหมาย ,</small>
                </td>
                <td>
                    <textarea class="form-control" name="command_reply[]" rows="2"></textarea>
                </td>
                <td>
                    <button class="btn btn-sm btn-danger remove-command">
                        <span class="material-icons">delete</span>
                    </button>
                </td>
            `;
            
            // ตั้งค่า listener สำหรับปุ่มลบ
            const removeButton = template.querySelector('.remove-command');
            removeButton.addEventListener('click', function(e) {
                e.preventDefault();
                this.closest('tr').remove();
                markSettingsModified();
            });
            
            // แทรกที่ท้ายตาราง
            commandsContainer.appendChild(template);
            markSettingsModified();
        });
        
        // ตั้งค่า listener สำหรับปุ่มลบที่มีอยู่
        document.querySelectorAll('.remove-command').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                this.closest('tr').remove();
                markSettingsModified();
            });
        });
    }

    // ปุ่มเลือกตำแหน่งจากแผนที่สำหรับจุดเพิ่มเติม
    const pickLocationButtons = document.querySelectorAll('.pick-location');
    if (pickLocationButtons.length > 0) {
        pickLocationButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const locationItem = this.closest('.additional-location-item');
                const latInput = locationItem.querySelector('[name="location_latitude[]"]');
                const lngInput = locationItem.querySelector('[name="location_longitude[]"]');
                
                // เปิดหน้าต่างเลือกตำแหน่ง
                showLocationPickerDialog(latInput, lngInput);
            });
        });
    }
}

/**
 * แสดงหน้าต่างเลือกตำแหน่งจากแผนที่
 */
function showLocationPickerDialog(latInput, lngInput) {
    // สร้างหน้าต่างโต้ตอบ
    const dialog = document.createElement('div');
    dialog.className = 'modal fade';
    dialog.id = 'locationPickerModal';
    dialog.setAttribute('tabindex', '-1');
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-labelledby', 'locationPickerModalLabel');
    dialog.setAttribute('aria-hidden', 'true');
    
    dialog.innerHTML = `
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="locationPickerModalLabel">เลือกตำแหน่งจากแผนที่</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="picker-map" style="height: 400px; width: 100%;"></div>
                    <p class="mt-2 text-muted">คลิกที่แผนที่เพื่อเลือกตำแหน่ง หรือลากหมุดเพื่อปรับตำแหน่ง</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="confirm-location">ยืนยันตำแหน่ง</button>
                </div>
            </div>
        </div>
    `;
    
    // เพิ่มหน้าต่างลงใน DOM
    document.body.appendChild(dialog);
    
    // แสดงหน้าต่าง
    $('#locationPickerModal').modal('show');
    
    // สร้างแผนที่สำหรับเลือกตำแหน่ง
    let pickerMap;
    let pickerMarker;
    
    // รอให้ modal แสดงเสร็จก่อนสร้างแผนที่
    $('#locationPickerModal').on('shown.bs.modal', function() {
        // กำหนดตำแหน่งเริ่มต้น
        const initialLat = latInput.value ? parseFloat(latInput.value) : parseFloat(document.getElementById('school_latitude').value) || 14.0065;
        const initialLng = lngInput.value ? parseFloat(lngInput.value) : parseFloat(document.getElementById('school_longitude').value) || 100.5018;
        
        // สร้างแผนที่
        pickerMap = new google.maps.Map(document.getElementById('picker-map'), {
            center: { lat: initialLat, lng: initialLng },
            zoom: 17,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        });
        
        // สร้างหมุด
        pickerMarker = new google.maps.Marker({
            position: { lat: initialLat, lng: initialLng },
            map: pickerMap,
            draggable: true,
            animation: google.maps.Animation.DROP
        });
        
        // เมื่อคลิกที่แผนที่ ให้ย้ายหมุด
        pickerMap.addListener('click', function(e) {
            pickerMarker.setPosition(e.latLng);
        });
    });
    
    // ปุ่มยืนยันตำแหน่ง
    document.getElementById('confirm-location').addEventListener('click', function() {
        // บันทึกตำแหน่ง
        const position = pickerMarker.getPosition();
        latInput.value = position.lat().toFixed(7);
        lngInput.value = position.lng().toFixed(7);
        
        // ปิดหน้าต่าง
        $('#locationPickerModal').modal('hide');
        
        // ทำเครื่องหมายว่ามีการเปลี่ยนแปลง
        markSettingsModified();
    });
    
    // ทำความสะอาดเมื่อปิดหน้าต่าง
    $('#locationPickerModal').on('hidden.bs.modal', function() {
        document.body.removeChild(dialog);
    });
}

/**
 * เริ่มต้นแผนที่
 */
function initializeMap() {
    // กำหนดตำแหน่งจากค่าใน input
    const lat = parseFloat(document.getElementById('school_latitude').value) || 14.0065;
    const lng = parseFloat(document.getElementById('school_longitude').value) || 100.5018;
    const radius = parseInt(document.getElementById('gps_radius').value === 'custom' ? 
        document.getElementById('custom_gps_radius').value : 
        document.getElementById('gps_radius').value) || 100;
    
    // สร้างแผนที่
    map = new google.maps.Map(document.getElementById('map-container'), {
        center: { lat: lat, lng: lng },
        zoom: 16,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    });
    
    // สร้างหมุดหลัก
    marker = new google.maps.Marker({
        position: { lat: lat, lng: lng },
        map: map,
        draggable: true,
        animation: google.maps.Animation.DROP,
        title: 'ตำแหน่งโรงเรียน'
    });
    
    // สร้างวงกลมแสดงรัศมี
    const circle = new google.maps.Circle({
        map: map,
        radius: radius,
        fillColor: '#3388ff',
        fillOpacity: 0.2,
        strokeColor: '#3388ff',
        strokeOpacity: 0.6,
        strokeWeight: 2
    });
    
    // ผูกวงกลมกับหมุด
    circle.bindTo('center', marker, 'position');
    
    // เมื่อลากหมุดและปล่อย ให้อัปเดตค่าละติจูด/ลองจิจูด
    marker.addListener('dragend', function() {
        const position = marker.getPosition();
        document.getElementById('school_latitude').value = position.lat().toFixed(7);
        document.getElementById('school_longitude').value = position.lng().toFixed(7);
        markSettingsModified();
    });
    
    // เมื่อคลิกที่แผนที่ ให้ย้ายหมุด
    map.addListener('click', function(e) {
        marker.setPosition(e.latLng);
        document.getElementById('school_latitude').value = e.latLng.lat().toFixed(7);
        document.getElementById('school_longitude').value = e.latLng.lng().toFixed(7);
        markSettingsModified();
    });
    
    // อัปเดตรัศมีเมื่อมีการเปลี่ยนค่า
    document.getElementById('gps_radius').addEventListener('change', updateCircleRadius);
    if (document.getElementById('custom_gps_radius')) {
        document.getElementById('custom_gps_radius').addEventListener('input', updateCircleRadius);
    }
    
    // แสดงหมุดสำหรับตำแหน่งเพิ่มเติม
    if (document.getElementById('enable_multiple_locations').checked) {
        showAdditionalMarkers();
    }
    
    // เมื่อเปิด/ปิดการใช้งานจุดเช็คชื่อหลายจุด
    document.getElementById('enable_multiple_locations').addEventListener('change', function() {
        if (this.checked) {
            showAdditionalMarkers();
        } else {
            hideAdditionalMarkers();
        }
    });
    
    // ฟังก์ชันอัปเดตรัศมี
    function updateCircleRadius() {
        const radiusValue = document.getElementById('gps_radius').value;
        let newRadius;
        
        if (radiusValue === 'custom') {
            newRadius = parseInt(document.getElementById('custom_gps_radius').value) || 100;
        } else {
            newRadius = parseInt(radiusValue) || 100;
        }
        
        circle.setRadius(newRadius);
    }
    
    // ฟังก์ชันแสดงหมุดเพิ่มเติม
    function showAdditionalMarkers() {
        // ลบหมุดเดิมก่อน
        hideAdditionalMarkers();
        
        // รับรายการตำแหน่งเพิ่มเติม
        const locationItems = document.querySelectorAll('.additional-location-item');
        
        locationItems.forEach((item, index) => {
            const nameInput = item.querySelector('[name="location_name[]"]');
            const radiusInput = item.querySelector('[name="location_radius[]"]');
            const latInput = item.querySelector('[name="location_latitude[]"]');
            const lngInput = item.querySelector('[name="location_longitude[]"]');
            
            if (latInput.value && lngInput.value) {
                const lat = parseFloat(latInput.value);
                const lng = parseFloat(lngInput.value);
                const radius = parseInt(radiusInput.value) || 100;
                const name = nameInput.value || `ตำแหน่งที่ ${index + 1}`;
                
                // สร้างหมุดสำหรับตำแหน่งเพิ่มเติม
                const additionalMarker = new google.maps.Marker({
                    position: { lat: lat, lng: lng },
                    map: map,
                    draggable: true,
                    animation: google.maps.Animation.DROP,
                    title: name,
                    icon: {
                        url: 'http://maps.google.com/mapfiles/ms/icons/green-dot.png'
                    }
                });
                
                // สร้างวงกลมแสดงรัศมี
                const additionalCircle = new google.maps.Circle({
                    map: map,
                    radius: radius,
                    fillColor: '#33cc33',
                    fillOpacity: 0.2,
                    strokeColor: '#33cc33',
                    strokeOpacity: 0.6,
                    strokeWeight: 2
                });
                
                // ผูกวงกลมกับหมุด
                additionalCircle.bindTo('center', additionalMarker, 'position');
                
                // เมื่อลากหมุดและปล่อย ให้อัปเดตค่าละติจูด/ลองจิจูด
                additionalMarker.addListener('dragend', function() {
                    const position = additionalMarker.getPosition();
                    latInput.value = position.lat().toFixed(7);
                    lngInput.value = position.lng().toFixed(7);
                    markSettingsModified();
                });
                
                // เก็บ object ไว้เพื่อลบในภายหลัง
                additionalMarkers.push({
                    marker: additionalMarker,
                    circle: additionalCircle
                });
            }
        });
    }
    
    // ฟังก์ชันซ่อนหมุดเพิ่มเติม
    function hideAdditionalMarkers() {
        additionalMarkers.forEach(item => {
            item.marker.setMap(null);
            item.circle.setMap(null);
        });
        additionalMarkers = [];
    }
}

/**
 * แสดงหน้าต่างโต้ตอบสำหรับเพิ่มปีการศึกษาใหม่
 */
function showAcademicYearDialog() {
    // สร้างหน้าต่างโต้ตอบแบบง่าย
    const dialog = document.createElement('div');
    dialog.className = 'modal fade';
    dialog.id = 'academicYearModal';
    dialog.setAttribute('tabindex', '-1');
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-labelledby', 'academicYearModalLabel');
    dialog.setAttribute('aria-hidden', 'true');
    
    dialog.innerHTML = `
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="academicYearModalLabel">เพิ่มปีการศึกษาใหม่</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="new_academic_year">ปีการศึกษา</label>
                        <input type="number" class="form-control" id="new_academic_year" value="${parseInt(document.getElementById('current_academic_year').value) + 1}">
                    </div>
                    <div class="form-group">
                        <label for="new_semester">ภาคเรียน</label>
                        <select class="form-control" id="new_semester">
                            <option value="1">ภาคเรียนที่ 1</option>
                            <option value="2">ภาคเรียนที่ 2</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="new_start_date">วันเริ่มต้นภาคเรียน</label>
                        <input type="date" class="form-control" id="new_start_date">
                    </div>
                    <div class="form-group">
                        <label for="new_end_date">วันสิ้นสุดภาคเรียน</label>
                        <input type="date" class="form-control" id="new_end_date">
                    </div>
                    <div class="form-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="new_is_active">
                            <label for="new_is_active">ตั้งเป็นปีการศึกษาปัจจุบัน</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="new_required_days">จำนวนวันที่ต้องเข้าแถวเพื่อผ่านกิจกรรม</label>
                        <input type="number" class="form-control" id="new_required_days" value="80">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="save-academic-year">บันทึก</button>
                </div>
            </div>
        </div>
    `;
    
    // เพิ่มหน้าต่างโต้ตอบลงใน DOM
    document.body.appendChild(dialog);
    
    // กำหนดค่าเริ่มต้นสำหรับวันที่
    const today = new Date();
    const startDate = document.getElementById('new_start_date');
    // ตั้งค่าเริ่มต้นเป็นวันที่ 16 พฤษภาคมของปีถัดไป (สำหรับภาคเรียนที่ 1)
    startDate.value = `${today.getFullYear() + 1}-05-16`;
    
    const endDate = document.getElementById('new_end_date');
    // ตั้งค่าเริ่มต้นเป็นวันที่ 15 ตุลาคมของปีถัดไป
    endDate.value = `${today.getFullYear() + 1}-10-15`;
    
    // ตั้งค่า event listener สำหรับปุ่มบันทึก
    document.getElementById('save-academic-year').addEventListener('click', function() {
        const year = document.getElementById('new_academic_year').value;
        const semester = document.getElementById('new_semester').value;
        const startDate = document.getElementById('new_start_date').value;
        const endDate = document.getElementById('new_end_date').value;
        const isActive = document.getElementById('new_is_active').checked;
        const requiredDays = document.getElementById('new_required_days').value;
        
        // ส่งข้อมูลไปบันทึกที่เซิร์ฟเวอร์
        saveAcademicYear(year, semester, startDate, endDate, isActive, requiredDays);
        
        // ปิดหน้าต่างโต้ตอบ
        $('#academicYearModal').modal('hide');
        document.body.removeChild(dialog);
    });
    
    // แสดงหน้าต่างโต้ตอบ
    $('#academicYearModal').modal('show');
}

/**
 * บันทึกปีการศึกษาใหม่
 */
function saveAcademicYear(year, semester, startDate, endDate, isActive, requiredDays) {
    // แสดง loading
    showLoadingIndicator();
    
    // ส่งข้อมูลไปบันทึกที่เซิร์ฟเวอร์
    fetch('api/academic_year.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            year: year,
            semester: semester,
            start_date: startDate,
            end_date: endDate,
            is_active: isActive ? 1 : 0,
            required_attendance_days: requiredDays
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccessMessage('เพิ่มปีการศึกษาใหม่เรียบร้อยแล้ว');
            // อัปเดตตัวเลือกในรายการเลือกปีการศึกษา
            updateAcademicYearOptions(year, semester, isActive);
        } else {
            showErrorMessage(result.message || 'เกิดข้อผิดพลาดในการเพิ่มปีการศึกษา');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
    })
    .finally(() => {
        hideLoadingIndicator();
    });
}

/**
 * อัปเดตตัวเลือกในรายการเลือกปีการศึกษา
 */
function updateAcademicYearOptions(year, semester, isActive) {
    // เพิ่มตัวเลือกปีการศึกษาใหม่ในรายการเลือก
    const yearSelect = document.getElementById('current_academic_year');
    const yearOption = document.createElement('option');
    yearOption.value = year;
    yearOption.textContent = `${year} (${parseInt(year) - 543})`;
    
    // หากเป็นปีการศึกษาปัจจุบัน ให้เลือกตัวเลือกนี้
    if (isActive) {
        yearOption.selected = true;
    }
    
    // เพิ่มตัวเลือกลงในรายการเลือก
    yearSelect.appendChild(yearOption);
    
    // ถ้าเป็นปีการศึกษาปัจจุบัน ให้อัปเดตภาคเรียนด้วย
    if (isActive) {
        const semesterSelect = document.getElementById('current_semester');
        semesterSelect.innerHTML = ''; // ล้างตัวเลือกเดิม
        
        const semesterOption = document.createElement('option');
        semesterOption.value = semester;
        semesterOption.textContent = `ภาคเรียนที่ ${semester}/${year}`;
        semesterOption.selected = true;
        
        semesterSelect.appendChild(semesterOption);
        
        // อัปเดตวันที่เริ่มต้นและสิ้นสุดภาคเรียน
        document.getElementById('semester_start_date').value = document.getElementById('new_start_date').value;
        document.getElementById('semester_end_date').value = document.getElementById('new_end_date').value;
        
        // ทำเครื่องหมายว่ามีการเปลี่ยนแปลง
        markSettingsModified();
    }
}

/**
 * แสดงหน้าต่างโต้ตอบสำหรับการกู้คืนข้อมูล
 */
function showRestoreDialog() {
    // สร้างหน้าต่างโต้ตอบแบบง่าย
    const dialog = document.createElement('div');
    dialog.className = 'modal fade';
    dialog.id = 'restoreModal';
    dialog.setAttribute('tabindex', '-1');
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-labelledby', 'restoreModalLabel');
    dialog.setAttribute('aria-hidden', 'true');
    
    dialog.innerHTML = `
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="restoreModalLabel">กู้คืนข้อมูล</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>คำเตือน!</strong> การกู้คืนข้อมูลจะเขียนทับข้อมูลปัจจุบันทั้งหมด กรุณาตรวจสอบให้แน่ใจว่าคุณต้องการกู้คืนข้อมูลจริงๆ
                    </div>
                    <div class="form-group">
                        <label for="backup-file">เลือกไฟล์สำรองข้อมูล</label>
                        <input type="file" class="form-control-file" id="backup-file" accept=".sql,.gz,.zip">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-danger" id="restore-confirm">กู้คืนข้อมูล</button>
                </div>
            </div>
        </div>
    `;
    
    // เพิ่มหน้าต่างโต้ตอบลงใน DOM
    document.body.appendChild(dialog);
    
    // ตั้งค่า event listener สำหรับปุ่มยืนยัน
    document.getElementById('restore-confirm').addEventListener('click', function() {
        const fileInput = document.getElementById('backup-file');
        if (fileInput.files.length === 0) {
            showErrorMessage('กรุณาเลือกไฟล์สำรองข้อมูล');
            return;
        }
        
        // สร้าง FormData สำหรับอัปโหลดไฟล์
        const formData = new FormData();
        formData.append('backup_file', fileInput.files[0]);
        
        // ส่งไฟล์ไปกู้คืนที่เซิร์ฟเวอร์
        restoreDatabase(formData);
        
        // ปิดหน้าต่างโต้ตอบ
        $('#restoreModal').modal('hide');
        document.body.removeChild(dialog);
    });
    
    // แสดงหน้าต่างโต้ตอบ
    $('#restoreModal').modal('show');
}

/**
 * ทำเครื่องหมายว่ามีการแก้ไขการตั้งค่า
 */
function markSettingsModified() {
    const saveButton = document.querySelector('.save-button');
    if (saveButton) {
        saveButton.classList.add('btn-warning');
        saveButton.innerHTML = `
            <span class="material-icons">warning</span>
            บันทึกการเปลี่ยนแปลง
        `;
    }
    
    // เพิ่ม event listener เพื่อยืนยันก่อนออกจากหน้าหากมีการเปลี่ยนแปลง
    window.onbeforeunload = function() {
        return 'คุณมีการเปลี่ยนแปลงการตั้งค่าที่ยังไม่ได้บันทึก ต้องการออกจากหน้านี้หรือไม่?';
    };
}

/**
 * บันทึกการตั้งค่า
 */
function saveSettings() {
    // รวบรวมข้อมูลการตั้งค่า
    const settingsData = collectSettingsData();
    
    // แสดง loading
    showLoadingIndicator();
    
    // ส่งข้อมูลไปบันทึกที่เซิร์ฟเวอร์
    fetch('api/settings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(settingsData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccessMessage('บันทึกการตั้งค่าเรียบร้อยแล้ว');
            resetModifiedState();
        } else {
            showErrorMessage(result.message || 'เกิดข้อผิดพลาดในการบันทึกการตั้งค่า');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
    })
    .finally(() => {
        hideLoadingIndicator();
    });
}

/**
 * สำรองฐานข้อมูล
 */
function backupDatabase() {
    // แสดง loading
    showLoadingIndicator();
    
    // ส่งคำขอสำรองข้อมูลไปยังเซิร์ฟเวอร์
    fetch('api/backup.php', {
        method: 'POST',
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccessMessage('สำรองข้อมูลเรียบร้อยแล้ว: ' + result.backup_file);
            // เปิดหน้าต่างให้ดาวน์โหลดไฟล์สำรองข้อมูล
            if (result.download_url) {
                window.open(result.download_url, '_blank');
            }
        } else {
            showErrorMessage(result.message || 'เกิดข้อผิดพลาดในการสำรองข้อมูล');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
    })
    .finally(() => {
        hideLoadingIndicator();
    });
}

/**
 * กู้คืนฐานข้อมูล
 */
function restoreDatabase(formData) {
    // แสดง loading
    showLoadingIndicator();
    
    // ส่งไฟล์สำรองข้อมูลไปยังเซิร์ฟเวอร์เพื่อกู้คืน
    fetch('api/restore.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccessMessage('กู้คืนข้อมูลเรียบร้อยแล้ว จะรีเฟรชหน้าในอีก 3 วินาที...');
            // รีเฟรชหน้าหลังจากกู้คืนเสร็จ
            setTimeout(function() {
                window.location.reload();
            }, 3000);
        } else {
            showErrorMessage(result.message || 'เกิดข้อผิดพลาดในการกู้คืนข้อมูล');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
    })
    .finally(() => {
        hideLoadingIndicator();
    });
}

/**
 * ส่ง SMS ทดสอบ
 */
function sendTestSms() {
    const phoneNumber = document.getElementById('test_phone_number').value;
    const message = document.getElementById('test_sms_message').value;
    
    if (!phoneNumber) {
        showErrorMessage('กรุณาระบุเบอร์โทรศัพท์ทดสอบ');
        return;
    }
    
    if (!message) {
        showErrorMessage('กรุณาระบุข้อความทดสอบ');
        return;
    }
    
    // แสดง loading
    showLoadingIndicator();
    
    // ส่งข้อมูลไปทดสอบที่เซิร์ฟเวอร์
    fetch('api/test_sms.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            phone_number: phoneNumber,
            message: message,
            provider: document.getElementById('sms_provider').value,
            api_key: document.getElementById('sms_api_key').value,
            api_secret: document.getElementById('sms_api_secret').value,
            api_url: document.getElementById('sms_api_url').value,
            sender_id: document.getElementById('sms_sender_id').value,
            use_unicode: document.getElementById('sms_use_unicode').checked
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccessMessage('ส่ง SMS ทดสอบเรียบร้อยแล้ว');
        } else {
            showErrorMessage(result.message || 'เกิดข้อผิดพลาดในการส่ง SMS');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
    })
    .finally(() => {
        hideLoadingIndicator();
    });
}

/**
 * อัปเดตการตั้งค่า LIFF
 */
function updateLiffSettings() {
    const liffId = document.getElementById('liff_id').value;
    const liffType = document.getElementById('liff_type').value;
    const liffUrl = document.getElementById('liff_url').value;
    
    if (!liffId) {
        showErrorMessage('กรุณาระบุ LIFF ID');
        return;
    }
    
    if (!liffUrl) {
        showErrorMessage('กรุณาระบุ LIFF URL');
        return;
    }
    
    // แสดง loading
    showLoadingIndicator();
    
    // ส่งข้อมูลไปอัปเดตที่เซิร์ฟเวอร์
    fetch('api/update_liff.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            liff_id: liffId,
            liff_type: liffType,
            liff_url: liffUrl
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccessMessage('อัปเดตการตั้งค่า LIFF เรียบร้อยแล้ว');
        } else {
            showErrorMessage(result.message || 'เกิดข้อผิดพลาดในการอัปเดตการตั้งค่า LIFF');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
    })
    .finally(() => {
        hideLoadingIndicator();
    });
}

/**
 * อัปเดตการตั้งค่า Rich Menu
 */
function updateRichMenuSettings() {
    // รวบรวมข้อมูล Rich Menu
    const richMenuData = {
        parent: {
            name: document.getElementById('parent_rich_menu_name').value,
            id: document.getElementById('parent_rich_menu_id').value
        },
        student: {
            name: document.getElementById('student_rich_menu_name').value,
            id: document.getElementById('student_rich_menu_id').value
        },
        teacher: {
            name: document.getElementById('teacher_rich_menu_name').value,
            id: document.getElementById('teacher_rich_menu_id').value
        },
        enable: document.getElementById('enable_rich_menu').checked
    };
    
    // แสดง loading
    showLoadingIndicator();
    
    // ส่งข้อมูลไปอัปเดตที่เซิร์ฟเวอร์
    fetch('api/update_rich_menu.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(richMenuData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccessMessage('อัปเดต Rich Menu เรียบร้อยแล้ว');
        } else {
            showErrorMessage(result.message || 'เกิดข้อผิดพลาดในการอัปเดต Rich Menu');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
    })
    .finally(() => {
        hideLoadingIndicator();
    });
}

/**
 * รวบรวมข้อมูลการตั้งค่าจากหน้าเว็บ
 * @returns {Object} ข้อมูลการตั้งค่าทั้งหมด
 */
function collectSettingsData() {
    // สร้างออบเจ็กต์สำหรับเก็บการตั้งค่าทั้งหมด
    const settings = {
        system: {},
        notification: {},
        attendance: {},
        gps: {},
        line: {},
        sms: {},
        webhook: {}
    };

    // รวบรวมการตั้งค่าระบบ
    settings.system = {
        system_name: document.getElementById('system_name').value,
        school_name: document.getElementById('school_name').value,
        school_code: document.getElementById('school_code').value,
        admin_registration_code: document.getElementById('admin_registration_code').value,
        school_address: document.getElementById('school_address').value,
        school_phone: document.getElementById('school_phone').value,
        school_email: document.getElementById('school_email').value,
        school_website: document.getElementById('school_website').value,
        current_academic_year: document.getElementById('current_academic_year').value,
        current_semester: document.getElementById('current_semester').value,
        semester_start_date: document.getElementById('semester_start_date').value,
        semester_end_date: document.getElementById('semester_end_date').value,
        auto_promote_students: document.getElementById('auto_promote_students').checked ? 1 : 0,
        reset_attendance_new_semester: document.getElementById('reset_attendance_new_semester').checked ? 1 : 0,
        system_language: document.getElementById('system_language').value,
        system_theme: document.getElementById('system_theme').value,
        dark_mode: document.getElementById('dark_mode').checked ? 1 : 0,
        backup_frequency: document.getElementById('backup_frequency').value,
        backup_keep_count: document.getElementById('backup_keep_count').value,
        backup_path: document.getElementById('backup_path').value
    };
    
    // ถ้าเลือกธีมกำหนดเอง ให้เก็บค่าสีด้วย
    if (settings.system.system_theme === 'custom') {
        settings.system.primary_color = document.getElementById('primary_color').value;
        settings.system.secondary_color = document.getElementById('secondary_color').value;
        settings.system.background_color = document.getElementById('background_color').value;
    }

    // รวบรวมการตั้งค่าการแจ้งเตือน
    settings.notification = {
        enable_notifications: document.getElementById('enable_notifications').checked ? 1 : 0,
        critical_notifications: document.getElementById('critical_notifications').checked ? 1 : 0,
        send_daily_summary: document.getElementById('send_daily_summary').checked ? 1 : 0,
        send_weekly_summary: document.getElementById('send_weekly_summary').checked ? 1 : 0,
        absence_threshold: document.getElementById('absence_threshold').value,
        risk_notification_frequency: document.getElementById('risk_notification_frequency').value,
        notification_time: document.getElementById('notification_time').value,
        auto_notifications: document.getElementById('auto_notifications').checked ? 1 : 0,
        risk_notification_message: document.getElementById('risk_notification_message').value,
        line_notification: document.getElementById('line_notification').checked ? 1 : 0,
        sms_notification: document.getElementById('sms_notification').checked ? 1 : 0,
        email_notification: document.getElementById('email_notification').checked ? 1 : 0,
        app_notification: document.getElementById('app_notification').checked ? 1 : 0,
        enable_bulk_notifications: document.getElementById('enable_bulk_notifications').checked ? 1 : 0,
        max_bulk_recipients: document.getElementById('max_bulk_recipients').value,
        enable_scheduled_notifications: document.getElementById('enable_scheduled_notifications').checked ? 1 : 0
    };
    
    // ถ้าเลือกจำนวนครั้งขาดแถวกำหนดเอง
    if (settings.notification.absence_threshold === 'custom') {
        settings.notification.custom_absence_threshold = document.getElementById('custom_absence_threshold').value;
    }

    // รวบรวมการตั้งค่าการเช็คชื่อ
    settings.attendance = {
        min_attendance_rate: document.getElementById('min_attendance_rate').value,
        required_attendance_days: document.getElementById('required_attendance_days').value,
        attendance_counting_period: document.getElementById('attendance_counting_period').value,
        count_weekend: document.getElementById('count_weekend').checked ? 1 : 0,
        count_holidays: document.getElementById('count_holidays').checked ? 1 : 0,
        exemption_dates: document.getElementById('exemption_dates').value,
        enable_qr: document.getElementById('enable_qr').checked ? 1 : 0,
        enable_pin: document.getElementById('enable_pin').checked ? 1 : 0,
        enable_gps: document.getElementById('enable_gps').checked ? 1 : 0,
        enable_photo: document.getElementById('enable_photo').checked ? 1 : 0,
        enable_manual: document.getElementById('enable_manual').checked ? 1 : 0,
        pin_expiration: document.getElementById('pin_expiration').value,
        pin_usage_limit: document.getElementById('pin_usage_limit').value,
        pin_length: document.getElementById('pin_length').value,
        pin_type: document.getElementById('pin_type').value,
        qr_expiration: document.getElementById('qr_expiration').value,
        qr_usage_limit: document.getElementById('qr_usage_limit').value,
        attendance_start_time: document.getElementById('attendance_start_time').value,
        attendance_end_time: document.getElementById('attendance_end_time').value,
        late_check: document.getElementById('late_check').checked ? 1 : 0,
        require_attendance_photo: document.getElementById('require_attendance_photo').checked ? 1 : 0,
        max_photo_size: document.getElementById('max_photo_size').value,
        allowed_photo_types: document.getElementById('allowed_photo_types').value
    };
    
    // ถ้าเลือกอัตราเข้าแถวกำหนดเอง
    if (settings.attendance.min_attendance_rate === 'custom') {
        settings.attendance.custom_attendance_rate = document.getElementById('custom_attendance_rate').value;
    }
    
    // ถ้าเปิดใช้งานการเช็คชื่อล่าช้า
    if (settings.attendance.late_check) {
        settings.attendance.late_check_duration = document.getElementById('late_check_duration').value;
        settings.attendance.late_check_status = document.getElementById('late_check_status').value;
        settings.attendance.late_deduct_points = document.getElementById('late_deduct_points').value;
        settings.attendance.absent_deduct_points = document.getElementById('absent_deduct_points').value;
    }

    // รวบรวมการตั้งค่า GPS
    settings.gps = {
        school_latitude: document.getElementById('school_latitude').value,
        school_longitude: document.getElementById('school_longitude').value,
        gps_radius: document.getElementById('gps_radius').value,
        gps_accuracy: document.getElementById('gps_accuracy').value,
        gps_check_interval: document.getElementById('gps_check_interval').value,
        gps_required: document.getElementById('gps_required').checked ? 1 : 0,
        gps_photo_required: document.getElementById('gps_photo_required').checked ? 1 : 0,
        gps_mock_detection: document.getElementById('gps_mock_detection').checked ? 1 : 0,
        allow_home_check: document.getElementById('allow_home_check').checked ? 1 : 0,
        allow_parent_verification: document.getElementById('allow_parent_verification').checked ? 1 : 0,
        home_check_reasons: document.getElementById('home_check_reasons').value,
        enable_multiple_locations: document.getElementById('enable_multiple_locations').checked ? 1 : 0
    };
    
    // ถ้าเลือกรัศมีกำหนดเอง
    if (settings.gps.gps_radius === 'custom') {
        settings.gps.custom_gps_radius = document.getElementById('custom_gps_radius').value;
    }
    
    // ถ้าเปิดใช้งานจุดเช็คชื่อหลายจุด
    if (settings.gps.enable_multiple_locations) {
        settings.gps.additional_locations = [];
        document.querySelectorAll('.additional-location-item').forEach(location => {
            // ตรวจสอบว่าไม่ใช่เทมเพลต
            const nameInput = location.querySelector('[name="location_name[]"]');
            if (nameInput && nameInput.value.trim() !== '') {
                settings.gps.additional_locations.push({
                    name: location.querySelector('[name="location_name[]"]').value,
                    radius: location.querySelector('[name="location_radius[]"]').value,
                    latitude: location.querySelector('[name="location_latitude[]"]').value,
                    longitude: location.querySelector('[name="location_longitude[]"]').value
                });
            }
        });
    }

    // รวบรวมการตั้งค่า LINE
    const singleLineOA = document.getElementById('single_line_oa') && document.getElementById('single_line_oa').checked;
    
    if (singleLineOA) {
        // กรณีใช้ Line OA เดียว
        settings.line = {
            single_line_oa: 1,
            line_oa_name: document.getElementById('line_oa_name').value,
            line_oa_id: document.getElementById('line_oa_id').value,
            line_channel_id: document.getElementById('line_channel_id').value,
            line_channel_secret: document.getElementById('line_channel_secret').value,
            line_access_token: document.getElementById('line_access_token').value,
            line_welcome_message: document.getElementById('line_welcome_message').value,
            liff_id: document.getElementById('liff_id').value,
            liff_type: document.getElementById('liff_type').value,
            liff_url: document.getElementById('liff_url').value
        };
    } else {
        // กรณีใช้หลาย Line OA
        settings.line = {
            single_line_oa: 0,
            // LINE OA สำหรับผู้ปกครอง
            parent_line_oa_name: document.getElementById('parent_line_oa_name').value,
            parent_line_oa_id: document.getElementById('parent_line_oa_id').value,
            parent_line_channel_id: document.getElementById('parent_line_channel_id').value,
            parent_line_channel_secret: document.getElementById('parent_line_channel_secret').value,
            parent_line_access_token: document.getElementById('parent_line_access_token').value,
            parent_line_welcome_message: document.getElementById('parent_line_welcome_message').value,
            
            // LINE OA สำหรับนักเรียน
            student_line_oa_name: document.getElementById('student_line_oa_name').value,
            student_line_oa_id: document.getElementById('student_line_oa_id').value,
            student_line_channel_id: document.getElementById('student_line_channel_id').value,
            student_line_channel_secret: document.getElementById('student_line_channel_secret').value,
            student_line_access_token: document.getElementById('student_line_access_token').value,
            student_line_welcome_message: document.getElementById('student_line_welcome_message').value,
            
            // LINE OA สำหรับครู
            teacher_line_oa_name: document.getElementById('teacher_line_oa_name').value,
            teacher_line_oa_id: document.getElementById('teacher_line_oa_id').value,
            teacher_line_channel_id: document.getElementById('teacher_line_channel_id').value,
            teacher_line_channel_secret: document.getElementById('teacher_line_channel_secret').value,
            teacher_line_access_token: document.getElementById('teacher_line_access_token').value,
            teacher_line_welcome_message: document.getElementById('teacher_line_welcome_message').value,
            
            // LIFF
            liff_id: document.getElementById('liff_id').value,
            liff_type: document.getElementById('liff_type').value,
            liff_url: document.getElementById('liff_url').value
        };
    }

    // รวบรวมการตั้งค่า SMS
    settings.sms = {
        enable_sms: document.getElementById('enable_sms').checked ? 1 : 0,
        sms_provider: document.getElementById('sms_provider').value,
        sms_api_key: document.getElementById('sms_api_key').value,
        sms_api_secret: document.getElementById('sms_api_secret').value,
        sms_api_url: document.getElementById('sms_api_url').value,
        sms_max_length: document.getElementById('sms_max_length').value,
        sms_sender_id: document.getElementById('sms_sender_id').value,
        sms_absence_template: document.getElementById('sms_absence_template').value,
        sms_use_unicode: document.getElementById('sms_use_unicode').checked ? 1 : 0,
        sms_delivery_report: document.getElementById('sms_delivery_report').checked ? 1 : 0,
        sms_daily_limit: document.getElementById('sms_daily_limit').value,
        sms_send_time: document.getElementById('sms_send_time').value
    };
    
    // ถ้าเลือกผู้ให้บริการกำหนดเอง
    if (settings.sms.sms_provider === 'custom') {
        settings.sms.custom_sms_provider_name = document.getElementById('custom_sms_provider_name').value;
    }
    
    // ถ้าเลือกช่วงเวลากำหนดเอง
    if (settings.sms.sms_send_time === 'custom') {
        settings.sms.sms_start_time = document.getElementById('sms_start_time').value;
        settings.sms.sms_end_time = document.getElementById('sms_end_time').value;
    }

    // รวบรวมการตั้งค่า Webhook
    settings.webhook = {
        enable_webhook: document.getElementById('enable_webhook').checked ? 1 : 0,
        parent_webhook_url: document.getElementById('parent_webhook_url').value,
        parent_webhook_secret: document.getElementById('parent_webhook_secret').value,
        student_webhook_url: document.getElementById('student_webhook_url').value,
        student_webhook_secret: document.getElementById('student_webhook_secret').value,
        teacher_webhook_url: document.getElementById('teacher_webhook_url').value,
        teacher_webhook_secret: document.getElementById('teacher_webhook_secret').value,
        enable_auto_reply: document.getElementById('enable_auto_reply').checked ? 1 : 0,
        initial_greeting: document.getElementById('initial_greeting').value,
        fallback_message: document.getElementById('fallback_message').value,
        enable_rich_menu: document.getElementById('enable_rich_menu').checked ? 1 : 0,
        parent_rich_menu_name: document.getElementById('parent_rich_menu_name').value,
        parent_rich_menu_id: document.getElementById('parent_rich_menu_id').value,
        student_rich_menu_name: document.getElementById('student_rich_menu_name').value,
        student_rich_menu_id: document.getElementById('student_rich_menu_id').value,
        teacher_rich_menu_name: document.getElementById('teacher_rich_menu_name').value,
        teacher_rich_menu_id: document.getElementById('teacher_rich_menu_id').value
    };
    
    // รวบรวมคำสั่งและการตอบกลับ
    settings.webhook.commands = [];
    document.querySelectorAll('#commands-container tr').forEach(row => {
        const keyInput = row.querySelector('[name="command_key[]"]');
        const replyInput = row.querySelector('[name="command_reply[]"]');
        if (keyInput && replyInput) {
            settings.webhook.commands.push({
                key: keyInput.value,
                reply: replyInput.value
            });
        }
    });

    return settings;
}

/**
 * แสดงข้อความสำเร็จ
 * @param {string} message - ข้อความที่ต้องการแสดง
 */
function showSuccessMessage(message) {
    // สร้างข้อความแจ้งเตือน
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show';
    alert.role = 'alert';
    alert.innerHTML = `
        <span class="material-icons alert-icon">check_circle</span>
        <span class="alert-message">${message}</span>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    // เพิ่มข้อความแจ้งเตือนลงในหน้า
    const alertContainer = document.querySelector('.alert-container');
    if (alertContainer) {
        alertContainer.appendChild(alert);
    } else {
        // ถ้าไม่มี container ให้สร้างและเพิ่มลงในหน้า
        const container = document.createElement('div');
        container.className = 'alert-container';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        container.appendChild(alert);
        document.body.appendChild(container);
    }
    
    // ซ่อนข้อความแจ้งเตือนหลังจาก 5 วินาที
    setTimeout(function() {
        alert.classList.remove('show');
        setTimeout(function() {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 500);
    }, 5000);
}

/**
 * แสดงข้อความข้อผิดพลาด
 * @param {string} message - ข้อความข้อผิดพลาด
 */
function showErrorMessage(message) {
    // สร้างข้อความแจ้งเตือน
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show';
    alert.role = 'alert';
    alert.innerHTML = `
        <span class="material-icons alert-icon">error</span>
        <span class="alert-message">${message}</span>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    // เพิ่มข้อความแจ้งเตือนลงในหน้า
    const alertContainer = document.querySelector('.alert-container');
    if (alertContainer) {
        alertContainer.appendChild(alert);
    } else {
        // ถ้าไม่มี container ให้สร้างและเพิ่มลงในหน้า
        const container = document.createElement('div');
        container.className = 'alert-container';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        container.appendChild(alert);
        document.body.appendChild(container);
    }
    
    // ซ่อนข้อความแจ้งเตือนหลังจาก 5 วินาที
    setTimeout(function() {
        alert.classList.remove('show');
        setTimeout(function() {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 500);
    }, 5000);
}

/**
 * แสดงตัวบ่งชี้การโหลด
 */
function showLoadingIndicator() {
    // สร้างโหลดดิ้ง
    const loading = document.createElement('div');
    loading.className = 'loading-overlay';
    loading.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">กำลังโหลด...</span>
            </div>
            <div class="loading-text mt-2">กำลังดำเนินการ...</div>
        </div>
    `;
    
    // เพิ่ม CSS สำหรับโหลดดิ้ง
    loading.style.position = 'fixed';
    loading.style.top = '0';
    loading.style.left = '0';
    loading.style.width = '100%';
    loading.style.height = '100%';
    loading.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
    loading.style.display = 'flex';
    loading.style.justifyContent = 'center';
    loading.style.alignItems = 'center';
    loading.style.zIndex = '9999';
    
    // เพิ่มโหลดดิ้งลงในหน้า
    document.body.appendChild(loading);
}

/**
 * ซ่อนตัวบ่งชี้การโหลด
 */
function hideLoadingIndicator() {
    // ลบโหลดดิ้งออกจากหน้า
    const loading = document.querySelector('.loading-overlay');
    if (loading) {
        document.body.removeChild(loading);
    }
}

/**
 * รีเซ็ตสถานะการแก้ไข
 */
function resetModifiedState() {
    const saveButton = document.querySelector('.save-button');
    if (saveButton) {
        saveButton.classList.remove('btn-warning');
        saveButton.innerHTML = `
            <span class="material-icons">save</span>
            บันทึกการตั้งค่า
        `;
    }
    
    // ลบ event listener การยืนยันก่อนออกจากหน้า
    window.onbeforeunload = null;
}