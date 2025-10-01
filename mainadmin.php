<?php 
    $page_title = "หน้าหลัก - ตัวชี้วัด";
    include 'templates/navbar.php';
    include 'config/conn.php';

    // นับจำนวนผู้ใช้
    $sql = "SELECT COUNT(*) AS total FROM employee";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $userCount = $row['total'];

    // ดึงข้อมูลพนักงาน + ชื่อสาขาจากตาราง department + title
    $sql_employee = "
    SELECT e.Emp_code, t.Title_shortname, e.Fname_th, e.Lname_th, d.Department_name
    FROM employee e
    JOIN department d ON e.Department_id = d.Department_id
    JOIN title t ON e.Title_id = t.Title_id
    ";

    $result_employee = mysqli_query($conn, $sql_employee);
?>
<link rel="stylesheet" href="css/mainadmin.css">
<div class="container">
    <div class="stats">
      <div class="stat-box">
        <h3>จำนวนผู้ใช้ในระบบ</h3>
        <div class="stat-number" id="userCount"><?php echo $userCount; ?> 👥</div>
      </div>
      <div class="stat-box">
        <h3>เปิด-ปิด การแก้ไข</h3>
        <label class="switch">
          <input type="checkbox" id="toggleSwitch" checked>
          <span class="slider"></span>
        </label>
      </div>
    </div>

    <h2 class="table-title">รายชื่อผู้ใช้ในระบบ</h2>
    <table>
      <thead>
        <tr>
          <th>อาจารย์</th>
          <th>สาขาวิชา</th>
          <th>การจัดการ</th>
        </tr>
      </thead>
      <tbody>
        <?php while($employee = mysqli_fetch_assoc($result_employee)) { ?>
        <tr>
          <td><?php echo $employee['Title_shortname']." ".$employee['Fname_th']." ".$employee['Lname_th']; ?></td>
          <td><?php echo $employee['Department_name']; ?></td>
          <td class="action">
            <a href="profile.php?Emp_code=<?php echo $employee['Emp_code']; ?>">
              ดูรายละเอียด
            </a>
          </td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
</div>

<?php include 'templates/footer.php'; ?>

