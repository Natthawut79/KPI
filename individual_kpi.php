<?php
$page_title = "แบบประเมินผลการปฏิบัติงาน";
include 'templates/navbar.php';
include 'config/conn.php';
include 'config/academic_year_resolver.php';
$system_current_academic_year = $current_academic_year;

if (!isset($_SESSION['Emp_code'])) {
    echo "<p>Error: กรุณา Login ก่อน.</p>";
    exit();
}

// --- [START] แก้ไข: ตรวจสอบว่าดูของใคร ---
$is_viewing_other = false; // ตั้งค่าเริ่มต้นว่าดูของตัวเอง

// ตรวจสอบว่ามีการส่ง Emp_code และ year มาจาก URL (mainsuper.php) หรือไม่
if (isset($_GET['Emp_code']) && !empty($_GET['Emp_code']) && isset($_GET['year']) && !empty($_GET['year'])) {

    // --- 1. ดูข้อมูลของคนอื่น (ที่คลิกมา) ---
    $is_viewing_other = true;
    $current_emp_code = $_GET['Emp_code']; // ใช้ Emp_code จาก URL
    $current_academic_year = $_GET['year']; // ใช้ year จาก URL

    // **สำคัญ:** ต้องค้นหา Type_id ของ User ที่กำลังดูข้อมูล
    $sql_get_user_type = "SELECT Type_id FROM user WHERE Emp_code = ? LIMIT 1";
    $stmt_get_type = $conn->prepare($sql_get_user_type);

    if ($stmt_get_type) {
        $stmt_get_type->bind_param("s", $current_emp_code);
        $stmt_get_type->execute();
        $result_get_type = $stmt_get_type->get_result();

        if ($row_type = $result_get_type->fetch_assoc()) {
            $user_type_id = $row_type['Type_id'];
        } else {
            // ไม่พบ User นี้
            echo "<p>Error: ไม่พบข้อมูลผู้ใช้ (Emp_code: " . htmlspecialchars($current_emp_code) . ").</p>";
            exit();
        }
        $stmt_get_type->close();
    } else {
        echo "<p>Error: ไม่สามารถเตรียมคำสั่งค้นหา Type_id ได้.</p>";
        exit();
    }

} else {
    // --- 2. ดูข้อมูลของตัวเอง (Login) ---
    $current_emp_code = $_SESSION['Emp_code']; // ใช้ Emp_code ของคนที่ Login
    $user_type_id = $_SESSION['Type_id']; // ใช้ Type_id ของคนที่ Login


}
$is_approved = false; // ตั้งค่าเริ่มต้นว่ายังไม่อนุมัติ

// เตรียมคำสั่ง SQL เพื่อนับว่ามี KPI ที่อนุมัติแล้วหรือไม่
// (เราจะเช็คเฉพาะ Submit_type_id = 2 ซึ่งคือการส่งรอบสุดท้าย)
$sql_check_approve = "SELECT COUNT(*) as approval_count
                      FROM individual_kpi
                      WHERE Emp_code = ?
                      AND Academic = ?
                      AND Approve_id = 2";

$stmt_check = $conn->prepare($sql_check_approve);

if ($stmt_check) {
    $stmt_check->bind_param("si", $current_emp_code, $current_academic_year);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();

    // ถ้าพบนับได้มากกว่า 0 แถว แปลว่าอนุมัติแล้ว
    if ($row_check && $row_check['approval_count'] > 0) {
        $is_approved = true;
    }
    $stmt_check->close();
}
// --- [END] สิ้นสุดการแก้ไข ---
$viewed_user_data = null;
$sql_get_user_info = "SELECT Fname_th, Lname_th FROM employee WHERE Emp_code = ? LIMIT 1";
$stmt_get_info = $conn->prepare($sql_get_user_info);

if ($stmt_get_info) {
    $stmt_get_info->bind_param("s", $current_emp_code);
    $stmt_get_info->execute();
    $result_get_info = $stmt_get_info->get_result();
    if ($row_info = $result_get_info->fetch_assoc()) {
        $viewed_user_data = $row_info;
    }
    $stmt_get_info->close();
}

if ($viewed_user_data === null) {
    // ถ้าไม่พบข้อมูล (ซึ่งไม่ควรเกิดขึ้นถ้าโค้ดก่อนหน้าทำงานถูก)
    $viewed_user_data = ['Fname_th' => 'ไม่พบ', 'Lname_th' => 'ข้อมูล'];
}

// 1. ตั้งค่าโซนเวลาประเทศไทย
date_default_timezone_set('Asia/Bangkok');
$current_datetime_str = date('Y-m-d H:i:s');

// 2. ตั้งค่าตัวแปรเริ่มต้น (Default = ปิด)
$is_editable = false;
$active_submit_type_id = 0;
$active_period_name = "นอกช่วงเวลาทำการ";

// --- [START] แก้ไข: ตรวจสอบสิทธิ์การแก้ไข (Logic ใหม่) ---

// ตรวจสอบว่ามี mode=edit ส่งมาจาก URL (จาก approve_kpi.php) หรือไม่
$is_admin_edit_mode = (isset($_GET['mode']) && $_GET['mode'] === 'edit');

// (ดึงปีปัจจุบันจริงๆ มาเทียบ)
$actual_current_year_ph = $system_current_academic_year;

// กรณีที่ 1: Admin กดแก้ไข (มาจาก approve_kpi.php)
// ($is_viewing_other คือ true เพราะมี Emp_code ใน URL)
if ($is_viewing_other && $is_admin_edit_mode) {

    // นี่คือ Admin ที่กด "แก้ไข"
    // เราจะตรวจสอบว่า "ระบบเปิดให้บันทึกหรือไม่" (เพื่อให้ Admin แก้ไขได้เฉพาะช่วงเวลาที่กำหนด)

    $sql_check_active_period = "SELECT ts.Status, ts.Submit_type_id, st.Submit_type_name
                                FROM toggles_switch ts
                                JOIN submit_type st ON ts.Submit_type_id = st.Submit_type_id
                                WHERE ? BETWEEN ts.Start_date AND ts.End_date
                                LIMIT 1";
    $stmt_check = $conn->prepare($sql_check_active_period);

    if ($stmt_check) {
        $stmt_check->bind_param("s", $current_datetime_str);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($row = $result_check->fetch_assoc()) {
            $active_submit_type_id = $row['Submit_type_id'];
            $active_period_name = $row['Submit_type_name'] . " (Admin Mode)";

            if ($row['Status'] === 'เปิด') {
                $is_editable = true; // Admin แก้ไขได้
            } else {
                $is_editable = false; // Admin ก็แก้ไขไม่ได้ถ้าระบบปิด
                $active_period_name = "ระบบปิดรับการบันทึก (Admin Mode)";
            }
        } else {
            $is_editable = false; // นอกช่วงเวลา Admin ก็แก้ไม่ได้
            $active_period_name = "นอกช่วงเวลาทำการ (Admin Mode)";
        }
        $stmt_check->close();
    } else {
        $is_editable = false;
        $active_period_name = "ระบบขัดข้อง";
    }

}
// กรณีที่ 2: ดูของคนอื่น (มาจาก mainsuper.php (ไม่มี mode=edit))
elseif ($is_viewing_other) {
    $is_editable = false; // ห้ามแก้ไขเด็ดขาด
    $active_period_name = "โหมดดูข้อมูล (Read-Only)";
}
// กรณีที่ 3: ดูของตัวเอง (แต่เป็นปีเก่า)
// ($current_academic_year คือปีที่กำลังดูอยู่ (ซึ่งอาจเป็นปีเก่าจาก mainsuper หรือปีปัจจุบันจาก session))
elseif ($current_academic_year != $actual_current_year_ph) {
    $is_editable = false; // ห้ามแก้ไขเด็ดขาด
    $active_period_name = "ดูข้อมูลย้อนหลัง (Read-Only)";
}
// กรณีที่ 4: ดูของตัวเอง (ปีปัจจุบัน)
else {
    // (ใช้โค้ดเดิมที่ตรวจสอบ toggles_switch ตามปกติ)
    $sql_check_active_period = "SELECT ts.Status, ts.Submit_type_id, st.Submit_type_name
                                FROM toggles_switch ts
                                JOIN submit_type st ON ts.Submit_type_id = st.Submit_type_id
                                WHERE ? BETWEEN ts.Start_date AND ts.End_date
                                LIMIT 1";

    $stmt_check = $conn->prepare($sql_check_active_period);

    if ($stmt_check) {
        $stmt_check->bind_param("s", $current_datetime_str);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($row = $result_check->fetch_assoc()) {
            $active_submit_type_id = $row['Submit_type_id'];
            $active_period_name = $row['Submit_type_name'];

            if ($row['Status'] === 'เปิด') {
                $is_editable = true; // สถานะเป็น "เปิด"
            }
            // (ถ้าไม่เจอ หรือ Status ปิด $is_editable จะยังเป็น false ตามค่าเริ่มต้น)
        }
        // (ถ้าไม่เจอช่วงเวลา $is_editable จะยังเป็น false ตามค่าเริ่มต้น)
        $stmt_check->close();
    } else {
        $is_editable = false;
        $active_period_name = "ระบบขัดข้อง";
    }
}
// --- [END] สิ้นสุดการแก้ไข ---

