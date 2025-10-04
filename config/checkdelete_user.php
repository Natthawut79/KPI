<?php
session_start();
include 'conn.php';
include 'auth_admin.php'; // ตรวจสอบสิทธิ์แอดมิน

// ตรวจสอบว่ามี Emp_code ส่งมาหรือไม่
if (isset($_GET['Emp_code'])) {
    $Emp_code = mysqli_real_escape_string($conn, $_GET['Emp_code']);

    // SQL ลบข้อมูลพนักงาน
    $sql = "DELETE FROM employee WHERE Emp_code = '$Emp_code';";

    if (mysqli_query($conn, $sql)) {
        // ลบสำเร็จ กลับไปหน้าหลัก พร้อมส่งข้อความแจ้งเตือน
        echo "<script>alert('ลบบัญชีผู้ใช้สำเร็จ!'); window.location='../mainadmin.php';</script>";
        exit();
    } else {
        echo "เกิดข้อผิดพลาดในการลบ: " . mysqli_error($conn);
    }
} else {
    // ถ้าไม่มี Emp_code ส่งมา กลับไปหน้าหลัก
    header("Location: ../mainadmin.php");
    exit();
}

mysqli_close($conn);
?>
