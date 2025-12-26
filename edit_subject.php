<?php
$page_title = "แก้ไขหัวข้อตัวชี้วัด";
include 'templates/navbar.php';
include 'config/conn.php';
include 'config/academic_year_resolver.php'; 

$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

$sql = "SELECT st.*, kt.Group_ID, kt.Academic 
        FROM subject_topic st
        LEFT JOIN kpi_type kt ON st.KPI_type_id = kt.KPI_type_id
        WHERE st.subject_id = '$subject_id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    echo "<script>alert('ไม่พบข้อมูล'); window.location='subject_topic.php';</script>";
    exit();
}
$current_year = $current_academic_year;
$is_editable = ($row['Academic'] == $current_year);
$disabled_style = "background-color: #cccccc !important; border-color: #cccccc !important; color: #666666 !important; cursor: not-allowed; pointer-events: none; opacity: 0.8; box-shadow: none;";
?>

<link rel="stylesheet" href="css/edit_indicator.css">

<div class="main-container">
    <div class="form-wrapper">
        <h1 class="form-title">แก้ไขหัวข้อตัวชี้วัด</h1>

        <form action="config/checkedit_subject.php" method="POST" onsubmit="return confirm('ยืนยันการบันทึกการแก้ไข?');">
            <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
            
            <input type="hidden" id="is_edit_mode" value="1">
            <input type="hidden" id="current_kpi_type_id" value="<?php echo $row['KPI_type_id']; ?>">

            <div class="form-group">
                <label>ปีการศึกษา :</label>
                <input type="number" id="academic_year" name="Academic" 
                       value="<?php echo $row['Academic']; ?>" required>
            </div>

            <div class="form-group" style="display: flex; flex-direction: row; align-items: center; flex-wrap: nowrap;">
                <label style="margin: 0; margin-right: 15px; white-space: nowrap;">กลุ่มผู้ใช้ตัวชี้วัด :</label>
                <div class="radio-container" style="display: flex; flex-direction: row; align-items: center;">
                <?php
                    $sql_groups = "SELECT Group_ID, Group_Name FROM group_use_kpis ORDER BY Group_ID ASC";
                    $res_groups = mysqli_query($conn, $sql_groups);
                    
                    while ($group = mysqli_fetch_assoc($res_groups)) {
                        $checked = ($group['Group_ID'] == $row['Group_ID']) ? 'checked' : ''; 
                        
                        echo "<label style='display: flex; align-items: center; margin: 0; margin-right: 25px; cursor: pointer; white-space: nowrap;'>";
                        echo "<input type='radio' name='Group_ID' value='{$group['Group_ID']}' $checked required style='margin-right: 8px;'> ";
                        echo htmlspecialchars($group['Group_Name']);
                        echo "</label>";
                    }
                ?>
                </div>
            </div>

            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด :</label>
                <select name="KPI_type_id" id="kpi_type_select" required>
                    <option value="">กำลังโหลดข้อมูล...</option>
                </select>
            </div>

            <div class="form-group">
                <label>ลำดับที่ :</label>
                <input type="number" name="subject_order" id="order_no_input" 
                       value="<?php echo $row['subject_order']; ?>" required>
            </div>

            <div class="form-group">
                <label>ชื่อหัวข้อตัวชี้วัด :</label>
                <input type="text" name="subject_name" 
                       value="<?php echo htmlspecialchars($row['subject_name']); ?>" required>
            </div>

            <div class="button-container">
                <button type="submit" class="save-btn"
                    <?php echo $is_editable ? '' : 'disabled'; ?>
                    style="<?php echo $is_editable ? '' : $disabled_style; ?>">
                    บันทึก
                </button>
                
                <a href="<?php echo $is_editable ? 'config/checkdelete_subject.php?subject_id=' . $subject_id : 'javascript:void(0);'; ?>" 
                   class="delete-btn" 
                   <?php if($is_editable): ?>
                       onclick="return confirm('คุณต้องการลบหัวข้อตัวชี้วัดนี้ใช่หรือไม่?');"
                   <?php endif; ?>
                   style="<?php echo $is_editable ? '' : $disabled_style; ?>">
                   ลบ
                </a>
            </div>
        </form>
    </div>
</div>

<script src="js/subject_topic.js"></script>
<?php include 'templates/footer.php'; ?>