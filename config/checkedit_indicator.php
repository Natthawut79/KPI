<?php
include 'conn.php'; // เรียกไฟล์เชื่อมต่อ DB

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- ค่าที่มีอยู่เดิม ---
    $KPI_topic_id = mysqli_real_escape_string($conn, $_POST['KPI_topic_id']);
    $KPI_type_id = mysqli_real_escape_string($conn, $_POST['KPI_type_id']);
    $Order_no = mysqli_real_escape_string($conn, $_POST['Order_no']);
    $KPI_topic_name = mysqli_real_escape_string($conn, $_POST['KPI_topic_name']);
    $Unit = mysqli_real_escape_string($conn, $_POST['Unit']);
    $Goal = mysqli_real_escape_string($conn, $_POST['Goal']);
    $Score_criteria = mysqli_real_escape_string($conn, $_POST['Score_criteria']);
    $Weight = mysqli_real_escape_string($conn, $_POST['Weight']);
    $Important_level_no = mysqli_real_escape_string($conn, $_POST['Important_level_no']);
    $Description_text = mysqli_real_escape_string($conn, $_POST['Description_text']);
    
    $Additional = mysqli_real_escape_string($conn, $_POST['fill_data']); // ค่าจาก "กรอกข้อมูลเพิ่มหรือไม่"
    $Retrieve = mysqli_real_escape_string($conn, $_POST['fetch_data']); // ค่าจาก "ดึงข้อมูลจากฐานข้อมูลหรือไม่"

    // --- [ vvvvvv ส่วนที่แก้ไข vvvvvv ] ---

    // 3. เตรียมค่า Table_id (สำหรับคอลัมน์ INT)
    $Table_id_sql_set; // เราจะสร้างส่วนของ SQL UPDATE
    if ($Retrieve === 'yes' && isset($_POST['Table_id']) && is_numeric($_POST['Table_id'])) {
        $Table_id_sql_set = "Table_id = " . intval($_POST['Table_id']);
    } else {
        // ถ้าไม่ดึงข้อมูล หรือไม่มีค่าส่งมา ให้ตั้งเป็น NULL
        $Table_id_sql_set = "Table_id = NULL"; 
    }

    // 4. เตรียมค่า Publication_type_id (สำหรับคอลัมน์ INT)
    $Publication_type_id_sql_set;
    if ($Retrieve === 'yes' && isset($_POST['Table_id']) && $_POST['Table_id'] == '3' && 
        isset($_POST['Publication_type_id']) && is_numeric($_POST['Publication_type_id'])) {
        
        $Publication_type_id_sql_set = "Publication_type_id = " . intval($_POST['Publication_type_id']);
    } else {
        // ถ้าเงื่อนไขไม่ตรง (ไม่ใช่ publication หรือ ไม่มีค่าส่งมา) ให้ตั้งเป็น NULL
        $Publication_type_id_sql_set = "Publication_type_id = NULL";
    }


    // === [ แก้ไข SQL UPDATE ] ===
    // เพิ่ม $Table_id_sql_set และ $Publication_type_id_sql_set
    $sql = "UPDATE kpi_topic 
            SET KPI_type_id = '$KPI_type_id',
                Order_no = '$Order_no',
                KPI_topic_name = '$KPI_topic_name',
                Unit = '$Unit',
                Goal = '$Goal',
                Score_criteria = '$Score_criteria',
                Weight = '$Weight',
                Important_level_no = '$Important_level_no',
                Description_text = '$Description_text',
                Additional = '$Additional', 
                Retrieve = '$Retrieve',
                $Table_id_sql_set,
                $Publication_type_id_sql_set
            WHERE KPI_topic_id = '$KPI_topic_id'";


    if (mysqli_query($conn, $sql)) {
        // อัปเดตสำเร็จ กลับไปที่หน้าแสดงข้อมูล
        echo "<script>
                alert('อัปเดตข้อมูลเรียบร้อยแล้ว');
                window.location.href='../indicators.php';
              </script>";
    } else {
        echo "เกิดข้อผิดพลาด: " . $sql . "<br>" . mysqli_error($conn);
    }
} else {
    echo "ไม่อนุญาตให้เข้าถึงหน้านี้โดยตรง";
}
?>