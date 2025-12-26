<?php
session_start();
include "conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $KPI_type_id = mysqli_real_escape_string($conn, $_POST['KPI_type_id']);
    $Order_no = mysqli_real_escape_string($conn, $_POST['Order_no']);
    $KPI_topic_name = mysqli_real_escape_string($conn, $_POST['KPI_topic_name']);
    $Unit = mysqli_real_escape_string($conn, $_POST['Unit']);
    $Goal = mysqli_real_escape_string($conn, $_POST['Goal']);
    $Score_criteria = mysqli_real_escape_string($conn, $_POST['Score_criteria']);
    $Weight = mysqli_real_escape_string($conn, $_POST['Weight']);
    $Important_level_no = mysqli_real_escape_string($conn, $_POST['Important_level_no']);
    $Description_text = mysqli_real_escape_string($conn, $_POST['Description_text']);
    $Additional = mysqli_real_escape_string($conn, $_POST['fill_data']);
    $Retrieve = mysqli_real_escape_string($conn, $_POST['fetch_data']);
    $criteria_1 = isset($_POST['criteria_1']) ? mysqli_real_escape_string($conn, $_POST['criteria_1']) : '';
    $criteria_2 = isset($_POST['criteria_2']) ? mysqli_real_escape_string($conn, $_POST['criteria_2']) : '';
    $criteria_3 = isset($_POST['criteria_3']) ? mysqli_real_escape_string($conn, $_POST['criteria_3']) : '';
    $criteria_4 = isset($_POST['criteria_4']) ? mysqli_real_escape_string($conn, $_POST['criteria_4']) : '';
    $criteria_5 = isset($_POST['criteria_5']) ? mysqli_real_escape_string($conn, $_POST['criteria_5']) : '';

    $subject_id_sql_val = "NULL"; 
    if (isset($_POST['subject_id']) && $_POST['subject_id'] !== '' && is_numeric($_POST['subject_id'])) {
        $subject_id_sql_val = intval($_POST['subject_id']);
    }

    $Table_id_sql_val = "NULL";
    if ($Retrieve === 'yes' && isset($_POST['Table_id']) && $_POST['Table_id'] !== '' && is_numeric($_POST['Table_id'])) {
        $Table_id_sql_val = intval($_POST['Table_id']);
    }

    $Publication_type_id_sql_val = "NULL";
    if (
        $Retrieve === 'yes' && isset($_POST['Table_id']) && $_POST['Table_id'] == '3' &&
        isset($_POST['Publication_type_id']) && $_POST['Publication_type_id'] !== '' && is_numeric($_POST['Publication_type_id'])
    ) {
        $Publication_type_id_sql_val = intval($_POST['Publication_type_id']);
    }

    $sql_insert = "INSERT into kpi_topic (
                    KPI_type_id, Order_no, KPI_topic_name, Unit, Goal, Score_criteria, Weight, 
                    Important_level_no, Description_text, Additional, Retrieve, 
                    subject_id,     
                    Table_id, Publication_type_id,
                    criteria_1, criteria_2, criteria_3, criteria_4, criteria_5
                )
                values (
                    '$KPI_type_id', '$Order_no', '$KPI_topic_name', '$Unit', '$Goal', 
                    '$Score_criteria', '$Weight', '$Important_level_no', '$Description_text', 
                    '$Additional', '$Retrieve', 
                    $subject_id_sql_val,            
                    $Table_id_sql_val, $Publication_type_id_sql_val,
                    '$criteria_1', '$criteria_2', '$criteria_3', '$criteria_4', '$criteria_5'
                )";

    if (mysqli_query($conn, $sql_insert)) {
        echo "<script>
                alert('สร้างตัวชี้วัดสำเร็จ!');
                window.location.href = '../indicators.php';
              </script>";
    } else {
        echo "Error: " . $sql_insert . "<br>" . mysqli_error($conn);
    }

} else {
    echo "Invalid request method.";
}
mysqli_close($conn);
?>