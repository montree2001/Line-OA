/**
 * check-in.js - JavaScript สำหรับหน้าเช็คชื่อนักเรียน
 * รองรับการเช็คชื่อด้วย GPS, QR Code, PIN และการสแกน QR
 */

document.addEventListener('DOMContentLoaded', function () {
    // Tab Navigation
    const tabItems = document.querySelectorAll('.tab-item');
    const tabPanes = document.querySelectorAll('.tab-pane');

    if (tabItems.length > 0) {
        tabItems.forEach(item => {
            item.addEventListener('click', function () {
                const tabId = this.getAttribute('data-tab');

                // ยกเลิกการ active ของ tabs อื่นๆ
                tabItems.forEach(tab => tab.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));

                // Active tab ที่ถูกคลิก
                this.classList.add('active');
                document.getElementById(`${tabId}-tab`).classList.add('active');

                // หยุดการสแกนถ้ากำลังสแกนอยู่และเปลี่ยนแท็บ
                if (tabId !== 'scan' && window.scanner) {
                    stopQRScanner();
                }
            });
        });
    }

    // --------------------------
    // ส่วนการเช็คชื่อด้วย GPS
    // --------------------------
    const mapContainer = document.getElementById('map');
    let map, userMarker, schoolMarker, accuracyCircle, boundaryCircle;
    let userLocation = null;

    if (mapContainer) {
        // ข้อมูล GPS จาก PHP
        const schoolLat = parseFloat(document.getElementById('school-lat').value);
        const schoolLng = parseFloat(document.getElementById('school-lng').value);
        const gpsRadius = parseInt(document.getElementById('gps-radius').value);

        // สร้างแผนที่
        map = L.map('map').setView([schoolLat, schoolLng], 15);

        // เพิ่ม OpenStreetMap layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // สร้าง marker จุดที่ตั้งโรงเรียน
        schoolMarker = L.marker([schoolLat, schoolLng], {
            icon: L.icon({
                iconUrl: 'assets/images/school-marker.png',
                iconSize: [32, 32],
                iconAnchor: [16, 32],
                popupAnchor: [0, -32]
            })
        }).addTo(map);
        schoolMarker.bindPopup("วิทยาลัยการอาชีพปราสาท");

        // สร้างวงกลมแสดงรัศมีที่อนุญาตให้เช็คชื่อได้
        boundaryCircle = L.circle([schoolLat, schoolLng], {
            radius: gpsRadius,
            color: '#06c755',
            fillColor: '#06c755',
            fillOpacity: 0.1
        }).addTo(map);

        // เริ่มต้นการค้นหาตำแหน่ง GPS
        startGPSTracking();
    }

    // ปุ่มเช็คชื่อด้วย GPS
    const checkInGpsBtn = document.getElementById('check-in-gps');
    if (checkInGpsBtn) {
        checkInGpsBtn.addEventListener('click', function () {
            if (userLocation) {
                checkInWithGPS(userLocation.lat, userLocation.lng);
            } else {
                showStatusMessage('error', 'ไม่สามารถระบุตำแหน่งของคุณได้ กรุณาลองใหม่อีกครั้ง');
            }
        });
    }

    function startGPSTracking() {
        if (navigator.geolocation) {
            const locationIcon = document.getElementById('location-icon');
            const locationStatus = document.getElementById('location-status');

            locationIcon.textContent = 'location_searching';
            locationStatus.textContent = 'กำลังค้นหาตำแหน่งของคุณ...';
            locationIcon.className = 'material-icons';
            locationStatus.className = 'status-text';

            navigator.geolocation.watchPosition(
                function (position) {
                    userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    };

                    document.getElementById('user-lat').value = userLocation.lat;
                    document.getElementById('user-lng').value = userLocation.lng;

                    updateUserMarker(userLocation);
                    checkLocationValidity(userLocation);
                },
                function (error) {
                    console.error('Error getting location:', error);

                    locationIcon.textContent = 'error';
                    locationStatus.textContent = 'ไม่สามารถระบุตำแหน่งของคุณได้ กรุณาเปิดการใช้งาน GPS';
                    locationIcon.className = 'material-icons error';
                    locationStatus.className = 'status-text error';

                    checkInGpsBtn.disabled = true;
                }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
            );
        } else {
            showStatusMessage('error', 'เบราว์เซอร์ของคุณไม่รองรับการใช้งาน GPS');
            checkInGpsBtn.disabled = true;
        }
    }

    function updateUserMarker(location) {
        const schoolLat = parseFloat(document.getElementById('school-lat').value);
        const schoolLng = parseFloat(document.getElementById('school-lng').value);

        // ลบ marker เดิมถ้ามี
        if (userMarker) map.removeLayer(userMarker);
        if (accuracyCircle) map.removeLayer(accuracyCircle);

        // สร้าง marker ใหม่
        userMarker = L.marker([location.lat, location.lng], {
            icon: L.icon({
                iconUrl: 'assets/images/user-marker.png',
                iconSize: [24, 24],
                iconAnchor: [12, 12],
                popupAnchor: [0, -12]
            })
        }).addTo(map);
        userMarker.bindPopup("ตำแหน่งของคุณ");

        // สร้างวงกลมแสดงความแม่นยำ
        accuracyCircle = L.circle([location.lat, location.lng], {
            radius: location.accuracy,
            color: '#2196F3',
            fillColor: '#2196F3',
            fillOpacity: 0.2
        }).addTo(map);

        // ปรับมุมมองแผนที่เพื่อให้เห็นทั้ง marker ของผู้ใช้และโรงเรียน
        const bounds = L.latLngBounds(
            L.latLng(location.lat, location.lng),
            L.latLng(schoolLat, schoolLng)
        );
        map.fitBounds(bounds, { padding: [50, 50] });
    }

    function checkLocationValidity(location) {
        const schoolLat = parseFloat(document.getElementById('school-lat').value);
        const schoolLng = parseFloat(document.getElementById('school-lng').value);
        const gpsRadius = parseInt(document.getElementById('gps-radius').value);

        // คำนวณระยะห่างระหว่างผู้ใช้กับโรงเรียน
        const distance = getDistance(
            location.lat, location.lng,
            schoolLat, schoolLng
        );

        const locationIcon = document.getElementById('location-icon');
        const locationStatus = document.getElementById('location-status');

        if (distance <= gpsRadius) {
            // อยู่ในรัศมีที่กำหนด
            locationIcon.textContent = 'check_circle';
            locationStatus.textContent = `คุณอยู่ในรัศมีที่กำหนด (${Math.round(distance)} เมตร จากวิทยาลัย)`;
            locationIcon.className = 'material-icons success';
            locationStatus.className = 'status-text success';

            checkInGpsBtn.disabled = false;
        } else {
            // อยู่นอกรัศมีที่กำหนด
            locationIcon.textContent = 'location_off';
            locationStatus.textContent = `คุณอยู่นอกรัศมีที่กำหนด (${Math.round(distance)} เมตร จากวิทยาลัย)`;
            locationIcon.className = 'material-icons error';
            locationStatus.className = 'status-text error';

            checkInGpsBtn.disabled = true;
        }
    }

    function getDistance(lat1, lon1, lat2, lon2) {
        // ฟังก์ชันคำนวณระยะทางระหว่างจุดสองจุดบนพื้นโลก (Haversine formula)
        const R = 6371e3; // รัศมีของโลกในหน่วยเมตร
        const φ1 = lat1 * Math.PI / 180;
        const φ2 = lat2 * Math.PI / 180;
        const Δφ = (lat2 - lat1) * Math.PI / 180;
        const Δλ = (lon2 - lon1) * Math.PI / 180;

        const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
            Math.cos(φ1) * Math.cos(φ2) *
            Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return R * c; // ระยะทางในหน่วยเมตร
    }

    function checkInWithGPS(lat, lng) {
        const studentId = document.getElementById('student-id').value;

        // แสดง loading
        checkInGpsBtn.disabled = true;
        checkInGpsBtn.innerHTML = '<span class="material-icons">hourglass_top</span> กำลังเช็คชื่อ...';

        // ส่งข้อมูลไปยัง server
        fetch('api/check_in.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                student_id: studentId,
                method: 'GPS',
                lat: lat,
                lng: lng
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showResultModal('success', 'เช็คชื่อสำเร็จ', 'คุณได้เช็คชื่อเข้าแถวเรียบร้อยแล้ว');
                } else {
                    showResultModal('error', 'เช็คชื่อไม่สำเร็จ', data.message || 'เกิดข้อผิดพลาดในการเช็คชื่อ กรุณาลองใหม่อีกครั้ง');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showResultModal('error', 'เช็คชื่อไม่สำเร็จ', 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
            })
            .finally(() => {
                checkInGpsBtn.disabled = false;
                checkInGpsBtn.innerHTML = '<span class="material-icons">gps_fixed</span> เช็คชื่อด้วย GPS';
            });
    }

    function showStatusMessage(type, message) {
        const locationIcon = document.getElementById('location-icon');
        const locationStatus = document.getElementById('location-status');

        if (type === 'error') {
            locationIcon.textContent = 'error';
            locationIcon.className = 'material-icons error';
        } else if (type === 'success') {
            locationIcon.textContent = 'check_circle';
            locationIcon.className = 'material-icons success';
        } else {
            locationIcon.textContent = 'info';
            locationIcon.className = 'material-icons';
        }

        locationStatus.textContent = message;
        locationStatus.className = type === 'error' ? 'status-text error' :
            type === 'success' ? 'status-text success' :
                'status-text';
    }

    // --------------------------
    // ส่วนการเช็คชื่อด้วย QR Code
    // --------------------------
    const generateQrBtn = document.getElementById('generate-qr');
    const checkQrStatusBtn = document.getElementById('check-qr-status');

    // แสดง QR Code ที่มีอยู่แล้ว (ถ้ามี)
    const existingQrData = document.getElementById('existing-qr-data');
    if (existingQrData && existingQrData.value) {
        try {
            const qrData = JSON.parse(existingQrData.value);
            generateQRCode(qrData);
        } catch (e) {
            console.error('Error parsing existing QR data:', e);
        }
    }

    // ปุ่มสร้าง QR Code
    if (document.getElementById('generate-qr')) {
        document.getElementById('generate-qr').addEventListener('click', function () {
            this.disabled = true;
            this.innerHTML = '<span class="material-icons">hourglass_empty</span> กำลังสร้าง QR Code...';

            var student_id = document.getElementById('student-id').value;

            // ใช้ XMLHttpRequest แทน fetch เพื่อความเข้ากันได้ที่ดีกว่า
            var xhr = new XMLHttpRequest();

            // แก้ path ให้ถูกต้อง
            xhr.open('POST', 'api/generate_qr.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');

            xhr.onload = function () {
                // ตรวจสอบว่าปุ่มยังมีอยู่หรือไม่
                var button = document.getElementById('generate-qr');
                if (!button) {
                    console.error('Button element not found');
                    return;
                }

                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        var data = JSON.parse(xhr.responseText);

                        if (data.success) {
                            // สร้าง QR Code ด้วย qrcode-generator library
                            var qr = qrcode(0, 'M');
                            qr.addData(JSON.stringify(data.qr_data));
                            qr.make();

                            // ตรวจสอบว่า qrDisplay มีอยู่จริง
                            var qrDisplay = document.getElementById('qr-display');
                            if (!qrDisplay) {
                                console.error('QR display element not found');
                                alert('เกิดข้อผิดพลาด: ไม่พบพื้นที่แสดง QR Code');
                                button.disabled = false;
                                button.innerHTML = '<span class="material-icons">qr_code</span> สร้าง QR Code';
                                return;
                            }

                            // แสดง QR Code
                            qrDisplay.innerHTML = qr.createImgTag(5);

                            // ปรับขนาดรูปภาพให้เต็มพื้นที่
                            var img = qrDisplay.querySelector('img');
                            if (img) {
                                img.style.maxWidth = '100%';
                                img.style.height = 'auto';
                            }

                            // ตรวจสอบว่า qrWrapper มีอยู่จริง
                            var qrWrapper = document.querySelector('.qr-wrapper');
                            if (!qrWrapper) {
                                console.error('QR wrapper element not found');
                                alert('เกิดข้อผิดพลาด: ไม่พบ QR wrapper');
                                button.disabled = false;
                                button.innerHTML = '<span class="material-icons">qr_code</span> สร้าง QR Code';
                                return;
                            }

                            qrWrapper.classList.add('active');

                            // ลบข้อความ placeholder ถ้ามี
                            var placeholder = qrWrapper.querySelector('.qr-placeholder');
                            if (placeholder) {
                                placeholder.remove();
                            }

                            // กำหนดเวลาหมดอายุ
                            var expireDate = new Date(data.expire_time);
                            var hours = expireDate.getHours();
                            var minutes = expireDate.getMinutes();
                            if (minutes < 10) minutes = '0' + minutes;
                            var expireTime = hours + ':' + minutes;

                            // สร้างหรืออัปเดตข้อความเวลาหมดอายุ
                            var qrExpire = qrWrapper.querySelector('.qr-expire');
                            if (!qrExpire) {
                                qrExpire = document.createElement('div');
                                qrExpire.className = 'qr-expire';
                                qrWrapper.appendChild(qrExpire);
                            }
                            qrExpire.innerHTML = '<span class="material-icons">access_time</span><span>หมดอายุเวลา ' + expireTime + ' น.</span>';

                            // เปลี่ยนปุ่มเป็นปุ่มตรวจสอบสถานะ
                            button.style.display = 'none';

                            // ตรวจสอบและสร้างปุ่มตรวจสอบสถานะ
                            if (document.getElementById('check-qr-status')) {
                                document.getElementById('check-qr-status').style.display = 'block';
                            } else {
                                // สร้างปุ่มตรวจสอบสถานะถ้ายังไม่มี
                                var newButton = document.createElement('button');
                                newButton.id = 'check-qr-status';
                                newButton.className = 'btn secondary';
                                newButton.innerHTML = '<span class="material-icons">refresh</span> ตรวจสอบสถานะ';
                                newButton.onclick = checkQRStatus;
                                // ตรวจสอบว่า button.parentNode มีอยู่จริง
                                if (button.parentNode) {
                                    button.parentNode.appendChild(newButton);
                                } else {
                                    console.error('Button parent node not found');
                                }
                            }
                        } else {
                            // แสดงข้อความแจ้งเตือนกรณีเกิดข้อผิดพลาด
                            alert(data.message || 'ไม่สามารถสร้าง QR Code ได้');
                            button.disabled = false;
                            button.innerHTML = '<span class="material-icons">qr_code</span> สร้าง QR Code';
                        }
                    } catch (e) {
                        console.error('Error parsing JSON:', e, xhr.responseText);
                        alert('เกิดข้อผิดพลาดในการประมวลผลข้อมูล: ' + e.message);
                        button.disabled = false;
                        button.innerHTML = '<span class="material-icons">qr_code</span> สร้าง QR Code';
                    }
                } else {
                    console.error('HTTP Error:', xhr.status, xhr.statusText, xhr.responseText);
                    alert('ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้: HTTP ' + xhr.status);
                    button.disabled = false;
                    button.innerHTML = '<span class="material-icons">qr_code</span> สร้าง QR Code';
                }
            };

            xhr.onerror = function () {
                console.error('Network Error');
                alert('ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                var button = document.getElementById('generate-qr');
                if (button) {
                    button.disabled = false;
                    button.innerHTML = '<span class="material-icons">qr_code</span> สร้าง QR Code';
                }
            };

            // ส่งข้อมูลเป็น JSON
            xhr.send(JSON.stringify({ student_id: student_id }));
        });
    }

    if (checkQrStatusBtn) {
        checkQrStatusBtn.addEventListener('click', checkQRStatus);
    }

    function generateQRCode(data) {
        const qrDisplay = document.getElementById('qr-display');
        if (!qrDisplay) return;

        // ล้างเนื้อหาเดิม
        qrDisplay.innerHTML = '';

        // สร้าง QR Code ด้วย qrcode-generator
        let qrString = '';
        if (typeof data === 'object') {
            qrString = JSON.stringify(data);
        } else {
            qrString = data;
        }

        const qr = qrcode(0, 'M');
        qr.addData(qrString);
        qr.make();

        // แสดง QR Code
        qrDisplay.innerHTML = qr.createImgTag(5, 10);
    }

    function checkQRStatus() {
        this.disabled = true;
        this.innerHTML = '<span class="material-icons">hourglass_top</span> กำลังตรวจสอบ...';

        const studentId = document.getElementById('student-id').value;

        fetch('api/check_attendance_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                student_id: studentId
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.is_checked_in) {
                        // เช็คชื่อแล้ว
                        let methodText = '';
                        switch (data.attendance.method) {
                            case 'GPS': methodText = 'GPS'; break;
                            case 'QR_Code': methodText = 'QR Code'; break;
                            case 'PIN': methodText = 'รหัส PIN'; break;
                            case 'Manual': methodText = 'ครูเช็คให้'; break;
                            default: methodText = data.attendance.method;
                        }

                        showResultModal('success', 'เช็คชื่อสำเร็จแล้ว',
                            `คุณได้เช็คชื่อเรียบร้อยแล้ว<br><br>` +
                            `วิธีการ: ${methodText}<br>` +
                            `เวลา: ${data.attendance.time} น.<br>` +
                            `${data.attendance.checker ? 'ผู้เช็คชื่อ: ' + data.attendance.checker + '<br>' : ''}` +
                            `${data.attendance.remarks ? 'หมายเหตุ: ' + data.attendance.remarks : ''}`,
                            true);
                    } else if (data.has_active_qr) {
                        // มี QR Code ที่ยังใช้งานได้
                        if (data.qr_info.is_expired) {
                            showResultModal('warning', 'QR Code หมดอายุ',
                                'QR Code ของคุณหมดอายุแล้ว กรุณาสร้างใหม่', false, true);
                        } else {
                            showResultModal('info', 'รอการเช็คชื่อ',
                                `QR Code ของคุณยังใช้งานได้<br><br>` +
                                `หมดอายุใน: ${data.qr_info.time_remaining_formatted}<br>` +
                                `โปรดให้ครูสแกน QR Code เพื่อเช็คชื่อ`);
                        }
                        this.disabled = false;
                        this.innerHTML = '<span class="material-icons">refresh</span> ตรวจสอบสถานะ';
                    } else {
                        // ยังไม่มีการเช็คชื่อและไม่มี QR Code
                        showResultModal('info', 'ยังไม่มีการเช็คชื่อ',
                            'คุณยังไม่ได้เช็คชื่อวันนี้<br>กรุณาสร้าง QR Code หรือใช้วิธีการอื่น', false, true);
                        this.disabled = false;
                        this.innerHTML = '<span class="material-icons">refresh</span> ตรวจสอบสถานะ';
                    }
                } else {
                    showResultModal('error', 'ตรวจสอบไม่สำเร็จ',
                        data.message || 'เกิดข้อผิดพลาดในการตรวจสอบ กรุณาลองใหม่อีกครั้ง');
                    this.disabled = false;
                    this.innerHTML = '<span class="material-icons">refresh</span> ตรวจสอบสถานะ';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showResultModal('error', 'ตรวจสอบไม่สำเร็จ',
                    'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
                this.disabled = false;
                this.innerHTML = '<span class="material-icons">refresh</span> ตรวจสอบสถานะ';
            });
    }

    // --------------------------
    // ส่วนการสแกน QR Code
    // --------------------------
    const startScanBtn = document.getElementById('start-scan');
    const stopScanBtn = document.getElementById('stop-scan');
    const qrScanner = document.getElementById('qr-scanner');
    let scanner = null;

    if (startScanBtn) {
        startScanBtn.addEventListener('click', startQRScanner);
    }

    if (stopScanBtn) {
        stopScanBtn.addEventListener('click', stopQRScanner);
    }

    function startQRScanner() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showResultModal('error', 'ไม่รองรับกล้อง', 'เบราว์เซอร์ของคุณไม่รองรับการใช้งานกล้อง');
            return;
        }

        // ขออนุญาตใช้กล้อง
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(stream => {
                // แสดงภาพจากกล้อง
                qrScanner.srcObject = stream;
                qrScanner.setAttribute('playsinline', true);
                qrScanner.play();

                // เปลี่ยนปุ่ม
                startScanBtn.style.display = 'none';
                stopScanBtn.style.display = 'block';

                // สร้างแสกนเนอร์
                window.scanner = stream;

                // ฟังก์ชันสำหรับการสแกน QR Code
                function checkForQRCode() {
                    // กำหนดขนาด canvas ตามขนาดวิดีโอ
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');

                    if (qrScanner.videoWidth && qrScanner.videoHeight) {
                        canvas.width = qrScanner.videoWidth;
                        canvas.height = qrScanner.videoHeight;

                        // วาดภาพจากวิดีโอลงบน canvas
                        context.drawImage(qrScanner, 0, 0, canvas.width, canvas.height);

                        // ดึงข้อมูลรูปภาพ
                        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);

                        try {
                            // ใช้ jsQR library สำหรับถอดรหัส QR (ต้องรวม library นี้ด้วย)
                            // const code = jsQR(imageData.data, imageData.width, imageData.height);

                            // ถ้ามี QR Code
                            // if (code) {
                            //     processQRCode(code.data);
                            // }

                            // หมายเหตุ: เนื่องจากไม่ได้รวม jsQR library จึงแสดงเฉพาะโครงสร้างตัวอย่าง
                            // ในการใช้งานจริง ต้องรวม library และแก้ comment ในส่วนนี้

                            // สมมุติว่าพบ QR Code สำหรับการทดสอบ
                            // processQRCode('{"type":"pin","pin":"1234"}');
                        } catch (error) {
                            console.error('QR Code scanning error:', error);
                        }
                    }

                    // ตรวจสอบซ้ำทุก 500ms
                    if (window.scanner) {
                        window.scanInterval = setTimeout(checkForQRCode, 500);
                    }
                }

                // เริ่มการสแกน
                checkForQRCode();

            })
            .catch(error => {
                console.error('Error accessing camera:', error);
                showResultModal('error', 'ไม่สามารถเข้าถึงกล้องได้', 'กรุณาอนุญาตการใช้งานกล้องและลองใหม่อีกครั้ง');
            });
    }

    function stopQRScanner() {
        if (window.scanInterval) {
            clearTimeout(window.scanInterval);
            window.scanInterval = null;
        }

        if (window.scanner) {
            // หยุดทุก track
            window.scanner.getTracks().forEach(track => track.stop());
            window.scanner = null;

            // ล้างวิดีโอ
            qrScanner.srcObject = null;
        }

        // เปลี่ยนปุ่ม
        startScanBtn.style.display = 'block';
        stopScanBtn.style.display = 'none';
    }

    function processQRCode(data) {
        // หยุดการสแกน
        stopQRScanner();

        try {
            // แปลงข้อมูล QR Code
            let qrData;
            try {
                qrData = JSON.parse(data);
            } catch (e) {
                qrData = { type: 'unknown', value: data };
            }

            const studentId = document.getElementById('student-id').value;

            // ตรวจสอบประเภทของ QR Code
            if (qrData.type === 'pin') {
                // กรณีเป็น PIN
                fetch('api/check_in_pin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        pin: qrData.pin
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showResultModal('success', 'เช็คชื่อสำเร็จ', 'คุณได้เช็คชื่อเข้าแถวเรียบร้อยแล้ว', true);
                        } else {
                            showResultModal('error', 'เช็คชื่อไม่สำเร็จ', data.message || 'รหัส PIN ไม่ถูกต้องหรือหมดอายุ');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showResultModal('error', 'เช็คชื่อไม่สำเร็จ', 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
                    });
            } else if (qrData.type === 'check_in') {
                // กรณีเป็น QR Code เช็คชื่อโดยตรง
                fetch('api/scan_qr_check_in.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        qr_data: data
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showResultModal('success', 'เช็คชื่อสำเร็จ', 'คุณได้เช็คชื่อเข้าแถวเรียบร้อยแล้ว', true);
                        } else {
                            showResultModal('error', 'เช็คชื่อไม่สำเร็จ', data.message || 'QR Code ไม่ถูกต้องหรือหมดอายุ');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showResultModal('error', 'เช็คชื่อไม่สำเร็จ', 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
                    });
            } else {
                // กรณี QR Code ไม่รู้จัก
                showResultModal('error', 'QR Code ไม่ถูกต้อง', 'QR Code นี้ไม่ใช่รูปแบบที่ใช้สำหรับการเช็คชื่อ');
            }
        } catch (error) {
            console.error('Error processing QR code:', error);
            showResultModal('error', 'อ่าน QR Code ไม่สำเร็จ', 'ไม่สามารถอ่านข้อมูลจาก QR Code ได้');
        }
    }

    // --------------------------
    // Modal แสดงผลการเช็คชื่อ
    // --------------------------
    const resultModal = document.getElementById('result-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalBody = document.getElementById('modal-body');
    const modalOk = document.getElementById('modal-ok');
    const closeModal = document.getElementById('close-modal');

    if (modalOk) {
        modalOk.addEventListener('click', function () {
            resultModal.style.display = 'none';


            // ถ้ามีการ reload หน้า
            if (this.dataset.reload === 'true') {
                window.location.reload();
            }

            // ถ้ามีการกลับไปหน้าหลัก
            if (this.dataset.home === 'true') {
                window.location.href = 'home.php';
            }

            // ถ้ามีการสร้าง QR ใหม่
            if (this.dataset.newqr === 'true') {
                // เปลี่ยนปุ่มตรวจสอบสถานะเป็นปุ่มสร้าง QR ใหม่
                const checkQrStatusBtn = document.getElementById('check-qr-status');
                if (checkQrStatusBtn) {
                    checkQrStatusBtn.outerHTML = `
                        <button id="generate-qr" class="btn primary">
                            <span class="material-icons">qr_code</span> สร้าง QR Code
                        </button>
                    `;

                    // เพิ่ม event listener ให้กับปุ่มใหม่
                    document.getElementById('generate-qr').addEventListener('click', function () {
                        this.disabled = true;
                        this.innerHTML = '<span class="material-icons">hourglass_top</span> กำลังสร้าง QR Code...';

                        const studentId = document.getElementById('student-id').value;

                        fetch('api/generate_qr.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                student_id: studentId
                            })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // แสดง QR Code
                                    generateQRCode(data.qr_data);

                                    // แสดงเวลาหมดอายุ
                                    const expireTime = new Date(data.expire_time);
                                    const hours = expireTime.getHours().toString().padStart(2, '0');
                                    const minutes = expireTime.getMinutes().toString().padStart(2, '0');

                                    const qrWrapper = document.querySelector('.qr-wrapper');
                                    qrWrapper.classList.add('active');

                                    const qrExpire = document.createElement('div');
                                    qrExpire.className = 'qr-expire';
                                    qrExpire.innerHTML = `
                                    <span class="material-icons">access_time</span>
                                    <span>หมดอายุเวลา ${hours}:${minutes} น.</span>
                                `;

                                    // ลบข้อความหมดอายุเดิม (ถ้ามี)
                                    const oldExpire = qrWrapper.querySelector('.qr-expire');
                                    if (oldExpire) {
                                        oldExpire.remove();
                                    }

                                    qrWrapper.appendChild(qrExpire);

                                    // เปลี่ยนปุ่มเป็นปุ่มตรวจสอบสถานะ
                                    this.outerHTML = `
                                    <button id="check-qr-status" class="btn secondary" onclick="checkQRStatus()">
                                        <span class="material-icons">refresh</span> ตรวจสอบสถานะ
                                    </button>
                                `;

                                } else {
                                    showResultModal('error', 'สร้าง QR Code ไม่สำเร็จ', data.message || 'เกิดข้อผิดพลาดในการสร้าง QR Code กรุณาลองใหม่อีกครั้ง');
                                    this.disabled = false;
                                    this.innerHTML = '<span class="material-icons">qr_code</span> สร้าง QR Code';
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showResultModal('error', 'สร้าง QR Code ไม่สำเร็จ', 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
                                this.disabled = false;
                                this.innerHTML = '<span class="material-icons">qr_code</span> สร้าง QR Code';
                            });
                    });
                }

                // ล้าง QR Code เดิม
                const qrDisplay = document.getElementById('qr-display');
                if (qrDisplay) {
                    qrDisplay.innerHTML = '';
                }

                const qrWrapper = document.querySelector('.qr-wrapper');
                if (qrWrapper) {
                    qrWrapper.classList.remove('active');
                    qrWrapper.innerHTML = `
                        <div class="qr-placeholder">
                            <span class="material-icons">qr_code</span>
                            <span>กดปุ่มด้านล่างเพื่อสร้าง QR Code</span>
                        </div>
                    `;
                }
            }
            window.location.href = 'check-in.php';

        });
    }

    if (closeModal) {
        closeModal.addEventListener('click', function () {
            resultModal.style.display = 'none';
        });
    }

    function showResultModal(type, title, message, goHome = false, newQR = false) {
        modalTitle.textContent = title;

        let iconName = '';
        let iconClass = '';
        let currentTime = '';
        let checkMethod = '';

        // ตรวจสอบว่าเป็นการเช็คชื่อสำเร็จหรือไม่ เพื่อแสดงเวลาและวิธีการ
        if (type === 'success' && title.includes('สำเร็จ')) {
            // เพิ่มเวลาปัจจุบัน
            const now = new Date();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            currentTime = `<div class="modal-submessage">เวลา ${hours}:${minutes} น.</div>`;

            // ระบุวิธีการเช็คชื่อ
            if (document.querySelector('.tab-item.active')) {
                const activeTab = document.querySelector('.tab-item.active').getAttribute('data-tab');
                switch (activeTab) {
                    case 'gps':
                        checkMethod = 'เช็คชื่อผ่าน GPS';
                        break;
                    case 'qr':
                        checkMethod = 'สร้าง QR Code ให้ครูสแกน';
                        break;
                    case 'pin':
                        checkMethod = 'เช็คชื่อด้วยรหัส PIN';
                        break;
                    case 'scan':
                        checkMethod = 'สแกน QR Code';
                        break;
                    default:
                        checkMethod = '';
                }

                if (checkMethod) {
                    checkMethod = `<div class="modal-submessage">วิธีการ: ${checkMethod}</div>`;
                }
            }
        }

        switch (type) {
            case 'success':
                iconName = 'check_circle';
                iconClass = 'success';
                break;
            case 'error':
                iconName = 'error';
                iconClass = 'error';
                break;
            case 'warning':
                iconName = 'warning';
                iconClass = 'warning';
                break;
            case 'info':
            default:
                iconName = 'info';
                iconClass = '';
                break;
        }

        // เพิ่ม effect พิเศษสำหรับ success
        const successGlow = type === 'success' ? '<div class="success-glow"></div>' : '';

        modalBody.innerHTML = `
            <div class="modal-icon ${iconClass}">
                <span class="material-icons">${iconName}</span>
                ${successGlow}
            </div>
            <div class="modal-message">${message}</div>
            ${currentTime}
            ${checkMethod}
        `;

        // ตั้งค่าปุ่ม OK
        modalOk.dataset.reload = 'false';
        modalOk.dataset.home = goHome ? 'true' : 'false';
        modalOk.dataset.newqr = newQR ? 'true' : 'false';

        // ปรับสีปุ่มตามประเภท
        switch (type) {
            case 'success':
                modalOk.style.backgroundColor = '#06c755';
                break;
            case 'error':
                modalOk.style.backgroundColor = '#f44336';
                break;
            case 'warning':
                modalOk.style.backgroundColor = '#ff9800';
                break;
            case 'info':
            default:
                modalOk.style.backgroundColor = '#2196F3';
                break;
        }

        // แสดง modal ด้วย animation
        resultModal.style.display = 'block';
        resultModal.classList.add('fade-in');

        // เพิ่ม event listener ของปุ่มปิด
        closeModal.onclick = function () {
            closeModalWithAnimation();
        };

        // เพิ่ม event listener เมื่อคลิกนอก modal
        resultModal.onclick = function (event) {
            if (event.target === resultModal) {
                closeModalWithAnimation();
            }
        };

        // ฟังก์ชั่นปิด modal ด้วย animation
        function closeModalWithAnimation() {
            resultModal.classList.remove('fade-in');
            resultModal.classList.add('fade-out');

            setTimeout(function () {
                resultModal.style.display = 'none';
                resultModal.classList.remove('fade-out');
            }, 300);
        }
    }

    // แก้ไขฟังก์ชันตรวจสอบพิกัด
    function checkLocation() {
        const locationStatus = document.getElementById('location-status');
        const checkLocationBtn = document.getElementById('check-location-btn');

        // ปิดปุ่มชั่วคราวระหว่างตรวจสอบ
        checkLocationBtn.disabled = true;
        checkLocationBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังตรวจสอบ...';

        // ตรวจสอบสถานะการเปิด GPS
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function (position) {
                    // เมื่อได้ตำแหน่งสำเร็จ
                    const userLat = position.coords.latitude;
                    const userLng = position.coords.longitude;

                    // ขอข้อมูลพิกัดจุดเช็คชื่อจาก API
                    fetch('api/check_location.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // คำนวณระยะห่าง
                                const distance = calculateDistance(
                                    userLat, userLng,
                                    parseFloat(data.location.lat),
                                    parseFloat(data.location.lng)
                                );

                                // ตรวจสอบระยะห่าง
                                if (distance <= data.location.radius) {
                                    locationStatus.innerHTML = '<span class="text-success">✓ คุณอยู่ในพื้นที่ที่กำหนด</span>';
                                    document.getElementById('location-verified').value = 'true';
                                    // เปิดให้ใช้งานส่วนถัดไป
                                    document.getElementById('pin-section').classList.remove('disabled');
                                } else {
                                    locationStatus.innerHTML = '<span class="text-danger">✗ คุณอยู่ห่างจากจุดเช็คชื่อเกินไป (ห่าง ' + distance.toFixed(2) + ' เมตร)</span>';
                                    document.getElementById('location-verified').value = 'false';
                                }
                            } else {
                                locationStatus.innerHTML = '<span class="text-danger">✗ ไม่สามารถตรวจสอบตำแหน่งได้: ' + data.message + '</span>';
                            }

                            // เปิดปุ่มอีกครั้ง
                            checkLocationBtn.disabled = false;
                            checkLocationBtn.innerHTML = 'ตรวจสอบตำแหน่ง';
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            locationStatus.innerHTML = '<span class="text-danger">✗ เกิดข้อผิดพลาดในการตรวจสอบตำแหน่ง</span>';
                            checkLocationBtn.disabled = false;
                            checkLocationBtn.innerHTML = 'ตรวจสอบตำแหน่ง';
                        });
                },
                function (error) {
                    // จัดการข้อผิดพลาด geolocation
                    let errorMessage = 'ไม่สามารถระบุตำแหน่งได้';
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'คุณไม่อนุญาตให้เข้าถึงตำแหน่ง กรุณาเปิดการใช้งาน GPS';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'ข้อมูลตำแหน่งไม่พร้อมใช้งาน';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'หมดเวลาในการรับข้อมูลตำแหน่ง';
                            break;
                    }
                    locationStatus.innerHTML = '<span class="text-danger">✗ ' + errorMessage + '</span>';
                    checkLocationBtn.disabled = false;
                    checkLocationBtn.innerHTML = 'ตรวจสอบตำแหน่ง';
                }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
            );
        } else {
            locationStatus.innerHTML = '<span class="text-danger">✗ เบราว์เซอร์ของคุณไม่รองรับการระบุตำแหน่ง</span>';
            checkLocationBtn.disabled = false;
            checkLocationBtn.innerHTML = 'ตรวจสอบตำแหน่ง';
        }
    }

    // ฟังก์ชันคำนวณระยะห่าง (ใช้สูตร Haversine)
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000; // รัศมีของโลกในเมตร
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        const distance = R * c;
        return distance; // ระยะห่างในเมตร
    }

    // ค้นหาปุ่มตรวจสอบสถานะด้วย ID
    const checkStatusBtn = document.getElementById('check-qr-status');

    if (checkStatusBtn) {
        // เพิ่ม event listener สำหรับการคลิก
        checkStatusBtn.addEventListener('click', function () {
            // แสดงการโหลด
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="material-icons spin">refresh</span> กำลังตรวจสอบ...';
            this.disabled = true;

            // ส่งคำขอไปยัง API เพื่อตรวจสอบสถานะ
            fetch('api/check_qr_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=check_status'
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('การเชื่อมต่อมีปัญหา');
                    }
                    return response.json();
                })
                .then(data => {
                    // คืนค่าปุ่มเป็นสถานะปกติ
                    this.innerHTML = originalText;
                    this.disabled = false;

                    // แสดงผลการตรวจสอบ
                    if (data.success) {
                        // แสดงข้อความสำเร็จ
                        showNotification(data.message, 'success');

                        // อัปเดตสถานะการแสดง QR code หากจำเป็น
                        if (data.status === 'active') {
                            document.getElementById('qr-status').textContent = 'กำลังใช้งาน';
                            document.getElementById('qr-status').className = 'status active';
                        } else {
                            document.getElementById('qr-status').textContent = 'หมดอายุ';
                            document.getElementById('qr-status').className = 'status inactive';
                        }

                        // เพิ่มเติม: อัปเดตเวลาตรวจสอบล่าสุด
                        const lastCheckedEl = document.getElementById('last-checked');
                        if (lastCheckedEl) {
                            lastCheckedEl.textContent = data.last_checked || new Date().toLocaleString('th-TH');
                        }
                    } else {
                        // แสดงข้อความข้อผิดพลาด
                        showNotification(data.message || 'ไม่สามารถตรวจสอบสถานะได้', 'error');
                    }
                })
                .catch(error => {
                    // จัดการข้อผิดพลาด
                    console.error('Error:', error);
                    this.innerHTML = originalText;
                    this.disabled = false;
                    showNotification('เกิดข้อผิดพลาดในการตรวจสอบสถานะ: ' + error.message, 'error');
                });
        });
    } else {
        console.error('ไม่พบปุ่มตรวจสอบสถานะ QR');
    }

    // ฟังก์ชันแสดงการแจ้งเตือน
    function showNotification(message, type) {
        // ตรวจสอบว่ามี container สำหรับการแจ้งเตือนหรือไม่
        let notificationContainer = document.querySelector('.notification-container');

        // ถ้าไม่มี container ให้สร้างใหม่
        if (!notificationContainer) {
            notificationContainer = document.createElement('div');
            notificationContainer.className = 'notification-container';
            notificationContainer.style.position = 'fixed';
            notificationContainer.style.top = '20px';
            notificationContainer.style.right = '20px';
            notificationContainer.style.zIndex = '9999';
            document.body.appendChild(notificationContainer);
        }

        // สร้างการแจ้งเตือน
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="material-icons">${type === 'success' ? 'check_circle' : 'error'}</span>
                <span class="message">${message}</span>
            </div>
            <button class="close-btn" onclick="this.parentElement.remove();">×</button>
        `;

        // กำหนด style พื้นฐาน
        notification.style.padding = '10px 15px';
        notification.style.margin = '10px 0';
        notification.style.borderRadius = '4px';
        notification.style.display = 'flex';
        notification.style.justifyContent = 'space-between';
        notification.style.alignItems = 'center';
        notification.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';

        if (type === 'success') {
            notification.style.backgroundColor = '#d4edda';
            notification.style.color = '#155724';
            notification.style.borderLeft = '4px solid #28a745';
        } else {
            notification.style.backgroundColor = '#f8d7da';
            notification.style.color = '#721c24';
            notification.style.borderLeft = '4px solid #dc3545';
        }

        // เพิ่มการแจ้งเตือน
        notificationContainer.appendChild(notification);

        // ซ่อนการแจ้งเตือนหลังจาก 5 วินาที
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    // เพิ่ม CSS สำหรับการหมุนไอคอน
    const style = document.createElement('style');
    style.textContent = `
        .material-icons.spin {
            animation: spin 1s infinite linear;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
});