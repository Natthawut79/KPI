<?php
    $page_title = "สร้างตัวชี้วัด";
    include 'templates/navbar.php'; // เรียกใช้ Navbar
?>

<link rel="stylesheet" href="css/createinfokpi.css">

<div class="main-container">
    <div class="form-wrapper">
        <h1 class="form-title">สร้างข้อมูลตัวชี้วัด</h1>

        <form action="#" method="POST" onsubmit="return confirm('คุณต้องการบันทึกข้อมูลใช่หรือไม่?');">
            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด :</label>
                <select name="kpi_type_id" required>
                    <option value="">--- เลือกประเภทตัวชี้วัด ---</option>
                    <option value="1">ตัวชี้วัดผลงานในงานประจำ</option>
                    <option value="2">ตัวชี้วัดผลงานในการพัฒนา</option>
                    <option value="3">ตัวชี้วัดระดับองค์กร</option>
                </select>
            </div>
            <div class="form-group">
                <label>ลำดับที่ :</label>
                <input type="number" name="order_no">
            </div>
            <div class="form-group">
                <label>ชื่อตัวชี้วัด :</label>
                <input type="text" name="indicator_name" required>
            </div>
            <div class="form-group">
                <label>หน่วยวัด :</label>
                <input type="text" name="unit">
            </div>
            <div class="form-group">
                <label>เป้าหมายความสำเร็จที่คาดหวังทั้งปี :</label>
                <input type="text" name="target">
            </div>
            <div class="form-group">
                <label>เกณฑ์การให้คะแนนตามผลงานที่ทำได้ :</label>
                <input type="text" name="scoring_criteria">
            </div>
            <div class="form-group">
                <label>น้ำหนัก :</label>
                <input type="number" name="weight" required>
            </div>
             <div class="form-group">
                <label>ระดับความสำคัญ :</label>
                <input type="text" name="priority_level">
            </div>
            <div class="form-group">
                <label>หมายเหตุ :</label>
                <input type="text" name="note">
            </div>

            <div class="button-container">
                <button type="submit" class="create-btn">สร้าง</button>
                <a href="kpitype.php" class="cancel-btn">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>

<?php
    include 'templates/footer.php'; // เรียกใช้ Footer
?>