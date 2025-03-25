<!-- ขั้นตอนเลือกครูที่ปรึกษาและชั้นเรียน -->
<div class="card">
    <div class="card-title">เลือกครูที่ปรึกษาและชั้นเรียน</div>
    <div class="card-content">
        <p>ผลการค้นหา: <?php echo count($_SESSION['search_teacher_results']); ?> รายการ</p>

        <?php if (empty($_SESSION['search_teacher_results'])): ?>
            <div class="no-results">
                <p>ไม่พบข้อมูลครูที่ปรึกษา</p>
                <a href="register.php?step=55" class="btn secondary">ระบุข้อมูลชั้นเรียนเอง</a>
            </div>
        <?php else: ?>
            <form method="POST" action="register.php?step=5">
                <div class="teacher-list">
                    <?php foreach ($_SESSION['search_teacher_results'] as $key => $teacher): ?>
                        <div class="teacher-card">
                            <div class="radio-container">
                                <input type="radio" name="teacher_id" id="teacher_<?php echo $teacher['teacher_id']; ?>" value="<?php echo $teacher['teacher_id']; ?>" required>
                                <label for="teacher_<?php echo $teacher['teacher_id']; ?>" class="radio-label">
                                    <div class="teacher-name"><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></div>
                                    <div class="teacher-department"><?php echo $teacher['department']; ?></div>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="input-container">
                    <label class="input-label">เลือกชั้นเรียน</label>
                    <select class="input-field" name="class_id" required>
                        <option value="" disabled selected>เลือกชั้นเรียน</option>
                        <?php
                        // ดึงข้อมูลชั้นเรียนของครูที่ปรึกษา
                        try {
                            $first_teacher = $_SESSION['search_teacher_results'][0]['teacher_id'];
                            $classes_stmt = $conn->prepare("SELECT c.class_id, c.level, c.department, c.group_number 
                                                FROM classes c 
                                                JOIN class_advisors ca ON c.class_id = ca.class_id
                                                WHERE ca.teacher_id = :teacher_id
                                                AND c.academic_year_id = :academic_year_id");
                            $classes_stmt->execute([
                                ':teacher_id' => $first_teacher,
                                ':academic_year_id' => $current_academic_year_id
                            ]);
                            
                            while ($class = $classes_stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='" . $class['class_id'] . "'>"
                                    . $class['level'] . " สาขา" . $class['department']
                                    . " กลุ่ม " . $class['group_number'] . "</option>";
                            }
                        } catch (PDOException $e) {
                            echo "<option value=''>เกิดข้อผิดพลาดในการดึงข้อมูลชั้นเรียน</option>";
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" class="btn primary">
                    ดำเนินการต่อ <span class="material-icons">arrow_forward</span>
                </button>
            </form>

        <?php endif; ?>
    </div>
</div>