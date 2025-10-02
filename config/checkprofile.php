<?php
session_start();
include 'conn.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $Fname_th = mysqli_real_escape_string($conn, $_POST['Fname_th']);
    $Lname_th = mysqli_real_escape_string($conn, $_POST['Lname_th']);
    $Fname_eng = mysqli_real_escape_string($conn, $_POST['Fname_eng']);
    $Lname_eng = mysqli_real_escape_string($conn, $_POST['Lname_eng']);

    // รับค่า id ตรง ๆ จาก select แปลงค่า name เป็น id เพื่อบันทึก
    $Title_id = mysqli_real_escape_string($conn, $_POST['Title_id']);
    $Department_id = mysqli_real_escape_string($conn, $_POST['Department_id']);

    $Emp_code = mysqli_real_escape_string($conn, $_POST['Emp_code']);

    $sql_update = "UPDATE employee 
                   SET Title_id = '$Title_id',
                       Fname_th = '$Fname_th',
                       Lname_th = '$Lname_th',
                       Fname_eng = '$Fname_eng',
                       Lname_eng = '$Lname_eng',
                       Department_id = '$Department_id'";

    if (!empty($_FILES['IMGname']['tmp_name'])) {
        $imgData = addslashes(file_get_contents($_FILES['IMGname']['tmp_name']));
        $sql_update .= ", IMGname = '$imgData'";
    }

    $sql_update .= " WHERE Emp_code = '$Emp_code'";

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