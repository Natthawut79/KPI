<?php
if (isset($_GET['ajax_request']) && $_GET['ajax_request'] == 'get_kpi_types') {
    include 'config/conn.php';
    
    $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
    $academic = isset($_GET['academic']) ? mysqli_real_escape_string($conn, $_GET['academic']) : '';
    
    $types = [];
    $sql = "SELECT KPI_type_id, KPI_Type_Name_EN, KPI_Type_Name_TH, Academic 
            FROM kpi_type 
            WHERE Group_ID = '$group_id' ";
    
    if (!empty($academic)) {
        $sql .= " AND Academic = '$academic' ";
    }
    
    $sql .= " ORDER BY Academic DESC, Order_No ASC";
    
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $types[] = $row;
        }
    }
    
    echo json_encode($types);
    exit();
}
$page_title = "หัวข้อตัวชี้วัด";
include 'templates/navbar.php';
include 'config/conn.php';
include 'config/academic_year_resolver.php';

$Academic = isset($_POST['Academic']) ? mysqli_real_escape_string($conn, $_POST['Academic']) : '';
$Group_ID = isset($_POST['group_use_kpis']) ? mysqli_real_escape_string($conn, $_POST['group_use_kpis']) : '';
$KPI_type_id = isset($_POST['kpi_type']) ? mysqli_real_escape_string($conn, $_POST['kpi_type']) : '';

$isSearch = ($_SERVER['REQUEST_METHOD'] === 'POST');
$noResult = (empty($Academic) && empty($Group_ID) && empty($KPI_type_id));

$sql = "
    SELECT 
        st.subject_id,
        st.subject_name,
        st.subject_order,
        kt.KPI_Type_Name_TH,
        kt.KPI_Type_Name_EN,
        kt.Academic,
        g.Group_Name,
        g.Group_ID
    FROM subject_topic st
    JOIN kpi_type kt ON st.KPI_type_id = kt.KPI_type_id
    JOIN group_use_kpis g ON kt.Group_ID = g.Group_ID
    WHERE 1
";

if (!empty($Academic)) {
    $sql .= " AND kt.Academic LIKE '%$Academic%'";
}
if (!empty($Group_ID)) {
    $sql .= " AND kt.Group_ID = '$Group_ID'";
}
if (!empty($KPI_type_id)) {
    $sql .= " AND kt.KPI_type_id = '$KPI_type_id'";
}

$sql .= " ORDER BY kt.Academic DESC, st.subject_order ASC";

$result = ($isSearch) ? mysqli_query($conn, $sql) : false;
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="css/indicators.css">

<div class="content-wrapper">
    <div class="page-header">
        <h1>หัวข้อตัวชี้วัดทั้งหมด</h1>
        <a href="create_subject.php" class="add-indicator-btn">
            <i class="fas fa-plus-circle"></i>
            <span>เพิ่มหัวข้อตัวชี้วัด</span>
        </a>
    </div>

    <div class="search-container">
        <form method="POST" class="search-form">
            <div class="form-group">
                <label for="Academic">ค้นหาตามปีการศึกษา</label>
                <input type="text" id="Academic" name="Academic" value="<?php echo htmlspecialchars($Academic); ?>" placeholder="ระบุปีการศึกษา">
            </div>

            <div class="form-group">
                <label for="group_use_kpis">กลุ่มผู้ใช้ตัวชี้วัด</label>
                <select id="group_use_kpis" name="group_use_kpis">
                    <option value="">ทั้งหมด</option>
                    <?php
                    $sql_group = "SELECT Group_ID, Group_Name FROM group_use_kpis ORDER BY Group_Name ASC";
                    $res_group = mysqli_query($conn, $sql_group);
                    if ($res_group) {
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
                <select id="kpi_type" name="kpi_type">
                    <option value="">ทั้งหมด</option>
                    <?php 
                    if (!empty($Group_ID)) {
                        $sql_t = "SELECT KPI_type_id, KPI_Type_Name_EN, Academic FROM kpi_type WHERE Group_ID = '$Group_ID' ";
                        if(!empty($Academic)){
                            $sql_t .= " AND Academic = '$Academic' ";
                        }
                        $sql_t .= " ORDER BY Academic DESC, Order_No ASC";
                        
                        $res_t = mysqli_query($conn, $sql_t);
                        while($row_t = mysqli_fetch_assoc($res_t)){
                            $sel = ($KPI_type_id == $row_t['KPI_type_id']) ? 'selected' : '';
                            echo "<option value='{$row_t['KPI_type_id']}' $sel>{$row_t['KPI_Type_Name_EN']}</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <button type="submit" class="search-button">ค้นหา</button>
        </form>
    </div>

    <div class="table-container">
        <table class="indicator-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ลำดับ</th>
                    <th class="subject-name-col">ชื่อหัวข้อตัวชี้วัด</th>
                    <th>ประเภทตัวชี้วัด</th>
                    <th>กลุ่มผู้ใช้</th>
                    <th>ปีการศึกษา</th>
                    <th class="text-center action-header">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $current_year = $current_academic_year;

                if($isSearch && $result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        if ($row['Academic'] == $current_year) {
                            $delete_btn = "<a href='config/checkdelete_subject.php?subject_id={$row['subject_id']}' class='action-btn btn-delete' 
                                           onclick='return confirm(\"คุณแน่ใจหรือว่าต้องการลบข้อมูลนี้?\");'>ลบ</a>";
                        } else {
                            $delete_btn = "<a href='javascript:void(0);' class='action-btn btn-delete' 
                                           style='background-color: #cccccc; cursor: not-allowed; opacity: 0.6;' 
                                           title='ไม่สามารถลบข้อมูลย้อนหลังได้' onclick='return false;'>ลบ</a>";
                        }

                        echo "<tr>
                                <td>{$row['subject_order']}</td>
                                <td>{$row['subject_name']}</td>
                                <td>{$row['KPI_Type_Name_EN']}</td>
                                <td>{$row['Group_Name']}</td>
                                <td>{$row['Academic']}</td>
                                
                                <td class='text-center action-cell'>
                                    <div class='action-buttons-wrapper'>
                                        <a href='edit_subject.php?subject_id={$row['subject_id']}' class='action-btn btn-edit'>แก้ไข</a>
                                        {$delete_btn}
                                    </div>
                                </td>
                              </tr>";
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const groupSelect = document.getElementById('group_use_kpis');
    const academicInput = document.getElementById('Academic');
    const kpiTypeSelect = document.getElementById('kpi_type');
    const currentSelectedKpi = '<?php echo $KPI_type_id; ?>';

    function loadKpiTypes() {
        const groupId = groupSelect.value;
        const academic = academicInput.value;
        if (!groupId) {
            kpiTypeSelect.innerHTML = '<option value="">ทั้งหมด</option>';
            return;
        }
        fetch(`subject_topic.php?ajax_request=get_kpi_types&group_id=${groupId}&academic=${academic}`)
            .then(response => response.json())
            .then(data => {
                let options = '<option value="">ทั้งหมด</option>';
                data.forEach(item => {
                    const isSelected = (item.KPI_type_id == currentSelectedKpi) ? 'selected' : '';
                    options += `<option value="${item.KPI_type_id}" ${isSelected}>${item.KPI_Type_Name_EN}</option>`;
                });
                kpiTypeSelect.innerHTML = options;
            })
            .catch(error => console.error('Error loading KPI types:', error));
    }
    groupSelect.addEventListener('change', loadKpiTypes);
    academicInput.addEventListener('input', loadKpiTypes);
});
</script>

<?php include 'templates/footer.php'; ?>