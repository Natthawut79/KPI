<?php 
session_start();
include "conn.php"; 

$Emp_code = $_POST['Emp_code'];
$Password = $_POST['Password'];

$sql = "SELECT * FROM user WHERE Emp_code='$Emp_code' AND Password='$Password' ";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result); 

if($row){
    $_SESSION['Emp_code'] = $row['Emp_code'];
    $_SESSION['Type_id']  = $row['Type_id'];

    // ตรวจสอบ Type_id ว่าเป็นค่าอะไร
    if($_SESSION['Type_id'] == 1){
        echo '<meta http-equiv="refresh" content="0;url=../index.php"> ';
    }elseif($_SESSION['Type_id'] == 2){
        echo '<meta http-equiv="refresh" content="0;url=../index.php"> ';
    }elseif($_SESSION['Type_id'] == 3){
        echo '<meta http-equiv="refresh" content="0;url=../index.php"> ';
    }elseif($_SESSION['Type_id'] == 4){
        echo '<meta http-equiv="refresh" content="0;url=../index.php"> ';
    }elseif($_SESSION['Type_id'] == 5){
        echo '<meta http-equiv="refresh" content="0;url=../index.php"> ';    
    }

}else{
    echo "<script>alert('ข้อมูลไม่ถูกต้อง');</script>";
    echo '<meta http-equiv="refresh" content="0;url=../login.php"> ';
}
?>
