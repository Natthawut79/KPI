<?php
    $page_title = "สร้างประเภทตัวชี้วัด";
    include 'templates/navbar.php'; // เรียกใช้ Navbar
?>

<link rel="stylesheet" href="css/ckpi.css">

<div class="main-container">
    <div class="form-wrapper">
        <h1 class="form-title">สร้างประเภทตัวชี้วัด</h1>

        <form action="config/add_kpi_type.php" method="POST">
            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด (ENG) :</label>
                <input type="text" name="kpi_type_name_eng" required>
            </div>
            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด (TH) :</label>
                <input type="text" name="kpi_type_name_th" required>
            </div>
            <div class="form-group">
                <label>ค่าน้ำหนัก :</label>
                <input type="number" name="weight" required>
            </div>
            <div class="form-group">
                <label>ลำดับที่ :</label>
                <input type="number" name="order_no" required>
            </div>
            <div class="form-group">
                <label>หมายเหตุ :</label>
                <input type="text" name="description">
            </div>
            <div class="form-group">
                <label>ปีการศึกษา :</label>
                <input type="number" name="academic_year" required>
            </div>
            <div class="form-group">
                <label>รหัสกลุ่มผู้ใช้ :</label>
                <input type="text" name="group_id" required>
            </div>

            <div class="button-container">
                <button type="submit" class="create-btn">สร้าง</button>
            </div>
        </form>
    </div>
</div>

<?php
    include 'templates/footer.php'; // เรียกใช้ Footer
?>