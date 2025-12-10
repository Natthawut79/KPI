<?php
$page_title = "เปิด-ปิดรอบการบันทึกผลงาน";
include 'templates/navbar.php';       // 1. เรียก Navbar
// (สมมติว่าหน้านี้สำหรับ Admin หรือ Super Admin)
include 'config/auth_admin.php'; // 2. ตรวจสอบสิทธิ์ (หรือใช้ auth_superadmin.php ถ้าต้องการ)
include 'config/conn.php';            // 3. เชื่อมต่อฐานข้อมูล

// 4. เรียกใช้ไฟล์ Logic ใหม่
include 'config/search_toggles.php';
// (ไฟล์นี้จะสร้างตัวแปร: $searchYear, $filter_submit_type, $result_submit_types,
// $result_toggles, $search_error_message)

// ฟังก์ชันแปลงวันที่เป็น พ.ศ.
function formatDateThai($date) {
    if (!$date || $date == '0000-00-00') {
        return '-';
    }
    $timestamp = strtotime($date);
    $year = date('Y', $timestamp) + 543; // บวก 543 ปี
    $month = date('m', $timestamp);
    $day = date('d', $timestamp);
    return $day . '/' . $month . '/' . $year;
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="css/manage_users.css"> 

<div class="content-wrapper">
    <div class="page-header">
        <h1>เปิด-ปิดรอบการบันทึกผลงาน</h1>
        <a href="toggles_switch.php" class="add-user-btn"> <i class="fas fa-plus-circle"></i> <span>เพิ่มรอบการบันทึกผลงาน</span>
        </a>
    </div>

    <div class="search-container" style="margin-bottom: 20px;">
        <form class="search-form layout-4col" method="GET">
            
            <div class="form-group">
                <label for="searchYear">ค้นหาปี (พ.ศ.)</label>
                <input type="text" id="searchYear" name="searchYear" placeholder="ค้นหาปี พ.ศ. ..." 
                       value="<?php echo htmlspecialchars($searchYear); ?>">
            </div>

            <div class="form-group">
                <label for="submitType">รอบการประเมิน</label>
                <select id="submitType" name="submitType">
                    <option value="all">ทั้งหมด</option>
                    <?php
                    // (ใช้ $result_submit_types จากไฟล์ logic)
                    if ($result_submit_types && mysqli_num_rows($result_submit_types) > 0) {
                        mysqli_data_seek($result_submit_types, 0); 
                        while ($type = mysqli_fetch_assoc($result_submit_types)) {
                            // ($type['Submit_type_id'] คือ 1 หรือ 2)
                            $selected = ($filter_submit_type == $type['Submit_type_id']) ? 'selected' : '';
                            echo '<option value="' . $type['Submit_type_id'] . '" ' . $selected . '>' . htmlspecialchars($type['Submit_type_name']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <button type="submit" class="search-button" name="search_button">ค้นหา</button>
        </form>
    </div>


    <div class="table-container">
        <table class="user-table">
            <thead>
                <tr>
                    <th>ปีการศึกษา</th>
                    <th>รอบการประเมิน</th>
                    <th>วันที่เริ่ม</th>
                    <th>วันที่สิ้นสุด</th>
                    <th>สถานะ</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
<?php
// 10. ตรรกะการแสดงผล
if (isset($search_error_message)) {
     echo "<tr><td colspan='6' class='text-center'>" . htmlspecialchars($search_error_message) . "</td></tr>";
}
elseif ($result_toggles === null) {
    echo "<tr><td colspan='6' class='text-center'>กรุณากรอกข้อมูล แล้วกดปุ่มค้นหา</td></tr>";
} 
elseif ($result_toggles && mysqli_num_rows($result_toggles) > 0) {
    while ($row = mysqli_fetch_assoc($result_toggles)) {
        
        // 10.1 กำหนดสไตล์ให้สถานะ
        $status_class = ($row['Status'] == 'เปิด') ? 'status-open' : 'status-closed';
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Academic']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Submit_type_name']) . "</td>";
        
        // แก้ไข: เรียกใช้ฟังก์ชัน formatDateThai เพื่อแสดงวันที่เป็น พ.ศ.
        echo "<td>" . htmlspecialchars(formatDateThai($row['Start_date'])) . "</td>";
        echo "<td>" . htmlspecialchars(formatDateThai($row['End_date'])) . "</td>";
        
        echo "<td class='{$status_class}'>" . htmlspecialchars($row['Status']) . "</td>";
        
        // 10.2 ปุ่มจัดการ (เหมือน indicators.php)
        echo '<td class="text-center">
                <a href="edit_toggle.php?id=' . $row['Toggles_id'] . '" class="action-btn btn-edit">แก้ไข</a>
                <a href="config/delete_toggle.php?id=' . $row['Toggles_id'] . '" class="action-btn btn-delete" 
                   onclick="return confirm(\'คุณแน่ใจหรือไม่ว่าต้องการลบ?\')">ลบ</a>
              </td>';
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6' class='text-center'>ไม่พบข้อมูลตามเงื่อนไขที่ค้นหา</td></tr>";
}
?>
</tbody>
        </table>
    </div>
</div>

<?php include 'templates/footer.php'; ?>