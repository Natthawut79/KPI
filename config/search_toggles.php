<?php
$searchYear = isset($_GET['searchYear']) ? trim($_GET['searchYear']) : '';
$filter_submit_type = isset($_GET['submitType']) ? mysqli_real_escape_string($conn, $_GET['submitType']) : 'all';

$sql_submit_types = "SELECT Submit_type_id, Submit_type_name FROM submit_type ORDER BY Submit_type_id";
$result_submit_types = mysqli_query($conn, $sql_submit_types);

$result_toggles = null;
$search_error_message = null; 

if (isset($_GET['search_button'])) {

    $sql = "SELECT ts.Toggles_id, ts.Academic, ts.Start_date, ts.End_date, ts.Status, st.Submit_type_name
            FROM toggles_switch ts
            LEFT JOIN submit_type st ON ts.Submit_type_id = st.Submit_type_id
            WHERE 1=1";

    $bind_params_values = []; 

    if ($searchYear !== '') {
        $sql .= " AND ts.Academic LIKE ?";
        $bind_params_values[] = $searchYear . "%";
    }

    if ($filter_submit_type != 'all') {
        $sql .= " AND ts.Submit_type_id = ?";
        $bind_params_values[] = $filter_submit_type;
    }
    
    $sql .= " ORDER BY ts.Academic DESC, ts.Submit_type_id ASC";

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