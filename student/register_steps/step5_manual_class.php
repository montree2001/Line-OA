<!-- ขั้นตอนกรอกข้อมูลห้องเรียนเอง -->
<div class="card">
    <div class="card-title">ระบุข้อมูลชั้นเรียน</div>
    <div class="card-content">
        <form method="POST" action="register.php?step=55">
            <div class="input-container">
                <label class="input-label">สาขาวิชา</label>
                <select class="input-field" name="department" required>
                    <option value="" disabled selected>เลือกสาขาวิชา</option>
                    <option value="ช่างยนต์">สาขาวิชาช่างยนต์</option>
                    <option value="ช่างกลโรงงาน">สาขาวิชาช่างกลโรงงาน</option>
                    <option value="ช่างไฟฟ้ากำลัง">สาขาวิชาช่างไฟฟ้ากำลัง</option>
                    <option value="ช่างอิเล็กทรอนิกส์">สาขาวิชาช่างอิเล็กทรอนิกส์</option>
                    <option value="การบัญชี">สาขาวิชาการบัญชี</option>
                    <option value="เทคโนโลยีสารสนเทศ">สาขาวิชาเทคโนโลยีสารสนเทศ</option>
                    <option value="การโรงแรม">สาขาวิชาการโรงแรม</option>
                    <option value="ช่างเชื่อมโลหะ">สาขาวิชาช่างเชื่อมโลหะ</option>
                </select>
            </div>

            <div class="input-container">
                <label class="input-label">กลุ่มเรียน</label>
                <select class="input-field" name="group_number" required>
                    <option value="" disabled selected>เลือกกลุ่มเรียน</option>
                    <option value="1">กลุ่ม 1</option>
                    <option value="2">กลุ่ม 2</option>
                    <option value="3">กลุ่ม 3</option>
                    <option value="4">กลุ่ม 4</option>
                    <option value="5">กลุ่ม 5</option>
                </select>
            </div>

            <button type="submit" class="btn primary">
                ดำเนินการต่อ <span class="material-icons">arrow_forward</span>
            </button>
        </form>
    </div>
</div>