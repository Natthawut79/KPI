document.addEventListener('DOMContentLoaded', function() {
    const groupSelect = document.getElementById('group_select');
    const orderNoInput = document.getElementById('order_no');
    const academicInput = document.getElementById('academic_input');
    const form = document.querySelector('form'); 
    const nameEnInput = document.querySelector('input[name="KPI_Type_Name_EN"]');
    const nameThInput = document.querySelector('input[name="KPI_Type_Name_TH"]');

    if (!groupSelect || !orderNoInput || !academicInput) return;

    function calculateNextOrder() {
        const selectedGroupId = groupSelect.value;
        const selectedYear = academicInput.value;
        if (!selectedGroupId || !selectedYear) {
            orderNoInput.value = '';
            return;
        }
        const existingRecords = ALL_KPI_DATA.filter(item => 
            item.Group_ID == selectedGroupId && item.Academic == selectedYear
        );

        let maxOrder = 0;
        if (existingRecords.length > 0) {
            const orders = existingRecords.map(item => parseInt(item.Order_No) || 0);
            maxOrder = Math.max(...orders);
        }
        orderNoInput.value = maxOrder + 1;
    }

    groupSelect.addEventListener('change', calculateNextOrder);
    academicInput.addEventListener('input', calculateNextOrder);

    calculateNextOrder();
    if (form) {
        form.addEventListener('submit', function(e) {
            const enValue = nameEnInput.value.trim();
            const thValue = nameThInput.value.trim();
            const enRegex = /^[\x20-\x7E]+$/;
            const thRegex = /^[ก-๙0-9\s\.\-\(\)]+$/;

            if (!enRegex.test(enValue)) {
                e.preventDefault();
                alert("ชื่อประเภทตัวชี้วัด (ENG) ต้องเป็นภาษาอังกฤษเท่านั้น");
                nameEnInput.focus();
                return;
            }

            if (!thRegex.test(thValue)) {
                e.preventDefault();
                alert("ชื่อประเภทตัวชี้วัด (TH) ต้องเป็นภาษาไทยเท่านั้น");
                nameThInput.focus();
                return;
            }
        });
        }
});