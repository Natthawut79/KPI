<?php
include 'config/academic_year_resolver.php';

$filter_status = isset($_GET['status']) && is_numeric($_GET['status']) ? $_GET['status'] : '1'; 

$search_name = isset($_GET['searchName']) ? mysqli_real_escape_string($conn, $_GET['searchName']) : '';
$filter_user_type = isset($_GET['userType']) ? mysqli_real_escape_string($conn, $_GET['userType']) : 'all';
$filter_department = isset($_GET['department']) ? mysqli_real_escape_string($conn, $_GET['department']) : 'all';

$sql_status_list = "SELECT Approve_id, Status_approve_name FROM status_approve ORDER BY Approve_id ASC";
$result_status_list = mysqli_query($conn, $sql_status_list);

$sql_user_types = "SELECT Type_id, Type_name, Type_name_th FROM user_type WHERE Type_id != 1 ORDER BY Type_id";
$result_user_types = mysqli_query($conn, $sql_user_types);

$sql_departments = "SELECT Department_id, Department_name FROM department ORDER BY Department_name";
$result_departments = mysqli_query($conn, $sql_departments);

$result_employee = null; 
$search_error_message = null; 

if (isset($_GET['search_button'])) {

    $current_year_ad = date('Y');
    $searchYear = $current_academic_year;

    $sql = "SELECT e.Emp_code, t.Title_shortname, e.Fname_th, e.Lname_th, 
                   uty.Type_name, uty.Type_name_th, d.Department_name,
                   gkm.Group_ID,
                   ik.Approve_id, sa.Status_approve_name 
            FROM employee e
            LEFT JOIN title t ON e.Title_id = t.Title_id
            LEFT JOIN user u ON e.Emp_code = u.Emp_code
            LEFT JOIN user_type uty ON u.Type_id = uty.Type_id
            LEFT JOIN department d ON e.Department_id = d.Department_id
            LEFT JOIN group_kpi_mapping gkm ON u.Type_id = gkm.Type_id
            
            INNER JOIN individual_kpi ik ON e.Emp_code = ik.Emp_code AND ik.Academic = ?
            LEFT JOIN status_approve sa ON ik.Approve_id = sa.Approve_id 
            
            WHERE u.Type_id != 1";

    $bind_params_values = []; 
    $bind_params_values[] = $current_academic_year;

    $sql .= " AND ik.Approve_id = ?";
    $bind_params_values[] = $filter_status;

    if (!empty($search_name)) {
        $sql .= " AND e.Fname_th LIKE ?";
        $bind_params_values[] = $search_name . '%';
    }

    if ($filter_user_type != 'all') {
        $sql .= " AND u.Type_id = ?";
        $bind_params_values[] = $filter_user_type;
    }

    if ($filter_department != 'all') {
        $sql .= " AND d.Department_id = ?";
        $bind_params_values[] = $filter_department;
    }
            
    $sql .= " GROUP BY e.Emp_code ORDER BY e.Fname_th ASC";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        if (!empty($bind_params_values)) {
            mysqli_stmt_execute($stmt, $bind_params_values);
        } else {
            mysqli_stmt_execute($stmt);
        }
        $result_employee = mysqli_stmt_get_result($stmt); 
        mysqli_stmt_close($stmt);
    } else {
        $search_error_message = "เกิดข้อผิดพลาดในการเตรียม Query: " . mysqli_error($conn);
    }
}
?>