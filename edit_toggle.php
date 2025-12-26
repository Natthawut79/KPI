<?php
$page_title = "แก้ไขรอบการบันทึกผลงาน";
include 'templates/navbar.php';
include 'config/auth_admin.php'; 
include 'config/conn.php';
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<p>Error: ไม่พบ ID ที่ต้องการแก้ไข</p>";
    include 'templates/footer.php';
    exit();
}
$toggles_id = mysqli_real_escape_string($conn, $_GET['id']);

$sql_data = "SELECT * FROM toggles_switch WHERE Toggles_id = ? LIMIT 1";
$stmt_data = mysqli_prepare($conn, $sql_data);
mysqli_stmt_bind_param($stmt_data, "i", $toggles_id);
mysqli_stmt_execute($stmt_data);
$result_data = mysqli_stmt_get_result($stmt_data);
$toggle_data = mysqli_fetch_assoc($result_data);
mysqli_stmt_close($stmt_data);

if (!$toggle_data) {
    echo "<p>Error: ไม่พบข้อมูล Toggles_id = $toggles_id</p>";
    include 'templates/footer.php';
    exit();
}
$sql_submit_types = "SELECT Submit_type_id, Submit_type_name FROM submit_type ORDER BY Submit_type_id";
$result_submit_types = $conn->query($sql_submit_types);
?>
<link rel="stylesheet" href="css/toggles_switch.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<div class="record-container">
    <div class="record-wrapper">
        <h1 class="form-title">แก้ไขรอบการบันทึกผลงาน</h1>

        <form id="recordForm" action="config/checkedit_toggle.php" method="POST">
            
            <input type="hidden" name="toggles_id" value="<?php echo htmlspecialchars($toggle_data['Toggles_id']); ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="academic_year"><i class="fas fa-calendar-day"></i> ปีการศึกษา (พ.ศ.):</label>
                    <input type="number" id="academic_year" name="academic_year"
                           value="<?php echo htmlspecialchars($toggle_data['Academic']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="submit_type_id"><i class="fas fa-tasks"></i> รอบการประเมิน:</label>
                    <select id="submit_type_id" name="submit_type_id" required>
                        <option value="" disabled>-- เลือกรอบการประเมิน --</option>
                        <?php
                        if ($result_submit_types && $result_submit_types->num_rows > 0) {
                             mysqli_data_seek($result_submit_types, 0); 
                             while ($type = $result_submit_types->fetch_assoc()) {
                                $selected = ($type['Submit_type_id'] == $toggle_data['Submit_type_id']) ? 'selected' : '';
                                echo '<option value="' . $type['Submit_type_id'] . '" ' . $selected . '>' . htmlspecialchars($type['Submit_type_name']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div> 
            
            <div class="setting-box">
                <h3>สถานะ</h3>
                <label class="switch">
                    <?php $status_checked = ($toggle_data['Status'] == 'เปิด') ? 'checked' : ''; ?>
                    <input type="checkbox" id="toggleSwitch" name="toggle_status" value="เปิด" <?php echo $status_checked; ?>>
                    <span class="slider"></span>
                </label>
                <input type="hidden" name="toggle_status_hidden" value="ปิด">
            </div>

            <div class="form-group date-group">
                <div class="date-input">
                    <label for="start-datetime"><i class="fas fa-calendar-alt"></i> วันที่เริ่มต้น:</label>
                    <input type="date" id="start-datetime" name="start-datetime" 
                           value="<?php echo date('Y-m-d', strtotime($toggle_data['Start_date'])); ?>" required>
                </div>
                <div class="date-input">
                    <label for="end-datetime"><i class="fas fa-calendar-check"></i> วันที่สิ้นสุด:</label>
                    <input type="date" id="end-datetime" name="end-datetime" 
                           value="<?php echo date('Y-m-d', strtotime($toggle_data['End_date'])); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="remarks"><i class="fas fa-edit"></i> หมายเหตุ:</label>
                <textarea id="remarks" name="description" rows="6" placeholder="ระบุรายละเอียดเพิ่มเติม..."><?php echo htmlspecialchars($toggle_data['Description']); ?></textarea>
            </div>
            
            <div class="button-container">
                <button type="submit" class="save-btn">บันทึกการแก้ไข</button>
                <a href="show_on_off.php" class="btn-cancel" style="text-decoration: none;">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>

<script src="js/edit_toggle.js"></script>

<?php include 'templates/footer.php'; ?>
</body>
</html>