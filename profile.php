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

// === [ 1. ตรวจสอบว่าผู้ใช้ที่ล็อกอินอยู่เป็น Admin (Type_id = 1) หรือไม่ ] ===
$is_admin = (isset($_SESSION['Type_id']) && $_SESSION['Type_id'] == 1);


$sql = "SELECT e.*,
               t.Title_id,
               t.Title_name,
               d.Department_id,
               d.Department_name,
               u.Type_id  
        FROM employee e
        LEFT JOIN title t ON e.Title_id = t.Title_id
        LEFT JOIN department d ON e.Department_id = d.Department_id
        LEFT JOIN user u ON e.Emp_code = u.Emp_code
        WHERE e.Emp_code = '$Emp_code'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);


$viewed_user_type_id = $row['Type_id'] ?? null;

$page_title = "แก้ไขข้อมูลผู้ใช้";
include 'templates/navbar.php';
?>
<link rel="stylesheet" href="css/profile.css">

<div class="main-container">
    <div class="profile-card">
        <form class="profile-form" action="config/checkprofile.php" method="post" enctype="multipart/form-data">
            <div class="profile-picture">
                <img src="data:image/jpeg;base64,<?php echo base64_encode($row['IMGname']); ?>" alt="Profile Picture"
                    id="profileImage" style="cursor: pointer;">
                <input type="file" name="IMGname" id="imageUpload" accept="image/*" hidden>
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
                <input type="text" name="Fname_th" value="<?php echo htmlspecialchars($row['Fname_th']); ?>">
            </div>
            <div class="form-row">
                <label for="last-name-th">นามสกุล(ไทย)</label>
                <input type="text" name="Lname_th" value="<?php echo htmlspecialchars($row['Lname_th']); ?>">
            </div>
            <div class="form-row">
                <label for="first-name-en">ชื่อ(eng)</label>
                <input type="text" name="Fname_eng" value="<?php echo htmlspecialchars($row['Fname_eng']); ?>">
            </div>
            <div class="form-row">
                <label for="last-name-en">นามสกุล(eng)</label>
                <input type="text" name="Lname_eng" value="<?php echo htmlspecialchars($row['Lname_eng']); ?>">
            </div>
            <div class="form-row">
                <label for="employee-id">รหัสพนักงาน</label>
                <input type="text" value="<?php echo htmlspecialchars($row['Emp_code']); ?>" disabled>
                <input type="hidden" name="Emp_code" value="<?php echo htmlspecialchars($row['Emp_code']); ?>">
            </div>

            <div class="form-row">
                <label for="Password">รหัสผ่านใหม่</label>
                <input type="password" name="Password" id="Password" placeholder="กรอก (ถ้าต้องการเปลี่ยน)">
            </div>

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

            <div class="form-row">
                <label for="Type_id">ประเภทผู้ใช้</label>

                <?php
                // กำหนด attribute 'disabled' ถ้าผู้ใช้ที่ล็อกอินไม่ใช่ Admin
                $disabled_attr = $is_admin ? '' : 'disabled';
                ?>

                <select name="Type_id" id="Type_id" required <?php echo $disabled_attr; ?>>
                    <?php
                    // แก้ไข SQL เพื่อดึง Type_name_th มาด้วย
                    $sql_type = "SELECT Type_id, Type_name, Type_name_th FROM user_type";
                    $res_type = mysqli_query($conn, $sql_type);
                    while ($type = mysqli_fetch_assoc($res_type)) {
                        // กำหนดการแสดงผลเป็น: ชื่อภาษาไทย (ชื่อภาษาอังกฤษ)
                        $display_name = htmlspecialchars($type['Type_name_th']) . " (" . htmlspecialchars($type['Type_name']) . ")";

                        // ตรวจสอบว่า Type_id ของ option ตรงกับ Type_id ของ user ที่กำลังดูหรือไม่
                        $selected = ($type['Type_id'] == $viewed_user_type_id) ? "selected" : "";

                        echo "<option value='{$type['Type_id']}' $selected>{$display_name}</option>";
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

<?php include 'templates/footer.php'; ?>