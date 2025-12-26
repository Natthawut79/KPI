<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'auth_superadmin.php'; 
include 'conn.php';
include 'academic_year_resolver.php';          
$current_academic_year = isset($_GET['year']) ? mysqli_real_escape_string($conn, $_GET['year']) : $current_academic_year;
$alert_message = "";
$emp_code = "";
$approve_id_to_set = -1; 
$action_type = ""; 
$redirect_page = "../approve_kpi.php"; 

if (isset($_GET['approve_user_id']) && !empty($_GET['approve_user_id'])) {
    $emp_code = mysqli_real_escape_string($conn, $_GET['approve_user_id']);
    $approve_id_to_set = 2;
    $action_type = "approve";
    $redirect_page = "../approve_kpi.php?status=not_approved&search_button=";

} elseif (isset($_GET['cancel_user_id']) && !empty($_GET['cancel_user_id'])) {
    $emp_code = mysqli_real_escape_string($conn, $_GET['cancel_user_id']);
    $approve_id_to_set = 1;
    $action_type = "cancel";
    $redirect_page = "../approve_kpi.php?status=approved&search_button=";

} else {
    $alert_message = "เกิดข้อผิดพลาด: ไม่พบรหัสพนักงานหรือการดำเนินการที่ถูกต้อง";
}
if (!empty($emp_code) && $approve_id_to_set != -1) {
    
    // ดึงปีการศึกษาปัจจุบัน
    $current_year_ad = date('Y');
    $searchYear = $current_academic_year;

    $sql = "UPDATE individual_kpi 
            SET Approve_id = ? 
            WHERE Emp_code = ? AND Academic = ?";
            
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isi", $approve_id_to_set, $emp_code, $current_academic_year);
        
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