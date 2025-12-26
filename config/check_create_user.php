<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $Emp_code       = mysqli_real_escape_string($conn, $_POST['Emp_code']);
    $Password       = mysqli_real_escape_string($conn, $_POST['Password']);
    $Title_id       = mysqli_real_escape_string($conn, $_POST['Title_id']);
    $Fname_th       = mysqli_real_escape_string($conn, $_POST['Fname_th']);
    $Lname_th       = mysqli_real_escape_string($conn, $_POST['Lname_th']);
    $Fname_eng      = mysqli_real_escape_string($conn, $_POST['Fname_eng']);
    $Lname_eng      = mysqli_real_escape_string($conn, $_POST['Lname_eng']);
    $Department_id  = mysqli_real_escape_string($conn, $_POST['Department_id']);
    $Type_id        = mysqli_real_escape_string($conn, $_POST['Type_id']);

    // จัดการไฟล์รูปภาพ 
    $imgData = null; // กำหนดค่าเริ่มต้นเป็น null
    if (isset($_FILES['IMGname']) && $_FILES['IMGname']['error'] == 0 && $_FILES['IMGname']['size'] > 0) {
        // อ่านข้อมูล binary ของไฟล์รูปภาพ
        $imgData = file_get_contents($_FILES['IMGname']['tmp_name']);
        if ($imgData === false) {
             echo "<script>alert('เกิดข้อผิดพลาดในการอ่านไฟล์รูปภาพ'); window.history.back();</script>";
             exit;
        }
    }

    mysqli_begin_transaction($conn);

    try {
        $sql_employee = "INSERT INTO employee
                        (Emp_code, Fname_th, Lname_th, Fname_eng, Lname_eng, IMGname, Title_id, Department_id)
                        VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt_employee = mysqli_prepare($conn, $sql_employee);
        mysqli_stmt_bind_param($stmt_employee, "sssssbss", $Emp_code, $Fname_th, $Lname_th, $Fname_eng, $Lname_eng, $imgData, $Title_id, $Department_id);

        //ถ้าไฟล์รูปเกินขนาดที่กำหนด จะไม่สามารถอัปโหลดได้
       if ($imgData !== null) {
            $chunkSize = 8192; 
            $length = strlen($imgData);
            
            for ($i = 0; $i < $length; $i += $chunkSize) {
                $chunk = substr($imgData, $i, $chunkSize);
                mysqli_stmt_send_long_data($stmt_employee, 5, $chunk);
            }
        }

        mysqli_stmt_execute($stmt_employee);

        $PasswordHash = password_hash($Password, PASSWORD_DEFAULT);
        $sql_user = "INSERT INTO user (Emp_code, Type_id, Password) VALUES (?, ?, ?)";

        $stmt_user = mysqli_prepare($conn, $sql_user);
        mysqli_stmt_bind_param($stmt_user, "sis", $Emp_code, $Type_id, $PasswordHash);
        mysqli_stmt_execute($stmt_user);

        mysqli_commit($conn);
        echo "<script>alert('สร้างบัญชีผู้ใช้เรียบร้อยแล้ว'); window.location='../manage_users.php';</script>";

    } catch (mysqli_sql_exception $exception) {
        // --- หากมีข้อผิดพลาด ให้ rollback ---
        mysqli_rollback($conn);
        echo "เกิดข้อผิดพลาด: " . $exception->getMessage();
    } finally {
        if (isset($stmt_employee)) {
            mysqli_stmt_close($stmt_employee);
        }
        if (isset($stmt_user)) {
            mysqli_stmt_close($stmt_user);
        }
    }
}
mysqli_close($conn);
?>