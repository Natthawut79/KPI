<?php
    $page_title = "เพิ่มรอบการบันทึกผลงาน";
    include 'templates/navbar.php';
    include 'config/auth_admin.php'; 
    include 'config/conn.php';

    $sql_submit_types = "SELECT Submit_type_id, Submit_type_name FROM submit_type ORDER BY Submit_type_id";
    $result_submit_types = $conn->query($sql_submit_types);
?>
<link rel="stylesheet" href="css/toggles_switch.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<div class="record-container">
    <div class="record-wrapper">
        <h1 class="form-title">เพิ่มรอบการบันทึกผลงานใหม่</h1>

        <form id="recordForm" action="config/checkcreate_toggle.php" method="POST">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="academic_year"><i class="fas fa-calendar-day"></i> ปีการศึกษา (พ.ศ.):<span class="required-mark">*</span></label>
                    <input type="number" id="academic_year" name="academic_year"
                           placeholder="เช่น 2568" 
                           value="" required>
                </div>
                <div class="form-group">
                    <label for="submit_type_id"><i class="fas fa-tasks"></i> รอบการประเมิน:<span class="required-mark">*</span></label>
                    <select id="submit_type_id" name="submit_type_id" required>
                        <option value="" disabled selected>-- โปรดเลือกรอบการประเมิน --</option>
                        <?php
                        if ($result_submit_types && $result_submit_types->num_rows > 0) {
                             mysqli_data_seek($result_submit_types, 0); 
                             while ($type = $result_submit_types->fetch_assoc()) {
                                echo '<option value="' . $type['Submit_type_id'] . '">' . htmlspecialchars($type['Submit_type_name']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div> 
            
            <div class="setting-box">
                <h3>สถานะ</h3>
                <label class="switch">
                    <input type="checkbox" id="toggleSwitch" name="toggle_status" value="เปิด" checked>
                    <span class="slider"></span>
                </label>
                <input type="hidden" name="toggle_status_hidden" value="ปิด">
            </div>

            <div class="form-group date-group">
                <div class="date-input">
                    <label for="start-datetime"><i class="fas fa-calendar-alt"></i> วันที่เริ่มต้น:<span class="required-mark">*</span></label>
                    <input type="date" id="start-datetime" name="start-datetime" required>
                </div>
                <div class="date-input">
                    <label for="end-datetime"><i class="fas fa-calendar-check"></i> วันที่สิ้นสุด:<span class="required-mark">*</span></label>
                    <input type="date" id="end-datetime" name="end-datetime" required>
                </div>
            </div>

            <div class="form-group">
                <label for="remarks"><i class="fas fa-edit"></i> หมายเหตุ:</label>
                <textarea id="remarks" name="description" rows="6" placeholder="ระบุรายละเอียดเพิ่มเติม..."></textarea>
            </div>
            
            <div class="button-container">
                <button type="submit" class="save-btn">บันทึก</button>
                <a href="show_on_off.php" class="btn-cancel" style="text-decoration: none;">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>
<script src="js/toggles_switch.js"></script>

<?php include 'templates/footer.php'; ?>
</body>
</html>