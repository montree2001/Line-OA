document.addEventListener('DOMContentLoaded', function() {
    // Inicializar funcionalidades de pestañas
    initTabs();
    
    // Inicializar temporizador PIN si existe
    initPinTimer();
    
    // Inicializar funcionalidades específicas de pestañas
    initQrScanner();
    initPinCodeTab();
    initManualTab();
    initGpsTab();
    
    // Mostrar mensajes de alerta si existen
    showAlerts();
});

// Inicializar sistema de pestañas
function initTabs() {
    const tabs = document.querySelectorAll('.tabs-container .tab');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Eliminar clase activa de todas las pestañas y contenidos
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(tc => tc.classList.remove('active'));

            // Añadir clase activa a la pestaña seleccionada y su contenido
            const tabId = this.getAttribute('data-tab');
            this.classList.add('active');
            document.getElementById(`${tabId}-tab`).classList.add('active');
            
            // Actualizar URL con el parámetro tab
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url);
            
            // Si es la pestaña QR, iniciar la cámara automáticamente
            if (tabId === 'qr-code' && document.getElementById('auto-start-camera')) {
                startCamera();
            }
        });
    });
}

// Temporizador para el PIN
function initPinTimer() {
    const pinTimerElement = document.getElementById('pinTimer');
    if (!pinTimerElement) return;
    
    let timerParts = pinTimerElement.textContent.split(':');
    let minutes = parseInt(timerParts[0], 10);
    let seconds = parseInt(timerParts[1], 10);
    
    if (isNaN(minutes) || isNaN(seconds)) return;
    
    // Actualizar el temporizador cada segundo
    const timerInterval = setInterval(function() {
        if (seconds <= 0) {
            if (minutes <= 0) {
                clearInterval(timerInterval);
                pinTimerElement.textContent = '00:00';
                document.getElementById('currentPin').textContent = '----';
                
                // Mostrar mensaje de expiración
                const pinDisplay = document.querySelector('.pin-display');
                if (pinDisplay) {
                    const expiredMessage = document.createElement('div');
                    expiredMessage.className = 'pin-expired';
                    expiredMessage.textContent = 'รหัส PIN หมดอายุแล้ว กรุณาสร้างรหัสใหม่';
                    pinDisplay.appendChild(expiredMessage);
                }
                
                return;
            }
            minutes--;
            seconds = 59;
        } else {
            seconds--;
        }
        
        pinTimerElement.textContent = 
            (minutes < 10 ? '0' + minutes : minutes) + ':' + 
            (seconds < 10 ? '0' + seconds : seconds);
    }, 1000);
}

// Funcionalidad de la cámara para escaneo QR
function initQrScanner() {
    const qrVideo = document.getElementById('qr-video');
    if (!qrVideo) return;
    
    // Verificar si ya existe un escáner HTML5
    if (typeof Html5QrcodeScanner !== 'undefined') {
        const scannerElement = document.getElementById('qr-reader');
        if (scannerElement) {
            try {
                const html5QrcodeScanner = new Html5QrcodeScanner(
                    "qr-reader", 
                    { 
                        fps: 10, 
                        qrbox: 250,
                        rememberLastUsedCamera: true 
                    }
                );
                
                html5QrcodeScanner.render((decodedText) => {
                    // Procesar QR escaneado
                    processQrCode(decodedText);
                    
                    // Opcional: detener el escáner después de un escaneo exitoso
                    // html5QrcodeScanner.clear();
                });
            } catch (error) {
                console.error("Error initializing QR scanner:", error);
            }
        }
    }
}

// Procesar un código QR escaneado
function processQrCode(qrData) {
    try {
        // Intentar analizar los datos JSON del QR
        const qrInfo = JSON.parse(qrData);
        
        if (qrInfo.type === 'student-check-in' && qrInfo.student_id) {
            // Mostrar información del estudiante y confirmación
            confirmStudentAttendance(qrInfo);
        } else {
            showScanError("รูปแบบ QR Code ไม่ถูกต้อง");
        }
    } catch (e) {
        // Si no es JSON válido, intentar otro formato
        if (qrData.startsWith('STP:')) {
            // Formato personalizado (ejemplo: STP:STUDENT_ID:TIMESTAMP)
            const parts = qrData.split(':');
            if (parts.length >= 2) {
                const studentId = parts[1];
                confirmStudentAttendance({
                    student_id: studentId,
                    type: 'student-check-in'
                });
            } else {
                showScanError("รูปแบบ QR Code ไม่ถูกต้อง");
            }
        } else {
            showScanError("ไม่สามารถอ่าน QR Code นี้ได้");
        }
    }
}

