<!-- ไฟล์นี้ควรไปอยู่ใน admin/templates/modals/import_modal.php -->

<!-- โมดัลนำเข้าข้อมูลนักเรียน -->
<div class="modal" id="importModal">
    <div class="modal-content modal-lg">
        <button class="modal-close" onclick="closeModal('importModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">นำเข้าข้อมูลนักเรียน</h2>

        <!-- ตัวบ่งชี้ขั้นตอน -->
        <div class="step-indicator">
            <div class="step active">
                <div class="step-number">1</div>
                <div class="step-text">เลือกไฟล์</div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-text">แม็ปข้อมูล</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-text">ตรวจสอบและยืนยัน</div>
            </div>
        </div>

        <form id="importForm" method="post" action="api/import_students.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import">

            <!-- ขั้นตอนที่ 1: เลือกไฟล์ -->
            <div class="import-step" id="step1">
                <div class="form-section">
                    <h3 class="section-title">เลือกไฟล์นำเข้า</h3>
                    
                    <div class="file-upload-container">
                        <div class="file-upload-area">
                            <input type="file" class="file-input" id="import_file" name="import_file" accept=".xlsx,.xls,.csv">
                            <div class="file-upload-content">
                                <span class="material-icons">cloud_upload</span>
                                <p>ลากไฟล์วางที่นี่ หรือคลิกเพื่อเลือกไฟล์</p>
                                <p class="file-types">รองรับไฟล์ Excel (.xlsx, .xls) หรือ CSV</p>
                            </div>
                        </div>
                        <div class="file-info">
                            <p>ไฟล์ที่เลือก: <span id="fileLabel">ยังไม่ได้เลือกไฟล์</span></p>
                        </div>
                    </div>

                    <div class="import-options">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="skip_header" name="skip_header" checked>
                            <label for="skip_header">ข้ามแถวแรก (หัวตาราง)</label>
                        </div>
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="update_existing" name="update_existing" checked>
                            <label for="update_existing">อัพเดตข้อมูลที่มีอยู่แล้ว</label>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">เลือกชั้นเรียนปลายทาง</h3>
                    <p class="section-desc">หากต้องการนำเข้านักเรียนเข้าชั้นเรียนเดียวกันทั้งหมด ให้เลือกชั้นเรียนที่นี่</p>
                    
                    <div class="form-group">
                        <select class="form-control" name="import_class_id" id="import_class_id">
                            <option value="">-- ไม่ระบุชั้นเรียน (ใช้ข้อมูลจากไฟล์) --</option>
                            <?php
                            // แสดงรายการชั้นเรียนจากฐานข้อมูล
                            if (isset($data['classGroups']) && is_array($data['classGroups'])):
                                foreach ($data['classGroups'] as $level => $classes):
                            ?>
                                <optgroup label="<?php echo $level; ?>">
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['class_id']; ?>">
                                            <?php echo $level . '/' . $class['group_number'] . ' ' . $class['department_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php
                                endforeach;
                            endif;
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">คำแนะนำการนำเข้าข้อมูล</h3>
                    <div class="import-instructions">
                        <ol>
                            <li>ไฟล์นำเข้าควรมีหัวตารางในแถวแรก (เลือก "ข้ามแถวแรก" ถ้ามี)</li>
                            <li>ข้อมูลที่จำเป็นต้องมี: รหัสนักเรียน, คำนำหน้า, ชื่อ, นามสกุล</li>
                            <li>คำนำหน้ารองรับเฉพาะ: นาย, นางสาว, เด็กชาย, เด็กหญิง, นาง</li>
                            <li>สถานะการศึกษารองรับ: กำลังศึกษา, พักการเรียน, พ้นสภาพ, สำเร็จการศึกษา</li>
                            <li>ระบบจะข้ามรายการที่มีข้อมูลไม่ครบถ้วน</li>
                            <li>สามารถ <a href="api/download_template.php?type=students" target="_blank">ดาวน์โหลดไฟล์ตัวอย่าง</a> เพื่อดูรูปแบบที่ถูกต้อง</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- ขั้นตอนที่ 2: แม็ปข้อมูล -->
            <div class="import-step" id="step2" style="display: none;">
                <div class="form-section">
                    <h3 class="section-title">ตัวอย่างข้อมูล</h3>
                    <p class="section-desc">ตัวอย่าง 5 รายการแรกจากไฟล์ที่อัปโหลด (พบข้อมูลทั้งหมด <span id="totalRecords">0</span> รายการ)</p>
                    
                    <div id="dataPreview" class="data-preview"></div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">แม็ปฟิลด์ข้อมูล</h3>
                    <p class="section-desc">โปรดเลือกว่าคอลัมน์ใดในไฟล์ตรงกับข้อมูลชนิดใด</p>
                    
                    <div class="field-mapping-container">
                        <div class="field-mapping-group">
                            <h4>ข้อมูลสำคัญ <span class="text-danger">*</span></h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="field-mapping">
                                        <label>รหัสนักเรียน <span class="text-danger">*</span></label>
                                        <select id="map_student_code" name="map_student_code" class="form-control" data-field="student_code" required>
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="field-mapping">
                                        <label>คำนำหน้า <span class="text-danger">*</span></label>
                                        <select id="map_title" name="map_title" class="form-control" data-field="title" required>
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="field-mapping">
                                        <label>ชื่อ <span class="text-danger">*</span></label>
                                        <select id="map_firstname" name="map_firstname" class="form-control" data-field="firstname" required>
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="field-mapping">
                                        <label>นามสกุล <span class="text-danger">*</span></label>
                                        <select id="map_lastname" name="map_lastname" class="form-control" data-field="lastname" required>
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="field-mapping-group">
                            <h4>ข้อมูลติดต่อ</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="field-mapping">
                                        <label>เบอร์โทรศัพท์</label>
                                        <select id="map_phone" name="map_phone" class="form-control" data-field="phone">
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="field-mapping">
                                        <label>อีเมล</label>
                                        <select id="map_email" name="map_email" class="form-control" data-field="email">
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="field-mapping-group">
                            <h4>ข้อมูลการศึกษา</h4>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="field-mapping">
                                        <label>ระดับชั้น</label>
                                        <select id="map_level" name="map_level" class="form-control" data-field="level">
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="field-mapping">
                                        <label>กลุ่ม</label>
                                        <select id="map_group" name="map_group" class="form-control" data-field="group">
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="field-mapping">
                                        <label>แผนกวิชา</label>
                                        <select id="map_department" name="map_department" class="form-control" data-field="department">
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="field-mapping">
                                        <label>สถานะการศึกษา</label>
                                        <select id="map_status" name="map_status" class="form-control" data-field="status">
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="field-mapping-note">
                    <p>หมายเหตุ: ระบบจะพยายามแม็ปฟิลด์อัตโนมัติตามชื่อหัวตาราง โปรดตรวจสอบความถูกต้อง</p>
                </div>
            </div>

            <!-- ขั้นตอนที่ 3: ตรวจสอบและยืนยัน -->
            <div class="import-step" id="step3" style="display: none;">
                <div class="form-section">
                    <h3 class="section-title">ตรวจสอบข้อมูลก่อนนำเข้า</h3>
                    
                    <div id="importSummary" class="import-summary"></div>
                    
                    <div class="destination-class">
                        <h4>ชั้นเรียนปลายทาง</h4>
                        <p id="selected_class_text" style="display: none;"></p>
                        <p class="info-text">
                            <span class="material-icons">info</span>
                            <?php if (isset($class_id) && !empty($class_id)): ?>
                                นักเรียนทั้งหมดจะถูกนำเข้าสู่ชั้นเรียนที่เลือกไว้
                            <?php else: ?>
                                ระบบจะใช้ข้อมูลชั้นเรียนจากไฟล์ หรือเว้นว่างถ้าไม่ระบุ
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="import-confirmation">
                        <p class="warning-text">
                            <span class="material-icons">warning</span>
                            การนำเข้าข้อมูลจะทำการเพิ่มหรืออัพเดตข้อมูลนักเรียนในระบบ โปรดตรวจสอบความถูกต้องก่อนดำเนินการ
                        </p>
                    </div>
                </div>
            </div>

            <!-- ปุ่มควบคุมขั้นตอน -->
            <div class="modal-actions">
                <button type="button" id="prevStepBtn" class="btn btn-secondary" style="display: none;" onclick="prevStep()">
                    <span class="material-icons">arrow_back</span>
                    ย้อนกลับ
                </button>
                <button type="button" id="nextStepBtn" class="btn btn-primary" disabled onclick="nextStep()">
                    ถัดไป
                    <span class="material-icons">arrow_forward</span>
                </button>
                <button type="submit" id="importSubmitBtn" class="btn btn-success" style="display: none;">
                    <span class="material-icons">cloud_upload</span>
                    นำเข้าข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>



<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner"></div>
    <div class="loading-text">กำลังประมวลผล...</div>
</div>

<!-- ต้องเพิ่ม CSS ไฟล์นี้ไว้ใน header.php -->
<!-- <link href="assets/css/import.css" rel="stylesheet"> -->

<!-- ต้องเพิ่ม JS libraries ไว้ใน footer.php -->
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script> -->
<!-- <script src="assets/js/import-students.js"></script> -->