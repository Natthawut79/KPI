document.addEventListener('DOMContentLoaded', function () {
    const groupSelect = document.getElementById('group_use_kpis');
    const typeSelect = document.getElementById('kpi_type');

    // รับค่าจาก data-attribute ที่เราฝังไว้ใน HTML (แทนการใช้ PHP echo ในไฟล์ JS)
    const selectedKpiTypeId = typeSelect.getAttribute('data-selected-id');

    function loadKpiTypes(groupId, selectedTypeId = null) {
        // ถ้าไม่ได้เลือกกลุ่ม (เลือก "ทั้งหมด" หรือ "ช่องว่าง") ให้แสดง "ทั้งหมด"
        if (!groupId || groupId === 'empty' || groupId === '') {
            typeSelect.innerHTML = '<option value="">ทั้งหมด</option>';
            return;
        }

        // เรียก AJAX ไปยังไฟล์ indicators.php
        fetch('indicators.php?group_id=' + groupId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // เริ่มต้นด้วย "ทั้งหมด"
                typeSelect.innerHTML = '<option value="">ทั้งหมด</option>';

                data.forEach(kpiType => {
                    const option = document.createElement('option');
                    option.value = kpiType.KPI_type_id;
                    option.textContent = kpiType.KPI_Type_Name_EN; // หรือ .KPI_Type_Name_TH

                    // หากมีค่าที่เคยเลือกไว้ (selectedTypeId) ให้ตั้งเป็น selected
                    if (selectedTypeId && kpiType.KPI_type_id == selectedTypeId) {
                        option.selected = true;
                    }
                    typeSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading KPI types:', error);
                typeSelect.innerHTML = '<option value="">ทั้งหมด (Error)</option>';
            });
    }

    // เพิ่ม Event Listener ให้ "กลุ่มผู้ใช้"
    if (groupSelect) {
        groupSelect.addEventListener('change', function () {
            // เมื่อเปลี่ยนกลุ่ม ให้โหลด Type ใหม่ (ไม่ต้องเลือก Type ID เดิม)
            loadKpiTypes(this.value, null);
        });

        // โหลดข้อมูลครั้งแรก (สำคัญมาก: เพื่อให้ dropdown แสดงผลถูกต้องหลังจากการค้นหา)
        if (groupSelect.value) {
            loadKpiTypes(groupSelect.value, selectedKpiTypeId);
        }
    }
});