<?php
session_start();
include 'config/conn.php';

// ถ้ามี Emp_code ส่งมาผ่าน URL (GET) ให้ใช้ค่านั้นก่อน
if (isset($_GET['Emp_code'])) {
    $Emp_code = mysqli_real_escape_string($conn, $_GET['Emp_code']);
} elseif (isset($_SESSION['Emp_code'])) {
    // ถ้าไม่มี GET แต่มี SESSION ให้ใช้ SESSION
    $Emp_code = mysqli_real_escape_string($conn, $_SESSION['Emp_code']);
} else {
    echo "ไม่พบรหัสพนักงาน!";
    exit();
}

$sql = "SELECT e.*, 
               t.Title_id,
               t.Title_name, 
               d.Department_id,
               d.Department_name 
        FROM employee e
        LEFT JOIN title t ON e.Title_id = t.Title_id
        LEFT JOIN department d ON e.Department_id = d.Department_id
        WHERE e.Emp_code = '$Emp_code'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

$page_title = "แก้ไขข้อมูลผู้ใช้";
include 'templates/navbar.php';
?>
<link rel="stylesheet" href="css/profile.css">

<div class="main-container">
    <div class="profile-card">
        <form class="profile-form" action="config/checkprofile.php" method="post" enctype="multipart/form-data">
            <div class="profile-picture">
                <label for="profile-pic">
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($row['IMGname']); ?>"
                        alt="Profile Picture" id="profileImage">
                </label>
                <input type="file" name="IMGname" id="imageUpload" accept="image/*" hidden
                    onchange="previewImage(event)">
            </div>
            <div class="form-row">
                <label for="Title_id">คำนำหน้าชื่อ</label>
                <select name="Title_id" required>
                    <?php
                    $sql_title = "SELECT Title_id, Title_name FROM title";
                    $res_title = mysqli_query($conn, $sql_title);
                    while ($t = mysqli_fetch_assoc($res_title)) {
                        $selected = ($t['Title_id'] == $row['Title_id']) ? "selected" : "";
                        echo "<option value='{$t['Title_id']}' $selected>{$t['Title_name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-row">
                <label for="first-name-th">ชื่อ(ไทย)</label>
                <input type="text" name="Fname_th" value="<?php echo $row['Fname_th']; ?>">
            </div>
            <div class="form-row">
                <label for="last-name-th">นามสกุล(ไทย)</label>
                <input type="text" name="Lname_th" value="<?php echo $row['Lname_th']; ?>">
            </div>
            <div class="form-row">
                <label for="first-name-en">ชื่อ(eng)</label>
                <input type="text" name="Fname_eng" value="<?php echo $row['Fname_eng']; ?>">
            </div>
            <div class="form-row">
                <label for="last-name-en">นามสกุล(eng)</label>
                <input type="text" name="Lname_eng" value="<?php echo $row['Lname_eng']; ?>">
            </div>
            <div class="form-row">
                <label for="employee-id">รหัสพนักงาน</label>
                <input type="text" value="<?php echo $row['Emp_code']; ?>" disabled>
                <!-- ส่ง Emp_code ไปแบบ hidden เพื่อ update -->
                <input type="hidden" name="Emp_code" value="<?php echo $row['Emp_code']; ?>">
            </div>

            <!-- Department Select -->
            <div class="form-row">
                <label for="Department_id">สาขา</label>
                <select name="Department_id" required>
                    <?php
                    $sql_dep = "SELECT Department_id, Department_name FROM department";
                    $res_dep = mysqli_query($conn, $sql_dep);
                    while ($dep = mysqli_fetch_assoc($res_dep)) {
                        $selected = ($dep['Department_id'] == $row['Department_id']) ? "selected" : "";
                        echo "<option value='{$dep['Department_id']}' $selected>{$dep['Department_name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="button-container">
                <button type="submit" class="save-button">บันทึกการเปลี่ยนแปลง</button>
            </div>
        </form>
    </div>
</div>


<script src="js/script.js"></script>
<?php include 'templates/footer.php'; ?>