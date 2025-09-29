<?php 
    $page_title = "แก้ไขข้อมูลผู้ใช้";
    include 'templates/navbar.php'; 
?>

<div class="main-container">
    <div class="profile-card">
      
      <div class="profile-picture">
        <img src="img/profile.png" alt="Profile Picture">
      </div>

      <form class="profile-form">
        <div class="form-row">
          <label for="prefix">คำนำหน้าชื่อ</label>
          <input type="text" id="prefix" value="รศ.">
        </div>
        <div class="form-row">
          <label for="first-name-th">ชื่อ(ไทย)</label>
          <input type="text" id="first-name-th" value="เอกรินทร์">
        </div>
        <div class="form-row">
          <label for="last-name-th">นามสกุล(ไทย)</label>
          <input type="text" id="last-name-th" value="วรุตบางกุร">
        </div>
        <div class="form-row">
            <label for="first-name-en">ชื่อ(eng)</label>
            <input type="text" id="first-name-en" value="Eakkarin">
        </div>
        <div class="form-row">
            <label for="last-name-en">นามสกุล(eng)</label>
            <input type="text" id="last-name-en" value="Worabangkoon">
        </div>
        <div class="form-row">
          <label for="employee-id">รหัสพนักงาน</label>
          <input type="text" id="employee-id" value="22154890" disabled>
        </div>
        <div class="form-row">
          <label for="department">สาขา</label>
          <input type="text" id="department" value="เทคโนโลยีสารสนเทศและการสื่อสาร" disabled>
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