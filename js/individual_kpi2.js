function calculateRowScore(scoreInput) {
    const row = scoreInput.closest('tr');
    if (!row) return;
    const weightText = row.querySelector('.topic-weight')?.textContent;
    const weight = parseFloat(weightText) || 0;
    let score = parseFloat(scoreInput.value) || 0;
    const totalScoreInput = row.querySelector('.total-score');
    if (!totalScoreInput) return;

    if (scoreInput.value !== '') {
        score = Math.max(1, Math.min(score, 5));
        if (parseFloat(scoreInput.value) !== score) {
           scoreInput.value = score;
        }
    } else {
        score = 0;
    }

    const calculatedScore = weight * score;
    totalScoreInput.value = calculatedScore.toFixed(2);

    const kpiTypeId = row.dataset.kpiTypeId;
    if (kpiTypeId) {
      calculateSectionTotal(kpiTypeId);
      calculateGrandTotal();
    } else {
        console.warn("Missing data-kpi-type-id on row:", row);
    }
}

// Function คำนวณคะแนนรวมประจำหมวด
function calculateSectionTotal(kpiTypeId) {
    const sectionRows = document.querySelectorAll(`#annual-review-table tbody tr[data-kpi-type-id="${kpiTypeId}"]:not(.section-total-row):not(.section-header)`);
    let sectionTotal = 0;
    sectionRows.forEach(row => {
        const totalScoreInput = row.querySelector('.total-score');
        if (totalScoreInput) {
            sectionTotal += parseFloat(totalScoreInput.value) || 0;
        }
    });

    // Update Annual Review Tab
    const sectionTotalInputAnnual = document.querySelector(`#annual-review-table .section-total-row[data-kpi-type-id="${kpiTypeId}"] .section-total-score`);
    if (sectionTotalInputAnnual) {
        sectionTotalInputAnnual.value = sectionTotal.toFixed(2);
    }

    // ⚠️ [START] MODIFICATION - อัปเดต Tab OKRs ด้วย
    const okrSectionRow = document.querySelector(`#okr-summary-table tr[data-kpi-type-id="${kpiTypeId}"]`);
    if (okrSectionRow) {
        const sectionTotalInputOkr = okrSectionRow.querySelector('.okr-section-score');
        if (sectionTotalInputOkr) {
            sectionTotalInputOkr.value = sectionTotal.toFixed(2);
        }
    }
    // ⚠️ [END] MODIFICATION
}

// Function คำนวณคะแนนรวมทั้งหมด
function calculateGrandTotal() {
    const allTotalScoreInputs = document.querySelectorAll('#annual-review-table tbody .total-score');
    let grandTotal = 0;
    allTotalScoreInputs.forEach(input => {
        grandTotal += parseFloat(input.value) || 0;
    });

    // Update Annual Review Tab (Total 500)
    const grandTotalInput500Annual = document.getElementById('grand-total-score-500');
    if (grandTotalInput500Annual) {
        grandTotalInput500Annual.value = grandTotal.toFixed(2);
    }

    // Calculate and Update Total 100%
    const grandTotalWeightEl = document.getElementById('grand-total-weight');
    const totalWeight = parseFloat(grandTotalWeightEl?.textContent) || 0;
    const maxPossibleScore = (totalWeight > 0) ? (totalWeight * 5) : 0;
    
    // ⬇⬇⬇ [หมายเหตุ] ⬇⬇⬇
    // คงสูตร * 100 ไว้ตามคำขอ (ไม่แก้ไขส่วนนี้)
    const grandTotalPercent = (maxPossibleScore > 0) ? (grandTotal / maxPossibleScore) * 50 : 0; 
    
    // ⬇⬇⬇ [เพิ่ม] อัปเดต hidden input (เพื่อให้ส่งค่า 100 max ไป) ⬇⬇⬇
    // (โค้ดนี้เพิ่มเข้ามาเพื่อให้การบันทึกคะแนนรวมทำงานได้ถูกต้อง)
    const hiddenTotal100Input = document.getElementById('grand-total-score-100-hidden');
    if (hiddenTotal100Input) {
        hiddenTotal100Input.value = grandTotalPercent.toFixed(2);
    }

    
    // ⚠️ [START] MODIFICATION - อัปเดต Tab OKRs ทั้ง 4 ช่อง
    
    // 1. Update OKR Tab (Summary Table)
    const okrTotalInput500 = document.getElementById('okr-grand-total-score-500');
    if (okrTotalInput500) {
        okrTotalInput500.value = grandTotal.toFixed(2);
    }
    const okrTotalInput100 = document.getElementById('okr-grand-total-score-100');
    if (okrTotalInput100) {
        // ⬇⬇⬇ [แก้ไข] ⬇⬇⬇
        // ใน Tab OKR ของหน้านี้ (ceo) ค่า 100 max ถูกแปลงเป็น 50
        // เราจะใช้สูตร * 50 สำหรับการแสดงผลใน Tab OKR เท่านั้น
        const grandTotalPercent_50 = (maxPossibleScore > 0) ? (grandTotal / maxPossibleScore) * 50 : 0;
        okrTotalInput100.value = grandTotalPercent_50.toFixed(2);
    }

    // 2. Update OKR Tab (Final Summary Section)
    const okrFinalInput500 = document.getElementById('okr-final-score-500');
    if (okrFinalInput500) {
        okrFinalInput500.value = grandTotal.toFixed(2);
    }
    const okrFinalInput100 = document.getElementById('okr-final-score-100');
    if (okrFinalInput100) {
        // ⬇⬇⬇ [แก้ไข] ⬇⬇⬇
        // ใช้ค่า * 50 ที่นี่ด้วย
        const grandTotalPercent_50 = (maxPossibleScore > 0) ? (grandTotal / maxPossibleScore) * 50 : 0;
        okrFinalInput100.value = grandTotalPercent_50.toFixed(2);
    }
    // ⚠️ [END] MODIFICATION
}


