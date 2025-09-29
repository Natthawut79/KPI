<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'KPI Management System'; ?></title>
    
    <link rel="stylesheet" href="css/style.css"> 
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="navbar">
    <div class="navbar-left">
        <img src="img/ICT.png" alt="Logo">
        <nav>
            <a href="index.php">หน้าหลัก</a> |
            <a href="indicator_types.php">ประเภทตัวชี้วัด</a> |
            <a href="indicators.php">ตัวชี้วัด</a>
        </nav>
    </div>
    <div class="user-box">
        
        <a href="profile.php">
            <img src="img/profile.png" alt="User" style="cursor: pointer;">
        </a>
        <div>
            <strong>Username :</strong> xxxxxxxx <br>
            <strong>ชื่อ-สกุล :</strong> สมชาย แสงดี <br> 
            <strong>ตำแหน่ง :</strong> Admin
        </div>
        <button class="logout-btn" onclick="logout()">
            <img src="img/logout.png" alt="Logout"> 
        </button>
    </div>
</div>