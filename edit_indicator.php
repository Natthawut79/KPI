<?php
    $page_title = "แก้ไขตัวชี้วัด";
    include 'templates/navbar.php'; // เรียกใช้ Navbar
    include 'config/conn.php'; // เชื่อมต่อ DB


    // รับค่า KPI_type_id จาก URL
if (isset($_GET['KPI_topic_id'])) {
    $KPI_topic_id = mysqli_real_escape_string($conn, $_GET['KPI_topic_id']);

    // ดึงข้อมูลจาก DB
    $sql = "SELECT * FROM kpi_topic WHERE KPI_topic_id = '$KPI_topic_id' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    if (!$row) {
        echo "<p>ไม่พบข้อมูลประเภทตัวชี้วัด</p>";
        exit();
    }
} else {
    echo "<p>ไม่พบรหัส KPI_type_id</p>";
    exit();
}
?>


<link rel="stylesheet" href="css/edit_indicator.css">

<div class="main-container">
    <div class="form-wrapper">
        <h1 class="form-title">แก้ไขข้อมูลตัวชี้วัด</h1>

        <form action="config/checkedit_indicator.php" method="POST">
    <input type="hidden" name="KPI_topic_id" value="<?php echo $row['KPI_topic_id']; ?>">
    
    <div class="form-group">
        <label>ชื่อประเภทตัวชี้วัด :</label>
        <select name="KPI_type_id" required>
            <?php
            $sql_kpi_type_id = "SELECT KPI_type_id,KPI_Type_Name_TH FROM kpi_type";
            $res_kpi_type_id = mysqli_query($conn, $sql_kpi_type_id);
            while ($group = mysqli_fetch_assoc($res_kpi_type_id)) {
                $selected = ($group['KPI_type_id'] == $row['KPI_type_id']) ? "selected" : "";
                echo "<option value='{$group['KPI_type_id']}' $selected>{$group['KPI_Type_Name_TH']}</option>";
            }
            ?>
        </select>
    </div>
            <div class="form-group">
                <label>ลำดับที่ :</label>
                <input type="text" name="Order_no" value="<?php echo $row['Order_no']; ?>" required>
            </div>
            <div class="form-group">
                <label>ชื่อตัวชี้วัด :</label>
                <input type="text" name="KPI_topic_name" value="<?php echo $row['KPI_topic_name']; ?>" required>
            </div>
            <div class="form-group">
                <label>หน่วยวัด :</label>
                <input type="text" name="Unit" value="<?php echo $row['Unit']; ?>">
            </div>
            <div class="form-group">
                <label>เป้าหมายความสำเร็จที่คาดหวังทั้งปี :</label>
                <input type="text" name="Goal" value="<?php echo $row['Goal']; ?>">
            </div>
            <div class="form-group">
                <label>เกณฑ์การให้คะแนนตามผลงานที่ทำได้ :</label>
                <input type="text" name="Score_criteria" value="<?php echo $row['Score_criteria']; ?>">
            </div>
            <div class="form-group">
                <label>น้ำหนัก :</label>
                <input type="number" name="Weight" value="<?php echo $row['Weight']; ?>">
            </div>


             <div class="form-group">
                <label>ระดับความสำคัญ :</label>
                <select name="Important_level_no" required>
                    <?php
                    $sql_important_level_no = "SELECT Important_level_no, Important_level_name FROM important_level";
                    $res_important_level_no = mysqli_query($conn, $sql_important_level_no);
                    while ($important_level_no = mysqli_fetch_assoc($res_important_level_no)) {
                        $selected = ($important_level_no['Important_level_no'] == $row['Important_level_no']) ? "selected" : "";
                        echo "<option value='{$important_level_no['Important_level_no']}' $selected>{$important_level_no['Important_level_name']}</option>";
                    }
                    ?>
                </select>
            </div>


            <div class="form-group">
                <label>หมายเหตุ :</label>
                <input type="text" name="Description_text" value="<?php echo $row['Description_text']; ?>">
            </div>

            <div class="button-container">
                <button type="submit" class="save-btn">บันทึก</button>
                


                <a href="config/checkdelete_indicator.php?KPI_topic_id=<?php echo $row['KPI_topic_id']; ?>"
                   class="delete-btn" 
                   onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลนี้?');">
                   ลบ
                </a>
            </div>
        </form>
    </div>
</div>

<?php
    include 'templates/footer.php'; // เรียกใช้ Footer
?>