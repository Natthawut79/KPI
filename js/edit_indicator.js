document.addEventListener('DOMContentLoaded', function() {
    
    const kpiTypeSelect = document.getElementById('kpi_type_select');
    const radioButtons = document.querySelectorAll('input[name="Group_ID"]');
    const subjectSelect = document.getElementById('subject_select');
    
    // รับค่าจาก data-attributes
    let loadedKpiTypeId = kpiTypeSelect ? kpiTypeSelect.getAttribute('data-loaded-id') : null;
    const initialGroupId = document.querySelector('input[name="Group_ID"]:checked')?.value;
    const loadedSubjectId = subjectSelect ? subjectSelect.getAttribute('data-loaded-subject-id') : null;

    // ฟังก์ชันโหลด Subject Topic
    function loadSubjects(kpiTypeId, selectedSubjectId = null) {
        if (!subjectSelect) return;

        if (!kpiTypeId) {
            subjectSelect.innerHTML = '<option value="">--- กรุณาเลือก ---</option>';
            return;
        }

        fetch(`edit_indicator.php?get_subjects=1&kpi_type_id=${kpiTypeId}`)
            .then(response => response.json())
            .then(data => {
                subjectSelect.innerHTML = '<option value="">--- กรุณาเลือก ---</option>';
                
                if (data && data.length > 0) {
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.subject_id;
                        option.textContent = item.subject_name;

                        if (selectedSubjectId && item.subject_id == selectedSubjectId) {
                            option.selected = true;
                        }
                        subjectSelect.appendChild(option);
                    });
                } else {
                     const option = document.createElement('option');
                     option.value = "";
                     option.textContent = "- ไม่มีหัวข้อตัวชี้วัด -";
                     subjectSelect.appendChild(option);
                }
            })
            .catch(error => console.error('Error loading subjects:', error));
    }

    // ฟังก์ชันโหลด KPI Types
    function loadKpiTypes(groupId, selectedKpiTypeId = null) {
        fetch('edit_indicator.php?group_id=' + groupId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                kpiTypeSelect.innerHTML = '<option value="">--- กรุณาเลือก ---</option>';
                data.forEach(kpiType => {
                    const option = document.createElement('option');
                    option.value = kpiType.KPI_type_id;
                    option.textContent = kpiType.KPI_Type_Name_EN; 

                    if (selectedKpiTypeId && kpiType.KPI_type_id == selectedKpiTypeId) {
                        option.selected = true;
                    }
                    kpiTypeSelect.appendChild(option);
                });
                
                // หลังจากโหลด Type เสร็จ ถ้ามี Type ถูกเลือกอยู่แล้ว ให้โหลด Subject ต่อ
                if (kpiTypeSelect.value) {
                    const subjIdToLoad = (selectedKpiTypeId == loadedKpiTypeId) ? loadedSubjectId : null;
                    loadSubjects(kpiTypeSelect.value, subjIdToLoad);
                } else {
                    loadSubjects("");
                }
            })
            .catch(error => console.error('Error loading KPI types:', error));
    }
    
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                loadKpiTypes(this.value, null); 
                // เมื่อเปลี่ยน Group -> kpi type เปลี่ยน -> subject ควรเคลียร์รอ type ใหม่
            }
        });
    });

    if (kpiTypeSelect) {
        kpiTypeSelect.addEventListener('change', function() {
            // เมื่อเปลี่ยน Type -> โหลด Subject ใหม่ (ไม่ต้องเลือกค่าเดิม เพราะเป็น type ใหม่แล้ว)
            loadSubjects(this.value, null);
        });
    }

    const articleTypeSelect = document.getElementById('article_type_select');
    const publicationOptionsGroup = document.getElementById('publication_options_group');
    // เพิ่มบรรทัดนี้เพื่ออ้างอิงถึง select ของประเภทการตีพิมพ์
    const publicationTypeSelect = document.getElementById('publication_type_select'); 
    
    const fetchDataRadios = document.querySelectorAll('input[name="fetch_data"]');
    const articleTypeGroup = document.getElementById('article_type_group');

    function togglePublicationOptions() {
        if (!articleTypeSelect || !publicationOptionsGroup) return;
        
        if (articleTypeSelect.value === '3') { 
            publicationOptionsGroup.style.display = 'grid'; 
            // เพิ่ม: บังคับเลือกเมื่อแสดง
            if (publicationTypeSelect) publicationTypeSelect.required = true; 
        } else {
            publicationOptionsGroup.style.display = 'none';
            // เพิ่ม: ยกเลิกบังคับเลือกเมื่อซ่อน และเคลียร์ค่า
            if (publicationTypeSelect) {
                publicationTypeSelect.required = false;
                publicationTypeSelect.value = ""; // รีเซ็ตเป็นค่าเริ่มต้น
            } 
        }
    }

    function toggleArticleOptions() {
        const selectedFetchRadio = document.querySelector('input[name="fetch_data"]:checked');
        if (!selectedFetchRadio) return;
        
        const selectedFetchData = selectedFetchRadio.value;

        if (selectedFetchData === 'yes') {
            if (articleTypeGroup) articleTypeGroup.style.display = 'grid'; 
            // เพิ่ม: บังคับเลือกเมื่อแสดง
            if (articleTypeSelect) articleTypeSelect.required = true;
            
            togglePublicationOptions(); 
        } else {
            if (articleTypeGroup) articleTypeGroup.style.display = 'none'; 
            if (publicationOptionsGroup) publicationOptionsGroup.style.display = 'none'; 
            
            // เพิ่ม: ยกเลิกบังคับเลือกทั้งหมดเมื่อซ่อน และเคลียร์ค่า
            if (articleTypeSelect) {
                articleTypeSelect.required = false;
                articleTypeSelect.value = "";
            }
            if (publicationTypeSelect) {
                publicationTypeSelect.required = false;
                publicationTypeSelect.value = "";
            }
        }
    }

    if (articleTypeSelect) {
        articleTypeSelect.addEventListener('change', togglePublicationOptions);
    }
    
    fetchDataRadios.forEach(radio => {
        radio.addEventListener('change', toggleArticleOptions);
    });
    
    if (initialGroupId) {
        loadKpiTypes(initialGroupId, loadedKpiTypeId);
    }

    toggleArticleOptions(); 
});