// 6. สร้างตัวแปรสำหรับ disable แค่ชุดเดียว
$disable_all_attr = !$is_editable ? 'disabled' : '';
$disable_all_title = !$is_editable ? 'title="ระบบปิดรับการบันทึก"' : 'title="ระบบเปิดให้บันทึก"';

// --- [สิ้นสุดส่วนตรวจสอบสถานะ] ---


// --- ดึงข้อมูล KPI Types และ Topics ... (ต่อไป)
$kpi_data = []; // เก็บข้อมูล Type และ Topics
$all_topic_ids_flat = []; // เก็บ Topic ID ทั้งหมดที่ต้องแสดง
$topic_details_for_calc = []; // เก็บ Weight และ Type ID ของแต่ละ Topic

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

$stmt_kpi_types = mysqli_prepare($conn, $sql_kpi_types);
mysqli_stmt_bind_param($stmt_kpi_types, "ii", $user_type_id, $current_academic_year);
mysqli_stmt_execute($stmt_kpi_types);
$result_kpi_types = mysqli_stmt_get_result($stmt_kpi_types);

if ($result_kpi_types) {
    while ($type_row = mysqli_fetch_assoc($result_kpi_types)) {
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
                                kt.Additional as Additional,
                                kt.Retrieve,    
                                kt.Table_id,
                                kt.Publication_type_id
                              FROM kpi_topic kt
                              JOIN kpi_type ktype ON kt.KPI_type_id = ktype.KPI_type_id
                              WHERE kt.KPI_type_id = ?
                              ORDER BY kt.Order_no ASC";

        $stmt_topics = mysqli_prepare($conn, $sql_topics);
        mysqli_stmt_bind_param($stmt_topics, "i", $current_kpi_type_id);
        mysqli_stmt_execute($stmt_topics);
        $result_topics = mysqli_stmt_get_result($stmt_topics);

        if ($result_topics) {
            while ($topic_row = mysqli_fetch_assoc($result_topics)) {
                $topic_id = $topic_row['KPI_topic_id'];
                $topics[$topic_id] = $topic_row;
                $all_topic_ids_flat[] = $topic_id;
                $topic_details_for_calc[$topic_id] = [
                    'weight' => $topic_row['weight'],
                    'kpi_type_id' => $current_kpi_type_id
                ];
            }
        }
        mysqli_stmt_close($stmt_topics);

        $type_row['topics'] = $topics;
        $kpi_data[$current_kpi_type_id] = $type_row;
    }
}
mysqli_stmt_close($stmt_kpi_types);
$all_topic_ids_flat = array_unique($all_topic_ids_flat);

// ดึงข้อมูล User Type (โค้ดเดิมของคุณ)
$user_type_details = null;
if (isset($user_type_id)) {
    $sql_user_type = "SELECT Type_id, Type_name, Type_name_th
                                  FROM user_type
                                  WHERE Type_id = ? LIMIT 1";
    $stmt_user_type = mysqli_prepare($conn, $sql_user_type);
    if ($stmt_user_type) {
        mysqli_stmt_bind_param($stmt_user_type, "i", $user_type_id);
        mysqli_stmt_execute($stmt_user_type);
        $res_user_type = mysqli_stmt_get_result($stmt_user_type);
        if ($res_user_type) {
            $user_type_details = mysqli_fetch_assoc($res_user_type);
        }
        mysqli_stmt_close($stmt_user_type);
    } else {
        error_log("Error preparing statement for user type: " . mysqli_error($conn));
        echo "<p>Error fetching user type data.</p>";
    }
}



$saved_review_data = [];

if (!empty($all_topic_ids_flat)) {
    $topic_ids_placeholder = implode(',', array_fill(0, count($all_topic_ids_flat), '?'));



    $sql_saved_review = "SELECT KPI_topic_id, Submit_type_id, Goal_job, Actual_work, score, total_score, Actual_work_all_year, File_url, Additional, Advice  
                                  FROM individual_kpi
                                  WHERE Emp_code = ? AND Academic = ? AND KPI_topic_id IN ($topic_ids_placeholder)";


    $stmt_saved_review = mysqli_prepare($conn, $sql_saved_review);

    if ($stmt_saved_review) {
        $types = "si" . str_repeat('i', count($all_topic_ids_flat));
        $params = array_merge([$current_emp_code, $current_academic_year], $all_topic_ids_flat);
        mysqli_stmt_bind_param($stmt_saved_review, $types, ...$params);

        mysqli_stmt_execute($stmt_saved_review);
        $result_saved_review = mysqli_stmt_get_result($stmt_saved_review);
        if ($result_saved_review) {
            while ($row_saved = mysqli_fetch_assoc($result_saved_review)) {
                $key = $row_saved['KPI_topic_id'] . "_" . $row_saved['Submit_type_id'];
                $saved_review_data[$key] = $row_saved;
            }
        }
        mysqli_stmt_close($stmt_saved_review);
    } else {
        error_log("Error preparing statement to fetch saved data: " . mysqli_error($conn));
        echo "<p>Error fetching saved data: " . htmlspecialchars(mysqli_error($conn)) . "</p>";
        $saved_review_data = [];
    }
} else {
    $saved_review_data = [];
}



$saved_category_scores = [];
$sql_cat_scores = "SELECT KPI_type_id, Category_score
                                FROM score_evaluation
                                WHERE Emp_code = ? AND Academic = ? AND Submit_type_id = 2";
$stmt_cat_scores = mysqli_prepare($conn, $sql_cat_scores);
if ($stmt_cat_scores) {
    mysqli_stmt_bind_param($stmt_cat_scores, "si", $current_emp_code, $current_academic_year);
    mysqli_stmt_execute($stmt_cat_scores);
    $res_cat_scores = mysqli_stmt_get_result($stmt_cat_scores);
    if ($res_cat_scores) {
        while ($cat_row = mysqli_fetch_assoc($res_cat_scores)) {
            $saved_category_scores[$cat_row['KPI_type_id']] = $cat_row['Category_score'];
        }
    }
    mysqli_stmt_close($stmt_cat_scores);
} else {
    error_log("Error preparing statement for category scores: " . mysqli_error($conn));
    echo "<p>Error fetching category scores.</p>";
}



$saved_total_scores = null;
$sql_total_scores = "SELECT Score_500_max, Score_100_max
                                      FROM total_score_evaluation
                                      WHERE Emp_code = ? AND Academic = ? AND Submit_type_id = 2 LIMIT 1";
$stmt_total_scores = mysqli_prepare($conn, $sql_total_scores);
if ($stmt_total_scores) {
    mysqli_stmt_bind_param($stmt_total_scores, "si", $current_emp_code, $current_academic_year);
    mysqli_stmt_execute($stmt_total_scores);
    $res_total_scores = mysqli_stmt_get_result($stmt_total_scores);
    if ($res_total_scores) {
        $saved_total_scores = mysqli_fetch_assoc($res_total_scores);
    }
    mysqli_stmt_close($stmt_total_scores);
} else {
    error_log("Error preparing statement for total scores: " . mysqli_error($conn));
    echo "<p>Error fetching total scores.</p>";
}


// ⬇⬇⬇ [เพิ่มส่วนนี้] ⬇⬇⬇
// --- ดึงข้อมูลไฟล์แนบทั้งหมดจาก attach_file ---
$saved_attach_files = [];
if (!empty($all_topic_ids_flat) && !empty($current_emp_code)) {
    $topic_ids_placeholder_files = implode(',', array_fill(0, count($all_topic_ids_flat), '?'));

    // ค้นหาไฟล์ที่ Table_id = 1 (หน้านี้)
    // และ File_name LIKE '%/Emp_code/%' (เพื่อกรอง User)
    // และ Fk_table_id อยู่ใน Topic ID ที่แสดง
    $sql_files = "SELECT File_path, File_name, Fk_table_id 
                  FROM attach_file 
                  WHERE Table_id = ? 
                  AND File_name LIKE ? 
                  AND Fk_table_id IN ($topic_ids_placeholder_files)";

    $stmt_files = mysqli_prepare($conn, $sql_files);
    if ($stmt_files) {
        $table_id_kpi = 1; // 1 = individual_kpi
        $like_pattern = "%/" . $current_emp_code . "/%"; // เช่น '%/19891105/%'

        $types = "is" . str_repeat('i', count($all_topic_ids_flat)); // i, s, i, i, i...
        $params = array_merge([$table_id_kpi, $like_pattern], $all_topic_ids_flat);

        mysqli_stmt_bind_param($stmt_files, $types, ...$params);

        mysqli_stmt_execute($stmt_files);
        $res_files = mysqli_stmt_get_result($stmt_files);
        while ($file_row = mysqli_fetch_assoc($res_files)) {
            // จัดกลุ่มไฟล์ตาม Fk_table_id (topic_id)
            $saved_attach_files[$file_row['Fk_table_id']][] = $file_row;
        }
        mysqli_stmt_close($stmt_files);
    }
}

$user_research_details = [];
$user_research_files = []; // แยกเก็บไฟล์ไว้ใช้กับส่วน Upload

