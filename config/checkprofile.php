<?php
session_start();
include 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $Emp_code = mysqli_real_escape_string($conn, $_POST['Emp_code']);
    $Password = mysqli_real_escape_string($conn, $_POST['Password']); 
    $Type_id = isset($_POST['Type_id']) ? mysqli_real_escape_string($conn, $_POST['Type_id']) : null;
    $is_admin = (isset($_SESSION['Type_id']) && $_SESSION['Type_id'] == 1);
    $Fname_th = mysqli_real_escape_string($conn, $_POST['Fname_th']);
    $Lname_th = mysqli_real_escape_string($conn, $_POST['Lname_th']);
    $Fname_eng = mysqli_real_escape_string($conn, $_POST['Fname_eng']);
    $Lname_eng = mysqli_real_escape_string($conn, $_POST['Lname_eng']);
    $Title_id = mysqli_real_escape_string($conn, $_POST['Title_id']);
    $Department_id = mysqli_real_escape_string($conn, $_POST['Department_id']);

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

    mysqli_begin_transaction($conn);

    try {
        $sql_update_employee = "UPDATE employee
                            SET Title_id = ?,
                                Fname_th = ?,
                                Lname_th = ?,
                                Fname_eng = ?,
                                Lname_eng = ?,
                                Department_id = ?";

        $types = "ssssss";
        $params = [$Title_id, $Fname_th, $Lname_th, $Fname_eng, $Lname_eng, $Department_id];

        if ($updateImage) {
            $sql_update_employee .= ", IMGname = ?";
            $types .= "b";
            $params[] = $imgData; 
        }

        $sql_update_employee .= " WHERE Emp_code = ?";
        $types .= "s";
        $params[] = $Emp_code;

        $stmt_employee = mysqli_prepare($conn, $sql_update_employee);
        if (!$stmt_employee) {
            throw new Exception("Error preparing employee statement: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt_employee, $types, ...$params);

        if ($updateImage && $imgData !== null) {
            mysqli_stmt_send_long_data($stmt_employee, 6, $imgData);
        }

        if (!mysqli_stmt_execute($stmt_employee)) {
            throw new Exception("Error executing employee update: " . mysqli_stmt_error($stmt_employee));
        }
        mysqli_stmt_close($stmt_employee);

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
        mysqli_rollback($conn);
        echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

} else {
     echo "Invalid request method.";
}
mysqli_close($conn);
?>