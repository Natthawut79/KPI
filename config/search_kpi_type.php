<?php
include 'config/conn.php';

// รับค่าจาก GET
$searchAcademicYear = isset($_GET['searchAcademicYear']) ? trim($_GET['searchAcademicYear']) : '';
$userType = isset($_GET['userType']) ? trim($_GET['userType']) : '';
$isSearch = ($searchAcademicYear !== '' || $userType !== '');

// ✅ ดึงข้อมูล Group ทั้งหมดจาก group_use_kpis
$sql_user_type = "SELECT Group_ID, Group_Name FROM group_use_kpis ORDER BY Group_ID ASC";
$result_user_type = mysqli_query($conn, $sql_user_type);

// ค่าเริ่มต้น (ยังไม่ค้นหา)
$result_kpi = null;

if ($isSearch) {
    $whereClauses = [];

    // ถ้ามีการกรอกปีการศึกษา
    if ($searchAcademicYear !== '') {
        $whereClauses[] = "k.Academic LIKE '%$searchAcademicYear%'";
    }

    // ถ้ามีการเลือกกลุ่มผู้ใช้
    if ($userType !== '' && $userType !== 'all') {
        $whereClauses[] = "k.Group_ID = '$userType'";
    }

    // รวมเงื่อนไข
    $whereSQL = '';
    if (count($whereClauses) > 0) {
        $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);
    }

    // ✅ ดึงข้อมูลจาก kpi_type พร้อมชื่อกลุ่มจาก group_use_kpis
    $sql_kpi = "
        SELECT 
            k.KPI_type_id,
            k.KPI_Type_Name_EN,
            k.KPI_Type_Name_TH,
            k.Weight,
            k.Order_No,
            k.Description_text,
            k.Academic,
            g.Group_Name
        FROM kpi_type k
        JOIN group_use_kpis g ON k.Group_ID = g.Group_ID
        $whereSQL
        ORDER BY k.Order_No ASC
    ";

    $result_kpi = mysqli_query($conn, $sql_kpi);
}
?>