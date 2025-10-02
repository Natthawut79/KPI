<?php
// กำหนดชื่อเพจสำหรับ navbar
$page_title = "ตัวชี้วัด";
// เรียกใช้ Navbar (ส่วนหัวของเว็บ)
include 'templates/navbar.php';
?>

<link rel="stylesheet" href="css/allkpi.css">

<div class="main-container">
    <div class="content-wrapper">
        <h1 class="page-title">ตัวชี้วัดทั้งหมด</h1>

        <div class="indicator-container">
            <div class="indicator-card">
                <form>
                    <h2>ข้อมูลตัวชี้วัด</h2>

                    <div class="form-group">
                        <label>ชื่อประเภทตัวชี้วัด :</label>
                        <input type="text" value="Corporate KPIs">
                    </div>
                    <div class="form-group">
                        <label>ลำดับที่ :</label>
                        <input type="text" value="1.1">
                    </div>
                    <div class="form-group">
                        <label>ชื่อตัวชี้วัด :</label>
                        <input type="text" value="การมีส่วนร่วมในการส่งนักศึกษาเข้าประกวดแข่งขันด้านวิชาการ">
                    </div>
                    <div class="form-group">
                        <label>หน่วยวัด :</label>
                        <input type="text" value="รางวัล">
                    </div>
                    <div class="form-group">
                        <label>เป้าหมายความสำเร็จที่คาดหวังทั้งปี :</label>
                        <input type="text" value="1 รางวัล">
                    </div>
                    <div class="form-group">
                        <label>เกณฑ์การให้คะแนนตามผลงานที่ทำได้ :</label>
                        <input type="text" value="1 = เป้าหมายแต่ไม่ได้รางวัล 5 = >= 1 รางวัล">
                    </div>
                    <div class="form-group">
                        <label>น้ำหนัก :</label>
                        <input type="number" value="3">
                    </div>
                    <div class="form-group">
                        <label>ระดับความสำคัญ :</label>
                        <input type="text" value="มาก">
                    </div>
                    <div class="form-group">
                        <label>หมายเหตุ :</label>
                        <input type="text" value="">
                    </div>

                    <div class="button-wrapper">
                        <button type="submit" class="edit-btn">แก้ไข</button>
                    </div>
                </form>
            </div>

            <div class="indicator-container">
            <div class="indicator-card">
                <form>
                    <h2>ข้อมูลตัวชี้วัด</h2>

                    <div class="form-group">
                        <label>ชื่อประเภทตัวชี้วัด :</label>
                        <input type="text" value="Corporate KPIs">
                    </div>
                    <div class="form-group">
                        <label>ลำดับที่ :</label>
                        <input type="text" value="1.1">
                    </div>
                    <div class="form-group">
                        <label>ชื่อตัวชี้วัด :</label>
                        <input type="text" value="การมีส่วนร่วมในการส่งนักศึกษาเข้าประกวดแข่งขันด้านวิชาการ">
                    </div>
                    <div class="form-group">
                        <label>หน่วยวัด :</label>
                        <input type="text" value="รางวัล">
                    </div>
                    <div class="form-group">
                        <label>เป้าหมายความสำเร็จที่คาดหวังทั้งปี :</label>
                        <input type="text" value="1 รางวัล">
                    </div>
                    <div class="form-group">
                        <label>เกณฑ์การให้คะแนนตามผลงานที่ทำได้ :</label>
                        <input type="text" value="1 = เป้าหมายแต่ไม่ได้รางวัล 5 = >= 1 รางวัล">
                    </div>
                    <div class="form-group">
                        <label>น้ำหนัก :</label>
                        <input type="number" value="3">
                    </div>
                    <div class="form-group">
                        <label>ระดับความสำคัญ :</label>
                        <input type="text" value="มาก">
                    </div>
                    <div class="form-group">
                        <label>หมายเหตุ :</label>
                        <input type="text" value="">
                    </div>

                    <div class="button-wrapper">
                        <button type="submit" class="edit-btn">แก้ไข</button>
                    </div>
                </form>
            </div>

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