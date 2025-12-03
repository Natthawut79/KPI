<?php
// ไฟล์: KPI/config/auth_superadmin.php
// ใชสำหรับตรวจสอบว่าเป็น Super Admin (Type_id = 2) หรือไม่

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ID ของ Super Admin คือ 2a
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