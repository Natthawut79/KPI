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
  alert("คุณได้ออกจากระบบแล้ว");
  // ในการใช้งานจริง อาจจะเปลี่ยนไปหน้า login
  // window.location.href = "login.html";
}