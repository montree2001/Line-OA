<?php
/**
 * import_teachers_modal.php - โมดัลสำหรับนำเข้าข้อมูลครู
 * ระบบ STUDENT-Prasat
 */
?>
<!-- โมดัลนำเข้าข้อมูลครู -->
<div class="modal fade" id="importTeacherModal" tabindex="-1" aria-labelledby="importTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importTeacherModalLabel">นำเข้าข้อมูลครูที่ปรึกษา</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- ตัวบ่งชี้ขั้นตอน -->
            <div class="step-indicator d-flex justify-content-center my-4">
                <div class="step active text-center mx-4">
                    <div class="step-number bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 40px; height: 40px;">1</div>
                    <div class="step-text mt-2">เลือกไฟล์</div>
                </div>
                <div class="step text-center mx-4">
                    <div class="step-number bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 40px; height: 40px;">2</div>
                    <div class="step-text mt-2">แม็ปข้อมูล</div>
                </div>
                <div class="step text-center mx-4">
                    <div class="step-number bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 40px; height: 40px;">3</div>
                    <div class="step-text mt-2">ตรวจสอบและยืนยัน</div>
                </div>
            </div>

            <form id="importTeacherFullForm" method="post" action="api/import_teachers.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import">
                <input type="hidden" name="current_step" id="current_step" value="1">

                <div class="modal-body">
                    <!-- ขั้นตอนที่ 1: เลือกไฟล์ -->
                    <div class="import-step" id="step1">
                        <div class="form-section border rounded p-4 mb-4 bg-light">
                            <h6 class="section-title fw-bold mb-3">เลือกไฟล์นำเข้า</h6>
                            
                            <div class="file-upload-container">
                                <div class="file-upload-area border border-2 border-dashed rounded p-5 text-center bg-white cursor-pointer">
                                    <input type="file" class="file-input d-none" id="import_file" name="import_file" accept=".xlsx,.xls,.csv">
                                    <div class="file-upload-content">
                                        <span class="material-icons mb-3" style="font-size: 60px; color: #28a745;">cloud_upload</span>
                                        <p class="mb-2 fs-5">ลากไฟล์วางที่นี่ หรือคลิกเพื่อเลือกไฟล์</p>
                                        <p class="file-types text-muted small">รองรับไฟล์ Excel (.xlsx, .xls) หรือ CSV</p>
                                    </div>
                                </div>
                                <div class="file-info mt-3">
                                    <p class="mb-0">ไฟล์ที่เลือก: <span id="fileLabel" class="text-primary">ยังไม่ได้เลือกไฟล์</span></p>
                                </div>
                            </div>

                            <div class="import-options mt-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input type="checkbox" id="skip_header" name="skip_header" class="form-check-input" checked>
                                            <label for="skip_header" class="form-check-label">ข้ามแถวแรก (หัวตาราง)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input type="checkbox" id="update_existing" name="update_existing" class="form-check-input" checked>
                                            <label for="update_existing" class="form-check-label">อัพเดตข้อมูลที่มีอยู่แล้ว</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section border rounded p-4 mb-4 bg-light">
                            <h6 class="section-title fw-bold mb-3">เลือกแผนกวิชาปลายทาง</h6>
                            <p class="section-desc text-muted small">หากต้องการนำเข้าครูเข้าแผนกเดียวกันทั้งหมด ให้เลือกแผนกที่นี่</p>
                            
                            <div class="form-group">
                                <select class="form-select" name="import_department_id" id="import_department_id">
                                    <option value="">-- ไม่ระบุแผนก (ใช้ข้อมูลจากไฟล์) --</option>
                                    <?php if (isset($data['departments']) && is_array($data['departments'])): ?>
                                        <?php foreach ($data['departments'] as $dept): ?>
                                            <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-section border rounded p-4 bg-light">
                            <h6 class="section-title fw-bold mb-3">คำแนะนำการนำเข้าข้อมูล</h6>
                            <div class="import-instructions bg-white p-3 rounded">
                                <ol class="ps-3 mb-0">
                                    <li class="mb-2">ไฟล์นำเข้าควรมีหัวตารางในแถวแรก (เลือก "ข้ามแถวแรก" ถ้ามี)</li>
                                    <li class="mb-2">ข้อมูลที่จำเป็นต้องมี: เลขบัตรประชาชน, คำนำหน้า, ชื่อ, นามสกุล</li>
                                    <li class="mb-2">คำนำหน้ารองรับ: นาย, นาง, นางสาว, ดร., ผศ., รศ., ศ., อื่นๆ</li>
                                    <li class="mb-2">ระบบจะข้ามรายการที่มีข้อมูลไม่ครบถ้วน</li>
                                    <li>สามารถ <a href="api/download_template.php?type=teachers" target="_blank" class="text-primary">ดาวน์โหลดไฟล์ตัวอย่าง</a> เพื่อดูรูปแบบที่ถูกต้อง</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- ขั้นตอนที่ 2: แม็ปข้อมูล -->
                    <div class="import-step" id="step2" style="display: none;">
                        <div class="form-section border rounded p-4 mb-4 bg-light">
                            <h6 class="section-title fw-bold mb-3">ตัวอย่างข้อมูล</h6>
                            <p class="section-desc text-muted small">ตัวอย่าง 5 รายการแรกจากไฟล์ที่อัปโหลด (พบข้อมูลทั้งหมด <span id="totalRecords" class="fw-bold">0</span> รายการ)</p>
                            
                            <div id="dataPreview" class="data-preview table-responsive bg-white rounded border">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>คอลัมน์ 1</th>
                                            <th>คอลัมน์ 2</th>
                                            <th>คอลัมน์ 3</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">กรุณาอัปโหลดไฟล์ในขั้นตอนแรก</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="form-section border rounded p-4 mb-4 bg-light">
                            <h6 class="section-title fw-bold mb-3">แม็ปฟิลด์ข้อมูล</h6>
                            <p class="section-desc text-muted small">โปรดเลือกว่าคอลัมน์ใดในไฟล์ตรงกับข้อมูลชนิดใด</p>
                            
                            <div class="field-mapping-container">
                                <div class="field-mapping-group mb-4 bg-white p-3 rounded border">
                                    <h6 class="mb-3">ข้อมูลสำคัญ <span class="text-danger">*</span></h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="field-mapping">
                                                <label class="form-label">เลขบัตรประชาชน <span class="text-danger">*</span></label>
                                                <select id="map_national_id" name="map_national_id" class="form-select" data-field="national_id" required>
                                                    <option value="-1">-- เลือกคอลัมน์ --</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="field-mapping">
                                                <label class="form-label">คำนำหน้า <span class="text-danger">*</span></label>
                                                <select id="map_title" name="map_title" class="form-select" data-field="title" required>
                                                    <option value="-1">-- เลือกคอลัมน์ --</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="field-mapping">
                                                <label class="form-label">ชื่อ <span class="text-danger">*</span></label>
                                                <select id="map_firstname" name="map_firstname" class="form-select" data-field="firstname" required>
                                                    <option value="-1">-- เลือกคอลัมน์ --</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="field-mapping">
                                                <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                                                <select id="map_lastname" name="map_lastname" class="form-select" data-field="lastname" required>
                                                    <option value="-1">-- เลือกคอลัมน์ --</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="field-mapping-group mb-4 bg-white p-3 rounded border">
                                    <h6 class="mb-3">ข้อมูลตำแหน่งและการติดต่อ</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="field-mapping">
                                                <label class="form-label">แผนก/ฝ่าย</label>
                                                <select id="map_department" name="map_department" class="form-select" data-field="department">
                                                    <option value="-1">-- เลือกคอลัมน์ --</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="field-mapping">
                                                <label class="form-label">ตำแหน่ง</label>
                                                <select id="map_position" name="map_position" class="form-select" data-field="position">
                                                    <option value="-1">-- เลือกคอลัมน์ --</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="field-mapping">
                                                <label class="form-label">เบอร์โทรศัพท์</label>
                                                <select id="map_phone" name="map_phone" class="form-select" data-field="phone">
                                                    <option value="-1">-- เลือกคอลัมน์ --</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="field-mapping">
                                                <label class="form-label">อีเมล</label>
                                                <select id="map_email" name="map_email" class="form-select" data-field="email">
                                                    <option value="-1">-- เลือกคอลัมน์ --</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info d-flex align-items-center">
                                <span class="material-icons me-3">info</span>
                                <div>
                                    <strong>หมายเหตุ:</strong> ระบบจะพยายามแม็ปฟิลด์อัตโนมัติตามชื่อหัวตาราง โปรดตรวจสอบความถูกต้อง
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ขั้นตอนที่ 3: ตรวจสอบและยืนยัน -->
                    <div class="import-step" id="step3" style="display: none;">
                        <div class="form-section border rounded p-4 bg-light">
                            <h6 class="section-title fw-bold mb-4">ตรวจสอบข้อมูลก่อนนำเข้า</h6>
                            
                            <div id="importSummary" class="import-summary mb-4">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <div class="card h-100 text-center border-0 shadow-sm">
                                            <div class="card-body">
                                                <h6 class="text-muted mb-2">จำนวนรายการทั้งหมด</h6>
                                                <h2 id="summary_total" class="mb-0 text-primary">0</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="card h-100 text-center border-0 shadow-sm">
                                            <div class="card-body">
                                                <h6 class="text-muted mb-2">คาดว่าจะนำเข้าใหม่</h6>
                                                <h2 id="summary_new" class="mb-0 text-success">0</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="card h-100 text-center border-0 shadow-sm">
                                            <div class="card-body">
                                                <h6 class="text-muted mb-2">คาดว่าจะอัพเดต</h6>
                                                <h2 id="summary_update" class="mb-0 text-info">0</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="card h-100 text-center border-0 shadow-sm">
                                            <div class="card-body">
                                                <h6 class="text-muted mb-2">อาจมีปัญหา</h6>
                                                <h2 id="summary_issues" class="mb-0 text-warning">0</h2>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="destination-dept mb-4 bg-white p-4 rounded border">
                                <h6 class="mb-3">แผนกวิชาปลายทาง</h6>
                                <p id="selected_dept_text" style="display: none;" class="mb-3 fw-bold fs-5"></p>
                                <div class="d-flex align-items-center">
                                    <span class="material-icons text-info me-2">info</span>
                                    <p id="dept_info_text" class="mb-0">ระบบจะใช้ข้อมูลแผนกจากไฟล์ หรือเว้นว่างถ้าไม่ระบุ</p>
                                </div>
                            </div>
                            
                            <div class="import-options mb-4 bg-white p-4 rounded border">
                                <h6 class="mb-3">ตัวเลือกการนำเข้า</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="material-icons me-2 text-primary">check_circle</span>
                                            <div>ข้ามแถวแรก (หัวตาราง): <span id="summary_skip_header" class="fw-bold">ใช่</span></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="material-icons me-2 text-primary">check_circle</span>
                                            <div>อัพเดตข้อมูลที่มีอยู่แล้ว: <span id="summary_update_existing" class="fw-bold">ใช่</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning d-flex">
                                <span class="material-icons me-3">warning</span>
                                <div>
                                    <strong>คำเตือน:</strong> การนำเข้าข้อมูลจะทำการเพิ่มหรืออัพเดตข้อมูลครูในระบบ ข้อมูลที่ไม่ครบถ้วนจะถูกข้าม
                                    โปรดตรวจสอบความถูกต้องก่อนดำเนินการ
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light p-3">
                    <button type="button" id="prevStepBtn" class="btn btn-secondary d-flex align-items-center" style="display: none;" onclick="prevStep()">
                        <span class="material-icons me-1" style="font-size: 16px;">arrow_back</span>
                        ย้อนกลับ
                    </button>
                    <button type="button" id="nextStepBtn" class="btn btn-primary d-flex align-items-center" disabled onclick="nextStep()">
                        ถัดไป
                        <span class="material-icons ms-1" style="font-size: 16px;">arrow_forward</span>
                    </button>
                    <button type="submit" id="importSubmitBtn" class="btn btn-success d-flex align-items-center" style="display: none;">
                        <span class="material-icons me-1" style="font-size: 16px;">cloud_upload</span>
                        นำเข้าข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner"></div>
    <div class="loading-text">กำลังประมวลผล...</div>
</div>
