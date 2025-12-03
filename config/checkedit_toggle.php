<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'auth_admin.php'; 
include 'conn.php';

$alert_message = "";
$redirect_page = "../show_on_off.php"; // กลับไปหน้ารายการ

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 2. รับค่าจากฟอร์ม
    $toggles_id = isset($_POST['toggles_id']) ? mysqli_real_escape_string($conn, $_POST['toggles_id']) : '';
    $academic_year = isset($_POST['academic_year']) ? mysqli_real_escape_string($conn, $_POST['academic_year']) : '';
    $submit_type_id = isset($_POST['submit_type_id']) ? mysqli_real_escape_string($conn, $_POST['submit_type_id']) : '';
    $start_date = isset($_POST['start-datetime']) ? mysqli_real_escape_string($conn, $_POST['start-datetime']) : '';
    $end_date = isset($_POST['end-datetime']) ? mysqli_real_escape_string($conn, $_POST['end-datetime']) : '';
    $status = isset($_POST['toggle_status']) ? $_POST['toggle_status'] : (isset($_POST['toggle_status_hidden']) ? $_POST['toggle_status_hidden'] : '');
    $description = isset($_POST['description']) && !empty($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : NULL;


    // 3. ตรวจสอบค่าว่าง
    if (empty($toggles_id) || empty($academic_year) || empty($submit_type_id) || empty($start_date) || empty($end_date) || empty($status)) {
        $alert_message = "เกิดข้อผิดพลาด: กรุณากรอกข้อมูลให้ครบถ้วน (ยกเว้นหมายเหตุ)";
    } else {
        
        // 4. ตรวจสอบว่าซ้ำซ้อนหรือไม่ (ต้องไม่ซ้ำกับ "แถวอื่น")
        $sql_check = "SELECT Toggles_id FROM toggles_switch 
                      WHERE Academic = ? AND Submit_type_id = ? 
                      AND Toggles_id != ?"; 
        
        $stmt_check = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "sii", $academic_year, $submit_type_id, $toggles_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($result_check) > 0) {
            $alert_message = "เกิดข้อผิดพลาด: ปีการศึกษา $academic_year (รอบ $submit_type_id) นี้ มีอยู่ในระบบแล้ว (ในรายการอื่น)";
        } else {
            
            // 4.1 ✅ [แก้ไข] ต่อท้ายเวลา ให้ครอบคลุมทั้งวัน
            $start_date_sql = $start_date . ' 00:00:00';
            $end_date_sql = $end_date . ' 23:59:59';

            // 4.2 ให้ UPDATE
            $sql_update = "UPDATE toggles_switch SET 
                                Academic = ?, 
                                Submit_type_id = ?, 
                                Start_date = ?, 
                                End_date = ?, 
                                Status = ?, 
                                Description = ?
                           WHERE Toggles_id = ?";
            
            $stmt_update = mysqli_prepare($conn, $sql_update);
            
            if ($stmt_update) {
                // 4.3 ✅ [แก้ไข] ใช้ตัวแปร $start_date_sql และ $end_date_sql
                mysqli_stmt_bind_param($stmt_update, "isssssi", 
                                     $academic_year, 
                                     $submit_type_id, 
                                     $start_date_sql, 
                                     $end_date_sql, 
                                     $status,
                                     $description,
                                     $toggles_id); 
                
                if (mysqli_stmt_execute($stmt_update)) {
                    $alert_message = "แก้ไขรอบการบันทึกสำเร็จ!";
                } else {
                    $alert_message = "เกิดข้อผิดพลาดในการบันทึก (Execute): " . mysqli_stmt_error($stmt_update);
                }
                mysqli_stmt_close($stmt_update);
            } else {
                $alert_message = "เกิดข้อผิดพลาดในการเตรียม SQL (Update): " . mysqli_error($conn);
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