<?php

if (isset($_GET['group_id'])) {
    include 'config/conn.php'; // เชื่อมต่อ DB
    include 'config/academic_year_resolver.php';

    $group_id = intval($_GET['group_id']);
    $types = [];

    if ($group_id > 0) {
        $current_academic = $current_academic_year;
        // 2. ดึงข้อมูล KPI Type โดยกรองทั้ง Group_ID และ Academic (ปีปัจจุบัน)
        $sql = "SELECT KPI_type_id, KPI_Type_Name_EN, KPI_Type_Name_TH 
                FROM kpi_type 
                WHERE Group_ID = ? AND Academic = ? 
                ORDER BY Order_No ASC";

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

    // ส่งข้อมูลกลับเป็น JSON และจบการทำงานทันที
    header('Content-Type: application/json');
    echo json_encode($types);
    exit();
}

// === [ ส่วนที่ 2: ดึง Order_no ล่าสุด ] ===
if (isset($_GET['kpi_type_id'])) {
    include 'config/conn.php'; // เชื่อมต่อ DB

    $kpi_type_id = intval($_GET['kpi_type_id']);
    $next_order = 1; // ค่าเริ่มต้น

    if ($kpi_type_id > 0) {
        // ค้นหา Order_no ที่มากที่สุดของ KPI_type_id ที่เลือก
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

    // ส่งข้อมูลกลับเป็น JSON
    header('Content-Type: application/json');
    echo json_encode(['next_order' => $next_order]);
    exit();
}


// โค้ด HTML เริ่มต้น
$page_title = "สร้างตัวชี้วัด";
include 'templates/navbar.php';
include 'config/conn.php';


$KPI_topic_id = isset($_GET['KPI_topic_id']) ? $_GET['KPI_topic_id'] : '';

// Query นี้อาจจะคืนค่า null ถ้าไม่มี $KPI_topic_id (กรณีสร้างใหม่) ซึ่งไม่เป็นไรเพราะมีการเช็ค isset ใน value แล้ว
$sql = "SELECT kt.*, 
               t.KPI_type_id, 
               t.KPI_Type_Name_EN,
               i.Important_level_no,
               i.Important_level_name,
               gtk.Group_ID
        FROM kpi_topic kt
        LEFT JOIN kpi_type t ON kt.KPI_type_id = t.KPI_type_id
        LEFT JOIN important_level i ON kt.Important_level_no = i.Important_level_no
        LEFT JOIN group_use_kpis gtk ON t.Group_ID = gtk.Group_ID
        WHERE kt.KPI_topic_id = '$KPI_topic_id'";

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

// ตั้งค่า Group ID เริ่มต้น (ถ้าเป็นการสร้างใหม่ ให้เลือกกลุ่มที่ 1)
$loaded_group_id = isset($row['Group_ID']) ? $row['Group_ID'] : 1;
// ตั้งค่า KPI Type ID เริ่มต้น (สำหรับการโหลดครั้งแรก)
$loaded_kpi_type_id = isset($row['KPI_type_id']) ? $row['KPI_type_id'] : null;
?>

<link rel="stylesheet" href="css/create_indicator.css">

<div class="main-container">
    <div class="form-wrapper">
        <h1 class="form-title">สร้างข้อมูลตัวชี้วัด</h1>

        <form action="config/checkcreate_indicator.php" method="POST"
            onsubmit="return confirm('คุณต้องการบันทึกข้อมูลใช่หรือไม่?');">

            <div class="form-group" style="display: flex; flex-direction: row; align-items: center; flex-wrap: nowrap;">
                <label style="margin: 0; margin-right: 15px; white-space: nowrap;">กลุ่มผู้ใช้ตัวชี้วัด :</label>
                
                <div class="radio-container" style="display: flex; flex-direction: row; align-items: center;">
                <?php
                    // ดึงข้อมูลกลุ่มผู้ใช้จาก DB
                    $sql_groups = "SELECT Group_ID, Group_Name FROM group_use_kpis ORDER BY Group_ID ASC";
                    $res_groups = mysqli_query($conn, $sql_groups);

                    if ($res_groups && mysqli_num_rows($res_groups) > 0) {
                        while ($group = mysqli_fetch_assoc($res_groups)) {
                            // ตรวจสอบว่าควร check radio ไหน
                            $checked = ($loaded_group_id == $group['Group_ID']) ? 'checked' : '';
                            
                            // Style ของแต่ละตัวเลือก:
                            // display: flex -> จัด radio คู่กับข้อความ
                            // white-space: nowrap -> ห้ามตัดคำขึ้นบรรทัดใหม่
                            // margin-right: 25px -> เว้นระยะห่างระหว่างตัวเลือกที่ 1 กับ 2
                            echo "<label style='display: flex; align-items: center; margin: 0; margin-right: 25px; cursor: pointer; white-space: nowrap;'>";
                            echo "<input type='radio' name='Group_ID' id='group_radio_{$group['Group_ID']}' value='{$group['Group_ID']}' $checked required style='margin-right: 8px;'> ";
                            echo htmlspecialchars($group['Group_Name']);
                            echo "</label>";
                        }
                    }
                ?>
                </div>
            </div>

            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด :</label>
                <select name="KPI_type_id" id="kpi_type_select" data-loaded-type-id="<?php echo $loaded_kpi_type_id; ?>"
                    required>
                    <option value="">--- กรุณาเลือก ---</option>
                </select>
            </div>

            <div class="form-group">
                <label>กรอกข้อมูลเพิ่มหรือไม่ :</label>
                <div>
                    <label style="display: inline-block; font-weight: normal; padding-top: 0; font-size: 1.3rem;">
                        <input type="radio" name="fill_data" value="no"
                            style="width: auto; vertical-align: middle; margin-right: 5px;" checked> ไม่กรอกข้อมูล
                    </label>
                    <label
                        style="display: inline-block; margin-right: 20px; font-weight: normal; padding-top: 0; font-size: 1.3rem;">
                        <input type="radio" name="fill_data" value="yes"
                            style="width: auto; vertical-align: middle; margin-right: 5px;"> กรอกข้อมูล
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>ดึงข้อมูลจากฐานข้อมูลหรือไม่ :</label>
                <div>
                    <label style="display: inline-block; font-weight: normal; padding-top: 0; font-size: 1.3rem;">
                        <input type="radio" name="fetch_data" value="no"
                            style="width: auto; vertical-align: middle; margin-right: 5px;" checked> ไม่ดึงข้อมูล
                    </label>
                    <label
                        style="display: inline-block; margin-right: 20px; font-weight: normal; padding-top: 0; font-size: 1.3rem;">
                        <input type="radio" name="fetch_data" value="yes"
                            style="width: auto; vertical-align: middle; margin-right: 5px;"> ดึงข้อมูล
                    </label>
                </div>
            </div>

            <div class="form-group" id="article_type_group" style="display: none;">
                <label>ประเภทบทความ :</label>
                <select name="Table_id" id="article_type_select">
                    <?php
                    $sql_table = "SELECT Table_id, Table_name FROM tablename WHERE Table_id IN (2, 3) ORDER BY Table_id ASC";
                    $res_table = mysqli_query($conn, $sql_table);
                    $current_table_id = isset($row['Table_id']) ? $row['Table_id'] : '';

                    if (mysqli_num_rows($res_table) > 0) {
                        while ($row_tbl = mysqli_fetch_assoc($res_table)) {
                            $selected = ($row_tbl['Table_id'] == $current_table_id) ? "selected" : "";
                            echo '<option value="' . $row_tbl['Table_id'] . '" ' . $selected . '>' . htmlspecialchars($row_tbl['Table_name']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="form-group" id="publication_options_group" style="display: none;">
                <label>ประเภทการตีพิมพ์ :</label>
                <select name="Publication_type_id" id="publication_type_select">
                    <?php
                    $sql_pub_type = "SELECT Publication_type_id, Publication_type_name FROM publicationtype ORDER BY Publication_type_id ASC";
                    $res_pub_type = mysqli_query($conn, $sql_pub_type);
                    $current_pub_id = isset($row['Publication_type_id']) ? $row['Publication_type_id'] : '';

                    if (mysqli_num_rows($res_pub_type) > 0) {
                        while ($row_pt = mysqli_fetch_assoc($res_pub_type)) {
                            $selected = ($row_pt['Publication_type_id'] == $current_pub_id) ? "selected" : "";
                            echo '<option value="' . $row_pt['Publication_type_id'] . '" ' . $selected . '>' . htmlspecialchars($row_pt['Publication_type_name']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>ลำดับที่ :</label>
                <input type="number" name="Order_no" id="order_no_input"
                    value="<?php echo isset($row['Order_no']) ? $row['Order_no'] : ''; ?>"
                    data-loaded-order-no="<?php echo isset($row['Order_no']) ? $row['Order_no'] : ''; ?>" required>
            </div>
            <div class="form-group">
                <label>ชื่อตัวชี้วัด :</label>
                <input type="text" name="KPI_topic_name"
                    value="<?php echo isset($row['KPI_topic_name']) ? htmlspecialchars($row['KPI_topic_name']) : ''; ?>"
                    required>
            </div>
            <div class="form-group">
                <label>หน่วยวัด :</label>
                <input type="text" name="Unit"
                    value="<?php echo isset($row['Unit']) ? htmlspecialchars($row['Unit']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>เป้าหมายความสำเร็จที่คาดหวังทั้งปี :</label>
                <input type="text" name="Goal"
                    value="<?php echo isset($row['Goal']) ? htmlspecialchars($row['Goal']) : ''; ?>">
            </div>

            <div class="form-group">
                <label>เกณฑ์การให้คะแนนตามผลงานที่ทำได้ :</label>
                <textarea name="Score_criteria"
                    rows="5"><?php echo isset($row['Score_criteria']) ? htmlspecialchars($row['Score_criteria']) : ''; ?></textarea>

                <label>เกณฑ์การให้คะแนน ช่องที่ 1:</label>
                <input type="text" name="criteria_1"
                    value="<?php echo isset($row['criteria_1']) ? htmlspecialchars($row['criteria_1']) : ''; ?>">

                <label>เกณฑ์การให้คะแนนช่องที่ 2:</label>
                <input type="text" name="criteria_2"
                    value="<?php echo isset($row['criteria_2']) ? htmlspecialchars($row['criteria_2']) : ''; ?>">

                <label>เกณฑ์การให้คะแนน ช่องที่ 3:</label>
                <input type="text" name="criteria_3"
                    value="<?php echo isset($row['criteria_3']) ? htmlspecialchars($row['criteria_3']) : ''; ?>">

                <label>เกณฑ์การให้คะแนน ช่องที่ 4:</label>
                <input type="text" name="criteria_4"
                    value="<?php echo isset($row['criteria_4']) ? htmlspecialchars($row['criteria_4']) : ''; ?>">

                <label>เกณฑ์การให้คะแนน ช่องที่ 5:</label>
                <input type="text" name="criteria_5"
                    value="<?php echo isset($row['criteria_5']) ? htmlspecialchars($row['criteria_5']) : ''; ?>">
            </div>

            <div class="form-group">
                <label>น้ำหนัก :</label>
                <input type="number" step="0.5" name="Weight"
                    value="<?php echo isset($row['Weight']) ? $row['Weight'] : ''; ?>" required>
            </div>

            <div class="form-group">
                <label>หมายเหตุ :</label>
                <input type="text" name="Description_text"
                    value="<?php echo isset($row['Description_text']) ? htmlspecialchars($row['Description_text']) : ''; ?>">
            </div>

            <div class="form-group">
                <label>ระดับความสำคัญ :</label>
                <select name="Important_level_no" required>
                    <?php
                    $sql_kpi_important_level_no = "SELECT Important_level_no, Important_level_name FROM important_level";
                    $res_kpi_important_level_no = mysqli_query($conn, $sql_kpi_important_level_no);
                    while ($kpi_important_level_no = mysqli_fetch_assoc($res_kpi_important_level_no)) {
                        $selected = (isset($row['Important_level_no']) && $kpi_important_level_no['Important_level_no'] == $row['Important_level_no']) ? "selected" : "";
                        echo "<option value ='{$kpi_important_level_no['Important_level_no']}' $selected>{$kpi_important_level_no['Important_level_name']}</option>";
                    }
                    ?>
                </select>
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