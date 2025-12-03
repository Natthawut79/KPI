document.addEventListener('DOMContentLoaded', function() {
    // ==========================================
    // 1. ประกาศตัวแปรและอ้างอิง Element
    // ==========================================
    const kpiTypeSelect = document.getElementById('kpi_type_select');
    const radioButtons = document.querySelectorAll('input[name="Group_ID"]');
    const orderNoInput = document.getElementById('order_no_input');
    
    const articleTypeSelect = document.getElementById('article_type_select');
    const publicationOptionsGroup = document.getElementById('publication_options_group');
    const fetchDataRadios = document.querySelectorAll('input[name="fetch_data"]');
    const articleTypeGroup = document.getElementById('article_type_group'); 

    // อ่านค่าเริ่มต้นจาก HTML data attributes (ถ้ามี)
    const loadedKpiTypeId = kpiTypeSelect ? kpiTypeSelect.getAttribute('data-loaded-type-id') : null;
    const loadedOrderNo = orderNoInput ? orderNoInput.getAttribute('data-loaded-order-no') : '';

    // ==========================================
    // 2. ฟังก์ชันสำหรับดึงข้อมูล (AJAX)
    // ==========================================

    // ฟังก์ชันโหลด Order No ถัดไป
    function loadNextOrderNo(kpiTypeId) {
        // ดึงค่าใหม่เฉพาะตอนสร้าง (ไม่มี loadedKpiTypeId) และมีการเลือกค่า
        if (kpiTypeId && !loadedKpiTypeId) { 
            fetch('create_indicator.php?kpi_type_id=' + kpiTypeId)
                .then(response => response.json())
                .then(data => {
                    if(orderNoInput) orderNoInput.value = data.next_order;
                })
                .catch(error => console.error('Error loading next Order_no:', error));
        } else if (!kpiTypeId && !loadedKpiTypeId) {
            // ถ้าเลือกกลับไปที่ค่าว่าง ให้ล้างช่อง
            if(orderNoInput) orderNoInput.value = '';
        }
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
                        // เลือกแสดงชื่อภาษาไทย (TH) หรืออังกฤษ (EN) ตามต้องการ
                        option.textContent = kpiType.KPI_Type_Name_EN; 
                        
                        // เลือกค่าเดิมอัตโนมัติ (กรณีแก้ไข)
                        if (loadedKpiTypeId && kpiType.KPI_type_id == loadedKpiTypeId) {
                            option.selected = true;
                        }
                        kpiTypeSelect.appendChild(option);
                    });
                }

                // จัดการช่อง Order No
                if (orderNoInput) {
                    if (loadedKpiTypeId) {
                        orderNoInput.value = loadedOrderNo; // กรณีแก้ไข ใช้ค่าเดิม
                    } else {
                        orderNoInput.value = ''; // กรณีสร้างใหม่ ล้างค่ารอเลือก KPI Type
                    }
                }
            })
            .catch(error => console.error('Error loading KPI types:', error));
    }

    // ==========================================
    // 3. ฟังก์ชันสำหรับซ่อน/แสดงฟิลด์ (Toggle)
    // ==========================================

    // เช็คประเภทการตีพิมพ์ (แสดงเมื่อเลือก publication)
    function togglePublicationOptions() {
        if (!articleTypeSelect || !publicationOptionsGroup) return;

        // เช็คว่าเป็น publication (ID 3) หรือไม่
        if (articleTypeSelect.value == '3') { 
            publicationOptionsGroup.style.display = 'grid'; // หรือ block แล้วแต่ CSS
        } else {
            publicationOptionsGroup.style.display = 'none';
        }
    }

    // เช็คการดึงข้อมูลจากฐานข้อมูล
    function toggleArticleOptions() {
        const selectedRadio = document.querySelector('input[name="fetch_data"]:checked');
        if (!selectedRadio) return;

        if (selectedRadio.value === 'yes') {
            if(articleTypeGroup) articleTypeGroup.style.display = 'grid'; 
            togglePublicationOptions(); 
        } else {
            if(articleTypeGroup) articleTypeGroup.style.display = 'none'; 
            if(publicationOptionsGroup) publicationOptionsGroup.style.display = 'none'; 
        }
    }

    // ==========================================
    // 4. กำหนด Event Listeners
    // ==========================================

    // เมื่อเปลี่ยนกลุ่ม KPI (Radio Group)
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                loadKpiTypes(this.value);
            }
        });
    });

    // เมื่อเปลี่ยน KPI Type (Dropdown) -> ไปดึง Order No
    if(kpiTypeSelect) {
        kpiTypeSelect.addEventListener('change', function() {
            loadNextOrderNo(this.value);
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

    // ==========================================
    // 5. เริ่มทำงานเมื่อโหลดหน้าเว็บ (Initial Run)
    // ==========================================
    
    // 5.1 โหลด KPI Types ตามกลุ่มที่เลือกไว้เริ่มต้น
    const checkedGroupRadio = document.querySelector('input[name="Group_ID"]:checked');
    if (checkedGroupRadio) {
        loadKpiTypes(checkedGroupRadio.value);
    } else if (radioButtons.length > 0) {
        // ถ้ายังไม่มีการเลือก (กรณีสร้างใหม่) ให้เลือกตัวแรกเป็น Default
        radioButtons[0].checked = true;
        loadKpiTypes(radioButtons[0].value);
    }

    // 5.2 ปรับสถานะการซ่อน/แสดงฟิลด์
    toggleArticleOptions();
});