// Mostrar error de escaneo
function showScanError(message) {
    const scanResultContainer = document.getElementById('scan-result-container');
    if (!scanResultContainer) return;
    
    scanResultContainer.innerHTML = `
        <div class="scan-result error">
            <div class="scan-icon error">
                <span class="material-icons">error</span>
            </div>
            <div class="scan-info">
                <div class="scan-message">${message}</div>
            </div>
        </div>
    `;
    
    document.getElementById('scan-result-empty').style.display = 'none';
}

// Confirmar asistencia de estudiante después de escaneo QR
function confirmStudentAttendance(studentInfo) {
    // Hacer solicitud AJAX para obtener información del estudiante
    fetch(`ajax/get_student.php?student_id=${studentInfo.student_id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar información del estudiante
                displayStudentInfo(data.student);
                
                // Registrar asistencia automáticamente
                recordAttendance(studentInfo.student_id, 'QR_Code');
            } else {
                showScanError(data.error || "ไม่พบข้อมูลนักเรียน");
            }
        })
        .catch(error => {
            console.error("Error fetching student data:", error);
            showScanError("เกิดข้อผิดพลาดในการดึงข้อมูล");
        });
}

// Mostrar información del estudiante después de escaneo exitoso
function displayStudentInfo(student) {
    const scanResultContainer = document.getElementById('scan-result-container');
    if (!scanResultContainer) return;
    
    // Formato de hora actual
    const now = new Date();
    const timeString = now.toLocaleTimeString('th-TH', {hour: '2-digit', minute:'2-digit'});
    
    scanResultContainer.innerHTML = `
        <div class="scan-result success">
            <div class="scan-icon success">
                <span class="material-icons">check_circle</span>
            </div>
            <div class="scan-info">
                <div class="scan-title">บันทึกการเช็คชื่อสำเร็จ</div>
                <div class="student-info">
                    <div class="student-name">${student.title}${student.first_name} ${student.last_name}</div>
                    <div class="student-id">รหัส ${student.student_code}</div>
                    <div class="student-class">${student.class}</div>
                </div>
                <div class="scan-time">เวลา: ${timeString} น.</div>
            </div>
        </div>
    `;
    
    document.getElementById('scan-result-empty').style.display = 'none';
    
    // Actualizar historial de escaneo
    updateScanHistory(student, timeString);
}

// Actualizar historial de escaneo
function updateScanHistory(student, timeString) {
    const scanHistory = document.getElementById('scan-history');
    if (!scanHistory) return;
    
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>${timeString}</td>
        <td>${student.title}${student.first_name} ${student.last_name}</td>
        <td>${student.class}</td>
        <td>QR Code</td>
        <td><span class="status-badge success">มาเรียน</span></td>
    `;
    
    scanHistory.prepend(newRow);
}

// Registrar asistencia mediante AJAX
function recordAttendance(studentId, method, status = 'present') {
    const formData = new FormData();
    formData.append('action', 'record_attendance');
    formData.append('student_id', studentId);
    formData.append('method', method);
    formData.append('status', status);
    
    fetch('ajax/attendance_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log("Attendance recorded:", data);
    })
    .catch(error => {
        console.error("Error recording attendance:", error);
    });
}

// Inicializar pestaña de código PIN
function initPinCodeTab() {
    // Manejar selección de casillas de verificación
    const checkboxes = document.querySelectorAll('input[name="attendance[]"]');
    if (checkboxes.length === 0) return;
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const row = this.closest('tr');
            if (this.checked) {
                row.classList.add('selected-row');
            } else {
                row.classList.remove('selected-row');
            }
            
            updateAttendanceSummary();
        });
    });
    
    // Manejar cambios en selects de estado
    const statusSelects = document.querySelectorAll('select.form-control');
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            updateAttendanceSummary();
        });
    });
    
    // Actualizar resumen inicialmente
    updateAttendanceSummary();
}

