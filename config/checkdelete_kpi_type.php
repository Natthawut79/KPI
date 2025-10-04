<?php
include 'conn.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่ามีการส่ง KPI_type_id มาหรือไม่
if (isset($_GET['KPI_type_id'])) {
    $KPI_type_id = mysqli_real_escape_string($conn, $_GET['KPI_type_id']);

    // สร้างคำสั่งลบ
    $sql = "DELETE FROM kpi_type WHERE KPI_type_id = '$KPI_type_id'";

    if (mysqli_query($conn, $sql)) {
        // ลบเสร็จ -> กลับไปหน้ารายการประเภทตัวชี้วัด
        echo "<script>alert('ลบประเภทตัวชี้วัดสำเร็จ!'); window.location='../kpi_type.php';</script>";
    } else {
        echo "เกิดข้อผิดพลาดในการลบข้อมูล: " . mysqli_error($conn);
    }
} else {
    echo "ไม่พบรหัส KPI_type_id";
}

mysqli_close($conn);
?>
