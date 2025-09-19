# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP-based student attendance tracking system ("STP-Prasat") integrated with LINE Official Account for Thai educational institutions. The system manages student check-ins, teacher supervision, and parent notifications through LINE messaging.

## Architecture

### Core Structure
- **Root level**: Main entry points (`index.php`, `login.php`) and shared utilities
- **admin/**: Administrative dashboard and management functions
- **student/**: Student-facing features (check-in, activities, profile)
- **teacher/**: Teacher tools (attendance tracking, class management)  
- **parent/**: Parent portal for monitoring student attendance
- **config/**: Database configuration and environment settings
- **api/**: API endpoints for LINE integration and mobile features
- **assets/**: Frontend resources (CSS, JS, images)
- **database/**: SQL schema and migration files
- **uploads/**: User-uploaded files and profile pictures

### Key Components
- **Authentication**: LINE Login integration with session management
- **Database**: MySQL database `stp_prasat` with timezone set to `Asia/Bangkok`
- **LINE Integration**: Official Account callbacks and messaging via LINE API
- **PDF Generation**: Uses mPDF for attendance reports
- **Excel Processing**: PhpSpreadsheet for data import/export

## Environment Setup

### Database Configuration
Database settings are in `config/db_config.php`:
- Host: localhost
- User: root  
- Password: (empty)
- Database: stp_prasat
- Timezone: Asia/Bangkok

### LINE Integration
Environment variables in `.env`:
- `LINE_CHANNEL_ID`: LINE Official Account channel ID
- `LINE_CHANNEL_SECRET`: Channel secret for authentication
- `LINE_CALLBACK_URL`: Webhook endpoint URL

## Dependencies

The project uses Composer for PHP package management:
- `vlucas/phpdotenv`: Environment variable management
- `phpoffice/phpspreadsheet`: Excel file processing  
- `mpdf/mpdf`: PDF report generation
- `shuchkin/simplexlsxgen`: Lightweight Excel generation

## Development Commands

Since this is a PHP web application running on XAMPP:

### Start Development Environment
```bash
# Start XAMPP services (Apache + MySQL)
sudo /Applications/XAMPP/xamppfiles/xampp start
```

### Install Dependencies
```bash
composer install
```

### Database Setup
```bash
# Import database schema
mysql -u root stp_prasat < database/stp_prasat.sql
```

### Testing Database Connection
```bash
php test_db.php
```

## System Features

### Student Module (`student/`)
- GPS-based attendance check-in
- QR code generation for teacher scanning
- PIN-based attendance entry
- Activity participation tracking
- Profile management and photo upload

### Teacher Module (`teacher/`)
- Class attendance management
- 4-digit PIN code generation for student check-ins
- QR code scanning capabilities
- Individual student progress reports
- Parent contact information access

### Parent Module (`parent/`)
- LINE notification system for attendance alerts
- Student attendance summary viewing
- Multi-child support for families
- Teacher advisor contact information

### Admin Module (`admin/`)
- System-wide attendance oversight
- Comprehensive reporting and analytics
- At-risk student identification
- Bulk LINE messaging to parents
- Data export capabilities

## File Structure Patterns

- **Entry points**: `index.php` files in each module directory
- **API endpoints**: Organized under `api/` with LINE webhook handlers
- **Shared components**: `struck/` contains common UI elements (header, sidebar, menu)
- **Database utilities**: `db_connect.php` and `config/db_config.php` for connections
- **Reports**: PDF generation scripts with `print_` prefix
- **AJAX handlers**: Files prefixed with `ajax_` for dynamic content loading


ต้องการปรับขอบเขตของระบบน้องห่วงใย Ai ดูแลผู้เรียน ต้องการให้สามารถดูแลให้ครอบคลุมมากยิ่งขึ้น
 ส่วนของ ผู้ดูแลระบบ ครูผู้สอน นักเรียน ผู้ปกครอง 
-ส่วนของผู้ดูแลระบบ
    - ต้องการให้เพิ่มส่วนของผลการเรียน วิเคราะห์ผลการเรียน ที่ ไม่ผ่าน เพื่อวิเคราะห์ผลการเรียน
    - ต้องการวิเคราะห์ข้อมูล สุขภาพ ข้อมูล ด้านสุขภาพจิต ซึมเศร้าหรือโรคที่ต้องการความดูแลอย่างใกล้ชิด
    - สามารถดูข้อมูลการเช็คชื่อเข้าเรียนของครูแต่ละวิชาได้
- ส่วนของนักเรียน
    - สามารถดูในส่วนของผลการเรียนได้
    - สามารถบันทึกข้อมูลสุขภาพได้และประเมินความเสี่ยงโรคซึมเศร้า
    - สามารถคุยกับ Ai ได้ ผ่าน Line Chat Bot ต้องการให้เอาประวัติการคุยมาวิเคราะห์เพื่อต้องการดูแล
- ส่วนของครู
    - สามารถเช็คชื่อของแต่ละวิชาที่ตนเองสอนได้ โดยสามารถสร้างรายวิชาและเลือกกลุ่มเรียนได้ที่ต้องการเช็ค
    - สามารถส่งผลการเรียนของห้องนั้นๆ ที่ตนเองสอนได้
    - สามารถติดตามวิเคราะห์ข้อมูลทุกด้านของนักเรียน เพื่อดูแลผู้เรียน
- ส่วนของผู้ปกครอง
    - สามารถติดตามข้อมูลต่างๆ ของนักเรียนได้
    - สามารถคุย line chat bot จะตอบเฉพาะข้อมูลของบุตรหลานตนเอง
    - สามารถสอบข้อมูลทั่วไปของวิทยาลัยได้
ต้องการปรับปรุงจากระบบเดิมไม่ต้องการให้กระทบระบบเดิมมากเป็นการขยายระบบให้มีความสามารถเพิ่มมากยิ่งขึ้น