if (!empty($current_emp_code)) {
    // ใช้ Subquery ดึงชื่อนักวิจัยทั้งหมดที่เกี่ยวข้องกับ Research_id นั้นๆ มาต่อกันด้วยจุลภาค
    $sql_get_research = "SELECT 
                            r.Research_id,
                            r.Project_name_th,
                            r.Calendaryear,
                            r.Finance_name,
                            r.Amount_support,
                            af.File_path, 
                            af.File_name,
                            (
                                SELECT GROUP_CONCAT(CONCAT(e.Fname_th, ' ', e.Lname_th) SEPARATOR ', ')
                                FROM emp_research er2
                                JOIN employee e ON er2.Emp_code = e.Emp_code
                                WHERE er2.Research_id = r.Research_id
                            ) AS Researcher_List
                          FROM emp_research er
                          JOIN research r ON er.Research_id = r.Research_id
                          LEFT JOIN attach_file af ON (af.Fk_table_id = r.Research_id AND af.Table_id = 2)
                          WHERE er.Emp_code = ?";

    $stmt_res = $conn->prepare($sql_get_research);
    if ($stmt_res) {
        $stmt_res->bind_param("s", $current_emp_code);
        $stmt_res->execute();
        $res_res = $stmt_res->get_result();
        while ($row = $res_res->fetch_assoc()) {
            // เก็บไฟล์แยกสำหรับ Auto-fill input file
            if (!empty($row['File_name'])) {
                $user_research_files[] = $row;
            }
            // เก็บรายละเอียดสำหรับ APA
            // (เช็คซ้ำเพื่อไม่ให้แสดงโปรเจกต์เดิมหลายรอบกรณีมีหลายไฟล์แนบ)
            $found = false;
            foreach ($user_research_details as $exist) {
                if ($exist['Research_id'] == $row['Research_id']) {
                    $found = true; 
                    break;
                }
            }
            if (!$found) {
                $user_research_details[] = $row;
            }
        }
        $stmt_res->close();
    }
}

// [MODIFIED] ดึงข้อมูลงานตีพิมพ์ พร้อมรายชื่อผู้แต่งทุกคน
$user_publications_details = [];
$user_publications = []; // เก็บ URL/File สำหรับ logic เดิม

if (!empty($current_emp_code)) {
    $sql_get_pub = "SELECT 
                        p.Publication_id,
                        p.Publication_name,
                        p.Calendaryear,
                        p.Journal_name,
                        p.Copy,
                        p.No,
                        p.Page_no,
                        p.Accept_date,
                        p.URL,
                        p.Status,
                        af.File_name,
                        (
                            SELECT GROUP_CONCAT(CONCAT(e.Fname_th, ' ', e.Lname_th) SEPARATOR ', ')
                            FROM emp_publication ep2
                            JOIN employee e ON ep2.Emp_code = e.Emp_code
                            WHERE ep2.Emp_publication_id = p.Publication_id
                        ) AS Author_List
                    FROM emp_publication ep
                    JOIN publication p ON ep.Emp_publication_id = p.Publication_id 
                    LEFT JOIN attach_file af ON (af.Fk_table_id = p.Publication_id AND af.Table_id = 3)
                    WHERE ep.Emp_code = ?";
                              
    $stmt_pub = $conn->prepare($sql_get_pub);
    if ($stmt_pub) {
        $stmt_pub->bind_param("s", $current_emp_code);
        $stmt_pub->execute();
        $res_pub = $stmt_pub->get_result();
        while ($row_pub = $res_pub->fetch_assoc()) {
             // เก็บข้อมูลสำหรับ Logic เดิม (URL, File)
             $user_publications[$row_pub['Status']][] = $row_pub;
             
             // เก็บรายละเอียดสำหรับ APA (แยกตาม Status เช่นเดิม)
             $is_duplicate = false;
             if (isset($user_publications_details[$row_pub['Status']])) {
                 foreach ($user_publications_details[$row_pub['Status']] as $existing_pub) {
                     if ($existing_pub['Publication_id'] == $row_pub['Publication_id']) {
                         $is_duplicate = true;
                         break;
                     }
                 }
             }
             if (!$is_duplicate) {
                 $user_publications_details[$row_pub['Status']][] = $row_pub;
             }
        }
        $stmt_pub->close();
    }
}

// --- ดึงข้อมูล OKR (รอชื่อตาราง) --- (โค้ดเดิมของคุณ)
$saved_okr_data = null; // Placeholder

?>


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="css/individualceo.css">
<link rel="preconnect" href="https://googleapis.com">
<link rel="preconnect" href="https://gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">

<div class="container">
    <div class="status-header-bar"></div>
    <?php if (!$is_editable): ?>
        <div class="alert-warning"
            style="background-color: #fff3cd; color: #856404; padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; border: 1px solid #ffeeba;">
            <strong><i class="fas fa-exclamation-triangle"></i> ปิดการแก้ไข</strong>
            <?php if ($active_submit_type_id == 0): ?>

            <?php else: ?>
                ระบบปิดรับการบันทึกข้อมูล
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="tab-container tab-header-container">
        <div class="tabs-group tabs-group-wrapper">
            <button class="tab-link active" data-tab="main-evaluation-form">ตัวชี้วัดผลสำเร็จของงาน</button>
            <button class="tab-link" data-tab="define-kpi-form">แบบประเมินประจำปี</button>
            <button class="tab-link" data-tab="okr-evaluation-form">แบบประเมินผลการปฏิบัติงาน (OKRs)</button>
        </div>

        <?php if ($is_viewing_other): ?>
            <div class="user-info-box user-info-card">
                
                <div class="user-profile-section">
                    <div class="user-icon-badge">
                        <i class="fas fa-user"></i>
                    </div>
                    <span class="user-name-label">
                        <?php echo htmlspecialchars($viewed_user_data['Fname_th'] . ' ' . $viewed_user_data['Lname_th']); ?>
                    </span>
                </div>

                <div class="user-period-section">
                    <span>
                        <i class="fas fa-calendar calendar-icon-gray"></i> 
                        ปี <?php echo htmlspecialchars($current_academic_year); ?>
                    </span>
                    
                    <span class="period-badge-success">
                        <?php 
                            if ($active_submit_type_id == 1) echo 'ครึ่งปีแรก';
                            elseif ($active_submit_type_id == 2) echo 'ครึ่งปีหลัง';
                            else echo 'ผลงานทั้งปี';
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div id="main-evaluation-form" class="tab-content active">
        <div class="title-container">
            <div class="page-title-header text-center">
                <h2>ตัวชี้วัดผลการปฏิบัติงานของอาจารย์</h2>
                <h4>Key Performance Indicators (KPIs)</h4>
            </div>
        </div>
        <div class="form-container" style="border-top-left-radius: 0; border-top-right-radius: 0;">
            <table class="table table-bordered kpi-table" id="kpi-evaluation-table">
                <thead>
                    <tr style="text-align: center; vertical-align: middle;">
                        <th rowspan="2" style="width: 5%; text-align: center; vertical-align: top;">ลำดับ</th>
                        <th rowspan="2" style="width: 20%;  text-align: center; vertical-align: top;">ประเภทตัวชี้วัด</th>
                        <th rowspan="2" style="width: 8%;  text-align: center; vertical-align: top;">น้ำหนัก (%)</th>
                        <th rowspan="2" style="width: 42%;  text-align: center; vertical-align: top;">หัวข้อตัวชี้วัด</th>
                        <th colspan="3">ระดับความสำคัญ</th>
                    </tr>
                    <tr>
                        <th style="width: 8%;">น้อย</th>
                        <th style="width: 8%;">ปานกลาง</th>
                        <th style="width: 8%;">มาก</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kpi_data as $kpi_type_id => $kpi_type):
                        $topics_in_type = $kpi_type['topics'] ?? [];
                        $topic_count = count($topics_in_type);
                        $rowspan = ($topic_count > 0) ? $topic_count : 1;
                        $first_topic_key = ($topic_count > 0) ? array_key_first($topics_in_type) : null;
                        $temp_topics = $topics_in_type;
                        ?>
                        <tr>
                            <td rowspan="<?php echo $rowspan; ?>" class="order-col-top">
                                <?php echo htmlspecialchars($kpi_type['Order_No']); ?>
                            </td>
                            <td rowspan="<?php echo $rowspan; ?>" class="text-left">
                                <span
                                    class="category-title"><?php echo htmlspecialchars($kpi_type['KPI_Type_Name_EN']); ?></span><br>
                                (<?php echo htmlspecialchars($kpi_type['KPI_Type_Name_TH']); ?>)
                            </td>
                            <td style="text-align: center; vertical-align: top;" rowspan="<?php echo $rowspan; ?>"><?php echo htmlspecialchars($kpi_type['TypeWeight']); ?>
                            </td>

                            <?php if ($topic_count > 0 && $first_topic_key !== null):
                                $first_topic = $temp_topics[$first_topic_key];
                                $imp_level = $first_topic['Important_level_no'];
                                ?>
                                <td class="text-left"><?php echo htmlspecialchars($first_topic['name']); ?></td>
                                <td style="text-align: center; vertical-align: middle;"><input type="radio" class="form-check-input"
                                        name="rating_<?php echo $kpi_type_id; ?>_<?php echo $first_topic['KPI_topic_id']; ?>"
                                        value="1" <?php echo ($imp_level == 1) ? 'checked' : ''; ?> disabled></td>
                                <td style="text-align: center; vertical-align: middle;"><input type="radio" class="form-check-input"
                                        name="rating_<?php echo $kpi_type_id; ?>_<?php echo $first_topic['KPI_topic_id']; ?>"
                                        value="2" <?php echo ($imp_level == 2) ? 'checked' : ''; ?> disabled></td>
                                <td style="text-align: center; vertical-align: middle;"><input type="radio" class="form-check-input"
                                        name="rating_<?php echo $kpi_type_id; ?>_<?php echo $first_topic['KPI_topic_id']; ?>"
                                        value="3" <?php echo ($imp_level == 3) ? 'checked' : ''; ?> disabled></td>
                            <?php else: ?>
                                <td class="text-left text-muted" colspan="4" style="text-align: center;">-- ไม่มีหัวข้อตัวชี้วัด
                                    --</td>
                            <?php endif; ?>
                        </tr>
                        <?php
                        if ($first_topic_key !== null) {
                            unset($temp_topics[$first_topic_key]);
                        }
                        foreach ($temp_topics as $topic_id => $current_topic):
                            $imp_level = $current_topic['Important_level_no'];
                            ?>
                            <tr>
                                <td class="text-left"><?php echo htmlspecialchars($current_topic['name']); ?></td>
                                <td style="text-align: center; vertical-align: middle;"><input type="radio"  class="form-check-input"
                                        name="rating_<?php echo $kpi_type_id; ?>_<?php echo $topic_id; ?>" value="1" <?php echo ($imp_level == 1) ? 'checked' : ''; ?> disabled></td>
                                <td style="text-align: center; vertical-align: middle;"><input type="radio" style="text-align: center;" class="form-check-input"
                                        name="rating_<?php echo $kpi_type_id; ?>_<?php echo $topic_id; ?>" value="2" <?php echo ($imp_level == 2) ? 'checked' : ''; ?> disabled></td>
                                <td style="text-align: center; vertical-align: middle;"><input type="radio" style="text-align: center;" class="form-check-input"
                                        name="rating_<?php echo $kpi_type_id; ?>_<?php echo $topic_id; ?>" value="3" <?php echo ($imp_level == 3) ? 'checked' : ''; ?> disabled></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="button-container mt-4" style="text-align: right; margin-right: 20px; margin-top: 10px;">

                <?php if ($is_approved): ?>

                    <a href="export_kpi.php?Emp_code=<?php echo htmlspecialchars($current_emp_code); ?>&year=<?php echo htmlspecialchars($current_academic_year); ?>"
                        target="_blank" class="btn btn-success btn-lg px-5 py-3"
                        style="font-size: 1.5rem; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </a>

                <?php else: ?>

                    <button class="btn btn-secondary btn-lg px-5 py-3" style="font-size: 1.5rem; font-weight: bold;"
                        disabled>
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </button>
                    <div class="mt-2 text-muted" style="font-size: 1rem; color: blue;">(รายงานจะ Export
                        ได้ต่อเมื่อได้รับการอนุมัติแล้ว)
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>


    <div id="define-kpi-form" class="tab-content">
        <div class="form-container">
            <div class="form-header text-center mb-4">
                <h2>เกณฑ์การให้คะแนนประเมินประจำปี / Annual Review Rating</h2>
            </div>
            <div class="rating-scale-container">
    <?php
    $sql_criteria = "SELECT * FROM criteria ORDER BY criteria_id ASC";
    $result_criteria = $conn->query($sql_criteria);

    if ($result_criteria && $result_criteria->num_rows > 0) {
        // วนลูปแสดงข้อมูลทีละแถว
        while ($row = $result_criteria->fetch_assoc()) {
            ?>
            <div class="rating-box">
                <strong><?php echo $row['criteria_id']; ?></strong>
                <p>
                    <?php echo htmlspecialchars($row['criteria_tname']); ?><br>
                    <?php echo htmlspecialchars($row['criteria_ename']); ?>
                </p>
                <span><?php echo htmlspecialchars($row['criteria_scale']); ?></span>
            </div>
            <?php
        }
    } else {
        echo "<p>ไม่พบข้อมูลเกณฑ์การประเมิน</p>";
    }
    ?>
