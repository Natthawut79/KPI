<?php
session_start();
// **สำคัญ:** ตรวจสอบ path ไปยัง conn.php ให้ถูกต้อง
include 'conn.php';

// (ฟังก์ชันนี้ถูกต้องแล้ว)
function getFileTypeIdFromExtension($ext) {
    switch (strtolower($ext)) {
        case 'pdf': return 1;
        case 'doc':
        case 'docx': return 2;
        case 'xls':
        case 'xlsx': return 3;
        default: return 4; // อื่นๆ (ppt, jpg, png ฯลฯ)
    }
}


// --- Basic Security Checks ---
header('Content-Type: application/json'); // Set header to return JSON

if (!isset($_SESSION['Emp_code'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized: กรุณา Login ก่อน']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$current_emp_code = $_SESSION['Emp_code']; // นี่คือรหัสของคนที่ Login (เช่น Admin)
$academic_year = isset($_POST['academic_year']) ? intval($_POST['academic_year']) : 0;

// 1. ตรวจสอบว่า Admin กำลังแก้ไขข้อมูลของใคร
if (isset($_POST['emp_code_to_save']) && !empty($_POST['emp_code_to_save'])) {
    $emp_code_for_sql = $_POST['emp_code_to_save'];
} else {
    $emp_code_for_sql = $current_emp_code;
}


// รับข้อมูล Array หลัก
$data = $_POST['data'] ?? [];

if ($academic_year <= 0 || empty($data)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน (ปีการศึกษา หรือ ข้อมูลหลัก)']);
    exit();
}

// --- กำหนดค่าสำหรับการอัปโหลด ---
define('UPLOAD_DIR_BASE', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'kpi_evidence' . DIRECTORY_SEPARATOR); // Path เต็มไปยังโฟลเดอร์หลัก
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
$allowed_mime_types = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'image/jpeg',
    'image/png'
];
$allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png']; 

// --- Database Operations ---
mysqli_begin_transaction($conn);
$all_queries_success = true;
$errors = [];

try {
    

$sql_upsert_individual = "INSERT INTO individual_kpi
                          (Emp_code, Academic, KPI_topic_id, Submit_type_id, Goal_job, Actual_work, Actual_work_all_year, score, total_score, File_path, File_url, Additional, Approve_id, Advice)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE
                          Goal_job = VALUES(Goal_job), Actual_work = VALUES(Actual_work), Actual_work_all_year = VALUES(Actual_work_all_year),
                          score = VALUES(score), total_score = VALUES(total_score),
                          File_path = VALUES(File_path), File_url = VALUES(File_url),
                          Additional = VALUES(Additional),
                          Approve_id = VALUES(Approve_id),
                          Advice = VALUES(Advice)";

    $stmt_upsert_individual = mysqli_prepare($conn, $sql_upsert_individual);
    if (!$stmt_upsert_individual) {
        throw new Exception("Prepare statement failed (individual_kpi): " . mysqli_error($conn));
    }

    $sql_insert_file = "INSERT INTO attach_file 
                                (File_name, Date, Table_id, File_type_id, Fk_table_id) 
                                VALUES (?, NOW(), ?, ?, ?)";
    $stmt_insert_file = mysqli_prepare($conn, $sql_insert_file);
    if (!$stmt_insert_file) {
        throw new Exception("Prepare statement failed (attach_file): " . mysqli_error($conn));
    }
    $table_id_kpi = 1; // 1 = individual_kpi
    

    // ดึงค่าน้ำหนัก (weight)
    $topic_weights = [];
    $topic_ids = array_map('intval', array_keys($data));
    if (!empty($topic_ids)) {
        $topic_ids_string = implode(',', $topic_ids);
        if (preg_match('/^[0-9,]+$/', $topic_ids_string)) {
            $sql_weights = "SELECT KPI_topic_id, Weight FROM kpi_topic WHERE KPI_topic_id IN ($topic_ids_string)";
            $res_weights = mysqli_query($conn, $sql_weights);
            if ($res_weights) {
                while ($row_w = mysqli_fetch_assoc($res_weights)) {
                    $topic_weights[$row_w['KPI_topic_id']] = $row_w['Weight'];
                }
                mysqli_free_result($res_weights);
            } else {
                throw new Exception("ไม่สามารถดึงข้อมูลน้ำหนักหัวข้อได้: " . mysqli_error($conn));
            }
        } else {
            throw new Exception("Invalid Topic IDs format.");
        }
    }

    foreach ($data as $topic_id => $topic_data) {
        if (!isset($topic_weights[$topic_id])) {
            $errors[] = "ไม่พบน้ำหนักสำหรับ Topic ID: $topic_id.";
            continue;
        }
        $topic_weight = $topic_weights[$topic_id];
        $advice_value = $topic_data['Advice'] ?? null;

        // [เพิ่มส่วนนี้] ตัวแปรสำหรับเก็บค่า H1 เพื่อนำไปรวมกับ H2
        $actual_h1_for_calc = null;
        
        // 1. ดึง File_path_id_string เก่า (ถ้ามี)
        $old_file_path_string = null;
        $sql_get_old_path = "SELECT File_path FROM individual_kpi 
                               WHERE Emp_code = ? AND Academic = ? AND KPI_topic_id = ? AND Submit_type_id = 2 
                               LIMIT 1";
        $stmt_get_old = mysqli_prepare($conn, $sql_get_old_path);
        if ($stmt_get_old) {
            mysqli_stmt_bind_param($stmt_get_old, "sii", $emp_code_for_sql, $academic_year, $topic_id);
            mysqli_stmt_execute($stmt_get_old);
            $res_old = mysqli_stmt_get_result($stmt_get_old);
            if ($row_old = mysqli_fetch_assoc($res_old)) {
                $old_file_path_string = $row_old['File_path']; // เช่น "101;102"
            }
            mysqli_stmt_close($stmt_get_old);
        }


        $new_file_path_ids = []; 
        
        $file_input_name = 'kpi_file_' . $topic_id; 

        // ตรวจสอบว่ามีการอัปโหลดไฟล์สำหรับ Topic นี้หรือไม่
        if (isset($_FILES[$file_input_name]) && !empty($_FILES[$file_input_name]['name'][0])) {
            
            $files_for_topic = $_FILES[$file_input_name];
            $file_count = count($files_for_topic['name']);

            // วนลูปทุกไฟล์
            for ($i = 0; $i < $file_count; $i++) {
                
                $fileName = $files_for_topic['name'][$i];
                $fileTmpPath = $files_for_topic['tmp_name'][$i];
                $fileSize = $files_for_topic['size'][$i];
                $fileType = $files_for_topic['type'][$i];
                $fileError = $files_for_topic['error'][$i];
                
                if ($fileError !== UPLOAD_ERR_OK) {
                    continue; 
                }

                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));

                if ($fileSize > MAX_FILE_SIZE) {
                    $errors[] = "ไฟล์ '$fileName' (Topic $topic_id) มีขนาดใหญ่เกิน 5MB";
                    continue;
                }
                if (!in_array($fileType, $allowed_mime_types) || !in_array($fileExtension, $allowed_extensions)) {
                     $errors[] = "ไฟล์ '$fileName' (Topic $topic_id) มีนามสกุลไฟล์ไม่ถูกต้อง";
                     continue;
                }

                $safe_basename = preg_replace("/[^a-zA-Z0-9.\-_]/", "_", basename($fileName));
                
                // ตรวจสอบไฟล์ซ้ำ
                $sql_check_dup = "SELECT File_path FROM attach_file 
                                  WHERE Table_id = ? 
                                  AND Fk_table_id = ? 
                                  AND File_name LIKE ?";
                                  
                $stmt_dup = mysqli_prepare($conn, $sql_check_dup);
                if ($stmt_dup) {
                    $search_pattern = "%_" . $safe_basename; 
                    mysqli_stmt_bind_param($stmt_dup, "iis", $table_id_kpi, $topic_id, $search_pattern);
                    mysqli_stmt_execute($stmt_dup);
                    mysqli_stmt_store_result($stmt_dup);
                    
                    if (mysqli_stmt_num_rows($stmt_dup) > 0) {
                        mysqli_stmt_close($stmt_dup);
                        continue; 
                    }
                    mysqli_stmt_close($stmt_dup);
                }

                $newFileName = $topic_id . '_' . time() . '_' . $i . '_' . $safe_basename; 
                $dest_path_relative = $academic_year . '/' . $emp_code_for_sql . '/' . $newFileName;
                $dest_path_full = UPLOAD_DIR_BASE . $dest_path_relative;
                $dest_dir = dirname($dest_path_full);

                if (!is_dir($dest_dir)) {
                    if (!mkdir($dest_dir, 0777, true)) {
                        $errors[] = "ไม่สามารถสร้างโฟลเดอร์สำหรับอัปโหลดได้ (Topic $topic_id)";
                        $all_queries_success = false;
                        break; 
                    }
                }
                
                if (move_uploaded_file($fileTmpPath, $dest_path_full)) {
                    
                    $file_type_id = getFileTypeIdFromExtension($fileExtension);
                    
                    mysqli_stmt_bind_param(
                        $stmt_insert_file,
                        "siii",
                        $dest_path_relative, 
                        $table_id_kpi,       
                        $file_type_id,       
                        $topic_id            
                    );
                    
                    if (mysqli_stmt_execute($stmt_insert_file)) {
                        $last_inserted_id = mysqli_insert_id($conn);
                        $new_file_path_ids[] = $last_inserted_id;
                    } else {
                        $errors[] = "Error saving file '$fileName' to DB: " . mysqli_stmt_error($stmt_insert_file);
                        $all_queries_success = false;
                    }
                    
                } else {
                    $errors[] = "เกิดข้อผิดพลาดในการย้ายไฟล์ '$fileName' (Topic $topic_id)";
                    $all_queries_success = false;
                }
            } // end for loop (ไฟล์ใน topic)
        }
        
        // 2. รวม ID เก่าและ ID ใหม่
        $old_ids = [];
        if (!empty($old_file_path_string)) {
            $old_ids = explode(';', $old_file_path_string); 
        }
        
        $all_ids = array_merge($old_ids, $new_file_path_ids);
        $all_ids_filtered = array_filter(array_unique($all_ids));
        
        $final_file_path_string = null;
        if (!empty($all_ids_filtered)) {
            $final_file_path_string = implode(';', $all_ids_filtered); 
        }
        

        // --- [START EDIT] กำหนดสถานะ Approve ---
        $approve_id_for_h1 = 1; // ค่าเริ่มต้น (สำหรับ User บันทึกเอง)
        $approve_id_for_h2 = 1; 

        // ตรวจสอบว่า User ที่ Login ($current_emp_code) ตรงกับ เจ้าของข้อมูล ($emp_code_for_sql) หรือไม่
        // ถ้าไม่ตรงกัน แสดงว่าเป็น Admin/Superadmin มาบันทึก -> เปลี่ยน Approve_id เป็น 3
        if ($current_emp_code !== $emp_code_for_sql) {
            $approve_id_for_h1 = 3;
            $approve_id_for_h2 = 3;
        }
        // --- [END EDIT] ---



        // Process H1 (Submit Type 1)
        if (isset($topic_data[1])) {
            $goal_h1 = isset($topic_data[1]['Goal_job']) && $topic_data[1]['Goal_job'] !== '' ? $topic_data[1]['Goal_job'] : null;
            $actual_h1 = isset($topic_data[1]['Actual_work']) && $topic_data[1]['Actual_work'] !== '' ? $topic_data[1]['Actual_work'] : null;
            
            // [เพิ่มส่วนนี้] เก็บค่า H1 ไว้ใช้คำนวณ
            $actual_h1_for_calc = $actual_h1;

            $score_h1 = null;
            $total_score_h1 = null;
            $actual_work_all_year_h1 = null;
            $file_url_h1 = null;  // H1 ไม่เก็บ url ไฟล์
            $submit_type_h1 = 1;
            $additional_h1 = '';
            
            $file_path_h1 = null; // H1 ไม่เก็บ path ไฟล์
            
            mysqli_stmt_bind_param(
                $stmt_upsert_individual,
                "siiisssidsssis", 
                $emp_code_for_sql, 
                $academic_year,
                $topic_id,
                $submit_type_h1,
                $goal_h1,
                $actual_h1,
                $actual_work_all_year_h1,
                $score_h1,
                $total_score_h1,
                $file_path_h1,   
                $file_url_h1,
                $additional_h1,
                $approve_id_for_h1,
                $advice_value
            );


            if (!mysqli_stmt_execute($stmt_upsert_individual)) {
                $all_queries_success = false;
                $errors[] = "Error saving H1 for topic $topic_id: " . mysqli_stmt_error($stmt_upsert_individual);
            }
        }

        // Process H2 (Submit Type 2) and Final Scores
        $goal_h2 = isset($topic_data[2]['Goal_job']) && $topic_data[2]['Goal_job'] !== '' ? $topic_data[2]['Goal_job'] : null;
        $actual_h2 = isset($topic_data[2]['Actual_work']) && $topic_data[2]['Actual_work'] !== '' ? $topic_data[2]['Actual_work'] : null;
        $score_input = $topic_data['score'] ?? '';
        $score_value = ($score_input !== '' && is_numeric($score_input) && $score_input >= 1 && $score_input <= 5) ? intval($score_input) : null;
        
        // =========================================================
        // [Logic ใหม่]: H1 + User Input + H2 (รวมแบบไม่ซ้ำ)
        // =========================================================
        
        // 1. รับค่าข้อความปัจจุบันจากหน้าเว็บ (ซึ่งมีข้อความที่ User พิมพ์เพิ่มอยู่ด้วย)
        $user_manual_input = trim($topic_data['Actual_work_all_year'] ?? '');
        $h1_val_clean = trim($actual_h1_for_calc ?? ''); // H1 ที่แท้จริง
        $h2_val_clean = trim($actual_h2 ?? '');          // H2 ที่แท้จริง

        // เริ่มต้นด้วยสิ่งที่ User เห็น/แก้ไขมา
        $final_all_year_text = $user_manual_input;

        // 2. ตรวจสอบว่า H1 หายไปหรือไม่? (ถ้ามี H1 แต่ในข้อความรวมหาไม่เจอ -> ให้เติมไว้ข้างหน้า)
        if (!empty($h1_val_clean)) {
            // ใช้ strpos เช็คว่ามีข้อความ H1 อยู่ในนั้นไหม
            if (strpos($final_all_year_text, $h1_val_clean) === false) {
                // ถ้าไม่มี ให้เอา H1 ไปแปะไว้ข้างหน้าสุด
                if (!empty($final_all_year_text)) {
                    $final_all_year_text = $h1_val_clean . "\n" . $final_all_year_text;
                } else {
                    $final_all_year_text = $h1_val_clean;
                }
            }
        }

        // 3. ตรวจสอบว่า H2 หายไปหรือไม่? (ถ้ามี H2 แต่ในข้อความรวมหาไม่เจอ -> ให้เติมต่อท้าย)
        if (!empty($h2_val_clean)) {
            if (strpos($final_all_year_text, $h2_val_clean) === false) {
                // ถ้าไม่มี ให้เอา H2 ไปต่อท้ายสุด
                if (!empty($final_all_year_text)) {
                    $final_all_year_text .= "\n" . $h2_val_clean;
                } else {
                    $final_all_year_text = $h2_val_clean;
                }
            }
        }

        // ค่าที่จะบันทึกลง DB
        $actual_work_all_year_value = !empty($final_all_year_text) ? $final_all_year_text : null;
        // =========================================================

        $total_score_value = ($score_value !== null) ? (floatval($topic_weight) * $score_value) : null;
        $file_url_input = $topic_data['File_url'] ?? '';
        $file_url_value = ($file_url_input !== '' && trim($file_url_input) !== '') ? trim($file_url_input) : null; 
        $additional_value_h2 = $topic_data['Additional'] ?? '';
        $submit_type_h2 = 2;

        
        mysqli_stmt_bind_param(
            $stmt_upsert_individual,
            "siiisssidsssis", 
            $emp_code_for_sql, 
            $academic_year,
            $topic_id,
            $submit_type_h2,
            $goal_h2,
            $actual_h2,
            $actual_work_all_year_value,
            $score_value,
            $total_score_value,
            $final_file_path_string, 
            $file_url_value,
            $additional_value_h2,
            $approve_id_for_h2,
            $advice_value
        );

        if (!mysqli_stmt_execute($stmt_upsert_individual)) {
            $all_queries_success = false;
            $errors[] = "Error saving H2/Score for topic $topic_id: " . mysqli_stmt_error($stmt_upsert_individual);
        }
    } // End foreach data
    mysqli_stmt_close($stmt_upsert_individual);
    mysqli_stmt_close($stmt_insert_file); 

    // --- 2. Recalculate and Update/Insert score_evaluation ---
    if ($all_queries_success) {
        $sql_delete_cat = "DELETE FROM score_evaluation WHERE Emp_code = ? AND Academic = ? AND Submit_type_id = 2";
        $stmt_delete_cat = mysqli_prepare($conn, $sql_delete_cat);
        if (!$stmt_delete_cat)
            throw new Exception("Prepare statement failed (delete score_evaluation): " . mysqli_error($conn));
        
        mysqli_stmt_bind_param($stmt_delete_cat, "si", $emp_code_for_sql, $academic_year);
        
        if (!mysqli_stmt_execute($stmt_delete_cat)) {
            $all_queries_success = false;
            $errors[] = "Error deleting old category scores: " . mysqli_stmt_error($stmt_delete_cat);
        }
        mysqli_stmt_close($stmt_delete_cat);
    }
    if ($all_queries_success) {
        $sql_insert_cat = "INSERT INTO score_evaluation (Emp_code, KPI_type_id, Academic, Submit_type_id, Category_score)
                            SELECT
                                  ik.Emp_code, kt.KPI_type_id, ik.Academic, 2, SUM(ik.total_score)
                            FROM individual_kpi ik
                            JOIN kpi_topic kt ON ik.KPI_topic_id = kt.KPI_topic_id
                            WHERE ik.Emp_code = ? AND ik.Academic = ? AND ik.Submit_type_id = 2 AND ik.total_score IS NOT NULL
                            GROUP BY ik.Emp_code, kt.KPI_type_id, ik.Academic";
        $stmt_insert_cat = mysqli_prepare($conn, $sql_insert_cat);
        if (!$stmt_insert_cat) {
            throw new Exception("Prepare statement failed (insert score_evaluation): " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt_insert_cat, "si", $emp_code_for_sql, $academic_year);
        
        if (!mysqli_stmt_execute($stmt_insert_cat)) {
            $all_queries_success = false;
            $errors[] = "Error calculating/saving category scores: " . mysqli_stmt_error($stmt_insert_cat);
        }
        mysqli_stmt_close($stmt_insert_cat);
    }



    if ($all_queries_success) {
        $grand_total_score_500 = isset($_POST['grand_total_score_500']) ? floatval($_POST['grand_total_score_500']) : 0.0;
        $grand_total_score_100 = isset($_POST['grand_total_score_100']) ? floatval($_POST['grand_total_score_100']) : 0.0; 

        $sql_upsert_total = "INSERT INTO total_score_evaluation 
                               (Emp_code, Academic, Submit_type_id, Score_500_max, Score_100_max) 
                             VALUES (?, ?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE
                               Score_500_max = VALUES(Score_500_max),
                               Score_100_max = VALUES(Score_100_max)";
        
        $stmt_upsert_total = mysqli_prepare($conn, $sql_upsert_total);
        
        if (!$stmt_upsert_total) {
            throw new Exception("Prepare statement failed (upsert total_score_evaluation): " . mysqli_error($conn));
        }
        $submit_type_total = 2; 
        
        mysqli_stmt_bind_param($stmt_upsert_total, "siidd", $emp_code_for_sql, $academic_year, $submit_type_total, $grand_total_score_500, $grand_total_score_100);
        
        if (!mysqli_stmt_execute($stmt_upsert_total)) {
            $all_queries_success = false;
            $errors[] = "Error saving total score: " . mysqli_stmt_error($stmt_upsert_total);
        }
        mysqli_stmt_close($stmt_upsert_total);
    }
    // --- จบส่วนที่ 3 ---


    // --- Commit or Rollback ---
    if ($all_queries_success) {
        mysqli_commit($conn);
        echo json_encode(['success' => true, 'message' => 'บันทึกผลการประเมินประจำปีเรียบร้อยแล้ว ✅']);
    } else {
        mysqli_rollback($conn);
        http_response_code(500);
        $unique_errors = array_unique($errors); // แสดงเฉพาะ Error ที่ไม่ซ้ำ
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึก: ' . implode('; ', $unique_errors)]);
    }

} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    error_log("Transaction failed [save_annual_review.php]: Line " . $e->getLine() . " - " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดร้ายแรง กรุณาลองใหม่อีกครั้ง หรือติดต่อผู้ดูแลระบบ (' . $e->getLine() . ')']);
}

mysqli_close($conn);
?>