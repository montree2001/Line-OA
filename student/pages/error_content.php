<!-- Error Page Content -->
<div class="container">
    <div class="error-container">
        <div class="error-icon">
            <i class="material-icons">error_outline</i>
        </div>
        <h2 class="error-title">เกิดข้อผิดพลาด</h2>
        <div class="error-message">
            <?php 
            // Display error message if available
            if (isset($_SESSION['error'])) {
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
            } else {
                echo "เกิดข้อผิดพลาดที่ไม่คาดคิด กรุณาลองใหม่อีกครั้ง";
            }
            ?>
        </div>
        <div class="error-details">
            <p>หากพบปัญหาต่อเนื่อง กรุณาติดต่อผู้ดูแลระบบ</p>
            <p class="timestamp">เวลาที่เกิดข้อผิดพลาด: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
        <div class="error-actions">
            <a href="home.php" class="btn-action btn-home">
                <i class="material-icons">home</i> กลับหน้าหลัก
            </a>
            <button class="btn-action btn-reload" onclick="window.location.reload()">
                <i class="material-icons">refresh</i> โหลดหน้าใหม่
            </button>
        </div>
    </div>
</div>

<style>
    .error-container {
        max-width: 500px;
        margin: 100px auto 0;
        padding: 30px;
        background-color: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        text-align: center;
        animation: fadeIn 0.5s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .error-icon {
        font-size: 80px;
        color: #f44336;
        margin-bottom: 20px;
    }
    
    .error-icon .material-icons {
        font-size: 80px;
    }
    
    .error-title {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 15px;
        color: #333;
    }
    
    .error-message {
        font-size: 16px;
        color: #555;
        margin-bottom: 20px;
        padding: 15px;
        background-color: #ffebee;
        border-radius: 10px;
        border-left: 4px solid #f44336;
    }
    
    .error-details {
        font-size: 14px;
        color: #777;
        margin-bottom: 25px;
    }
    
    .timestamp {
        font-size: 12px;
        color: #999;
        margin-top: 10px;
    }
    
    .error-actions {
        display: flex;
        justify-content: center;
        gap: 15px;
    }
    
    .btn-action {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px 20px;
        border-radius: 50px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-action .material-icons {
        margin-right: 8px;
        font-size: 18px;
    }
    
    .btn-home {
        background-color: #06c755;
        color: white;
        border: none;
    }
    
    .btn-home:hover {
        background-color: #05a849;
        box-shadow: 0 4px 12px rgba(6, 199, 85, 0.3);
        transform: translateY(-2px);
    }
    
    .btn-reload {
        background-color: white;
        color: #333;
        border: 1px solid #ddd;
    }
    
    .btn-reload:hover {
        background-color: #f5f5f5;
        border-color: #ccc;
        transform: translateY(-2px);
    }
    
    @media (max-width: 480px) {
        .error-container {
            margin: 50px auto 0;
            padding: 20px;
        }
        
        .error-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }
        
        .error-icon .material-icons {
            font-size: 60px;
        }
        
        .error-title {
            font-size: 20px;
        }
        
        .error-message {
            font-size: 14px;
        }
        
        .error-actions {
            flex-direction: column;
            gap: 10px;
        }
        
        .btn-action {
            width: 100%;
        }
    }
</style> 