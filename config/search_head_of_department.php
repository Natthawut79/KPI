<?php

$current_user_dept_id = null;
$stmt_dept = mysqli_prepare($conn, "SELECT Department_id FROM employee WHERE Emp_code = ? LIMIT 1");
if ($stmt_dept) {
    mysqli_stmt_bind_param($stmt_dept, "s", $current_emp_code);
    mysqli_stmt_execute($stmt_dept);
    $result_dept = mysqli_stmt_get_result($stmt_dept);
    if ($row_dept = mysqli_fetch_assoc($result_dept)) {
        $current_user_dept_id = $row_dept['Department_id'];
    }
    mysqli_stmt_close($stmt_dept);
}

// 2. รับค่า View Mode (default = others)
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'others'; 
$is_my_work = ($view_mode === 'mine');

// 3. รับค่า Filter (2 ช่อง: ชื่อ, ปี)
$searchName = isset($_GET['searchName']) ? trim($_GET['searchName']) : '';
$searchYear = isset($_GET['searchYear']) ? trim($_GET['searchYear']) : '';

// (ไม่มี $result_user_types)

// 5. ตั้งค่าผลลัพธ์เริ่มต้น
$result_kpi_list = null;
$search_error_message = null; 

// 6. ตรวจสอบว่ากด "ค้นหา"
if (isset($_GET['search_button'])) {

    // 7. ✅ [แก้ไข] สร้าง SQL Query หลัก (กลับไปใช้ Type_id != 1)
    $sql_kpi_list = "
        SELECT 
            e.Emp_code, t.Title_shortname, e.Fname_th, e.Lname_th, 
            d.Department_name, uty.Type_name, ik.kpi_year, gkm.Group_ID 
        FROM (
            SELECT DISTINCT Emp_code, Academic AS kpi_year 
            FROM individual_kpi WHERE Academic IS NOT NULL AND Academic != 0
        ) AS ik
        JOIN employee e ON ik.Emp_code = e.Emp_code
        JOIN department d ON e.Department_id = d.Department_id
        JOIN title t ON e.Title_id = t.Title_id
        JOIN user u ON e.Emp_code = u.Emp_code
        LEFT JOIN user_type uty ON u.Type_id = uty.Type_id 
        JOIN group_kpi_mapping gkm ON u.Type_id = gkm.Type_id 
        WHERE u.Type_id != 1 -- ✅ [FIX] กลับไปใช้เงื่อนไขกว้าง (ไม่เอา Admin)
    ";

    // 8. สร้าง Arrayสำหรับ Bind
    $bind_params_values = []; 

    // 8.1 ✅ [แก้ไข] ตรรกะ View Mode
    if ($is_my_work) {
        // "ผลงานของฉัน"
        // (SQL: WHERE u.Type_id != 1 AND ik.Emp_code = ?)
        // นี่จะค้นหาเจอ Type_id 5 (หัวหน้าสาขา) ของคุณ
        $sql_kpi_list .= " AND ik.Emp_code = ?";
        $bind_params_values[] = $current_emp_code;
    } else {
        // "ผลงานของบุคลากรในสาขา"
        $sql_kpi_list .= " AND ik.Emp_code != ?"; // 1. ไม่ใช่ฉัน
        $bind_params_values[] = $current_emp_code;
        
        // 2. ✅ [เพิ่ม] บังคับให้เป็น Type_id 3 (อาจารย์) เฉพาะในโหมดนี้
        $sql_kpi_list .= " AND u.Type_id = 3"; 
        
        if ($current_user_dept_id) {
            $sql_kpi_list .= " AND d.Department_id = ?"; // 3. Department_id ต้องตรงกับฉัน
            $bind_params_values[] = $current_user_dept_id;
        } else {
            $sql_kpi_list .= " AND 1=0"; 
        }
    }

    // 8.2 Filters (เหมือนเดิม)
    if ($searchName !== '') {
        $sql_kpi_list .= " AND (e.Fname_th LIKE ? OR e.Lname_th LIKE ?)";
        $bind_params_values[] = $searchName . "%";
        $bind_params_values[] = $searchName . "%";
    }
    if ($searchYear !== '') {
        $sql_kpi_list .= " AND CAST(ik.kpi_year AS CHAR) LIKE ?";
        $bind_params_values[] = $searchYear . "%";
    }
    
    $sql_kpi_list .= " ORDER BY e.Fname_th ASC, ik.kpi_year DESC";

    // 9. รัน Query (เหมือนเดิม)
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