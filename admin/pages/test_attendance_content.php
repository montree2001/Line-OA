<?php
/**
 * pages/test_attendance_content.php - Content for test attendance page
 */
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>ทดสอบการเช็คชื่อย้อนหลัง</h1>
        <div>
            <button class="btn btn-outline-info" data-toggle="modal" data-target="#helpModal">
                <span class="material-icons">help_outline</span> วิธีใช้งาน
            </button>
        </div>
    </div>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">หน้าหลัก</a></li>
            <li class="breadcrumb-item active">ทดสอบการเช็คชื่อย้อนหลัง</li>
        </ol>
    </nav>
</div>

<?php if (isset($success_message) && !empty($success_message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <strong><i class="material-icons align-middle">check_circle</i> สำเร็จ!</strong> <?php echo $success_message; ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<?php if (isset($error_message) && !empty($error_message)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong><i class="material-icons align-middle">error</i> ผิดพลาด!</strong> <?php echo $error_message; ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="material-icons align-middle">history</i> สร้างข้อมูลการเช็คชื่อย้อนหลัง
                </h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="class_id">
                                    <i class="material-icons align-middle text-primary">class</i> เลือกชั้นเรียน
                                </label>
                                <select class="form-control" id="class_id" name="class_id" required>
                                    <option value="">-- เลือกชั้นเรียน --</option>
                                    <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['class_id']; ?>">
                                        <?php echo $class['level'] . ' ' . $class['department_name'] . ' กลุ่ม ' . $class['group_number']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">เลือกชั้นเรียนที่ต้องการสร้างข้อมูลการเช็คชื่อ</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="date">
                                    <i class="material-icons align-middle text-primary">event</i> เลือกวันที่
                                </label>
                                <select class="form-control" id="date" name="date" required>
                                    <option value="">-- เลือกวันที่ --</option>
                                    <?php foreach ($days as $day): ?>
                                    <?php 
                                        $date_obj = new DateTime($day);
                                        $thai_date = $date_obj->format('d/m/') . ($date_obj->format('Y') + 543);
                                        $day_name = $date_obj->format('D');
                                        
                                        // แปลงชื่อวันเป็นภาษาไทย
                                        $day_names = [
                                            'Mon' => 'จันทร์',
                                            'Tue' => 'อังคาร',
                                            'Wed' => 'พุธ',
                                            'Thu' => 'พฤหัสบดี',
                                            'Fri' => 'ศุกร์',
                                            'Sat' => 'เสาร์',
                                            'Sun' => 'อาทิตย์'
                                        ];
                                        $thai_day_name = $day_names[$day_name] ?? $day_name;
                                    ?>
                                    <option value="<?php echo $day; ?>">
                                        <?php echo $thai_date; ?> (<?php echo $thai_day_name; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">เลือกวันที่ต้องการสร้างข้อมูลการเช็คชื่อ</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="check_method">
                                    <i class="material-icons align-middle text-primary">how_to_reg</i> วิธีการเช็คชื่อ
                                </label>
                                <select class="form-control" id="check_method" name="check_method" required>
                                    <option value="">-- เลือกวิธีการเช็คชื่อ --</option>
                                    <option value="GPS">GPS</option>
                                    <option value="QR_Code">QR Code</option>
                                    <option value="PIN">PIN</option>
                                    <option value="Manual">เช็คชื่อโดยครู</option>
                                </select>
                                <small class="form-text text-muted">เลือกวิธีการเช็คชื่อที่จะใช้</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="percentage">
                                    <i class="material-icons align-middle text-primary">percent</i> เปอร์เซ็นต์การเข้าแถว (0-100%)
                                </label>
                                <input type="number" class="form-control" id="percentage" name="percentage" min="0" max="100" value="90" required>
                                <small class="form-text text-muted">กำหนดเปอร์เซ็นต์ของนักเรียนทั้งหมดที่จะมีการเช็คชื่อในวันที่เลือก</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="material-icons align-middle">info</i> 
                        ระบบจะสร้างข้อมูลการเช็คชื่อย้อนหลังตามเปอร์เซ็นต์ที่กำหนด โดยจะสุ่มเลือกนักเรียนในชั้นเรียนที่เลือก
                        และใช้วิธีการเช็คชื่อตามที่ระบุ ข้อมูลที่สร้างจะถูกบันทึกลงในฐานข้อมูลเสมือนการเช็คชื่อจริง
                    </div>
                    
                    <button type="submit" name="generate" class="btn btn-primary">
                        <i class="material-icons align-middle">add_circle</i> สร้างข้อมูลการเช็คชื่อย้อนหลัง
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($recent_attendance)): ?>
<div class="row mt-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="material-icons align-middle">receipt_long</i> ข้อมูลการเช็คชื่อย้อนหลังล่าสุด
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>วันที่</th>
                                <th>รหัสนักศึกษา</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th>ชั้นเรียน</th>
                                <th>เวลาเช็คชื่อ</th>
                                <th>วิธีการเช็คชื่อ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_attendance as $attendance): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $date_obj = new DateTime($attendance['date']);
                                    echo $date_obj->format('d/m/') . ($date_obj->format('Y') + 543);
                                    ?>
                                </td>
                                <td><?php echo $attendance['student_code']; ?></td>
                                <td><?php echo $attendance['first_name'] . ' ' . $attendance['last_name']; ?></td>
                                <td><?php echo $attendance['level'] . ' ' . $attendance['department_name'] . ' กลุ่ม ' . $attendance['group_number']; ?></td>
                                <td><?php echo $attendance['check_time']; ?></td>
                                <td>
                                    <?php
                                    $method_labels = [
                                        'GPS' => '<span class="badge badge-info">GPS</span>',
                                        'QR_Code' => '<span class="badge badge-success">QR Code</span>',
                                        'PIN' => '<span class="badge badge-warning">PIN</span>',
                                        'Manual' => '<span class="badge badge-secondary">Manual</span>'
                                    ];
                                    echo $method_labels[$attendance['check_method']] ?? $attendance['check_method'];
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="attendance_history.php" class="btn btn-outline-info">
                        <i class="material-icons align-middle">visibility</i> ดูประวัติการเช็คชื่อทั้งหมด
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- คำแนะนำการใช้งาน Modal -->
<div class="modal fade" id="helpModal" tabindex="-1" role="dialog" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="helpModalLabel">
                    <i class="material-icons align-middle">help</i> วิธีใช้งานทดสอบการเช็คชื่อย้อนหลัง
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <strong>คำเตือน:</strong> การใช้งานฟีเจอร์นี้จะสร้างข้อมูลการเช็คชื่อจริงในฐานข้อมูล ควรใช้เฉพาะในสภาพแวดล้อมทดสอบเท่านั้น
                </div>
                
                <h5>ขั้นตอนการใช้งาน</h5>
                <ol>
                    <li>เลือกชั้นเรียนที่ต้องการสร้างข้อมูลการเช็คชื่อ</li>
                    <li>เลือกวันที่ต้องการสร้างข้อมูลการเช็คชื่อ</li>
                    <li>เลือกวิธีการเช็คชื่อที่ต้องการใช้ (GPS, QR Code, PIN, หรือการเช็คชื่อโดยครู)</li>
                    <li>ระบุเปอร์เซ็นต์ของนักเรียนที่จะมีการเช็คชื่อ</li>
                    <li>คลิกปุ่ม "สร้างข้อมูลการเช็คชื่อย้อนหลัง"</li>
                </ol>
                
                <h5>รายละเอียดเพิ่มเติม</h5>
                <ul>
                    <li>ระบบจะไม่สร้างข้อมูลซ้ำหากนักเรียนมีการเช็คชื่อในวันที่เลือกแล้ว</li>
                    <li>ถ้าใช้วิธีการเช็คชื่อแบบ GPS ระบบจะสร้างพิกัด GPS ใกล้เคียงกับพิกัดโรงเรียนแบบสุ่ม</li>
                    <li>ถ้าใช้วิธีการเช็คชื่อแบบ PIN ระบบจะสร้างรหัส PIN แบบสุ่ม 4 หลัก</li>
                    <li>เวลาเช็คชื่อจะถูกสร้างแบบสุ่มระหว่าง 7:30 - 8:00 น.</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="material-icons align-middle">close</i> ปิด
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    if (typeof $.fn.tooltip !== 'undefined') {
        $('[data-toggle="tooltip"]').tooltip();
    }
});
</script>