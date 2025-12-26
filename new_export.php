<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'vendor/autoload.php';
include 'config/conn.php';
include 'config/academic_year_resolver.php'; 


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
    // กรณีดูของตัวเอง
    $current_emp_code = $_SESSION['Emp_code'];
    $user_type_id = $_SESSION['Type_id'];
    
    // [แก้ไข 3] ปรับ Logic การเลือกปี
    if (isset($_GET['year']) && !empty($_GET['year'])) {
         $current_academic_year = $_GET['year'];
    } else {
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

// [START] ดึงข้อมูล Description Year
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
        
        //Array สำหรับเก็บ Subjects และ Topics
        $grouped_data = []; // เก็บข้อมูลแบบ Subject -> Topics
        $orphaned_topics = []; // เก็บ Topics ที่ไม่มี Subject
        $total_rows_in_type = 0; // ตัวนับจำนวนบรรทัดทั้งหมด (เอาไว้ Merge Cell)

        $sql_subjects = "SELECT subject_id, subject_name, subject_order FROM subject_topic WHERE kpi_type_id = ? ORDER BY subject_order ASC";
        
        $stmt_sub = $conn->prepare($sql_subjects);
        $stmt_sub->bind_param("i", $current_kpi_type_id);
        $stmt_sub->execute();
        $res_sub = $stmt_sub->get_result();
        
        while ($sub = $res_sub->fetch_assoc()) {
            $sub_id = $sub['subject_id'];
            $grouped_data[$sub_id] = [
                'info' => $sub,
                'topics' => []
            ];
            $total_rows_in_type++; 
        }
        $stmt_sub->close();
        $sql_topics = "SELECT
                        kt.KPI_topic_id, kt.subject_id,
                        kt.Order_no,
                        ktype.Order_no as TypeOrder,
                        kt.KPI_topic_name as name,
                        kt.Unit as unit,
                        kt.Score_criteria as criteria,
                        kt.criteria_1, kt.criteria_2, kt.criteria_3, kt.criteria_4, kt.criteria_5, 
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
                $sub_id = $topic_row['subject_id'];
                $all_topic_ids_flat[] = $topic_id; 

                if (!empty($sub_id) && isset($grouped_data[$sub_id])) {
                    $grouped_data[$sub_id]['topics'][] = $topic_row;
                } else {
                    $orphaned_topics[] = $topic_row;
                }
                $total_rows_in_type++;
            }
        }
        $stmt_topics->close();
        foreach ($grouped_data as $gid => &$group) {
            $subj_order = $group['info']['subject_order'];
            $seq = 1;
            
            foreach ($group['topics'] as &$topic) {
                $topic['id'] = $subj_order . '.' . $seq;
                $seq++;
            }
        }
        unset($group);
        unset($topic);
        $orphan_seq = 1;
        foreach ($orphaned_topics as &$topic) {
             $topic['id'] = $type_row['Order_No'] . '.' . $orphan_seq;
             $orphan_seq++;
        }
        unset($topic);
        $type_row['grouped_data'] = $grouped_data;
        $type_row['orphaned_topics'] = $orphaned_topics;
        $type_row['total_rows'] = ($total_rows_in_type == 0) ? 1 : $total_rows_in_type;

        $kpi_data[$current_kpi_type_id] = $type_row;
    }
}
$stmt_kpi_types->close();
$all_topic_ids_flat = array_unique($all_topic_ids_flat);
$saved_review_data = [];

if (!empty($all_topic_ids_flat)) {
    $topic_ids_placeholder = implode(',', array_fill(0, count($all_topic_ids_flat), '?'));

    // ดึงข้อมูลรายหัวข้อ (individual_kpi)
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
    }
}

// ดึงคะแนนรายหมวด (score_evaluation)
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
}

// ดึงคะแนนรวมทั้งปี (total_score_evaluation)
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
}

// --- Setup Spreadsheet ---
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
    ->setCreator("KPI System")
    ->setTitle("KPI Report " . $current_emp_code . " ปี " . $current_academic_year)
    ->setSubject("KPI Report");

// Font Default
$spreadsheet->getDefaultStyle()->getFont()->setName('TH Sarabun New')->setSize(16);



// 1. Blue Header
$style_header_blue = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF3030FF']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

// 2. User Info Label (White BG)
$style_info_label = [
    'font' => ['bold' => false, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']]
];

// 3. User Info Value (Light Yellow)
$style_info_value = [
    'font' => ['size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF2CC']]
];

// 4. Green KPI Header
$style_kpi_header_green = [
    'font' => ['bold' => false, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2EFDA']]
];

// 5. Table Column Headers
$style_table_header = [
    'font' => ['bold' => true, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9EAD3']]
];

// 6. Data Cells
$style_data_center = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$style_data_left = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$style_type_col = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDDEBF7']]
];

$style_type_weight = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDDEBF7']]
];

$style_weight_cell = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_TOP],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF2CC']]
];
$style_subject_header = [
    'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FF000000']], 
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER,],
    'fill' => [
        'fillType' => Fill::FILL_SOLID, 
        'startColor' => ['argb' => 'FFD9D9D9'] // <--- แก้เป็นสีเทา (Code: FFD9D9D9)
    ], 
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('1.หัวข้อตัวชี้วัด(รายบุคคล)');
$sheet1->getColumnDimension('A')->setWidth(2);  // Margin
$sheet1->getColumnDimension('B')->setWidth(40); // ประเภท
$sheet1->getColumnDimension('C')->setWidth(15); // น้ำหนัก
$sheet1->getColumnDimension('D')->setWidth(30); // หัวข้อ (Part 1)
$sheet1->getColumnDimension('E')->setWidth(30); // หัวข้อ (Part 2 - merged)
$sheet1->getColumnDimension('F')->setWidth(15); // น้ำหนักข้อ

