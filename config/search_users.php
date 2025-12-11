<?php
include 'conn.php';

// รับค่าจาก GET
$searchName = isset($_GET['searchName']) ? trim($_GET['searchName']) : '';
$userType   = isset($_GET['userType']) ? trim($_GET['userType']) : '';
$isSearch   = ($searchName !== '' || $userType !== ''); // ✅ เช็กว่ามีการค้นหาหรือไม่

// ดึงประเภทผู้ใช้ทั้งหมด
$sql_user_type = "SELECT Type_id, Type_name, Type_name_th FROM user_type ORDER BY Type_id ASC";
$result_user_type = mysqli_query($conn, $sql_user_type);

// ✅ ถ้าไม่มีการค้นหา → ไม่ต้อง query employee
$result_employee = null;

if ($isSearch) {
    $whereClauses = [];

    // ถ้ามีการกรอกชื่อ
    if ($searchName !== '') {
        // ✅ [แก้ไข] ค้นหาเฉพาะชื่อจริง และต้อง "ขึ้นต้นด้วย" คำนั้น
        $whereClauses[] = "e.Fname_th LIKE '$searchName%'";
    }

    // ถ้าเลือกประเภทที่ไม่ใช่ "ทั้งหมด"
    if ($userType !== '' && $userType !== 'all') {
        $whereClauses[] = "u.Type_id = '$userType'";
    }

    // ประกอบ WHERE ถ้ามีเงื่อนไข
    $whereSQL = '';
    if (count($whereClauses) > 0) {
        $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);
    }

    // ✅ แสดงข้อมูลตามเงื่อนไข (ทั้งหมด = ไม่กรองประเภท แต่ยังกรองชื่อ)
    $sql_employee = "
        SELECT e.Emp_code, t.Title_shortname, e.Fname_th, e.Lname_th, 
               d.Department_name, ut.Type_name, ut.Type_name_th
        FROM employee e
        JOIN department d ON e.Department_id = d.Department_id
        JOIN title t ON e.Title_id = t.Title_id
        JOIN user u ON e.Emp_code = u.Emp_code
        JOIN user_type ut ON u.Type_id = ut.Type_id
        $whereSQL
        ORDER BY e.Emp_code ASC
    ";
    $result_employee = mysqli_query($conn, $sql_employee);
}
?>