<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'auth_admin.php'; 
include 'conn.php';           

$alert_message = "";
$redirect_page = "../show_on_off.php";

// ตรวจสอบว่าได้รับ Toggles_id มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $alert_message = "เกิดข้อผิดพลาด: ไม่พบ ID ของรายการที่ต้องการลบ";
} else {
    $toggles_id_to_delete = mysqli_real_escape_string($conn, $_GET['id']);

    $sql = "DELETE FROM toggles_switch WHERE Toggles_id = ?";
            
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // (i = integer)
        mysqli_stmt_bind_param($stmt, "i", $toggles_id_to_delete);
        
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            
            if ($affected_rows > 0) {
                $alert_message = "ลบรายการรอบการบันทึกสำเร็จ!";
            } else {
                $alert_message = "ไม่พบรายการที่ต้องการลบ (หรืออาจถูกลบไปแล้ว)";
            }
        } else {
            $alert_message = "เกิดข้อผิดพลาดในการลบ: " . mysqli_stmt_error($stmt);
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