</div>

            <form action="config/save_annual_review.php" method="POST" id="annualReviewForm"
                enctype="multipart/form-data">
                <input type="hidden" name="emp_code_to_save" value="<?php echo htmlspecialchars($current_emp_code); ?>">
                <input type="hidden" name="academic_year" value="<?php echo $current_academic_year; ?>">
                <input type="hidden" name="active_submit_type_id" value="<?php echo $active_submit_type_id; ?>">


                <table class="table table-bordered kpi-detail-table" id="annual-review-table">
                    <colgroup>
    <col style="width: 3%;">
    <col style="width: 8%;"> <col style="width: 4%;">
    <col style="width: 5%;">
    <col style="width: 7%;"> <col style="width: 4%;">
    <col class="temp-hidden-goal">

    <?php if ($active_submit_type_id != 2): ?>
        <col style="width: <?php echo ($active_submit_type_id == 1) ? '30%' : '15%'; ?>;">
    <?php endif; ?>

    <col class="temp-hidden-goal">

    <?php if ($active_submit_type_id != 1): ?>
        <col style="width: 15%;">
    <?php endif; ?>

    <col style="<?php echo ($active_submit_type_id == 2) ? 'width: 15%;' : 'display: none;'; ?>">

    <col style="width: 6%;">
    <col style="width: 6%;">
    <col style="width: 5%;">
    <col style="width: 4%;"> <col style="width: 3%;">
    <col style="width: 4%;">
    
    <col style="width: 10%;">
</colgroup>
                        
                    <thead>
    <tr>
        <th rowspan="4" style="vertical-align: top; text-align: center;">ข้อที่</th>
<th rowspan="4" style="vertical-align: top; text-align: center;">ตัวชี้วัดผลงาน</th>
<th rowspan="4" style="vertical-align: top; text-align: center;">หน่วยวัด</th>
<th rowspan="4" style="vertical-align: top; text-align: center;">เป้าหมาย<br>ทั้งปี</th>
<th rowspan="4" style="vertical-align: top; text-align: center;">เกณฑ์การให้คะแนน</th>
<th rowspan="4" style="vertical-align: top; text-align: center;">ค่าน้ำหนัก<br>(หัวข้อ)</th>
        
        <th colspan="<?php echo ($active_submit_type_id == 1 || $active_submit_type_id == 2) ? '9' : '10'; ?>">การประเมินผลสำเร็จของงาน</th>
    </tr>

    <tr>
        <?php if ($active_submit_type_id == 2): // ถ้าเป็นช่วง H2 ?>
            <th colspan="1">ครึ่งปีหลัง</th>
            <th colspan="8">สรุปผลรวมทั้งปี</th>
        <?php elseif ($active_submit_type_id == 1): // ถ้าเป็นช่วง H1 ?>
            <th colspan="1">ครึ่งปีแรก</th>
            <th colspan="8">สรุปผลรวมทั้งปี</th>
        <?php else: // นอกช่วงเวลา (แสดงทั้งคู่) ?>
            <th colspan="1">ครึ่งปีแรก</th>
            <th colspan="1">ครึ่งปีหลัง</th>
            <th colspan="8">สรุปผลรวมทั้งปี</th>
        <?php endif; ?>
    </tr>

    <tr>
        <?php if ($active_submit_type_id != 2): ?>
            <th rowspan="2" style="vertical-align: top; text-align: center;">ผลงานจริง<br>ครึ่งปีแรก</th>
        <?php endif; ?>

        <?php if ($active_submit_type_id != 1): ?>
            <th rowspan="2" style="vertical-align: top; text-align: center;">ผลงานจริง<br>ครึ่งปีหลัง</th>
        <?php endif; ?>

<th rowspan="2" style="<?php echo ($active_submit_type_id == 2) ? '' : 'display:none;'; ?> text-align: center; vertical-align: top;">ผลงานจริง<br><u>ทั้งปี</u></th>     
<th rowspan="2" style="vertical-align: top; text-align: center;">อัปโหลดไฟล์<br>(หลักฐาน)</th>
        <th rowspan="2" style="vertical-align: top; text-align: center;">URL ไฟล์</th>
        <th colspan="4">ผลเทียบเป้าหมาย</th>
        <th rowspan="2" style="vertical-align: top; text-align: center;" style="width: 15%;">หมายเหตุ</th>
    </tr>
    <tr>
        <th style="vertical-align: top; text-align: center;"> ค่าน้ำหนัก</th>
        <th style="vertical-align: top; text-align: center;">ผลคะแนน<br>(1-5)</th>
        <th style="vertical-align: top; text-align: center;" colspan="2">คะแนนรวม<br>(น้ำหนักxผลคะแนน)</th>
    </tr>