// Actualizar resumen de asistencia
function updateAttendanceSummary() {
    const statusSelects = document.querySelectorAll('select.form-control');
    let presentCount = 0;
    let lateCount = 0;
    let absentCount = 0;
    let leaveCount = 0;
    
    statusSelects.forEach(select => {
        const checkbox = select.closest('tr').querySelector('input[type="checkbox"]');
        if (checkbox && checkbox.checked) {
            switch (select.value) {
                case 'present':
                    presentCount++;
                    break;
                case 'late':
                    lateCount++;
                    break;
                case 'absent':
                    absentCount++;
                    break;
                case 'leave':
                    leaveCount++;
                    break;
            }
        }
    });
    
    // Actualizar contadores en la interfaz
    const presentElement = document.querySelector('.attendance-summary .attendance-stat:nth-child(1) .attendance-stat-value');
    const lateElement = document.querySelector('.attendance-summary .attendance-stat:nth-child(2) .attendance-stat-value');
    const absentElement = document.querySelector('.attendance-summary .attendance-stat:nth-child(3) .attendance-stat-value');
    const leaveElement = document.querySelector('.attendance-summary .attendance-stat:nth-child(4) .attendance-stat-value');
    
    if (presentElement) presentElement.textContent = presentCount;
    if (lateElement) lateElement.textContent = lateCount;
    if (absentElement) absentElement.textContent = absentCount;
    if (leaveElement) leaveElement.textContent = leaveCount;
}

// Inicializar pestaña de asistencia manual
function initManualTab() {
    const classLevelSelect = document.getElementById('classLevel');
    const classRoomSelect = document.getElementById('classRoom');
    
    if (!classLevelSelect || !classRoomSelect) return;
    
    // Manejar cambio de nivel
    classLevelSelect.addEventListener('change', function() {
        // Limpiar y cargar nuevas opciones de sala
        fetchClassRooms(this.value);
    });
}

