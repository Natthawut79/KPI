<?php
// กำหนดชื่อเพจสำหรับ navbar
$page_title = "ประเภทตัวชี้วัด";
// เรียกใช้ Navbar (ส่วนหัวของเว็บ)
include 'templates/navbar.php';
?>

<link rel="stylesheet" href="css/kpitype.css">

<div class="main-container">
    <div>
        <h1 class="page-title">ประเภทตัวชี้วัดทั้งหมด</h1>

    </div>

    <div class="indicator-container">

        <div class="indicator-card">
            <form>
                <h2>ข้อมูลประเภทตัวชี้วัด</h2>

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
                    <input type="text" value="ตัวอย่าง: เป็นการวัดผลตามหน้าที่และการประเมินผลปีต่อปี">
                </div>
                <div class="form-group">
                    <label>ปีการศึกษา :</label>
                    <input type="text" value="2568">
                </div>
                <div class="form-group">
                    <label>รหัสกลุ่มผู้ใช้ :</label>
                    <input type="text" value="G1_2568">
                </div>

                <div class="button-wrapper">
                    <button type="submit" class="edit-btn">แก้ไข</button>
                </div>
            </form>
        </div>

        <div class="indicator-card">
            <form>
                <h2>ข้อมูลประเภทตัวชี้วัด</h2>

                <div class="form-group">
                    <label>ชื่อประเภทตัวชี้วัด (ENG) :</label>
                    <input type="text" value="Improvement KPIs">
                </div>
                <div class="form-group">
                    <label>ชื่อประเภทตัวชี้วัด (TH) :</label>
                    <input type="text" value="ตัวชี้วัดผลงานในการพัฒนา">
                </div>
                <div class="form-group">
                    <label>ค่าน้ำหนัก :</label>
                    <input type="number" value="20">
                </div>
                <div class="form-group">
                    <label>ลำดับที่ :</label>
                    <input type="number" value="1">
                </div>
                <div class="form-group">
                    <label>หมายเหตุ :</label>
                    <input type="text">
                </div>
                <div class="form-group">
                    <label>ปีการศึกษา :</label>
                    <input type="text" value="2568">
                </div>
                <div class="form-group">
                    <label>รหัสกลุ่มผู้ใช้ :</label>
                    <input type="text" value="G1_2568">
                </div>

                <div class="button-wrapper">
                    <button type="submit" class="edit-btn">แก้ไข</button>
                </div>
            </form>
        </div>

        <div class="indicator-card">
            <form>
                <h2>ข้อมูลประเภทตัวชี้วัด</h2>

                <div class="form-group">
                    <label>ชื่อประเภทตัวชี้วัด (ENG) :</label>
                    <input type="text" value="Corporate KPIs">
                </div>
                <div class="form-group">
                    <label>ชื่อประเภทตัวชี้วัด (TH) :</label>
                    <input type="text" value="ตัวชี้วัดระดับองค์กร">
                </div>
                <div class="form-group">
                    <label>ค่าน้ำหนัก :</label>
                    <input type="number" value="50">
                </div>
                <div class="form-group">
                    <label>ลำดับที่ :</label>
                    <input type="number" value="1">
                </div>
                <div class="form-group">
                    <label>หมายเหตุ :</label>
                    <input type="text">
                </div>
                <div class="form-group">
                    <label>ปีการศึกษา :</label>
                    <input type="text" value="2568">
                </div>
                <div class="form-group">
                    <label>รหัสกลุ่มผู้ใช้ :</label>
                    <input type="text" value="G2_2568">
                </div>

                <div class="button-wrapper">
                    <button type="submit" class="edit-btn">แก้ไข</button>
                </div>
            </form>
        </div>
    </div>

    <div class="add-btn-container">
        <a href="#" class="add-btn">+</a>
    </div>

</div>

<?php
// เรียกใช้ Footer (ส่วนท้ายของเว็บ)
include 'templates/footer.php';
?>