<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'vendor/autoload.php';
include 'config/conn.php';


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if (!isset($_SESSION['Emp_code']) && !isset($_GET['Emp_code'])) {
    die("Error: กรุณา Login");
}

$current_emp_code = '';
$current_academic_year = '';
$user_type_id = 0;
$viewed_user_data = null;

if (isset($_GET['Emp_code']) && !empty($_GET['Emp_code']) && isset($_GET['year']) && !empty($_GET['year'])) {
    $current_emp_code = $_GET['Emp_code'];
    $current_academic_year = $_GET['year'];

    $sql_get_user_type = "SELECT Type_id FROM user WHERE Emp_code = ? LIMIT 1";
    $stmt_get_type = $conn->prepare($sql_get_user_type);
    if ($stmt_get_type) {
        $stmt_get_type->bind_param("s", $current_emp_code);
        $stmt_get_type->execute();
        $result_get_type = $stmt_get_type->get_result();
        if ($row_type = $result_get_type->fetch_assoc()) {
            $user_type_id = $row_type['Type_id'];
        } else {
            die("Error: ไม่พบ User Type ของ Emp_code ที่ระบุ");
        }
        $stmt_get_type->close();
    } else {
        die("Error: ไม่สามารถเตรียมคำสั่งค้นหา Type_id ได้");
    }
} else {
    // กรณีดูของตัวเอง (ดึงจาก Session)
    $current_emp_code = $_SESSION['Emp_code'];
    $user_type_id = $_SESSION['Type_id'];
    
    // ถ้าไม่ได้ส่งปีมา ให้ใช้ปีปัจจุบัน
    if (isset($_GET['year']) && !empty($_GET['year'])) {
         $current_academic_year = $_GET['year'];
    } else {
        $current_year_ad = date('Y');
        $current_academic_year = intval($current_year_ad) + 543;
    }
}

// ดึงข้อมูลพนักงาน
$sql_get_user_info = "SELECT e.Emp_code, t.Title_name, e.Fname_th, e.Lname_th, d.Department_name
                      FROM employee e
                      LEFT JOIN title t ON e.Title_id = t.Title_id
                      LEFT JOIN department d ON e.Department_id = d.Department_id
                      WHERE e.Emp_code = ? LIMIT 1";

$stmt_get_info = $conn->prepare($sql_get_user_info);
$stmt_get_info->bind_param("s", $current_emp_code);
$stmt_get_info->execute();
$result_get_info = $stmt_get_info->get_result();
$viewed_user_data = $result_get_info->fetch_assoc();
$stmt_get_info->close();

if (!$viewed_user_data) {
    die("Error: ไม่พบข้อมูลพนักงานสำหรับ Emp_code: " . htmlspecialchars($current_emp_code));
}

// [START] ดึงข้อมูล Description Year สำหรับ Tab 1 Footer
$footer_description = "";
$sql_desc_year = "SELECT description FROM description_year WHERE Academic = ? LIMIT 1";
$stmt_desc = $conn->prepare($sql_desc_year);
if ($stmt_desc) {
    $stmt_desc->bind_param("s", $current_academic_year);
    $stmt_desc->execute();
    $res_desc = $stmt_desc->get_result();
    if ($row_desc = $res_desc->fetch_assoc()) {
        $footer_description = $row_desc['description'];
    }
    $stmt_desc->close();
}
// [END] Description Year

$kpi_data = []; 
$all_topic_ids_flat = [];
$topic_details_for_calc = [];
$total_kpi_type_weight = 0;

$sql_kpi_types = "SELECT
                    kt.KPI_type_id,
                    kt.KPI_Type_Name_TH,
                    kt.KPI_Type_Name_EN,
                    kt.Weight AS TypeWeight,
                    kt.Order_No
                  FROM kpi_type kt
                  JOIN group_kpi_mapping gkm ON kt.Group_ID = gkm.Group_ID
                  WHERE gkm.Type_id = ? AND kt.Academic = ?
                  ORDER BY kt.Order_No ASC";

