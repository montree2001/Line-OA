# แผนการขยายระบบน้องห่วงใย AI ดูแลผู้เรียน

## ภาพรวมการขยายระบบ

การขยายระบบจากระบบเช็คชื่อเดิมเป็น **ระบบน้องห่วงใย AI ดูแลผู้เรียนแบบองค์รวม** ที่ครอบคลุม:
- ผลการเรียน และการวิเคราะห์ผลการเรียน
- การติดตามสุขภาพและสุขภาพจิต
- ระบบ AI Chat Bot ที่วิเคราะห์พฤติกรรม
- การดูแลนักเรียนแบบ 360 องศา

## โครงสร้างไฟล์ใหม่

### 1. ส่วนผู้ดูแลระบบ (Admin Module) - เพิ่มเติม

```
admin/
├── academic/
│   ├── academic_results.php          # หน้าดูผลการเรียนทั้งหมด
│   ├── failed_students_analysis.php  # วิเคราะห์นักเรียนที่ไม่ผ่าน
│   ├── grade_analytics.php           # วิเคราะห์เกรดและแนวโน้ม
│   └── subject_performance.php       # ผลการเรียนแยกตามวิชา
├── health/
│   ├── health_overview.php           # ภาพรวมสุขภาพนักเรียน
│   ├── mental_health_analysis.php    # วิเคราะห์สุขภาพจิต
│   ├── depression_risk_report.php    # รายงานความเสี่ยงซึมเศร้า
│   └── health_alerts.php             # การแจ้งเตือนด้านสุขภาพ
├── teacher_monitoring/
│   ├── teacher_attendance_overview.php # ดูการเช็คชื่อของครูทุกคน
│   ├── class_attendance_reports.php   # รายงานการเข้าเรียนตามวิชา
│   └── teacher_performance.php        # ประสิทธิภาพการสอน
├── ai_insights/
│   ├── ai_dashboard.php              # Dashboard AI Analytics
│   ├── chat_analysis.php             # วิเคราะห์การสนทนา AI
│   ├── risk_prediction.php           # คาดการณ์ความเสี่ยง
│   └── intervention_recommendations.php # คำแนะนำการแทรกแซง
└── reports/
    ├── comprehensive_care_report.php # รายงานการดูแลแบบองค์รวม
    ├── semester_summary.php          # สรุปภาคเรียน
    └── export/
        ├── export_academic_data.php  # ส่งออกข้อมูลการเรียน
        └── export_health_data.php    # ส่งออกข้อมูลสุขภาพ
```

### 2. ส่วนนักเรียน (Student Module) - เพิ่มเติม

```
student/
├── academic/
│   ├── my_grades.php                 # ดูผลการเรียนของตนเอง
│   ├── grade_history.php             # ประวัติผลการเรียน
│   ├── academic_progress.php         # ความก้าวหน้าการเรียน
│   └── study_recommendations.php     # คำแนะนำการเรียน
├── health/
│   ├── health_profile.php            # โปรไฟล์สุขภาพ
│   ├── health_record.php             # บันทึกข้อมูลสุขภาพ
│   ├── mental_health_assessment.php  # แบบประเมินสุขภาพจิต
│   ├── depression_screening.php      # การคัดกรองซึมเศร้า
│   └── health_tips.php               # คำแนะนำด้านสุขภาพ
├── ai_chat/
│   ├── chat_interface.php            # หน้าต่างแชท AI
│   ├── chat_history.php              # ประวัติการสนทนา
│   └── mood_tracker.php              # ติดตามอารมณ์
└── api/
    ├── save_health_data.php          # บันทึกข้อมูลสุขภาพ
    ├── mental_health_api.php         # API ประเมินสุขภาพจิต
    ├── ai_chat_api.php               # API แชท AI
    └── mood_api.php                  # API บันทึกอารมณ์
```

### 3. ส่วนครู (Teacher Module) - เพิ่มเติม

