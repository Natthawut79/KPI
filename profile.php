<?php
session_start();
include 'config/conn.php';

if (isset($_GET['Emp_code'])) {
    $Emp_code = mysqli_real_escape_string($conn, $_GET['Emp_code']);
} elseif (isset($_SESSION['Emp_code'])) {
    // ถ้าไม่มี GET แต่มี SESSION ให้ใช้ SESSION
    $Emp_code = mysqli_real_escape_string($conn, $_SESSION['Emp_code']);
} else {
    echo "ไม่พบรหัสพนักงาน!";
    exit();
}

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
        <form class="profile-form" action="config/checkprofile.php" method="post" enctype="multipart/form-data" onsubmit="return validateProfileForm()">
            <div class="profile-picture">
                <img src="data:image/jpeg;base64,<?php echo base64_encode($row['IMGname']); ?>" alt="Profile Picture"
                    id="profileImage" style="cursor: pointer;">
                <input type="file" name="IMGname" id="imageUpload" accept="image/*" hidden onchange="previewImage(event)">
            </div>
            
            <div class="form-row">
                <label for="Title_id">คำนำหน้าชื่อ <span style="color:red">*</span></label>
                <select name="Title_id" id="Title_id" required>
                    <option value="">--โปรดเลือกคำนำหน้าชื่อ--</option>
                    <?php
                    $sql_title = "SELECT Title_id, Title_name FROM title ORDER BY Title_name ASC";
                    $res_title = mysqli_query($conn, $sql_title);
                    while ($t = mysqli_fetch_assoc($res_title)) {
                        $selected = ($t['Title_id'] == $row['Title_id']) ? "selected" : "";
                        echo "<option value='{$t['Title_id']}' $selected>{$t['Title_name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-row">
                <label for="first-name-th">ชื่อ(ไทย) <span style="color:red">*</span></label>
                <input type="text" name="Fname_th" id="Fname_th" value="<?php echo htmlspecialchars($row['Fname_th']); ?>" required>
            </div>
            <div class="form-row">
                <label for="last-name-th">นามสกุล(ไทย) <span style="color:red">*</span></label>
                <input type="text" name="Lname_th" id="Lname_th" value="<?php echo htmlspecialchars($row['Lname_th']); ?>" required>
            </div>
            <div class="form-row">
                <label for="first-name-en">ชื่อ(eng) <span style="color:red">*</span></label>
                <input type="text" name="Fname_eng" id="Fname_eng" value="<?php echo htmlspecialchars($row['Fname_eng']); ?>" required>
            </div>
            <div class="form-row">
                <label for="last-name-en">นามสกุล(eng) <span style="color:red">*</span></label>
                <input type="text" name="Lname_eng" id="Lname_eng" value="<?php echo htmlspecialchars($row['Lname_eng']); ?>" required>
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
                <label for="Department_id">สาขา <span style="color:red">*</span></label>
                <select name="Department_id" id="Department_id" required>
                    <option value="">--โปรดเลือกสาขา--</option>
                    <?php
                    $sql_dep = "SELECT Department_id, Department_name FROM department ORDER BY Department_name ASC";
                    $res_dep = mysqli_query($conn, $sql_dep);
                    while ($dep = mysqli_fetch_assoc($res_dep)) {
                        $selected = ($dep['Department_id'] == $row['Department_id']) ? "selected" : "";
                        echo "<option value='{$dep['Department_id']}' $selected>{$dep['Department_name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-row">
                <label for="Type_id">ประเภทผู้ใช้ <span style="color:red">*</span></label>

                <?php
                $disabled_attr = $is_admin ? '' : 'disabled';
                ?>

                <select name="Type_id" id="Type_id" required <?php echo $disabled_attr; ?>>
                    <option value="">--โปรดเลือกประเภทผู้ใช้--</option>
                    <?php
                    $sql_type = "SELECT Type_id, Type_name, Type_name_th FROM user_type ORDER BY Type_name_th ASC";
                    $res_type = mysqli_query($conn, $sql_type);
                    while ($type = mysqli_fetch_assoc($res_type)) {
                        $display_name = htmlspecialchars($type['Type_name_th']) . " (" . htmlspecialchars($type['Type_name']) . ")";
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

<script>
    function previewImage(event) {
        var reader = new FileReader();
        reader.onload = function(){
            var output = document.getElementById('profileImage');
            output.src = reader.result;
        };
        if(event.target.files.length > 0) {
            reader.readAsDataURL(event.target.files[0]);
        }
    }

    function validateProfileForm() {
        // 1. ตรวจสอบคำนำหน้าชื่อ
        const titleSelect = document.getElementById('Title_id');
        if (titleSelect.value === "") {
            alert("กรุณาเลือกคำนำหน้าชื่อ");
            titleSelect.focus();
            return false;
        }

        // 2. ตรวจสอบชื่อ (ไทย)
        const fnameThInput = document.getElementById('Fname_th');
        const thaiRegex = /^[ก-๙]+$/; 
        if (!thaiRegex.test(fnameThInput.value)) {
            alert("ชื่อ (ไทย) ต้องเป็นภาษาไทยเท่านั้น");
            fnameThInput.focus();
            return false;
        }

        // 3. ตรวจสอบนามสกุล (ไทย)
        const lnameThInput = document.getElementById('Lname_th');
        if (!thaiRegex.test(lnameThInput.value)) {
            alert("นามสกุล (ไทย) ต้องเป็นภาษาไทยเท่านั้น");
            lnameThInput.focus();
            return false;
        }

        // 4. ตรวจสอบชื่อ (Eng)
        const fnameEngInput = document.getElementById('Fname_eng');
        const engRegex = /^[a-zA-Z]+$/;
        if (!engRegex.test(fnameEngInput.value)) {
            alert("ชื่อ (Eng) ต้องเป็นภาษาอังกฤษเท่านั้น");
            fnameEngInput.focus();
            return false;
        }

        // 5. ตรวจสอบนามสกุล (Eng)
        const lnameEngInput = document.getElementById('Lname_eng');
        if (!engRegex.test(lnameEngInput.value)) {
            alert("นามสกุล (Eng) ต้องเป็นภาษาอังกฤษเท่านั้น");
            lnameEngInput.focus();
            return false;
        }

        // 6. ตรวจสอบสาขา
        const deptSelect = document.getElementById('Department_id');
        if (deptSelect.value === "") {
            alert("กรุณาเลือกสาขา");
            deptSelect.focus();
            return false;
        }

        // 7. ตรวจสอบประเภทผู้ใช้ (เฉพาะกรณีที่ไม่ได้ถูก Disabled)
        const typeSelect = document.getElementById('Type_id');
        if (!typeSelect.disabled && typeSelect.value === "") {
            alert("กรุณาเลือกประเภทผู้ใช้");
            typeSelect.focus();
            return false;
        }


        return confirm('คุณต้องการบันทึกการเปลี่ยนแปลงใช่หรือไม่?');
    }
</script>

<?php include 'templates/footer.php'; ?>