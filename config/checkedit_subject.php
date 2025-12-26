<?php
session_start();
include "conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = mysqli_real_escape_string($conn, $_POST['subject_id']);
    $subject_name = mysqli_real_escape_string($conn, $_POST['subject_name']);
    $subject_order = mysqli_real_escape_string($conn, $_POST['subject_order']);
    $KPI_type_id = mysqli_real_escape_string($conn, $_POST['KPI_type_id']);
    $Academic = mysqli_real_escape_string($conn, $_POST['Academic']);
    $Group_ID = mysqli_real_escape_string($conn, $_POST['Group_ID']);

    $sql = "UPDATE subject_topic 
            SET subject_name = '$subject_name', 
                subject_order = '$subject_order', 
                KPI_type_id = '$KPI_type_id',
                Academic = '$Academic',
                Group_ID = '$Group_ID'
            WHERE subject_id = '$subject_id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>
                alert('แก้ไขข้อมูลสำเร็จ!');
                window.location.href = '../subject_topic.php';
              </script>";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}
mysqli_close($conn);
?>