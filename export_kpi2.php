<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. โหลด Library และการเชื่อมต่อ
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
    // กรณีดูของคนอื่น (ส่งมาจาก GET)
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

    // [แก้ไขจุดที่ 1] เพิ่ม Advice ใน SELECT
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

$saved_okr_data = [];

$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
    ->setCreator("KPI System")
    ->setTitle("KPI Report " . $current_emp_code . " ปี " . $current_academic_year)
    ->setSubject("KPI Report");

// ตั้งค่า Font เริ่มต้น
$spreadsheet->getDefaultStyle()->getFont()->setName('TH Sarabun New')->setSize(16);

//  กำหนด Style Arrays ที่จะใช้ (จากโค้ดเดิมและโค้ดที่ผู้ใช้ให้มา)
$style_title_array = [
    'font' => ['bold' => true, 'size' => 18], // 18-20 is good
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
];

// (ใช้ชื่อนี้เพื่อให้ตรงกับโค้ด Tab 2 และ Tab 3 ที่มีอยู่)
$header_style_array = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']]
];
$tab1_header_style_array = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FF000000']], // สีตัวอักษรดำ
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2EFDA']] // สีเขียวอ่อนตามภาพ
];
// [START] (*** EDITED ***)
$tab3_header_style_array = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], // White text
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4F81BD']] // Dark Blue
];
// [END] (*** EDITED ***)
$style_align_center_array = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
];

$style_all_borders_array = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

//  เพิ่ม: Styles ที่จำเป็นสำหรับ Tab 3
$style_align_wrap_text_array = [
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_LEFT,
        'vertical' => Alignment::VERTICAL_TOP,
        'wrapText' => true
    ]
];

// (เหมือน $header_style_array)
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

// [START] (*** NEW ***) Add Styles for Rating Table
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


$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('1.หัวข้อตัวชี้วัด');

// --- ส่วนหัวเรื่อง ---
$sheet1->setCellValue('A1', "การจัดทำหัวข้อตัวชี้วัดผลสำเร็จของงาน (KPIs) และการประเมินระดับความสำคัญของตัวชี้วัด ประจำปีการศึกษา $current_academic_year");
$sheet1->mergeCells('A1:G1');
$sheet1->getStyle('A1')->applyFromArray($style_title_array);
$sheet1->setCellValue('A2', 'วัตถุประสงค์ : เพื่อพนักงานสามารถทราบถึงเหตุและผลของการจัดทำตัวชี้วัดผลสำเร็จของงาน (KPIs) ให้สอดคล้อง และเชื่อมโยงกับความสำเร็จขององค์กร และหน่วยงาน อย่างมีประสิทธิผลในการดำเนินการสูงสุด');
$sheet1->mergeCells('A2:G2');
$sheet1->getStyle('A2')->getAlignment()->setWrapText(true);

// --- ส่วนหัวตาราง (แบบใหม่ 2 แถว) ---
// แถวบน (Row 4)
$sheet1->setCellValue('A4', "ตัวชี้วัดผลสำเร็จของงาน\n(Key Performance Indicators: KPIs)");
$sheet1->mergeCells('A4:D4');
$sheet1->setCellValue('E4', "ระดับความสำคัญและมีความเชื่อมโยงส่งผลกระทบ\nต่อความสำเร็จขององค์กร\n(ประเภท/เป้าหมาย/ตัวชี้วัด)");
$sheet1->mergeCells('E4:G4');

// แถวล่าง (Row 5)
$sheet1->setCellValue('A5', 'ลำดับ');
$sheet1->setCellValue('B5', 'ประเภทตัวชี้วัด');
// [START] (*** EDITED ***)
$sheet1->setCellValue('C5', "น้ำหนัก"); // แก้ไขข้อความ C5
// [END] (*** EDITED ***)
$sheet1->setCellValue('D5', 'หัวข้อตัวชี้วัด');
$sheet1->setCellValue('E5', 'น้อย');
$sheet1->setCellValue('F5', 'ปานกลาง');
$sheet1->setCellValue('G5', 'มาก');

// ใช้ Style สีเขียว (ที่สร้างไว้) กับหัวตารางทั้งหมด A4:G5
$sheet1->getStyle('A4:G5')->applyFromArray($tab1_header_style_array);
// จัดข้อความแถวบน (A4, E4) ให้อยู่ตรงกลาง ( style เดิมจัดกลางอยู่แล้ว แต่กันเหนียว)
$sheet1->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet1->getStyle('E4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// --- ตั้งค่าความกว้างคอลัมน์ (Tab 1) ---
$sheet1->getColumnDimension('A')->setWidth(10);
$sheet1->getColumnDimension('B')->setWidth(30);
$sheet1->getColumnDimension('C')->setWidth(15);
$sheet1->getColumnDimension('D')->setWidth(100);
$sheet1->getColumnDimension('E')->setWidth(10);
$sheet1->getColumnDimension('F')->setWidth(10);
$sheet1->getColumnDimension('G')->setWidth(10);
$sheet1->getRowDimension(4)->setRowHeight(60); // เพิ่มความสูงให้แถวบนของหัวตาราง
$sheet1->getRowDimension(5)->setRowHeight(30); // แถวล่างของหัวตาราง


$row = 6; // <-- แก้ไขแถวเริ่มต้นข้อมูลเป็น 6
// [END] (*** EDITED ***)
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
                $sheet1->setCellValue('D' . $row, htmlspecialchars($topic['name']));
                $imp_level = $topic['Important_level_no'];

                if ($imp_level == 1) {
                    $sheet1->setCellValue('E' . $row, '✓');
                } elseif ($imp_level == 2) {
                    $sheet1->setCellValue('F' . $row, '✓');
                } elseif ($imp_level == 3) {
                    $sheet1->setCellValue('G' . $row, '✓');
                }

                $sheet1->getStyle('E' . $row . ':G' . $row)->applyFromArray($style_align_center_array);
                $sheet1->getStyle('A' . $row . ':G' . $row)->applyFromArray($style_all_borders_array);
                $row++;
            }
            $end_row_of_group = $row - 1;

            // 1. จัดตำแหน่งแนวตั้ง (ชิดบน) ให้กับคอลัมน์ A, B, C ของทั้งกลุ่ม (ไม่ว่าจะมี 1 แถวหรือหลายแถว)
            $sheet1->getStyle("A{$current_type_start_row}:C{$end_row_of_group}")
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP); // จัดชิดบน

            // 2. จัดตำแหน่งแนวนอน (กึ่งกลาง) ให้กับ A และ C (ไม่ว่าจะมี 1 แถวหรือหลายแถว)
            $sheet1->getStyle("A{$current_type_start_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("C{$current_type_start_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // 3. ถ้ามีมากกว่า 1 แถว (rowspan > 1) ค่อยทำการผสานเซลล์
            if ($rowspan > 1) {
                $sheet1->mergeCells("A{$current_type_start_row}:A{$end_row_of_group}");
                $sheet1->mergeCells("B{$current_type_start_row}:B{$end_row_of_group}");
                $sheet1->mergeCells("C{$current_type_start_row}:C{$end_row_of_group}");
            }
        } else {
            $sheet1->setCellValue('A' . $row, htmlspecialchars($kpi_type['Order_No']));
            $sheet1->setCellValue('B' . $row, htmlspecialchars($kpi_type['KPI_Type_Name_EN']));
            $sheet1->setCellValue('C' . $row, htmlspecialchars($kpi_type['TypeWeight']));
            $sheet1->setCellValue('D' . $row, '-- ไม่มีหัวข้อตัวชี้วัด --');
            $sheet1->mergeCells('D' . $row . ':G' . $row);
            $sheet1->getStyle('A' . $row . ':G' . $row)->applyFromArray($style_all_borders_array);
            $sheet1->getStyle('A' . $row . ':C' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $sheet1->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }
    }
} else {
    // [START] (*** EDITED ***)
    $sheet1->setCellValue('A6', '--- ไม่พบข้อมูลตัวชี้วัดสำหรับผู้ใช้และปีการศึกษานี้ ---'); // <-- แก้ไขแถวเป็น A6
    $sheet1->mergeCells('A6:G6'); // <-- แก้ไขแถวเป็น A6:G6
    $sheet1->getStyle('A6:G6')->applyFromArray($style_all_borders_array); // <-- แก้ไขแถวเป็น A6:G6
    // [END] (*** EDITED ***)
}



// === [ TAB 2: 2.แบบกำหนดรายละเอียด KPIs ] ===
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('2.แบบกำหนดรายละเอียด KPIs');

// +++ [START] NEW TABLE FROM IMAGE (เพิ่มส่วนนี้) +++
$row = 1;

// --- Define Styles for this new table ---
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
$style_review_label_beige = [
    'font' => ['bold' => true], // (เพิ่ม Bold)
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0E5D1']] // Beige
];
$style_review_box_beige = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0E5D1']] // Beige
];
$style_review_label_yellow = [
    'font' => ['bold' => true], // (เพิ่ม Bold)
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF7CC']] // Light Yellow
];
$style_review_box_yellow = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF7CC']] // Light Yellow
];

// [START] (*** NEW ***) Modify merge ranges to span A-O
// --- Row 1: Signature Headers (Beige) ---
$sheet2->setCellValue('A' . $row, 'รอบการประเมิน');
$sheet2->mergeCells('A' . $row . ':C' . $row); // 3 cols
$sheet2->setCellValue('D' . $row, 'ลายมือชื่อพนักงาน');
$sheet2->mergeCells('D' . $row . ':H' . $row); // 5 cols
$sheet2->setCellValue('I' . $row, 'ลายมือชื่อผู้บังคับบัญชา');
$sheet2->mergeCells('I' . $row . ':O' . $row); // 7 cols
$sheet2->getStyle('A' . $row . ':O' . $row)->applyFromArray($style_sig_header); // Span A-O
$sheet2->getRowDimension($row)->setRowHeight(30);
$row++; // $row = 2

// --- Row 2: Mid-Year (Yellow) ---
$sheet2->setCellValue('A' . $row, 'การประเมินผลกลางปี (เดือนมกราคม 2568)');
$sheet2->mergeCells('A' . $row . ':C' . $row); // 3 cols
$sheet2->getStyle('A' . $row . ':C' . $row)->applyFromArray($style_sig_label_yellow);
$sheet2->mergeCells('D' . $row . ':H' . $row); // 5 cols (Empty box 1)
$sheet2->getStyle('D' . $row . ':H' . $row)->applyFromArray($style_sig_box_yellow);
$sheet2->mergeCells('I' . $row . ':O' . $row); // 7 cols (Empty box 2)
$sheet2->getStyle('I' . $row . ':O' . $row)->applyFromArray($style_sig_box_yellow);
$sheet2->getRowDimension($row)->setRowHeight(50); // Taller box
$row++; // $row = 3

// --- Row 3: End-Year (Yellow) ---
$sheet2->setCellValue('A' . $row, 'การประเมินผลปลายปี (เดือนกรกฎาคม 2568)');
$sheet2->mergeCells('A' . $row . ':C' . $row); // 3 cols
$sheet2->getStyle('A' . $row . ':C' . $row)->applyFromArray($style_sig_label_yellow);
$sheet2->mergeCells('D' . $row . ':H' . $row); // 5 cols (Empty box 1)
$sheet2->getStyle('D' . $row . ':H' . $row)->applyFromArray($style_sig_box_yellow);
$sheet2->mergeCells('I' . $row . ':O' . $row); // 7 cols (Empty box 2)
$sheet2->getStyle('I' . $row . ':O' . $row)->applyFromArray($style_sig_box_yellow);
$sheet2->getRowDimension($row)->setRowHeight(50); // Taller box
$row++; // $row = 4

// --- Row 4: Blank row + Start of Review Block ( *** START EDIT *** )
// [START] (*** NEW ***) Set row height to match 130 total and align to I-O
$sheet2->setCellValue('I' . $row, 'ลงนาม'); // Label in I
$sheet2->getStyle('I' . $row)->applyFromArray($style_review_label_yellow);
$sheet2->mergeCells('J' . $row . ':O' . $row); // Box in J-O (6 cols)
$sheet2->getStyle('J' . $row . ':O' . $row)->applyFromArray($style_review_box_yellow);
$sheet2->getRowDimension($row)->setRowHeight(32.5); // (130 / 4)
$row++; // $row = 5

// --- Row 5: Review Block ---
$sheet2->setCellValue('I' . $row, 'ชื่อ-สกุล'); // Label in I
$sheet2->getStyle('I' . $row)->applyFromArray($style_review_label_yellow);
$sheet2->mergeCells('J' . $row . ':O' . $row); // Box in J-O
$sheet2->getStyle('J' . $row . ':O' . $row)->applyFromArray($style_review_box_yellow);
$sheet2->getRowDimension($row)->setRowHeight(32.5); // (130 / 4)
$row++; // $row = 6

