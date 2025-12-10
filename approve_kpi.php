<?php
$page_title = "อนุมัติผลงาน";
include 'templates/navbar.php';     
include 'config/auth_superadmin.php'; 
include 'config/conn.php';         
include 'config/search_approve.php'; 


$current_year_ad = date('Y');
$current_academic_year = intval($current_year_ad) + 543; 

?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="css/manage_users.css"> 


<div class="content-wrapper">
    <div class="page-header">
        <h1>อนุมัติผลงานบุคลากร</h1>
    </div>

    <div class="search-container" style="margin-bottom: 20px;">
        <form class="search-form layout-4col" method="GET">
            
            <div class="form-group">
                <label for="searchName">ชื่อ</label>
                <input type="text" id="searchName" name="searchName" placeholder="ค้นหาชื่อ..." 
                       value="<?php echo htmlspecialchars($search_name); ?>">
            </div>

            <div class="form-group">
                <label for="userType">ประเภทผู้ใช้</label>
                <select id="userType" name="userType">
                    <option value="all">ทั้งหมด</option>
                    <?php
                    if ($result_user_types && mysqli_num_rows($result_user_types) > 0) {
                        mysqli_data_seek($result_user_types, 0); 
                        while ($type = mysqli_fetch_assoc($result_user_types)) {
                            $selected = ($filter_user_type == $type['Type_id']) ? 'selected' : '';
                            echo '<option value="' . $type['Type_id'] . '" ' . $selected . '>' . htmlspecialchars($type['Type_name_th']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="department">สาขา</label>
                <select id="department" name="department">
                    <option value="all">ทั้งหมด</option>
                     <?php
                    if ($result_departments && mysqli_num_rows($result_departments) > 0) {
                         mysqli_data_seek($result_departments, 0); 
                        while ($dept = mysqli_fetch_assoc($result_departments)) {
                            $selected = ($filter_department == $dept['Department_id']) ? 'selected' : '';
                            echo '<option value="' . $dept['Department_id'] . '" ' . $selected . '>' . htmlspecialchars($dept['Department_name']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="approvalStatus">สถานะการอนุมัติ</label>
                <select id="approvalStatus" name="status">
                    <option value="not_approved" <?php echo ($filter_status == 'not_approved') ? 'selected' : ''; ?>>
                        ยังไม่อนุมัติ
                    </option>
                    <option value="approved" <?php echo ($filter_status == 'approved') ? 'selected' : ''; ?>>
                        อนุมัติแล้ว
                    </option>
                </select>
            </div>

            <button type="submit" class="search-button" name="search_button">ค้นหา</button>
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
if (isset($search_error_message)) {
     echo "<tr><td colspan='5' class='text-center'>" . htmlspecialchars($search_error_message) . "</td></tr>";
}
elseif ($result_employee === null) {
    echo "<tr><td colspan='5' class='text-center'>กรุณากรอกข้อมูล แล้วกดปุ่มค้นหา</td></tr>";
} 
elseif ($result_employee && mysqli_num_rows($result_employee) > 0) {
    $count = 1; 
    while ($row = mysqli_fetch_assoc($result_employee)) {
        
        $group_id = $row['Group_ID'];
        $emp_code = $row['Emp_code'];
        $edit_link_href = '#'; 

      
        if ($group_id == 1) {
            $edit_link_href = 'individual_kpi.php?Emp_code=' . $emp_code . '&year=' . $current_academic_year . '&mode=edit';
        } elseif ($group_id == 2) {
            $edit_link_href = 'individualceo_kpi.php?Emp_code=' . $emp_code . '&year=' . $current_academic_year . '&mode=edit';
        }
        
        
        $emp_full_name = htmlspecialchars($row['Title_shortname'] . $row['Fname_th'] . " " . $row['Lname_th']);

        echo "<tr>";
        echo "<td>" . $count++ . "</td>";
        echo "<td>" . $emp_full_name . "</td>";
        echo "<td>" . htmlspecialchars($row['Type_name_th']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Department_name']) . "</td>";
        
        echo '<td class="text-center">';
        if ($filter_status == 'approved') {
            
            echo '<a href="config/process_approval.php?cancel_user_id=' . $emp_code . '&year=' . $current_academic_year . '" 
                     class="action-btn btn-cancel-approval" 
                     onclick="return confirm(\'คุณต้องการ *ยกเลิก* การอนุมัติผลงานของ ' . addslashes($emp_full_name) . ' (ปี ' . $current_academic_year . ') ใช่หรือไม่?\')">
                     <i class="fas fa-times-circle"></i> ยกเลิกการอนุมัติ
                  </a>';
        } else {
            echo '<a href="' . $edit_link_href . '" class="action-btn btn-edit">แก้ไข</a>';
            
            echo '<a href="config/process_approval.php?approve_user_id=' . $emp_code . '&year=' . $current_academic_year . '" 
                     class="action-btn btn-approve" 
                     onclick="return confirm(\'คุณต้องการอนุมัติผลงานของ ' . addslashes($emp_full_name) . '  ใช่หรือไม่?\')">อนุมัติ</a>';
        }
        
        echo '</td>';
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5' class='text-center'>ไม่พบข้อมูลตามเงื่อนไขที่ค้นหา</td></tr>";
}
?>
</tbody>
        </table>
    </div>
</div>

<?php include 'templates/footer.php'; ?>