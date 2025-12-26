document.addEventListener('DOMContentLoaded', function() {
    
    // ส่วนจัดการ Form Submit (checkbox)
    const recordForm = document.getElementById('recordForm');
    if (recordForm) {
        recordForm.addEventListener('submit', function(e) {
            var checkbox = document.getElementById('toggleSwitch');
            var hiddenInput = document.querySelector('input[name="toggle_status_hidden"]');
            
            if (checkbox && checkbox.checked) {
                if(hiddenInput) {
                    hiddenInput.disabled = true; 
                }
                checkbox.value = "เปิด";
            } else if (checkbox) {
                // ถ้าไม่ได้ติ๊ก ให้ disable checkbox เพื่อไม่ให้ส่งค่าไป (จะส่งค่าจาก hidden แทน)
                checkbox.disabled = true; 
                if(hiddenInput) {
                    hiddenInput.disabled = false; 
                }
            }
        });
    }

    // ส่วนจัดการ Date Input (ตรวจสอบวันเริ่มต้น-สิ้นสุด)
    const startDateInput = document.getElementById('start-datetime');
    const endDateInput = document.getElementById('end-datetime');

    if (startDateInput && endDateInput) {
        function validateDates() {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);

            if (startDateInput.value && endDateInput.value) {
                if (endDate < startDate) {
                    alert("วันที่สิ้นสุดต้องไม่น้อยกว่าวันที่เริ่มต้น");
                    endDateInput.value = "";
                }
            }
        }

        startDateInput.addEventListener('change', validateDates);
        endDateInput.addEventListener('change', validateDates);
    }
});