// --- Row 6: Review Block ---
$sheet2->setCellValue('I' . $row, 'ตำแหน่ง'); // Label in I
$sheet2->getStyle('I' . $row)->applyFromArray($style_review_label_yellow);
$sheet2->mergeCells('J' . $row . ':O' . $row); // Box in J-O
$sheet2->getStyle('J' . $row . ':O' . $row)->applyFromArray($style_review_box_yellow);
$sheet2->getRowDimension($row)->setRowHeight(32.5); // (130 / 4)
$row++; // $row = 7

// --- Row 7: (*** NEW ***) Add centered text for Supervisor Name ---
$sheet2->setCellValue('J' . $row, 'ชื่อผู้บังคับบัญชา'); // Text in J-O
$sheet2->mergeCells('J' . $row . ':O' . $row); // Merge J-O
$sheet2->getStyle('J' . $row . ':O' . $row)->applyFromArray($style_align_center_array); // จัดกลาง
$sheet2->getStyle('J' . $row . ':O' . $row)->getFont()->setBold(true); // ทำตัวหนา
$sheet2->getRowDimension($row)->setRowHeight(32.5); // (130 / 4)
$row++; // $row = 8
// [END] (*** NEW ***)
// --- ( *** END EDIT *** ) ---


// [START] (*** NEW ***) Add Rating Scale Table from Image (แทนที่ $row++;)
$row = 9; // Start at row 9

// --- Row 1: Orange Title Bar ---
$sheet2->setCellValue('A' . $row, 'ส่วนที่ 1 : SECTION FIRST-GOAL SETTING AND EVALUATION/การประเมินผลตามเป้าหมายที่กำหนด');
$sheet2->mergeCells('A' . $row . ':O' . $row); // Span A-O
$sheet2->getStyle('A' . $row . ':O' . $row)->applyFromArray($style_rating_title_orange);
$sheet2->getRowDimension($row)->setRowHeight(30);
$row++; // $row = 10

// --- Row 2: Light Blue Subtitle Bar ---
$sheet2->setCellValue('A' . $row, 'เกณฑ์การให้คะแนนและผลการประเมิน / Annual Review Rating');
$sheet2->mergeCells('A' . $row . ':O' . $row); // Span A-O
$sheet2->getStyle('A' . $row . ':O' . $row)->applyFromArray($style_rating_subtitle_blue);
$sheet2->getRowDimension($row)->setRowHeight(30);
$row++; // $row = 11

// --- Row 3: Grey Rating Boxes ---
$box1_text = "1\nสำเร็จตามเป้าหมายน้อยมาก\nต่ำกว่า 40%\nVery Low Achievement";
$box2_text = "2\nสำเร็จตามเป้าหมายน้อย\n40%-59%\nUnder Achievement";
$box3_text = "3\nสำเร็จตามเป้าหมายพอควร\n60%-79%\nPartial Achievement";
$box4_text = "4\nสำเร็จตามเป้าหมายส่วนใหญ่\n80%-99%\nNearly Achievement of Target";
$box5_text = "5\nสำเร็จตามเป้าหมาย\n100%\nAchievement of Target";

// Box 1 (A-B)
$sheet2->setCellValue('A' . $row, $box1_text);
$sheet2->mergeCells('A' . $row . ':B' . $row); // 2 cols
// Box 2 (C-E)
$sheet2->setCellValue('C' . $row, $box2_text);
$sheet2->mergeCells('C' . $row . ':E' . $row); // 3 cols
// Box 3 (F-H)
$sheet2->setCellValue('F' . $row, $box3_text);
$sheet2->mergeCells('F' . $row . ':H' . $row); // 3 cols
// Box 4 (I-K)
$sheet2->setCellValue('I' . $row, $box4_text);
$sheet2->mergeCells('I' . $row . ':K' . $row); // 3 cols
// Box 5 (L-O)
$sheet2->setCellValue('L' . $row, $box5_text);
$sheet2->mergeCells('L' . $row . ':O' . $row); // 4 cols

// Apply style to all boxes
$sheet2->getStyle('A' . $row . ':O' . $row)->applyFromArray($style_rating_box_grey); // Span A-O
$sheet2->getRowDimension($row)->setRowHeight(100); // Set height for 4 lines of text

$row++; // $row = 12


$row++;


$row_num = $row; // (แก้ไข) เริ่มตารางหลักที่แถวถัดไป (ตอนนี้ $row = 13)
$sheet2->setCellValue('A' . $row_num, 'ลำดับ');
$sheet2->setCellValue('B' . $row_num, 'ตัวชี้วัด (KPIs)');
$sheet2->setCellValue('C' . $row_num, 'หน่วยนับ');
$sheet2->setCellValue('D' . $row_num, 'เป้าหมาย');
$sheet2->setCellValue('E' . $row_num, 'เกณฑ์การให้คะแนน');
$sheet2->setCellValue('F' . $row_num, 'น้ำหนัก');
$sheet2->setCellValue('G' . $row_num, 'เป้าหมายครึ่งปีแรก'); // (*** NEW COLUMN ***)
$sheet2->setCellValue('H' . $row_num, 'ผลงานครึ่งปีแรก'); // (was G)
$sheet2->setCellValue('I' . $row_num, 'เป้าหมายครึ่งปีหลัง'); // (was H)
$sheet2->setCellValue('J' . $row_num, 'ผลงานครึ่งปีหลัง'); // (was I)
$sheet2->setCellValue('K' . $row_num, 'ผลงานรวม'); // (was J)
$sheet2->setCellValue('L' . $row_num, 'ค่าน้ำหนัก'); // (was K)
$sheet2->setCellValue('M' . $row_num, 'ผลคะแนน'); // (was L)
$sheet2->setCellValue('N' . $row_num, 'คะแนนรวมน้ำหนักxผลคะแนน'); // (was M)
$sheet2->setCellValue('O' . $row_num, 'หมายเหตุ'); // (was N)
//  แก้ไข: ปรับ range
$sheet2->getStyle('A' . $row_num . ':O' . $row_num)->applyFromArray($header_style_array); // (A-O)
$sheet2->getRowDimension($row_num)->setRowHeight(60); // (Increased height)