$header_text_line1 = "แบบฟอร์อมกำหนดหัวข้อตัวชี้วัด KPIs รายบุคคล"; 
$sheet1->setCellValue('B2', $header_text_line1);
$sheet1->mergeCells('B2:F2'); // ผสาน B ถึง F ให้ข้อความอยู่กึ่งกลางกระดาษ
$sheet1->getStyle('B2')->applyFromArray([
    'font' => ['bold' => true, 'size' => 30, 'name' => 'TH Sarabun New'], // ตัวหนา ขนาด 20
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);
$sheet1->getRowDimension(2)->setRowHeight(35); // ความสูงแถว

// บรรทัดที่ 3: ประจำปีการศึกษา (ถ้ามีในรูป)
$header_text_line2 = "ชี้แจง : กำหนด KPIs รายบุคคล โดยแปลงจาก KPIs ของหน่วยงานและบทบาทหน้าที่ของแต่ละบุคคล โดยกำหนดเป้าหมายรายบุคคลให้ชัดเจน";
$sheet1->setCellValue('B3', $header_text_line2);
$sheet1->mergeCells('B3:F3');
$sheet1->getStyle('B3')->applyFromArray([
    'font' => ['bold' => true, 'size' => 18, 'name' => 'TH Sarabun New'],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);
$sheet1->getRowDimension(3)->setRowHeight(30);

// Row 5: Header Blue
$sheet1->setCellValue('B5', 'ข้อมูลผู้ถูกประเมิน');
$sheet1->mergeCells('B5:F5'); // B-F
$sheet1->getStyle('B5:F5')->applyFromArray($style_header_blue);
$sheet1->getRowDimension(5)->setRowHeight(30);

// Data Preparation
$user_fullname = $viewed_user_data['Fname_th'] . ' ' . $viewed_user_data['Lname_th'];
$user_pos_text = '-';
$sql_get_typename = "SELECT Type_name_th FROM user_type WHERE Type_id = ? LIMIT 1";
$stmt_typename = $conn->prepare($sql_get_typename);

if ($stmt_typename) {
    $stmt_typename->bind_param("i", $user_type_id);
    $stmt_typename->execute();
    $res_typename = $stmt_typename->get_result();
    if ($row_typename = $res_typename->fetch_assoc()) {
        $user_pos_text = $row_typename['Type_name_th'];
    }
    $stmt_typename->close();
}

$user_pos = $user_pos_text; 
$user_dept = $viewed_user_data['Department_name'] ?? '-';
$user_sup = "คณบดีคณะวิทยาศาสตร์และเทคโนโลยี (ผศ.ดร. อิทธิพงษ์ เขมะเพชร)"; 

// Row 6: ชื่อ-นามสกุล
$sheet1->setCellValue('B6', 'ชื่อ-นามสกุล');
$sheet1->setCellValue('C6', $user_fullname);
$sheet1->mergeCells('C6:F6');
$sheet1->getStyle('B6')->applyFromArray($style_info_label);
$sheet1->getStyle('C6:F6')->applyFromArray($style_info_value);

// Row 7: ตำแหน่งงานปัจจุบัน
$sheet1->setCellValue('B7', 'ตำแหน่งงานปัจจุบัน');
$sheet1->setCellValue('C7', $user_pos);
$sheet1->mergeCells('C7:F7');
$sheet1->getStyle('B7')->applyFromArray($style_info_label);
$sheet1->getStyle('C7:F7')->applyFromArray($style_info_value);

// Row 8: หน่วยงาน
$sheet1->setCellValue('B8', 'หน่วยงาน');
$sheet1->setCellValue('C8', $user_dept);
$sheet1->mergeCells('C8:F8');
$sheet1->getStyle('B8')->applyFromArray($style_info_label);
$sheet1->getStyle('C8:F8')->applyFromArray($style_info_value);

// Row 9: ชื่อผู้บังคับบัญชาโดยตรง
$sheet1->setCellValue('B9', 'ชื่อผู้บังคับบัญชาโดยตรง');
$sheet1->setCellValue('C9', $user_sup);
$sheet1->mergeCells('C9:F9');
$sheet1->getStyle('B9')->applyFromArray($style_info_label);
$sheet1->getStyle('C9:F9')->applyFromArray($style_info_value);



// Row 11: Green Header
$sheet1->setCellValue('B11', 'กำหนดหัวข้อตัวชี้วัด KPIs รายบุคคล');
$sheet1->mergeCells('B11:F11');
$sheet1->getStyle('B11:F11')->applyFromArray($style_kpi_header_green);
$sheet1->getRowDimension(11)->setRowHeight(30);

// Row 12-13: Table Headers
$sheet1->setCellValue('B12', "ประเภทตัวชี้วัด");
$sheet1->mergeCells('B12:B13');

$sheet1->setCellValue('C12', "น้ำหนัก\n(100%)");
$sheet1->mergeCells('C12:C13');

$sheet1->setCellValue('D12', "หัวข้อตัวชี้วัด");
$sheet1->mergeCells('D12:E13'); // Merge D-E and 12-13

$sheet1->setCellValue('F12', "น้ำหนัก\n(Weight)");
$sheet1->mergeCells('F12:F13');

$sheet1->getStyle('B12:F13')->applyFromArray($style_table_header);


$row = 14;
$total_sum_weight = 0;

if (!empty($kpi_data)) {
    foreach ($kpi_data as $kpi_type_id => $kpi_type) {
        $rowspan = $kpi_type['total_rows'];
        $current_type_start_row = $row;
        $end_row_of_group = $row + $rowspan - 1;

        // --- Column B (Type Name) ---
        $type_name_display = htmlspecialchars($kpi_type['KPI_Type_Name_EN']);
        $type_name_display .= "\n(" . htmlspecialchars($kpi_type['KPI_Type_Name_TH']) . ")";
        $sheet1->setCellValue('B' . $row, $type_name_display);
        
        // --- Column C (Type Weight) ---
        $sheet1->setCellValue('C' . $row, htmlspecialchars($kpi_type['TypeWeight']));

        // Merge B และ C ตามจำนวนบรรทัดทั้งหมดในกลุ่ม (Subject + Topic)
        if ($rowspan > 1) {
            $sheet1->mergeCells("B{$current_type_start_row}:B{$end_row_of_group}");
            $sheet1->mergeCells("C{$current_type_start_row}:C{$end_row_of_group}");
        }

        $sheet1->getStyle("B{$current_type_start_row}:B{$end_row_of_group}")->applyFromArray($style_type_col);
        $sheet1->getStyle("C{$current_type_start_row}:C{$end_row_of_group}")->applyFromArray($style_type_weight);
        $sheet1->getStyle("B{$current_type_start_row}:C{$end_row_of_group}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        // --- วนลูปแสดงข้อมูล (Subject -> Topics) ---
        $has_data = false;

        // แสดงกลุ่มที่มี Subject
        if (!empty($kpi_type['grouped_data'])) {
            foreach ($kpi_type['grouped_data'] as $sub_id => $group) {
                $has_data = true;
                $subject_text = $group['info']['subject_order'] . '. ' . $group['info']['subject_name'];
                
                $sheet1->setCellValue('D' . $row, $subject_text); 
                $sheet1->mergeCells("D{$row}:E{$row}");
                $sheet1->setCellValue('F' . $row, ''); 

                $sheet1->getStyle("D{$row}:F{$row}")->applyFromArray($style_subject_header);
                $row++;

                foreach ($group['topics'] as $topic) {
                    $topic_name = htmlspecialchars($topic['name']);
                    $sheet1->setCellValue('D' . $row, $topic_name);
                    $sheet1->mergeCells("D{$row}:E{$row}");
                    
                    $weight_val = (float)$topic['weight'];
                    $sheet1->setCellValue('F' . $row, $weight_val);
                    $total_sum_weight += $weight_val;

                    // Styles Data
                    $sheet1->getStyle("D{$row}:E{$row}")->applyFromArray($style_data_left);
                    $sheet1->getStyle("D{$row}:E{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF2CC');
                    $sheet1->getStyle("F{$row}")->applyFromArray($style_weight_cell);
                    $row++;
                }
            }
        }
        if (!empty($kpi_type['orphaned_topics'])) {
            $has_data = true;
            foreach ($kpi_type['orphaned_topics'] as $topic) {
                $topic_name = htmlspecialchars($topic['name']);
                $sheet1->setCellValue('D' . $row, $topic_name);
                $sheet1->mergeCells("D{$row}:E{$row}");
                
                $weight_val = (float)$topic['weight'];
                $sheet1->setCellValue('F' . $row, $weight_val);
                $total_sum_weight += $weight_val;

                $sheet1->getStyle("D{$row}:E{$row}")->applyFromArray($style_data_left);
                $sheet1->getStyle("F{$row}")->applyFromArray($style_weight_cell);
                $row++;
            }
        }

        if (!$has_data) {
            $sheet1->setCellValue('D' . $row, '-- ไม่มีหัวข้อ --');
            $sheet1->mergeCells("D{$row}:E{$row}");
            $sheet1->setCellValue('F' . $row, '-');
            $sheet1->getStyle("D{$row}:F{$row}")->applyFromArray($style_data_center);
            $row++;
        }
    }
    
    $sheet1->setCellValue('E' . $row, 'รวม'); 
    $sheet1->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet1->setCellValue('F' . $row, $total_sum_weight);
    $sheet1->getStyle('F' . $row)->applyFromArray($style_table_header); 
    $sheet1->getStyle('F' . $row)->getFill()->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFF200'));

} else {
    $sheet1->setCellValue('B14', '--- ไม่พบข้อมูล ---');
    $sheet1->mergeCells('B14:F14');
    $row++;
}

$row += 1;
if (!empty($footer_description)) {
    $sheet1->setCellValue('B' . $row, "" . $footer_description);
    $sheet1->mergeCells('B' . $row . ':F' . ($row));
    $sheet1->getStyle('B' . $row)->getFont()->getColor()->setARGB('FFFF0000'); // Red Text
}

// ===================================================================
// TAB 2: 2.แบบกำหนดรายละเอียด KPIs
// ===================================================================
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('2.แบบกำหนดรายละเอียด KPIs');

$sheet2->getColumnDimension('A')->setWidth(2);   
$sheet2->getColumnDimension('B')->setWidth(8);  

// C-F: พื้นที่หลักเดิม (จะถูกรวมใน Section 2 แต่แยกใน Section 1)
$sheet2->getColumnDimension('C')->setWidth(40);  
$sheet2->getColumnDimension('D')->setWidth(12);  
$sheet2->getColumnDimension('E')->setWidth(20);  
$sheet2->getColumnDimension('F')->setWidth(20);

// G: เดิมเป็นเกณฑ์ เริ่มเปลี่ยนเป็นส่วนท้ายของตัวชี้วัดใน Sec 2
$sheet2->getColumnDimension('G')->setWidth(15); 

// H-L: เกณฑ์ 1-5 (ปรับให้เท่ากันตามขอ)
$val_criteria_width = 11; 
$sheet2->getColumnDimension('H')->setWidth($val_criteria_width);
$sheet2->getColumnDimension('I')->setWidth($val_criteria_width);
$sheet2->getColumnDimension('J')->setWidth($val_criteria_width);
$sheet2->getColumnDimension('K')->setWidth($val_criteria_width);
$sheet2->getColumnDimension('L')->setWidth($val_criteria_width);

// M-P: ส่วนคะแนน (ปรับให้เท่ากันและเล็กที่สุดตามขอ)
$val_eval_width = 9;
$sheet2->getColumnDimension('M')->setWidth($val_eval_width);
$sheet2->getColumnDimension('N')->setWidth($val_eval_width);
$sheet2->getColumnDimension('O')->setWidth($val_eval_width);
$sheet2->getColumnDimension('P')->setWidth($val_eval_width);

// --- Style Arrays (เหมือนเดิม) ---
$style_header_sheet2 = [
    'font' => ['bold' => true, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']]
];
$style_all_borders = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$style_data_text = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$style_data_center = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$style_sig_header = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0E5D1']]
];
$style_sig_box = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF7CC']]
];


$row = 1;

// [START] ข้อมูลผู้ถูกประเมิน
// Row 1: Header Blue (Merge B-P)
$sheet2->setCellValue('B' . $row, 'ข้อมูลผู้ถูกประเมิน');
$sheet2->mergeCells('B' . $row . ':P' . $row); 
$sheet2->getStyle('B' . $row . ':P' . $row)->applyFromArray($style_header_blue);
$sheet2->getRowDimension($row)->setRowHeight(30);
$row++;

// Row 2: ชื่อ-นามสกุล
$sheet2->setCellValue('B' . $row, 'ชื่อ-นามสกุล');
$sheet2->setCellValue('E' . $row, $user_fullname); 
$sheet2->mergeCells('B' . $row . ':D' . $row); 
$sheet2->mergeCells('E' . $row . ':P' . $row); 
$sheet2->getStyle('B' . $row . ':D' . $row)->applyFromArray($style_info_label);
$sheet2->getStyle('E' . $row . ':P' . $row)->applyFromArray($style_info_value);
$row++;

// Row 3: ตำแหน่งงานปัจจุบัน
$sheet2->setCellValue('B' . $row, 'ตำแหน่งงานปัจจุบัน');
$sheet2->setCellValue('E' . $row, $user_pos);
$sheet2->mergeCells('B' . $row . ':D' . $row);
$sheet2->mergeCells('E' . $row . ':P' . $row);
$sheet2->getStyle('B' . $row . ':D' . $row)->applyFromArray($style_info_label);
$sheet2->getStyle('E' . $row . ':P' . $row)->applyFromArray($style_info_value);
$row++;

// Row 4: หน่วยงาน
$sheet2->setCellValue('B' . $row, 'หน่วยงาน');
$sheet2->setCellValue('E' . $row, $user_dept);
$sheet2->mergeCells('B' . $row . ':D' . $row);
$sheet2->mergeCells('E' . $row . ':P' . $row);
$sheet2->getStyle('B' . $row . ':D' . $row)->applyFromArray($style_info_label);
$sheet2->getStyle('E' . $row . ':P' . $row)->applyFromArray($style_info_value);
$row++;

// Row 5: ชื่อผู้บังคับบัญชาโดยตรง
$sheet2->setCellValue('B' . $row, 'ชื่อผู้บังคับบัญชาโดยตรง');
$sheet2->setCellValue('E' . $row, $user_sup);
$sheet2->mergeCells('B' . $row . ':D' . $row);
$sheet2->mergeCells('E' . $row . ':P' . $row);
$sheet2->getStyle('B' . $row . ':D' . $row)->applyFromArray($style_info_label);
$sheet2->getStyle('E' . $row . ':P' . $row)->applyFromArray($style_info_value);
$row++;

$row++; // เว้น 1 บรรทัด

// --- ส่วนลายเซ็น ---
$style_sig_header_pink = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FF000000']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2DCDB']]
];

