<?php

$filter_status = isset($_GET['status']) ? $_GET['status'] : 'not_approved'; 
$search_name = isset($_GET['searchName']) ? mysqli_real_escape_string($conn, $_GET['searchName']) : '';
$filter_user_type = isset($_GET['userType']) ? mysqli_real_escape_string($conn, $_GET['userType']) : 'all';
$filter_department = isset($_GET['department']) ? mysqli_real_escape_string($conn, $_GET['department']) : 'all';

// 2. Query ข้อมูลสำหรับ Dropdown ประเภทผู้ใช้ (ยกเว้น Type_id = 1)
$sql_user_types = "SELECT Type_id, Type_name, Type_name_th FROM user_type WHERE Type_id != 1 ORDER BY Type_id";
$result_user_types = mysqli_query($conn, $sql_user_types);

// 3. Query ข้อมูลสำหรับ Dropdown สาขา
$sql_departments = "SELECT Department_id, Department_name FROM department ORDER BY Department_name";
$result_departments = mysqli_query($conn, $sql_departments);

// 4. ตั้งค่าผลลัพธ์เริ่มต้นเป็น null
$result_employee = null; 
$search_error_message = null; // สำหรับเก็บข้อความ Error

// 5. ตรวจสอบว่ามีการกดปุ่ม "ค้นหา" หรือไม่
if (isset($_GET['search_button'])) {

    // 5.1 ดึงปีการศึกษาปัจจุบัน
    $current_year_ad = date('Y');
    $current_academic_year = intval($current_year_ad) + 543;

    // 5.2 SQL Query หลัก
    // [แก้ไข] เปลี่ยนเป็น INNER JOIN เพื่อดึงเฉพาะคนที่มีข้อมูลในปีปัจจุบัน และเชื่อมเงื่อนไขปีการศึกษาที่นี่เลย
    $sql = "SELECT e.Emp_code, t.Title_shortname, e.Fname_th, e.Lname_th, 
                   uty.Type_name, uty.Type_name_th, d.Department_name,
                   gkm.Group_ID
            FROM employee e
            LEFT JOIN title t ON e.Title_id = t.Title_id
            LEFT JOIN user u ON e.Emp_code = u.Emp_code
            LEFT JOIN user_type uty ON u.Type_id = uty.Type_id
            LEFT JOIN department d ON e.Department_id = d.Department_id
            LEFT JOIN group_kpi_mapping gkm ON u.Type_id = gkm.Type_id
            
            INNER JOIN individual_kpi ik ON e.Emp_code = ik.Emp_code AND ik.Academic = ?
            
            WHERE u.Type_id != 1"; // (ไม่แสดง Admin)

    // 5.3 สร้าง Array สำหรับเก็บค่าที่จะ Bind
    $bind_params_values = []; 
    $bind_params_values[] = $current_academic_year;

    // 5.4 Bind สถานะ
    // [แก้ไข] ปรับเงื่อนไขเช็คสถานะจากค่าในตารางโดยตรง
    if ($filter_status == 'approved') {
        $sql .= " AND ik.Approve_id = 2"; 
    } else {
        // กรณี not_approved คือสถานะที่ไม่ใช่ 2 (เช่น 1 = รออนุมัติ)
        $sql .= " AND ik.Approve_id != 2"; 
    }

    // 5.5 Bind ชื่อ
    if (!empty($search_name)) {
        $sql .= " AND e.Fname_th LIKE ?";
        $bind_params_values[] = $search_name . '%';
    }

    // 5.6 Bind ประเภทผู้ใช้
    if ($filter_user_type != 'all') {
        $sql .= " AND u.Type_id = ?";
        $bind_params_values[] = $filter_user_type;
    }

    // 5.7 Bind สาขา
    if ($filter_department != 'all') {
        $sql .= " AND d.Department_id = ?";
        $bind_params_values[] = $filter_department;
    }
            
    // Group By เพื่อไม่ให้ชื่อซ้ำกรณีมีหลาย KPI items
    $sql .= " GROUP BY e.Emp_code
              ORDER BY e.Fname_th ASC";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // 5.8 Bind พารามิเตอร์ทั้งหมด
        if (!empty($bind_params_values)) {
            mysqli_stmt_execute($stmt, $bind_params_values);
        } else {
            mysqli_stmt_execute($stmt);
        }
        
        $result_employee = mysqli_stmt_get_result($stmt); 
        mysqli_stmt_close($stmt);
        
    } else {
        // กรณี Query พัง
        $search_error_message = "เกิดข้อผิดพลาดในการเตรียม Query: " . mysqli_error($conn);
    }
}
// สิ้นสุด if (isset($_GET['search_button']))
?>