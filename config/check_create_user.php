<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'conn.php'; // เชื่อมต่อฐานข้อมูล

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // รับค่าจากฟอร์ม
    $Emp_code       = mysqli_real_escape_string($conn, $_POST['Emp_code']);
    $Password       = mysqli_real_escape_string($conn, $_POST['Password']);
    $Title_id       = mysqli_real_escape_string($conn, $_POST['Title_id']);
    $Fname_th       = mysqli_real_escape_string($conn, $_POST['Fname_th']);
    $Lname_th       = mysqli_real_escape_string($conn, $_POST['Lname_th']);
    $Fname_eng      = mysqli_real_escape_string($conn, $_POST['Fname_eng']);
    $Lname_eng      = mysqli_real_escape_string($conn, $_POST['Lname_eng']);
    $Department_id  = mysqli_real_escape_string($conn, $_POST['Department_id']);
    $Type_id        = mysqli_real_escape_string($conn, $_POST['Type_id']);

    // --- **[แก้ไข]** จัดการไฟล์รูปภาพ ---
    $imgData = null; // กำหนดค่าเริ่มต้นเป็น null
    if (isset($_FILES['IMGname']) && $_FILES['IMGname']['error'] == 0 && $_FILES['IMGname']['size'] > 0) {
        // อ่านข้อมูล binary ของไฟล์รูปภาพ
        $imgData = file_get_contents($_FILES['IMGname']['tmp_name']);
        if ($imgData === false) {
             // Handle error reading file if necessary
             echo "<script>alert('เกิดข้อผิดพลาดในการอ่านไฟล์รูปภาพ'); window.history.back();</script>";
             exit;
        }
    }
    // --- **[สิ้นสุดการแก้ไข]** ---


    // --- เริ่มต้น Transaction ---
    mysqli_begin_transaction($conn);

    try {
        // 1. SQL สำหรับเพิ่มข้อมูลลงในตาราง employee
        // --- **[แก้ไข]** เปลี่ยน ? ตัวที่ 6 (IMGname) และ bind_param type เป็น 'b' สำหรับ BLOB ---
        $sql_employee = "INSERT INTO employee
                        (Emp_code, Fname_th, Lname_th, Fname_eng, Lname_eng, IMGname, Title_id, Department_id)
                        VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt_employee = mysqli_prepare($conn, $sql_employee);
        // --- **[แก้ไข]** เปลี่ยน type string ตัวที่ 6 เป็น 'b' และเตรียมส่ง $imgData ---
        mysqli_stmt_bind_param($stmt_employee, "sssssbss", $Emp_code, $Fname_th, $Lname_th, $Fname_eng, $Lname_eng, $imgData, $Title_id, $Department_id);

        // --- **[แก้ไข]** ส่งข้อมูล BLOB ---
        // For LONGBLOB, you might need to send data in packets if it's large
        // Check if imgData is not null before sending
        if ($imgData !== null) {
            mysqli_stmt_send_long_data($stmt_employee, 5, $imgData); // Parameter index 5 is IMGname
        }
        // --- **[สิ้นสุดการแก้ไข]** ---

        mysqli_stmt_execute($stmt_employee);

        // 2. SQL สำหรับเพิ่มข้อมูลลงในตาราง user (เหมือนเดิม)
        $PasswordHash = password_hash($Password, PASSWORD_DEFAULT);
        $sql_user = "INSERT INTO user (Emp_code, Type_id, Password) VALUES (?, ?, ?)";

        $stmt_user = mysqli_prepare($conn, $sql_user);
        mysqli_stmt_bind_param($stmt_user, "sis", $Emp_code, $Type_id, $PasswordHash);
        mysqli_stmt_execute($stmt_user);

        // --- หากสำเร็จทั้งหมด ให้ commit ---
        mysqli_commit($conn);
        echo "<script>alert('สร้างบัญชีผู้ใช้เรียบร้อยแล้ว'); window.location='../manage_users.php';</script>";

    } catch (mysqli_sql_exception $exception) {
        // --- หากมีข้อผิดพลาด ให้ rollback ---
        mysqli_rollback($conn);
        // แสดงข้อผิดพลาด (ควรปิดใน production)
        echo "เกิดข้อผิดพลาด: " . $exception->getMessage();
        // แสดง alert หรือข้อความที่เป็นมิตรกับผู้ใช้
        // echo "<script>alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล'); window.history.back();</script>";
    } finally {
        // ปิด statement เสมอ
        if (isset($stmt_employee)) {
            mysqli_stmt_close($stmt_employee);
        }
        if (isset($stmt_user)) {
            mysqli_stmt_close($stmt_user);
        }
    }
}
mysqli_close($conn); // ปิด connection ตอนท้ายสุด
?>