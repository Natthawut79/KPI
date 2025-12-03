<?php 
    $page_title = "หน้าหลัก - ตัวชี้วัด";
    include 'templates/navbar.php';
    include 'config/conn.php';
    include 'config/auth_admin.php';

    // นับจำนวนผู้ใช้
    $sql = "SELECT COUNT(*) AS total FROM employee";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $userCount = $row['total'];

?>
<link rel="stylesheet" href="css/mainadmin.css">
<div class="container">
    <div class="stats">
      <div class="stat-box">
        <h3>จำนวนผู้ใช้ในระบบ</h3>
        <div class="stat-number" id="userCount"><?php echo $userCount; ?> 👥</div>
      </div>
    </div>
    
<?php include 'templates/footer.php'; ?>