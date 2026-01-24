<?php
    $page_title = "สร้างบัญชีผู้ใช้";
    include 'templates/navbar.php'; 
    include 'config/conn.php';
?>

<link rel="stylesheet" href="css/create_user.css">

<div class="main-container">
    <div class="form-wrapper">
        <h1 class="form-title">สร้างบัญชีผู้ใช้</h1>
        
        <form action="config/check_create_user.php" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
            
            <div class="profile-picture">
                <label for="profile-pic">
                    <img src="img/profile.png" alt="Profile Picture" id="profileImage">
                </label>
                <input type="file" name="IMGname" id="imageUpload" accept="image/*" hidden onchange="previewImage(event)">
                <p style="text-align: center; color: #666; font-size: 0.9rem; margin-top: 5px;">คลิกที่รูปเพื่ออัปโหลด <span style="color:red">*</span></p>
            </div>

            <div class="form-group">
                <label class="required">รหัสพนักงาน:</label>
                <input type="text" name="Emp_code" id="Emp_code" required placeholder="ตัวเลข 8 หลัก">
            </div>
            <div class="form-group">
                <label class="required">รหัสผ่าน:</label>
                <input type="password" name="Password" required>
            </div>
            <div class="form-group">
                <label class="required">คำนำหน้าชื่อ:</label>
                <select name="Title_id" id="Title_id" required>
                    <option value="">--โปรดเลือกคำนำหน้าชื่อ--</option>
                    <?php
                    $sql_title = "SELECT * FROM title ORDER BY Title_name ASC";
                    $res_title = mysqli_query($conn, $sql_title);
                    while ($t = mysqli_fetch_assoc($res_title)) {
                        echo "<option value='{$t['Title_id']}'>{$t['Title_name']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label class="required">ชื่อ (ไทย):</label> 
                <input type="text" name="Fname_th" id="Fname_th" required>
            </div>
            <div class="form-group">
                <label class="required">นามสกุล (ไทย):</label>
                <input type="text" name="Lname_th" id="Lname_th" required>
            </div>
            <div class="form-group">
                <label class="required">ชื่อ (Eng):</label>
                 <input type="text" name="Fname_eng" id="Fname_eng" required>
            </div>
            <div class="form-group">
                <label class="required">นามสกุล (Eng):</label>
                 <input type="text" name="Lname_eng" id="Lname_eng" required>
            </div>
            <div class="form-group">
                <label class="required">สาขา:</label>
                <select name="Department_id" id="Department_id" required>
                    <option value="">--โปรดเลือกสาขา--</option>
                    <?php
                    $sql_dep = "SELECT * FROM department ORDER BY Department_name ASC";
                    $res_dep = mysqli_query($conn, $sql_dep);
                    while ($dep = mysqli_fetch_assoc($res_dep)) {
                        echo "<option value='{$dep['Department_id']}'>{$dep['Department_name']}</option>";
                    }
                    ?>
                </select>
            </div>
           <div class="form-group">
                <label class="required">ประเภทผู้ใช้:</label>
                <select name="Type_id" id="Type_id" required>
                    <option value="">--โปรดเลือกประเภทผู้ใช้--</option>
                    <?php
                    $sql_type = "SELECT * FROM user_type ORDER BY Type_name_th ASC";
                    $res_type = mysqli_query($conn, $sql_type);

                    while ($type = mysqli_fetch_assoc($res_type)) {
                        $display_name = htmlspecialchars($type['Type_name_th']) . " (" . htmlspecialchars($type['Type_name']) . ")";
                        echo "<option value='{$type['Type_id']}'>{$display_name}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="button-container">
                <button type="submit" class="create-btn">บันทึก</button>
                <a href="manage_users.php" class="cancel-btn">ยกเลิก</a>
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
        reader.readAsDataURL(event.target.files[0]);
    }

    function validateForm() {
        var isValid = true;

        // --- 1. ตรวจสอบรูปภาพ  ---
        const imgInput = document.getElementById('imageUpload');
        if (imgInput.files.length === 0) {
            alert("กรุณาเลือกรูปภาพโปรไฟล์ก่อนบันทึก");
            return false;
        }

        // 2. ตรวจสอบรหัสพนักงาน
        const empCodeInput = document.getElementById('Emp_code');
        const empCode = empCodeInput.value;
        const empCodeRegex = /^\d{8}$/;
        if (!empCodeRegex.test(empCode)) {
            alert("รหัสพนักงานต้องเป็นตัวเลขจำนวน 8 หลักเท่านั้น");
            empCodeInput.focus();
            return false;
        }

        // --- ส่วนที่เพิ่มใหม่: ตรวจสอบ Dropdown ---
        // ตรวจสอบคำนำหน้าชื่อ
        const titleSelect = document.getElementById('Title_id');
        if (titleSelect.value === "") {
            alert("กรุณาเลือกคำนำหน้าชื่อ");
            titleSelect.focus();
            return false;
        }

        // 3. ตรวจสอบชื่อ (ไทย)
        const fnameThInput = document.getElementById('Fname_th');
        const thaiRegex = /^[ก-๙]+$/; 
        if (!thaiRegex.test(fnameThInput.value)) {
            alert("ชื่อ (ไทย) ต้องเป็นภาษาไทยเท่านั้น");
            fnameThInput.focus();
            return false;
        }

        // 4. ตรวจสอบนามสกุล (ไทย)
        const lnameThInput = document.getElementById('Lname_th');
        if (!thaiRegex.test(lnameThInput.value)) {
            alert("นามสกุล (ไทย) ต้องเป็นภาษาไทยเท่านั้น");
            lnameThInput.focus();
            return false;
        }

        // 5. ตรวจสอบชื่อ (Eng)
        const fnameEngInput = document.getElementById('Fname_eng');
        const engRegex = /^[a-zA-Z]+$/;
        if (!engRegex.test(fnameEngInput.value)) {
            alert("ชื่อ (Eng) ต้องเป็นภาษาอังกฤษเท่านั้น");
            fnameEngInput.focus();
            return false;
        }

        // 6. ตรวจสอบนามสกุล (Eng)
        const lnameEngInput = document.getElementById('Lname_eng');
        if (!engRegex.test(lnameEngInput.value)) {
            alert("นามสกุล (Eng) ต้องเป็นภาษาอังกฤษเท่านั้น");
            lnameEngInput.focus();
            return false;
        }

        // --- ส่วนที่เพิ่มใหม่: ตรวจสอบ Dropdown ที่เหลือ ---
        // ตรวจสอบสาขา
        const deptSelect = document.getElementById('Department_id');
        if (deptSelect.value === "") {
            alert("กรุณาเลือกสาขา");
            deptSelect.focus();
            return false;
        }

        // ตรวจสอบประเภทผู้ใช้
        const typeSelect = document.getElementById('Type_id');
        if (typeSelect.value === "") {
            alert("กรุณาเลือกประเภทผู้ใช้");
            typeSelect.focus();
            return false;
        }

        return confirm('คุณต้องการสร้างบัญชีผู้ใช้ใช่หรือไม่?');
    }
</script>

<?php
    include 'templates/footer.php';
?>