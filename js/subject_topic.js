document.addEventListener('DOMContentLoaded', function() {
    const kpiTypeSelect = document.getElementById('kpi_type_select');
    const radioButtons = document.querySelectorAll('input[name="Group_ID"]');
    const orderNoInput = document.getElementById('order_no_input');
    const academicInput = document.getElementById('academic_year');
    
    const isEditMode = document.getElementById('is_edit_mode');
    const currentKpiTypeIdInput = document.getElementById('current_kpi_type_id');
    let initialLoadComplete = false;
    function loadNextOrderNo() {
        const checkedRadio = document.querySelector('input[name="Group_ID"]:checked');
        const academic = academicInput ? academicInput.value : '';

        if (checkedRadio && academic) {
            const groupId = checkedRadio.value;
            fetch(`create_subject.php?get_next_order=1&group_id=${groupId}&academic=${academic}`)
                .then(response => response.json())
                .then(data => {
                    if (data.next_order && orderNoInput) {
                        orderNoInput.value = data.next_order;
                    }
                })
                .catch(err => console.error('Error loading order:', err));
        }
    }
    function loadKpiTypes() {
        const checkedRadio = document.querySelector('input[name="Group_ID"]:checked');
        const groupId = checkedRadio ? checkedRadio.value : '';
        const academic = academicInput ? academicInput.value : '';
        const currentKpiTypeId = currentKpiTypeIdInput ? currentKpiTypeIdInput.value : '';

        if (!groupId) {
            kpiTypeSelect.innerHTML = '<option value="">--- กรุณาเลือก ---</option>';
            return;
        }

        fetch(`create_subject.php?group_id=${groupId}&academic=${academic}`)
            .then(response => response.json())
            .then(data => {
                let options = '<option value="">--- กรุณาเลือก ---</option>';
                data.forEach(item => {
                    const isSelected = (item.KPI_type_id == currentKpiTypeId) ? 'selected' : '';
                    
                    options += `<option value="${item.KPI_type_id}" ${isSelected}>${item.KPI_Type_Name_EN}</option>`;
                });
                kpiTypeSelect.innerHTML = options;
            })
            .catch(error => console.error('Error loading KPI types:', error));
    }
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            initialLoadComplete = true;
            loadKpiTypes();
            loadNextOrderNo();
        });
    });
    if (academicInput) {
        const handleAcademicChange = function() {
            initialLoadComplete = true;
            loadKpiTypes();
            loadNextOrderNo();
        };
        academicInput.addEventListener('change', handleAcademicChange);
        academicInput.addEventListener('input', handleAcademicChange);
    }

    loadKpiTypes();
    if (!isEditMode) {
        loadNextOrderNo();
    }
    const form = document.querySelector('form');
    const subjectNameInput = document.querySelector('input[name="subject_name"]');

    if (form) {
        form.addEventListener('submit', function(e) {
            const subjectName = subjectNameInput.value.trim();
            const validNameRegex = /^[ก-๙0-9\s\.\-\(\)]+$/;

            if (!validNameRegex.test(subjectName)) {
                e.preventDefault();
                alert("ชื่อหัวข้อตัวชี้วัดต้องประกอบด้วยภาษาไทยหรือตัวเลขเท่านั้น");
                subjectNameInput.focus();
                return;
            }
        });
    }
});