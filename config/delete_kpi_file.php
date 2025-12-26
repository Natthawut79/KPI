<?php
session_start();
include 'conn.php';

header('Content-Type: application/json');

// ตรวจสอบ Emp_code
if (!isset($_SESSION['Emp_code'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
$current_emp_code = $_SESSION['Emp_code'];
// รับค่า JSON
$data = json_decode(file_get_contents('php://input'), true);
$file_path_id_to_delete = $data['file_path_id'] ?? 0;

// รับ academic_year จาก JSON body (ที่ JavaScript ส่งมา)
$current_academic_year = $data['academic_year'] ?? 0; 

// แก้ไขการตรวจสอบ
if ($file_path_id_to_delete <= 0 || $current_academic_year <= 0) { 
    echo json_encode(['success' => false, 'message' => 'Invalid File ID or Academic Year']);
    exit();
}

mysqli_begin_transaction($conn);

try {
    // ดึงข้อมูลไฟล์ (รวมถึง Fk_table_id)
    $file_name_relative = null;
    $topic_id = null;
    
    $sql_select = "SELECT File_name, Fk_table_id FROM attach_file WHERE File_path = ?";
    $stmt_select = mysqli_prepare($conn, $sql_select);
    if (!$stmt_select) throw new Exception("Prepare failed (select): " . mysqli_error($conn));
    
    mysqli_stmt_bind_param($stmt_select, "i", $file_path_id_to_delete);
    mysqli_stmt_execute($stmt_select);
    $result = mysqli_stmt_get_result($stmt_select);
    $file_row = mysqli_fetch_assoc($result);
    
    if (!$file_row) {
        throw new Exception("ไม่พบไฟล์ (ID: $file_path_id_to_delete)");
    }
    
    $file_name_relative = $file_row['File_name'];
    $topic_id = $file_row['Fk_table_id'];
    mysqli_stmt_close($stmt_select);

    $path_parts = explode('/', $file_name_relative);
    if (count($path_parts) < 3 || $path_parts[1] !== $current_emp_code) {
        throw new Exception("คุณไม่ใช่เจ้าของไฟล์นี้");
    }

    //  ลบข้อมูลจาก attach_file
    $sql_delete = "DELETE FROM attach_file WHERE File_path = ?";
    $stmt_delete = mysqli_prepare($conn, $sql_delete);
    if (!$stmt_delete) throw new Exception("Prepare failed (delete): " . mysqli_error($conn));
    
    mysqli_stmt_bind_param($stmt_delete, "i", $file_path_id_to_delete);
    if (!mysqli_stmt_execute($stmt_delete)) {
        throw new Exception("ลบข้อมูลใน DB (attach_file) ไม่สำเร็จ: " . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt_delete);

    //  ลบไฟล์จริงออกจาก Server
    $upload_dir_base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'kpi_evidence' . DIRECTORY_SEPARATOR;
    $full_file_path = $upload_dir_base . $file_name_relative;
    $full_file_path = str_replace('/', DIRECTORY_SEPARATOR, $full_file_path);

    if (file_exists($full_file_path)) {
        if (!@unlink($full_file_path)) {
            error_log("Could not delete physical file: " . $full_file_path);
        }
    } else {
         error_log("Physical file not found: " . $full_file_path);
    }

    $old_file_path_string = null;
    $sql_get_kpi = "SELECT File_path FROM individual_kpi 
                    WHERE Emp_code = ? AND Academic = ? AND KPI_topic_id = ? AND Submit_type_id = 2 
                    LIMIT 1";
    $stmt_get_kpi = mysqli_prepare($conn, $sql_get_kpi);
    if (!$stmt_get_kpi) throw new Exception("Prepare failed (get kpi): ".mysqli_error($conn));
    
    mysqli_stmt_bind_param($stmt_get_kpi, "sii", $current_emp_code, $current_academic_year, $topic_id);
    mysqli_stmt_execute($stmt_get_kpi);
    $res_kpi = mysqli_stmt_get_result($stmt_get_kpi);
    
    if ($row_kpi = mysqli_fetch_assoc($res_kpi)) {
        $old_file_path_string = $row_kpi['File_path'];
    }
    mysqli_stmt_close($stmt_get_kpi);

    if ($old_file_path_string !== null) {
        $file_ids_array = explode(';', $old_file_path_string);
        $key_to_remove = array_search((string)$file_path_id_to_delete, $file_ids_array, true);
        if ($key_to_remove !== false) {
            unset($file_ids_array[$key_to_remove]);
        }
        $file_ids_array_filtered = array_filter($file_ids_array);
        $new_file_path_string = null;
        if (!empty($file_ids_array_filtered)) {
             $new_file_path_string = implode(';', $file_ids_array_filtered);
        }

        $sql_update_kpi = "UPDATE individual_kpi SET File_path = ? 
                           WHERE Emp_code = ? AND Academic = ? AND KPI_topic_id = ? AND Submit_type_id = 2";
        $stmt_update_kpi = mysqli_prepare($conn, $sql_update_kpi);
        if (!$stmt_update_kpi) throw new Exception("Prepare failed (update kpi): ".mysqli_error($conn));
        
        mysqli_stmt_bind_param($stmt_update_kpi, "ssii", $new_file_path_string, $current_emp_code, $current_academic_year, $topic_id);
        
        if (!mysqli_stmt_execute($stmt_update_kpi)) {
             throw new Exception("อัปเดต individual_kpi ไม่สำเร็จ: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt_update_kpi);
    }
    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => 'ลบไฟล์เรียบร้อย']);

} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conn);
?>