$style_sig_label_white = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']]
];

$style_sig_box_yellow = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF2CC']]
];

$eval_year = intval($current_academic_year); 

// Row 1: Header
$sheet2->setCellValue('B' . $row, 'รอบการประเมิน');
$sheet2->mergeCells('B' . $row . ':F' . $row); // 5 คอลัมน์ซ้าย

$sheet2->setCellValue('G' . $row, 'ลายเซ็นอาจารย์');
$sheet2->mergeCells('G' . $row . ':K' . $row); // 5 คอลัมน์กลาง

$sheet2->setCellValue('L' . $row, 'ลายเซ็นผู้บังคับบัญชาโดยตรง');
$sheet2->mergeCells('L' . $row . ':P' . $row); // 5 คอลัมน์ขวา

$sheet2->getStyle('B' . $row . ':P' . $row)->applyFromArray($style_sig_header_pink);
$sheet2->getRowDimension($row)->setRowHeight(30);
$row++;

// Row 2: ทบทวนกลางปี
$sheet2->setCellValue('B' . $row, 'ทบทวนกลางปี ( ' . $eval_year . ')');
$sheet2->mergeCells('B' . $row . ':F' . $row);
$sheet2->getStyle('B' . $row . ':F' . $row)->applyFromArray($style_sig_label_white);

