<?php


$view_mode = isset($_GET['view']) ? $_GET['view'] : 'others'; 
$is_my_work = ($view_mode === 'mine');


$searchName = isset($_GET['searchName']) ? trim($_GET['searchName']) : '';
$searchYear = isset($_GET['searchYear']) ? trim($_GET['searchYear']) : '';
$filter_user_type = isset($_GET['userType']) ? mysqli_real_escape_string($conn, $_GET['userType']) : 'all';
$filter_department = isset($_GET['department']) ? mysqli_real_escape_string($conn, $_GET['department']) : 'all';


$sql_user_types = "SELECT Type_id, Type_name FROM user_type WHERE Type_id != 1 ORDER BY Type_id";
$result_user_types = mysqli_query($conn, $sql_user_types);

$sql_departments = "SELECT Department_id, Department_name FROM department ORDER BY Department_name";
$result_departments = mysqli_query($conn, $sql_departments);

// 4. ตั้งค่าผลลัพธ์เริ่มต้นเป็น null
$result_kpi_list = null;
$search_error_message = null; 

// 5. ตรวจสอบว่ามีการกดปุ่ม "ค้นหา" หรือไม่
if (isset($_GET['search_button'])) {

    // 6. สร้าง Query
    $sql_kpi_list = "
        SELECT 
            e.Emp_code, 
            t.Title_shortname, 
            e.Fname_th, 
            e.Lname_th, 
            d.Department_name,
            uty.Type_name, 
            ik.kpi_year,
            gkm.Group_ID 
        FROM 
            (
                SELECT DISTINCT 
                     Emp_code, 
                     Academic AS kpi_year 
                 FROM individual_kpi
                 WHERE Academic IS NOT NULL AND Academic != 0
            ) AS ik
        JOIN 
            employee e ON ik.Emp_code = e.Emp_code
        JOIN 
            department d ON e.Department_id = d.Department_id
        JOIN 
            title t ON e.Title_id = t.Title_id
        JOIN
            user u ON e.Emp_code = u.Emp_code
        LEFT JOIN 
            user_type uty ON u.Type_id = uty.Type_id 
        JOIN 
            group_kpi_mapping gkm ON u.Type_id = gkm.Type_id 
        WHERE
            u.Type_id != 1 
    ";

    // 7. สร้าง Array สำหรับ Bind (วิธีเดียวกับ approve_kpi.php)
    $bind_params_values = []; 

    // 7.1 View Mode
    // ($current_emp_code ถูกประกาศไว้ใน mainsuper.php ก่อน include ไฟล์นี้)
    if ($is_my_work) {
        $sql_kpi_list .= " AND ik.Emp_code = ?";
        $bind_params_values[] = $current_emp_code; 
    } else {
        $sql_kpi_list .= " AND ik.Emp_code != ?";
        $bind_params_values[] = $current_emp_code;
    }

    // 7.2 Filters
    if ($searchName !== '') {
        $sql_kpi_list .= " AND (e.Fname_th LIKE ? OR e.Lname_th LIKE ?)";
        $bind_params_values[] = $searchName . "%"; 
        $bind_params_values[] = $searchName . "%";
    }
    
    if ($searchYear !== '') {
        $sql_kpi_list .= " AND CAST(ik.kpi_year AS CHAR) LIKE ?";
        $bind_params_values[] = $searchYear . "%"; 
    }

    if ($filter_user_type != 'all') {
        $sql_kpi_list .= " AND u.Type_id = ?";
        $bind_params_values[] = $filter_user_type;
    }

    if ($filter_department != 'all') {
        $sql_kpi_list .= " AND d.Department_id = ?";
        $bind_params_values[] = $filter_department;
    }
    
    $sql_kpi_list .= " ORDER BY e.Fname_th ASC, ik.kpi_year DESC";

    // 8. เตรียมและรัน Query
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