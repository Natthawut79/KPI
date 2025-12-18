<?php

// 1. รับค่า Filter (1 ช่อง: ปี)
$searchYear = isset($_GET['searchYear']) ? trim($_GET['searchYear']) : '';

// 2. (ไม่มี Dropdown ให้ Query)

// 3. ตั้งค่าผลลัพธ์เริ่มต้น
$result_kpi_list = null;
$search_error_message = null; 

// 4. ตรวจสอบว่ากด "ค้นหา"
if (isset($_GET['search_button'])) {

    // 5. สร้าง SQL Query หลัก (เหมือน mainsuper)
    $sql_kpi_list = "
    SELECT 
        e.Emp_code, t.Title_shortname, e.Fname_th, e.Lname_th, 
        d.Department_name, uty.Type_name_th, ik.kpi_year, ik.Approve_id, sa.Status_approve_name, gkm.Group_ID 
    FROM (
        SELECT DISTINCT Emp_code, Academic AS kpi_year, Approve_id 
        FROM individual_kpi WHERE Academic IS NOT NULL AND Academic != 0
    ) AS ik
    JOIN employee e ON ik.Emp_code = e.Emp_code
    JOIN department d ON e.Department_id = d.Department_id
    JOIN title t ON e.Title_id = t.Title_id
    JOIN user u ON e.Emp_code = u.Emp_code
    LEFT JOIN user_type uty ON u.Type_id = uty.Type_id 
    JOIN group_kpi_mapping gkm ON u.Type_id = gkm.Type_id
    LEFT JOIN 
            status_approve sa ON ik.Approve_id = sa.Approve_id 
    
    WHERE ik.Emp_code = ? 
";

    // 7. สร้าง Array สำหรับ Bind
    $bind_params_values = []; 
    $bind_params_values[] = $current_emp_code; // 1. Bind รหัสของคนที่ล็อกอิน

    // 8. Filters (มีแค่ปี)
    if ($searchYear !== '') {
        $sql_kpi_list .= " AND CAST(ik.kpi_year AS CHAR) LIKE ?";
        $bind_params_values[] = $searchYear . "%";
    }
    
    $sql_kpi_list .= " ORDER BY ik.kpi_year DESC";

    // 9. รัน Query
    $stmt = mysqli_prepare($conn, $sql_kpi_list);
    
    if ($stmt === false) {
        $search_error_message = "SQL Prepare Error: " . mysqli_error($conn);
    } else {
        if (!empty($bind_params_values)) {
            mysqli_stmt_execute($stmt, $bind_params_values); 
        } else {
            mysqli_stmt_execute($stmt);
        }
        $result_kpi_list = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    }
} 
?>