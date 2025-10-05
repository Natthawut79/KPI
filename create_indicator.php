<?php
$page_title = "สร้างตัวชี้วัด";
include 'templates/navbar.php'; 
include 'config/conn.php'; // <<< เพิ่มตรงนี้

// ✅ ดักค่าจาก GET
$KPI_topic_id = isset($_GET['KPI_topic_id']) ? $_GET['KPI_topic_id'] : '';

$sql = "SELECT kt.*, 
               t.KPI_type_id, 
               t.KPI_Type_Name_EN,
               i.Important_level_no,
               i.Important_level_name
        FROM kpi_topic kt
        LEFT JOIN kpi_type t ON kt.KPI_type_id = t.KPI_type_id
        LEFT JOIN important_level i ON kt.Important_level_no = i.Important_level_no
        WHERE kt.KPI_topic_id = '$KPI_topic_id'";

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

?>
<link rel="stylesheet" href="css/create_indicator.css">
<div class="main-container">
    <div class="form-wrapper">
        <h1 class="form-title">สร้างข้อมูลตัวชี้วัด</h1>
        
        <form action="config/checkcreate_indicator.php" method="POST" onsubmit="return confirm('คุณต้องการบันทึกข้อมูลใช่หรือไม่?');">
            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด :</label>
                <select name="KPI_type_id" required>
                    <?php
                    $sql_kpi_topic = "SELECT KPI_type_id,KPI_Type_Name_EN FROM kpi_type";
                    $res_kpi_topic = mysqli_query($conn, $sql_kpi_topic);
                    while ($kpi_topic = mysqli_fetch_assoc($res_kpi_topic)){
                        $selected = ($kpi_topic['KPI_type_id'] == $row['KPI_type_id']) ? "selected" : "";
                        echo "<option value ='{$kpi_topic['KPI_type_id']}'$selected>{$kpi_topic['KPI_Type_Name_EN']}</option>";
                    }
                    ?>
                </select>
            </div>
                
            <div class="form-group">
                <label>ลำดับที่ :</label>
                <input type="number" name="Order_no" required>
            </div>
            <div class="form-group">
                <label>ชื่อตัวชี้วัด :</label> 
                <input type="text" name="KPI_topic_name" required>
            </div>
            <div class="form-group">
                <label>หน่วยวัด :</label>
                <input type="text" name="Unit">
            </div>
            <div class="form-group">
                <label>เป้าหมายความสำเร็จที่คาดหวังทั้งปี :</label>
                <input type="text" name="Goal" required>
            </div>
            <div class="form-group">
                <label>เกณฑ์การให้คะแนนตามผลงานที่ทำได้ :</label>
                <input type="text" name="Score_criteria" required>
            </div>
            <div class="form-group">
                <label>น้ำหนัก :</label>
                <input type="number" name="Weight" required>
            </div>

             <div class="form-group">
                <label>ระดับความสำคัญ :</label>
                <select name="Important_level_no" required>
            <?php
                $sql_kpi_important_level_no = "SELECT Important_level_no, Important_level_name FROM important_level";
                $res_kpi_important_level_no = mysqli_query($conn, $sql_kpi_important_level_no);
                    while ($kpi_important_level_no = mysqli_fetch_assoc($res_kpi_important_level_no)) {
                        $selected = ($row['Important_level_no']) == $row['Important_level_no'] ? "selected" : "";
                        echo "<option value ='{$kpi_important_level_no['Important_level_no']}' $selected>{$kpi_important_level_no['Important_level_name']}</option>";
                    }
            ?>
            </select>

            </div>
            <div class="form-group">
                <label>หมายเหตุ :</label>
                <input type="text" name="Description_text" required>
            </div>

            <div class="button-container">
                <button type="submit" class="create-btn">สร้าง</button>
                <a href="kpi_type.php" class="cancel-btn">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>

<?php
    include 'templates/footer.php'; // เรียกใช้ Footer
?>
Collapse
message.txt
5 KB
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