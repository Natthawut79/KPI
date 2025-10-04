<?php
    $page_title = "สร้างบัญชีผู้ใช้";
    include 'templates/navbar.php'; 
    include 'config/conn.php';

?>

<link rel="stylesheet" href="css/create_user.css">

<div class="main-container">
    <div class="form-wrapper">
        <h1 class="form-title">สร้างบัญชีผู้ใช้</h1>
        
        <form action="config/check_create_user.php" method="POST" enctype="multipart/form-data" onsubmit="return confirm('คุณต้องการสร้างบัญชีผู้ใช้ใช่หรือไม่?');">
            
            <div class="profile-picture">
                <label for="profile-pic">
                    <img src="img/profile.png" alt="Profile Picture" id="profileImage">
                </label>
                <input type="file" name="IMGname" id="imageUpload" accept="image/*" hidden>
            </div>

            <div class="form-group">
                <label>รหัสพนักงาน:</label>
                <input type="text" name="Emp_code" required>
            </div>
            <div class="form-group">
                <label>รหัสผ่าน:</label>
                <input type="password" name="Password" required>
            </div>
            <div class="form-group">
                <label>คำนำหน้าชื่อ:</label>
                <select name="Title_id" required>
                    <?php
                    $sql_title = "SELECT Title_id, Title_name FROM title";
                    $res_title = mysqli_query($conn, $sql_title);
                    while ($t = mysqli_fetch_assoc($res_title)) {
                        echo "<option value='{$t['Title_id']}'>{$t['Title_name']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>ชื่อ (ไทย):</label> 
                <input type="text" name="Fname_th" required>
            </div>
            <div class="form-group">
                <label>นามสกุล (ไทย):</label>
                <input type="text" name="Lname_th" required>
            </div>
            <div class="form-group">
                <label>ชื่อ (Eng):</label>
                <input type="text" name="Fname_eng">
            </div>
            <div class="form-group">
                <label>นามสกุล (Eng):</label>
                <input type="text" name="Lname_eng">
            </div>
            <div class="form-group">
                <label>สาขา:</label>
                <select name="Department_id" required>
            <?php
                $sql_dep = "SELECT Department_id, Department_name FROM department";
                $res_dep = mysqli_query($conn, $sql_dep);
                    while ($dep = mysqli_fetch_assoc($res_dep)) {
                        echo "<option value='{$dep['Department_id']}'>{$dep['Department_name']}</option>";
                    }
            ?>
            </select>
            </div>
            <div class="form-group">
                <label>ประเภทผู้ใช้:</label>
                <select name="Type_id" required>
            <?php
                $sql_type = "SELECT Type_id, Type_name FROM user_type";
                $res_type = mysqli_query($conn, $sql_type);
                    while ($type = mysqli_fetch_assoc($res_type)) {
                        echo "<option value='{$type['Type_id']}'>{$type['Type_name']}</option>";
                    }
            ?>        
                </select>
            </div>
            <div class="button-container">
                <button type="submit" class="create-btn">สร้าง</button>
                <a href="mainadmin.php" class="cancel-btn">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>

<?php
    include 'templates/footer.php';
?>