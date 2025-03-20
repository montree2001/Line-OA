<!-- Step Indicator -->
<div class="steps">
    <div class="step <?php echo ($step >= 1) ? 'completed' : ''; ?> <?php echo ($step === 1) ? 'active' : ''; ?>">
        <div class="step-number">1</div>
        <div class="step-title">เข้าสู่ระบบ</div>
    </div>

    <div class="step-line <?php echo ($step > 1) ? 'completed' : ''; ?>"></div>

    <div class="step <?php echo ($step > 2) ? 'completed' : ''; ?> <?php echo ($step === 2) ? 'active' : ''; ?>">
        <div class="step-number">2</div>
        <div class="step-title">รหัสนักศึกษา</div>
    </div>

    <div class="step-line <?php echo ($step > 3) ? 'completed' : ''; ?>"></div>

    <div class="step <?php echo ($step > 4) ? 'completed' : ''; ?> <?php echo (in_array($step, [3, '3manual', 4])) ? 'active' : ''; ?>">
        <div class="step-number">3</div>
        <div class="step-title">ชั้นเรียน</div>
    </div>

    <div class="step-line <?php echo ($step > 6) ? 'completed' : ''; ?>"></div>

    <div class="step <?php echo ($step > 6) ? 'completed' : ''; ?> <?php echo (in_array($step, [5, '5manual', 6])) ? 'active' : ''; ?>">
        <div class="step-number">4</div>
        <div class="step-title">ข้อมูลเพิ่มเติม</div>
    </div>

    <div class="step-line <?php echo ($step === 7) ? 'completed' : ''; ?>"></div>

    <div class="step <?php echo ($step === 7) ? 'active' : ''; ?>">
        <div class="step-number">5</div>
        <div class="step-title">เสร็จสิ้น</div>
    </div>
</div>