// Obtener salas de clase disponibles según nivel
function fetchClassRooms(level) {
    if (!level) return;
    
    const classRoomSelect = document.getElementById('classRoom');
    if (!classRoomSelect) return;
    
    // Limpiar opciones actuales excepto la primera
    while (classRoomSelect.options.length > 1) {
        classRoomSelect.remove(1);
    }
    
    // Obtener nuevas opciones de sala
    fetch(`ajax/get_class_rooms.php?level=${level}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.rooms) {
                data.rooms.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.group_number;
                    option.textContent = room.group_number;
                    classRoomSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error("Error fetching class rooms:", error);
        });
}

// Cargar estudiantes de una clase específica
// ฟังก์ชันโหลดรายชื่อนักเรียนที่ปรับปรุงใหม่
function loadClassStudents() {
    const classLevel = document.getElementById('classLevel').value;
    const classRoom = document.getElementById('classRoom').value;
    const attendanceDate = document.getElementById('attendanceDate').value;
    
    if (!classLevel || !classRoom || !attendanceDate) {
        alert('กรุณาเลือกระดับชั้น ห้องเรียน และวันที่');
        return;
    }
    
    // แสดง loading indicator
    const studentList = document.getElementById('student-list');
    if (studentList) {
        studentList.innerHTML = '<tr><td colspan="6" class="text-center">กำลังโหลดข้อมูล...</td></tr>';
    }
    
    // ใช้ absolute path หรือ path ที่ถูกต้องตามโครงสร้างไดเรกทอรี
    const ajaxUrl = `ajax/get_class_students.php?level=${encodeURIComponent(classLevel)}&room=${encodeURIComponent(classRoom)}&date=${encodeURIComponent(attendanceDate)}`;
    
    console.log("Fetching data from:", ajaxUrl); // Debug log
    
    // ดึงข้อมูลนักเรียน
    fetch(ajaxUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("Received data:", data); // Debug log
            
            if (data.success && studentList) {
                if (data.students && data.students.length > 0) {
                    renderStudentList(data.students, studentList);
                    
                    // อัปเดตหัวข้อ
                    const title = document.querySelector('.manual-attendance-form h3');
                    if (title) {
                        title.textContent = `รายชื่อนักเรียนชั้น ${classLevel}/${classRoom}`;
                    }
                    
                    // อัปเดตวันที่
                    const dateElement = document.querySelector('.manual-attendance-form p');
                    if (dateElement) {
                        const displayDate = new Date(attendanceDate);
                        // แปลงปีเป็นพุทธศักราช
                        const thaiYear = displayDate.getFullYear() + 543;
                        const displayDateStr = `${displayDate.getDate()}/${displayDate.getMonth() + 1}/${thaiYear}`;
                        dateElement.textContent = `วันที่ ${displayDateStr}`;
                    }
                    
                    // เริ่มการทำงานของ event listeners
                    initPinCodeTab();
                } else {
                    studentList.innerHTML = '<tr><td colspan="6" class="text-center">ไม่พบข้อมูลนักเรียนในห้องเรียนนี้</td></tr>';
                }
            } else {
                studentList.innerHTML = `<tr><td colspan="6" class="text-center">เกิดข้อผิดพลาด: ${data.error || 'ไม่สามารถดึงข้อมูลได้'}</td></tr>`;
            }
        })
        .catch(error => {
            console.error("Error loading students:", error);
            if (studentList) {
                studentList.innerHTML = `<tr><td colspan="6" class="text-center">เกิดข้อผิดพลาดในการโหลดข้อมูล: ${error.message}</td></tr>`;
            }
        });
}

// ฟังก์ชันสำหรับแสดงรายชื่อนักเรียน
function renderStudentList(students, container) {
    if (!students || students.length === 0) {
        container.innerHTML = '<tr><td colspan="6" class="text-center">ไม่พบข้อมูลนักเรียน</td></tr>';
        return;
    }
    
    let html = '';
    students.forEach((student, index) => {
        // ตรวจสอบว่ามีข้อมูลที่จำเป็นหรือไม่
        const studentName = student.title ? 
            `${student.title}${student.first_name} ${student.last_name}` : 
            `${student.first_name} ${student.last_name}`;
            
        const studentId = student.student_id || '';
        const studentCode = student.student_code || '-';
        const attendanceStatus = student.attendance_status || 'absent';
        const remarks = student.remarks || '';
        
        html += `
            <tr>
                <td>${index + 1}</td>
                <td>
                    <input type="checkbox" name="manual_attendance[]" value="${studentId}" ${attendanceStatus !== 'absent' ? 'checked' : ''}>
                </td>
                <td>${studentName}</td>
                <td>${studentCode}</td>
                <td>
                    <select class="form-control form-control-sm" name="attendance[${studentId}][status]">
                        <option value="present" ${attendanceStatus === 'present' ? 'selected' : ''}>มาเรียน</option>
                        <option value="late" ${attendanceStatus === 'late' ? 'selected' : ''}>มาสาย</option>
                        <option value="absent" ${attendanceStatus === 'absent' ? 'selected' : ''}>ขาดเรียน</option>
                        <option value="leave" ${attendanceStatus === 'leave' ? 'selected' : ''}>ลา</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="attendance[${studentId}][remarks]" 
                           placeholder="หมายเหตุ" value="${remarks}">
                </td>
            </tr>
        `;
    });
    
    container.innerHTML = html;
    
    // อัปเดตข้อมูลสรุป
    updateAttendanceSummary();
}

// Renderizar lista de estudiantes
function renderStudentList(students, container) {
    if (!students || students.length === 0) {
        container.innerHTML = '<tr><td colspan="6" class="text-center">ไม่พบข้อมูลนักเรียน</td></tr>';
        return;
    }
    
    let html = '';
    students.forEach((student, index) => {
        html += `
            <tr>
                <td>${index + 1}</td>
                <td>
                    <input type="checkbox" name="manual_attendance[]" value="${student.student_id}" ${student.attendance_status ? 'checked' : ''}>
                </td>
                <td>${student.title}${student.first_name} ${student.last_name}</td>
                <td>${student.student_number || '-'}</td>
                <td>
                    <select class="form-control form-control-sm" name="attendance[${student.student_id}][status]">
                        <option value="present" ${student.attendance_status === 'present' ? 'selected' : ''}>มาเรียน</option>
                        <option value="late" ${student.attendance_status === 'late' ? 'selected' : ''}>มาสาย</option>
                        <option value="absent" ${student.attendance_status === 'absent' ? 'selected' : ''}>ขาดเรียน</option>
                        <option value="leave" ${student.attendance_status === 'leave' ? 'selected' : ''}>ลา</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="attendance[${student.student_id}][remarks]" 
                           placeholder="หมายเหตุ" value="${student.remarks || ''}">
                </td>
            </tr>
        `;
    });
    
    container.innerHTML = html;
}

// Inicializar pestaña GPS
function initGpsTab() {
    const mapElement = document.getElementById('map');
    if (!mapElement) return;
    
    // Si se está utilizando Leaflet para el mapa
    if (typeof L !== 'undefined' && mapElement) {
        // Obtener coordenadas del centro desde los inputs
        const latInput = document.getElementById('schoolLatitude');
        const lngInput = document.getElementById('schoolLongitude');
        const radiusInput = document.getElementById('allowedRadius');
        
        if (!latInput || !lngInput || !radiusInput) return;
        
        const lat = parseFloat(latInput.value) || 13.7563;
        const lng = parseFloat(lngInput.value) || 100.5018;
        const radius = parseInt(radiusInput.value) || 100;
        
        // Inicializar mapa
        const map = L.map('map').setView([lat, lng], 16);
        
        // Añadir capa de OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Añadir marcador de la escuela
        const schoolMarker = L.marker([lat, lng]).addTo(map);
        schoolMarker.bindPopup("<b>โรงเรียน</b>").openPopup();
        
        // Añadir círculo para radio permitido
        const circle = L.circle([lat, lng], {
            color: 'green',
            fillColor: '#3de05c',
            fillOpacity: 0.2,
            radius: radius
        }).addTo(map);
        
        // Actualizar mapa cuando cambian las coordenadas o el radio
        latInput.addEventListener('change', updateMapCenter);
        lngInput.addEventListener('change', updateMapCenter);
        radiusInput.addEventListener('change', updateMapRadius);
        
        function updateMapCenter() {
            const newLat = parseFloat(latInput.value) || lat;
            const newLng = parseFloat(lngInput.value) || lng;
            map.setView([newLat, newLng], 16);
            schoolMarker.setLatLng([newLat, newLng]);
            circle.setLatLng([newLat, newLng]);
        }
        
        function updateMapRadius() {
            const newRadius = parseInt(radiusInput.value) || radius;
            circle.setRadius(newRadius);
            document.getElementById('radius-display').textContent = newRadius;
        }
    }
}

// Funciones para acciones de la interfaz

// Iniciar cámara para escaneo QR
function startCamera() {
    const videoElement = document.getElementById('qr-video');
    const placeholderElement = document.getElementById('camera-placeholder');
    
    if (!videoElement || !placeholderElement) return;

    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function(stream) {
                videoElement.srcObject = stream;
                videoElement.style.display = 'block';
                placeholderElement.style.display = 'none';
                videoElement.play();
                
                // Iniciar escaneo de QR
                startQrScanner(videoElement);
            })
            .catch(function(error) {
                console.error("Error accessing camera:", error);
                alert('ไม่สามารถเปิดกล้องได้ กรุณาตรวจสอบสิทธิ์การใช้งานกล้อง');
            });
    } else {
        alert('เบราว์เซอร์ของคุณไม่สนับสนุนการใช้งานกล้อง');
    }
}

// Iniciar escáner QR usando jsQR o librería similar
function startQrScanner(videoElement) {
    // Si se está utilizando jsQR
    if (typeof jsQR !== 'undefined') {
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        let scanning = true;
        
        function scanQR() {
            if (videoElement.readyState === videoElement.HAVE_ENOUGH_DATA && scanning) {
                canvas.height = videoElement.videoHeight;
                canvas.width = videoElement.videoWidth;
                context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
                
                const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height);
                
                if (code) {
                    console.log("QR Code found:", code.data);
                    processQrCode(code.data);
                    // Desactivar escaneo temporalmente después de encontrar un código
                    scanning = false;
                    setTimeout(() => { scanning = true; }, 3000); // Reactivar después de 3 segundos
                }
            }
            
            requestAnimationFrame(scanQR);
        }
        
        scanQR();
    }
}

// Activar/desactivar flash
function toggleFlash() {
    const videoElement = document.getElementById('qr-video');
    if (!videoElement || !videoElement.srcObject) {
        alert('กรุณาเปิดกล้องก่อนใช้แฟลช');
        return;
    }
    
    const track = videoElement.srcObject.getVideoTracks()[0];
    if (!track) return;
    
    try {
        const capabilities = track.getCapabilities();
        // Verificar si la cámara admite flash
        if (capabilities.torch) {
            const flashActive = track.getConstraints().advanced?.find(c => c.torch === true);
            track.applyConstraints({
                advanced: [{ torch: !flashActive }]
            })
            .then(() => {
                const flashButton = document.querySelector('.btn-secondary .material-icons');
                if (flashButton) {
                    flashButton.textContent = !flashActive ? 'flash_off' : 'flash_on';
                }
            })
            .catch(err => {
                console.error("Error toggling flash:", err);
                alert('ไม่สามารถควบคุมแฟลชได้');
            });
        } else {
            alert('กล้องนี้ไม่สนับสนุนการใช้งานแฟลช');
        }
    } catch (error) {
        console.error("Error checking flash capabilities:", error);
        alert('ไม่สามารถตรวจสอบความสามารถแฟลชได้');
    }
}

// Generar PIN nuevo
function generatePin() {
    const formData = new FormData();
    formData.append('ajax_generate_pin', '1');
    
    // Añadir ID de clase si está disponible
    const classFilter = document.getElementById('pinClassFilter');
    if (classFilter && classFilter.value) {
        formData.append('class_id', classFilter.value);
    }
    
    fetch('check_attendance.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar UI
            const pinElement = document.getElementById('currentPin');
            const timerElement = document.getElementById('pinTimer');
            
            if (pinElement) pinElement.textContent = data.pin;
            
            if (timerElement) {
                const minutes = Math.floor(data.expiration_minutes);
                timerElement.textContent = `${minutes < 10 ? '0' + minutes : minutes}:00`;
                
                // Reiniciar temporizador
                initPinTimer();
            }
            
            // Eliminar mensaje de expiración si existe
            const expiredMessage = document.querySelector('.pin-expired');
            if (expiredMessage) {
                expiredMessage.remove();
            }
            
            // Opcional: mostrar mensaje de éxito
            showToast('สร้างรหัส PIN ใหม่เรียบร้อย');
        } else {
            alert(data.error || 'เกิดข้อผิดพลาดในการสร้างรหัส PIN');
        }
    })
    .catch(error => {
        console.error("Error generating PIN:", error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    });
}

// Copiar PIN al portapapeles
function copyPin() {
    const pin = document.getElementById('currentPin').textContent;
    if (pin === '----') {
        alert('ไม่มีรหัส PIN ที่ใช้งานได้');
        return;
    }
    
    navigator.clipboard.writeText(pin)
        .then(() => showToast('คัดลอกรหัส PIN เรียบร้อย'))
        .catch(err => {
            console.error("Error copying PIN:", err);
            alert('ไม่สามารถคัดลอกรหัสได้');
        });
}

// Verificar posición actual GPS
function testGpsLocation() {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const accuracy = position.coords.accuracy;
                
                // Calcular distancia a la escuela
                const schoolLat = parseFloat(document.getElementById('schoolLatitude').value);
                const schoolLng = parseFloat(document.getElementById('schoolLongitude').value);
                const distance = calculateDistance(lat, lng, schoolLat, schoolLng);
                
                // Mostrar resultados
                alert(`ตำแหน่งปัจจุบัน:
ละติจูด: ${lat.toFixed(6)}
ลองจิจูด: ${lng.toFixed(6)}
ความแม่นยำ: ±${accuracy.toFixed(0)} เมตร
ระยะห่างจากโรงเรียน: ${distance.toFixed(0)} เมตร`);
                
                // Si está utilizando un mapa para visualizar
                updateLocationOnMap(lat, lng);
            },
            function(error) {
                alert(`ไม่สามารถระบุตำแหน่งได้: ${getLocationErrorMessage(error.code)}`);
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    } else {
        alert('เบราว์เซอร์ของคุณไม่สนับสนุน GPS');
    }
}

// Obtener mensaje de error por código
function getLocationErrorMessage(code) {
    switch(code) {
        case 1: return "ผู้ใช้ไม่อนุญาตให้เข้าถึงตำแหน่ง";
        case 2: return "ไม่สามารถรับตำแหน่งได้";
        case 3: return "หมดเวลาในการรับตำแหน่ง";
        default: return "เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ";
    }
}

// Calcular distancia entre dos coordenadas (fórmula de Haversine)
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371000; // Radio de la Tierra en metros
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
        Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

// Actualizar configuración GPS
function updateGpsSettings() {
    const latitude = document.getElementById('schoolLatitude').value;
    const longitude = document.getElementById('schoolLongitude').value;
    const radius = document.getElementById('allowedRadius').value;
    
    if (!latitude || !longitude || !radius) {
        alert('กรุณากรอกข้อมูลให้ครบถ้วน');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'update_gps_settings');
    formData.append('latitude', latitude);
    formData.append('longitude', longitude);
    formData.append('radius', radius);
    
    fetch('ajax/settings_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar UI
            const radiusDisplay = document.getElementById('radius-display');
            if (radiusDisplay) radiusDisplay.textContent = radius;
            
            showToast('บันทึกการตั้งค่า GPS เรียบร้อย');
            
            // Actualizar mapa si está visible
            const mapElement = document.getElementById('map');
            if (mapElement && typeof updateMapCenter === 'function') {
                updateMapCenter();
            }
        } else {
            alert(data.error || 'เกิดข้อผิดพลาดในการบันทึกการตั้งค่า');
        }
    })
    .catch(error => {
        console.error("Error updating GPS settings:", error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    });
}

// Mostrar notificación toast
function showToast(message, type = 'success') {
    // Crear elemento toast si no existe
    let toast = document.getElementById('toast-message');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast-message';
        document.body.appendChild(toast);
        
        // Añadir estilos si no existen en CSS
        if (!document.getElementById('toast-styles')) {
            const style = document.createElement('style');
            style.id = 'toast-styles';
            style.textContent = `
                #toast-message {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 10px 20px;
                    border-radius: 4px;
                    color: white;
                    font-weight: bold;
                    z-index: 9999;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                }
                #toast-message.show {
                    opacity: 1;
                }
                #toast-message.success {
                    background-color: #4CAF50;
                }
                #toast-message.error {
                    background-color: #F44336;
                }
                #toast-message.warning {
                    background-color: #FF9800;
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    // Configurar mensaje y tipo
    toast.textContent = message;
    toast.className = type;
    
    // Mostrar toast
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    // Ocultar después de 3 segundos
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Seleccionar todos los estudiantes
function checkAllStudents() {
    const checkboxes = document.querySelectorAll('input[name="attendance[]"], input[name="manual_attendance[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
        const row = checkbox.closest('tr');
        if (row) row.classList.add('selected-row');
    });
    
    updateAttendanceSummary();
}

