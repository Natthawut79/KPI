<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$superadmin_type_id = 2; 

if (!isset($_SESSION['Type_id']) || $_SESSION['Type_id'] != $superadmin_type_id) {
    // ถ้าไม่ใช่ Super Admin, ให้ออกจากหน้านี้
    // คุณสามารถเปลี่ยนหน้าที่จะ redirect ได้ตามต้องการ
    echo "<script>
            alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
            window.location.href = '../login.php'; 
          </script>";
    exit;
}
?>