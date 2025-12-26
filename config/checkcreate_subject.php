<?php
session_start();
include "conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $KPI_type_id = isset($_POST['KPI_type_id']) ? mysqli_real_escape_string($conn, $_POST['KPI_type_id']) : '';
    $subject_order = isset($_POST['subject_order']) ? mysqli_real_escape_string($conn, $_POST['subject_order']) : '';
    $subject_name = isset($_POST['subject_name']) ? mysqli_real_escape_string($conn, $_POST['subject_name']) : '';
    $subject_academic = isset($_POST['Academic']) ? mysqli_real_escape_string($conn, $_POST['Academic']) : '';
    $Group_ID = isset($_POST['Group_ID']) ? mysqli_real_escape_string($conn, $_POST['Group_ID']) : ''; 

    // ตรวจสอบค่าว่างก่อนบันทึก
    if (empty($KPI_type_id) || empty($subject_name)) {
        echo "<script>alert('กรุณากรอกข้อมูลให้ครบถ้วน (ประเภทตัวชี้วัด หรือ ชื่อหัวข้อ หายไป)'); window.history.back();</script>";
        exit();
    }

    // เพิ่ม Group_ID เข้าไปในคำสั่ง INSERT
    $sql = "INSERT INTO subject_topic (subject_name, subject_order, KPI_type_id, Academic, Group_ID) 
            VALUES ('$subject_name', '$subject_order', '$KPI_type_id','$subject_academic', '$Group_ID')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('เพิ่มข้อมูลสำเร็จ'); window.location.href = '../subject_topic.php';</script>";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}
?>