$sheet2->setCellValue('G' . $row, '(ลงชื่อ)');
$sheet2->mergeCells('G' . $row . ':K' . $row);
$sheet2->getStyle('G' . $row . ':K' . $row)->applyFromArray($style_sig_box_yellow);

$sheet2->setCellValue('L' . $row, '(ลงชื่อ)');
$sheet2->mergeCells('L' . $row . ':P' . $row);
$sheet2->getStyle('L' . $row . ':P' . $row)->applyFromArray($style_sig_box_yellow);

$sheet2->getRowDimension($row)->setRowHeight(40);
$row++;

// Row 3: ประเมินผลประจำปี
$sheet2->setCellValue('B' . $row, 'การประเมินผลประจำปี (' . $eval_year . ')');
$sheet2->mergeCells('B' . $row . ':F' . $row);
$sheet2->getStyle('B' . $row . ':F' . $row)->applyFromArray($style_sig_label_white);

$sheet2->setCellValue('G' . $row, '(ลงชื่อ)');
$sheet2->mergeCells('G' . $row . ':K' . $row);
$sheet2->getStyle('G' . $row . ':K' . $row)->applyFromArray($style_sig_box_yellow);

$sheet2->setCellValue('L' . $row, '(ลงชื่อ)');
$sheet2->mergeCells('L' . $row . ':P' . $row);
$sheet2->getStyle('L' . $row . ':P' . $row)->applyFromArray($style_sig_box_yellow);

$sheet2->getRowDimension($row)->setRowHeight(40);
$row++;

$row++;

// --- ส่วน Rating Scale ---
$row++; 
$sheet2->setCellValue('B' . $row, 'ส่วนที่ 1 : SECTION FIRST-GOAL SETTING AND EVALUATION/การประเมินผลตามเป้าหมายที่กำหนด');
$sheet2->mergeCells('B' . $row . ':P' . $row);
$sheet2->getStyle('B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF79646');
$sheet2->getStyle('B' . $row)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
$sheet2->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$row++;

$sheet2->setCellValue('B' . $row, 'เกณฑ์การให้คะแนนและผลการประเมิน / Annual Review Rating');
$sheet2->mergeCells('B' . $row . ':P' . $row);
$sheet2->getStyle('B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDAEEF3');
$sheet2->getStyle('B' . $row)->getFont()->setBold(true);
$sheet2->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$row++;

// --- Style Arrays ---
$style_header_blue = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0000FF']], 
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$style_header_salmon = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF79646']], 
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$style_header_peach = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFABF8F']], 
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

// --- สร้าง Header ใหม่ (B-P) ---
$sheet2->setCellValue('B' . $row, 'ส่วนที่ 1 : KPI (Key Performance Indicators) การประเมิน “ผลลัพธ์งาน” ตามเป้าหมายที่กำหนด โดยยึดโยงกับ KPIs ของหน่วยงาน/องค์กร');
$sheet2->mergeCells('B' . $row . ':P' . $row);
$sheet2->getStyle('B' . $row . ':P' . $row)->applyFromArray($style_header_blue);
$row++;

$sheet2->setCellValue('B' . $row, 'ผลสำเร็จของงาน');
$sheet2->mergeCells('B' . $row . ':P' . $row);
$sheet2->getStyle('B' . $row . ':P' . $row)->applyFromArray($style_header_salmon);
$row++;

$sheet2->setCellValue('B' . $row, 'การวางแผนกำหนดเป้าหมาย');
$sheet2->mergeCells('B' . $row . ':P' . $row);
$sheet2->getStyle('B' . $row . ':P' . $row)->applyFromArray($style_header_peach);
$row++;

// ===================================================================
// ส่วนหัวตาราง (Header) (Section 1 คงเดิม)
// ===================================================================
$start_head = $row;

$sheet2->setCellValue('B' . $row, 'ลำดับ');
$sheet2->mergeCells('B' . $row . ':B' . ($row+1)); 

$sheet2->setCellValue('C' . $row, 'ตัวชี้วัดผลงาน');
$sheet2->mergeCells('C' . $row . ':C' . ($row+1)); 

$sheet2->setCellValue('D' . $row, "น้ำหนัก\n(100)");
$sheet2->mergeCells('D' . $row . ':D' . ($row+1));

$sheet2->setCellValue('E' . $row, 'หน่วยวัด');
$sheet2->mergeCells('E' . $row . ':E' . ($row+1));

$sheet2->setCellValue('F' . $row, "เป้าหมายความสำเร็จ\nที่คาดหวังทั้งปี");
$sheet2->mergeCells('F' . $row . ':F' . ($row+1));

// เกณฑ์การให้คะแนน (G-K)
$sheet2->setCellValue('G' . $row, 'เกณฑ์การให้คะแนนตามผลงานที่ทำได้ทั้งปี');
$sheet2->mergeCells('G' . $row . ':K' . $row); 

// ผลงาน/คะแนน (L-M)
$sheet2->setCellValue('L' . $row, "ผลงานที่ทำได้จริงทั้งปี\nเทียบกับเป้าหมายมาตรฐานผลงาน");
$sheet2->mergeCells('L' . $row . ':M' . $row);

// หมายเหตุ (N-P)
$sheet2->setCellValue('N' . $row, 'หมายเหตุ');
$sheet2->mergeCells('N' . $row . ':P' . ($row+1)); 

$sheet2->getStyle('B' . $row . ':P' . $row)->applyFromArray($style_header_peach);
$row++;

// แถวที่ 2
$sheet2->setCellValue('G' . $row, '1');
$sheet2->setCellValue('H' . $row, '2');
$sheet2->setCellValue('I' . $row, '3');
$sheet2->setCellValue('J' . $row, '4');
$sheet2->setCellValue('K' . $row, '5');

$sheet2->setCellValue('L' . $row, "ผลคะแนน\n(1-5)");
$sheet2->setCellValue('M' . $row, "คะแนนรวม\n(น้ำหนักxผล)");

$sheet2->getStyle('B' . $row . ':P' . $row)->applyFromArray($style_header_peach);
$sheet2->getStyle('B' . ($row-1) . ':P' . $row)->getAlignment()->setWrapText(true);
$row++;