document.addEventListener('DOMContentLoaded', () => {
    // Initial Calculations on Page Load
    // ทำให้แน่ใจว่าทุกแถวถูกคำนวณตอนโหลดหน้า
    document.querySelectorAll('#annual-review-table .score-input').forEach(input => {
        if (input.value) { // คำนวณเฉพาะแถวที่มีค่าอยู่แล้ว
            calculateRowScore(input);
        }
    });
    // คำนวณคะแนนรวมทั้งหมด 1 ครั้งตอนโหลด
    calculateGrandTotal();


    // Tab Handling Logic
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    tabLinks.forEach(link => {
        link.addEventListener('click', () => {
            const tabId = link.getAttribute('data-tab');
            tabLinks.forEach(item => item.classList.remove('active'));
            tabContents.forEach(item => item.classList.remove('active'));
            link.classList.add('active');
            const activeTab = document.getElementById(tabId);
            if(activeTab) {
                activeTab.classList.add('active');
            }
        });
    });

    // Set Initial Active Tab
    const activeLink = document.querySelector('.tab-link.active') || (tabLinks.length > 0 ? tabLinks[0] : null);
    if (activeLink) {
         const activeTabId = activeLink.getAttribute('data-tab');
         const activeTabContent = document.getElementById(activeTabId);
         tabLinks.forEach(item => item.classList.remove('active'));
         tabContents.forEach(item => item.classList.remove('active'));
         activeLink.classList.add('active');
         if (activeTabContent) {
             activeTabContent.classList.add('active');
         }
    }

    // AJAX Submission for Annual Review Form
    const annualReviewForm = document.getElementById('annualReviewForm2'); // ⬇⬇⬇ [แก้ไข] ⬇⬇⬇
    if (annualReviewForm) {
        annualReviewForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // ⚠️ [START] MODIFICATION - ใช้ FormData จาก Form โดยตรง
            // ไม่ต้องสร้าง FormData เอง เพราะไฟล์จะมาด้วย
            const formData = new FormData(annualReviewForm); // ⬇⬇⬇ [แก้ไข] ⬇⬇⬇
            // ⚠️ [END] MODIFICATION

            const submitButton = annualReviewForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'กำลังบันทึก...';

            fetch('config/save_annual_review2.php', { method: 'POST', body: formData }) // ⬇⬇⬇ [แก้ไข] ⬇⬇⬇
            .then(response => {
                if (!response.ok) {
                     return response.json().then(err => { throw new Error(err.message || 'เกิดข้อผิดพลาด HTTP: ' + response.status); });
                }
                return response.json();
            })
            .then(data => {
                alert(data.message);
                if (data.success) {
                    window.location.reload();
                } else {
                     submitButton.disabled = false;
                     submitButton.textContent = 'บันทึกผลการประเมิน';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาด: ' + error.message);
                 submitButton.disabled = false;
                 submitButton.textContent = 'บันทึกผลการประเมิน';
            });
        });
    }

    

    // --- AJAX Submission for OKR Form (Placeholder) ---
    const okrForm = document.getElementById('okrEvaluationForm');
     if (okrForm) {
         const okrSubmitButton = okrForm.querySelector('button[type="submit"]');
        
     }
     const prefilledInputs = document.querySelectorAll('input[type="file"][data-prefill]');

        prefilledInputs.forEach(input => {
            // 2. อ่านข้อมูล JSON รายชื่อไฟล์
            const filesData = JSON.parse(input.dataset.prefill);

            if (filesData && filesData.length > 0) {
                const dataTransfer = new DataTransfer(); // เครื่องมือจำลองการเลือกไฟล์

                // 3. วนลูปไปดึงไฟล์จริงจาก Server (Fetch)
                const fetchPromises = filesData.map(fileInfo =>
                    fetch(fileInfo.url)
                        .then(response => response.blob()) // แปลงเป็น Blob (ก้อนข้อมูลไฟล์)
                        .then(blob => {
                            // สร้าง File Object ใหม่ขึ้นมา
                            const file = new File([blob], fileInfo.name, { type: blob.type || 'application/pdf' });
                            dataTransfer.items.add(file); // ยัดใส่รายการ
                        })
                        .catch(err => console.error("Error fetching file:", err))
                );

                // 4. เมื่อดึงครบทุกไฟล์แล้ว ให้ยัดใส่เข้าไปใน input
                Promise.all(fetchPromises).then(() => {
                    input.files = dataTransfer.files;

                    // (Option) แสดงข้อความแจ้งเตือนสีเขียวว่ามีไฟล์ถูกเลือกแล้ว
                    const fileNameDisplay = document.getElementById('filename-' + input.getAttribute('data-topic-id'));
                    if (fileNameDisplay) {
                        fileNameDisplay.textContent = input.files.length + " ไฟล์ที่ถูกดึง";
                        fileNameDisplay.style.color = "#28a745";
                    }
                });
            }
        });
    });


