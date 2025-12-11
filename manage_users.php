<?php
$page_title = "จัดการบัญชีผู้ใช้";
include 'templates/navbar.php';
include 'config/search_users.php'; // ✅ ดึงข้อมูลจากไฟล์แยก
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="css/manage_users.css">

<div class="content-wrapper">
    <div class="page-header">
        <h1>บัญชีผู้ใช้</h1>
        <a href="create_user.php" class="add-user-btn">
            <i class="fas fa-plus-circle"></i> <span>เพิ่มบัญชีผู้ใช้</span>
        </a>
    </div>

    <div class="search-container">
        <form class="search-form" method="GET">
            <div class="form-group">
                <label for="searchName">ชื่อ-นามสกุล</label>
                <input type="text" id="searchName" name="searchName" placeholder="ค้นหา..." 
                       value="<?php echo htmlspecialchars($searchName); ?>">
            </div>

            <div class="form-group">
                <label for="userType">ประเภทผู้ใช้</label>
                <select id="userType" name="userType">
                    <option value="all" <?php echo ($userType == 'all') ? 'selected' : ''; ?>>ทั้งหมด</option>
                    <?php
                    if ($result_user_type && mysqli_num_rows($result_user_type) > 0) {
                        mysqli_data_seek($result_user_type, 0);
                        while ($type = mysqli_fetch_assoc($result_user_type)) {
                            $selected = ($userType == $type['Type_id']) ? 'selected' : '';
                            echo '<option value="' . $type['Type_id'] . '" ' . $selected . '>' . htmlspecialchars($type['Type_name_th']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <button type="submit" class="search-button">ค้นหา</button>
        </form>
    </div>

    <div class="table-container">
        <table class="user-table">
            <thead>
                <tr>
                    <th>ลำดับ</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>ประเภท</th>
                    <th>สาขา</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
<?php
if ($result_employee === null) {
    echo "<tr><td colspan='5' class='text-center'>กรุณากรอกข้อมูล แล้วกดปุ่มค้นหา</td></tr>";
} elseif (mysqli_num_rows($result_employee) > 0) {
    $count = 1;
    while ($row = mysqli_fetch_assoc($result_employee)) {
        echo "<tr>";
        echo "<td>" . $count++ . "</td>";
        echo "<td>" . $row['Title_shortname'] . $row['Fname_th'] . " " . $row['Lname_th'] . "</td>";
        echo "<td>" . $row['Type_name_th'] . "</td>";
        echo "<td>" . $row['Department_name'] . "</td>";
        echo '<td class="text-center">
                <a href="profile.php?Emp_code=' . $row['Emp_code'] . '" class="action-btn btn-edit">แก้ไข</a>
                <a href="config/checkdelete_user.php?Emp_code=' . $row['Emp_code'] . '" class="action-btn btn-delete" onclick="return confirm(\'คุณแน่ใจหรือไม่ว่าต้องการลบ?\')">ลบ</a>
              </td>';
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5' class='text-center'>ไม่พบข้อมูลที่ค้นหา</td></tr>";
}
?>
</tbody>
        </table>
    </div>
</div>

<?php include 'templates/footer.php'; ?>