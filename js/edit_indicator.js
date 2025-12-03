document.addEventListener('DOMContentLoaded', function() {
    
    // --- [ส่วนที่ 1: Logic เดิมสำหรับโหลด KPI Types] ---
    const kpiTypeSelect = document.getElementById('kpi_type_select');
    const radioButtons = document.querySelectorAll('input[name="Group_ID"]');
    
    // รับค่าจาก data-attributes ที่ฝังไว้ใน HTML
    // (เราต้องแก้ PHP ให้ส่งค่าเหล่านี้มาทาง HTML attribute แทนการ echo ใส่ JS โดยตรง)
    const formContainer = document.querySelector('.form-wrapper'); // หรือ element อื่นที่ครอบอยู่
    let loadedKpiTypeId = kpiTypeSelect.getAttribute('data-loaded-id');
    const initialGroupId = document.querySelector('input[name="Group_ID"]:checked')?.value;

    function loadKpiTypes(groupId, selectedKpiTypeId = null) {
        // ชี้ไปที่ไฟล์ PHP (edit_indicator.php) เพื่อดึงข้อมูล JSON
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
            })
            .catch(error => console.error('Error loading KPI types:', error));
    }
    
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                // เมื่อเปลี่ยนกลุ่ม, ไม่ต้องเลือก Type ID เดิม
                loadKpiTypes(this.value, null); 
            }
        });
    });

    // --- [ส่วนที่ 2: Logic ใหม่สำหรับซ่อน/แสดงฟิลด์] ---
    
    const articleTypeSelect = document.getElementById('article_type_select');
    const publicationOptionsGroup = document.getElementById('publication_options_group');
    const fetchDataRadios = document.querySelectorAll('input[name="fetch_data"]');
    const articleTypeGroup = document.getElementById('article_type_group');

    // (ฟังก์ชันสลับ "ประเภทการตีพิมพ์")
    function togglePublicationOptions() {
        // ตรวจสอบว่ามี element นี้อยู่จริงหรือไม่ก่อนเรียกใช้
        if (!articleTypeSelect || !publicationOptionsGroup) return;

        // เช็ค Value ของ Table ID ที่เป็น publication (ในโค้ดเดิมคือ value="3")
        // ควรตรวจสอบให้แน่ใจว่าค่า value ใน HTML ตรงกับ Database จริงๆ
        if (articleTypeSelect.value === '3') { 
            publicationOptionsGroup.style.display = 'grid'; 
        } else {
            publicationOptionsGroup.style.display = 'none';
        }
    }

    // (ฟังก์ชันสลับ "ประเภทบทความ")
    function toggleArticleOptions() {
        const selectedFetchData = document.querySelector('input[name="fetch_data"]:checked').value;

        if (selectedFetchData === 'yes') {
            if (articleTypeGroup) articleTypeGroup.style.display = 'grid'; 
            togglePublicationOptions(); 
        } else {
            if (articleTypeGroup) articleTypeGroup.style.display = 'none'; 
            if (publicationOptionsGroup) publicationOptionsGroup.style.display = 'none'; 
        }
    }

    // --- [ส่วนที่ 3: Event Listeners และ Initial Calls] ---

    if (articleTypeSelect) {
        articleTypeSelect.addEventListener('change', togglePublicationOptions);
    }
    
    fetchDataRadios.forEach(radio => {
        radio.addEventListener('change', toggleArticleOptions);
    });

    // --- Run on page load ---
    
    // โหลด KPI Type ครั้งแรก
    if (initialGroupId) {
        loadKpiTypes(initialGroupId, loadedKpiTypeId);
    }

    // เรียกใช้ฟังก์ชันซ่อน/แสดงผลครั้งแรก
    toggleArticleOptions(); 
});