```
teacher/
├── class_management/
│   ├── my_classes.php                # จัดการวิชาที่สอน
│   ├── create_class.php              # สร้างวิชาใหม่
│   ├── class_roster.php              # รายชื่อนักเรียนในวิชา
│   └── class_schedule.php            # ตารางสอน
├── attendance/
│   ├── daily_attendance.php          # เช็คชื่อรายวัน
│   ├── attendance_summary.php        # สรุปการเข้าเรียน
│   ├── generate_pin.php              # สร้าง PIN เช็คชื่อ
│   └── attendance_reports.php        # รายงานการเข้าเรียน
├── grading/
│   ├── grade_entry.php               # ใส่คะแนน
│   ├── grade_calculation.php         # คำนวณเกรด
│   ├── submit_grades.php             # ส่งผลการเรียน
│   └── grade_history.php             # ประวัติการให้คะแนน
├── student_care/
│   ├── student_overview.php          # ภาพรวมนักเรียนที่ดูแล
│   ├── at_risk_students.php          # นักเรียนเสี่ยง
│   ├── academic_analysis.php         # วิเคราะห์การเรียน
│   ├── health_monitoring.php         # ติดตามสุขภาพ
│   ├── chat_insights.php             # ข้อมูลจากการแชท AI
│   └── intervention_plans.php        # แผนการแทรกแซง
└── api/
    ├── class_api.php                 # API จัดการคลาส
    ├── attendance_api.php            # API เช็คชื่อ
    ├── grading_api.php               # API ให้คะแนน
    └── student_analytics_api.php     # API วิเคราะห์นักเรียน
```

### 4. ส่วนผู้ปกครอง (Parent Module) - เพิ่มเติม

```
parent/
├── child_monitoring/
│   ├── child_dashboard.php           # Dashboard ลูก
│   ├── academic_progress.php         # ความก้าวหน้าการเรียน
│   ├── attendance_summary.php        # สรุปการเข้าเรียน
│   ├── grade_reports.php             # รายงานผลการเรียน
│   └── health_status.php             # สถานะสุขภาพ
├── communication/
│   ├── teacher_contact.php           # ติดต่อครู
│   ├── school_announcements.php      # ประกาศโรงเรียน
│   └── appointment_booking.php       # นัดหมายพบครู
├── ai_chat/
│   ├── parent_chat.php               # แชทเฉพาะข้อมูลลูก
│   ├── school_info_chat.php          # สอบถามข้อมูลโรงเรียน
│   └── chat_history.php              # ประวัติการสนทนา
└── alerts/
    ├── academic_alerts.php           # แจ้งเตือนการเรียน
    ├── health_alerts.php             # แจ้งเตือนสุขภาพ
    └── behavioral_alerts.php         # แจ้งเตือนพฤติกรรม
```

### 5. ระบบ AI และ Analytics

```
ai/
├── chatbot/
│   ├── chatbot_engine.php            # เครื่องมือ AI Chatbot
│   ├── intent_recognition.php        # จำแนกความตั้งใจ
│   ├── sentiment_analysis.php        # วิเคราะห์อารมณ์
│   └── response_generator.php        # สร้างคำตอบ
├── analytics/
│   ├── student_risk_analysis.php     # วิเคราะห์ความเสี่ยงนักเรียน
│   ├── academic_prediction.php       # ทำนายผลการเรียน
│   ├── health_risk_assessment.php    # ประเมินความเสี่ยงสุขภาพ
│   └── intervention_recommender.php  # แนะนำการแทรกแซง
├── data_processing/
│   ├── chat_analyzer.php             # วิเคราะห์การสนทนา
│   ├── pattern_detection.php         # ตรวจจับรูปแบบ
│   ├── anomaly_detection.php         # ตรวจจับความผิดปกติ
│   └── trend_analysis.php            # วิเคราะห์แนวโน้ม
└── models/
    ├── depression_model.php          # โมเดลตรวจจับซึมเศร้า
    ├── academic_performance_model.php # โมเดลทำนายผลการเรียน
    └── risk_scoring_model.php        # โมเดลให้คะแนนความเสี่ยง
```

### 6. API และ Integration

```
api/
├── v2/
│   ├── academic/
│   │   ├── grades.php                # API ผลการเรียน
│   │   ├── subjects.php              # API วิชา
│   │   └── classes.php               # API คลาส
│   ├── health/
│   │   ├── records.php               # API บันทึกสุขภาพ
│   │   ├── assessments.php           # API ประเมินสุขภาพ
│   │   └── alerts.php                # API แจ้งเตือนสุขภาพ
│   ├── ai/
│   │   ├── chat.php                  # API แชท
│   │   ├── analysis.php              # API วิเคราะห์
│   │   └── predictions.php           # API ทำนาย
│   └── notifications/
│       ├── push.php                  # Push Notifications
│       ├── line.php                  # LINE Notifications
│       └── email.php                 # Email Notifications
├── webhooks/
│   ├── line_webhook_v2.php           # LINE Webhook ปรับปรุง
│   └── ai_webhook.php                # AI System Webhook
└── integrations/
    ├── school_mis.php                # เชื่อมต่อระบบ MIS
    ├── health_system.php             # เชื่อมต่อระบบสุขภาพ
    └── external_ai.php               # เชื่อมต่อ AI ภายนอก
```

