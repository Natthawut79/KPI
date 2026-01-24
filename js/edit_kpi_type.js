document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editKpiForm');
    
    // อ้างอิง Input ช่องชื่อ
    const nameEnInput = document.querySelector('input[name="KPI_Type_Name_EN"]');
    const nameThInput = document.querySelector('input[name="KPI_Type_Name_TH"]');

    if (form) {
        form.addEventListener('submit', function(e) {
            const enValue = nameEnInput.value.trim();
            const thValue = nameThInput.value.trim();
            const enRegex = /^[\x20-\x7E]+$/;
            const thRegex = /^[ก-๙0-9\s\.\-\(\)]+$/;

            // ตรวจสอบภาษาอังกฤษ
            if (!enRegex.test(enValue)) {
                e.preventDefault();
                alert("ชื่อประเภทตัวชี้วัด (ENG) ต้องเป็นตัวอักษรภาษาอังกฤษเท่านั้น");
                nameEnInput.focus();
                return;
            }

            // ตรวจสอบภาษาไทย
            if (!thRegex.test(thValue)) {
                e.preventDefault();
                alert("ชื่อประเภทตัวชี้วัด (TH) ต้องเป็นตัวอักษรภาษาไทยเท่านั้น");
                nameThInput.focus();
                return;
            }
        });
    }
});