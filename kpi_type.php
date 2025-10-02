<?php
$page_title = "ประเภทตัวชี้วัด";
include 'templates/navbar.php';
include 'config/conn.php';
include 'config/auth_admin.php';

$sql = "SELECT * FROM kpi_type"; 
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
?>
<link rel="stylesheet" href="css/kpi_type.css">

<div class="main-container">
    <div>
        <h1 class="page-title">ประเภทตัวชี้วัดทั้งหมด</h1>
    </div>
    <div class="indicator-container">
        <?php while($row = mysqli_fetch_assoc($result)) { ?>
        <div class="indicator-card">
            <form>
                <h2>ข้อมูลประเภทตัวชี้วัด</h2>

                <div class="form-group">
                    <label>ชื่อประเภทตัวชี้วัด (ENG) :</label>
                    <input type="text" value="<?php echo $row['KPI_Type_Name_EN']; ?>"readonly>
                </div>
                <div class="form-group">
                    <label>ชื่อประเภทตัวชี้วัด (TH) :</label>
                    <input type="text" value="<?php echo $row['KPI_Type_Name_TH']; ?>"readonly>
                </div>
                <div class="form-group">
                    <label>ค่าน้ำหนัก :</label>
                    <input type="number" value="<?php echo $row['Weight']; ?>"readonly>
                </div>
                <div class="form-group">
                    <label>ลำดับที่ :</label>
                    <input type="number" value="<?php echo $row['Order_No']; ?>"readonly>
                </div>
                <div class="form-group">
                    <label>หมายเหตุ :</label>
                    <input type="text" value="<?php echo $row['Description_text']; ?>"readonly>
                </div>
                <div class="form-group">
                    <label>ปีการศึกษา :</label>
                    <input type="number" value="<?php echo $row['Academic']; ?>"readonly>
                </div>
                <div class="form-group">
                    <label>รหัสกลุ่มผู้ใช้ :</label>
                    <input type="text" value="<?php echo $row['Group_ID']; ?>"readonly>
                </div>

                <div class="button-wrapper">
                <a href="edit_kpi_type.php?KPI_type_id=<?php echo $row['KPI_type_id']; ?>" class="edit-btn">แก้ไข</a>
                </div>
            </form>
        </div>
        <?php } ?>
    </div>

    <div class="add-btn-container">
        <a href="create_kpi_type.php" class="add-btn">+</a>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
