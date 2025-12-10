<?php
$page_title = "หน้าหลัก - ตัวชี้วัด";
include 'config/auth_superadmin.php';
include 'templates/navbar.php';
include 'config/conn.php';
if (!isset($_SESSION['Emp_code'])) {
    header('Location: login.php');
    exit;
}
$current_emp_code = $_SESSION['Emp_code'];
include 'config/search_mainsuper.php';

?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="css/manage_users.css">
<link rel="stylesheet" href="css/maindashboard.css">


<div class="content-wrapper">

    <div class="action-container">
        <a href="mainsuper.php?view=mine" class="action-box <?php echo $is_my_work ? 'active' : ''; ?>">
            <i class="fas fa-user-check"></i>
            ผลงานของฉัน
        </a>
        <a href="mainsuper.php?view=others" class="action-box <?php echo !$is_my_work ? 'active' : ''; ?>">
            <i class="fas fa-users-cog"></i>
            ผลงานของอาจารย์ในคณะ
        </a>
    </div>

    <div class="search-container">
        <form class="search-form layout-4col" method="GET">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">

            <div class="form-group">
                <label for="searchName">ค้นหาชื่อ</label>
                <input type="text" id="searchName" name="searchName" placeholder="ค้นหาชื่อ..."
                    value="<?php echo htmlspecialchars($searchName); ?>">
            </div>

            <div class="form-group">
                <label for="searchYear">ค้นหาปี (พ.ศ.)</label>
                <input type="text" id="searchYear" name="searchYear" placeholder="ค้นหาปี พ.ศ. ..."
                    value="<?php echo htmlspecialchars($searchYear); ?>">
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
                            echo '<option value="' . $type['Type_id'] . '" ' . $selected . '>' . htmlspecialchars($type['Type_name']) . '</option>';
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

            <button type="submit" class="search-button" name="search_button">ค้นหา</button>
        </form>
    </div>

    <h2 class="table-title">
        <?php echo $is_my_work ? 'รายการผลงานของฉัน (แบ่งตามปี)' : 'รายการผลงานของอาจารย์ในคณะ (แบ่งตามปี)'; ?>
    </h2>

    <div class="table-container">
        <table class="user-table">
            <thead>
                <tr>
                    <th>อาจารย์</th>
                    <th>ประเภทผู้ใช้</th>
                    <th>สาขาวิชา</th>
                    <th>ปีการประเมิน</th>
                    <th>สถานะ</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php

                if (isset($search_error_message)) {
                    echo "<tr><td colspan='6' class='text-center'>" . htmlspecialchars($search_error_message) . "</td></tr>";
                } elseif ($result_kpi_list === null) {
                    echo "<tr><td colspan='6' class='text-center'>กรุณากรอกข้อมูล แล้วกดปุ่มค้นหา</td></tr>";
                } elseif (mysqli_num_rows($result_kpi_list) > 0) {
                    while ($row = mysqli_fetch_assoc($result_kpi_list)) {

                        $group_id = $row['Group_ID'];
                        $emp_code = $row['Emp_code'];
                        $kpi_year = $row['kpi_year'];

                        $edit_link_href = '#';

                        if ($group_id == 1) {
                            $edit_link_href = 'individual_kpi.php?Emp_code=' . $emp_code . '&year=' . $kpi_year;
                        } elseif ($group_id == 2) {
                            $edit_link_href = 'individualceo_kpi.php?Emp_code=' . $emp_code . '&year=' . $kpi_year;
                        }
                        // --- แทรกโค้ดด้านล่างนี้ต่อจากบรรทัดด้านบน ---
                        $status_msg = "";
                        $status_color = "";

                        if ($row['Approve_id'] == 1) {
                            $status_msg = "ยังไม่รับการอนุมัติ";
                            $status_color = "color: #e0a800;"; // สีเหลืองเข้ม/ส้ม
                        } elseif ($row['Approve_id'] == 2) {
                            $status_msg = "ได้รับการอนุมัติ";
                            $status_color = "color: #28a745;"; // สีเขียว
                        } elseif ($row['Approve_id'] == 3) {
                            $status_msg = "รอการเเก้ไข";
                            $status_color = "color: #dc3545;"; // สีแดง
                        } else {
                            $status_msg = "-";
                        }

                    
                        // ------------------------------------------

                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['Title_shortname'] . $row['Fname_th'] . " " . $row['Lname_th']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['Type_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['Department_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['kpi_year']) . "</td>";
                        echo '<td style="' . $status_color . ' font-weight: bold;">' . $status_msg . '</td>';
                        

                        echo '<td class="text-center">
                            <a href="' . $edit_link_href . '" class="action-btn btn-edit">ดูรายละเอียด</a>
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