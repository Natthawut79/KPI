// --- ฟังก์ชัน Logout ---
function logout() {
  window.location.href = 'login.php';
}

// --- โค้ดสำหรับหน้า INDEX.PHP ---
// ค้นหา element ของ toggle switch
const toggleSwitch = document.getElementById('toggleSwitch');
// ตรวจสอบว่า element นี้มีอยู่ในหน้าเว็บหรือไม่ก่อนจะเพิ่ม event listener
if (toggleSwitch) {
    toggleSwitch.addEventListener("change", () => {
        if (toggleSwitch.checked) {
            alert("โหมดแก้ไขเปิดอยู่ ✅");
        } else {
            alert("โหมดแก้ไขปิดอยู่ ❌");
        }
    });
}

// --- โค้ดสำหรับหน้า PROFILE.PHP ---
// ค้นหา element ของฟอร์มโปรไฟล์
const profileForm = document.querySelector('.profile-form');
// ตรวจสอบว่า element นี้มีอยู่ในหน้าเว็บหรือไม่ก่อนจะเพิ่ม event listener
if (profileForm) {
  const successModal = document.getElementById('successModal');
  const closeModalBtn = document.getElementById('closeModalBtn');

  profileForm.addEventListener('submit', function(event) {
    event.preventDefault(); 
    successModal.classList.add('show');
  });

  closeModalBtn.addEventListener('click', function() {
    successModal.classList.remove('show');
  });

  window.addEventListener('click', function(event) {
    if (event.target == successModal) {
      successModal.classList.remove('show');
    }
  });
}