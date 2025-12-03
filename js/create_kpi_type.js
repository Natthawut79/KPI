document.addEventListener('DOMContentLoaded', function() {
    // 1. อ้างอิง Element
    const groupSelect = document.getElementById('group_select');
    const orderNoInput = document.getElementById('order_no');
    const academicInput = document.getElementById('academic_input');

    // ตรวจสอบว่ามี Element ครบไหม
    if (!groupSelect || !orderNoInput || !academicInput) return;

    // 2. ฟังก์ชันคำนวณ Order No แบบ Real-time
    function calculateNextOrder() {
        const selectedGroupId = groupSelect.value;
        const selectedYear = academicInput.value;

        // ถ้าข้อมูลยังไม่ครบ ให้เคลียร์ค่าลำดับที่
        if (!selectedGroupId || !selectedYear) {
            orderNoInput.value = '';
            return;
        }

        // 3. กรองข้อมูลจาก ALL_KPI_DATA (ที่ส่งมาจาก PHP)
        // เพื่อหาว่าใน database มีข้อมูลของ กลุ่มนี้ + ปีนี้ ไหม
        // (ใช้ == เพื่อเปรียบเทียบค่าโดยไม่สนว่าเป็น string หรือ int)
        const existingRecords = ALL_KPI_DATA.filter(item => 
            item.Group_ID == selectedGroupId && item.Academic == selectedYear
        );

        // 4. หาค่า Order_No ที่สูงที่สุดที่มีอยู่
        let maxOrder = 0;
        if (existingRecords.length > 0) {
            // ดึงเฉพาะ field Order_No ออกมาเป็น array ตัวเลข
            const orders = existingRecords.map(item => parseInt(item.Order_No) || 0);
            // หาค่ามากสุดใน array
            maxOrder = Math.max(...orders);
        }

        // 5. กำหนดลำดับถัดไป = ค่ามากสุด + 1
        // ถ้าปีนั้นยังไม่มีข้อมูลเลย maxOrder จะเป็น 0 ดังนั้นลำดับถัดไปจะเป็น 1
        orderNoInput.value = maxOrder + 1;
    }

    // 6. สั่งให้ทำงานเมื่อมีการเปลี่ยนค่า "กลุ่มผู้ใช้" หรือ "ปีการศึกษา"
    groupSelect.addEventListener('change', calculateNextOrder);
    
    // ใช้ event 'input' กับช่องปีการศึกษา เพื่อให้คำนวณทันทีที่พิมพ์
    academicInput.addEventListener('input', calculateNextOrder);

    // รันครั้งแรก (เผื่อกรณีมีค่า Default หรือ Browser จำค่าเก่าไว้)
    calculateNextOrder();
});