// ลูปข้อมูลผลงาน (จาก $kpi_data และ $saved_review_data)
$row_num = $row_num + 1;
$data_start_row = $row_num;
$type_order = 1;
foreach ($kpi_data as $type_id => $kpi_type) {
    // [START] (*** EDITED ***)
    // ชื่อหมวด
    $sheet2->setCellValue('A' . $row_num, $type_order . '.');

    // 1. Merge Title (B-E)
    $sheet2->mergeCells('B' . $row_num . ':E' . $row_num);
    $sheet2->setCellValue('B' . $row_num, ' ' . $kpi_type['KPI_Type_Name_TH']);

    // 2. Set Value for Weight (F)
    $sheet2->setCellValue('F' . $row_num, $kpi_type['TypeWeight']);

    // 3. Merge empty space (G-N)
    $sheet2->mergeCells('G' . $row_num . ':N' . $row_num);
    $sheet2->setCellValue('G' . $row_num, ''); // Set empty string for merged cell

    // 4. Style A (Order No)
    $sheet2->getStyle('A' . $row_num)->getFont()->setBold(true)->setSize(20);
    $sheet2->getStyle('A' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // 5. Style B-E (Title)
    $sheet2->getStyle('B' . $row_num . ':E' . $row_num)->getFont()->setBold(true)->setSize(20);

    // 6. Style F (Weight)
    $sheet2->getStyle('F' . $row_num)->getFont()->setBold(true)->setSize(20);
    $sheet2->getStyle('F' . $row_num)->applyFromArray($style_align_center_array);

    // 7. Style O (Remarks) - remains empty
    $sheet2->setCellValue('O' . $row_num, ''); // (was N) ช่องหมายเหตุของหมวด

    // 8. Style Full Row Background Color
    $sheet2->getStyle('A' . $row_num . ':O' . $row_num)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDAEEF3'); // (*** EDITED ***) Was FFF2F2F2 (light grey), now light blue
    // [END] (*** EDITED ***)

    $row_num++;
    $type_order++;

    if (!empty($kpi_type['topics'])) {
        foreach ($kpi_type['topics'] as $topic_id => $topic) {

            $saved_h1 = $saved_review_data[$topic_id . "_1"] ?? null;
            $saved_h2 = $saved_review_data[$topic_id . "_2"] ?? null;

            // --- [1] เตรียมข้อมูลชื่อ KPI และ รายละเอียดเพิ่มเติม ---
            $kpi_name_display = $topic['name'];

            // ตรวจสอบว่าต้องมี Additional หรือไม่
            if (isset($topic['Additional']) && $topic['Additional'] === 'yes') {
                $add_text = $saved_h2['Additional'] ?? ($saved_h1['Additional'] ?? '-');
                // นำข้อความมาต่อท้ายในตัวแปรเดียวกัน (ใช้ \n เพื่อขึ้นบรรทัดใหม่ใน Excel)
                $kpi_name_display .= "\n\nคณะกรรมการชุดที่ :\n(ระบุ).................................................................." . $add_text;
            }

            // --- [2] เขียนข้อมูลลงในแถว ---
            $sheet2->setCellValue('A' . $row_num, $topic['id']);

            // ใส่ข้อมูลที่รวมแล้วลงในช่อง B ช่องเดียว
            $sheet2->setCellValue('B' . $row_num, $kpi_name_display);

            $sheet2->setCellValue('C' . $row_num, $topic['unit']);
            $sheet2->setCellValue('D' . $row_num, $topic['target']);
            $sheet2->setCellValue('E' . $row_num, $topic['criteria']);
            $sheet2->setCellValue('F' . $row_num, $topic['weight'] ?? '');

            $sheet2->setCellValue('G' . $row_num, $saved_h1 ? $saved_h1['Goal_job'] : '');
            $sheet2->setCellValue('H' . $row_num, $saved_h1 ? $saved_h1['Actual_work'] : '');
            $sheet2->setCellValue('I' . $row_num, $saved_h1 ? $saved_h1['score'] : '');

            $sheet2->setCellValue('J' . $row_num, $saved_h2 ? $saved_h2['Actual_work'] : '');
            $sheet2->setCellValue('K' . $row_num, ($saved_h1 ? $saved_h1['Actual_work_all_year'] : '') . "" . ($saved_h2 ? $saved_h2['Actual_work_all_year'] : ''));
            $sheet2->setCellValue('L' . $row_num, $topic['weight'] ?? '');
            $sheet2->setCellValue('M' . $row_num, $saved_h2 ? $saved_h2['score'] : '');

            $final_total_score = $saved_h2['total_score'] ?? ($saved_h1['total_score'] ?? '');
            $sheet2->setCellValue('N' . $row_num, $final_total_score);
            $advice_text = $saved_h2['Advice'] ?? ($saved_h1['Advice'] ?? '');
            $sheet2->setCellValue('O' . $row_num, $advice_text);
            
            $sheet2->getStyle('A' . $row_num . ':O' . $row_num)->applyFromArray($style_all_borders_array);

            // 1. ตั้งค่าพื้นฐาน: ให้ทุกช่องชิดขอบบน (Vertical Top)
            $sheet2->getStyle('A' . $row_num . ':O' . $row_num)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

            // 2. จัดกึ่งกลาง (Center) เฉพาะคอลัมน์ที่ต้องการ
            // A=ลำดับ, C=หน่วยนับ, D=เป้าหมาย, F=น้ำหนัก
            $sheet2->getStyle('A' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle('C' . $row_num . ':D' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle('F' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // จัดกึ่งกลางสำหรับส่วนคะแนนท้ายตาราง (L, M, N)
            $sheet2->getStyle('L' . $row_num . ':N' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // 3. จัดชิดซ้าย (Left) เฉพาะคอลัมน์ที่ข้อความยาว
            // E=เกณฑ์การให้คะแนน
            $sheet2->getStyle('E' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            

            // *สำคัญ* ต้องเปิด Wrap Text ที่คอลัมน์ B เพื่อให้เห็นข้อความที่ขึ้นบรรทัดใหม่
            $sheet2->getStyle('B' . $row_num)->getAlignment()->setWrapText(true);

            // ถ้ามี Additional ให้ไฮไลท์สีเหลืองอ่อนๆ ที่ช่อง B เพื่อให้สังเกตง่าย (ถ้าต้องการ)
            if (isset($topic['Additional']) && $topic['Additional'] === 'yes') {
                $sheet2->getStyle('B' . $row_num)->getFill()
                    ->setFillType(Fill::FILL_SOLID);

            }

            $row_num++;
        }
    } else {
        // กรณีไม่มีหัวข้อ
        $sheet2->mergeCells('A' . $row_num . ':O' . $row_num);
        $sheet2->setCellValue('A' . $row_num, '   - ไม่มีตัวชี้วัดในหมวดนี้ -');
        $sheet2->getStyle('A' . $row_num)->getFont()->setItalic(true);
        $sheet2->getStyle('A' . $row_num . ':O' . $row_num)->applyFromArray($style_all_borders_array);
        $row_num++;
    }


    // [START] (*** EDITED ***)
    // สรุปคะแนนท้ายหมวด
    $sheet2->mergeCells('A' . $row_num . ':M' . $row_num); // (was A-L)
    $sheet2->setCellValue('A' . $row_num, 'รวมคะแนนประจำหมวด');
    $sheet2->getStyle('A' . $row_num)->getFont()->setBold(true);
    $sheet2->getStyle('A' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    $sheet2->setCellValue('N' . $row_num, $saved_category_scores[$type_id] ?? '0.00'); // (was M)
    $sheet2->getStyle('N' . $row_num)->getFont()->setBold(true);

    // $sheet2->getStyle('A' . $row_num . ':O' . $row_num)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0'); // (*** EDITED ***) ลบสีเทาออก
    // [END] (*** EDITED ***)

    $row_num++;
}

// [START] (*** NEW ***)
//  เพิ่ม: แถบสีส้มตามรูปภาพ (แก้ไข Layout)
// ส่วนซ้าย
$sheet2->mergeCells('A' . $row_num . ':E' . $row_num); // (No change)
$sheet2->setCellValue('A' . $row_num, 'รวมน้ำหนักด้านผลสำเร็จของงาน');
$sheet2->getStyle('A' . $row_num)->getFont()->setBold(true)->setSize(18);
$sheet2->getStyle('A' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// ช่องน้ำหนักรวม
$sheet2->setCellValue('F' . $row_num, $total_kpi_type_weight); // (No change)
$sheet2->getStyle('F' . $row_num)->getFont()->setBold(true)->setSize(18);
$sheet2->getStyle('F' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// ส่วนขวา
$sheet2->mergeCells('G' . $row_num . ':M' . $row_num); // (was G-L)
$sheet2->setCellValue('G' . $row_num, 'คะแนนรวมทั้งปี');
$sheet2->getStyle('G' . $row_num)->getFont()->setBold(true)->setSize(18);
$sheet2->getStyle('G' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// ช่องคะแนน
$sheet2->setCellValue('N' . $row_num, $saved_total_scores['Score_500_max'] ?? 'N/A'); // (was M)
$sheet2->getStyle('N' . $row_num)->getFont()->setBold(true)->setSize(18);
$sheet2->getStyle('N' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); //  จัดกลางคะแนนส้ม

// Set Orange background color
$sheet2->getStyle('A' . $row_num . ':O' . $row_num)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFCD5B4'); // (was A-N)
// [END] (*** NEW ***)


// สรุปคะแนนรวมทั้งหมด (Green Rows)
$row_num += 1;


// [START] (*** NEW ***)
// Set Column Widths (Sheet 2) (ตอนนี้ 15 คอลัมน์ A-O)
$sheet2->getColumnDimension('A')->setWidth(20);
$sheet2->getColumnDimension('B')->setWidth(15);
$sheet2->getColumnDimension('C')->setWidth(15);
$sheet2->getColumnDimension('D')->setWidth(20);
$sheet2->getColumnDimension('E')->setWidth(15);
$sheet2->getColumnDimension('F')->setWidth(15);
$sheet2->getColumnDimension('G')->setWidth(25); // (Signature G/H/I area)
$sheet2->getColumnDimension('H')->setWidth(15);
$sheet2->getColumnDimension('I')->setWidth(15);
$sheet2->getColumnDimension('J')->setWidth(15); // (NEW) (was I)
$sheet2->getColumnDimension('K')->setWidth(40); // (was J) ผลงานรวม
$sheet2->getColumnDimension('L')->setWidth(10); // (was K) ค่าน้ำหนัก
$sheet2->getColumnDimension('M')->setWidth(10); // (was L) ผลคะแนน
$sheet2->getColumnDimension('N')->setWidth(12); // (was M) คะแนนรวม...
$sheet2->getColumnDimension('O')->setWidth(20); // (was N) หมายเหตุ

// (ปรับคอลัมน์ตารางหลักใหม่ ให้เหมาะสม)
$sheet2->getColumnDimension('B')->setWidth(40); // B-ตัวชี้วัด
$sheet2->getColumnDimension('D')->setWidth(20); // D-เป้าหมาย
$sheet2->getColumnDimension('E')->setWidth(30); // E-เกณฑ์
$sheet2->getColumnDimension('G')->setWidth(30); // (*** NEW ***) G-เป้าหมาย H1
$sheet2->getColumnDimension('H')->setWidth(30); // (was G) H-ผลงาน H1
$sheet2->getColumnDimension('I')->setWidth(30); // (was H) I-เป้าหมาย H2
$sheet2->getColumnDimension('J')->setWidth(30); // (was I) J-ผลงาน H2
// [END] (*** NEW ***)


// [START] (*** NEW ***)
// Enable Wrap Text
$sheet2->getStyle('B' . $data_start_row . ':B' . $row_num)->getAlignment()->setWrapText(true); // B-ตัวชี้วัด
$sheet2->getStyle('D' . $data_start_row . ':D' . $row_num)->getAlignment()->setWrapText(true); // D-เป้าหมาย
$sheet2->getStyle('E' . $data_start_row . ':E' . $row_num)->getAlignment()->setWrapText(true); // E-เกณฑ์
$sheet2->getStyle('G' . $data_start_row . ':G' . $row_num)->getAlignment()->setWrapText(true); // (*** NEW ***) G-เป้าหมาย H1
$sheet2->getStyle('H' . $data_start_row . ':H' . $row_num)->getAlignment()->setWrapText(true); // (was G) H-ผลงาน H1
$sheet2->getStyle('J' . $data_start_row . ':K' . $row_num)->getAlignment()->setWrapText(true); // (was I-J) J-ผลงาน H2, K-ผลงานรวม
$sheet2->getStyle('O' . $data_start_row . ':O' . $row_num)->getAlignment()->setWrapText(true); // (was N) O-หมายเหตุ
// [END] (*** NEW ***)


// Set Vertical Alignment to Top for all data rows
$sheet2->getStyle('A' . $data_start_row . ':O' . $row_num)->getAlignment()->setVertical(Alignment::VERTICAL_TOP); // (was A-N)

/// [START] (*** EDITED ***)
//  แก้ไข: จัดกลางแนวนอน (Horizontal Center) แต่ให้ชิดบน (Vertical Top)
//  คอลัมน์ C (หน่วยนับ), D (เป้าหมาย), F (น้ำหนัก)
$sheet2->getStyle('C' . $data_start_row . ':C' . $row_num)
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_TOP);

$sheet2->getStyle('D' . $data_start_row . ':D' . $row_num)
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_TOP);

$sheet2->getStyle('F' . $data_start_row . ':F' . $row_num)
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_TOP);

// ส่วนคอลัมน์คะแนน (L, M, N) ก็ควรจัดแบบเดียวกัน (ถ้าต้องการ)
$sheet2->getStyle('L' . $data_start_row . ':L' . $row_num)
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_TOP);

$sheet2->getStyle('M' . $data_start_row . ':M' . $row_num)
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_TOP);

$sheet2->getStyle('N' . $data_start_row . ':N' . $row_num)
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_TOP);


$sheet3 = $spreadsheet->createSheet();
$sheet3->setTitle('3.แบบประเมินการปฏิบัติงาน');

//  เพิ่ม: ตั้งค่าความกว้างคอลัมน์ (A-O)
$sheet3->getColumnDimension('A')->setWidth(10);
$sheet3->getColumnDimension('B')->setWidth(15);
$sheet3->getColumnDimension('C')->setWidth(15);
$sheet3->getColumnDimension('D')->setWidth(15);
$sheet3->getColumnDimension('E')->setWidth(15);
$sheet3->getColumnDimension('F')->setWidth(15);
$sheet3->getColumnDimension('G')->setWidth(15);
$sheet3->getColumnDimension('H')->setWidth(15);
$sheet3->getColumnDimension('I')->setWidth(15);
$sheet3->getColumnDimension('J')->setWidth(15);
$sheet3->getColumnDimension('K')->setWidth(15);
$sheet3->getColumnDimension('L')->setWidth(15);
$sheet3->getColumnDimension('M')->setWidth(15);
$sheet3->getColumnDimension('N')->setWidth(15);
$sheet3->getColumnDimension('O')->setWidth(15);

$row = 1;
$sheet3->setCellValue('A' . $row, 'ส่วนที่ 1: เกณฑ์สำหรับการประเมินประจำปี');
$sheet3->getStyle('A' . $row)->getFont()->setBold(true);
$sheet3->mergeCells('A' . $row . ':O' . $row);
$row++; // 16

$headerCriteriaRow = $row;
$sheet3->setCellValue('A' . $headerCriteriaRow, 'เกณฑ์การให้คะแนน');
$sheet3->mergeCells('A' . $headerCriteriaRow . ':C' . $headerCriteriaRow);
$sheet3->setCellValue('D' . $headerCriteriaRow, 'คำนิยาม');
$sheet3->mergeCells('D' . $headerCriteriaRow . ':O' . $headerCriteriaRow);
// ใช้ $style_table_header ที่มีอยู่
$sheet3->getStyle('A' . $headerCriteriaRow . ':O' . $headerCriteriaRow)->applyFromArray($tab3_header_style_array); // แก้ไขชื่อ Style
$sheet3->getRowDimension($headerCriteriaRow)->setRowHeight(30);
$row++; // 17

// --- ข้อมูลเกณฑ์ (อัปเดตข้อความจาก individual_kpi.php Tab 3) ---
$criteria_data_tab3 = [
    '5' => "สำเร็จตามเป้าหมาย (Achievement of Target)\n100%",
    '4' => "สำเร็จตามเป้าหมายส่วนใหญ่ (Nearly Achievement of Target)\n80%-99%",
    '3' => "สำเร็จตามเป้าหมายพอควร (Partial Achievement)\n60%-79%",
    '2' => "สำเร็จตามเป้าหมายน้อย (Under Achievement)\n40%-59%",
    '1' => "สำเร็จตามเป้าหมายน้อยมาก (Very Low Achievement)\nต่ำกว่า 40%"
];

$criteria_start_row = $row; // 17
foreach ($criteria_data_tab3 as $score => $definition) {
    $sheet3->setCellValue('A' . $row, $score);
    $sheet3->mergeCells('A' . $row . ':C' . $row);

    // ใส่ข้อความที่รวม Eng และ % แล้ว
    $sheet3->setCellValue('D' . $row, $definition);
    $sheet3->mergeCells('D' . $row . ':O' . $row);

    $sheet3->getStyle('A' . $row . ':O' . $row)->applyFromArray($style_all_borders_array); // แก้ไขชื่อ Style
    $sheet3->getRowDimension($row)->setRowHeight(60); // เพิ่มความสูงเล็กน้อยสำหรับ 2 บรรทัด
    $row++;
}
$criteria_end_row = $row - 1; // 21

// --- ใช้ Style กับข้อมูลเกณฑ์ ---
// จัดกลางให้คะแนน (A-C)
$sheet3->getStyle('A' . $criteria_start_row . ':C' . $criteria_end_row)->applyFromArray($style_align_center_array); // แก้ไขชื่อ Style
// จัดชิดซ้ายบนและตัดคำให้คำนิยาม (D-O)
$sheet3->getStyle('D' . $criteria_start_row . ':O' . $criteria_end_row)->applyFromArray($style_align_wrap_text_array); // แก้ไขชื่อ Style

// --- เว้นว่าง 2 แถว ตาม CSV (Rows 22, 23) ---
$row++; // 22
$row++; // 23
// [END] จบส่วนเกณฑ์การประเมิน

// --- ส่วนที่ 1 (เดิม): ตารางประเมิน (เหมือน Tab 2) ---
$row++; // (ลบ $row = 24;)
$sheet3->setCellValue('A' . $row, 'ส่วนที่ 1 : SECTION FIRST-GOAL SETTING AND EVALUATION/การประเมินผลตามเป้าหมายที่กำหนด');
$sheet3->mergeCells('A' . $row . ':O' . $row);
$sheet3->getStyle('A' . $row)->getFont()->setBold(true);
$row++; // (แก้ไข) ข้ามมาที่แถว 26

$headerRow = $row;
$sheet3->setCellValue('A' . $headerRow, "ลำดับ");
$sheet3->mergeCells('A' . $headerRow . ':A' . $headerRow);
$sheet3->setCellValue('B' . $headerRow, "ประเภทตัวชี้วัด");
$sheet3->mergeCells('B' . $headerRow . ':K' . $headerRow);
$sheet3->setCellValue('L' . $headerRow, "น้ำหนัก\n(100%)");
$sheet3->mergeCells('L' . $headerRow . ':O' . $headerRow);
$sheet3->getStyle('A' . $headerRow . ':O' . $headerRow)->applyFromArray($tab3_header_style_array); // แก้ไขชื่อ Style
$sheet3->getRowDimension($headerRow)->setRowHeight(50);

// --- [START] ส่วนข้อมูล Tab 3 (โครงสร้างใหม่) (เหมือน Tab 2) ---
$row++; // (ลบ $row = $row + 2;)
if (!empty($kpi_data)) {
    foreach ($kpi_data as $kpi_type_id => $kpi_type) {

        // --- 1. ช่อง "ลำดับ" (A) ---
        $sheet3->setCellValue('A' . $row, htmlspecialchars($kpi_type['Order_No']));
        // (คอลัมน์ A ไม่ต้อง Merge)

        // --- 2. ช่อง "ประเภทตัวชี้วัด" (B-K) ---
        // รวม EN และ TH โดยขึ้นบรรทัดใหม่
        $type_name_display = htmlspecialchars($kpi_type['KPI_Type_Name_EN']) . "\n" . htmlspecialchars($kpi_type['KPI_Type_Name_TH']);
        $sheet3->setCellValue('B' . $row, $type_name_display);
        $sheet3->mergeCells('B' . $row . ':K' . $row);

        // --- 3. ช่อง "น้ำหนัก" (L-O) ---
        $sheet3->setCellValue('L' . $row, htmlspecialchars($kpi_type['TypeWeight']));
        $sheet3->mergeCells('L' . $row . ':O' . $row);


        // --- [สำคัญ] ใช้ Style กับแถวที่เพิ่งสร้าง ---
        // (สันนิษฐานว่าคุณมี Style เหล่านี้จากโค้ดส่วนก่อนหน้า)

        // 1. ตีเส้นขอบทั้งหมด
        $sheet3->getStyle('A' . $row . ':O' . $row)->applyFromArray($style_all_borders_array); // แก้ไขชื่อ Style

        // 2. จัดข้อความ "ลำดับ" และ "น้ำหนัก" ให้อยู่ตรงกลาง
        $sheet3->getStyle('A' . $row)->applyFromArray($style_align_center_array); // แก้ไขชื่อ Style
        $sheet3->getStyle('L' . $row . ':O' . $row)->applyFromArray($style_align_center_array); // แก้ไขชื่อ Style

        // 3. จัดข้อความ "ประเภทตัวชี้วัด" ให้ชิดซ้าย-บน และตัดคำ (wrap text)
        $sheet3->getStyle('B' . $row . ':K' . $row)->applyFromArray($style_align_wrap_text_array); // แก้ไขชื่อ Style
        // (ถ้า $style_align_wrap_text ไม่ได้ตั้งค่าชิดซ้าย ให้ใช้บรรทัดนี้แทน)
        // $sheet3->getStyle('B' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // 4. ตั้งค่าความสูงแถว (ถ้าต้องการ)
        $sheet3->getRowDimension($row)->setRowHeight(60);

        // เลื่อน $row ไปแถวถัดไป
        $row++;
    }
    // --- 4. พิมพ์สรุปคะแนนรวม (Footer) ---
    $row++; // เว้น 1 แถว
    $score_500 = $saved_total_scores['Score_500_max'] ?? '0.00';
    $score_100 = $saved_total_scores['Score_100_max'] ?? '0.00';

    $sheet3->setCellValue('J' . $row, 'Score/คะแนนรวม (500 คะแนน)');
    $sheet3->mergeCells('J' . $row . ':M' . $row);
    $sheet3->getStyle('J' . $row . ':M' . $row)->applyFromArray($style_grand_total_label_array); // แก้ไขชื่อ Style
    $sheet3->setCellValue('O' . $row, number_format(floatval($score_500), 2));
    $sheet3->getStyle('O' . $row)->applyFromArray($style_grand_total_value_array); // แก้ไขชื่อ Style
    $row++;

    $sheet3->setCellValue('J' . $row, 'Score/คะแนนรวม (อัตราส่วน 80)');
    $sheet3->mergeCells('J' . $row . ':M' . $row);
    $sheet3->getStyle('J' . $row . ':M' . $row)->applyFromArray($style_grand_total_label_array); // แก้ไขชื่อ Style
    $sheet3->setCellValue('O' . $row, number_format(floatval($score_100), 2));
    $sheet3->getStyle('O' . $row)->applyFromArray($style_grand_total_value_array); // แก้ไขชื่อ Style

    $row++; // เว้น 1 แถว

    // --- 1. หัวข้อหลัก "สรุปความคิดเห็น..." ---
    $sheet3->setCellValue('A' . $row, 'สรุปความคิดเห็นเพิ่มเติม (จากหัวหน้างาน/ผู้บังคับบัญชา)');
    $sheet3->mergeCells('A' . $row . ':O' . $row);
    $sheet3->getStyle('A' . $row)->getFont()->setBold(true);
    $row++;

    // --- 2. หัวตารางย่อย (กลางปี / ปลายปี) ---
    $headerCommentRow = $row;
    $sheet3->setCellValue('A' . $headerCommentRow, 'ครั้งที่ 1 (กลางปี)');
    $sheet3->mergeCells('A' . $headerCommentRow . ':G' . $headerCommentRow); // ผสานครึ่งแรก (A-G)
    $sheet3->setCellValue('H' . $headerCommentRow, 'ครั้งที่ 2 (รวมทั้งปี)');
    $sheet3->mergeCells('H' . $headerCommentRow . ':O' . $headerCommentRow); // ผสานครึ่งหลัง (H-O)
    $sheet3->getStyle('A' . $headerCommentRow . ':O' . $headerCommentRow)->applyFromArray($tab3_header_style_array); // แก้ไขชื่อ Style
    $row++;

    // --- 3. ข้อมูล (Textarea) ---
    $dataCommentRow = $row;
    // (ดึงข้อมูลจาก $saved_okr_data ที่ควรจะดึงมาก่อนหน้านี้)
    $sheet3->setCellValue('A' . $dataCommentRow, htmlspecialchars($saved_okr_data['comments_mid'] ?? ''));
    $sheet3->mergeCells('A' . $dataCommentRow . ':G' . $dataCommentRow);
    $sheet3->setCellValue('H' . $dataCommentRow, htmlspecialchars($saved_okr_data['comments_end'] ?? ''));
    $sheet3->mergeCells('H' . $dataCommentRow . ':O' . $dataCommentRow);

    // ใช้ Style (ตีเส้นขอบ + ตัดคำ)
    $sheet3->getStyle('A' . $dataCommentRow . ':O' . $dataCommentRow)->applyFromArray($style_all_borders_array); // แก้ไขชื่อ Style
    $sheet3->getStyle('A' . $dataCommentRow . ':O' . $dataCommentRow)->applyFromArray($style_align_wrap_text_array); // แก้ไขชื่อ Style
    $sheet3->getRowDimension($dataCommentRow)->setRowHeight(80); // ตั้งค่าความสูงแถว (ปรับได้ตามต้องการ)
    $row++; // เว้น 1 แถว
    $row++;
    // --- START: ส่วนที่ 2: การประเมินความสามารถหรือพฤติกรรม ---

    // 1. หัวข้อหลัก "ส่วนที่ 2..."
    $sheet3->setCellValue('A' . $row, 'ส่วนที่ 2 : การประเมินความสามารถหรือพฤติกรรม SECTION SECOND – COMPETENCY EVALUATION/');
    $sheet3->mergeCells('A' . $row . ':O' . $row);
    $sheet3->getStyle('A' . $row)->getFont()->setBold(true);
    $row++;

    // 2. คำแนะนำ (Instructions) - 6 แถว (ไทย/อังกฤษ สลับกัน)
    $instructionStartRow = $row;
    $sheet3->setCellValue('A' . $row, '* ประเมินการปฏิบัติงานของพนักงานตามกลุ่มความสามารถและพฤติกรรมในตารางตามเกณฑ์ที่กำหนดให้ กรุณาทำเครื่องหมาย X ลงในช่องที่เหมาะสม');
    $sheet3->mergeCells('A' . $row . ':O' . $row);
    $row++;
    $sheet3->setCellValue('A' . $row, 'Evaluate the employee’s performance under each competency area with reference to the rating scale below. Put a “X” in the appropriate box.');
    $sheet3->mergeCells('A' . $row . ':O' . $row);
    $row++;

    $sheet3->setCellValue('A' . $row, '* คำอธิบายความสามารถหรือพฤติกรรมแต่ละกลุ่มเป็นการให้คำจำกัดความอย่างกว้างๆ');
    $sheet3->mergeCells('A' . $row . ':O' . $row);
    $row++;
    $sheet3->setCellValue('A' . $row, 'The description of each competency is not meant to be prescriptive or exhaustive.');
    $sheet3->mergeCells('A' . $row . ':O' . $row);
    $row++;

    $sheet3->setCellValue('A' . $row, '* กรุณาระบุตัวอย่างของพฤติกรรมที่พนักงานนั้นๆ ได้แสดงออกภายใต้หัวข้อ "ความคิดเห็น" ตามความเหมาะสม');
    $sheet3->mergeCells('A' . $row . ':O' . $row);
    $row++;
    $sheet3->setCellValue('A' . $row, 'Give specific examples of behaviors to support the rating under “Remarks” where appropriate.');
    $sheet3->mergeCells('A' . $row . ':O' . $row);
    $row++;

    // ตั้งค่า Style ให้กับแถวคำแนะนำทั้งหมด (ตัวเล็ก + ตัดคำ)
    $sheet3->getStyle('A' . $instructionStartRow . ':O' . ($row - 1))->getFont()->setSize(10);
    $sheet3->getStyle('A' . $instructionStartRow . ':O' . ($row - 1))->getAlignment()->setWrapText(true);

    // 3. หัวตาราง Rating Scale
    $ratingHeaderRow = $row;
    $sheet3->setCellValue('A' . $ratingHeaderRow, 'สเกล / Rating Scale');
    $sheet3->mergeCells('A' . $ratingHeaderRow . ':C' . $ratingHeaderRow); // 3 คอลัมน์ (A-C)

    $sheet3->setCellValue('D' . $ratingHeaderRow, 'คำอธิบาย / Description');
    $sheet3->mergeCells('D' . $ratingHeaderRow . ':O' . $ratingHeaderRow); // 12 คอลัมน์ (D-O)

    // ใช้ Style หัวตารางจากโค้ดเดิมของคุณ
    $sheet3->getStyle('A' . $ratingHeaderRow . ':O' . $ratingHeaderRow)->applyFromArray($tab3_header_style_array); // แก้ไขชื่อ Style
    $row++;

    // 4. เนื้อหาตาราง Rating (4 แถว)

    // --- Rating 4 ---
    $ratingRow4 = $row;
    $sheet3->setCellValue('A' . $ratingRow4, "4\nดีเด่น\nOutstanding");
    $sheet3->mergeCells('A' . $ratingRow4 . ':C' . $ratingRow4);
    $sheet3->setCellValue('D' . $ratingRow4, "ผลงานดีเด่นในทุกๆ ด้าน และปฏิบัติได้อย่างต่อเนื่อง เป็นตัวอย่างที่ดีให้แก่สมาชิกในทีมมีผลงานได้\nConsistently excels in this area with significant contribution. Is the example of the team");
    $sheet3->mergeCells('D' . $ratingRow4 . ':O' . $ratingRow4);
    $row++;

    // --- Rating 3 ---
    $ratingRow3 = $row;
    $sheet3->setCellValue('A' . $ratingRow3, "3\nดี\nVery Competent");
    $sheet3->mergeCells('A' . $ratingRow3 . ':C' . $ratingRow3);
    $sheet3->setCellValue('D' . $ratingRow3, "ผลงานตรงตามเป้าหมายเป็นส่วนใหญ่หรือทำได้ตามความคาดหวังในบางครั้ง มีสิ่งที่ต้องปรับปรุงเพิ่มเติมเล็กน้อย\nMeets the requirements most of the time. May exceed expectations at times. Only a few areas to improve");
    $sheet3->mergeCells('D' . $ratingRow3 . ':O' . $ratingRow3);
    $row++;

    // --- Rating 2 ---
    $ratingRow2 = $row;
    $sheet3->setCellValue('A' . $ratingRow2, "2\nปานกลาง\nModerately Competent");
    $sheet3->mergeCells('A' . $ratingRow2 . ':C' . $ratingRow2);
    $sheet3->setCellValue('D' . $ratingRow2, "ผลงานตรงตามเป้าหมายในภาพรวม แต่อาจต้องเอาใจใส่ในบางประเด็น มีสิ่งที่ต้องปรับปรุงเพิ่มเติมในบางประเด็น\nMeets the normal requirements for the job in general. May require attention at times, some areas to improve");
    $sheet3->mergeCells('D' . $ratingRow2 . ':O' . $ratingRow2);
    $row++;

    // --- Rating 1 ---
    $ratingRow1 = $row;
    $sheet3->setCellValue('A' . $ratingRow1, "1\nควรปรับปรุง\nImprovement Required");
    $sheet3->mergeCells('A' . $ratingRow1 . ':C' . $ratingRow1);
    $sheet3->setCellValue('D' . $ratingRow1, "ควรปรับปรุงการทำงานและอาจจำเป็นต้องมีผู้แนะนำในการปรับปรุงการทำงาน ต้องใช้ความพยายามในการทำงานให้มากขึ้น\nRequires improvement and regular coaching. More efforts are expected");
    $sheet3->mergeCells('D' . $ratingRow1 . ':O' . $ratingRow1);
    $row++;

    // ตั้งค่า Style ให้กับแถวเนื้อหา Rating ทั้งหมด
    $ratingContentRange = 'A' . $ratingRow4 . ':O' . ($row - 1);
    $sheet3->getStyle($ratingContentRange)->applyFromArray($style_all_borders_array); // ตีเส้นขอบ
    $sheet3->getStyle($ratingContentRange)->applyFromArray($style_align_wrap_text_array); // ตัดคำ (จากโค้ดเดิม)
    $sheet3->getStyle($ratingContentRange)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP); // จัดชิดบน

    // ตั้งค่า Style พิเศษสำหรับคอลัมน์สเกล (A-C) ให้อยู่ตรงกลาง
    $ratingScaleRange = 'A' . $ratingRow4 . ':C' . ($row - 1);
    $sheet3->getStyle($ratingScaleRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet3->getStyle($ratingScaleRange)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

    // ตั้งค่าความสูงแถวอัตโนมัติ
    foreach (range($ratingRow4, ($row - 1)) as $idx) {
        $sheet3->getRowDimension($idx)->setRowHeight(-1);
    }
    // 5. เว้นบรรทัด
    $row++;
    // --- END: ส่วนที่ 2 ---
    $style_light_green_bg = [
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2EFDA']],
        'font' => ['bold' => true],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
    ];

    $style_light_yellow_header = [
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
        'font' => ['bold' => true],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
    ];

    $style_light_blue_header = [
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DDEBF7']],
        'font' => ['bold' => true],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
    ];

    $style_data_cell_yellow = [
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
    ];

    $style_data_cell_blue = [
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DDEBF7']],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
    ];

    // Style สำหรับข้อความหัวข้อย่อย (1.1, 1.2, ...)
    $style_competency_label = [
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP, 'wrapText' => true],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
    ];

    // Style สำหรับช่อง "เจ้าหน้าที่" / "บังคับบัญชา" (พื้นขาว, กึ่งกลาง)
    $style_evaluator_label = [
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
    ];


    /**
     * =================================================================
     * HELPER FUNCTION: สร้างบล็อกตารางประเมิน
     * =================================================================
     * ฟังก์ชันนี้จะสร้างตาราง 1 บล็อก (หัวข้อหลัก, หัวข้อย่อย, ช่องความคิดเห็น)
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet The worksheet object
     * @param int $startRow The row number to start creating the block
     * @param string $mainTitle The main competency title (e.g., "1. ความรู้และทักษะในงาน")
     * @param array $subTopics Array of sub-topic strings (e.g., ["1.1 ...", "1.2 ..."])
     * @param array $styles Array of style definitions
     * @return int The next available row number after this block
     */
    function createCompetencyBlock($sheet, $startRow, $mainTitle, $subTopics, $styles)
    {

        // --- 1. หัวตารางหลัก (2 แถว) ---
        $headerRow = $startRow;
        $subHeaderRow = $headerRow + 1;

        // หัวข้อหลัก (ผสาน 2 แถว)
        $sheet->mergeCells('A' . $headerRow . ':G' . $subHeaderRow);
        $sheet->setCellValue('A' . $headerRow, $mainTitle);
        $sheet->getStyle('A' . $headerRow . ':G' . $subHeaderRow)->applyFromArray($styles['green_bg']);

        // ผู้ประเมิน (ผสาน 2 แถว)
        $sheet->mergeCells('H' . $headerRow . ':H' . $subHeaderRow);
        $sheet->setCellValue('H' . $headerRow, "ผู้ประเมิน");
        $sheet->getStyle('H' . $headerRow . ':H' . $subHeaderRow)->applyFromArray($styles['yellow_header']);

        // ผลประเมินครึ่งปีแรก (แถวบน)
        $sheet->mergeCells('I' . $headerRow . ':J' . $headerRow);
        $sheet->setCellValue('I' . $headerRow, "ผลประเมินครึ่งปีแรก");
        $sheet->getStyle('I' . $headerRow . ':J' . $headerRow)->applyFromArray($styles['yellow_header']);

        // ผลประเมินครึ่งปีหลัง (แถวบน)
        $sheet->mergeCells('K' . $headerRow . ':L' . $headerRow);
        $sheet->setCellValue('K' . $headerRow, "ผลประเมินครึ่งปีหลัง");
        $sheet->getStyle('K' . $headerRow . ':L' . $headerRow)->applyFromArray($styles['yellow_header']);

        // หัวตารางย่อย (แถวล่าง) - เจ้าหน้าที่ / บังคับบัญชา
        $sheet->setCellValue('I' . $subHeaderRow, "เจ้าหน้าที่");
        $sheet->getStyle('I' . $subHeaderRow)->applyFromArray($styles['yellow_header']);
        $sheet->setCellValue('J' . $subHeaderRow, "บังคับบัญชา");
        $sheet->getStyle('J' . $subHeaderRow)->applyFromArray($styles['yellow_header']);

        $sheet->setCellValue('K' . $subHeaderRow, "เจ้าหน้าที่");
        $sheet->getStyle('K' . $subHeaderRow)->applyFromArray($styles['yellow_header']);
        $sheet->setCellValue('L' . $subHeaderRow, "บังคับบัญชา");
        $sheet->getStyle('L' . $subHeaderRow)->applyFromArray($styles['yellow_header']);

        // ภาพรวมทั้งปี (ผสาน 2 แถว)
        $sheet->mergeCells('M' . $headerRow . ':O' . $subHeaderRow);
        $sheet->setCellValue('M' . $headerRow, "ภาพรวมทั้งปี\n(พนักงาน)");
        $sheet->getStyle('M' . $headerRow . ':O' . $subHeaderRow)->applyFromArray($styles['blue_header']);

        $currentRow = $subHeaderRow + 1; // ชี้ไปที่แถวถัดไปที่จะเริ่มใส่ข้อมูล

        // --- 2. สร้างแถวหัวข้อย่อย (วน Loop ตามข้อมูล) ---
        foreach ($subTopics as $topic) {
            $row1 = $currentRow; // แถวเจ้าหน้าที่
            $row2 = $currentRow + 1; // แถวบังคับบัญชา

            // หัวข้อย่อย (ผสาน 2 แถว)
            $sheet->mergeCells('A' . $row1 . ':G' . $row2);
            $sheet->setCellValue('A' . $row1, $topic);
            $sheet->getStyle('A' . $row1 . ':G' . $row2)->applyFromArray($styles['competency_label']);

            // ช่อง "ผู้ประเมิน"
            $sheet->setCellValue('H' . $row1, 'เจ้าหน้าที่');
            $sheet->getStyle('H' . $row1)->applyFromArray($styles['evaluator_label']);
            $sheet->setCellValue('H' . $row2, 'บังคับบัญชา');
            $sheet->getStyle('H' . $row2)->applyFromArray($styles['evaluator_label']);

            // ช่องกรอกข้อมูล (สีเหลือง)
            $sheet->getStyle('I' . $row1 . ':L' . $row1)->applyFromArray($styles['data_yellow']);
            $sheet->getStyle('I' . $row2 . ':L' . $row2)->applyFromArray($styles['data_yellow']);

            // ช่องสรุป (สีฟ้า, ผสาน 2 แถว)
            $sheet->mergeCells('M' . $row1 . ':O' . $row2);
            $sheet->getStyle('M' . $row1 . ':O' . $row2)->applyFromArray($styles['data_blue']);

            // ตั้งค่าความสูงแถว
            $sheet->getRowDimension($row1)->setRowHeight(30);
            $sheet->getRowDimension($row2)->setRowHeight(30);

            $currentRow += 2; // ขยับ $currentRow ไปทีละ 2 แถว
        }

        // --- 3. แถว "ความคิดเห็น" ---
        $commentRow = $currentRow;
        $sheet->mergeCells('A' . $commentRow . ':G' . $commentRow);
        $sheet->setCellValue('A' . $commentRow, 'ความคิดเห็น :');
        $sheet->getStyle('A' . $commentRow . ':G' . $commentRow)->applyFromArray($styles['competency_label']);
        $sheet->getStyle('A' . $commentRow . ':G' . $commentRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $commentRow . ':G' . $commentRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // ช่องกรอกความคิดเห็น (ผสาน)
        $sheet->mergeCells('H' . $commentRow . ':O' . $commentRow);
        $sheet->getStyle('H' . $commentRow . ':O' . $commentRow)->applyFromArray($styles['all_borders']); // ใช้ $style_all_borders ที่มีอยู่
        $sheet->getStyle('H' . $commentRow . ':O' . $commentRow)->getAlignment()->setWrapText(true);
        $sheet->getRowDimension($commentRow)->setRowHeight(60);

        $currentRow++;
        $currentRow++; // เว้น 1 แถว

        return $currentRow; // คืนค่าแถวถัดไปที่พร้อมใช้งาน
    }

    // --- 3. รวบรวม Styles ทั้งหมดใส่ Array ---
// (ใช้ $style_all_borders จากโค้ดเดิมของคุณ)
    $styles_array = [
        'green_bg' => $style_light_green_bg,
        'yellow_header' => $style_light_yellow_header,
        'blue_header' => $style_light_blue_header,
        'data_yellow' => $style_data_cell_yellow,
        'data_blue' => $style_data_cell_blue,
        'competency_label' => $style_competency_label,
        'evaluator_label' => $style_evaluator_label,
        'all_borders' => $style_all_borders_array, // แก้ไขชื่อ Style
    ];

    // --- 4. ข้อมูลสำหรับตารางที่ 1 (จากภาพ) ---
    $mainTitle1 = "1. ความรู้และทักษะในงาน";
    $subTopics1 = [
        "1.1 สามารถให้ความชำนาญในการพัฒนาการทำงานของตน",
        "1.2 สามารถแสดงความคิดเห็นหรือคำแนะนำในงานได้อย่างชาญฉลาด",
        "1.3 เต็มใจแบ่งปันและถ่ายทอดความรู้"
    ];

    // --- 5. เรียกใช้งานฟังก์ชันเพื่อสร้างตารางที่ 1 ---
// (ใช้ $sheet3 และ $row จากโค้ดเดิมของคุณ)
    $row = createCompetencyBlock($sheet3, $row, $mainTitle1, $subTopics1, $styles_array);

    // --- 6. ข้อมูลสำหรับตารางที่ 2 (จากภาพ) ---
    $mainTitle2 = "2. การทำงานให้สำเร็จ";
    $subTopics2 = [
        "2.1 สามารถลงมือปฏิบัติงานตามแผนได้อย่างสำเร็จ",
        "2.2 สามารถระบุประเด็นปัญหาและสาเหตุ พร้อมทั้งสามารถนำเสนอแนวทางการแก้ไขปัญหานั้นๆ ได้",
        "2.3 มีความกระตือรือร้นและมุ่งมั่นที่จะทำงานให้สำเร็จอย่างสม่ำเสมอ"
    ];

    // --- 7. เรียกใช้งานฟังก์ชันเพื่อสร้างตารางที่ 2 ---
    $row = createCompetencyBlock($sheet3, $row, $mainTitle2, $subTopics2, $styles_array);

    // --- 8. ข้อมูลสำหรับตารางที่ 3 (จากภาพ) ---
    $mainTitle3 = "3. การให้ความสำคัญกับลูกค้า (ผู้มีส่วนได้ส่วนเสีย)";
    $subTopics3 = [
        "3.1 สามารถวิเคราะห์ความคาดหวังและความต้องการของผู้บริหารและลูกค้าทุกระดับ (ทั้งภายในและภายนอก)",
        "3.2 สามารถสร้างและรักษาความสัมพันธ์ที่ดีกับผู้บริหารและลูกค้าทุกระดับ เพื่อให้สามารถตอบสนองความต้องการตลอดจนเพื่อการสร้างความน่าเชื่อถือและไว้วางใจซึ่งกันและกัน"
    ];

    // --- 9. เรียกใช้งานฟังก์ชันเพื่อสร้างตารางที่ 3 ---
    $row = createCompetencyBlock($sheet3, $row, $mainTitle3, $subTopics3, $styles_array);

    $mainTitle4 = "4. ความสามารถในการติดต่อสื่อสารทั้งการพูดและเขียน";
    $subTopics4 = [
        "4.1 สามารถสื่อสารทั้งการพูดและเขียนได้อย่างชัดเจน ถูกต้องและกระชับได้ความ",
        "4.2 สามารถใช้สื่อต่างๆ ในการสื่อสารได้อย่างเหมาะสมกับสถานการณ์ เช่น การประชุม พบปะ การใช้อีเมล เป็นต้น",
        "4.3 สามารถสื่อสารด้วยความมั่นใจ (เช่น มีความพร้อมในด้านข้อมูล มีการเตรียมตัวและการจัดการที่ดี)"
    ];
    $row = createCompetencyBlock($sheet3, $row, $mainTitle4, $subTopics4, $styles_array);

    $mainTitle5 = "5. การบริหารการเปลี่ยนแปลง";
    $subTopics5 = [
        "5.1 สามารถส่งเสริมและผลักดันให้เกิดการพัฒนาปรับเปลี่ยนแนวทางการทำงานให้ดีขึ้น",
        "5.2 เปิดใจยอมรับและมีความยืดหยุ่นต่อการเปลี่ยนแปลง",
        "5.3 ให้การสนับสนุนด้านทรัพยากร แก้ไขปัญหาและอุปสรรคพร้อมทั้งแสดงบทบาทในฐานะเป็นผู้ประสานงานสำหรับการเปลี่ยนแปลง"
    ];
    $row = createCompetencyBlock($sheet3, $row, $mainTitle5, $subTopics5, $styles_array);

    $mainTitle6 = "6. ความรอบรู้ในธุรกิจของมหาวิทยาลัย";
    $subTopics6 = [
        "6.1 สามารถแสดงออกซึ่งความรอบรู้ในธุรกิจของมหาวิทยาลัยและทักษะที่ต้องการในการดำเนินธุรกิจตั้งแต่การวางแผนติดต่อสื่อสาร 
และการปฏิบัติงานตามแผน",
        "6.2  สามารถนำเสนอความคิดสร้างสรรค์ พร้อมทั้งสามารถมองเห็นโอกาสทางธุรกิจและแนวทางในการปรับปรุงงานได้",
        "6.3 สามารถมองเห็นภาพรวมของความสัมพันธ์ขององค์ประกอบต่างๆ ทางธุรกิจได้ (เช่น เข้าใจและวิเคราะห์ผลกระทบทางธุรกิจ ในลักษณะของแผนปฏิบัติงานหรือข้อเสนอแนะ)"
    ];
    $row = createCompetencyBlock($sheet3, $row, $mainTitle6, $subTopics6, $styles_array);

    $mainTitle7 = "7. ความสามารถในการวางแผนและจัดระบบงาน";
    $subTopics7 = [
        "7.1 สามารถกำหนดแผนระยะกลางถึงระยะยาว (เช่น วัตถุประสงค์ของแผนเป้าหมาย)ให้ความสำคัญกับการทำให้ปัจจัยที่ได้รับผลกระทบมากที่สุดประสบความสำเร็จ",
        "7.2  ใช้ทรัพยากรได้อย่างมีประสิทธิภาพและประสิทธิผลในการปฏิบัติงานตามโครงการหรือตามแผน",
        "7.3 วางแผนตารางการทำงานที่สอดคล้องกับสถานการณ์จริงพร้อมทั้งติดตามผลและปรับเปลี่ยนให้เหมาะสมกับสถานการณ์ที่เปลี่ยนแปลงไป"
    ];
    $row = createCompetencyBlock($sheet3, $row, $mainTitle7, $subTopics7, $styles_array);

    $mainTitle8 = "8. ฝึกสอนและพัฒนาผู้อื่น";
    $subTopics8 = [
        "8.1 พิจารณาทบทวนผลงานของสมาชิกในทีมพร้อมทั้งชี้แจงสมาชิกให้ทราบถึงความสำเร็จ",
        "8.2 ฝึกสอนและพัฒนาพนักงานให้มีความรู้และทักษะที่จำเป็นในการทำงาน",
        "8.3 พยายามสร้างและนำศักยภาพในตัวพนักงานมาใช้อย่างเต็มที่"
    ];
    $row = createCompetencyBlock($sheet3, $row, $mainTitle8, $subTopics8, $styles_array);

    $mainTitle9 = "9. ความสามารถในการแก้ไขปัญหา/ตัดสินใจ";
    $subTopics9 = [
        "9.1 จัดการกับปัญหาทันที สามารถคลี่คลายวิกฤตการณ์ พร้อมทั้งสามารถวิเคราะห์สาเหตุของปัญหานั้นๆ",
        "9.2 รวบรวมข้อมูลและการนำเสนอแนวทางการแก้ไขปัญหาอย่างเป็นขั้นตอน",
        "9.3 สามารถตัดสินใจและพร้อมรับผิดชอบต่อการกระทำหรือการตัดสินใจของตน"
    ];
    $row = createCompetencyBlock($sheet3, $row, $mainTitle9, $subTopics9, $styles_array);

    $mainTitle10 = "10. ความสามารถในการโน้มน้าวให้เกิดการปฏิบัติและการคลี่คลายความขัดแย้ง";
    $subTopics10 = [
        "10.1 สามารถโน้มน้าวให้เกิดการปฏิบัติโดยมีหลักการและเหตุผล",
        "10.2 สามารถนำเสนอความคิดของตนและโน้มน้าวให้เกิดการปฏิบัติร่วมกัน สามารถแสดงความเห็นที่อาจไม่ตรงกับผู้อื่น โดยไม่ได้มุ่งเน้นการโจมตีหรือให้ร้ายผู้ปฏิบัติงาน",
        "10.3 สามารถปฏิบัติงานให้สำเร็จโดยที่แต่ละฝ่ายที่เกี่ยวข้องได้ประโยชน์และสามารถรักษาความสัมพันธ์อันดีไว้ แสดงความสามารถในการควบคุมการคลี่คลายความขัดแย้งต่างๆ"
    ];
    $row = createCompetencyBlock($sheet3, $row, $mainTitle10, $subTopics10, $styles_array);

    $mainTitle11 = "11. การทำงานร่วมกันเป็นทีม";
    $subTopics11 = [
        "11.1 สามารถอำนวยการและประสานงานให้สมาชิกในทีมงานมีส่วนร่วมในการกำหนดเป้าหมาย การวางแผนการปฏิบัติงาน ตลอดจนการตัดสินใจร่วมกัน",
        "11.2 สามารถแสดงบทบาทในฐานะสมาชิกในทีมหรือหัวหน้าทีมได้ดี เช่น การแสดงความคิดเห็น การสนับสนุนสมาชิกคนอื่นๆ ภายในทีมงาน",
        "11.3 สามารถสื่อสาร ให้คำแนะนำและให้ความช่วยเหลือ เพื่อให้เกิดความปรองดองกันภายในทีมงาน",
        "11.4 พยายามทำงานร่วมกับผู้อื่นอย่างใกล้ชิด เพื่อแบ่งปันข้อมูลในการทำงานซึ่งกันและกัน"
    ];
    $row = createCompetencyBlock($sheet3, $row, $mainTitle11, $subTopics11, $styles_array);

    $mainTitle12 = "12. การจัดการอารมณ์ของตนเอง";
    $subTopics12 = [
        "12.1 สามารถจัดการกับสถานการณ์ที่บีบคั้นหรือเคร่งเครียดด้วยความสุขุมและมั่นใจ",
        "12.2 สามารถควบคุมหรือระงับอารมณ์ของตนได้",
        "12.3 พิจารณาทางเลือกเชิงบวกอื่นๆ ได้ เมื่อเผชิญหน้ากับวิกฤตการณ์",
    ];
    $row = createCompetencyBlock($sheet3, $row, $mainTitle12, $subTopics12, $styles_array);

    $mainTitle13 = "13. ความสามารถในการจูงใจพัฒนาตนเอง";
    $subTopics13 = [
        "13.1 กำหนดมาตรฐานการทำงานของตนและสามารถปฏิบัติได้",
        "13.2 แสดงความกระตือรือร้นและพร้อมที่จะเรียนรู้สิ่งใหม่ๆ",
        "13.3 แสดงความตั้งใจที่จะพัฒนาตนเอง",
    ];
    $row = createCompetencyBlock($sheet3, $row, $mainTitle13, $subTopics13, $styles_array);



    $sheet3->setCellValue('A' . $row, 'สรุปผลการประเมินทั้งปี');
    $sheet3->mergeCells('A' . $row . ':O' . $row);
    $sheet3->getStyle('A' . $row)->getFont()->setBold(true);
    $row++;
    $row++; // เว้น 1 แถว

    // --- 2. แถวคะแนน 500 ---
// (ตัวแปร $score_500 ถูกกำหนดค่าไว้แล้วจากส่วน Footer ก่อนหน้านี้)
    $sheet3->setCellValue('B' . $row, 'ส่วนที่ 1: การประเมินผลตามเป้าหมายที่กำหนด');
    $sheet3->mergeCells('B' . $row . ':G' . $row); // Label
    $sheet3->setCellValue('H' . $row, number_format(floatval($score_500), 2)); // Value
    $sheet3->mergeCells('H' . $row . ':J' . $row);
    $sheet3->setCellValue('K' . $row, 'คะแนน, ของคะแนนเต็ม 500 คะแนน');
    $sheet3->mergeCells('K' . $row . ':O' . $row); // Unit

    // (ใช้ Styles ที่มีอยู่)
    $sheet3->getStyle('B' . $row . ':G' . $row)->applyFromArray($style_grand_total_label_array); // จัดชิดขวา
    $sheet3->getStyle('H' . $row . ':J' . $row)->applyFromArray($style_grand_total_value_array); // ตีเส้นขอบ
    $sheet3->getStyle('K' . $row . ':O' . $row)->getFont()->setBold(true); // ตัวหนา
    $row++;

    // --- 3. แถวคะแนน 100 ---
// (ตัวแปร $score_100 ถูกกำหนดค่าไว้แล้วจากส่วน Footer ก่อนหน้านี้)
    $sheet3->setCellValue('B' . $row, 'คะแนนของส่วนที่ 1 (อัตราส่วน 80) =');
    $sheet3->mergeCells('B' . $row . ':G' . $row); // Label
    $sheet3->setCellValue('H' . $row, number_format(floatval($score_100), 2)); // Value
    $sheet3->mergeCells('H' . $row . ':J' . $row);
    $sheet3->setCellValue('K' . $row, '%');
    $sheet3->mergeCells('K' . $row . ':O' . $row); // Unit

    // (ใช้ Styles ที่มีอยู่)
    $sheet3->getStyle('B' . $row . ':G' . $row)->applyFromArray($style_grand_total_label_array); // จัดชิดขวา
    $sheet3->getStyle('H' . $row . ':J' . $row)->applyFromArray($style_grand_total_value_array); // ตีเส้นขอบ
    $sheet3->getStyle('K' . $row . ':O' . $row)->getFont()->setBold(true); // ตัวหนา
    $row++; // เว้น 1 แถว
    $row++; // เว้นอีก 1 แถว
    // --- แถวที่ 1: "ส่วนที่ 2: การประเมินผลตามเป้าหมายที่กำหนด..." ---
    $sheet3->setCellValue('B' . $row, 'ส่วนที่ 2: การประเมินผลตามเป้าหมายที่กำหนด :');
    $sheet3->mergeCells('B' . $row . ':G' . $row);
    $sheet3->getStyle('B' . $row . ':G' . $row)->applyFromArray($style_grand_total_label_array); // แก้ไขชื่อ Style

    // ช่องกรอกที่ 1 (คะแนนที่ได้)
    $sheet3->setCellValue('H' . $row, ''); // <-- ช่องว่างให้ผู้ใช้กรอก
    $sheet3->mergeCells('H' . $row . ':J' . $row);
    $sheet3->getStyle('H' . $row . ':J' . $row)->applyFromArray($style_grand_total_value_array); // แก้ไขชื่อ Style

    // ข้อความ "คะแนน, ของคะแนนเต็ม"
    $sheet3->setCellValue('K' . $row, 'คะแนน, ของคะแนนเต็ม 40 คะแนน');
    $sheet3->mergeCells('K' . $row . ':O' . $row); // Unit

    $row++;

    // --- แถวที่ 2: "คะแนนของส่วนที่ 2 (อัตราส่วน 40)..." ---
    $sheet3->setCellValue('B' . $row, 'คะแนนของส่วนที่ 2 (อัตราส่วน 20) =');
    $sheet3->mergeCells('B' . $row . ':G' . $row);
    $sheet3->getStyle('B' . $row . ':G' . $row)->applyFromArray($style_grand_total_label_array); // แก้ไขชื่อ Style

    // ช่องกรอกที่ 3 (ผลลัพธ์ %)
    $sheet3->setCellValue('H' . $row, ''); // <-- ช่องว่างให้ผู้ใช้กรอก
    $sheet3->mergeCells('H' . $row . ':J' . $row);
    $sheet3->getStyle('H' . $row . ':J' . $row)->applyFromArray($style_grand_total_value_array); // แก้ไขชื่อ Style

    $sheet3->setCellValue('K' . $row, '%');
    $sheet3->getStyle('K' . $row)->getFont()->setBold(true);
    $row++;
    $row++; // เว้น 1 แถว


    // --- 1. หัวข้อหลัก "ข้อเสนอแนะ..." ---
    $sheet3->setCellValue('A' . $row, 'ข้อเสนอแนะจากหัวหน้างาน / ผู้บังคับบัญชา');
    $sheet3->mergeCells('A' . $row . ':O' . $row);
    $sheet3->getStyle('A' . $row)->getFont()->setBold(true);
    $row++;

    // --- 2. หัวตารางย่อย (เรื่อง / ครึ่งปีแรก / ครึ่งปีหลัง) ---
    $headerFeedbackRow = $row;
    $sheet3->setCellValue('A' . $headerFeedbackRow, 'เรื่อง');
    $sheet3->mergeCells('A' . $headerFeedbackRow . ':E' . $headerFeedbackRow); // คอลัมน์ 1
    $sheet3->setCellValue('F' . $headerFeedbackRow, 'ครึ่งปีแรก');
    $sheet3->mergeCells('F' . $headerFeedbackRow . ':J' . $headerFeedbackRow); // คอลัมน์ 2
    $sheet3->setCellValue('K' . $headerFeedbackRow, 'ครึ่งปีหลัง');
    $sheet3->mergeCells('K' . $headerFeedbackRow . ':O' . $headerFeedbackRow); // คอลัมน์ 3
    $sheet3->getStyle('A' . $headerFeedbackRow . ':O' . $headerFeedbackRow)->applyFromArray($tab3_header_style_array); // แก้ไขชื่อ Style
    $row++;

    // --- 3. แถว "จุดเด่น" ---
    $dataStrengthsRow = $row;
    $sheet3->setCellValue('A' . $dataStrengthsRow, 'จุดเด่น');
    $sheet3->mergeCells('A' . $dataStrengthsRow . ':E' . $dataStrengthsRow);
    // (ดึงข้อมูลจาก $saved_okr_data)
    $sheet3->setCellValue('F' . $dataStrengthsRow, htmlspecialchars($saved_okr_data['feedback_strengths_h1'] ?? ''));
    $sheet3->mergeCells('F' . $dataStrengthsRow . ':J' . $dataStrengthsRow);
    $sheet3->setCellValue('K' . $dataStrengthsRow, htmlspecialchars($saved_okr_data['feedback_strengths_h2'] ?? ''));
    $sheet3->mergeCells('K' . $dataStrengthsRow . ':O' . $dataStrengthsRow);

    // ใช้ Style (ตีเส้นขอบ + ตัดคำ)
    $sheet3->getStyle('A' . $dataStrengthsRow . ':O' . $dataStrengthsRow)->applyFromArray($style_all_borders_array); // แก้ไขชื่อ Style
    $sheet3->getStyle('A' . $dataStrengthsRow . ':O' . $dataStrengthsRow)->applyFromArray($style_align_wrap_text_array); // แก้ไขชื่อ Style
    $sheet3->getStyle('A' . $dataStrengthsRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // (จัด "จุดเด่น" ชิดซ้าย)
    $sheet3->getRowDimension($dataStrengthsRow)->setRowHeight(80); // ตั้งค่าความสูงแถว
    $row++;

    // --- 4. แถว "จุดที่ควรปรับปรุง" ---
    $dataImprovementRow = $row;
    $sheet3->setCellValue('A' . $dataImprovementRow, 'จุดที่ควรปรับปรุง');
    $sheet3->mergeCells('A' . $dataImprovementRow . ':E' . $dataImprovementRow);
    // (ดึงข้อมูลจาก $saved_okr_data)
    $sheet3->setCellValue('F' . $dataImprovementRow, htmlspecialchars($saved_okr_data['feedback_improvement_h1'] ?? ''));
    $sheet3->mergeCells('F' . $dataImprovementRow . ':J' . $dataImprovementRow);
    $sheet3->setCellValue('K' . $dataImprovementRow, htmlspecialchars($saved_okr_data['feedback_improvement_h2'] ?? ''));
    $sheet3->mergeCells('K' . $dataImprovementRow . ':O' . $dataImprovementRow); // แก้ไข $dataStrengthsRow เป็น $dataImprovementRow

    // ใช้ Style (ตีเส้นขอบ + ตัดคำ)
    $sheet3->getStyle('A' . $dataImprovementRow . ':O' . $dataImprovementRow)->applyFromArray($style_all_borders_array); // แก้ไขชื่อ Style
    $sheet3->getStyle('A' . $dataImprovementRow . ':O' . $dataImprovementRow)->applyFromArray($style_align_wrap_text_array); // แก้ไขชื่อ Style
    $sheet3->getStyle('A' . $dataImprovementRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // (จัด "จุดที่ควรปรับปรุง" ชิดซ้าย)
    $sheet3->getRowDimension($dataImprovementRow)->setRowHeight(80); // ตั้งค่าความสูงแถว
    $row++; // เว้น 1 แถว
    $row++; // เว้นอีก 1 แถว

    // --- 1. หัวข้อหลัก "ความเหมาะสม..." ---
    $sheet3->setCellValue('A' . $row, 'ความเหมาะสมในการปฏิบัติหน้าที่ (พร้อมระบุเหตุผล/ช่วงเวลาที่เหมาะสม)');
    $sheet3->mergeCells('A' . $row . ':O' . $row);
    $sheet3->getStyle('A' . $row)->getFont()->setBold(true);
    $row++;

    // --- 2. หัวตารางย่อย (รอบ / เหมาะสม... / ควรเพิ่ม... / ควรย้าย...) ---
    $headerSuitabilityRow = $row;
    $sheet3->setCellValue('A' . $headerSuitabilityRow, 'รอบ');
    $sheet3->mergeCells('A' . $headerSuitabilityRow . ':C' . $headerSuitabilityRow); // คอลัมน์ 1 (3 cols)
    $sheet3->setCellValue('D' . $headerSuitabilityRow, 'เหมาะสมกับตำแหน่งหน้าที่เดิม');
    $sheet3->mergeCells('D' . $headerSuitabilityRow . ':G' . $headerSuitabilityRow); // คอลัมน์ 2 (4 cols)
    $sheet3->setCellValue('H' . $headerSuitabilityRow, 'ควรรับผิดชอบงานเพิ่มขึ้น');
    $sheet3->mergeCells('H' . $headerSuitabilityRow . ':K' . $headerSuitabilityRow); // คอลัมน์ 3 (4 cols)
    $sheet3->setCellValue('L' . $headerSuitabilityRow, 'ควรโยกย้ายไปรับหน้าที่ใหม่/เลื่อนระดับ');
    $sheet3->mergeCells('L' . $headerSuitabilityRow . ':O' . $headerSuitabilityRow); // คอลัมน์ 4 (4 cols)
    $sheet3->getStyle('A' . $headerSuitabilityRow . ':O' . $headerSuitabilityRow)->applyFromArray($tab3_header_style_array); // แก้ไขชื่อ Style
    $sheet3->getRowDimension($headerSuitabilityRow)->setRowHeight(40); // เพิ่มความสูงหัวตาราง
    $row++;

    // --- 3. แถว "ครึ่งปีแรก" ---
    $dataSuitabilityH1 = $row;
    $sheet3->setCellValue('A' . $dataSuitabilityH1, 'ครึ่งปีแรก');
    $sheet3->mergeCells('A' . $dataSuitabilityH1 . ':C' . $dataSuitabilityH1);
    // (ดึงข้อมูลจาก $saved_okr_data)
    $sheet3->setCellValue('D' . $dataSuitabilityH1, htmlspecialchars($saved_okr_data['suitability_h1_current'] ?? ''));
    $sheet3->mergeCells('D' . $dataSuitabilityH1 . ':G' . $dataSuitabilityH1);
    $sheet3->setCellValue('H' . $dataSuitabilityH1, htmlspecialchars($saved_okr_data['suitability_h1_increase'] ?? ''));
    $sheet3->mergeCells('H' . $dataSuitabilityH1 . ':K' . $dataSuitabilityH1);
    $sheet3->setCellValue('L' . $dataSuitabilityH1, htmlspecialchars($saved_okr_data['suitability_h1_move'] ?? ''));
    $sheet3->mergeCells('L' . $dataSuitabilityH1 . ':O' . $dataSuitabilityH1);

    // ใช้ Style (ตีเส้นขอบ + ตัดคำ)
    $sheet3->getStyle('A' . $dataSuitabilityH1 . ':O' . $dataSuitabilityH1)->applyFromArray($style_all_borders_array); // แก้ไขชื่อ Style
    $sheet3->getStyle('A' . $dataSuitabilityH1 . ':O' . $dataSuitabilityH1)->applyFromArray($style_align_wrap_text_array); // แก้ไขชื่อ Style
    $sheet3->getStyle('A' . $dataSuitabilityH1)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // (จัด "ครึ่งปีแรก" ชิดซ้าย)
    $sheet3->getRowDimension($dataSuitabilityH1)->setRowHeight(80); // ตั้งค่าความสูงแถว
    $row++;

    // --- 4. แถว "ครึ่งปีหลัง" ---
    $dataSuitabilityH2 = $row;
    $sheet3->setCellValue('A' . $dataSuitabilityH2, 'ครึ่งปีหลัง');
    $sheet3->mergeCells('A' . $dataSuitabilityH2 . ':C' . $dataSuitabilityH2);
    // (ดึงข้อมูลจาก $saved_okr_data)
    $sheet3->setCellValue('D' . $dataSuitabilityH2, htmlspecialchars($saved_okr_data['suitability_h2_current'] ?? ''));
    $sheet3->mergeCells('D' . $dataSuitabilityH2 . ':G' . $dataSuitabilityH2);
    $sheet3->setCellValue('H' . $dataSuitabilityH2, htmlspecialchars($saved_okr_data['suitability_h2_increase'] ?? ''));
    $sheet3->mergeCells('H' . $dataSuitabilityH2 . ':K' . $dataSuitabilityH2);
    $sheet3->setCellValue('L' . $dataSuitabilityH2, htmlspecialchars($saved_okr_data['suitability_h2_move'] ?? ''));
    $sheet3->mergeCells('L' . $dataSuitabilityH2 . ':O' . $dataSuitabilityH2);

    // ใช้ Style (ตีเส้นขอบ + ตัดคำ)
    $sheet3->getStyle('A' . $dataSuitabilityH2 . ':O' . $dataSuitabilityH2)->applyFromArray($style_all_borders_array); // แก้ไขชื่อ Style
    $sheet3->getStyle('A' . $dataSuitabilityH2 . ':O' . $dataSuitabilityH2)->applyFromArray($style_align_wrap_text_array); // แก้ไขชื่อ Style
    $sheet3->getStyle('A' . $dataSuitabilityH2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // (จัด "ครึ่งปีหลัง" ชิดซ้าย)
    $sheet3->getRowDimension($dataSuitabilityH2)->setRowHeight(80); // ตั้งค่าความสูงแถว

    // เลื่อน $row ไปอีกเพื่อเตรียมพื้นที่สำหรับ "ส่วนที่ 2: แผนการพัฒนา"
    $row = $row + 3;

} else {
    $sheet3->setCellValue('A28', '--- ไม่มีข้อมูล ---'); // (แก้ไข) ปรับเลขแถว
    $sheet3->mergeCells('A28:O28');
    $sheet3->getStyle('A28:O28')->applyFromArray($style_all_borders_array); // แก้ไขชื่อ Style
    $row = 29; // ตั้งค่า $row เผื่อกรณีไม่มีข้อมูล
}
// --- [END] จบส่วนข้อมูลหลัก Tab 3 ---

// --- ส่วนที่ 2: แผนการพัฒนา (Development Plan) (เพิ่มตาม CSV) ---
$sheet3->setCellValue('A' . $row, 'ส่วนที่ 3 : แผนการพัฒนา /SECTION THIRD – DEVELOPMENT PLAN');
$sheet3->getStyle('A' . $row)->getFont()->setBold(true);
$sheet3->mergeCells('A' . $row . ':O' . $row);
$row++;

$sheet3->setCellValue('A' . $row, "การกำหนดแนวทางการฝึกอบรมและพัฒนาที่จะช่วยปรับปรุงการปฏิบัติงานหรือพัฒนาเส้นทางอาชีพของพนักงาน
Identification of training and development options to fulfill the Appraiser’s current job requirements or career development needs.  ");
$sheet3->mergeCells('A' . $row . ':O' . $row);
$sheet3->getStyle('A' . $row)->getAlignment()->setWrapText(true);
$sheet3->getRowDimension($row)->setRowHeight(50); // เพิ่มความสูง
$row++;
$row++; // เว้นว่าง

// --- หัวข้อแผนครึ่งปีแรก ---
$sheet3->setCellValue('A' . $row, 'ครึ่งปีแรก');
$sheet3->getStyle('A' . $row)->getFont()->setBold(true);
$sheet3->mergeCells('A' . $row . ':O' . $row);
$sheet3->getStyle('A' . $row)->applyFromArray($style_align_center_array); // จัดกลาง
$row++;

$headerRow = $row;
$sheet3->setCellValue('A' . $headerRow, "แนวทางการพัฒนาและปรับปรุงผลการปฏิบัติงาน\nPerformance Focus");
$sheet3->mergeCells('A' . $headerRow . ':E' . $headerRow);
$sheet3->setCellValue('F' . $headerRow, "แนวปฏิบัติ / ผู้รับผิดชอบ \n(เช่น โครงการ การหมุนเวียนงาน)\nActions / Accountability");
$sheet3->mergeCells('F' . $headerRow . ':K' . $headerRow);
$sheet3->setCellValue('L' . $headerRow, "ความคิดเห็นจากการประเมินผลงานรอบครึ่งปีแรก\nMid-Year Review Comments");
$sheet3->mergeCells('L' . $headerRow . ':O' . $headerRow);
$sheet3->getStyle('A' . $headerRow . ':O' . $headerRow)->applyFromArray($tab3_header_style_array); // แก้ไขชื่อ Style
$sheet3->getRowDimension($headerRow)->setRowHeight(80);
$row++;


// เราจะวน 3 "ชุด"
// โดยใช้ $i = 1 (ชุดที่ 1), $i = 3 (ชุดที่ 2), $i = 5 (ชุดที่ 3)
// $i += 2 หมายถึง ให้ $i เพิ่มทีละ 2
for ($i = 1; $i <= 5; $i += 2) {

    // --- แถว "แผน" (ใช้ $i) ---
    // (รอบแรก $i = 1, รอบสอง $i = 3, รอบสาม $i = 5)
    $sheet3->setCellValue('A' . $row, 'แผน');
    $sheet3->setCellValue('B' . $row, htmlspecialchars($saved_okr_data["dev_plan_h1_plan{$i}"] ?? ''));
    $sheet3->mergeCells('B' . $row . ':E' . $row);
    $sheet3->setCellValue('F' . $row, htmlspecialchars($saved_okr_data["dev_plan_h1_results{$i}"] ?? ''));
    $sheet3->mergeCells('F' . $row . ':K' . $row);
    $sheet3->setCellValue('L' . $row, htmlspecialchars($saved_okr_data["dev_plan_h1_comments{$i}"] ?? ''));
    $sheet3->mergeCells('L' . $row . ':O' . $row);

    // ใช้ Style กับแถว "แผน"
    $sheet3->getStyle('A' . $row . ':O' . $row)->applyFromArray($style_all_borders_array); // แก้ไขชื่อ Style
    $sheet3->getStyle('A' . $row)->applyFromArray($style_plan_label_array); // แก้ไขชื่อ Style
    $sheet3->getRowDimension($row)->setRowHeight(40);

    // [สำคัญ] เพิ่ม $row 1 ครั้ง
    $row++;

    // --- แถว "ผล" (ใช้ $i + 1) ---
    // (รอบแรก $j = 2, รอบสอง $j = 4, รอบสาม $j = 6)
    $j = $i + 1; // สร้างตัวแปร $j (คือ $i + 1) เพื่อให้โค้ดอ่านง่าย

    $sheet3->setCellValue('A' . $row, 'ผล');
    // ใช้ $j กับข้อมูล "ผล" ทั้งหมด
    $sheet3->setCellValue('B' . $row, htmlspecialchars($saved_okr_data["dev_plan_h1_plan{$j}"] ?? ''));
    $sheet3->mergeCells('B' . $row . ':E' . $row);
    $sheet3->setCellValue('F' . $row, htmlspecialchars($saved_okr_data["dev_plan_h1_results{$j}"] ?? ''));
    $sheet3->mergeCells('F' . $row . ':K' . $row);
    $sheet3->setCellValue('L' . $row, htmlspecialchars($saved_okr_data["dev_plan_h1_comments{$j}"] ?? ''));
    $sheet3->mergeCells('L' . $row . ':O' . $row);

    // ใช้ Style กับแถว "ผล"
    $sheet3->getStyle('A' . $row . ':O' . $row)->applyFromArray($style_all_borders_array); // แก้ไขชื่อ Style
    // $sheet3->getStyle('A' . $row)->applyFromArray($style_result_label); // (ถ้ามี Style ของ "ผล")
    $sheet3->getRowDimension($row)->setRowHeight(40);

    // [สำคัญ] เพิ่ม $row อีกครั้ง
    $row++;
}

$row++; // เว้นว่าง
// --- หัวข้อแผนครึ่งปีหลัง ---
$sheet3->setCellValue('A' . $row, 'ครึ่งปีหลัง');
$sheet3->getStyle('A' . $row)->getFont()->setBold(true);
$sheet3->mergeCells('A' . $row . ':O' . $row);
$sheet3->getStyle('A' . $row)->applyFromArray($style_align_center_array); // จัดกลาง
$row++;

$headerRow = $row;
$sheet3->setCellValue('A' . $headerRow, "แนวทางการพัฒนาและปรับปรุงผลการปฏิบัติงาน\nPerformance Focus");
$sheet3->mergeCells('A' . $headerRow . ':E' . $headerRow);
$sheet3->setCellValue('F' . $headerRow, "แนวปฏิบัติ / ผู้รับผิดชอบ \n(เช่น โครงการ การหมุนเวียนงาน)\nActions / Accountability");
$sheet3->mergeCells('F' . $headerRow . ':K' . $headerRow);
$sheet3->setCellValue('L' . $headerRow, "ความคิดเห็นจากการประเมินผลงานรอบปลายปี\nYear-End Review Comments");
$sheet3->mergeCells('L' . $headerRow . ':O' . $headerRow);
$sheet3->getStyle('A' . $headerRow . ':O' . $headerRow)->applyFromArray($tab3_header_style_array); // แก้ไขชื่อ Style
$sheet3->getRowDimension($headerRow)->setRowHeight(80);
$row++;


// เราจะวน 3 "ชุด"
// โดยใช้ $i = 1 (ชุดที่ 1), $i = 3 (ชุดที่ 2), $i = 5 (ชุดที่ 3)
// $i += 2 หมายถึง ให้ $i เพิ่มทีละ 2
// (ข้อควรระวัง: โค้ดด้านล่างนี้ยังคงดึงข้อมูล h1 มาใช้ คุณอาจจะต้องเปลี่ยนเป็น h2 ถ้าข้อมูลแยกกัน)
for ($i = 1; $i <= 5; $i += 2) {

    // --- แถว "แผน" (ใช้ $i) ---
    // (รอบแรก $i = 1, รอบสอง $i = 3, รอบสาม $i = 5)
    $sheet3->setCellValue('A' . $row, 'แผน');
    $sheet3->setCellValue('B' . $row, htmlspecialchars($saved_okr_data["dev_plan_h2_plan{$i}"] ?? '')); // (แก้เป็น h2)
    $sheet3->mergeCells('B' . $row . ':E' . $row);
    $sheet3->setCellValue('F' . $row, htmlspecialchars($saved_okr_data["dev_plan_h2_results{$i}"] ?? '')); // (แก้เป็น h2)
    $sheet3->mergeCells('F' . $row . ':K' . $row);
    $sheet3->setCellValue('L' . $row, htmlspecialchars($saved_okr_data["dev_plan_h2_comments{$i}"] ?? '')); // (แก้เป็น h2)
    $sheet3->mergeCells('L' . $row . ':O' . $row);

    // ใช้ Style กับแถว "แผน"
    $sheet3->getStyle('A' . $row . ':O' . $row)->applyFromArray($style_all_borders_array); // แก้ไขชื่อ Style
    $sheet3->getStyle('A' . $row)->applyFromArray($style_plan_label_array); // แก้ไขชื่อ Style
    $sheet3->getRowDimension($row)->setRowHeight(40);

    // [สำคัญ] เพิ่ม $row 1 ครั้ง
    $row++;

    // --- แถว "ผล" (ใช้ $i + 1) ---
    // (รอบแรก $j = 2, รอบสอง $j = 4, รอบสาม $j = 6)
    $j = $i + 1; // สร้างตัวแปร $j (คือ $i + 1) เพื่อให้โค้ดอ่านง่าย

    $sheet3->setCellValue('A' . $row, 'ผล');
    // ใช้ $j กับข้อมูล "ผล" ทั้งหมด
    $sheet3->setCellValue('B' . $row, htmlspecialchars($saved_okr_data["dev_plan_h2_plan{$j}"] ?? '')); // (แก้เป็น h2)
    $sheet3->mergeCells('B' . $row . ':E' . $row);
    $sheet3->setCellValue('F' . $row, htmlspecialchars($saved_okr_data["dev_plan_h2_results{$j}"] ?? '')); // (แก้เป็น h2)
    $sheet3->mergeCells('F' . $row . ':K' . $row);
    $sheet3->setCellValue('L' . $row, htmlspecialchars($saved_okr_data["dev_plan_h2_comments{$j}"] ?? '')); // (แก้เป็น h2)
    $sheet3->mergeCells('L' . $row . ':O' . $row);

    // ใช้ Style กับแถว "ผล"
    $sheet3->getStyle('A' . $row . ':O' . $row)->applyFromArray($style_all_borders_array); // แก้ไขชื่อ Style
    // $sheet3->getStyle('A' . $row)->applyFromArray($style_result_label); // (ถ้ามี Style ของ "ผล")
    $sheet3->getRowDimension($row)->setRowHeight(40);

    // [สำคัญ] เพิ่ม $row อีกครั้ง
    $row++;
}
// --- [END] จบส่วนข้อมูล Tab 3 ---

// [END] (*** โค้ด Tab 3 ใหม่ของคุณที่แก้ไขแล้ว ***)

// ===================================================================
// ⬆⬆⬆ [END] โค้ดที่แก้ไขสำหรับ TAB 3 (โครงสร้างใหม่) ⬆⬆⬆
// ===================================================================


// 6. ส่งไฟล์ให้ User
// -----------------------------------------------------------------
// ตั้งค่า Active Sheet เป็นแผ่นแรก
$spreadsheet->setActiveSheetIndex(0);

// ตั้งชื่อไฟล์
//  แก้ไข: ใช้ตัวแปร $current_emp_code, $current_academic_year
$filename = 'KPI_Report_' . $current_emp_code . '_' . $current_academic_year . '.xlsx';

// ส่ง Header ให้ Browser
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
//  ลบ Header ที่ซ้ำซ้อนจากโค้ด Tab 3 ที่ผู้ใช้ส่งมา

// เขียนไฟล์
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

$conn->close();
exit;

?>