<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config/conn.php';

// === [ 1. ดึงชื่อไฟล์ของหน้าปัจจุบัน ] ===
$current_page = basename($_SERVER['PHP_SELF']);
// === [ สิ้นสุดการเพิ่มเติม ] ===


// ตรวจสอบการ login และดึงข้อมูลผู้ใช้จากฐานข้อมูล
$user = null;
if(isset($_SESSION['Emp_code'])){
    $Emp_code = $_SESSION['Emp_code'];

    // ดึงข้อมูลเพื่อแสดงใน Navbar
    $sql = "SELECT e.Emp_code, e.Fname_th, e.Lname_th, t.Title_name, e.IMGname, ut.Type_name_th
            FROM employee e
            LEFT JOIN title t ON e.Title_id = t.Title_id
            LEFT JOIN user u ON e.Emp_code = u.Emp_code 
            LEFT JOIN user_type ut ON u.Type_id = ut.Type_id
            WHERE e.Emp_code = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $Emp_code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(!$result){
        die("Query Failed: " . mysqli_error($conn));
    }

    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt); // ปิด statement หลังจากใช้งานเสร็จ
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'KPI Management System'; ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="navbar">
    <div class="navbar-left">
        <img src="img/ICT.png" alt="Logo">

        <nav>
            <?php if (isset($_SESSION['Type_id'])): ?>
                <?php
                // === [ 2. ตรวจสอบ $current_page กับ href ของแต่ละลิงก์ ] ===
                switch ($_SESSION['Type_id']) {
                    case 1: // Admin Menu
                        // ตรวจสอบว่าหน้าปัจจุบันตรงกับกลุ่มลิงก์หรือไม่
                        $active_main = ($current_page == 'mainadmin.php') ? 'active-nav' : '';
                        $active_users = (in_array($current_page, ['manage_users.php', 'create_user.php', 'profile.php'])) ? 'active-nav' : '';
                        $active_kpi_type = (in_array($current_page, ['kpi_type.php', 'create_kpi_type.php', 'edit_kpi_type.php'])) ? 'active-nav' : '';
                        $active_indicators = (in_array($current_page, ['indicators.php', 'create_indicator.php', 'edit_indicator.php'])) ? 'active-nav' : '';
                        $active_toggle = (in_array($current_page, ['show_on_off.php', 'toggles_switch.php', 'edit_toggles_switch.php'])) ? 'active-nav' : '';

                        echo "<a href=\"mainadmin.php\" class=\"$active_main\">หน้าหลัก</a> |
                              <a href=\"manage_users.php\" class=\"$active_users\">บัญชีผู้ใช้</a> |
                              <a href=\"kpi_type.php\" class=\"$active_kpi_type\">ประเภทตัวชี้วัด</a> |
                              <a href=\"indicators.php\" class=\"$active_indicators\">ตัวชี้วัด</a> |
                              <a href=\"show_on_off.php\" class=\"$active_toggle\">เปิด-ปิดผลงาน</a>";
                        break;

                    case 2: // Superadmin Menu
                        $active_main = ($current_page == 'mainsuper.php') ? 'active-nav' : '';
                        $active_kpi_type = (in_array($current_page, ['kpi_type.php', 'create_kpi_type.php', 'edit_kpi_type.php'])) ? 'active-nav' : '';
                        $active_indicators = (in_array($current_page, ['indicators.php', 'create_indicator.php', 'edit_indicator.php'])) ? 'active-nav' : '';
                        $active_kpi_ceo = ($current_page == 'individualceo_kpi.php') ? 'active-nav' : '';
                        
                        // --- START: แก้ไขส่วนนี้ ---
                        $active_approve = ($current_page == 'approve_kpi.php') ? 'active-nav' : ''; // <-- เพิ่มบรรทัดนี้

                        echo "<a href=\"mainsuper.php\" class=\"$active_main\">หน้าหลัก</a> |
                              <a href=\"kpi_type.php\" class=\"$active_kpi_type\">ประเภทตัวชี้วัด</a> |
                              <a href=\"indicators.php\" class=\"$active_indicators\">ตัวชี้วัด</a> |
                              <a href=\"individualceo_kpi.php\" class=\"$active_kpi_ceo\">บันทึกผลการดำเนินงาน</a> |
                              <a href=\"approve_kpi.php\" class=\"$active_approve\">การอนุมัติผลงาน</a>"; // <-- แก้ไข href จาก #
                        // --- END: แก้ไขส่วนนี้ ---
                        break;  

                    case 3: // bachelor Menu
                        $active_main = ($current_page == 'main_bachelor.php') ? 'active-nav' : '';
                        $active_profile = ($current_page == 'profile.php') ? 'active-nav' : '';
                        $active_kpi = ($current_page == 'individual_kpi.php') ? 'active-nav' : '';

                        echo "<a href=\"main_bachelor.php\" class=\"$active_main\">หน้าหลัก</a> |
                              <a href=\"profile.php\" class=\"$active_profile\">บัญชีผู้ใช้</a> |
                              <a href=\"individual_kpi.php\" class=\"$active_kpi\">จัดการบันทึกผลงาน</a>";
                        break;

                    case 4: // Associate_Dean Menu
                        $active_main = ($current_page == 'main_associate_dean.php') ? 'active-nav' : ''; // ตามโค้ดเดิมที่ link ไป main_bachelor.php
                        $active_profile = ($current_page == 'profile.php') ? 'active-nav' : '';
                        $active_kpi_ceo = ($current_page == 'individualceo_kpi.php') ? 'active-nav' : '';

                        echo "<a href=\"main_associate_dean.php\" class=\"$active_main\">หน้าหลัก</a> |
                              <a href=\"profile.php\" class=\"$active_profile\">บัญชีผู้ใช้</a> |
                              <a href=\"individualceo_kpi.php\" class=\"$active_kpi_ceo\">จัดการบันทึกผลงาน</a>";
                        break;

                    case 5: // Head_of_Department Menu
                        $active_main = ($current_page == 'main_head_of_department.php') ? 'active-nav' : '';
                        $active_profile = ($current_page == 'profile.php') ? 'active-nav' : '';
                        $active_kpi_ceo = ($current_page == 'individualceo_kpi.php') ? 'active-nav' : '';

                        echo "<a href=\"main_head_of_department.php\" class=\"$active_main\">หน้าหลัก</a> |
                              <a href=\"profile.php\" class=\"$active_profile\">บัญชีผู้ใช้</a> |
                              <a href=\"individualceo_kpi.php\" class=\"$active_kpi_ceo\">บันทึกผลการดำเนินงาน</a>";
                        break;
                }
                ?>
            <?php endif; ?>
        </nav>
    </div>
    <div class="user-box">
        <a href="profile.php?Emp_code=<?php echo htmlspecialchars($Emp_code ?? ''); ?>">
        <?php // --- **[แก้ไข]** ส่วนแสดงรูปภาพ ---
            // ตรวจสอบว่ามีข้อมูลรูปภาพ (BLOB) หรือไม่
            if ($user && !empty($user['IMGname'])) {
                // แสดงรูปภาพจากข้อมูล BLOB ที่เข้ารหัส Base64
                echo '<img src="data:image/jpeg;base64,' . base64_encode($user['IMGname']) . '" alt="Profile Picture" style="cursor: pointer;">';
            } else {
                // แสดงรูปภาพเริ่มต้นถ้าไม่มีข้อมูลในฐานข้อมูล
                echo '<img src="img/profile.png" alt="Default Profile Picture" style="cursor: pointer;">';
            }
         
        ?>
        </a>
        <div>
            <strong>รหัสพนักงาน :</strong> <?php echo htmlspecialchars($user['Emp_code'] ?? ''); ?> <br>
            <strong>ชื่อ-สกุล :</strong> <?php echo htmlspecialchars($user['Fname_th'] ?? '') . " " . htmlspecialchars($user['Lname_th'] ?? ''); ?> <br>
            <strong>ตำแหน่ง :</strong> <?php echo htmlspecialchars($user['Type_name_th'] ?? ''); ?>
        </div>
        <button class="logout-btn" onclick="logout()">
            <img src="img/logout.png" alt="Logout">
        </button>
    </div>
</div>

</html>