<?php
$page_title = "แก้ไขประเภทตัวชี้วัด";
include 'templates/navbar.php'; 
include 'config/conn.php'; // เชื่อมต่อ DB

// รับค่า KPI_type_id จาก URL
if (isset($_GET['KPI_type_id'])) {
    $KPI_type_id = mysqli_real_escape_string($conn, $_GET['KPI_type_id']);

    // ดึงข้อมูลจาก DB
    $sql = "SELECT * FROM kpi_type WHERE KPI_type_id = '$KPI_type_id' LIMIT 1";
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

<link rel="stylesheet" href="css/edit_kpi_type.css">

<div class="main-container">
    <div class="form-wrapper">
        <h1 class="form-title">แก้ไขข้อมูลประเภทตัวชี้วัด</h1>

        <form action="config/checkedit_kpi_type.php" method="POST">
            <!-- hidden ไว้ส่งไปตอน update -->
            <input type="hidden" name="KPI_type_id" value="<?php echo $row['KPI_type_id']; ?>">

            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด (ENG) :</label>
                <input type="text" name="KPI_Type_Name_EN" value="<?php echo $row['KPI_Type_Name_EN']; ?>" required>
            </div>
            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด (TH) :</label>
                <input type="text" name="KPI_Type_Name_TH" value="<?php echo $row['KPI_Type_Name_TH']; ?>" required>
            </div>
            <div class="form-group">
                <label>ค่าน้ำหนัก :</label>
                <input type="number" name="Weight" value="<?php echo $row['Weight']; ?>" required>
            </div>
            <div class="form-group">
                <label>ลำดับที่ :</label>
                <input type="number" name="Order_No" value="<?php echo $row['Order_No']; ?>" required>
            </div>
            <div class="form-group">
                <label>หมายเหตุ :</label>
                <input type="text" name="Description_text" value="<?php echo $row['Description_text']; ?>">
            </div>
            <div class="form-group">
                <label>ปีการศึกษา :</label>
                <input type="number" name="Academic" value="<?php echo $row['Academic']; ?>" required>
            </div>
            <div class="form-group">
                <label>กลุ่มผู้ใช้ :</label>
                <select name="Group_ID" required>
                    <?php
                    $sql_group = "SELECT Group_ID, Group_Name FROM group_use_kpis";
                    $res_group = mysqli_query($conn, $sql_group);
                    while ($group = mysqli_fetch_assoc($res_group)) {
                        $selected = ($group['Group_ID'] == $row['Group_ID']) ? "selected" : "";
                        echo "<option value='{$group['Group_ID']}' $selected>{$group['Group_Name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="button-container">
                <button type="submit" class="save-btn">บันทึก</button>
                <a href="config/delete_kpi_type.php?KPI_type_id=<?php echo $row['KPI_type_id']; ?>"></a>
                <a href="config/checkdelete_kpi_type.php?KPI_type_id=<?php echo $row['KPI_type_id']; ?>"
                   class="delete-btn" 
                   onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลนี้?');">
                   ลบ
                </a>
            </div>
        </form>
    </div>
</div>

<?php
include 'templates/footer.php';
?>