$stmt_kpi_types = $conn->prepare($sql_kpi_types);
$stmt_kpi_types->bind_param("ii", $user_type_id, $current_academic_year);
$stmt_kpi_types->execute();
$result_kpi_types = $stmt_kpi_types->get_result();

if ($result_kpi_types) {
    while ($type_row = $result_kpi_types->fetch_assoc()) {
        $total_kpi_type_weight += $type_row['TypeWeight']; 
        $current_kpi_type_id = $type_row['KPI_type_id'];
        $topics = [];

        $sql_topics = "SELECT
                        kt.KPI_topic_id,
                        CONCAT(ktype.Order_no, '.', kt.Order_no) as id,
                        kt.KPI_topic_name as name,
                        kt.Unit as unit,
                        kt.Score_criteria as criteria,
                        kt.Weight as weight,
                        kt.Goal as target,
                        kt.Important_level_no,
                        kt.Additional as Additional
                      FROM kpi_topic kt
                      JOIN kpi_type ktype ON kt.KPI_type_id = ktype.KPI_type_id
                      WHERE kt.KPI_type_id = ?
                      ORDER BY kt.Order_no ASC";

        $stmt_topics = $conn->prepare($sql_topics);
        $stmt_topics->bind_param("i", $current_kpi_type_id);
        $stmt_topics->execute();
        $result_topics = $stmt_topics->get_result();

        if ($result_topics) {
            while ($topic_row = $result_topics->fetch_assoc()) {
                $topic_id = $topic_row['KPI_topic_id'];
                $topics[$topic_id] = $topic_row;
                $all_topic_ids_flat[] = $topic_id;
                $topic_details_for_calc[$topic_id] = [
                    'weight' => $topic_row['weight'],
                    'kpi_type_id' => $current_kpi_type_id
                ];
            }
        }
        $stmt_topics->close();

        $type_row['topics'] = $topics;
        $kpi_data[$current_kpi_type_id] = $type_row;
    }
}
$stmt_kpi_types->close();
$all_topic_ids_flat = array_unique($all_topic_ids_flat);


$saved_review_data = [];

if (!empty($all_topic_ids_flat)) {
   
    $topic_ids_placeholder = implode(',', array_fill(0, count($all_topic_ids_flat), '?'));

    $sql_saved_review = "SELECT KPI_topic_id, Submit_type_id, Goal_job, Actual_work, score, total_score, Actual_work_all_year, File_path, File_url, Additional, Advice
                         FROM individual_kpi
                         WHERE Emp_code = ? AND Academic = ? AND KPI_topic_id IN ($topic_ids_placeholder)";

    $stmt_saved_review = $conn->prepare($sql_saved_review);

    if ($stmt_saved_review) {
        $types = "si" . str_repeat('i', count($all_topic_ids_flat));
        $params = array_merge([$current_emp_code, $current_academic_year], $all_topic_ids_flat);
        
        $stmt_saved_review->bind_param($types, ...$params);
        $stmt_saved_review->execute();
        $result_saved_review = $stmt_saved_review->get_result();
        
        if ($result_saved_review) {
            while ($row_saved = $result_saved_review->fetch_assoc()) {
                $key = $row_saved['KPI_topic_id'] . "_" . $row_saved['Submit_type_id'];
                $saved_review_data[$key] = $row_saved;
            }
        }
        $stmt_saved_review->close();
    } else {
        die("Error: ไม่สามารถเตรียมคำสั่งดึงข้อมูล individual_kpi ได้: " . $conn->error);
    }
}


$saved_category_scores = [];
$sql_cat_scores = "SELECT KPI_type_id, Category_score
                   FROM score_evaluation
                   WHERE Emp_code = ? AND Academic = ? AND Submit_type_id = 2";
