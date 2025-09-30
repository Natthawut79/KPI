<?php
session_start();
include 'config/conn.php';

// ถ้ามี Emp_code ส่งมาผ่าน URL (GET) ให้ใช้ค่านั้นก่อน
if (isset($_GET['Emp_code'])) {
    $Emp_code = mysqli_real_escape_string($conn, $_GET['Emp_code']);
} elseif (isset($_SESSION['Emp_code'])) {
    // ถ้าไม่มี GET แต่มี SESSION ให้ใช้ SESSION
    $Emp_code = mysqli_real_escape_string($conn, $_SESSION['Emp_code']);
} else {
    echo "ไม่พบรหัสพนักงาน!";
    exit();
}

$sql = "SELECT e.*, t.Title_name 
        FROM employee e 
        LEFT JOIN title t ON e.Title_id = t.Title_id 
        WHERE e.Emp_code = '$Emp_code'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

$page_title = "แก้ไขข้อมูลผู้ใช้";
include 'templates/navbar.php';
?>

<div class="main-container">
    <div class="profile-card">

      <div class="profile-picture">
        <img src="img/profile.png" alt="Profile Picture">
      </div>


      <form class="profile-form" action="checkprofile.php" method="post">
          <div class="form-row">
            <label for="prefix">คำนำหน้าชื่อ</label>
            <input type="text" name="Title_name" value="<?php echo $row['Title_name']; ?>">
          </div>
          <div class="form-row">
            <label for="first-name-th">ชื่อ(ไทย)</label>
            <input type="text" name="Fname_th" value="<?php echo $row['Fname_th']; ?>">
          </div>
          <div class="form-row">
            <label for="last-name-th">นามสกุล(ไทย)</label>
            <input type="text" name="Lname_th" value="<?php echo $row['Lname_th'];?>">
          </div>
          <div class="form-row">
              <label for="first-name-en">ชื่อ(eng)</label>
              <input type="text" name="Fname_eng" value="<?php echo $row['Fname_eng'];?>">
          </div>
          <div class="form-row">
              <label for="last-name-en">นามสกุล(eng)</label>
              <input type="text" name="Lname_eng" value="<?php echo $row['Lname_eng'];?>">
          </div>
          <div class="form-row">
            <label for="employee-id">รหัสพนักงาน</label>
            <input type="text" name="Emp_code" value="<?php echo $row['Emp_code'];?>" disabled>
          </div>
          <div class="form-row">
            <label for="department">สาขา</label>
            <input type="text" name="Department_name" value="<?php echo $row['Department_id'];?>">
          </div>

          <div class="button-container">
              <button type="submit" class="save-button">บันทึกการเปลี่ยนแปลง</button>
          </div>

      </form>
    </div>
</div>

<div id="successModal" class="modal">
    <div class="modal-content">
      <span class="modal-icon">✅</span>
      <h2>บันทึกข้อมูลสำเร็จ</h2>
      <button id="closeModalBtn" class="close-button">ตกลง</button>
    </div>
</div>

<?php include 'templates/footer.php'; ?>