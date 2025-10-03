<?php
include 'conn.php'; // เรียกไฟล์เชื่อมต่อ DB

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับค่าที่ส่งมาจากฟอร์ม
    $KPI_type_id       = mysqli_real_escape_string($conn, $_POST['KPI_type_id']);
    $KPI_Type_Name_EN  = mysqli_real_escape_string($conn, $_POST['KPI_Type_Name_EN']);
    $KPI_Type_Name_TH  = mysqli_real_escape_string($conn, $_POST['KPI_Type_Name_TH']);
    $Weight            = mysqli_real_escape_string($conn, $_POST['Weight']);
    $Order_No          = mysqli_real_escape_string($conn, $_POST['Order_No']);
    $Description_text  = mysqli_real_escape_string($conn, $_POST['Description_text']);
    $Academic          = mysqli_real_escape_string($conn, $_POST['Academic']);
    $Group_ID          = mysqli_real_escape_string($conn, $_POST['Group_ID']);

    // เขียนคำสั่ง SQL สำหรับอัปเดตข้อมูล
    $sql = "UPDATE kpi_type 
            SET KPI_Type_Name_EN = '$KPI_Type_Name_EN',
                KPI_Type_Name_TH = '$KPI_Type_Name_TH',
                Weight = '$Weight',
                Order_No = '$Order_No',
                Description_text = '$Description_text',
                Academic = '$Academic',
                Group_ID = '$Group_ID'
            WHERE KPI_type_id = '$KPI_type_id'";

    if (mysqli_query($conn, $sql)) {
        // อัปเดตสำเร็จ กลับไปที่หน้าแสดงข้อมูล
        echo "<script>
                alert('อัปเดตข้อมูลเรียบร้อยแล้ว');
                window.location.href='../kpi_type.php';
              </script>";
    } else {
        echo "เกิดข้อผิดพลาด: " . mysqli_error($conn);
    }
} else {
    echo "ไม่อนุญาตให้เข้าถึงหน้านี้โดยตรง";
}
?>