$stmt_cat_scores = $conn->prepare($sql_cat_scores);
if ($stmt_cat_scores) {
    $stmt_cat_scores->bind_param("si", $current_emp_code, $current_academic_year);
    $stmt_cat_scores->execute();
    $res_cat_scores = $stmt_cat_scores->get_result();
    if ($res_cat_scores) {
        while ($cat_row = $res_cat_scores->fetch_assoc()) {
            $saved_category_scores[$cat_row['KPI_type_id']] = $cat_row['Category_score'];
        }
    }
    $stmt_cat_scores->close();
} else {
     die("Error: ไม่สามารถเตรียมคำสั่งดึงข้อมูล score_evaluation ได้: " . $conn->error);
}

$saved_total_scores = null;
$sql_total_scores = "SELECT Score_500_max, Score_100_max
                     FROM total_score_evaluation 
                     WHERE Emp_code = ? AND Academic = ? AND Submit_type_id = 2 
                     LIMIT 1";
$stmt_total_scores = $conn->prepare($sql_total_scores);
if ($stmt_total_scores) {
    $stmt_total_scores->bind_param("si", $current_emp_code, $current_academic_year);
    $stmt_total_scores->execute();
    $res_total_scores = $stmt_total_scores->get_result();
    if ($res_total_scores) {
        $saved_total_scores = $res_total_scores->fetch_assoc();
    }
    $stmt_total_scores->close();
} else {
    die("Error: ไม่สามารถเตรียมคำสั่งดึงข้อมูล total_score_evaluation ได้: " . $conn->error);
}

$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
    ->setCreator("KPI System")
    ->setTitle("KPI Report " . $current_emp_code . " ปี " . $current_academic_year)
    ->setSubject("KPI Report");

// ตั้งค่า Font เริ่มต้น
$spreadsheet->getDefaultStyle()->getFont()->setName('TH Sarabun New')->setSize(16);

//  กำหนด Style Arrays
$style_title_array = [
    'font' => ['bold' => true, 'size' => 18],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
];

$header_style_array = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']]
];

$tab1_header_style_array = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FF000000']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2EFDA']] // สีเขียวอ่อน
];

$tab3_header_style_array = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], // White text
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4F81BD']] // Dark Blue
];

$style_align_center_array = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
];

$style_all_borders_array = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$style_align_wrap_text_array = [
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_LEFT,
        'vertical' => Alignment::VERTICAL_TOP,
        'wrapText' => true
    ]
];

$style_plan_header_array = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']]
];

$style_grand_total_label_array = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER]
];

$style_grand_total_value_array = [
    'font' => ['bold' => true, 'size' => 18],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$style_plan_label_array = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_TOP],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

// Styles for Rating Table (Tab 2)
$style_rating_title_orange = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 18],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF79646']] // Orange
];

$style_rating_subtitle_blue = [
    'font' => ['bold' => true, 'size' => 18],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDAEEF3']] // Light Blue
];

$style_rating_box_grey = [
    'font' => ['size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']] // Light Grey
];

// Styles for Tab 2 Header (Beige/Yellow)
$style_sig_header = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0E5D1']] // Beige
];
$style_sig_label_yellow = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF7CC']] // Light Yellow
];
$style_sig_box_yellow = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF7CC']] // Light Yellow
];
$style_review_label_yellow = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF7CC']] // Light Yellow
];
$style_review_box_yellow = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF7CC']] // Light Yellow
];


// ===================================================================
// TAB 1: 1.หัวข้อตัวชี้วัด (EDITED)
// ===================================================================
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('1.หัวข้อตัวชี้วัด');

// --- 1.1 Header & User Info ---
$sheet1->setCellValue('A1', "การจัดทำหัวข้อตัวชี้วัดผลสำเร็จของงาน (KPIs) ประจำปีการศึกษา $current_academic_year");
$sheet1->mergeCells('A1:G1'); // ใช้ A-G (รวมคอลัมน์ใหม่)
$sheet1->getStyle('A1')->applyFromArray($style_title_array);

