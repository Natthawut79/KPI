<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Management System - Login</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    </head>
<body>
    <div class="page-wrapper">
        <div class="news-container">
            <h2 class="news-title">- ข่าวสารและกิจกรรม -</h2>
            <div class="news-grid">
                <div class="news-card">
                    <img src="img/ICT.png" alt="News Image">
                    <div class="news-content">
                        <h3>ขอแสดงความยินดีกับนักศึกษา</h3>
                        <p>นักศึกษาจากคณะวิทยาศาสตร์และเทคโนโลยี ได้รับรางวัลชนะเลิศจากการแข่งขัน...</p>
                        <a href="#" class="read-more">Read More</a>
                    </div>
                </div>
                <div class="news-card">
                    <img src="img/admin_img.png" alt="News Image">
                    <div class="news-content">
                        <h3>ประกาศรับสมัครนักศึกษาใหม่</h3>
                        <p>เปิดรับสมัครนักศึกษาใหม่ ประจำปีการศึกษา 2568 ตั้งแต่วันนี้เป็นต้นไป...</p>
                        <a href="#" class="read-more">Read More</a>
                    </div>
                </div>
                <div class="news-card">
                    <img src="img/admin_img.png" alt="News Image">
                    <div class="news-content">
                        <h3>กิจกรรม Open House 2025</h3>
                        <p>ขอเชิญชวนน้องๆ นักเรียนเข้าร่วมกิจกรรมเปิดบ้านคณะวิทยาศาสตร์และเทคโนโลยี...</p>
                        <a href="#" class="read-more">Read More</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="login-container">
            <div class="login-wrapper">
                <div class="welcome-section">
                    <img src="img/ICT.png" alt="UTCC Logo" class="logo">
                    <p class="welcome-text">Welcome to</p>
                    <h1 class="system-name">KPI Management System</h1>
                </div>
                
                <div class="login-form-container">
                    <form id="loginForm" class="login-form" action="config/checklogin.php" method="post">
                        <h2>LOGIN</h2>
                        
                        <label for="Emp_code_input" class="login-label">รหัสพนักงาน</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="number" id="Emp_code_input" name="Emp_code" placeholder="รหัสพนักงาน" required>
                        </div>
                        
                        <label for="Password_input" class="login-label">รหัสผ่าน</label> <div class="input-group">
                            <i class="fas fa-keyboard"></i>
                            <input type="password" id="Password_input" name="Password" placeholder="รหัสผ่าน" required>
                        </div>
                        <button type="submit" class="login-btn">LOGIN</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    </body>
</html>