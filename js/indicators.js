document.addEventListener('DOMContentLoaded', function () {
    const groupSelect = document.getElementById('group_use_kpis');
    const typeSelect = document.getElementById('kpi_type');
    const academicInput = document.getElementById('Academic');
    const selectedKpiTypeId = typeSelect.getAttribute('data-selected-id');

    function loadKpiTypes(groupId, selectedTypeId = null) {
        //  ดึงค่าปีการศึกษาปัจจุบันจาก Input
        const academic = academicInput ? academicInput.value : '';

        // ถ้าไม่ได้เลือกกลุ่ม ให้แสดง "ทั้งหมด" และจบการทำงาน
        if (!groupId || groupId === 'empty' || groupId === '') {
            typeSelect.innerHTML = '<option value="">ทั้งหมด</option>';
            return;
        }

        // ส่งพารามิเตอร์ academic ไปด้วย
        fetch(`indicators.php?group_id=${groupId}&academic=${academic}`)
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
                    option.textContent = kpiType.KPI_Type_Name_EN; 

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

    // Event Listener เมื่อเปลี่ยน "กลุ่มผู้ใช้"
    if (groupSelect) {
        groupSelect.addEventListener('change', function () {
            loadKpiTypes(this.value, null);
        });
    }

    // Event Listener เมื่อเปลี่ยน "ปีการศึกษา"
    if (academicInput) {
        academicInput.addEventListener('input', function () {
            // โหลดข้อมูลใหม่โดยใช้ Group ID ปัจจุบัน
            if (groupSelect.value) {
                loadKpiTypes(groupSelect.value, selectedKpiTypeId);
            }
        });
    }

    if (groupSelect && groupSelect.value) {
        loadKpiTypes(groupSelect.value, selectedKpiTypeId);
    }
});