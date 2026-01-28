<?php
$page_title = "แก้ไขประเภทตัวชี้วัด";
include 'templates/navbar.php'; 
include 'config/conn.php';
include 'config/academic_year_resolver.php';
if (isset($_GET['KPI_type_id'])) {
    $KPI_type_id = mysqli_real_escape_string($conn, $_GET['KPI_type_id']);

    $sql = "SELECT * FROM kpi_type WHERE KPI_type_id = '$KPI_type_id' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);


    $current_year = $current_academic_year; 
    $is_editable = ($row['Academic'] >= $current_year);

    $disabled_style = 'background-color: #cccccc !important; cursor: not-allowed; pointer-events: none; opacity: 0.7;';

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

        <form id="editKpiForm" action="config/checkedit_kpi_type.php" method="POST">
            <input type="hidden" name="KPI_type_id" value="<?php echo $row['KPI_type_id']; ?>">
            <div class="form-group">
                <label>กลุ่มผู้ใช้ :<span class="required-mark">  *</span></label>
                <select name="Group_ID" required>
                    <?php
                    $sql_group = "SELECT Group_ID, Group_Name FROM group_use_kpis ORDER BY Group_Name ASC";
                    $res_group = mysqli_query($conn, $sql_group);
                    while ($group = mysqli_fetch_assoc($res_group)) {
                        $selected = ($group['Group_ID'] == $row['Group_ID']) ? "selected" : "";
                        echo "<option value='{$group['Group_ID']}' $selected>{$group['Group_Name']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>ปีการศึกษา :<span class="required-mark">  *</span></label>
                <input type="number" name="Academic" value="<?php echo $row['Academic']; ?>" required>
            </div>

            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด (ENG) :<span class="required-mark">  *</span></label>
                <input type="text" name="KPI_Type_Name_EN" value="<?php echo $row['KPI_Type_Name_EN']; ?>" required>
            </div>
            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด (TH) :<span class="required-mark">  *</span></label>
                <input type="text" name="KPI_Type_Name_TH" value="<?php echo $row['KPI_Type_Name_TH']; ?>" required>
            </div>
            <div class="form-group">
                <label>ค่าน้ำหนัก :<span class="required-mark">  *</span></label>
                <input type="number" name="Weight" value="<?php echo $row['Weight']; ?>" required>
            </div>
            <div class="form-group">
                <label>ลำดับที่ :<span class="required-mark">  *</span></label>
                <input type="number" name="Order_No" value="<?php echo $row['Order_No']; ?>" required>
            </div>
            <div class="form-group">
            <label>หมายเหตุ :</label>
            <textarea name="Description_text" rows="5"><?php echo $row['Description_text']; ?></textarea>
            </div>
            
            

            <div class="button-container">
                <button type="submit" class="save-btn" 
                    <?php echo $is_editable ? '' : 'disabled'; ?>
                    style="<?php echo $is_editable ? '' : $disabled_style; ?>">
                    บันทึก
                </button>

                <a href="config/checkdelete_kpi_type.php?KPI_type_id=<?php echo $row['KPI_type_id']; ?>"
                   class="delete-btn" 
                   onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลนี้?');"
                   <?php echo $is_editable ? '' : 'style="' . $disabled_style . '"'; ?>>
                   ลบ
                </a>
            </div>
        </form>
    </div>
</div>
<script src="js/edit_kpi_type.js"></script>
<?php
include 'templates/footer.php';
?>