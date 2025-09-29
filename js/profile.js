// --- ฟังก์ชัน Logout (ตัวอย่าง) ---
function logout() {
  // ไม่ต้องมี alert แต่จะ redirect กลับไปหน้า login ทันที
  window.location.href = 'login.php';
}

// --- ฟังก์ชันควบคุม Pop-up ---
// ตรวจสอบว่าเราอยู่ในหน้าที่มีฟอร์มโปรไฟล์หรือไม่ เพื่อไม่ให้เกิด error ในหน้าอื่น
const profileForm = document.querySelector('.profile-form');

if (profileForm) {
  const successModal = document.getElementById('successModal');
  const closeModalBtn = document.getElementById('closeModalBtn');

  // Event listener สำหรับการ submit ฟอร์ม
  profileForm.addEventListener('submit', function(event) {
    // ป้องกันไม่ให้หน้าเว็บโหลดใหม่
    event.preventDefault(); 
    
    // แสดง Pop-up
    successModal.classList.add('show');
  });

  // Event listener สำหรับการกดปุ่ม "ตกลง" ใน Pop-up
  closeModalBtn.addEventListener('click', function() {
    // ซ่อน Pop-up
    successModal.classList.remove('show');
  });

  // Event listener สำหรับการคลิกที่พื้นหลังสีดำเพื่อปิด Pop-up
  window.addEventListener('click', function(event) {
    if (event.target == successModal) {
      successModal.classList.remove('show');
    }
  });
}