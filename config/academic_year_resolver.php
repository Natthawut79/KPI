<?php
date_default_timezone_set('Asia/Bangkok');
$current_datetime_str = date('Y-m-d H:i:s');
$current_academic_year = null;

// 2. ค้นหาปีการศึกษาที่ถูกต้องจากตาราง toggles_switch
// โดยค้นหาปีที่วันเวลาปัจจุบันอยู่ระหว่าง Start_date และ End_date
$sql_get_academic_year = "SELECT Academic 
                          FROM toggles_switch 
                          WHERE ? BETWEEN Start_date AND End_date
                          ORDER BY End_date DESC 
                          LIMIT 1";

// ตรวจสอบการเตรียม Statement
if ($stmt_year = $conn->prepare($sql_get_academic_year)) {
    $stmt_year->bind_param("s", $current_datetime_str);
    $stmt_year->execute();
    $result_year = $stmt_year->get_result();

    if ($row_year = $result_year->fetch_assoc()) {
        // กำหนดตัวแปรปีการศึกษาที่ถูกต้อง (เช่น 2568)
        $current_academic_year = $row_year['Academic']; 
    }
    $stmt_year->close();
}

// 3. กำหนด Fallback: หากไม่พบปีการศึกษาที่ใช้งานอยู่
if ($current_academic_year === null) {
    $current_academic_year = intval(date('Y')) + 543;
}
?>