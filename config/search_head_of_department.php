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

$view_mode = isset($_GET['view']) ? $_GET['view'] : 'others'; 
$is_my_work = ($view_mode === 'mine');

$searchName = isset($_GET['searchName']) ? trim($_GET['searchName']) : '';
$searchYear = isset($_GET['searchYear']) ? trim($_GET['searchYear']) : '';


$result_kpi_list = null;
$search_error_message = null; 

if (isset($_GET['search_button'])) {

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
        WHERE u.Type_id != 1 
    ";

    $bind_params_values = []; 

    if ($is_my_work) {
        $sql_kpi_list .= " AND ik.Emp_code = ?";
        $bind_params_values[] = $current_emp_code;
    } else {
        $sql_kpi_list .= " AND ik.Emp_code != ?";
        $bind_params_values[] = $current_emp_code;
        $sql_kpi_list .= " AND u.Type_id = 3"; 
        
        if ($current_user_dept_id) {
            $sql_kpi_list .= " AND d.Department_id = ?";
            $bind_params_values[] = $current_user_dept_id;
        } else {
            $sql_kpi_list .= " AND 1=0"; 
        }
    }

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