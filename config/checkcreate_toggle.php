<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'auth_admin.php'; 
include 'conn.php';

$alert_message = "";
$redirect_page = "../show_on_off.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    //  รับค่าจากฟอร์ม
    $academic_year = isset($_POST['academic_year']) ? mysqli_real_escape_string($conn, $_POST['academic_year']) : '';
    $submit_type_id = isset($_POST['submit_type_id']) ? mysqli_real_escape_string($conn, $_POST['submit_type_id']) : '';
    $start_date = isset($_POST['start-datetime']) ? mysqli_real_escape_string($conn, $_POST['start-datetime']) : '';
    $end_date = isset($_POST['end-datetime']) ? mysqli_real_escape_string($conn, $_POST['end-datetime']) : '';
    $status = isset($_POST['toggle_status']) ? $_POST['toggle_status'] : (isset($_POST['toggle_status_hidden']) ? $_POST['toggle_status_hidden'] : '');
    $description = isset($_POST['description']) && !empty($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : NULL;

    // ตรวจสอบค่าว่าง
    if (empty($academic_year) || empty($submit_type_id) || empty($start_date) || empty($end_date) || empty($status)) {
        $alert_message = "เกิดข้อผิดพลาด: กรุณากรอกข้อมูลให้ครบถ้วน (ยกเว้นหมายเหตุ)";
    } else {
        
        // ตรวจสอบว่าซ้ำซ้อนหรือไม่
        $sql_check = "SELECT Toggles_id FROM toggles_switch WHERE Academic = ? AND Submit_type_id = ?";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "si", $academic_year, $submit_type_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($result_check) > 0) {
            $alert_message = "เกิดข้อผิดพลาด: ปีการศึกษา $academic_year (รอบ $submit_type_id) นี้ มีอยู่ในระบบแล้ว";
        } else {
            $start_date_sql = $start_date . ' 00:00:00';
            $end_date_sql = $end_date . ' 23:59:59';
            
            $sql_insert = "INSERT INTO toggles_switch (Academic, Submit_type_id, Start_date, End_date, Status, Description)
                           VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt_insert = mysqli_prepare($conn, $sql_insert);
            
            if ($stmt_insert) {
                mysqli_stmt_bind_param($stmt_insert, "isssss", 
                                     $academic_year, 
                                     $submit_type_id, 
                                     $start_date_sql, 
                                     $end_date_sql, 
                                     $status,
                                     $description);
                
                if (mysqli_stmt_execute($stmt_insert)) {
                    $alert_message = "เพิ่มรอบการบันทึกสำเร็จ!";
                } else {
                    $alert_message = "เกิดข้อผิดพลาดในการบันทึก (Execute): " . mysqli_stmt_error($stmt_insert);
                }
                mysqli_stmt_close($stmt_insert);
            } else {
                $alert_message = "เกิดข้อผิดพลาดในการเตรียม SQL (Insert): " . mysqli_error($conn);
            }
        }
        mysqli_stmt_close($stmt_check);
    }
} else {
    $alert_message = "เกิดข้อผิดพลาด: ไม่มีการส่งข้อมูล (Invalid Request)";
}

mysqli_close($conn);
echo "<script>alert('" . addslashes(htmlspecialchars($alert_message)) . "');</script>";
echo '<meta http-equiv="refresh" content="0;url=' . $redirect_page . '"> ';
exit();
?>