// Function to export HTML table to CSV
function exportTableToCsv(tableId, filename) {
    var csv = [];
    var rows = document.querySelectorAll("#" + tableId + " tr");

    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");

        for (var j = 0; j < cols.length; j++) {
            let cellElement = cols[j];
            let cellText = '';

             let inputElement = cellElement.querySelector('input, select, textarea');
             if (inputElement) {
                 let linkElement = cellElement.querySelector('a[target="_blank"]');
                 if (linkElement && inputElement.classList.contains('file-url')) {
                     cellText = inputElement.value;
                 }
                 // ⚠️ [START] MODIFICATION - ปรับปรุงการดึงชื่อไฟล์สำหรับ Export
                 else if (inputElement.classList.contains('file-upload')) {
                     // พยายามดึงชื่อไฟล์จาก .existing-files ก่อน
                     let existingFiles = [];
                     cellElement.querySelectorAll('.existing-files .file-link-wrapper a').forEach(a => {
                         existingFiles.push(a.textContent.trim());
                     });
                     if (existingFiles.length > 0) {
                         cellText = existingFiles.join('; '); // หากมีหลายไฟล์
                     } else if (inputElement.files && inputElement.files.length > 0) {
                         let newFiles = [];
                         for(let f = 0; f < inputElement.files.length; f++) {
                             newFiles.push(inputElement.files[f].name);
                         }
                         cellText = newFiles.join('; ');
                     } else {
                         cellText = '';
                     }
                 }
                 // ⚠️ [END] MODIFICATION
                 else if (inputElement.type === 'radio' || inputElement.type === 'checkbox') {
                     cellText = inputElement.checked ? inputElement.value : '';
                 } else {
                     cellText = inputElement.value;
                 }
             } else {
                 cellText = cellElement.innerText;
             }

            let cleanText = cellText.trim().replace(/\s\s+/g, ' ').replace(/"/g, '""');
            row.push('"' + cleanText + '"');
        }
        csv.push(row.join(","));
    }

    var csvFile = new Blob(["\uFEFF" + csv.join("\n")], {type: "text/csv;charset=utf-8;"});
    var downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
/**
 * ฟังก์ชันสำหรับลบไฟล์ที่แนบไปแล้ว
 * @param {HTMLElement} buttonElement - องค์ประกอบปุ่มที่ถูกคลิก (this)
 * @param {number} fileId - ID ของไฟล์ (File_path) ที่ต้องการลบ
 */
function deleteFile(buttonElement, fileId) {
    
    if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการลบไฟล์นี้?')) {
        return;
    }

    const form = document.getElementById('annualReviewForm2'); 
    const academicYearInput = form.querySelector('input[name="academic_year"]');
    const academicYear = academicYearInput ? academicYearInput.value : 0;

    if (academicYear == 0) {
        alert('ไม่พบปีการศึกษาในฟอร์ม ไม่สามารถลบได้');
        return;
    }
    // 
    // ▼▼▼ ตรวจสอบส่วนนี้ให้ถูกต้อง ▼▼▼
    //
    fetch('config/delete_kpi_file.php', { // <-- 1. ต้องเป็น delete_kpi_file.php
        method: 'POST',                      // <-- 2. ต้องเป็น POST
        headers: {
            'Content-Type': 'application/json' // <-- 3. ต้องเป็น application/json
        },
        body: JSON.stringify({ 
            file_path_id: fileId,
            academic_year: academicYear // <-- เพิ่มตัวนี้
        }) // <-- 4. ต้องส่งเป็น JSON body
    })
    //
    // ▲▲▲ ตรวจสอบส่วนนี้ให้ถูกต้อง ▲▲▲
    //
    .then(response => {
        // (เพิ่มการตรวจสอบ response.ok)
        if (!response.ok) {
             // ถ้า server ตอบกลับมาว่ามีปัญหา (เช่น 404, 500)
             // เราต้องอ่าน text เพื่อดูว่า error คืออะไร
             return response.json().then(err => { 
                 throw new Error(err.message || 'Server error'); 
             });
        }
        return response.json(); 
    })
    .then(data => {
        if (data.success) {
            const fileWrapper = buttonElement.closest('.file-link-wrapper');
            if (fileWrapper) {
                fileWrapper.remove();
            }
            alert(data.message || 'ลบไฟล์สำเร็จ');
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error deleting file:', error);
        // แสดง error ที่มาจาก server (ถ้ามี)
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error.message);
    });
}