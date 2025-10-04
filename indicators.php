<?php
// กำหนดชื่อเพจสำหรับ navbar
$page_title = "ตัวชี้วัด";
// เรียกใช้ Navbar (ส่วนหัวของเว็บ)
include 'templates/navbar.php';
include 'config/conn.php';
include 'config/auth_admin.php';
$sql = "SELECT kt.*, 
               kn.KPI_Type_Name_TH, 
               i.Important_level_no, 
               i.Important_level_name
        FROM kpi_topic kt
        LEFT JOIN kpi_type kn ON kt.KPI_type_id = kn.KPI_type_id
        LEFT JOIN important_level i ON kt.Important_level_no = i.Important_level_no";


$result = mysqli_query($conn, $sql);
// $row = mysqli_fetch_assoc($result);
if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
?>

<link rel="stylesheet" href="css/indicators.css">

<div class="main-container">
    <div class="content-wrapper">
        <h1 class="page-title">ตัวชี้วัดทั้งหมด</h1>

        <div class="indicator-container">
            <?php while($row = mysqli_fetch_assoc($result)) { ?>
            <div class="indicator-card">
                <form>
                    <h2>ข้อมูลตัวชี้วัด</h2>

                    <div class="form-group">
                        <label>ชื่อประเภทตัวชี้วัด :</label>
                        <input type="text" value="<?php echo $row['KPI_Type_Name_TH']; ?>" readonly>

                    </div>
                    <div class="form-group">
                        <label>ลำดับที่ :</label>
                        <input type="text" value="<?php echo $row['Order_no']; ?>"readonly>
                    </div>
                    <div class="form-group">
                        <label>ชื่อตัวชี้วัด :</label>
                        <input type="text" value="<?php echo $row['KPI_topic_name']; ?>"readonly>
                    </div>
                    <div class="form-group">
                        <label>หน่วยวัด :</label>
                        <input type="text" value="<?php echo $row['Unit']; ?>"readonly>
                    </div>
                    <div class="form-group">
                        <label>เป้าหมายความสำเร็จที่คาดหวังทั้งปี :</label>
                        <input type="text" value="<?php echo $row['Goal']; ?>"readonly>
                    </div>
                    <div class="form-group">
                        <label>เกณฑ์การให้คะแนนตามผลงานที่ทำได้ :</label>
                        <input type="text" value="<?php echo $row['Score_criteria']; ?>"readonly>
                    </div>
                    <div class="form-group">
                        <label>น้ำหนัก :</label>
                        <input type="number" value="<?php echo $row['Weight']; ?>"readonly>
                    </div>
                    <div class="form-group">
                        <label>ระดับความสำคัญ :</label>
                        <input type="text" value="<?php echo $row['Important_level_name']; ?>"readonly>
                    </div>
                    <div class="form-group">
                        <label>หมายเหตุ :</label>
                        <input type="text" value="<?php echo $row['Description_text']; ?>"readonly>
                    </div>

                    <div class="button-wrapper">
                        <a href="edit_indicator.php?KPI_topic_id=<?php echo $row['KPI_topic_id']; ?>" class="edit-btn">แก้ไข</a>
                    </div>
                </form>
            </div>
            <?php } ?>
        </div>
    </div>

    <div class="add-btn-container">
        <a href="create_indicator.php" class="add-btn">+</a>
    </div>
</div>

<?php
// เรียกใช้ Footer (ส่วนท้ายของเว็บ)
include 'templates/footer.php';
?>