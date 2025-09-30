<?php
session_start();
include "config/conn.php";

if (!isset($_SESSION['Emp_code'])) {
    echo "กรุณาเข้าสู่ระบบก่อน!";
    exit();
}

$Emp_code   = mysqli_real_escape_string($conn, $_SESSION['Emp_code']);
$Title_name = mysqli_real_escape_string($conn, $_POST['Title_name']);
$Fname_th   = mysqli_real_escape_string($conn, $_POST['Fname_th']);
$Lname_th   = mysqli_real_escape_string($conn, $_POST['Lname_th']);
$Fname_eng  = mysqli_real_escape_string($conn, $_POST['Fname_eng']);
$Lname_eng  = mysqli_real_escape_string($conn, $_POST['Lname_eng']);

$sql = "UPDATE employee 
        SET Title_name = '$Title_name', 
            Fname_th = '$Fname_th', 
            Lname_th = '$Lname_th', 
            Fname_eng = '$Fname_eng', 
            Lname_eng = '$Lname_eng' 
        WHERE Emp_code = '$Emp_code'";

if (mysqli_query($conn, $sql)) {
    header("Location: homepage.php?success=1");
    exit();
} else {
    echo "เกิดข้อผิดพลาดในการอัพเดตข้อมูล: " . mysqli_error($conn);
}

mysqli_close($conn);
?>

