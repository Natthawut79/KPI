const toggle = document.querySelector(".switch input");

toggle.addEventListener("change", () => {
  if (toggle.checked) {
    alert("โหมดแก้ไขเปิดอยู่ ✅");
  } else {
    alert("โหมดแก้ไขปิดอยู่ ❌");
  }
});

// ฟังก์ชันสำหรับปุ่มออกจากระบบ
function logout() {
  // ไม่ต้องมี alert แต่จะ redirect กลับไปหน้า login ทันที
  window.location.href = 'login.php';
}