<?php
session_start();
include 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- รับข้อมูล ---
    $Emp_code = mysqli_real_escape_string($conn, $_POST['Emp_code']);
    $Password = mysqli_real_escape_string($conn, $_POST['Password']); 
    
    // === [ 1. รับค่า Type_id ที่ส่งมาจากฟอร์ม ] ===
    $Type_id = isset($_POST['Type_id']) ? mysqli_real_escape_string($conn, $_POST['Type_id']) : null;
    
    // === [ 2. ตรวจสอบว่าผู้ใช้ที่ *ล็อกอิน* อยู่ เป็น Admin (Type_id = 1) หรือไม่ ] ===
    $is_admin = (isset($_SESSION['Type_id']) && $_SESSION['Type_id'] == 1);

    $Fname_th = mysqli_real_escape_string($conn, $_POST['Fname_th']);
    $Lname_th = mysqli_real_escape_string($conn, $_POST['Lname_th']);
    $Fname_eng = mysqli_real_escape_string($conn, $_POST['Fname_eng']);
    $Lname_eng = mysqli_real_escape_string($conn, $_POST['Lname_eng']);
    $Title_id = mysqli_real_escape_string($conn, $_POST['Title_id']);
    $Department_id = mysqli_real_escape_string($conn, $_POST['Department_id']);

    // --- จัดการรูปภาพ (โค้ดเดิม) ---
    $imgData = null;
    $updateImage = false;
    if (isset($_FILES['IMGname']) && $_FILES['IMGname']['error'] == 0 && $_FILES['IMGname']['size'] > 0) {
        $imgData = file_get_contents($_FILES['IMGname']['tmp_name']);
        if ($imgData !== false) {
            $updateImage = true;
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการอ่านไฟล์รูปภาพ'); window.history.back();</script>";
            exit;
        }
    }

    // --- เริ่มต้น Transaction ---
    mysqli_begin_transaction($conn);

    try {
        // --- 1. อัปเดตตาราง employee (โค้ดเดิมที่ปรับปรุง) ---
        $sql_update_employee = "UPDATE employee
                            SET Title_id = ?,
                                Fname_th = ?,
                                Lname_th = ?,
                                Fname_eng = ?,
                                Lname_eng = ?,
                                Department_id = ?";

        $types = "ssssss"; // Types (6 ตัว s)
        $params = [$Title_id, $Fname_th, $Lname_th, $Fname_eng, $Lname_eng, $Department_id];

        // เพิ่มการอัปเดตรูปภาพ ถ้ามีการอัปโหลด
        if ($updateImage) {
            $sql_update_employee .= ", IMGname = ?";
            $types .= "b"; // 'b' for BLOB
            $params[] = $imgData; 
        }

        $sql_update_employee .= " WHERE Emp_code = ?";
        $types .= "s"; // 's' for Emp_code
        $params[] = $Emp_code;

        $stmt_employee = mysqli_prepare($conn, $sql_update_employee);
        if (!$stmt_employee) {
            throw new Exception("Error preparing employee statement: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt_employee, $types, ...$params);

        // ส่งข้อมูล BLOB (รูปภาพ) ถ้ามี
        if ($updateImage && $imgData !== null) {
            mysqli_stmt_send_long_data($stmt_employee, 6, $imgData);
        }

        if (!mysqli_stmt_execute($stmt_employee)) {
            throw new Exception("Error executing employee update: " . mysqli_stmt_error($stmt_employee));
        }
        mysqli_stmt_close($stmt_employee);

        // --- 2. อัปเดตตาราง user (รหัสผ่าน) (โค้ดเดิม) ---
        if (!empty($Password)) {
            $PasswordHash = password_hash($Password, PASSWORD_DEFAULT);
            $sql_update_pass = "UPDATE user SET Password = ? WHERE Emp_code = ?";
            
            $stmt_pass = mysqli_prepare($conn, $sql_update_pass);
            if (!$stmt_pass) {
                 throw new Exception("Error preparing password statement: " . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($stmt_pass, "ss", $PasswordHash, $Emp_code);
            
            if (!mysqli_stmt_execute($stmt_pass)) {
                throw new Exception("Error updating password: " . mysqli_stmt_error($stmt_pass));
            }
            mysqli_stmt_close($stmt_pass);
        }
        
        // === [ 3. เพิ่ม Logic การอัปเดต "ประเภทผู้ใช้" (Role) ] ===
        // ตรวจสอบว่า: 1. ผู้ใช้ที่ล็อกอินอยู่เป็น Admin และ 2. มีค่า Type_id ส่งมา
        if ($is_admin && $Type_id !== null) {
            $sql_update_role = "UPDATE user SET Type_id = ? WHERE Emp_code = ?";
            
            $stmt_role = mysqli_prepare($conn, $sql_update_role);
            if (!$stmt_role) {
                 throw new Exception("Error preparing role statement: ". mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($stmt_role, "is", $Type_id, $Emp_code);
            
            if (!mysqli_stmt_execute($stmt_role)) {
                throw new Exception("Error updating user role: " . mysqli_stmt_error($stmt_role));
            }
            mysqli_stmt_close($stmt_role);
        }
        mysqli_commit($conn);
        
        echo "<script>
                alert('บันทึกข้อมูลเรียบร้อยแล้ว!');
                window.location='../profile.php?Emp_code=$Emp_code';
              </script>";

    } catch (Exception $e) {
        // --- 5. Rollback ถ้ามีปัญหา ---
        mysqli_rollback($conn);
        echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

} else {
     echo "Invalid request method.";
}
mysqli_close($conn);
?>