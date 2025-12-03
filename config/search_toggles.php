<?php
// (ไฟล์นี้ถูกเรียกใช้โดย toggles_switch.php ซึ่ง $conn มีอยู่แล้ว)

// 1. รับค่า Filter (ปี และ ประเภทการส่ง)
$searchYear = isset($_GET['searchYear']) ? trim($_GET['searchYear']) : '';
$filter_submit_type = isset($_GET['submitType']) ? mysqli_real_escape_string($conn, $_GET['submitType']) : 'all';

// 2. Query ข้อมูลสำหรับ Dropdown "รอบการประเมิน" (ครึ่งปีแรก/หลัง)
$sql_submit_types = "SELECT Submit_type_id, Submit_type_name FROM submit_type ORDER BY Submit_type_id";
$result_submit_types = mysqli_query($conn, $sql_submit_types);

// 3. ตั้งค่าผลลัพธ์เริ่มต้น
$result_toggles = null;
$search_error_message = null; 

// 4. ตรวจสอบว่ากด "ค้นหา"
if (isset($_GET['search_button'])) {

    // 5. สร้าง SQL Query หลัก
    $sql = "SELECT ts.Toggles_id, ts.Academic, ts.Start_date, ts.End_date, ts.Status, st.Submit_type_name
            FROM toggles_switch ts
            LEFT JOIN submit_type st ON ts.Submit_type_id = st.Submit_type_id
            WHERE 1=1"; // (WHERE 1=1 เพื่อให้ต่อ AND ง่าย)

    // 6. สร้าง Array สำหรับ Bind
    $bind_params_values = []; 

    // 7. เพิ่มเงื่อนไข Filters
    if ($searchYear !== '') {
        $sql .= " AND ts.Academic LIKE ?";
        $bind_params_values[] = $searchYear . "%";
    }

    if ($filter_submit_type != 'all') {
        $sql .= " AND ts.Submit_type_id = ?";
        $bind_params_values[] = $filter_submit_type;
    }
    
    $sql .= " ORDER BY ts.Academic DESC, ts.Submit_type_id ASC";

    // 8. รัน Query
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt === false) {
        $search_error_message = "SQL Prepare Error: " . mysqli_error($conn);
    } else {
        if (!empty($bind_params_values)) {
            mysqli_stmt_execute($stmt, $bind_params_values); 
        } else {
            mysqli_stmt_execute($stmt);
        }
        $result_toggles = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    }
} 
?>