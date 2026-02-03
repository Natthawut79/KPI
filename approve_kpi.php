<?php
$page_title = "อนุมัติผลงาน";
include 'templates/navbar.php';     
include 'config/auth_superadmin.php'; 
include 'config/conn.php';         
include 'config/search_approve.php'; 
include 'config/academic_year_resolver.php';
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
                    <?php
                    if ($result_status_list && mysqli_num_rows($result_status_list) > 0) {
                        mysqli_data_seek($result_status_list, 0);
                        while ($status_row = mysqli_fetch_assoc($result_status_list)) {
                            $selected = ($filter_status == $status_row['Approve_id']) ? 'selected' : '';
                            echo '<option value="' . $status_row['Approve_id'] . '" ' . $selected . '>' . 
                                 htmlspecialchars($status_row['Status_approve_name']) . 
                                 '</option>';
                        }
                    } else {
                        echo '<option value="1">รอตรวจสอบ</option>';
                    }
                    ?>
                </select>
            </div>

            <button type="submit" class="search-button" name="search_button">ค้นหา</button>
        </form>
    </div>

    <div class="table-container">
        <table class="user-table approve-table">
            <thead>
                <tr>
                    <th>ลำดับ</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>ประเภท</th>
                    <th>สาขา</th>
                    <th class="text-center">สถานะ</th>
                    
                    <?php if ($filter_status == '2'): ?>
                        <th class="text-center">ดาวน์โหลด</th>
                    <?php endif; ?>
                    
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
<?php
if (isset($search_error_message)) {
     echo "<tr><td colspan='7' class='text-center'>" . htmlspecialchars($search_error_message) . "</td></tr>";
}
elseif ($result_employee === null) {
    echo "<tr><td colspan='7' class='text-center'>กรุณากรอกข้อมูล แล้วกดปุ่มค้นหา</td></tr>";
} 
elseif ($result_employee && mysqli_num_rows($result_employee) > 0) {
    $count = 1; 
    while ($row = mysqli_fetch_assoc($result_employee)) {
        
        $group_id = $row['Group_ID'];
        $emp_code = $row['Emp_code'];
        $emp_full_name = htmlspecialchars($row['Title_shortname'] . $row['Fname_th'] . " " . $row['Lname_th']);
        $target_page = ($group_id == 2) ? 'individualceo_kpi.php' : 'individual_kpi.php';

        echo "<tr>";
        echo "<td>" . $count++ . "</td>";
        echo "<td>" . $emp_full_name . "</td>";
        echo "<td>" . htmlspecialchars($row['Type_name_th']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Department_name']) . "</td>";
        
        // --- ส่วนแสดงสถานะ (Status Color) ---
        $status_msg = !empty($row['Status_approve_name']) ? $row['Status_approve_name'] : "-";
        $status_color = "";
        
        if ($row['Approve_id'] == 1) {
            $status_color = "color: #e0a800;";
        } elseif ($row['Approve_id'] == 2) {
            $status_color = "color: #28a745;";
        } elseif ($row['Approve_id'] == 3) {
            $status_color = "color: #dc3545;";
        }
        echo '<td class="text-center" style="' . $status_color . ' font-weight: bold;">' . $status_msg . '</td>';

        if ($filter_status == '2') {
            
            $export_script = 'new_export.php';
            $export_link = $export_script . '?Emp_code=' . $emp_code . '&year=' . $current_academic_year;
            
            echo '<td class="text-center">';
            echo '<a href="' . $export_link . '" target="_blank" class="action-btn" style="background-color: #28a745; color: white; display:inline-block; padding: 6px 12px; text-decoration: none; border-radius: 4px;"><i class="fas fa-file-excel"></i> Export</a>';
            echo '</td>';

            echo '<td class="text-center">';
            $view_link = $target_page . '?Emp_code=' . $emp_code . '&year=' . $current_academic_year;
            echo '<a href="' . $view_link . '" class="action-btn btn-edit" style="margin-right: 5px;">ดูรายละเอียด</a>';

            // ปุ่มยกเลิกอนุมัติ
            echo '<a href="config/process_approval.php?cancel_user_id=' . $emp_code . '&year=' . $current_academic_year . '" 
                     class="action-btn btn-cancel-approval" 
                     style="background-color: #dc3545; color: white;"
                     onclick="return confirm(\'คุณต้องการ *ยกเลิก* การอนุมัติผลงานของ ' . addslashes($emp_full_name) . ' ใช่หรือไม่?\')">
                     <i class="fas fa-times-circle"></i> ยกเลิกอนุมัติ
                  </a>';
            echo '</td>';

        } else {
            echo '<td class="text-center">';
            
            // ปุ่มแก้ไข (มี mode=edit -> แก้ไขได้)
            $edit_link = $target_page . '?Emp_code=' . $emp_code . '&year=' . $current_academic_year . '&mode=edit';
            echo '<a href="' . $edit_link . '" class="action-btn btn-edit" style="margin-right: 5px;">แก้ไข</a>';
            
            // ปุ่มอนุมัติ
            echo '<a href="config/process_approval.php?approve_user_id=' . $emp_code . '&year=' . $current_academic_year . '" 
                     class="action-btn btn-approve" 
                     onclick="return confirm(\'คุณต้องการอนุมัติผลงานของ ' . addslashes($emp_full_name) . '  ใช่หรือไม่?\')">อนุมัติ</a>';
            echo '</td>';
        }
        
        echo "</tr>";
    }
} else {
    $colspan = ($filter_status == '2') ? 7 : 6;
    echo "<tr><td colspan='" . $colspan . "' class='text-center'>ไม่พบข้อมูลตามเงื่อนไขที่ค้นหา</td></tr>";
}
?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'templates/footer.php'; ?>