<?php
include 'conn.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่ามีการส่ง KPI_topic_id มาหรือไม่
if (isset($_GET['KPI_topic_id'])) {
    $KPI_topic_id = mysqli_real_escape_string($conn, $_GET['KPI_topic_id']);

    // สร้างคำสั่งลบ
    $sql = "DELETE FROM kpi_topic WHERE KPI_topic_id = '$KPI_topic_id'";

    if (mysqli_query($conn, $sql)) {
        // ลบเสร็จ -> กลับไปหน้ารายการประเภทตัวชี้วัด
        echo "<script>alert('ลบตัวชี้วัดสำเร็จ!'); window.location='../indicators.php';</script>";
    } else {
        echo "เกิดข้อผิดพลาดในการลบข้อมูล: " . mysqli_error($conn);
    }
} else {
    echo "ไม่พบรหัส KPI_topic_id";
}

mysqli_close($conn);
?>