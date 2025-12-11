<?php
$page_title = "ประเภทตัวชี้วัด";
include 'templates/navbar.php';
include 'config/search_kpi_type.php';
include 'config/academic_year_resolver.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="css/kpi_type.css">


<div class="content-wrapper">
    <div class="page-header">
        <h1>ประเภทตัวชี้วัด</h1>
        <a href="create_kpi_type.php" class="add-kpi-btn">
            <i class="fas fa-plus-circle"></i>
            <span>เพิ่มประเภทตัวชี้วัด</span>
        </a>
    </div>

    <div class="search-container">
        <form class="search-form" method="GET">
            <div class="form-group">
                <label for="searchAcademicYear">ค้นหาตามปีการศึกษา</label>
                <input type="text" id="searchAcademicYear" name="searchAcademicYear"
                       placeholder="ปีการศึกษา..." value="<?php echo htmlspecialchars($searchAcademicYear); ?>">
            </div>

            <div class="form-group">
    <label for="userType">กลุ่มผู้ใช้ประเภทตัวชี้วัด</label>
    <select id="userType" name="userType">
        <option value="all" <?php echo ($userType == 'all') ? 'selected' : ''; ?>>ทั้งหมด</option>
        <?php
        if ($result_user_type && mysqli_num_rows($result_user_type) > 0) {
            mysqli_data_seek($result_user_type, 0);
            while ($type = mysqli_fetch_assoc($result_user_type)) {
                $selected = ($userType == $type['Group_ID']) ? 'selected' : '';
                echo '<option value="' . $type['Group_ID'] . '" ' . $selected . '>' . htmlspecialchars($type['Group_Name']) . '</option>';
            }
        }
        ?>
    </select>
</div>



            <button type="submit" class="search-button">ค้นหา</button>
        </form>
    </div>

    <div class="table-container">
        <table class="kpi-table">
            <thead>
                <tr>
                    <th>ชื่อประเภทตัวชี้วัด (ENG)</th>
                    <th>ชื่อประเภทตัวชี้วัด (TH)</th>
                    <th>ค่าน้ำหนัก</th>
                    <th>ลำดับที่</th>
                    <th>หมายเหตุ</th>
                    <th>ปีการศึกษา</th>
                    <th>กลุ่มผู้ใช้</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
<?php
// [เพิ่ม] กำหนดปีปัจจุบัน (พ.ศ.) ไว้นอก Loop หรือใน Loop ก็ได้
$current_year = $current_academic_year;

if ($result_kpi === null) {
    echo "<tr><td colspan='8' class='text-center'>กรุณากรอกข้อมูล แล้วกดปุ่มค้นหา</td></tr>";
} elseif (mysqli_num_rows($result_kpi) > 0) {
    while ($row = mysqli_fetch_assoc($result_kpi)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['KPI_Type_Name_EN']) . "</td>";
        echo "<td>" . htmlspecialchars($row['KPI_Type_Name_TH']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Weight']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Order_No']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Description_text']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Academic']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Group_Name']) . "</td>";
        
        echo '<td class="text-center">';

        // ปุ่มแก้ไข (แสดงปกติ)
        echo '<a href="edit_kpi_type.php?KPI_type_id=' . $row['KPI_type_id'] . '" class="action-btn btn-edit">แก้ไข</a> ';
        
        // [แก้ไข] เงื่อนไขตรวจสอบปีการศึกษาสำหรับปุ่มลบ
        if ($row['Academic'] == $current_year) {
            // กรณีปีตรงกับปีปัจจุบัน -> แสดงปุ่มลบปกติ
            echo '<a href="config/checkdelete_kpi_type.php?KPI_type_id=' . $row['KPI_type_id'] . '" class="action-btn btn-delete"
                   onclick="return confirm(\'คุณแน่ใจหรือไม่ว่าต้องการลบ?\')">ลบ</a>';
        } else {
            // กรณีไม่ใช่ปีปัจจุบัน -> แสดงปุ่มลบแบบ Disable (สีเทา + กดไม่ได้)
            echo '<a href="#" class="action-btn btn-delete" 
                   style="background-color: #cccccc; cursor: not-allowed; pointer-events: none; opacity: 0.6;" 
                   title="ไม่สามารถลบข้อมูลย้อนหลังได้">ลบ</a>';
        }

        echo '</td>';
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='8' class='text-center'>ไม่พบข้อมูลที่ค้นหา</td></tr>";
}
?>
</tbody>

        </table>
    </div>
</div>

<?php include 'templates/footer.php'; ?>