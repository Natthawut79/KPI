<?php
$page_title = "หน้าหลัก"; // เปลี่ยน Title
include 'templates/navbar.php';
include 'config/conn.php';

// 1. ตรวจสอบสิทธิ์
if (!isset($_SESSION['Emp_code'])) {
    header('Location: login.php');
    exit;
}
$current_emp_code = $_SESSION['Emp_code'];

// 2. ✅ [แก้ไข] เรียกใช้ไฟล์ Logic ใหม่
include 'config/search_bachelor.php';
// (ไฟล์นี้จะสร้างตัวแปร: $searchYear, $result_kpi_list, $search_error_message)
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="css/manage_users.css">
<link rel="stylesheet" href="css/maindashboard.css">


<div class="content-wrapper">

    <div class="search-container">
        <form class="search-form layout-1col" method="GET">

            <div class="form-group">
                <label for="searchYear">ค้นหาปี (พ.ศ.)</label>
                <input type="text" id="searchYear" name="searchYear" placeholder="ค้นหาปี พ.ศ. ..."
                    value="<?php echo htmlspecialchars($searchYear); ?>">
            </div>

            <button type="submit" class="search-button" name="search_button">ค้นหา</button>
        </form>
    </div>

    <h2 class="table-title">
        รายการผลงานของฉัน (แบ่งตามปี)
    </h2>

    <div class="table-container">
        <table class="user-table">
            <thead>
                <tr>
                    <th class='text-center'>อาจารย์</th>
                    <th class='text-center'>ประเภทผู้ใช้</th>
                    <th class='text-center'>สาขาวิชา</th>
                    <th class='text-center'>ปีการประเมิน</th>
                    <th class='text-center'>สถานะ</th>
                    <th class='text-center'>ดาวน์โหลด</th>
                    <th class='text-center' >จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // 13. ตรรกะการแสดงผล
                if (isset($search_error_message)) {
                    echo "<tr><td colspan='7' class='text-center'>" . htmlspecialchars($search_error_message) . "</td></tr>";
                } elseif ($result_kpi_list === null) {
                    echo "<tr><td colspan='7' class='text-center'>กรุณากรอกข้อมูล แล้วกดปุ่มค้นหา</td></tr>";
                } elseif (mysqli_num_rows($result_kpi_list) > 0) {
                    while ($row = mysqli_fetch_assoc($result_kpi_list)) {

                        $group_id = $row['Group_ID'];
                        $emp_code = $row['Emp_code'];
                        $kpi_year = $row['kpi_year'];
                        $edit_link_href = '#';

                        if ($group_id == 1) {
                            $edit_link_href = 'individual_kpi.php?Emp_code=' . $emp_code . '&year=' . $kpi_year;
                            $export_script = 'export_kpi.php';
                        } elseif ($group_id == 2) {
                            $edit_link_href = 'individualceo_kpi.php?Emp_code=' . $emp_code . '&year=' . $kpi_year;
                            $export_script = 'export_kpi2.php';
                        }
                        

                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['Title_shortname'] . $row['Fname_th'] . " " . $row['Lname_th']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['Type_name_th']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['Department_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['kpi_year']) . "</td>";
                       
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

                        // --- สร้างปุ่ม Export (แยก logic ออกมา) ---
                        $export_btn_html = "";
                        if ($row['Approve_id'] == 2) {
                            // ปุ่มสีเขียว (Export ได้)
                            $export_link = $export_script . '?Emp_code=' . $emp_code . '&year=' . $kpi_year;
                            $export_btn_html = '<a href="' . $export_link . '" target="_blank" style="display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: normal; transition: background-color 0.3s;"><i class="fas fa-file-excel"></i> Export</a>';
                        } else {
                            // ปุ่มสีเทา (Disabled)
                            $export_btn_html = '<span style="display: inline-block; padding: 10px 20px; background-color: #6c757d; color: white; border-radius: 4px; font-size: 12px; cursor: not-allowed; opacity: 0.6; font-weight: normal;"><i class="fas fa-file-excel"></i> Export</span>';
                        }
                    

                        echo '<td class="text-center" style="' . $status_color . ' font-weight: bold;">' . $status_msg . '</td>';

                        // แสดงปุ่ม Export ในช่องใหม่ (Download)
                        echo '<td class="text-center">' . $export_btn_html . '</td>';
                        
                        echo '<td class="text-center">
                            <a href="' . $edit_link_href . '" class="action-btn btn-edit">ดูรายละเอียด</a>
                          </td>';
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