<?php
session_start();
// ตรวจสอบว่าไฟล์ conn.php อยู่ในโฟลเดอร์เดียวกัน (config)
include "conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- ค่าที่มีอยู่เดิม ---
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

    // เพิ่มเติม ค่าที่ส่งมาจากฟอร์ม (criteria_1 ถึง criteria_5)
    $criteria_1 = isset($_POST['criteria_1']) ? mysqli_real_escape_string($conn, $_POST['criteria_1']) : '';
    $criteria_2 = isset($_POST['criteria_2']) ? mysqli_real_escape_string($conn, $_POST['criteria_2']) : '';
    $criteria_3 = isset($_POST['criteria_3']) ? mysqli_real_escape_string($conn, $_POST['criteria_3']) : '';
    $criteria_4 = isset($_POST['criteria_4']) ? mysqli_real_escape_string($conn, $_POST['criteria_4']) : '';
    $criteria_5 = isset($_POST['criteria_5']) ? mysqli_real_escape_string($conn, $_POST['criteria_5']) : '';



    $Table_id_sql_val = "NULL";

    if ($Retrieve === 'yes' && isset($_POST['Table_id']) && is_numeric($_POST['Table_id'])) {
        // แปลงเป็นตัวเลข (Integer)
        $Table_id_sql_val = intval($_POST['Table_id']);
    }

    // 4. ค่าจาก "ประเภทการตีพิมพ์" (name="Publication_type_id")
    // ตั้งค่าเริ่มต้นเป็น NULL (สำหรับ SQL)
    $Publication_type_id_sql_val = "NULL";

    // ตรวจสอบเงื่อนไขทั้งหมด
    if (
        $Retrieve === 'yes' && isset($_POST['Table_id']) && $_POST['Table_id'] == '3' &&
        isset($_POST['Publication_type_id']) && is_numeric($_POST['Publication_type_id'])
    ) {

        // แปลงเป็นตัวเลข (Integer)
        $Publication_type_id_sql_val = intval($_POST['Publication_type_id']);
    }

    // แก้ไข SQL INSERT
    // (คอลัมน์ที่เป็นข้อความยังคงมี '...' แต่คอลัมน์ตัวเลข $Table_id_sql_val และ $Publication_type_id_sql_val ไม่มี)
    // ต้องแก้ตรงนี้เพิ่มด้วยครับ!
    $sql_insert = "INSERT into kpi_topic (
                    KPI_type_id, Order_no, KPI_topic_name, Unit, Goal, Score_criteria, Weight, 
                    Important_level_no, Description_text, Additional, Retrieve, 
                    Table_id, Publication_type_id,
                    criteria_1, criteria_2, criteria_3, criteria_4, criteria_5  -- เพิ่มชื่อคอลัมน์ตรงนี้
                )
                values (
                    '$KPI_type_id', '$Order_no', '$KPI_topic_name', '$Unit', '$Goal', 
                    '$Score_criteria', '$Weight', '$Important_level_no', '$Description_text', 
                    '$Additional', '$Retrieve', 
                    $Table_id_sql_val, $Publication_type_id_sql_val,
                    '$criteria_1', '$criteria_2', '$criteria_3', '$criteria_4', '$criteria_5' -- เพิ่มตัวแปรตรงนี้
                )";

    if (mysqli_query($conn, $sql_insert)) {
        echo "<script>
                alert('สร้างตัวชี้วัดสำเร็จ!');
                window.location.href = '../indicators.php';
              </script>";
    } else {
        // หากเกิดข้อผิดพลาด ให้แสดง Error เพื่อตรวจสอบ
        echo "Error: " . $sql_insert . "<br>" . mysqli_error($conn);
    }

} else {
    echo "Invalid request method.";
}

mysqli_close($conn);
?>