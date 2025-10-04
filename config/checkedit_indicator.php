<?php
include 'conn.php'; // เรียกไฟล์เชื่อมต่อ DB

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $KPI_topic_id       = mysqli_real_escape_string($conn, $_POST['KPI_topic_id']);
    $KPI_type_id        = mysqli_real_escape_string($conn, $_POST['KPI_type_id']);
    $Order_no           = mysqli_real_escape_string($conn, $_POST['Order_no']);
    $KPI_topic_name     = mysqli_real_escape_string($conn, $_POST['KPI_topic_name']);
    $Unit               = mysqli_real_escape_string($conn, $_POST['Unit']);
    $Goal               = mysqli_real_escape_string($conn, $_POST['Goal']);
    $Score_criteria     = mysqli_real_escape_string($conn, $_POST['Score_criteria']);
    $Weight             = mysqli_real_escape_string($conn, $_POST['Weight']);
    $Important_level_no = mysqli_real_escape_string($conn, $_POST['Important_level_no']);
    $Description_text   = mysqli_real_escape_string($conn, $_POST['Description_text']);

    $sql = "UPDATE kpi_topic 
            SET KPI_type_id = '$KPI_type_id',
                Order_no = '$Order_no',
                KPI_topic_name = '$KPI_topic_name',
                Unit = '$Unit',
                Goal = '$Goal',
                Score_criteria = '$Score_criteria',
                Weight = '$Weight',
                Important_level_no = '$Important_level_no',
                Description_text = '$Description_text'
            WHERE KPI_topic_id = '$KPI_topic_id'";


    if (mysqli_query($conn, $sql)) {
        // อัปเดตสำเร็จ กลับไปที่หน้าแสดงข้อมูล
        echo "<script>
                alert('อัปเดตข้อมูลเรียบร้อยแล้ว');
                window.location.href='../indicators.php';
              </script>";
    } else {
        echo "เกิดข้อผิดพลาด: " . mysqli_error($conn);
    }
} else {
    echo "ไม่อนุญาตให้เข้าถึงหน้านี้โดยตรง";
}
?>