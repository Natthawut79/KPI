<?php
session_start();
include "conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    $KPI_type_id = mysqli_real_escape_string($conn, $_POST['KPI_type_id']);
    $Order_no = mysqli_real_escape_string($conn, $_POST['Order_no']);
    $KPI_topic_name = mysqli_real_escape_string($conn, $_POST['KPI_topic_name']);
    $Unit = mysqli_real_escape_string($conn, $_POST['Unit']);
    $Goal = mysqli_real_escape_string($conn, $_POST['Goal']);
    $Score_criteria = mysqli_real_escape_string($conn, $_POST['Score_criteria']);
    $Weight = mysqli_real_escape_string($conn, $_POST['Weight']);
    $Important_level_no = mysqli_real_escape_string($conn, $_POST['Important_level_no']);
    $Description_text = mysqli_real_escape_string($conn, $_POST['Description_text']);
}

    $sql_insert = "INSERT into kpi_topic (KPI_type_id,Order_no,KPI_topic_name,Unit,Goal,Score_criteria,Weight,Important_level_no,Description_text )
                    values ('$KPI_type_id','$Order_no','$KPI_topic_name','$Unit','$Goal','$Score_criteria','$Weight','$Important_level_no','$Description_text')";

     if (mysqli_query($conn, $sql_insert)) {
        echo "<script>
                alert('บันทึกข้อมูลเรียบร้อยแล้ว!');

              </script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
?>