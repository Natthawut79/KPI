<?php
    $page_title = "แก้ไขตัวชี้วัด";
    include 'templates/navbar.php'; // เรียกใช้ Navbar
?>

<link rel="stylesheet" href="css/editinfokpi.css">

<div class="main-container">
    <div class="form-wrapper">
        <h1 class="form-title">แก้ไขข้อมูลตัวชี้วัด</h1>

        <form action="#" method="POST">
            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด :</label>
                <select name="kpi_type_id" required>
                    <option value="">--- เลือกประเภทตัวชี้วัด ---</option>
                    <option value="1">ตัวชี้วัดผลงานในงานประจำ</option>
                    <option value="2">ตัวชี้วัดผลงานในการพัฒนา</option>
                    <option value="3" selected>Corporate KPIs</option>
                </select>
            </div>
            <div class="form-group">
                <label>ลำดับที่ :</label>
                <input type="text" name="order_no" value="1.1">
            </div>
            <div class="form-group">
                <label>ชื่อตัวชี้วัด :</label>
                <input type="text" name="indicator_name" value="การมีส่วนร่วมในการส่งนักศึกษาเข้าประกวดแข่งขันด้านวิชาการ" required>
            </div>
            <div class="form-group">
                <label>หน่วยวัด :</label>
                <input type="text" name="unit" value="รางวัล">
            </div>
            <div class="form-group">
                <label>เป้าหมายความสำเร็จที่คาดหวังทั้งปี :</label>
                <input type="text" name="target" value="1 รางวัล">
            </div>
            <div class="form-group">
                <label>เกณฑ์การให้คะแนนตามผลงานที่ทำได้ :</label>
                <input type="text" name="scoring_criteria" value="1 = เข้าร่วมแต่ไม่ได้รับรางวัล 5 = >= 1 รางวัล">
            </div>
            <div class="form-group">
                <label>น้ำหนัก :</label>
                <input type="number" name="weight" value="3" required>
            </div>
             <div class="form-group">
                <label>ระดับความสำคัญ :</label>
                <input type="text" name="priority_level" value="มาก">
            </div>
            <div class="form-group">
                <label>หมายเหตุ :</label>
                <input type="text" name="note">
            </div>

            <div class="button-container">
                <button type="submit" class="save-btn">บันทึก</button>
                <a href="#" class="delete-btn" onclick="return confirm('คุณต้องการลบข้อมูลนี้ใช่หรือไม่?');">ลบ</a>
            </div>
        </form>
    </div>
</div>

<?php
    include 'templates/footer.php'; // เรียกใช้ Footer
?>