</thead>
                    <tbody>
                        <?php
                        $total_weight_sum_topics = 0;
                        $total_weight_sum_types = 0;

                        foreach ($kpi_data as $kpi_type_id => $kpi_type):
                            $current_type_weight = floatval($kpi_type['TypeWeight']);
                            $total_weight_sum_types += $current_type_weight;
                            ?>
                            <tr class="section-header" data-kpi-type-id="<?php echo $kpi_type_id; ?>"data-type-weight="<?php echo floatval($kpi_type['TypeWeight']); ?>">
                                <td colspan="16">
                                    <?php echo htmlspecialchars($kpi_type['Order_No']) . '. ' . htmlspecialchars($kpi_type['KPI_Type_Name_EN'] . ' ' . $kpi_type['KPI_Type_Name_TH']); ?>
                                    <?php echo htmlspecialchars($current_type_weight); ?>
                                </td>
                            </tr>
                            <?php if (!empty($kpi_type['topics'])): ?>
                                <?php foreach ($kpi_type['topics'] as $topic_id => $item):
                                    $topic_weight = floatval($item['weight']);
                                    $total_weight_sum_topics += $topic_weight;
                                    $saved_h1 = $saved_review_data[$topic_id . "_1"] ?? null;
                                    $saved_h2 = $saved_review_data[$topic_id . "_2"] ?? null;

                                    $latest_score_data = $saved_h2 ?? $saved_h1;
                                    $score_value = $latest_score_data['score'] ?? '';
                                    $total_score_value = $latest_score_data['total_score'] ?? (($score_value !== '' && $score_value >= 1 && $score_value <= 5) ? ($topic_weight * intval($score_value)) : '0.00');

                                    
$actual_work_all_year_value = $latest_score_data['Actual_work_all_year'] ?? '';

// ถ้าใน DB ว่างเปล่า ให้ลองเอาค่า H1 + H2 มาต่อกันเป็นค่าเริ่มต้น (Optional)
if (empty($actual_work_all_year_value)) {
    $h1_val = $saved_h1['Actual_work'] ?? '';
    $h2_val = $saved_h2['Actual_work'] ?? '';
    $actual_work_all_year_value = trim($h1_val . "\n" . $h2_val);
}
                                    // ================= END: EDIT 1 =================
                        
                                    // ⬇⬇⬇ [แก้ไข] ดึง $file_path_value จาก H2 (สำหรับส่งให้ logic append) ⬇⬇⬇
                                    $file_path_value = $saved_h2['File_path'] ?? null;
                                    $file_url_value = $latest_score_data['File_url'] ?? ($saved_h1['File_url'] ?? '');
                                    $additional_text_value = $saved_h2['Additional'] ?? '';

                                    ?>
                                    <tr data-topic-id="<?php echo $topic_id; ?>"
                                        data-weight="<?php echo htmlspecialchars($item['weight']); ?>"
                                        data-kpi-type-id="<?php echo $kpi_type_id; ?>">
                                        <td class="text-left" style="vertical-align: top;"><?php echo htmlspecialchars($item['id']); ?></td>
                                        <td class="text-left" style="vertical-align: top;">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                            <?php if (isset($item['Additional']) && strtoupper($item['Additional']) == 'YES'): ?>
                                                <div class="additional-input-box" style="margin-top: 5px;">
                                                    <textarea id="additionaltext<?php echo $topic_id; ?>"
                                                        name="data[<?php echo $topic_id; ?>][Additional]" class="form-control" rows="2"
                                                        style="width: 100%; font-size: 0.9rem;" placeholder="กรอกข้อมูลเพิ่มเติม..."
                                                        <?php echo $disable_all_attr; ?>><?php echo htmlspecialchars($additional_text_value); ?></textarea>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="vertical-align:top;"><?php echo htmlspecialchars($item['unit']); ?></td>
                                        <td style="vertical-align:top;"><?php echo htmlspecialchars($item['target']); ?></td>

                                        <td style="vertical-align:top; text-align: left;">
                                            <div class="criteria-box">
                                                <?php echo nl2br(htmlspecialchars($item['criteria'])); ?>
                                            </div>
                                        </td>
                                        <td style="vertical-align:top;" class="topic-weight"><?php echo htmlspecialchars($item['weight']); ?></td>

                                        <td class="temp-hidden-goal">
                                            <textarea class="form-control goal-h1" rows="3"
                                                name="data[<?php echo $topic_id; ?>][1][Goal_job]" placeholder="เป้าหมาย"
                                                <?php echo $disable_all_attr; ?>><?php echo htmlspecialchars($saved_h1['Goal_job'] ?? ''); ?></textarea>
                                        </td>

                                        <?php
    $apa_auto_text = "";

    // 1. กรณีงานวิจัย (Table_id = 2)
    if (isset($item['Retrieve']) && $item['Retrieve'] === 'yes' && isset($item['Table_id']) && $item['Table_id'] == 2) {
        if (!empty($user_research_details)) {
            $lines = [];
            foreach ($user_research_details as $res) {
                // APA Format งานวิจัย
                $authors = !empty($res['Researcher_List']) ? $res['Researcher_List'] : ($res['Project_lead'] ?? "คณะผู้วิจัย");
                $year   = !empty($res['Calendaryear']) ? "(".$res['Calendaryear'].")" : "";
                $title  = !empty($res['Project_name_th']) ? $res['Project_name_th'] : "ไม่มีชื่อโครงการ";
                $fund   = !empty($res['Finance_name']) ? "ได้รับทุนสนับสนุนจาก: ".$res['Finance_name'] : "";
                $amount = !empty($res['Amount_support']) ? number_format($res['Amount_support']) . " บาท" : "";
                
                $fund_info = $fund;
                if ($fund && $amount) {
                    $fund_info .= " ($amount)";
                } elseif ($amount) {
                    $fund_info = "งบประมาณ: $amount";
                }
                
                $str = "$authors. $year. $title. $fund_info";
                $lines[] = trim($str);
            }
            $apa_auto_text = implode("\n\n", $lines);
        }
    }

    // 2. กรณีงานตีพิมพ์ (Table_id = 3)
    if (isset($item['Retrieve']) && $item['Retrieve'] === 'yes' && isset($item['Table_id']) && $item['Table_id'] == 3) {
        $req_type = $item['Publication_type_id'] ?? null;
        if ($req_type && isset($user_publications_details[$req_type])) {
            $lines = [];
            foreach ($user_publications_details[$req_type] as $pub) {
                // APA Format บทความ
                $authors = !empty($pub['Author_List']) ? $pub['Author_List'] : ($pub['Co_author'] ?? "ผู้แต่ง");
                $year = !empty($pub['Calendaryear']) ? "(".$pub['Calendaryear'].")" : "";
                $title = !empty($pub['Publication_name']) ? $pub['Publication_name'] : "ไม่มีชื่อบทความ";
                $journal = !empty($pub['Journal_name']) ? $pub['Journal_name'] : "";
                
                $vol_issue = "";
                if(!empty($pub['Copy'])) $vol_issue .= $pub['Copy'];
                if(!empty($pub['No'])) $vol_issue .= "(".$pub['No'].")";
                
                $pages = !empty($pub['Page_no']) ? ", ".$pub['Page_no'] : "";
                
                $str = "$authors. $year. $title. $journal";
                if ($vol_issue) $str .= ", $vol_issue";
                if ($pages) $str .= "$pages";
                
                $lines[] = trim($str);
            }
            $apa_auto_text = implode("\n\n", $lines);
        }
    }

    // เลือกค่าที่จะแสดง
    $actual_h1_value_to_show = !empty($saved_h1['Actual_work']) ? $saved_h1['Actual_work'] : $apa_auto_text;
    // --- [END] ย้าย Logic ออกมาแล้ว ---
?>

<?php if ($active_submit_type_id != 2): ?>
    <td style="vertical-align:top; text-align: center;">
        <textarea class="form-control actual-h1" rows="3"
            name="data[<?php echo $topic_id; ?>][1][Actual_work]"
            <?php echo $disable_all_attr; ?>><?php echo htmlspecialchars($actual_h1_value_to_show); ?></textarea>
    </td>
<?php else: ?>
    <input type="hidden" name="data[<?php echo $topic_id; ?>][1][Actual_work]" value="<?php echo htmlspecialchars($actual_h1_value_to_show); ?>">
<?php endif; ?>

                                        <td class="temp-hidden-goal">
                                            <textarea class="form-control goal-h2" rows="3"
                                                name="data[<?php echo $topic_id; ?>][2][Goal_job]" placeholder="เป้าหมาย"
                                                <?php echo $disable_all_attr; ?>><?php echo htmlspecialchars($saved_h2['Goal_job'] ?? ''); ?></textarea>
                                        </td>

                                        <?php if ($active_submit_type_id != 1): // ถ้า *ไม่* ใช่ช่วง H1 (ให้แสดง H2) ?>
                                            <td style="vertical-align:top; text-align: center;">
                                                <textarea class="form-control actual-h2" rows="3"
                                                    name="data[<?php echo $topic_id; ?>][2][Actual_work]" placeholder="ผลงานจริง"
                                                    <?php echo $disable_all_attr; ?>><?php echo htmlspecialchars($saved_h2['Actual_work'] ?? ''); ?></textarea>
                                            </td>
                                        <?php endif; ?>

                                        <td style="<?php echo ($active_submit_type_id == 2) ? '' : 'display:none;'; ?> text-align: center; vertical-align: top;">
    <textarea class="form-control actual-year" rows="3"
        name="data[<?php echo $topic_id; ?>][Actual_work_all_year]"
        <?php 
        // เงื่อนไข: ถ้าเป็นรอบ 2 และระบบเปิด ($is_editable) ให้พิมพ์ได้ (ไม่ disable)
        echo ($active_submit_type_id == 2 && $is_editable) ? '' : 'disabled'; 
        ?>><?php echo htmlspecialchars($actual_work_all_year_value); ?></textarea>
