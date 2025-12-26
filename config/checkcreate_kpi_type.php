<?php
include 'conn.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $KPI_Type_Name_EN = mysqli_real_escape_string($conn, $_POST['KPI_Type_Name_EN']);
    $KPI_Type_Name_TH = mysqli_real_escape_string($conn, $_POST['KPI_Type_Name_TH']);
    $Weight = mysqli_real_escape_string($conn, $_POST['Weight']);
    $Order_No = mysqli_real_escape_string($conn, $_POST['Order_No']);
    $Description_text = mysqli_real_escape_string($conn, $_POST['Description_text']);
    $Academic = mysqli_real_escape_string($conn, $_POST['Academic']);
    $Group_ID = mysqli_real_escape_string($conn, $_POST['Group_ID']);

    $sql = "INSERT INTO kpi_type 
                (KPI_Type_Name_EN, KPI_Type_Name_TH, Weight, Order_No, Description_text, Academic, Group_ID) 
            VALUES 
                ('$KPI_Type_Name_EN', '$KPI_Type_Name_TH', '$Weight', '$Order_No', '$Description_text', '$Academic', '$Group_ID')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('สร้างประเภทตัวชี้วัดสำเร็จ!'); window.location='../kpi_type.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    echo "Invalid Request!";
}
?>