### 7. Shared Components และ Libraries

```
includes/
├── ai/
│   ├── ChatbotEngine.php             # คลาส Chatbot
│   ├── SentimentAnalyzer.php         # วิเคราะห์อารมณ์
│   ├── RiskCalculator.php            # คำนวณความเสี่ยง
│   └── PredictionEngine.php          # เครื่องมือทำนาย
├── health/
│   ├── HealthDataManager.php         # จัดการข้อมูลสุขภาพ
│   ├── MentalHealthAssessment.php    # ประเมินสุขภาพจิต
│   └── DepressionScreening.php       # คัดกรองซึมเศร้า
├── academic/
│   ├── GradeCalculator.php           # คำนวณเกรด
│   ├── AttendanceTracker.php         # ติดตามการเข้าเรียน
│   └── AcademicAnalyzer.php          # วิเคราะห์การเรียน
└── notifications/
    ├── AlertManager.php              # จัดการการแจ้งเตือน
    ├── LineNotification.php          # การแจ้งเตือนผ่าน LINE
    └── EmailNotification.php         # การแจ้งเตือนผ่านอีเมล
```

## การเชื่อมต่อกับระบบเดิม

### ฐานข้อมูล
- ใช้ฐานข้อมูลเดิม `stp_prasat`
- เพิ่มตารางใหม่ผ่าน `schema_expansion.sql`
- ไม่แก้ไขโครงสร้างตารางเดิม
- ใช้ Foreign Key เชื่อมโยงข้อมูลเดิมกับข้อมูลใหม่

### Authentication
- ใช้ระบบ Login เดิมผ่าน LINE
- เพิ่ม role และ permission ใหม่
- รักษาความเข้ากันได้กับระบบเดิม

### UI/UX
- ใช้ theme และ layout เดิม
- เพิ่ม navigation menu ใหม่
- รักษา responsive design
- เพิ่ม dashboard ใหม่สำหรับแต่ละ role

## Timeline การพัฒนา

### Phase 1: Academic System (4 สัปดาห์)
1. สร้างตารางฐานข้อมูลด้านการเรียน
2. พัฒนาระบบจัดการวิชาและคลาส
3. สร้างระบบเช็คชื่อแยกตามวิชา
4. พัฒนาระบบใส่คะแนนและคำนวณเกรด

### Phase 2: Health Monitoring (3 สัปดาห์)
1. สร้างตารางข้อมูลสุขภาพ
2. พัฒนาแบบฟอร์มบันทึกสุขภาพ
3. สร้างแบบประเมินสุขภาพจิต
4. พัฒนาระบบแจ้งเตือนความเสี่ยง

### Phase 3: AI Chatbot (5 สัปดาห์)
1. ออกแบบ AI Chatbot Architecture
2. พัฒนาระบบวิเคราะห์ความรู้สึก
3. สร้างระบบจัดเก็บและวิเคราะห์การสนทนา
4. พัฒนาระบบแจ้งเตือนจาก AI Analysis

### Phase 4: Analytics & Reports (3 สัปดาห์)
1. สร้าง Dashboard การวิเคราะห์
2. พัฒนาระบบรายงานแบบองค์รวม
3. สร้างระบบ Export ข้อมูล
4. ทดสอบและปรับปรุงระบบ

### Phase 5: Integration & Testing (2 สัปดาห์)
1. รวมระบบทั้งหมดเข้าด้วยกัน
2. ทดสอบการทำงานร่วมกันของระบบ
3. ปรับปรุง UI/UX
4. Training และ Documentation

## การรักษาความปลอดภัย

### ข้อมูลส่วนบุคคล
- เข้ารหัสข้อมูลสุขภาพ
- จำกัดการเข้าถึงข้อมูลตาม role
- Log การเข้าถึงข้อมูลสำคัญ

### AI และ Analytics
- ไม่เก็บข้อความแชทที่ระบุตัวตนได้
- วิเคราะห์ข้อมูลในรูปแบบสถิติเท่านั้น
- มีการ review จากมนุษย์สำหรับกรณีเสี่ยงสูง

### การแจ้งเตือน
- ใช้ระบบการแจ้งเตือนแบบขั้นบันได
- ไม่เปิดเผยข้อมูลสุขภาพจิตในข้อความแจ้งเตือน
- ให้เฉพาะผู้มีสิทธิ์เท่านั้นที่เห็นรายละเอียด