// Deseleccionar todos los estudiantes
function uncheckAllStudents() {
    const checkboxes = document.querySelectorAll('input[name="attendance[]"], input[name="manual_attendance[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        const row = checkbox.closest('tr');
        if (row) row.classList.remove('selected-row');
    });
    
    updateAttendanceSummary();
}

// Guardar asistencia (PIN)
function saveAttendance() {
    // Validar que haya estudiantes seleccionados
    const selectedStudents = document.querySelectorAll('input[name="attendance[]"]:checked');
    if (selectedStudents.length === 0) {
        alert('กรุณาเลือกนักเรียนที่ต้องการบันทึกการเช็คชื่อ');
        return;
    }
    
    // Obtener datos del formulario
    const form = document.querySelector('.pin-users-container form');
    if (!form) {
        alert('ไม่พบฟอร์มสำหรับการบันทึกข้อมูล');
        return;
    }
    
    // Enviar formulario
    form.submit();
}

// Guardar asistencia manual
function saveManualAttendance() {
    // Validar que haya estudiantes seleccionados
    const selectedStudents = document.querySelectorAll('input[name="manual_attendance[]"]:checked');
    if (selectedStudents.length === 0) {
        alert('กรุณาเลือกนักเรียนที่ต้องการบันทึกการเช็คชื่อ');
        return;
    }
    
    // Obtener datos del formulario
    const form = document.querySelector('.manual-attendance-form form');
    if (!form) {
        alert('ไม่พบฟอร์มสำหรับการบันทึกข้อมูล');
        return;
    }
    
    // Añadir campo hidden para identificar la acción
    const hiddenField = document.createElement('input');
    hiddenField.type = 'hidden';
    hiddenField.name = 'save_attendance';
    hiddenField.value = '1';
    form.appendChild(hiddenField);
    
    // Enviar formulario
    form.submit();
}

