<?php
if (isset($_GET['group_id'])) {
    include 'config/conn.php'; // เชื่อมต่อ DB เฉพาะเมื่อเป็น AJAX request

    $group_id = intval($_GET['group_id']);
    $types = [];

    if ($group_id > 0) {
        // ดึงข้อมูล kpi_type ที่ตรงกับ Group_ID ที่ส่งมา
        $sql_ajax = "SELECT KPI_type_id, KPI_Type_Name_EN, KPI_Type_Name_TH
                FROM kpi_type
                WHERE Group_ID = ?
                ORDER BY Order_No ASC";

        $stmt = mysqli_prepare($conn, $sql_ajax);
        mysqli_stmt_bind_param($stmt, "i", $group_id);
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

    // ส่งข้อมูลกลับเป็น JSON แล้วหยุดการทำงานทันที
    header('Content-Type: application/json');
    echo json_encode($types);
    exit(); // <<-- สำคัญมาก: หยุดไม่ให้แสดง HTML ด้านล่าง
}
$page_title = "ตัวชี้วัด";
include 'templates/navbar.php';
include 'config/conn.php';
include 'config/academic_year_resolver.php';

$Academic = isset($_POST['Academic']) ? mysqli_real_escape_string($conn, $_POST['Academic']) : '';
$Group_ID = isset($_POST['group_use_kpis']) ? mysqli_real_escape_string($conn, $_POST['group_use_kpis']) : '';
$KPI_type_id = isset($_POST['kpi_type']) ? mysqli_real_escape_string($conn, $_POST['kpi_type']) : ''; // <-- เพิ่มตัวแปรใหม่

// ตรวจสอบว่ามีการกดค้นหาหรือไม่
$isSearch = ($_SERVER['REQUEST_METHOD'] === 'POST');


$noResult = (empty($Academic) && $Group_ID === 'empty' && empty($KPI_type_id));
$sql = "
    SELECT 
        kt.KPI_topic_id,
        kt.KPI_topic_name,
        kt.Unit,
        kt.Goal,
        il.Important_level_name AS Priority,
        gtk.Group_Name,
        ktype.Academic
    FROM kpi_topic AS kt
    JOIN kpi_type AS ktype ON kt.KPI_type_id = ktype.KPI_type_id
    JOIN group_use_kpis AS gtk ON ktype.Group_ID = gtk.Group_ID
    JOIN important_level AS il ON kt.Important_level_no = il.Important_level_no
    WHERE 1
";
if (!empty($Academic)) {
    $sql .= " AND ktype.Academic LIKE '%$Academic%'";
}
if (!empty($Group_ID) && $Group_ID !== 'empty') {
    $sql .= " AND gtk.Group_ID = '$Group_ID'";
}
// เพิ่มเงื่อนไขสำหรับ KPI Type
if (!empty($KPI_type_id)) {
    $sql .= " AND ktype.KPI_type_id = '$KPI_type_id'";
}
// === [ สิ้นสุดการแก้ไขส่วน SQL WHERE ] ===

$sql .= " ORDER BY kt.KPI_topic_id ASC";

// ✅ ถ้าผู้ใช้เลือกช่องว่าง ให้ไม่ดึงข้อมูล
$result = ($isSearch && !$noResult) ? mysqli_query($conn, $sql) : false;
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="css/indicators.css">

<div class="content-wrapper">
    <div class="page-header">
        <h1>ตัวชี้วัดทั้งหมด</h1>
        <a href="create_indicator.php" class="add-indicator-btn">
            <i class="fas fa-plus-circle"></i>
            <span>เพิ่มตัวชี้วัด</span>
        </a>
    </div>

    <div class="search-container">
        <form method="POST" class="search-form">
            <div class="form-group">
                <label for="Academic">ค้นหาตามปีการศึกษา</label>
                <input type="text" id="Academic" name="Academic" value="<?php echo htmlspecialchars($Academic); ?>"
                    placeholder="ปีการศึกษา...">
            </div>

            <div class="form-group">
                <label for="group_use_kpis">กลุ่มผู้ใช้ตัวชี้วัด</label>
                <select id="group_use_kpis" name="group_use_kpis">
                    <option value="">ทั้งหมด</option>
                    <?php
                    $sql_group = "SELECT Group_ID, Group_Name FROM group_use_kpis ORDER BY Group_Name ASC";
                    $res_group = mysqli_query($conn, $sql_group);
                    if ($res_group && mysqli_num_rows($res_group) > 0) {
                        while ($g = mysqli_fetch_assoc($res_group)) {
                            $selected = ($Group_ID == $g['Group_ID']) ? 'selected' : '';
                            echo "<option value='{$g['Group_ID']}' $selected>{$g['Group_Name']}</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="kpi_type">ประเภทตัวชี้วัด</label>
                <select id="kpi_type" name="kpi_type" data-selected-id="<?php echo htmlspecialchars($KPI_type_id); ?>">
                    <option value="">ทั้งหมด</option>
                </select>
            </div>

            <button type="submit" class="search-button">ค้นหา</button>
        </form>
    </div>

    <div class="table-container">
        <table class="indicator-table">
            <thead>
                <tr>
                    <th>ลำดับ</th>
                    <th>ชื่อตัวชี้วัด</th>
                    <th>หน่วยวัด</th>
                    <th>เป้าหมาย</th>
                    <th>ระดับความสำคัญ</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // [เพิ่ม] กำหนดปีปัจจุบัน (พ.ศ.)
                $current_year = $current_academic_year;

                if ($noResult) {
                    // กรณีเลือก "ช่องว่าง"
                    echo "<tr><td colspan='6' class='text-center text-muted'>ไม่มีข้อมูลให้แสดง</td></tr>";
                } elseif ($isSearch && $result && mysqli_num_rows($result) > 0) {
                    $count = 1;
                    while ($row = mysqli_fetch_assoc($result)) {
                        
                        // [เพิ่ม] สร้างปุ่มลบตามเงื่อนไขปีปัจจุบัน
                        $delete_btn = '';
                        if ($row['Academic'] == $current_year) {
                            // ปุ่มลบปกติ
                            $delete_btn = "<a href='config/checkdelete_indicator.php?KPI_topic_id={$row['KPI_topic_id']}' class='action-btn btn-delete' 
                                           onclick='return confirm(\"คุณแน่ใจหรือว่าต้องการลบตัวชี้วัดนี้?\");'>ลบ</a>";
                        } else {
                            // ปุ่มลบแบบ Disable (สีเทา + กดไม่ได้)
                            $delete_btn = "<a href='#' class='action-btn btn-delete' 
                                           style='background-color: #cccccc; cursor: not-allowed; pointer-events: none; opacity: 0.6;' 
                                           title='ไม่สามารถลบข้อมูลย้อนหลังได้'>ลบ</a>";
                        }

                        echo "<tr>
                                <td>{$count}</td>
                                <td>{$row['KPI_topic_name']}</td>
                                <td>{$row['Unit']}</td>
                                <td>{$row['Goal']}</td>
                                <td>{$row['Priority']}</td>
                                
                                <td class='text-center'>
                                    <a href='edit_indicator.php?KPI_topic_id={$row['KPI_topic_id']}' class='action-btn btn-edit'>แก้ไข</a>
                                    {$delete_btn} 
                                </td>
                              </tr>";
                        $count++;
                    }
                } elseif ($isSearch) {
                    echo "<tr><td colspan='6' class='text-center'>ไม่พบข้อมูล</td></tr>";
                } else {
                    echo "<tr><td colspan='6' class='text-center text-muted'>กรุณากรอกข้อมูลเพื่อค้นหา</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script src="js/indicators.js"></script>

<?php include 'templates/footer.php'; ?>