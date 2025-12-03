<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'auth_superadmin.php'; 
include 'conn.php';          

$alert_message = "";
$emp_code = "";
$approve_id_to_set = -1; 
$action_type = ""; 
$redirect_page = "../approve_kpi.php"; 

// 3. ตรวจสอบการดำเนินการ (Approve หรือ Cancel)
if (isset($_GET['approve_user_id']) && !empty($_GET['approve_user_id'])) {
    // --- ถ้าเป็นการ "อนุมัติ" ---
    $emp_code = mysqli_real_escape_string($conn, $_GET['approve_user_id']);
    $approve_id_to_set = 2; // (2 = อนุมัติแล้ว)
    $action_type = "approve";
    $redirect_page = "../approve_kpi.php?status=not_approved&search_button="; // กลับไปหน้ารออนุมัติ

} elseif (isset($_GET['cancel_user_id']) && !empty($_GET['cancel_user_id'])) {
    // --- ถ้าเป็นการ "ยกเลิก" ---
    $emp_code = mysqli_real_escape_string($conn, $_GET['cancel_user_id']);
    $approve_id_to_set = 1; // (0 = ยังไม่อนุมัติ/ยกเลิก)
    $action_type = "cancel";
    $redirect_page = "../approve_kpi.php?status=approved&search_button="; // กลับไปหน้าที่อนุมัติแล้ว

} else {
    $alert_message = "เกิดข้อผิดพลาด: ไม่พบรหัสพนักงานหรือการดำเนินการที่ถูกต้อง";
}


// 5. ตรวจสอบว่ามี Emp_code และการดำเนินการที่ถูกต้องหรือไม่
if (!empty($emp_code) && $approve_id_to_set != -1) {
    
    // 6. ดึงปีการศึกษาปัจจุบัน
    $current_year_ad = date('Y');
    $current_academic_year = intval($current_year_ad) + 543;

    // 7. สร้าง SQL UPDATE
    $sql = "UPDATE individual_kpi 
            SET Approve_id = ? 
            WHERE Emp_code = ? AND Academic = ?";
            
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isi", $approve_id_to_set, $emp_code, $current_academic_year);
        
        // 8. Execute
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            
            if ($affected_rows > 0) {
                if ($action_type == "approve") {
                    $alert_message = "อนุมัติผลงานเรียบร้อยแล้ว";
                } else {
                    $alert_message = "ยกเลิกการอนุมัติผลงานเรียบร้อยแล้ว";
                }
            } else {
                if ($action_type == "approve") {
                    $alert_message = "ไม่พบข้อมูลผลงาน (KPI) ที่ต้องอนุมัติ";
                } else {
                     $alert_message = "ไม่พบข้อมูลผลงาน (KPI) ที่ต้องยกเลิก";
                }
            }
        } else {
            $alert_message = "เกิดข้อผิดพลาดในการอัปเดต: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $alert_message = "เกิดข้อผิดพลาดในการเตรียม SQL: " . mysqli_error($conn);
    }
}

mysqli_close($conn);

echo "<script>alert('" . addslashes(htmlspecialchars($alert_message)) . "');</script>";
echo '<meta http-equiv="refresh" content="0;url=' . $redirect_page . '"> ';
exit();
?>