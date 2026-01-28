document.addEventListener('DOMContentLoaded', function() {

    const kpiTypeSelect = document.getElementById('kpi_type_select');
    const radioButtons = document.querySelectorAll('input[name="Group_ID"]');
    const orderNoInput = document.getElementById('order_no_input');
    
    const subjectSelect = document.getElementById('subject_select');
    const publicationTypeSelect = document.getElementById('publication_type_select');
    const articleTypeSelect = document.getElementById('article_type_select');
    const publicationOptionsGroup = document.getElementById('publication_options_group');
    const fetchDataRadios = document.querySelectorAll('input[name="fetch_data"]');
    const articleTypeGroup = document.getElementById('article_type_group'); 

    const loadedKpiTypeId = kpiTypeSelect ? kpiTypeSelect.getAttribute('data-loaded-type-id') : null;
    const loadedOrderNo = orderNoInput ? orderNoInput.getAttribute('data-loaded-order-no') : '';
    const loadedSubjectId = subjectSelect ? subjectSelect.getAttribute('data-loaded-subject-id') : null;

    function loadNextOrderNo(kpiTypeId) {
        if (kpiTypeId && !loadedKpiTypeId) { 
            fetch('create_indicator.php?kpi_type_id=' + kpiTypeId)
                .then(response => response.json())
                .then(data => {
                    if(orderNoInput) orderNoInput.value = data.next_order;
                })
                .catch(error => console.error('Error loading next Order_no:', error));
        } else if (!kpiTypeId && !loadedKpiTypeId) {
            if(orderNoInput) orderNoInput.value = '';
        }
    }

    function loadSubjects(kpiTypeId) {
        if (!subjectSelect) return;

        if (!kpiTypeId) {
            subjectSelect.innerHTML = '<option value="">--- กรุณาเลือก ---</option>';
            return;
        }

        fetch(`create_indicator.php?get_subjects=1&kpi_type_id=${kpiTypeId}`)
            .then(response => response.json())
            .then(data => {
                // ล้างตัวเลือกเก่า
                subjectSelect.innerHTML = '<option value="">--- กรุณาเลือก ---</option>';

                if (data && data.length > 0) {
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.subject_id;
                        option.textContent = item.subject_name;

                        // เลือกค่าเดิมอัตโนมัติ (กรณีแก้ไข หรือโหลดครั้งแรก)
                        if (loadedSubjectId && item.subject_id == loadedSubjectId) {
                            option.selected = true;
                        }
                        subjectSelect.appendChild(option);
                    });
                } else {
                    // กรณีไม่มีข้อมูล
                    const option = document.createElement('option');
                    option.value = "";
                    option.textContent = "- ไม่มีหัวข้อตัวชี้วัด -";
                    subjectSelect.appendChild(option);
                }
            })
            .catch(error => console.error('Error loading subjects:', error));
    }

    // ฟังก์ชันโหลด KPI Types ตามกลุ่ม
    function loadKpiTypes(groupId) {
        fetch('create_indicator.php?group_id=' + groupId) 
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // ล้างตัวเลือกเก่า
                if(kpiTypeSelect) {
                    kpiTypeSelect.innerHTML = '<option value="">--- กรุณาเลือก ---</option>';
                    
                    data.forEach(kpiType => {
                        const option = document.createElement('option');
                        option.value = kpiType.KPI_type_id;
                        option.textContent = kpiType.KPI_Type_Name_EN; 
                        
                        // เลือกค่าเดิมอัตโนมัติ (กรณีแก้ไข)
                        if (loadedKpiTypeId && kpiType.KPI_type_id == loadedKpiTypeId) {
                            option.selected = true;
                        }
                        kpiTypeSelect.appendChild(option);
                    });

                    // หลังจากโหลด KPI Type เสร็จ ให้โหลด Subject ต่อทันที ถ้ามีค่าเลือกอยู่
                    if (kpiTypeSelect.value) {
                        loadSubjects(kpiTypeSelect.value);
                    } else {
                        loadSubjects("");
                    }
                }

                // จัดการช่อง Order No
                if (orderNoInput) {
                    if (loadedKpiTypeId) {
                        orderNoInput.value = loadedOrderNo;
                    } else {
                        orderNoInput.value = '';
                    }
                }
            })
            .catch(error => console.error('Error loading KPI types:', error));
    }
    // เช็คประเภทการตีพิมพ์ (แสดงเมื่อเลือก publication)
    function togglePublicationOptions() {
        if (!articleTypeSelect || !publicationOptionsGroup) return;

        if (articleTypeSelect.value === '3') { 
            publicationOptionsGroup.style.display = 'grid'; 
            // เพิ่ม: บังคับเลือก
            if (publicationTypeSelect) publicationTypeSelect.required = true;
        } else {
            publicationOptionsGroup.style.display = 'none';
            // เพิ่ม: เลิกบังคับเลือก และเคลียร์ค่า
            if (publicationTypeSelect) {
                publicationTypeSelect.required = false;
                publicationTypeSelect.value = ""; 
            }
        }
    }

    function toggleArticleOptions() {
        const selectedFetchRadio = document.querySelector('input[name="fetch_data"]:checked');
        if (!selectedFetchRadio) return;
        
        const selectedFetchData = selectedFetchRadio.value;

        if (selectedFetchData === 'yes') {
            if (articleTypeGroup) articleTypeGroup.style.display = 'grid'; 
            // เพิ่ม: บังคับเลือก
            if (articleTypeSelect) articleTypeSelect.required = true;
            
            togglePublicationOptions(); 
        } else {
            if (articleTypeGroup) articleTypeGroup.style.display = 'none'; 
            if (publicationOptionsGroup) publicationOptionsGroup.style.display = 'none'; 
            
            // เพิ่ม: เลิกบังคับเลือก และเคลียร์ค่าทั้งหมด
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

    // เมื่อเปลี่ยนกลุ่ม KPI (Radio Group)
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                loadKpiTypes(this.value);
            }
        });
    });

    // เมื่อเปลี่ยน KPI Type (Dropdown) -> ไปดึง Order No และ Subject
    if(kpiTypeSelect) {
        kpiTypeSelect.addEventListener('change', function() {
            loadNextOrderNo(this.value);
            loadSubjects(this.value); // [เพิ่มใหม่] เรียกโหลด Subject เมื่อเปลี่ยน KPI Type
        });
    }

    // เมื่อเปลี่ยนประเภทบทความ -> เช็ค Publication
    if(articleTypeSelect) {
        articleTypeSelect.addEventListener('change', togglePublicationOptions);
    }
    
    // เมื่อเปลี่ยน Radio ดึงข้อมูล
    fetchDataRadios.forEach(radio => {
        radio.addEventListener('change', toggleArticleOptions);
    });
    
    // โหลด KPI Types ตามกลุ่มที่เลือกไว้เริ่มต้น
    const checkedGroupRadio = document.querySelector('input[name="Group_ID"]:checked');
    if (checkedGroupRadio) {
        loadKpiTypes(checkedGroupRadio.value);
    } else if (radioButtons.length > 0) {
        // ถ้ายังไม่มีการเลือก (กรณีสร้างใหม่) ให้เลือกตัวแรกเป็น Default
        radioButtons[0].checked = true;
        loadKpiTypes(radioButtons[0].value);
    }

    //ปรับสถานะการซ่อน/แสดงฟิลด์
    toggleArticleOptions();
});