// Mostrar modales de foto y mapa
function showPhotoModal(photoId) {
    const modal = document.getElementById('photoModal');
    if (!modal) return;
    
    // Cargar imagen si es necesario
    const photoElement = modal.querySelector('.modal-photo img');
    if (photoElement) {
        photoElement.src = `uploads/attendance/${photoId}.jpg`;
    }
    
    modal.style.display = 'flex';
}

function showMapModal(mapId) {
    const modal = document.getElementById('mapModal');
    if (!modal) return;
    
    // Cargar datos del mapa si es necesario
    fetch(`ajax/get_location.php?map_id=${mapId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                initModalMap(data.latitude, data.longitude, data.school_lat, data.school_lng);
            }
        })
        .catch(error => {
            console.error("Error loading map data:", error);
        });
    
    modal.style.display = 'flex';
}

// Inicializar mapa en modal
function initModalMap(studentLat, studentLng, schoolLat, schoolLng) {
    const mapContainer = document.getElementById('modalMapContainer');
    if (!mapContainer || typeof L === 'undefined') return;
    
    // Limpiar mapa anterior si existe
    mapContainer.innerHTML = '';
    
    // Crear contenedor de mapa
    const mapElement = document.createElement('div');
    mapElement.id = 'modalMap';
    mapElement.style.width = '100%';
    mapElement.style.height = '400px';
    mapContainer.appendChild(mapElement);
    
    // Inicializar mapa
    const map = L.map('modalMap');
    
    // Añadir capa de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // Añadir marcadores
    const schoolMarker = L.marker([schoolLat, schoolLng], {
        icon: L.divIcon({
            className: 'school-marker',
            html: '<span class="material-icons">school</span>',
            iconSize: [36, 36],
            iconAnchor: [18, 18]
        })
    }).addTo(map);
    schoolMarker.bindPopup("<b>โรงเรียน</b>").openPopup();
    
    const studentMarker = L.marker([studentLat, studentLng], {
        icon: L.divIcon({
            className: 'student-marker',
            html: '<span class="material-icons">person_pin</span>',
            iconSize: [36, 36],
            iconAnchor: [18, 18]
        })
    }).addTo(map);
    studentMarker.bindPopup("<b>ตำแหน่งนักเรียน</b>").openPopup();
    
    // Dibujar línea entre escuela y estudiante
    const line = L.polyline([
        [schoolLat, schoolLng],
        [studentLat, studentLng]
    ], {
        color: 'blue',
        dashArray: '5, 10'
    }).addTo(map);
    
    // Calcular distancia
    const distance = calculateDistance(schoolLat, schoolLng, studentLat, studentLng);
    
    // Añadir etiqueta de distancia
    const midLat = (schoolLat + studentLat) / 2;
    const midLng = (schoolLng + studentLng) / 2;
    
    L.marker([midLat, midLng], {
        icon: L.divIcon({
            className: 'distance-label',
            html: `<div>${Math.round(distance)} เมตร</div>`,
            iconSize: [80, 20],
            iconAnchor: [40, 10]
        })
    }).addTo(map);
    
    // Ajustar vista para mostrar ambos marcadores
    const bounds = L.latLngBounds([
        [schoolLat, schoolLng],
        [studentLat, studentLng]
    ]);
    map.fitBounds(bounds, { padding: [50, 50] });
}

// Cerrar modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}

// Mostrar alertas si existen
function showAlerts() {
    const successAlert = document.getElementById('success-alert');
    const errorAlert = document.getElementById('error-alert');
    
    if (successAlert) {
        successAlert.style.display = 'flex';
        setTimeout(() => {
            successAlert.style.opacity = '0';
            setTimeout(() => {
                successAlert.style.display = 'none';
            }, 300);
        }, 3000);
    }
    
    if (errorAlert) {
        errorAlert.style.display = 'flex';
    }
}

// Descargar informe de asistencia
function downloadAttendanceReport() {
    // Mostrar modal con opciones de descarga si existe
    const modal = document.getElementById('downloadReportModal');
    if (modal) {
        modal.style.display = 'flex';
        return;
    }
    
    // Si no hay modal, redirigir directamente a la descarga
    window.location.href = 'reports/attendance_report.php';
}