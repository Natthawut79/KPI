<?php
if (isset($_GET['group_id'])) {
    include 'config/conn.php';
    include 'config/academic_year_resolver.php';

    $group_id = intval($_GET['group_id']);
    $types = [];

    if ($group_id > 0) {
        $current_academic = $current_academic_year;
        $sql = "SELECT KPI_type_id, KPI_Type_Name_EN, KPI_Type_Name_TH 
                FROM kpi_type 
                WHERE Group_ID = ? AND Academic = ? 
                ORDER BY KPI_Type_Name_EN ASC";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $group_id, $current_academic);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
            while ($row_type = mysqli_fetch_assoc($result)) {
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

if (isset($_GET['get_subjects']) && isset($_GET['kpi_type_id'])) {
    include 'config/conn.php';

    $kpi_type_id = intval($_GET['kpi_type_id']);
    $subjects = [];

    if ($kpi_type_id > 0) {
        $sql = "SELECT subject_id, subject_name 
                FROM subject_topic 
                WHERE KPI_type_id = ? 
                ORDER BY subject_name ASC";

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

if (isset($_GET['kpi_type_id'])) {
    include 'config/conn.php';

    $kpi_type_id = intval($_GET['kpi_type_id']);
    $next_order = 1;

    if ($kpi_type_id > 0) {
        $sql = "SELECT MAX(Order_no) AS max_order FROM kpi_topic WHERE KPI_type_id = ?";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $kpi_type_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
            $row_order = mysqli_fetch_assoc($result);
            if (isset($row_order['max_order']) && $row_order['max_order'] !== null) {
                $next_order = $row_order['max_order'] + 1;
            }
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);

    header('Content-Type: application/json');
    echo json_encode(['next_order' => $next_order]);
    exit();
}
$page_title = "สร้างตัวชี้วัด";
include 'templates/navbar.php';
include 'config/conn.php';
?>

<link rel="stylesheet" href="css/create_indicator.css">

<div class="main-container">
    <div class="form-wrapper">
        <h1 class="form-title">สร้างข้อมูลตัวชี้วัด</h1>

        <form action="config/checkcreate_indicator.php" method="POST"
            onsubmit="return confirm('คุณต้องการบันทึกข้อมูลใช่หรือไม่?');">

            <div class="form-group">
                <label>กลุ่มผู้ใช้ตัวชี้วัด :<span class="required-mark">  *</span></label>
                
                <div class="radio-container">
                <?php
                    $sql_groups = "SELECT Group_ID, Group_Name FROM group_use_kpis ORDER BY Group_ID ASC";
                    $res_groups = mysqli_query($conn, $sql_groups);

                    if ($res_groups && mysqli_num_rows($res_groups) > 0) {
                        while ($group = mysqli_fetch_assoc($res_groups)) {
                            echo "<label class='radio-label'>";
                            echo "<input type='radio' name='Group_ID' value='{$group['Group_ID']}' required> ";
                            echo htmlspecialchars($group['Group_Name']);
                            echo "</label>";
                        }
                    }
                ?>
                </div>
            </div>

            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด :<span class="required-mark">  *</span></label>
                <select name="KPI_type_id" id="kpi_type_select" required>
                    <option value="">--- กรุณาเลือก ---</option>
                </select>
            </div>

            <div class="form-group">
                <label>ชื่อหัวข้อตัวชี้วัด :<span class="required-mark">  *</span></label>
                <select name="subject_id" id="subject_select" required>
                    <option value="">--- กรุณาเลือก ---</option>
                </select>
            </div>

            <div class="form-group">
                <label>กรอกข้อมูลเพิ่มหรือไม่ :<span class="required-mark">  *</span></label>
                <div class="radio-container">
                    <label class="radio-label">
                        <input type="radio" name="fill_data" value="no" checked> ไม่กรอกข้อมูล
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="fill_data" value="yes"> กรอกข้อมูล
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>ดึงข้อมูลจากฐานข้อมูลหรือไม่ :<span class="required-mark">  *</span></label>
                <div class="radio-container">
                    <label class="radio-label">
                        <input type="radio" name="fetch_data" value="no" checked> ไม่ดึงข้อมูล
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="fetch_data" value="yes"> ดึงข้อมูล
                    </label>
                </div>
            </div>

            <div class="form-group" id="article_type_group" style="display: none;">
                <label>ประเภทบทความ :</label>
                <select name="Table_id" id="article_type_select">
                    <option value="">--- กรุณาเลือก ---</option>
                    <?php
                    $sql_table = "SELECT Table_id, Table_name FROM tablename WHERE Table_id IN (2, 3) ORDER BY Table_name ASC";
                    $res_table = mysqli_query($conn, $sql_table);

                    if (mysqli_num_rows($res_table) > 0) {
                        while ($row_tbl = mysqli_fetch_assoc($res_table)) {
                            echo '<option value="' . $row_tbl['Table_id'] . '">' . htmlspecialchars($row_tbl['Table_name']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="form-group" id="publication_options_group" style="display: none;">
                <label>ประเภทการตีพิมพ์ :</label>
                <select name="Publication_type_id" id="publication_type_select">
                    <option value="">--- กรุณาเลือก ---</option>
                    <?php
                    $sql_pub_type = "SELECT Publication_type_id, Publication_type_name FROM publicationtype ORDER BY Publication_type_name ASC";
                    $res_pub_type = mysqli_query($conn, $sql_pub_type);

                    if (mysqli_num_rows($res_pub_type) > 0) {
                        while ($row_pt = mysqli_fetch_assoc($res_pub_type)) {
                            echo '<option value="' . $row_pt['Publication_type_id'] . '">' . htmlspecialchars($row_pt['Publication_type_name']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>ลำดับที่ :<span class="required-mark">  *</span></label>
                <input type="number" name="Order_no" id="order_no_input" required>
            </div>
            <div class="form-group">
                <label>ชื่อตัวชี้วัด :<span class="required-mark">  *</span></label>
                <input type="text" name="KPI_topic_name" required>
            </div>
            <div class="form-group">
                <label>หน่วยวัด :<span class="required-mark">  *</span></label>
                <input type="text" name="Unit" required>
            </div>
            <div class="form-group">
                <label>เป้าหมายความสำเร็จที่คาดหวังทั้งปี :<span class="required-mark">  *</span></label>
                <input type="text" name="Goal" required>
            </div>

            <div class="form-group">
                <label>เกณฑ์การให้คะแนนตามผลงานที่ทำได้ :<span class="required-mark">  *</span></label>
                <textarea name="Score_criteria" required rows="5"></textarea>

                <label>เกณฑ์การให้คะแนน ช่องที่ 1:</label>
                <input type="text" name="criteria_1">

                <label>เกณฑ์การให้คะแนน ช่องที่ 2:</label>
                <input type="text" name="criteria_2">

                <label>เกณฑ์การให้คะแนน ช่องที่ 3:</label>
                <input type="text" name="criteria_3">

                <label>เกณฑ์การให้คะแนน ช่องที่ 4:</label>
                <input type="text" name="criteria_4">

                <label>เกณฑ์การให้คะแนน ช่องที่ 5:</label>
                <input type="text" name="criteria_5">
            </div>

            <div class="form-group">
                <label>น้ำหนัก :<span class="required-mark">  *</span></label>
                <input type="number" step="0.5" name="Weight" required>
            </div>
            <div class="form-group">
                <label>ระดับความสำคัญ :<span class="required-mark">  *</span></label>
                <select name="Important_level_no" required>
                    <option value="">--- กรุณาเลือก ---</option> 
                    <?php
                    $sql_kpi_important_level_no = "SELECT Important_level_no, Important_level_name FROM important_level";
                    $res_kpi_important_level_no = mysqli_query($conn, $sql_kpi_important_level_no);
                    while ($kpi_important_level_no = mysqli_fetch_assoc($res_kpi_important_level_no)) {
                        echo "<option value ='{$kpi_important_level_no['Important_level_no']}'>{$kpi_important_level_no['Important_level_name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>หมายเหตุ :</label>
                <textarea name="Description_text" rows="5"></textarea>
            </div>
            

            <div class="button-container">
                <button type="submit" class="create-btn">สร้าง</button>
                <a href="kpi_type.php" class="cancel-btn">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>
<script src="js/create_indicator.js"></script>

<?php
include 'templates/footer.php';
?>