</td>
                                        <td style="text-align: center; vertical-align: top;">
<?php
    // 1. สร้างตัวแปรเก็บข้อมูลไฟล์ที่จะดึงมาแสดงใน input (Auto-fill)
    $files_to_prefill = [];

    // --- กรณี A: งานวิจัย (Table_id = 2) ---
    if (isset($item['Retrieve']) && $item['Retrieve'] === 'yes' && isset($item['Table_id']) && $item['Table_id'] == 2 && !empty($user_research_files)) {
        foreach ($user_research_files as $res_file) {
            $files_to_prefill[] = [
                'url' => 'uploads/kpi_evidence/' . $res_file['File_name'], 
                'name' => basename($res_file['File_name']), 
                'type' => 'application/pdf'
            ];
        }
    }

    // --- กรณี B: งานตีพิมพ์ (Table_id = 3) --- [เพิ่มใหม่]
    if (isset($item['Retrieve']) && $item['Retrieve'] === 'yes' && isset($item['Table_id']) && $item['Table_id'] == 3) {
        
        // ดึงค่า Publication_type_id ที่กำหนดใน KPI หัวข้อนี้
        $required_pub_type = $item['Publication_type_id'] ?? null;
        
        // ตรวจสอบว่ามีข้อมูลงานตีพิมพ์ที่ Status ตรงกันหรือไม่
        if ($required_pub_type && isset($user_publications[$required_pub_type])) {
            foreach ($user_publications[$required_pub_type] as $pub_item) {
                // ถ้างานตีพิมพ์นี้มีไฟล์แนบ (File_name ไม่ว่าง) ให้ดึงมาใส่
                if (!empty($pub_item['File_name'])) {
                    $files_to_prefill[] = [
                        'url' => 'uploads/kpi_evidence/' . $pub_item['File_name'],
                        'name' => basename($pub_item['File_name']),
                        'type' => 'application/pdf'
                    ];
                }
            }
        }
    }

    // แปลงข้อมูลเป็น JSON เพื่อใส่ใน attribute
    $files_json_attr = "";
    if (!empty($files_to_prefill)) {
        $files_json_attr = "data-prefill='" . htmlspecialchars(json_encode($files_to_prefill), ENT_QUOTES, 'UTF-8') . "'";
    }
?>

    <input type="file" class="form-control file-upload"
        name="kpi_file_<?php echo $topic_id; ?>[]" multiple
        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png"
        data-topic-id="<?php echo $topic_id; ?>" <?php echo $files_json_attr; ?>
        onchange="displaySelectedFileNames(this, <?php echo $topic_id; ?>)" <?php echo $disable_all_attr; ?>>

    <span class="selected-filename" id="filename-<?php echo $topic_id; ?>"></span>

    <div class="existing-files" id="existing-files-<?php echo $topic_id; ?>" style="margin-top: 10px;">
        <?php
        $current_topic_files = $saved_attach_files[$topic_id] ?? [];
        if (!empty($current_topic_files)):
            foreach ($current_topic_files as $file):
                $file_url = 'uploads/kpi_evidence/' . htmlspecialchars($file['File_name']);
                $file_basename = basename(htmlspecialchars($file['File_name']));
                $display_name = preg_replace('/^' . $topic_id . '_\d+_\d+_/', '', $file_basename);
                ?>
                <div class="file-link-wrapper" id="file-wrapper-<?php echo $file['File_path']; ?>">
                    <a href="<?php echo $file_url; ?>" target="_blank" class="uploaded-file-link" title="<?php echo $display_name; ?>">
                        <i class="fas fa-file-alt"></i> <?php echo $display_name; ?>
                    </a>
                    <button type="button" class="btn-delete-file" onclick="deleteFile(this, <?php echo $file['File_path']; ?>)" <?php echo $disable_all_attr; ?>>&times;</button>
                </div>
                <?php
            endforeach;
        endif;
        ?>
    </div>

    </td>
    <td style="text-align: center; vertical-align: top;">
    <?php
    // เตรียม URL ที่จะดึงมาอัตโนมัติ (Auto-fill)
    $auto_filled_url = "";
    
    // เงื่อนไขงานตีพิมพ์ (Table_id = 3)
    if (isset($item['Retrieve']) && $item['Retrieve'] === 'yes' && isset($item['Table_id']) && $item['Table_id'] == 3) {
        $required_pub_type = $item['Publication_type_id'] ?? null;
        
        // ใช้ตัวแปร $user_publications ที่เราแก้ในจุดที่ 1
        if ($required_pub_type && isset($user_publications[$required_pub_type])) {
            $url_list = [];
            foreach ($user_publications[$required_pub_type] as $pub_item) {
                if (!empty($pub_item['URL'])) {
                    $url_list[] = $pub_item['URL'];
                }
            }
            $auto_filled_url = implode("\n", $url_list);
        }
    }

    // กำหนดค่าที่จะแสดง (ถ้ามีของเดิม ให้ใช้ของเดิม)
    $text_area_value = !empty($file_url_value) ? $file_url_value : $auto_filled_url;
    ?>

    <textarea  class="form-control file-url" rows="3" 
        name="data[<?php echo $topic_id; ?>][File_url]" placeholder="URL..."
        <?php echo $disable_all_attr; ?>><?php echo htmlspecialchars($text_area_value); ?></textarea>

    <?php 
    // ปุ่มเปิดลิงก์ (แสดงเฉพาะตอนบันทึกแล้ว)
    if (!empty($file_url_value)) {
        $urls_to_show = explode("\n", $file_url_value);
        foreach ($urls_to_show as $url_line) {
            $url_line = trim($url_line);
            if (!empty($url_line)) {
                echo '<a href="' . htmlspecialchars($url_line) . '" target="_blank"
                    style="display: block; margin-top: 5px; font-size: 0.9em; word-break: break-all; text-decoration: none;">
                    <i class="fas fa-external-link-alt"></i> เปิดลิงก์
                </a>';
            }
        }
    }
    ?>
</td>

                                        <td style="vertical-align:top; text-align: center;"><?php echo htmlspecialchars($item['weight']); ?></td>

                                        <td style="vertical-align:top; text-align: center;" class="total-score-cell">
                                            <select class="form-control score-input" name="data[<?php echo $topic_id; ?>][score]"
                                                oninput="calculateRowScore(this)" <?php echo $disable_all_attr; ?>>
                                                <option value="" <?php echo ($score_value == '') ? 'selected' : ''; ?>></option>
                                                <option value="1" <?php echo ($score_value == '1') ? 'selected' : ''; ?>>1</option>
                                                <option value="2" <?php echo ($score_value == '2') ? 'selected' : ''; ?>>2</option>
                                                <option value="3" <?php echo ($score_value == '3') ? 'selected' : ''; ?>>3</option>
                                                <option value="4" <?php echo ($score_value == '4') ? 'selected' : ''; ?>>4</option>
                                                <option value="5" <?php echo ($score_value == '5') ? 'selected' : ''; ?>>5</option>
                                            </select>
                                        </td>

                                        <td style="vertical-align:top; text-align: center;" class="total-score-cell" colspan="2">
                                            <input type="number" step="0.01" class="form-control total-score"
                                                name="data[<?php echo $topic_id; ?>][total_score]"
                                                value="<?php echo number_format(floatval($total_score_value), 2, '.', ''); ?>"
                                                readonly style="background-color: #e9ecef;">
                                        </td>

                                        <td style="vertical-align:top; text-align: center;">
                                            <input type="hidden" name="data[<?php echo $topic_id; ?>][Advice]" value="<?php echo htmlspecialchars($latest_score_data['Advice'] ?? ''); ?>">
                                            <textarea class="form-control" rows="2" 
    name="data[<?php echo $topic_id; ?>][Advice]" style="min-width: 100%;" 
    <?php 
        if (!$is_editable || $_SESSION['Type_id'] != 2) {
            echo 'disabled style="background-color: #e9ecef; cursor: not-allowed;"'; 
        }
    ?>
><?php echo htmlspecialchars($latest_score_data['Advice'] ?? ''); ?></textarea>
</td>
                                    </tr>

                                <?php endforeach; ?>
                                
                                <tr class="section-total-row" data-kpi-type-id="<?php echo $kpi_type_id; ?>">
    <td colspan="<?php echo ($active_submit_type_id == 1) ? '11' : '12'; ?>" class="text-right"><strong>รวมคะแนนประจำหมวด</strong></td>
    
    <td colspan="2" class="text-center">
        <input type="number" step="0.01" class="form-control section-total-score" value="<?php echo number_format(floatval($saved_category_scores[$kpi_type_id] ?? 0), 2); ?>" readonly style="background-color: #e9ecef;">
    </td>
    <td></td>
</tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="16" class="text-center text-muted">-- ไม่มีหัวข้อตัวชี้วัดสำหรับประเภทนี้ --
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach;
                        $max_possible_score_display = ($total_weight_sum_types > 0) ? ($total_weight_sum_types * 5) : 0;
                        ?>
                    </tbody>
                    <tfoot>
    <tr>
        <td colspan="5" class="text-right"><strong>รวมน้ำหนักด้านผลสำเร็จของงาน</strong></td>

        <td class="text-center"><strong id="grand-total-weight"><?php echo $total_weight_sum_types; ?></strong></td>

        <td colspan="<?php echo ($active_submit_type_id == 1) ? '5' : '6'; ?>" class="text-center text-right">
            <strong>คะแนนรวมด้านผลสำเร็จของงานทั้งปี </strong>
        </td>

        <td colspan="2">
            <input type="number" step="0.01" id="grand-total-score-500" name="grand_total_score_500"
                class="form-control"
                value="<?php echo number_format(floatval($saved_total_scores['Score_500_max'] ?? 0), 2); ?>"
                readonly style="background-color: #e9ecef;">
            <input type="hidden" id="grand-total-score-100-hidden" name="grand_total_score_100">
        </td>
        <td></td>
    </tr>
</tfoot>
                </table>
                <div class="text-center mt-4" style="margin-top: 10px;">
                    <button type="submit" class="btn btn-success" 
    style="font-size: 1rem; padding: 10px 30px; width: auto; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.15); border-radius: 50px;"
    <?php echo $disable_all_attr; ?> <?php echo $disable_all_title; ?>>
    <i class="fas fa-save"></i> บันทึกผลการประเมิน