$sheet1->setCellValue('A2', 'วัตถุประสงค์ : เพื่อพนักงานสามารถทราบถึงเหตุและผลของการจัดทำตัวชี้วัดผลสำเร็จของงาน (KPIs) ให้สอดคล้อง และเชื่อมโยงกับความสำเร็จขององค์กร และหน่วยงาน อย่างมีประสิทธิผลในการดำเนินการสูงสุด');
$sheet1->mergeCells('A2:G2');
$sheet1->getStyle('A2')->getAlignment()->setWrapText(true);

// ข้อมูลผู้ประเมิน (Row 3, 4)
$evaluator_name = $viewed_user_data['Fname_th'] . ' ' . $viewed_user_data['Lname_th'];
$evaluator_pos = $viewed_user_data['Title_name'] ?? '-';
$evaluator_dept = $viewed_user_data['Department_name'] ?? '-';

$sheet1->setCellValue('A3', 'ชื่อผู้รับการประเมิน: ' . $evaluator_name);
$sheet1->mergeCells('A3:D3');
$sheet1->setCellValue('E3', 'ตำแหน่ง: ' . $evaluator_pos);
$sheet1->mergeCells('E3:G3');

$sheet1->setCellValue('A4', 'สังกัด: ' . $evaluator_dept);
$sheet1->mergeCells('A4:G4');

// ข้อมูลผู้บังคับบัญชา (Row 5 - Hardcode)
$sheet1->setCellValue('A5', 'ชื่อผู้บังคับบัญชา: ดร.สมชาย ใจดี '); // Hardcode ตามคำขอ
$sheet1->mergeCells('A5:D5');
$sheet1->setCellValue('E5', '');
$sheet1->mergeCells('E5:G5');

// Styles for Info
$sheet1->getStyle('A3:G5')->getFont()->setSize(16);

// --- 1.2 Table Header (Row 7-8) ---
// ปรับโครงสร้างคอลัมน์ใหม่: A=ลำดับ, B=ประเภท, C=น้ำหนัก(ประเภท), D=หัวข้อ, E=น้ำหนัก(ตัวชี้วัด)
// (ตัดคอลัมน์ F, G เดิมที่เป็น Importance Level ออก)

$row = 7;
$sheet1->setCellValue('A'.$row, "ตัวชี้วัดผลสำเร็จของงาน\n(Key Performance Indicators: KPIs)");
$sheet1->mergeCells('A'.$row.':E'.$row); // Merge หัวตาราง
$sheet1->getStyle('A'.$row)->getAlignment()->setWrapText(true);

$row = 8;
$sheet1->setCellValue('A'.$row, 'ลำดับ');
$sheet1->setCellValue('B'.$row, 'ประเภทตัวชี้วัด');
$sheet1->setCellValue('C'.$row, 'น้ำหนัก (ประเภท)');
$sheet1->setCellValue('D'.$row, 'หัวข้อตัวชี้วัด');
$sheet1->setCellValue('E'.$row, 'น้ำหนักตัวชี้วัด'); // เปลี่ยนจาก "น้อย/ปานกลาง/มาก" เป็นช่องนี้

// Style Header
$sheet1->getStyle('A7:E8')->applyFromArray($tab1_header_style_array);

// ตั้งค่าความกว้างคอลัมน์
$sheet1->getColumnDimension('A')->setWidth(10);
$sheet1->getColumnDimension('B')->setWidth(30);
$sheet1->getColumnDimension('C')->setWidth(15);
$sheet1->getColumnDimension('D')->setWidth(60);
$sheet1->getColumnDimension('E')->setWidth(20);

