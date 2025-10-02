<?php
    $page_title = "แก้ไขประเภทตัวชี้วัด";
    include 'templates/navbar.php'; // เรียกใช้ Navbar
?>

<link rel="stylesheet" href="css/edit_kpi_type.css">

<div class="main-container">
    <div class="form-wrapper">
        <h1 class="form-title">ข้อมูลประเภทตัวชี้วัด</h1>

        <form>
            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด (ENG) :</label>
                <input type="text" value="Standing KPIs">
            </div>
            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด (TH) :</label>
                <input type="text" value="ตัวชี้วัดผลงานในงานประจำ">
            </div>
            <div class="form-group">
                <label>ค่าน้ำหนัก :</label>
                <input type="number" value="80">
            </div>
            <div class="form-group">
                <label>ลำดับที่ :</label>
                <input type="number" value="1">
            </div>
            <div class="form-group">
                <label>หมายเหตุ :</label>
                <input type="text" value="ตัวอย่างเช่น เป็นการวัดผลงานหลักที่ทำและการประเมินต้องมี Impact ต่อการทำงาน">
            </div>
            <div class="form-group">
                <label>ปีการศึกษา :</label>
                <input type="number" value="2568">
            </div>
            <div class="form-group">
                <label>รหัสกลุ่มผู้ใช้ :</label>
                <input type="text" value="G1_2568">
            </div>

            <div class="button-container">
                <button type="submit" class="save-btn">บันทึก</button>
                <a href="" class="delete-btn">ลบ</a>
            </div>
        </form>
    </div>
</div>

<?php
    include 'templates/footer.php'; // เรียกใช้ Footer
?>