</button>

                    <?php if (isset($_SESSION['Type_id']) && $_SESSION['Type_id'] == 2 && $is_admin_edit_mode): ?>
                        <a href="config/process_approval.php?approve_user_id=<?php echo htmlspecialchars($current_emp_code); ?>&year=<?php echo htmlspecialchars($current_academic_year); ?>"
                           class="btn" 
                           style="background-color: #007bff; color: #ffffff; font-size: 1rem; padding: 10px 30px; width: auto; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.15); border-radius: 50px; margin-left: 15px;"
                           onclick="return confirm('คุณต้องการอนุมัติผลงานของ <?php echo htmlspecialchars($viewed_user_data['Fname_th'] . ' ' . $viewed_user_data['Lname_th']); ?> ใช่หรือไม่?')">
                            <i class="fas fa-check-circle"></i> อนุมัติงาน
                        </a>
                    <?php endif; ?>

                </div>
            </form>
        </div>
    </div>


    <div id="okr-evaluation-form" class="tab-content">
        <div class="form-container okr-form-styles">

            <form action="#" method="POST" id="okrEvaluationForm">
                <input type="hidden" name="academic_year" value="<?php echo $current_academic_year; ?>">

                <div class="section-title-bar">ข้อมูลผู้ถูกประเมิน</div>
                <div class="info-grid">
                    <label>ชื่อ-นามสกุล:</label> <input type="text" class="form-control"
                        value="<?php echo htmlspecialchars($viewed_user_data['Fname_th'] ?? '') . ' ' . htmlspecialchars($viewed_user_data['Lname_th'] ?? ''); ?>"
                        disabled>
                    <label>ตำแหน่งงานปัจจุบัน:</label> <input type="text" class="form-control"
                        value="<?php echo htmlspecialchars($user_type_details['Type_name_th'] ?? ''); ?>" disabled>
                    <label>หน่วยงาน:</label> <input type="text" class="form-control" value="คณะวิทยาศาสตร์และเทคโนโลยี"
                        disabled>
                    <label>ชื่อผู้บังคับบัญชาโดยตรง:</label> <input type="text" class="form-control"
                        name="direct_supervisor_name"
                        value="<?php echo htmlspecialchars($saved_okr_data['direct_supervisor_name'] ?? ''); ?>"
                        readonly>
                </div>

                <table class="evaluation-table">
                    <thead>
                        <tr>
                            <th>รอบการประเมิน</th>
                            <th>ลายเซ็นพนักงาน</th>
                            <th>ลายเซ็นผู้บังคับบัญชาโดยตรง</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>การประเมินผลกลางปี (<?php echo $current_academic_year; ?>)</td>
                            <td><input type="text" class="form-control" name="eval_date_mid_line"
                                    value="<?php echo htmlspecialchars($saved_okr_data['eval_date_mid_line'] ?? ''); ?>"
                                    readonly></td>
                            <td><input type="text" class="form-control" name="eval_date_mid_direct"
                                    value="<?php echo htmlspecialchars($saved_okr_data['eval_date_mid_direct'] ?? ''); ?>"
                                    readonly></td>
                        </tr>
                        <tr>
                            <td>การประเมินผลปลายปี (<?php echo $current_academic_year; ?>)</td>
                            <td><input type="text" class="form-control" name="eval_date_end_line"
                                    value="<?php echo htmlspecialchars($saved_okr_data['eval_date_end_line'] ?? ''); ?>"
                                    readonly></td>
                            <td><input type="text" class="form-control" name="eval_date_end_direct"
                                    value="<?php echo htmlspecialchars($saved_okr_data['eval_date_end_direct'] ?? ''); ?>"
                                    readonly></td>
                        </tr>
                    </tbody>
                </table>
                <div class="signature-block">
                    <strong>ลงนาม: __________________________________________</strong>
                    <div>
                        <p><strong>ชื่อ-สกุล:</strong>อิทธิพงษ์ เขมะเพชร</p>
                        <p><strong>ตำแหน่ง:</strong> คณบดีคณะวิทยาศาสตร์และเทคโนโลยี</p>
                        <p class="note"><strong>ผู้บังคับบัญชาหน่วยงานอนุมัติ</strong></p>
                    </div>
                </div>

                <div class="section-title-bar">ส่วนที่ 1: เกณฑ์สำหรับการประเมินประจำปี</div>
                <div class="rating-scale-container">
                    <div class="rating-box"><strong>1</strong>
                        <p>สำเร็จตามเป้าหมายน้อยมาก<br>Very Low Achievement</p><span>ต่ำกว่า 40%</span>
                    </div>
                    <div class="rating-box"><strong>2</strong>
                        <p>สำเร็จตามเป้าหมายน้อย<br>Under Achievement</p><span>40%-59%</span>
                    </div>
                    <div class="rating-box"><strong>3</strong>
                        <p>สำเร็จตามเป้าหมายพอควร<br>Partial Achievement</p><span>60%-79%</span>
                    </div>
                    <div class="rating-box"><strong>4</strong>
                        <p>สำเร็จตามเป้าหมายส่วนใหญ่<br>Nearly Achievement of Target</p><span>80%-99%</span>
                    </div>
                    <div class="rating-box"><strong>5</strong>
                        <p>สำเร็จตามเป้าหมาย<br>Achievement of Target</p><span>100%</span>
                    </div>
                </div>

                <table class="evaluation-table summary-table" id="okr-summary-table">
                    <thead>
                        <tr>
                            <th>ข้อที่</th>
                            <th>Measure / Target / ตัวชี้วัด</th>
                            <th>น้ำหนัก (%)</th>
                            <th>ผลรวมคะแนนประจำปี</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kpi_data as $kpi_type_id => $kpi_type): ?>
                            <tr data-kpi-type-id="<?php echo $kpi_type_id; ?>">
                                <td style = "text-align: center; vertical-align: top;"><?php echo htmlspecialchars($kpi_type['Order_No']); ?></td>
                                <td><?php echo htmlspecialchars($kpi_type['KPI_Type_Name_EN'] . '   ' . htmlspecialchars($kpi_type['KPI_Type_Name_TH'])); ?>
                                </td>
                                <td style = "text-align: center; vertical-align: top;" class="okr-type-weight"><?php echo htmlspecialchars($kpi_type['TypeWeight']); ?></td>
                                <td style = "text-align: right; vertical-align: top;"class="form-control okr-section-score"><?php echo number_format(floatval($saved_category_scores[$kpi_type_id] ?? 0), 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="2" class="text-right"><strong>Score/คะแนนรวม (เต็ม
                                    <?php echo $max_possible_score_display; ?> คะแนน)</strong></td>
                            <td style = "text-align: right; vertical-align: top;" colspan="2" id="okr-grand-total-score-500" class="form-control"><?php echo number_format(floatval($saved_total_scores['Score_500_max'] ?? 0), 2); ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="text-right"><strong>Score/คะแนนรวม (อัตตราส่วน 80 )</strong></td>
                            <td style = "text-align: right; vertical-align: top;" colspan="2"id="okr-grand-total-score-100" class="form-control">
                                    <?php echo number_format(floatval($saved_total_scores['Score_100_max'] ?? 0), 2); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <!-- <div class="section-title-bar">สรุปความคิดเห็นเพิ่มเติม (จากหัวหน้างาน/ผู้บังคับบัญชา)</div>
                <table class="evaluation-table">
                    <thead>
                        <tr>
                            <th>ครั้งที่ 1 (กลางปี)</th>
                            <th>ครั้งที่ 2 (รวมทั้งปี)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><textarea class="form-control" name="comments_mid" rows="4"
                                    readonly></textarea>
                            </td>
                            <td><textarea class="form-control" name="comments_end" rows="4"
                                    readonly></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="final-summary-section">
                    <h4>สรุปผลการประเมินทั้งปี</h4>
                    <div class="summary-line">
                        <div class="summary-group">
                            <span>ส่วนที่ 1: การประเมินผลตามเป้าหมายที่กำหนด</span>
                            <input type="text" class="form-control summary-input" id="okr-final-score-500"
                                value="<?php echo number_format(floatval($saved_total_scores['Score_500_max'] ?? 0), 2); ?>"
                                readonly>
                            <span>คะแนน, ของคะแนนเต็ม 500 คะแนน</span>
                        </div>

                        <div class="summary-group">
                            <span>คะแนนของส่วนที่ 1 (อัตราส่วน 100) =</span>
                            <input type="text" class="form-control summary-input" id="okr-final-score-100"
                                value="<?php echo number_format(floatval($saved_total_scores['Score_100_max'] ?? 0), 2); ?>"
                                readonly>
                            <span>%</span>
                        </div>
                    </div>

                </div>
                <table class="evaluation-table">
                    <thead>
                        <tr>
                            <th colspan="3">ข้อเสนอแนะจากหัวหน้างาน / ผู้บังคับบัญชา</th>
                        </tr>
                        <tr>
                            <th style="width: 10%;">เรื่อง</th>
                            <th>ครึ่งปีแรก</th>
                            <th>ครึ่งปีหลัง</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>จุดเด่น</strong></td>
                            <td><textarea class="form-control" name="feedback_strengths_h1"
                                    readonly></textarea>
                            </td>
                            <td><textarea class="form-control" name="feedback_strengths_h2"
                                    readonly></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>จุดที่ควรปรับปรุง</strong></td>
                            <td><textarea class="form-control" name="feedback_improvement_h1"
                                    readonly></textarea>
                            </td>
                            <td><textarea class="form-control" name="feedback_improvement_h2"
                                    readonly></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table class="evaluation-table suitability-table">
                    <thead>
                        <tr>
                            <th class="main-header" colspan="4">ความเหมาะสมในการปฏิบัติหน้าที่
                                (พร้อมระบุเหตุผล/ช่วงเวลาที่เหมาะสม)</th>
                        </tr>
                        <tr>
                            <th class="sub-header" style="width: 15%;">รอบ</th>
                            <th class="sub-header">เหมาะสมกับตำแหน่งหน้าที่เดิม</th>
                            <th class="sub-header">ควรรับผิดชอบงานเพิ่มขึ้น</th>
                            <th class="sub-header">ควรโยกย้ายไปรับหน้าที่ใหม่/เลื่อนระดับ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="topic-header">ครึ่งปีแรก</td>
                            <td><textarea class="form-control" name="suitability_h1_current"
                                    readonly></textarea>
                            </td>
                            <td><textarea class="form-control" name="suitability_h1_increase"
                                    readonly></textarea>
                            </td>
                            <td><textarea class="form-control" name="suitability_h1_move"
                                    readonly></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="topic-header">ครึ่งปีหลัง</td>
                            <td><textarea class="form-control" name="suitability_h2_current"
                                    readonly></textarea>
                            </td>
                            <td><textarea class="form-control" name="suitability_h2_increase"
                                    readonly></textarea>
                            </td>
                            <td><textarea class="form-control" name="suitability_h2_move"
                                    readonly></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="section-title-bar">ส่วนที่ 2: แผนการพัฒนา</div>
                <p>การกำหนดแนวทางการฝึกอบรมและพัฒนาเพื่อช่วยปรับปรุงการปฏิบัติงานหรือพัฒนาเส้นทางอาชีพของพนักงาน</p>

                <table class="evaluation-table dev-plan-table">
                    <thead>
                        <tr>
                            <th class="main-header" colspan="3">ครึ่งปีแรก</th>
                        </tr>
                        <tr>
                            <th class="sub-header" style="width: 35%;">แนวทางการพัฒนาและปรับปรุงผลการปฏิบัติงาน<br><span
                                    class="eng-text">Performance Focus</span></th>
                            <th class="sub-header" style="width: 35%;">แนวปฏิบัติ / ผู้รับผิดชอบ<br><span
                                    class="eng-text">(เช่น โครงการ การหมุนเวียนงาน การมอบหมายงานพิเศษ การฝึกอบรม
                                    ฯลฯ)</span><br><span class="eng-text">Actions / Accountability</span></th>
                            <th class="sub-header" style="width: 30%;">
                                ความคิดเห็นจากการประเมินผลงานรอบครึ่งปีแรก<br><span class="eng-text">Mid-Year Review
                                    Comments</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="topic-header">แผน</td>
                            <td class="input-cell"><textarea class="form-control" name="dev_plan_h1_plan1"
                                    readonly></textarea>
                            </td>
                            <td class="input-cell" rowspan="1"><textarea class="form-control"
                                    name="dev_plan_h1_comments1"
                                    readonly></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="topic-header">ผล</td>
                            <td class="input-cell"><textarea class="form-control" name="dev_plan_h1_results1"
                                    readonly></textarea>
                            </td>
                            <td class="input-cell" rowspan="1"><textarea class="form-control"
                                    name="dev_plan_h1_comments1"
                                    readonly></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="topic-header">แผน</td>
                            <td class="input-cell"><textarea class="form-control" name="dev_plan_h1_plan2"
                                    readonly></textarea>
                            </td>
                            <td class="input-cell" rowspan="1"><textarea class="form-control"
                                    name="dev_plan_h1_comments2"
                                    readonly></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="topic-header">ผล</td>
                            <td class="input-cell"><textarea class="form-control" name="dev_plan_h1_results2"
                                    readonly></textarea>
                            </td>
                            <td class="input-cell" rowspan="1"><textarea class="form-control"
                                    name="dev_plan_h1_comments1"
                                    readonly></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="topic-header">แผน</td>
                            <td class="input-cell"><textarea class="form-control" name="dev_plan_h1_plan3"
                                    readonly></textarea>
                            </td>
                            <td class="input-cell" rowspan="1"><textarea class="form-control"
                                    name="dev_plan_h1_comments3"
                                    readonly></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="topic-header">ผล</td>
                            <td class="input-cell"><textarea class="form-control" name="dev_plan_h1_results3"
                                    readonly></textarea>
                            </td>
                            <td class="input-cell" rowspan="1"><textarea class="form-control"
                                    name="dev_plan_h1_comments1"
                                    readonly></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <table class="evaluation-table dev-plan-table" style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th class="main-header" colspan="3">ครึ่งปีหลัง</th>
                        </tr>
                        <tr>
                            <th class="sub-header">แนวทางการพัฒนาและปรับปรุงผลการปฏิบัติงาน<br><span
                                    class="eng-text">Performance Focus</span></th>
                            <th class="sub-header">แนวปฏิบัติ / ผู้รับผิดชอบ<br><span class="eng-text">Actions /
                                    Accountability</span></th>
                            <th class="sub-header">ความคิดเห็นจากการประเมินผลงานรอบสิ้นปี<br><span
                                    class="eng-text">Year-End Review Comments</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="topic-header">แผน</td>
                            <td class="input-cell"><textarea class="form-control" name="dev_plan_h2_plan1"
                                    readonly></textarea>
                            </td>
                            <td class="input-cell" rowspan="1"><textarea class="form-control"
                                    name="dev_plan_h2_comments1"
                                    readonly></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="topic-header">ผล</td>
                            <td class="input-cell"><textarea class="form-control" name="dev_plan_h2_results1"
                                    readonly></textarea>
                            </td>
                            <td class="input-cell" rowspan="1"><textarea class="form-control"
                                    name="dev_plan_h2_comments2"
                                    readonly></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="topic-header">แผน</td>
                            <td class="input-cell"><textarea class="form-control" name="dev_plan_h2_plan2"
                                    readonly></textarea>
                            </td>
                            <td class="input-cell" rowspan="1"><textarea class="form-control"
                                    name="dev_plan_h2_comments2"
                                    readonly></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="topic-header">ผล</td>
                            <td class="input-cell"><textarea class="form-control" name="dev_plan_h2_results2"
                                    readonly></textarea>
                            </td>
                            <td class="input-cell" rowspan="1"><textarea class="form-control"
                                    name="dev_plan_h2_comments2"
                                    readonly></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="topic-header">แผน</td>
                            <td class="input-cell"><textarea class="form-control" name="dev_plan_h2_plan3"
                                    readonly></textarea>
                            </td>
                            <td class="input-cell" rowspan="1"><textarea class="form-control"
                                    name="dev_plan_h2_comments3"
                                    readonly></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="topic-header">ผล</td>
                            <td class="input-cell"><textarea class="form-control" name="dev_plan_h2_results3"
                                    readonly></textarea>
                            </td>
                            <td class="input-cell" rowspan="1"><textarea class="form-control"
                                    name="dev_plan_h2_comments2"
                                    readonly></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>


                <div class="text-center mt-4">

                </div> -->


        </div>
    </div>
</div>

<script src="js/individual_kpi.js"></script>

<?php
include 'templates/footer.php';
?>
</body>