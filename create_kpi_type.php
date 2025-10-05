<?php
    $page_title = "สร้างประเภทตัวชี้วัด";
    include 'templates/navbar.php'; 
        include 'config/conn.php';

?>

<link rel="stylesheet" href="css/create_kpi_type.css">

<div class="main-container">
    <div class="form-wrapper">
        <h1 class="form-title">สร้างประเภทตัวชี้วัด</h1>

        <form action="config/checkcreate_kpi_type.php " method="POST">
            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด (ENG) :</label>
                <input type="text" name="KPI_Type_Name_EN" required>
            </div>
            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด (TH) :</label>
                <input type="text" name="KPI_Type_Name_TH" required>
            </div>
            <div class="form-group">
                <label>ค่าน้ำหนัก :</label>
                <input type="number" name="Weight" required>
            </div>
            <div class="form-group">
                <label>ลำดับที่ :</label>
                <input type="number" name="Order_No" required>
            </div>
            <div class="form-group">
                <label>หมายเหตุ :</label>
                <input type="text" name="Description_text">
            </div>
            <div class="form-group">
                <label>ปีการศึกษา :</label>
                <input type="number" name="Academic" required>
            </div>
            <div class="form-group">
                <label>กลุ่มผู้ใช้ :</label>
                <select name="Group_ID" required>
                    <?php
                    // ดึงข้อมูลกลุ่มผู้ใช้ทั้งหมดมาแสดง
                    $sql_group = "SELECT Group_ID, Group_Name FROM group_use_kpis";
                    $res_group = mysqli_query($conn, $sql_group);
                    while ($group = mysqli_fetch_assoc($res_group)) {
                        echo "<option value='{$group['Group_ID']}'>{$group['Group_Name']}</option>";
                    }
                    ?>
                </select>
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