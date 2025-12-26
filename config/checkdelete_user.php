<?php
session_start();
include 'conn.php';
include 'auth_admin.php';

if (isset($_GET['Emp_code'])) {
    $Emp_code = mysqli_real_escape_string($conn, $_GET['Emp_code']);
    mysqli_begin_transaction($conn);

    try {
        $sql_user = "DELETE FROM user WHERE Emp_code = '$Emp_code'";
        mysqli_query($conn, $sql_user);

        $sql_emp = "DELETE FROM employee WHERE Emp_code = '$Emp_code'";
        mysqli_query($conn, $sql_emp);

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