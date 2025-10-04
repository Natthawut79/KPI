<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'conn.php'; // เชื่อมต่อฐานข้อมูล

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // รับค่าจากฟอร์ม
    $Emp_code   = mysqli_real_escape_string($conn, $_POST['Emp_code']);
    $Password   = mysqli_real_escape_string($conn, $_POST['Password']);
    $Title_id   = mysqli_real_escape_string($conn, $_POST['Title_id']);
    $Fname_th   = mysqli_real_escape_string($conn, $_POST['Fname_th']);
    $Lname_th   = mysqli_real_escape_string($conn, $_POST['Lname_th']);
    $Fname_eng  = mysqli_real_escape_string($conn, $_POST['Fname_eng']);
    $Lname_eng  = mysqli_real_escape_string($conn, $_POST['Lname_eng']);
    $Department_id = mysqli_real_escape_string($conn, $_POST['Department_id']);
    $Type_id    = mysqli_real_escape_string($conn, $_POST['Type_id']);

    // การเข้ารหัสรหัสผ่าน
    $PasswordHash = password_hash($Password, PASSWORD_DEFAULT);

    // จัดการไฟล์รูปภาพ
    $IMGname = null;
    if (!empty($_FILES['IMGname']['name'])) {
        $targetDir = "../uploads/profile/";  // สร้างโฟลเดอร์นี้ไว้ด้วย
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $IMGname = time() . "_" . basename($_FILES["IMGname"]["name"]);
        $targetFile = $targetDir . $IMGname;

        if (move_uploaded_file($_FILES["IMGname"]["tmp_name"], $targetFile)) {
            // อัปโหลดสำเร็จ
        } else {
            $IMGname = null; // ถ้าอัปโหลดไม่สำเร็จ
        }
    }

    // SQL Insert
    $sql = "INSERT INTO user 
            (Emp_code, Password, Title_id, Fname_th, Lname_th, Fname_eng, Lname_eng, Department_id, Type_id, IMGname)
            VALUES 
            ('$Emp_code', '$PasswordHash', '$Title_id', '$Fname_th', '$Lname_th', '$Fname_eng', '$Lname_eng', '$Department_id', '$Type_id', '$IMGname')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('สร้างบัญชีผู้ใช้เรียบร้อยแล้ว'); window.location='../mainadmin.php';</script>";
    } else {
        echo "เกิดข้อผิดพลาด: " . mysqli_error($conn);
    }
}
?>
