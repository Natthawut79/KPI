<?php
$page_title = "สร้างประเภทตัวชี้วัด";
include 'templates/navbar.php';
include 'config/conn.php';

$sql_group = "SELECT Group_ID, Group_Name FROM group_use_kpis ORDER BY Group_Name ASC";
$res_group = mysqli_query($conn, $sql_group);
$options_html = "";
if ($res_group) {
    while ($group = mysqli_fetch_assoc($res_group)) {
        $options_html .= "<option value='{$group['Group_ID']}'>{$group['Group_Name']}</option>";
    }
}

$sql_all_kpi = "SELECT Group_ID, Academic, Order_No FROM kpi_type";
$res_all_kpi = mysqli_query($conn, $sql_all_kpi);
$all_kpi_data = [];
if ($res_all_kpi) {
    while ($row = mysqli_fetch_assoc($res_all_kpi)) {
        $all_kpi_data[] = $row;
    }
}
$json_all_kpi = json_encode($all_kpi_data);
?>

<link rel="stylesheet" href="css/create_kpi_type.css">

<div class="main-container">
    <div class="form-wrapper">
        <h1 class="form-title">เพิ่มประเภทตัวชี้วัด</h1>

        <form action="config/checkcreate_kpi_type.php " method="POST">

            <div class="form-group">
                <label>ปีการศึกษา :<span class="required-mark">  *</span></label>
                <input type="number" name="Academic" id="academic_input" value="" required>
            </div>

            <div class="form-group">
                <label>กลุ่มผู้ใช้ :<span class="required-mark">  *</span></label>
                <select name="Group_ID" id="group_select" required>
                    <option value="">--- โปรดเลือกกลุ่มผู้ใช้ ---</option>
                    <?php echo $options_html; ?>
                </select>
            </div>

            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด (ENG) :<span class="required-mark">  *</span></label>
                <input type="text" name="KPI_Type_Name_EN" required>
            </div>
            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด (TH) :<span class="required-mark">  *</span></label>
                <input type="text" name="KPI_Type_Name_TH" required>
            </div>
            <div class="form-group">
                <label>ค่าน้ำหนัก :<span class="required-mark">  *</span></label>
                <input type="number" name="Weight" required>
            </div>
            <div class="form-group">
                <label>ลำดับที่ :<span class="required-mark">  *</span></label>
                <input type="number" name="Order_No" id="order_no" required>
            </div>
            <div class="form-group">
                <label>หมายเหตุ :</label>
                <textarea name="Description_text" rows="5"></textarea>
            </div>

            <div class="button-container">
                <button type="submit" class="create-btn">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
    const ALL_KPI_DATA = <?php echo $json_all_kpi; ?>;
</script>

<script src="js/create_kpi_type.js"></script>

<?php
include 'templates/footer.php';
?>