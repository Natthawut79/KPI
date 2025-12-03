<?php
session_start();
include 'conn.php';
include 'auth_admin.php'; // ตรวจสอบสิทธิ์แอดมิน

// ตรวจสอบว่ามี Emp_code ส่งมาหรือไม่
if (isset($_GET['Emp_code'])) {
    $Emp_code = mysqli_real_escape_string($conn, $_GET['Emp_code']);

    // เริ่มต้น transaction (เพื่อให้แน่ใจว่าทำครบหรือยกเลิกทั้งชุด)
    mysqli_begin_transaction($conn);

    try {
        // ลบจาก user ก่อน (เผื่อมี foreign key)
        $sql_user = "DELETE FROM user WHERE Emp_code = '$Emp_code'";
        mysqli_query($conn, $sql_user);

        // ลบจาก employee ต่อ
        $sql_emp = "DELETE FROM employee WHERE Emp_code = '$Emp_code'";
        mysqli_query($conn, $sql_emp);

        // ถ้าไม่มีปัญหา ให้ commit
        mysqli_commit($conn);

        echo "<script>alert('ลบบัญชีผู้ใช้สำเร็จ!'); window.location='../manage_users.php';</script>";
        exit();

    } catch (Exception $e) {
        // ถ้ามีปัญหา ให้ rollback ย้อนกลับ
        mysqli_rollback($conn);
        echo "เกิดข้อผิดพลาดในการลบ: " . $e->getMessage();
    }

} else {
    // ถ้าไม่มี Emp_code ส่งมา กลับไปหน้าหลัก
    header("Location: ../mainadmin.php");
    exit();
}

mysqli_close($conn);
?>