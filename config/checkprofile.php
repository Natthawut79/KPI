<?php
session_start();
include 'conn.php';

// ตรวจสอบว่าเป็น POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // รับค่าจากฟอร์ม
    $Fname_th   = mysqli_real_escape_string($conn, $_POST['Fname_th']);
    $Lname_th   = mysqli_real_escape_string($conn, $_POST['Lname_th']);
    $Fname_eng  = mysqli_real_escape_string($conn, $_POST['Fname_eng']);
    $Lname_eng  = mysqli_real_escape_string($conn, $_POST['Lname_eng']);

    // รับค่า id ตรง ๆ จาก select
    $Title_id       = mysqli_real_escape_string($conn, $_POST['Title_id']);
    $Department_id  = mysqli_real_escape_string($conn, $_POST['Department_id']);

    // รหัสพนักงานจาก hidden input (ไม่ใช้ session แล้ว ปลอดภัยกว่า)
    $Emp_code   = mysqli_real_escape_string($conn, $_POST['Emp_code']);

    // --- Update ข้อมูล ---
    $sql_update = "UPDATE employee 
                   SET Title_id = '$Title_id',
                       Fname_th = '$Fname_th',
                       Lname_th = '$Lname_th',
                       Fname_eng = '$Fname_eng',
                       Lname_eng = '$Lname_eng',
                       Department_id = '$Department_id'
                   WHERE Emp_code = '$Emp_code'";

    if (mysqli_query($conn, $sql_update)) {
    echo "<script>
            alert('บันทึกข้อมูลเรียบร้อยแล้ว!');
            window.location='../profile.php?Emp_code=$Emp_code';
          </script>";
} else {
    echo "Error: " . mysqli_error($conn);
}
}
?>