// --- 1.3 Data Loop ---
$row = 9; // Start data at row 9
if (!empty($kpi_data)) {
    foreach ($kpi_data as $kpi_type_id => $kpi_type) {
        $topics_in_type = $kpi_type['topics'] ?? [];
        $topic_count = count($topics_in_type);
        $rowspan = ($topic_count > 0) ? $topic_count : 1;
        $current_type_start_row = $row;

        if ($topic_count > 0) {
            $is_first_topic = true;
            foreach ($topics_in_type as $topic_id => $topic) {
                if ($is_first_topic) {
                    $sheet1->setCellValue('A' . $row, htmlspecialchars($kpi_type['Order_No']));
                    $type_name_display = htmlspecialchars($kpi_type['KPI_Type_Name_EN']) . "\n(" . htmlspecialchars($kpi_type['KPI_Type_Name_TH']) . ")";
                    $sheet1->setCellValue('B' . $row, $type_name_display);
                    $sheet1->getStyle('B' . $row)->getAlignment()->setWrapText(true);
                    $sheet1->setCellValue('C' . $row, htmlspecialchars($kpi_type['TypeWeight']));
                    $is_first_topic = false;
                }
                
                // หัวข้อตัวชี้วัด
                $sheet1->setCellValue('D' . $row, htmlspecialchars($topic['name']));
                $sheet1->getStyle('D' . $row)->getAlignment()->setWrapText(true);

                // น้ำหนักตัวชี้วัด (มาแทนช่อง Checkbox เดิม)
                $sheet1->setCellValue('E' . $row, htmlspecialchars($topic['weight']));
                $sheet1->getStyle('E' . $row)->applyFromArray($style_align_center_array);

                // เส้นขอบ
                $sheet1->getStyle('A' . $row . ':E' . $row)->applyFromArray($style_all_borders_array);
                $row++;
            }
            $end_row_of_group = $row - 1; 

            // จัดตำแหน่ง Group (A, B, C)
            $sheet1->getStyle("A{$current_type_start_row}:C{$end_row_of_group}")
                   ->getAlignment()
                   ->setVertical(Alignment::VERTICAL_TOP); 
            $sheet1->getStyle("A{$current_type_start_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("C{$current_type_start_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Merge A, B, C if needed
            if ($rowspan > 1) {
                $sheet1->mergeCells("A{$current_type_start_row}:A{$end_row_of_group}");
                $sheet1->mergeCells("B{$current_type_start_row}:B{$end_row_of_group}");
                $sheet1->mergeCells("C{$current_type_start_row}:C{$end_row_of_group}");
            }
        } else {
            // กรณีไม่มีหัวข้อใน Type นี้
            $sheet1->setCellValue('A' . $row, htmlspecialchars($kpi_type['Order_No']));
            $sheet1->setCellValue('B' . $row, htmlspecialchars($kpi_type['KPI_Type_Name_EN']));
            $sheet1->setCellValue('C' . $row, htmlspecialchars($kpi_type['TypeWeight']));
            $sheet1->setCellValue('D' . $row, '-- ไม่มีหัวข้อตัวชี้วัด --');
            $sheet1->mergeCells('D' . $row . ':E' . $row);
            $sheet1->getStyle('A' . $row . ':E' . $row)->applyFromArray($style_all_borders_array);
            $sheet1->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }
    }
} else {
    $sheet1->setCellValue('A9', '--- ไม่พบข้อมูลตัวชี้วัดสำหรับผู้ใช้และปีการศึกษานี้ ---');
    $sheet1->mergeCells('A9:E9');
    $sheet1->getStyle('A9:E9')->applyFromArray($style_all_borders_array);
    $row++;
}

// --- 1.4 Footer (Description Year) ---
$row += 1; // เว้น 1 บรรทัด
if (!empty($footer_description)) {
    $sheet1->setCellValue('A' . $row, "หมายเหตุ / คำอธิบายเพิ่มเติม:\n" . $footer_description);
    $sheet1->mergeCells('A' . $row . ':E' . ($row + 3)); // Merge 4 บรรทัด
    $sheet1->getStyle('A' . $row)->getAlignment()->setWrapText(true);
    $sheet1->getStyle('A' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    $sheet1->getStyle('A' . $row . ':E' . ($row + 3))->applyFromArray($style_all_borders_array);
}


$spreadsheet->setActiveSheetIndex(0);

$filename = 'KPI_Report_' . $current_emp_code . '_' . $current_academic_year . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

$conn->close();
exit;

?>