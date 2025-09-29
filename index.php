<?php 
    $page_title = "หน้าหลัก - ตัวชี้วัด";
    include 'templates/navbar.php'; 
?>

<div class="container">
    <div class="stats">
      <div class="stat-box">
        <h3>จำนวนผู้ใช้ในระบบ</h3>
        <div class="stat-number" id="userCount">35 👥</div>
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
        <tr>
          <td>ผศ.เดชาภัทร วรยุทธพรพงศ์</td>
          <td>เทคโนโลยีสารสนเทศและการสื่อสาร</td>
          <td class="action">ดูรายละเอียด <img src="img/edit.png" alt="แก้ไข"></td>
        </tr>
        <tr>
          <td>ผศ.ดร.อนุสรณ์ พิมพ์งาม</td>
          <td>เทคโนโลยีสารสนเทศและการสื่อสาร</td>
          <td class="action">ดูรายละเอียด <img src="img/edit.png" alt="แก้ไข"></td>
        </tr>
        <tr>
          <td>ผศ.ชญาดา มาศธีรสกุล</td>
          <td>เทคโนโลยีสารสนเทศและการสื่อสาร</td>
          <td class="action">ดูรายละเอียด <img src="img/edit.png" alt="แก้ไข"></td>
        </tr>
        <tr>
          <td>ผศ.ดร.ศราวุธ สิงห์คร</td>
          <td>เทคโนโลยีสารสนเทศและการสื่อสาร</td>
          <td class="action">ดูรายละเอียด <img src="img/edit.png" alt="แก้ไข"></td>
        </tr>
        <tr>
          <td>ผศ.ดร.รัตน์ โรจนรัตน์</td>
          <td>เทคโนโลยีสารสนเทศและการสื่อสาร</td>
          <td class="action">ดูรายละเอียด <img src="img/edit.png" alt="แก้ไข"></td>
        </tr>
      </tbody>
    </table>
</div>

<?php include 'templates/footer.php'; ?>