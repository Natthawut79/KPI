<?php 
session_start();
include "conn.php"; 

$Emp_code = $_POST['Emp_code'];
$Password = $_POST['Password'];

$stmt = $conn->prepare("SELECT * FROM user WHERE Emp_code = ?");
$stmt->bind_param("s", $Emp_code);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row && password_verify($Password, $row['Password'])) {
    $_SESSION['Emp_code'] = $row['Emp_code'];
    $_SESSION['Type_id']  = $row['Type_id'];

    // Redirect based on user type
    switch ($_SESSION['Type_id']) {
        case 1: // admin
            header("Location: ../mainadmin.php");
            break;
        case 2: // superadmin
            header("Location: ../mainsuper.php");
            break;
        case 3: // Bachelor (Teacher)
            header("Location: ../main_bachelor.php");
            break;
        case 4: // Associate_Dean
            header("Location: ../main_associate_dean.php");
            break;
        case 5: // Head_of_Department
            header("Location: ../main_head_of_department.php");
            break;
    }
    exit();

}else{
    echo "<script>alert('ข้อมูลไม่ถูกต้อง');</script>";
    echo '<meta http-equiv="refresh" content="0;url=../login.php"> ';
}
?>