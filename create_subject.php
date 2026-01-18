<?php
include 'config/conn.php';
include 'config/academic_year_resolver.php';

if (isset($_GET['get_next_order']) && isset($_GET['group_id']) && isset($_GET['academic'])) {
    $group_id = intval($_GET['group_id']);
    $academic = intval($_GET['academic']);
    $next_order = 1;

    // หาค่ามากสุด โดยอิงจาก Group_ID และ Academic
    $sql = "SELECT MAX(subject_order) AS max_order 
            FROM subject_topic 
            WHERE Group_ID = ? AND Academic = ?";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $group_id, $academic);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['max_order']) {
        $next_order = $row['max_order'] + 1;
    }
    
    mysqli_stmt_close($stmt);

    echo json_encode(['next_order' => $next_order]);
    exit();
}

if (isset($_GET['group_id'])) {
    $group_id = intval($_GET['group_id']);
    $academic = isset($_GET['academic']) ? intval($_GET['academic']) : $current_academic_year;

    $types = [];
    if ($group_id > 0) {
        $sql = "SELECT KPI_type_id, KPI_Type_Name_EN, KPI_Type_Name_TH 
                FROM kpi_type 
                WHERE Group_ID = ? AND Academic = ? 
                ORDER BY Order_No ASC";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $group_id, $academic);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $types[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
    
    header('Content-Type: application/json');
    echo json_encode($types);
    exit();
}

$page_title = "สร้างหัวข้อตัวชี้วัด";
include 'templates/navbar.php';
include 'config/conn.php';
include 'config/academic_year_resolver.php';
?>

<link rel="stylesheet" href="css/create_indicator.css">

<div class="main-container">
    <div class="form-wrapper">
        <h1 class="form-title">สร้างหัวข้อตัวชี้วัด</h1>

        <form action="config/checkcreate_subject.php" method="POST" onsubmit="return confirm('คุณต้องการบันทึกข้อมูลใช่หรือไม่?');">
            
            <div class="form-group">
                <label>ปีการศึกษา :</label>
                <input type="number" id="academic_year" name="Academic" 
                       value="<?php echo $current_academic_year; ?>" required>
            </div>

            <div class="form-group">
                <label>กลุ่มผู้ใช้ตัวชี้วัด :</label>
                
                <div class="radio-container">
                <?php
                    $sql_groups = "SELECT Group_ID, Group_Name FROM group_use_kpis ORDER BY Group_ID ASC";
                    $res_groups = mysqli_query($conn, $sql_groups);
                    $first = true;
                    while ($group = mysqli_fetch_assoc($res_groups)) {
                        $checked = $first ? 'checked' : ''; 
                        $first = false;
                        
                        // ใช้ class radio-label แทน inline style
                        echo "<label class='radio-label'>";
                        echo "<input type='radio' name='Group_ID' value='{$group['Group_ID']}' $checked required> ";
                        echo htmlspecialchars($group['Group_Name']);
                        echo "</label>";
                    }
                ?>
                </div>
            </div>

            <div class="form-group">
                <label>ชื่อประเภทตัวชี้วัด :</label>
                <select name="KPI_type_id" id="kpi_type_select" required>
                    <option value="">--- กรุณาเลือก ---</option>
                </select>
            </div>

            <div class="form-group">
                <label>ชื่อหัวข้อตัวชี้วัด :</label>
                <input type="text" name="subject_name" required placeholder="กรอกชื่อหัวข้อตัวชี้วัด...">
            </div>

            <div class="form-group">
                <label>ลำดับที่ :</label>
                <input type="number" name="subject_order" id="order_no_input" required>
            </div>

            <div class="button-container">
                <button type="submit" class="create-btn">สร้าง</button>
                <a href="subject_topic.php" class="cancel-btn">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>

<script src="js/subject_topic.js"></script>
<?php include 'templates/footer.php'; ?>