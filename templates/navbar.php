<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config/conn.php';

// ตรวจสอบว่ามีการ login
$user = null;
if(isset($_SESSION['Emp_code'])){
    $Emp_code = $_SESSION['Emp_code'];

    // ดึงข้อมูล Username, Fname, Lname, Title_name, Type_id
    $sql = "SELECT e.Emp_code, e.Fname_th, e.Lname_th,  t.Title_name
        FROM employee e
        LEFT JOIN title t ON e.Title_id = t.Title_id
        WHERE e.Emp_code = '$Emp_code'";

$result = mysqli_query($conn, $sql);

if(!$result){
    die("Query Failed: " . mysqli_error($conn) . "<br>SQL: " . $sql);
}

$user = mysqli_fetch_assoc($result);

}
?>


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
            <a href="kpitype.php">ประเภทตัวชี้วัด</a> |
            <a href="indicators.php">ตัวชี้วัด</a>
        </nav>
    </div>
    <div class="user-box">
        <a href="profile.php?Emp_code=<?php echo $Emp_code ?? ''; ?>">
            <img src="img/profile.png" alt="User" style="cursor: pointer;">
        </a>
        <div>
            <strong>รหัสพนักงาน :</strong> <?php echo $user['Emp_code'] ?? ''; ?> <br>
            <strong>ชื่อ-สกุล :</strong> 
                <?php echo ($user['Fname_th'] ?? '')." ".($user['Lname_th'] ?? ''); ?> <br>
            <strong>ตำแหน่ง :</strong> 
                <?php echo $user['Title_name'] ?? ''; ?>
        </div>
        <button class="logout-btn" onclick="logout()">
            <img src="img/logout.png" alt="Logout"> 
        </button>
    </div>
</div>
