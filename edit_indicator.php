<?php
if (isset($_GET['get_subjects']) && isset($_GET['kpi_type_id'])) {
    include 'config/conn.php';
    $kpi_type_id = intval($_GET['kpi_type_id']);
    $subjects = [];

    if ($kpi_type_id > 0) {
        $sql = "SELECT subject_id, subject_name 
                FROM subject_topic 
                WHERE KPI_type_id = ? 
                ORDER BY subject_order ASC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $kpi_type_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $subjects[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);

    header('Content-Type: application/json');
    echo json_encode($subjects);
    exit();
}
if (isset($_GET['group_id'])) {
    include 'config/conn.php'; 
    include 'config/academic_year_resolver.php';

    $group_id = intval($_GET['group_id']);
    $types = [];

    if ($group_id > 0) {
        $sql_ajax = "SELECT KPI_type_id, KPI_Type_Name_EN, KPI_Type_Name_TH
                     FROM kpi_type
                     WHERE Group_ID = ? AND Academic = ?
                     ORDER BY Order_No ASC";

        $stmt = mysqli_prepare($conn, $sql_ajax);
        mysqli_stmt_bind_param($stmt, "is", $group_id, $current_academic_year);
        mysqli_stmt_execute($stmt);
        $result_ajax = mysqli_stmt_get_result($stmt);

        if ($result_ajax) {
            while ($row_type = mysqli_fetch_assoc($result_ajax)) {
                $types[] = $row_type;
            }
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);

    header('Content-Type: application/json');
    echo json_encode($types);
    exit(); 
}

$page_title = "แก้ไขตัวชี้วัด";
include 'templates/navbar.php'; 
include 'config/conn.php';
include 'config/academic_year_resolver.php'; 

if (isset($_GET['KPI_topic_id'])) {
    $KPI_topic_id = mysqli_real_escape_string($conn, $_GET['KPI_topic_id']);

    $sql = "SELECT kt.*,
                   t.KPI_type_id,
                   t.Group_ID,
                   t.Academic
            FROM kpi_topic kt
            JOIN kpi_type t ON kt.KPI_type_id = t.KPI_type_id
            WHERE kt.KPI_topic_id = '$KPI_topic_id'
            LIMIT 1";

    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    if (!$row) {
        echo "<p>ไม่พบข้อมูลตัวชี้วัด</p>";
        include 'templates/footer.php';
        exit();
    }

    $current_year = $current_academic_year;
    $is_editable = ($row['Academic'] == $current_year);

    $btn_base_style = "min-width: 100px; text-align: center; justify-content: center; display: inline-flex; align-items: center; margin-right: 10px;";
    $disabled_style = "background-color: #cccccc !important; border-color: #cccccc !important; color: #666666 !important; cursor: not-allowed; pointer-events: none; opacity: 0.8; box-shadow: none;";
    
    $loaded_group_id = $row['Group_ID'];
    $loaded_kpi_type_id = $row['KPI_type_id'];

    $additional_value = (isset($row['Additional']) && $row['Additional'] == 'yes') ? 'yes' : 'no';
    $retrieve_value = (isset($row['Retrieve']) && $row['Retrieve'] == 'yes') ? 'yes' : 'no';

} else {
    echo "<p>ไม่พบรหัส KPI_topic_id</p>";
    include 'templates/footer.php';
    exit();
}
?>

<link rel="stylesheet" href="css/edit_indicator.css">

<div class="main-container">
    <div class="form-wrapper">
        <h1 class="form-title">แก้ไขข้อมูลตัวชี้วัด</h1>

        <form action="config/checkedit_indicator.php" method="POST" onsubmit="return confirm('คุณต้องการบันทึกการแก้ไขใช่หรือไม่?');">
            <input type="hidden" name="KPI_topic_id" value="<?php echo htmlspecialchars($row['KPI_topic_id']); ?>">

            <div class="form-group">
                <label>กลุ่มผู้ใช้ตัวชี้วัด :</label>
                
                <div class="radio-container">
                <?php
                    $sql_groups = "SELECT Group_ID, Group_Name FROM group_use_kpis ORDER BY Group_ID ASC";
                    $res_groups = mysqli_query($conn, $sql_groups);

                    if ($res_groups && mysqli_num_rows($res_groups) > 0) {
                        while ($group = mysqli_fetch_assoc($res_groups)) {
                            $checked = ($loaded_group_id == $group['Group_ID']) ? 'checked' : '';
                            // ใช้ class radio-label แทน inline style
                            echo "<label class='radio-label'>";
                            echo "<input type='radio' name='Group_ID' id='group_radio_{$group['Group_ID']}' value='{$group['Group_ID']}' $checked required> ";
                            echo htmlspecialchars($group['Group_Name']);
                            echo "</label>";
                        }
                    }
                ?>
                </div>
            </div>

            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด :</label>
                <select name="KPI_type_id" id="kpi_type_select" data-loaded-id="<?php echo $loaded_kpi_type_id; ?>" required>
                     <option value="">--- กรุณาเลือก ---</option>
                </select>
            </div>

            <div class="form-group">
                <label>ชื่อหัวข้อตัวชี้วัด :</label>
                <select name="subject_id" id="subject_select" data-loaded-subject-id="<?php echo isset($row['subject_id']) ? $row['subject_id'] : ''; ?>">
                    <option value="">--- กรุณาเลือก ---</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>กรอกข้อมูลเพิ่มหรือไม่ :</label>
                <div class="radio-container">
                    <label class="radio-label">
                        <input type="radio" name="fill_data" value="no" <?php echo ($additional_value == 'no') ? 'checked' : ''; ?>> ไม่กรอกข้อมูล
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="fill_data" value="yes" <?php echo ($additional_value == 'yes') ? 'checked' : ''; ?>> กรอกข้อมูล
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>ดึงข้อมูลจากฐานข้อมูลหรือไม่ :</label>
                <div class="radio-container">
                    <label class="radio-label">
                        <input type="radio" name="fetch_data" value="no" <?php echo ($retrieve_value == 'no') ? 'checked' : ''; ?>> ไม่ดึงข้อมูล
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="fetch_data" value="yes" <?php echo ($retrieve_value == 'yes') ? 'checked' : ''; ?>> ดึงข้อมูล
                    </label>
                </div>
            </div>

            <div class="form-group" id="article_type_group" style="display: none;">
                <label>ประเภทบทความ :</label>
                <select name="Table_id" id="article_type_select">
                    <option value="2" <?php echo (isset($row['Table_id']) && $row['Table_id'] == 2) ? 'selected' : ''; ?>>research</option>
                    <option value="3" <?php echo (isset($row['Table_id']) && $row['Table_id'] == 3) ? 'selected' : ''; ?>>publication</option>
                </select>
            </div>
            <div class="form-group" id="publication_options_group" style="display: none;">
                <label>ประเภทการตีพิมพ์ :</label>
                <select name="Publication_type_id" id="publication_type_select">
                    <option value="1" <?php echo (isset($row['Publication_type_id']) && $row['Publication_type_id'] == 1) ? 'selected' : ''; ?>>นานาชาติ (ฐาน Scopus)</option>
                    <option value="2" <?php echo (isset($row['Publication_type_id']) && $row['Publication_type_id'] == 2) ? 'selected' : ''; ?>>ฐานข้อมูล TCI</option>
                    <option value="3" <?php echo (isset($row['Publication_type_id']) && $row['Publication_type_id'] == 3) ? 'selected' : ''; ?>>อาจารย์ประจำหลักสูตร</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>ลำดับที่ :</label>
                <input type="number" name="Order_no" value="<?php echo htmlspecialchars($row['Order_no']); ?>" required>
            </div>
            <div class="form-group">
                <label>ชื่อตัวชี้วัด :</label>
                <input type="text" name="KPI_topic_name" value="<?php echo htmlspecialchars($row['KPI_topic_name']); ?>" required>
            </div>
            <div class="form-group">
                <label>หน่วยวัด :</label>
                <input type="text" name="Unit" value="<?php echo htmlspecialchars($row['Unit']); ?>">
            </div>
            <div class="form-group">
                <label>เป้าหมายความสำเร็จที่คาดหวังทั้งปี :</label>
                <input type="text" name="Goal" value="<?php echo htmlspecialchars($row['Goal']); ?>">
            </div>
            <div class="form-group">
                <label>เกณฑ์การให้คะแนนตามผลงานที่ทำได้ :</label>
                <textarea name="Score_criteria" rows="5"><?php echo htmlspecialchars($row['Score_criteria']); ?></textarea>
                
                <label>เกณฑ์การให้คะแนน ช่องที่ 1:</label>
                <input type="text" name="criteria_1" value="<?php echo isset($row['criteria_1']) ? htmlspecialchars($row['criteria_1']) : ''; ?>">

                <label>เกณฑ์การให้คะแนน ช่องที่ 2:</label>
                <input type="text" name="criteria_2" value="<?php echo isset($row['criteria_2']) ? htmlspecialchars($row['criteria_2']) : ''; ?>">

                <label>เกณฑ์การให้คะแนน ช่องที่ 3:</label>
                <input type="text" name="criteria_3" value="<?php echo isset($row['criteria_3']) ? htmlspecialchars($row['criteria_3']) : ''; ?>">

                <label>เกณฑ์การให้คะแนน ช่องที่ 4:</label>
                <input type="text" name="criteria_4" value="<?php echo isset($row['criteria_4']) ? htmlspecialchars($row['criteria_4']) : ''; ?>">

                <label>เกณฑ์การให้คะแนน ช่องที่ 5:</label>
                <input type="text" name="criteria_5" value="<?php echo isset($row['criteria_5']) ? htmlspecialchars($row['criteria_5']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>น้ำหนัก :</label>
                <input type="number" step="0.5" name="Weight" value="<?php echo htmlspecialchars($row['Weight']); ?>">
            </div>
            <div class="form-group">
                <label>ระดับความสำคัญ :</label>
                <select name="Important_level_no" required>
                    <?php
                    $sql_important_level_no = "SELECT Important_level_no, Important_level_name FROM important_level";
                    $res_important_level_no = mysqli_query($conn, $sql_important_level_no);
                    while ($important_level_no = mysqli_fetch_assoc($res_important_level_no)) {
                        $selected = ($important_level_no['Important_level_no'] == $row['Important_level_no']) ? "selected" : "";
                        echo "<option value='" . htmlspecialchars($important_level_no['Important_level_no']) . "' $selected>" . htmlspecialchars($important_level_no['Important_level_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>หมายเหตุ :</label>
                <textarea name="Description_text" rows="5"><?php echo htmlspecialchars($row['Description_text']); ?></textarea>
            </div>
            <div class="button-container">
                <button type="submit" class="save-btn"
                    <?php echo $is_editable ? '' : 'disabled'; ?>
                    style="<?php echo $is_editable ? '' : $disabled_style; ?>">
                    บันทึก
                </button>

                <a href="config/checkdelete_indicator.php?KPI_topic_id=<?php echo htmlspecialchars($row['KPI_topic_id']); ?>"
                   class="delete-btn"
                   onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลนี้?');"
                   <?php echo $is_editable ? '' : 'style="' . $disabled_style . '"'; ?>> ลบ
                </a>
            </div>
        </form>
    </div>
</div>

<script src="js/edit_indicator.js"></script>
<?php
    include 'templates/footer.php'; 
    mysqli_close($conn); 
?>