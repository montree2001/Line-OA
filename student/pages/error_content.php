<!-- Error Page Content -->
<div class="container">
    <div class="error-container">
        <div class="error-icon">
            <span class="material-icons">error</span>
        </div>
        <div class="error-title">เกิดข้อผิดพลาด</div>
        <div class="error-message"><?php echo $error_message ?? 'ไม่สามารถดำเนินการได้ในขณะนี้'; ?></div>
        <div class="error-actions">
            <button class="btn-outline" onclick="history.back()">
                <span class="material-icons">arrow_back</span>
                ย้อนกลับ
            </button>
            <button class="btn-primary" onclick="location.href='home.php'">
                <span class="material-icons">home</span>
                กลับหน้าหลัก
            </button>
        </div>
        <div class="error-help">
            หากปัญหายังคงอยู่ กรุณาติดต่อผู้ดูแลระบบ
        </div>
        <div class="error-time">
            เวลาที่เกิดข้อผิดพลาด: <?php echo date('d/m/Y H:i:s'); ?>
        </div>
    </div>
</div>

<style>
    .error-container {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        padding: 30px 20px;
        margin: 20px;
        text-align: center;
    }
    
    .error-icon {
        margin-bottom: 15px;
    }
    
    .error-icon .material-icons {
        font-size: 64px;
        color: #f44336;
    }
    
    .error-title {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 10px;
        color: #333;
    }
    
    .error-message {
        font-size: 16px;
        color: #666;
        margin-bottom: 25px;
        line-height: 1.5;
    }
    
    .error-actions {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-bottom: 25px;
    }
    
    .btn-outline {
        background-color: transparent;
        border: 1px solid #06c755;
        color: #06c755;
        padding: 10px 15px;
        border-radius: 5px;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        transition: background-color 0.2s;
    }
    
    .btn-outline:hover {
        background-color: rgba(6, 199, 85, 0.1);
    }
    
    .btn-primary {
        background-color: #06c755;
        border: none;
        color: white;
        padding: 10px 15px;
        border-radius: 5px;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        transition: background-color 0.2s;
    }
    
    .btn-primary:hover {
        background-color: #05b04c;
    }
    
    .btn-outline .material-icons, .btn-primary .material-icons {
        font-size: 18px;
        margin-right: 5px;
    }
    
    .error-help {
        font-size: 14px;
        color: #777;
        margin-bottom: 15px;
    }
    
    .error-time {
        font-size: 12px;
        color: #999;
    }
</style> 