if (!empty($kpi_data)) {
    foreach ($kpi_data as $type_id => $kpi_type) {
        
        // --- ส่วนหัวข้อ KPI Type (หมวดหลัก) ---
        // (1.1) คอลัมน์ B: ไม่แสดงเลขลำดับแล้ว (ปล่อยว่าง)
        $sheet2->setCellValue('B' . $row, ''); 
        
        // (1.2) คอลัมน์ C: แสดงชื่อ EN + (TH)
        $type_name_display = $kpi_type['KPI_Type_Name_EN'] . ' (' . $kpi_type['KPI_Type_Name_TH'] . ')';
        $sheet2->setCellValue('C' . $row, $type_name_display);
        
        $sheet2->setCellValue('D' . $row, $kpi_type['TypeWeight']);
        $sheet2->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('D' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        $sheet2->mergeCells('E' . $row . ':P' . $row);
        
        // Styles หมวดหลัก
        $sheet2->getStyle('B' . $row . ':P' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDAEEF3');
        $sheet2->getStyle('B' . $row . ':P' . $row)->getFont()->setBold(true);
        $sheet2->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); 
        $sheet2->getStyle('B' . $row . ':P' . $row)->applyFromArray($style_all_borders);

        $row++;
        // $type_order++; // เอาออก

        // ฟังก์ชันย่อยสำหรับแสดงแถวตัวชี้วัด (เหมือนเดิม)
        $renderTopicRow = function($topic) use ($sheet2, &$row, $saved_review_data, $style_all_borders) {
             $topic_id = $topic['KPI_topic_id'];
             $saved_h1 = $saved_review_data[$topic_id . "_1"] ?? null;
             $saved_h2 = $saved_review_data[$topic_id . "_2"] ?? null;

             $kpi_name_display = $topic['name'];
             if (isset($topic['Additional']) && $topic['Additional'] === 'yes') {
                 $add_text = $saved_h2['Additional'] ?? ($saved_h1['Additional'] ?? '-');
                 $kpi_name_display .= "\n(คณะกรรมการ: " . $add_text . ")";
             }

             // ใส่ข้อมูล
             $sheet2->setCellValue('B' . $row, $topic['id']);
             $sheet2->setCellValue('C' . $row, $kpi_name_display);
             $sheet2->setCellValue('D' . $row, $topic['weight']);
             $sheet2->setCellValue('E' . $row, $topic['unit']);
             $sheet2->setCellValue('F' . $row, $topic['target']);
             
             $sheet2->setCellValue('G' . $row, $topic['criteria_1'] ?? '');
             $sheet2->setCellValue('H' . $row, $topic['criteria_2'] ?? '');
             $sheet2->setCellValue('I' . $row, $topic['criteria_3'] ?? '');
             $sheet2->setCellValue('J' . $row, $topic['criteria_4'] ?? '');
             $sheet2->setCellValue('K' . $row, $topic['criteria_5'] ?? '');

             $score = $saved_h2['score'] ?? ($saved_h1['score'] ?? '');
             $sheet2->setCellValue('L' . $row, $score);

             $total_score = $saved_h2['total_score'] ?? ($saved_h1['total_score'] ?? '');
             $sheet2->setCellValue('M' . $row, $total_score);

             $advice = $saved_h2['Advice'] ?? ($saved_h1['Advice'] ?? '');
             $sheet2->setCellValue('N' . $row, $advice);
             $sheet2->mergeCells('N' . $row . ':P' . $row);

             // Styles
             $sheet2->getStyle('B' . $row . ':P' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
             $sheet2->getStyle('B' . $row . ':P' . $row)->getAlignment()->setWrapText(true);
             $sheet2->getStyle('B' . $row . ':P' . $row)->applyFromArray($style_all_borders);
             $sheet2->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
             $sheet2->getStyle('D' . $row . ':M' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
             $sheet2->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
             $sheet2->getStyle('N' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
             $sheet2->getStyle('G' . $row . ':K' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFDF5');

             $row++;
        };

        // --- 1. แสดงกลุ่มที่มี Subject ---
        if (!empty($kpi_type['grouped_data'])) {
            foreach ($kpi_type['grouped_data'] as $sub_id => $group) {
                // (2) แก้ไขการแสดงผล Subject Topic
                
                // คอลัมน์ B: ลำดับ Subject
                $sheet2->setCellValue('B' . $row, $group['info']['subject_order']);
                $sheet2->getStyle('B' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FF000000']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ]);

                // คอลัมน์ C: ชื่อ Subject (Merge ไปถึง P เพื่อให้มีที่เขียนยาวๆ)
                $sheet2->setCellValue('C' . $row, $group['info']['subject_name']);
                $sheet2->mergeCells('C' . $row . ':P' . $row); 
                
                $sheet2->getStyle('C' . $row . ':P' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FF000000']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']], // สีเทา
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ]);
                $row++;

                // แสดง Topics ในกลุ่มนี้
                foreach ($group['topics'] as $topic) {
                    $renderTopicRow($topic);
                }
            }
        }

        // --- 2. แสดง Topics ที่ไม่มี Subject (Orphaned) ---
        if (!empty($kpi_type['orphaned_topics'])) {
            foreach ($kpi_type['orphaned_topics'] as $topic) {
                $renderTopicRow($topic);
            }
        }
        
        // สรุปคะแนนหมวด (Footer)
        $sheet2->setCellValue('B' . $row, 'รวมคะแนนหมวด');
        $sheet2->mergeCells('B' . $row . ':L' . $row); 
        $sheet2->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        $sheet2->setCellValue('M' . $row, $saved_category_scores[$type_id] ?? '0.00'); 
        $sheet2->mergeCells('N' . $row . ':P' . $row); 

        $sheet2->getStyle('B' . $row . ':P' . $row)->applyFromArray($style_all_borders);
        $sheet2->getStyle('B' . $row)->getFont()->setBold(true);
        $sheet2->getStyle('M' . $row)->getFont()->setBold(true);
        $sheet2->getStyle('M' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;
    }
}

// --- Footer Summary (Grand Total) ---
$sheet2->setCellValue('B' . $row, 'รวมน้ำหนักด้านผลสำเร็จของงาน');
$sheet2->mergeCells('B' . $row . ':C' . $row);
$sheet2->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

$sheet2->setCellValue('D' . $row, $total_kpi_type_weight);
$sheet2->mergeCells('E' . $row . ':L' . $row); 
$sheet2->setCellValue('E' . $row, 'คะแนนรวมทั้งปี');
$sheet2->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

$sheet2->setCellValue('M' . $row, $saved_total_scores['Score_500_max'] ?? '');
$sheet2->mergeCells('N' . $row . ':P' . $row); 

// Style Footer
$sheet2->getStyle('B' . $row . ':P' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFCD5B4');
$sheet2->getStyle('B' . $row . ':P' . $row)->getFont()->setBold(true)->setSize(16);
$sheet2->getStyle('B' . $row . ':P' . $row)->applyFromArray($style_all_borders);
$sheet2->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet2->getStyle('M' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$row++;
// ===================================================================
// ตารางสรุปคะแนนตามหมวด
// ===================================================================
$row++; 

// 1. Style
$style_header_orange_dark = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FF000000']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFED7D31']], 
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$style_val_orange = [
    'font' => ['bold' => true, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFED7D31']], 
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

// 2. Header
// B-G
$sheet2->setCellValue('B' . $row, 'Measure/ Target / ตัวชี้วัด');
$sheet2->mergeCells('B' . $row . ':G' . $row); 

// H-K
$sheet2->setCellValue('H' . $row, "Weighting (น้ำหนัก)\n(100)");
$sheet2->mergeCells('H' . $row . ':K' . $row);

// L-P
$sheet2->setCellValue('L' . $row, 'คะแนน');
$sheet2->mergeCells('L' . $row . ':P' . $row);

$sheet2->getStyle('B' . $row . ':P' . $row)->applyFromArray($style_header_orange_dark);
$sheet2->getStyle('H' . $row)->getAlignment()->setWrapText(true);

$sheet2->getRowDimension($row)->setRowHeight(50); 
$row++;

// 3. Loop
if (!empty($kpi_data)) {
    foreach ($kpi_data as $type_id => $kpi_type) {
        $type_name = $kpi_type['KPI_Type_Name_EN'] . ' (' . $kpi_type['KPI_Type_Name_TH'] . ')';
        
        $sheet2->setCellValue('B' . $row, $type_name);
        $sheet2->mergeCells('B' . $row . ':G' . $row);
        $sheet2->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('B' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->setCellValue('H' . $row, $kpi_type['TypeWeight']);
        $sheet2->mergeCells('H' . $row . ':K' . $row);
        $sheet2->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('H' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $cat_score = $saved_category_scores[$type_id] ?? '0.00';
        $sheet2->setCellValue('L' . $row, $cat_score);
        $sheet2->mergeCells('L' . $row . ':P' . $row);
        $sheet2->getStyle('L' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('L' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->getStyle('B' . $row . ':P' . $row)->applyFromArray($style_all_borders);
        $sheet2->getRowDimension($row)->setRowHeight(25);
        $row++;
    }
}

// 4. Footer
$sheet2->setCellValue('H' . $row, "Score /คะแนนเต็ม\n(500 คะแนน)");
$sheet2->mergeCells('H' . $row . ':K' . $row);

$sheet2->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet2->getStyle('H' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER); 
$sheet2->getStyle('H' . $row)->getAlignment()->setWrapText(true);
$sheet2->getStyle('H' . $row)->getFont()->setBold(true);

$total_score_val = $saved_total_scores['Score_500_max'] ?? '0.00';
$sheet2->setCellValue('L' . $row, $total_score_val);
$sheet2->mergeCells('L' . $row . ':P' . $row);
$sheet2->getStyle('L' . $row . ':P' . $row)->applyFromArray($style_val_orange);

$sheet2->getStyle('H' . $row . ':P' . $row)->applyFromArray($style_all_borders);

$sheet2->getRowDimension($row)->setRowHeight(50);
$row++;

// ===================================================================
// SECTION 2: Competency (สมรรถนะ) (Shifted B-P)
// ===================================================================
$row++; 

// --- 1. Style ---
$style_sec2_head_blue = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0000FF']], 
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$style_sec2_subhead_peach = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FF000000']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF4B084']], 
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$style_sec2_topic_head_bg = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFCE4D6']], 
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$style_sec2_text_left = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']] 
];

$style_sec2_criteria_merged = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']] 
];

$style_reason_label_gray = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FF000000']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']] 
];

$style_sec2_yellow_bg = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF2CC']] 
];


// --- 2. Header หลัก (B-P) ---
$sheet2->setCellValue('B' . $row, 'ส่วนที่ 2 : Competency (สมรรถนะ) การประเมิน "พฤติกรรมและวิธีการทำงาน" ที่สนับสนุนเป้าหมายองค์กร ครอบคลุม Soft Skills และคุณลักษณะตามที่องค์กรกำหนด');
$sheet2->mergeCells('B' . $row . ':P' . $row);
$sheet2->getStyle('B' . $row . ':P' . $row)->applyFromArray($style_sec2_head_blue);
$sheet2->getRowDimension($row)->setRowHeight(35);
$row++;

// --- 3. Header ตาราง (B-P) ---
$start_head_row = $row;
// [แก้ไข] ตัวชี้วัด ยาวถึง G
$sheet2->setCellValue('B' . $row, 'ตัวชี้วัด');
$sheet2->mergeCells('B' . $row . ':G' . ($row + 1)); 

// [แก้ไข] เกณฑ์ ขยับมา H-L
$sheet2->setCellValue('H' . $row, 'เกณฑ์การให้คะแนนตามผลงานที่ทำได้');
$sheet2->mergeCells('H' . $row . ':L' . $row); 

// [แก้ไข] ผลงาน ขยับมา M-P
$sheet2->setCellValue('M' . $row, "ผลงานที่ทำได้จริงเทียบกับเป้าหมายมาตรฐานผลงาน\nผลคะแนนที่ได้ตามเกณฑ์ (1-10)"); 
$sheet2->mergeCells('M' . $row . ':P' . $row); 

$sheet2->getRowDimension($row)->setRowHeight(40);
$row++;

// Sub-header แถว 2
$sheet2->setCellValue('H' . $row, '1');
$sheet2->setCellValue('I' . $row, '2');
$sheet2->setCellValue('J' . $row, '3');
$sheet2->setCellValue('K' . $row, '4');
$sheet2->setCellValue('L' . $row, '5');

// [แก้ไข] แบ่ง M-N เป็น ตนเอง (2 ช่อง)
$sheet2->setCellValue('M' . $row, "จากตนเอง");
$sheet2->mergeCells('M' . $row . ':N' . $row); 

// [แก้ไข] แบ่ง O-P เป็น ผู้บังคับบัญชา (2 ช่อง)
$sheet2->setCellValue('O' . $row, "จากผู้บังคับบัญชา");
$sheet2->mergeCells('O' . $row . ':P' . $row); 

$sheet2->getStyle('B' . $start_head_row . ':P' . $row)->applyFromArray($style_sec2_subhead_peach);
$row++;

$supervisor_score_rows = []; 

// --- 4. Loop ข้อมูล ---
$sql_head = "SELECT * FROM excel_head_topic ORDER BY No ASC"; 
$stmt_head = $conn->prepare($sql_head);
$stmt_head->execute();
$result_head = $stmt_head->get_result();

if ($result_head) {
    while ($head_row = $result_head->fetch_assoc()) {
        $head_text = $head_row['No'] . '. ' . $head_row['head_topic_ename'] . ' - ' . $head_row['head_topic_tname'];
        
        // B-P
        $sheet2->setCellValue('B' . $row, $head_text);
        $sheet2->mergeCells('B' . $row . ':P' . $row); 
        $sheet2->getStyle('B' . $row . ':P' . $row)->applyFromArray($style_sec2_topic_head_bg);
        $row++;

        $start_sub_row = $row;
        $sub_topic_count = 0;

        $current_head_id = $head_row['head_topic_id'];
        
        $sql_topic = "SELECT * FROM excel_topic WHERE head_topic_id = ? ORDER BY No ASC";
        $stmt_topic = $conn->prepare($sql_topic);
        $stmt_topic->bind_param("i", $current_head_id);
        $stmt_topic->execute();
        $result_topic = $stmt_topic->get_result();

        if ($result_topic) {
            while ($topic_row = $result_topic->fetch_assoc()) {
                $sub_topic_count++;
                
                // 1. เตรียมข้อความ
                $topic_display = $topic_row['No'] . " " . $topic_row['topic_tname'] . " / " . $topic_row['topic_ename'] . "\n";
                $topic_display .= "นิยาม: " . $topic_row['Description_th'] . "\n";
                $topic_display .= $topic_row['Description_en'];

                // 2. แสดงผลลง Cell (B-G)
                $sheet2->setCellValue('B' . $row, $topic_display);
                $sheet2->mergeCells('B' . $row . ':G' . $row);
                $sheet2->getStyle('B' . $row . ':G' . $row)->applyFromArray($style_sec2_text_left);

                // --- สูตรคำนวณความสูง (เหมือนเดิม) ---
                $lines_array = explode("\n", $topic_display);
                $count_visual_lines = 0;
                $chars_per_line = 90; 

                foreach ($lines_array as $line_text) {
                    $clean_text = preg_replace('/[่-๋์็]/u', '', $line_text);
                    $len = mb_strlen($clean_text);
                    if ($len == 0) {
                        $count_visual_lines += 1;
                    } else {
                        $count_visual_lines += ceil($len / $chars_per_line);
                    }
                }

                $height_per_line = 22; 
                $padding = 10; 
                $final_height = ($count_visual_lines * $height_per_line) + $padding;
                $sheet2->getRowDimension($row)->setRowHeight($final_height);
                $supervisor_score_rows[] = $row; 

                $row++;
            }
        }
        $stmt_topic->close();

        // Merge แนวตั้ง H-P
        $end_sub_row = $row - 1; 

        if ($sub_topic_count > 0) {
            // H-L (Criteria 1-5)
            $sheet2->mergeCells('H' . $start_sub_row . ':H' . $end_sub_row);
            $sheet2->setCellValue('H' . $start_sub_row, "ไม่ผ่านทุกข้อ\nหรือเกือบทั้งหมด");
            
            $sheet2->mergeCells('I' . $start_sub_row . ':I' . $end_sub_row);
            $sheet2->setCellValue('I' . $start_sub_row, "ไม่ผ่านบางข้อ");

            $sheet2->mergeCells('J' . $start_sub_row . ':J' . $end_sub_row);
            $sheet2->setCellValue('J' . $start_sub_row, "ผ่านทุกข้อ");

            $sheet2->mergeCells('K' . $start_sub_row . ':K' . $end_sub_row);
            $sheet2->setCellValue('K' . $start_sub_row, "ผ่านทุกข้อและ\nบางข้อดีกว่าที่คาดหวัง");

            $sheet2->mergeCells('L' . $start_sub_row . ':L' . $end_sub_row);
            $sheet2->setCellValue('L' . $start_sub_row, "ผ่านทุกข้อและ\nดีกว่าที่คาดหวัง");

            // M-N (ตนเอง)
            $sheet2->mergeCells('M' . $start_sub_row . ':N' . $end_sub_row);
            
            // O-P (หัวหน้า)
            // *หมายเหตุ: ตรงนี้คือช่องที่เราจะดึงค่าไปคำนวณ
            $sheet2->mergeCells('O' . $start_sub_row . ':P' . $end_sub_row);

            $sheet2->getStyle('H' . $start_sub_row . ':P' . $end_sub_row)->applyFromArray($style_sec2_criteria_merged);
        }

        // --- Footer เหตุผล ---
        // 1. เหตุผลตนเอง
        $sheet2->setCellValue('B' . $row, "อธิบายเหตุผลการให้คะแนนจากประเมินของตนเอง");
        $sheet2->mergeCells('B' . $row . ':G' . $row);
        $sheet2->getStyle('B' . $row . ':G' . $row)->applyFromArray($style_reason_label_gray);

        $sheet2->mergeCells('H' . $row . ':P' . $row);
        $sheet2->getStyle('H' . $row . ':P' . $row)->applyFromArray($style_sec2_yellow_bg);
        $sheet2->getRowDimension($row)->setRowHeight(40); 
        $row++;

        // 2. เหตุผลผู้บังคับบัญชา
        $sheet2->setCellValue('B' . $row, "อธิบายเหตุผลการให้คะแนนจากประเมินของผู้บังคับบัญชา");
        $sheet2->mergeCells('B' . $row . ':G' . $row);
        $sheet2->getStyle('B' . $row . ':G' . $row)->applyFromArray($style_reason_label_gray);

        $sheet2->mergeCells('H' . $row . ':P' . $row);
        $sheet2->getStyle('H' . $row . ':P' . $row)->applyFromArray($style_sec2_yellow_bg);
        $sheet2->getRowDimension($row)->setRowHeight(40);
        $row++;
    }
}
$stmt_head->close();

// ===================================================================
// SECTION 3: Competency Summary (สรุปคะแนนสมรรถนะ + สูตรคำนวณ)
// ===================================================================
$row++;

// --- 1. กำหนด Style (เพิ่มใหม่สำหรับส่วนนี้) ---
$style_comp_header_orange = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FF000000']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFED7D31']], 
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$style_comp_desc_white = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']], 
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$style_comp_score_orange = [
    'font' => ['bold' => true, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFED7D31']], 
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

// --- 2. ส่วนหัวตาราง ---
// B-K
$sheet2->setCellValue('B' . $row, 'Measure/ Target / ตัวชี้วัด');
$sheet2->mergeCells('B' . $row . ':K' . $row);

// L-P
$sheet2->setCellValue('L' . $row, 'คะแนน');
$sheet2->mergeCells('L' . $row . ':P' . $row);

$sheet2->getStyle('B' . $row . ':P' . $row)->applyFromArray($style_comp_header_orange);
$sheet2->getRowDimension($row)->setRowHeight(35);

$row++;

// --- 3. ส่วนเนื้อหา ---
$start_comp_row = $row;

// [ซ้ายบรรทัดที่ 1]
$sheet2->setCellValue('B' . $row, 'Competency (สมรรถนะ) การประเมิน "พฤติกรรมและวิธีการทำงาน"');
$sheet2->mergeCells('B' . $row . ':K' . $row);
$sheet2->getStyle('B' . $row . ':K' . $row)->applyFromArray($style_comp_desc_white);
$sheet2->getRowDimension($row)->setRowHeight(30);

$row++;

// [ซ้ายบรรทัดที่ 2]
$sheet2->setCellValue('B' . $row, 'Score /คะแนนเต็ม (50 คะแนน)');
$sheet2->mergeCells('B' . $row . ':K' . $row);
$sheet2->getStyle('B' . $row . ':K' . $row)->applyFromArray($style_comp_desc_white);
$sheet2->getStyle('B' . $row)->getFont()->getColor()->setARGB('FFFF0000'); 
$sheet2->getRowDimension($row)->setRowHeight(30);


$formula_str = "0";

if (!empty($supervisor_score_rows)) {
    // แปลงเลขแถวเป็น Cell Reference (เช่น O45, O48)
    // ใช้คอลัมน์ O เพราะเป็นคอลัมน์แรกของกลุ่ม Merge O-P
    $cell_refs = array_map(function($r) {
        return "O" . $r; 
    }, $supervisor_score_rows);
    
    // เชื่อมด้วยเครื่องหมาย +
    $sum_part = implode('+', $cell_refs);
    
    // สร้างสูตรสมบูรณ์
    $formula_str = "=(" . $sum_part . ")*2";
}

$sheet2->setCellValue('L' . $start_comp_row, $formula_str);
$sheet2->mergeCells('L' . $start_comp_row . ':P' . $row);
$sheet2->getStyle('L' . $start_comp_row . ':P' . $row)->applyFromArray($style_comp_score_orange);

$cell_ref_competency_raw = 'L' . $start_comp_row; 

$row++;

// ===================================================================
// SECTION 4: Final Summary (สรุปผลการประเมินทั้งปี)
// ===================================================================
$row++; // เว้นบรรทัดจาก Section 3

// --- 1. กำหนด Style ---
$style_final_header_blue = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF3030FF']], // น้ำเงินสด
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$style_final_subhead_white = [
    'font' => ['size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']], // ขาว
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$style_final_label_left = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']], // ขาว
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$style_final_score_orange = [
    'font' => ['bold' => true, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFED7D31']], // ส้มเข้ม
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$style_final_text_center = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']], // ขาว
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$style_final_percent = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']], // ขาว
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];


// --- 2. Header (สีน้ำเงิน) ---
$sheet2->setCellValue('B' . $row, 'สรุปผลการประเมินทั้งปี');
$sheet2->mergeCells('B' . $row . ':P' . $row);
$sheet2->getStyle('B' . $row . ':P' . $row)->applyFromArray($style_final_header_blue);
$sheet2->getRowDimension($row)->setRowHeight(35);
$row++;

// --- 3. Sub-Header (คำอธิบาย) ---
$sheet2->setCellValue('B' . $row, "ผลการประเมินความสามารถและสมรรถนะทั้งปี\n(คะแนนทั้งหมดรวมกันหารด้วยจำนวนข้อที่ประเมิน)");
$sheet2->mergeCells('B' . $row . ':P' . $row);
$sheet2->getStyle('B' . $row . ':P' . $row)->applyFromArray($style_final_subhead_white);
$sheet2->getStyle('B' . $row)->getAlignment()->setWrapText(true); // ตัดบรรทัด
$sheet2->getRowDimension($row)->setRowHeight(40);
$row++;

// --- 4. Row 1: ส่วนที่ 1 (KPIs) ---
// B-D: Label
$sheet2->setCellValue('B' . $row, 'ส่วนที่ 1. การประเมินผลตามเป้าหมายที่กำหนด (KPIs)');
$sheet2->mergeCells('B' . $row . ':D' . $row);
$sheet2->getStyle('B' . $row . ':D' . $row)->applyFromArray($style_final_label_left);

// E-F: 500 (ส้ม)
// ดึงค่าคะแนนเต็ม 500 จาก DB (Score_500_max) มาโชว์
$score_part1_500 = $saved_total_scores['Score_500_max'] ?? '0';
$sheet2->setCellValue('E' . $row, $score_part1_500);
$sheet2->mergeCells('E' . $row . ':F' . $row);
$sheet2->getStyle('E' . $row . ':F' . $row)->applyFromArray($style_final_score_orange);

// G-I: ข้อความ
$sheet2->setCellValue('G' . $row, 'คะแนน  ของคะแนนเต็ม 500 คะแนน');
$sheet2->mergeCells('G' . $row . ':I' . $row);
$sheet2->getStyle('G' . $row . ':I' . $row)->applyFromArray($style_final_text_center);

// J-L: ข้อความ (อัตราส่วน 80)
$sheet2->setCellValue('J' . $row, 'คะแนนของส่วนที่ 1 (อัตราส่วน 80) =');
$sheet2->mergeCells('J' . $row . ':L' . $row);
$sheet2->getStyle('J' . $row . ':L' . $row)->applyFromArray($style_final_text_center);
// ทำสีน้ำเงินให้คำว่า (อัตราส่วน 80) - *ทำยากใน Cell เดียว ข้ามไปก่อน เอาเป็น text ธรรมดา

// [M-O] Score 80%
$score_part1_80 = $saved_total_scores['Score_100_max'] ?? '0';
$sheet2->setCellValue('M' . $row, $score_part1_80);
$sheet2->mergeCells('M' . $row . ':O' . $row);
$sheet2->getStyle('M' . $row . ':O' . $row)->applyFromArray($style_final_score_orange);

// [สำคัญมาก!] จำตำแหน่งเซลล์คะแนน KPI 80% ไว้
$cell_ref_kpi_total = 'M' . $row; 

// P: %
$sheet2->setCellValue('P' . $row, '%');
$sheet2->getStyle('P' . $row)->applyFromArray($style_final_percent);

$sheet2->getRowDimension($row)->setRowHeight(35);
$row++;

// --- 5. Row 2: ส่วนที่ 2 (Competency) ---
// B-D: Label
$sheet2->setCellValue('B' . $row, 'ส่วนที่ 2. การประเมินสมรรถนะ (Competency)');
$sheet2->mergeCells('B' . $row . ':D' . $row);
$sheet2->getStyle('B' . $row . ':D' . $row)->applyFromArray($style_final_label_left);

// [E-F] คะแนนดิบ (ดึงจาก Section 3)
// ใช้ตัวแปร $cell_ref_competency_raw ที่เราจำไว้ข้างบน
$sheet2->setCellValue('E' . $row, "=$cell_ref_competency_raw"); 
$sheet2->mergeCells('E' . $row . ':F' . $row);
$sheet2->getStyle('E' . $row . ':F' . $row)->applyFromArray($style_final_score_orange);

// [สำคัญมาก!] จำตำแหน่งเซลล์คะแนนดิบแถวนี้ไว้ (เพื่อใช้คำนวณ 20%)
$cell_ref_comp_row_raw = 'E' . $row;

// G-I: ข้อความ
$sheet2->setCellValue('G' . $row, 'คะแนน  ของคะแนนเต็ม 50 คะแนน');
$sheet2->mergeCells('G' . $row . ':I' . $row);
$sheet2->getStyle('G' . $row . ':I' . $row)->applyFromArray($style_final_text_center);

// J-L: ข้อความ (อัตราส่วน 20)
$sheet2->setCellValue('J' . $row, 'คะแนนของส่วนที่ 2 (อัตราส่วน 20) =');
$sheet2->mergeCells('J' . $row . ':L' . $row);
$sheet2->getStyle('J' . $row . ':L' . $row)->applyFromArray($style_final_text_center);

// [M-O] Score 20%
// คำนวณจากช่องคะแนนดิบด้านหน้า * 0.4
$sheet2->setCellValue('M' . $row, "=$cell_ref_comp_row_raw * 0.4");
$sheet2->mergeCells('M' . $row . ':O' . $row);
$sheet2->getStyle('M' . $row . ':O' . $row)->applyFromArray($style_final_score_orange);

// [สำคัญมาก!] จำตำแหน่งเซลล์คะแนน Competency 20% ไว้
$cell_ref_comp_total = 'M' . $row;

// P: %
$sheet2->setCellValue('P' . $row, '%');
$sheet2->getStyle('P' . $row)->applyFromArray($style_final_percent);

$sheet2->getRowDimension($row)->setRowHeight(35);
$row++;

// --- 6. Footer: รวม (ส่วนที่ 1 + ส่วนที่ 2) ---
// B-L: Label (น้ำเงิน)
$sheet2->setCellValue('B' . $row, 'รวม (ส่วนที่ 1 + ส่วนที่ 2) =');
$sheet2->mergeCells('B' . $row . ':L' . $row);
$sheet2->getStyle('B' . $row . ':L' . $row)->applyFromArray($style_final_header_blue);
$sheet2->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // ชิดขวา

// [M-O] ผลรวม (ส้ม)
// ใช้ตัวแปรที่เราจำไว้ 2 ตัวมาบวกกัน
$sheet2->setCellValue('M' . $row, "=$cell_ref_kpi_total + $cell_ref_comp_total");
$sheet2->mergeCells('M' . $row . ':O' . $row);
$sheet2->getStyle('M' . $row . ':O' . $row)->applyFromArray($style_final_score_orange);

// P: % (ขาว)
$sheet2->setCellValue('P' . $row, '%');
$sheet2->getStyle('P' . $row)->applyFromArray($style_final_percent);

$sheet2->getRowDimension($row)->setRowHeight(35);
$row++;

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