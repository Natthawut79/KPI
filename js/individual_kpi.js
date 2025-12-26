function calculateRowScore(scoreInput) {
    const row = scoreInput.closest('tr');
    if (!row) return;
    const weightText = row.querySelector('.topic-weight')?.textContent;
    const weight = parseFloat(weightText) || 0;
    let score = parseFloat(scoreInput.value) || 0;
    const totalScoreInput = row.querySelector('.total-score');
    if (!totalScoreInput) return;

    if (scoreInput.value !== '') {
        score = Math.max(1, score);
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
    
    // ดึงค่าน้ำหนักของหมวด (Type Weight)
    const headerRow = document.querySelector(`#annual-review-table tbody tr.section-header[data-kpi-type-id="${kpiTypeId}"]`);
    const typeWeight = headerRow ? (parseFloat(headerRow.dataset.typeWeight) || 0) : 0;

    let totalRawScore = 0;      // คะแนนดิบที่ทำได้รวมกัน
    let totalMaxScore = 0;      // คะแนนเต็มที่เป็นไปได้ (น้ำหนักหัวข้อ x 5)

    sectionRows.forEach(row => {
        // ดึงน้ำหนักของหัวข้อย่อย
        const weightText = row.querySelector('.topic-weight')?.textContent;
        const topicWeight = parseFloat(weightText) || 0;

        // ดึงคะแนนที่ได้ (/คะแนนดิบ)
        const totalScoreInput = row.querySelector('.total-score');
        if (totalScoreInput) {
            totalRawScore += parseFloat(totalScoreInput.value) || 0;
        }

        // คำนวณคะแนนเต็มของหัวข้อนี้ (น้ำหนักหัวข้อ x 5 คะแนนเต็ม)
        totalMaxScore += (topicWeight * 5);
    });

    // (คะแนนที่ได้ / คะแนนเต็ม) * น้ำหนักหมวด
    let weightedSectionScore = 0;
    if (totalMaxScore > 0) {
        weightedSectionScore = (totalRawScore / totalMaxScore) * typeWeight;
    }

    // แสดงผลลัพธ์
    const sectionTotalInputAnnual = document.querySelector(`#annual-review-table .section-total-row[data-kpi-type-id="${kpiTypeId}"] .section-total-score`);
    if (sectionTotalInputAnnual) {
        sectionTotalInputAnnual.value = (weightedSectionScore * 5).toFixed(2);
    }
}

function calculateGrandTotal() {
    const allSectionTotalInputs = document.querySelectorAll('#annual-review-table .section-total-score');
    
    let grandTotal = 0; // นี่คือคะแนนรวมสเกล 100 (ตามน้ำหนัก)
    allSectionTotalInputs.forEach(input => {
        grandTotal += parseFloat(input.value) || 0;
        
    });
    

    // แสดงผลคะแนนเต็ม 500 
    const grandTotalInput500Annual = document.getElementById('grand-total-score-500');
    if (grandTotalInput500Annual) {
        grandTotalInput500Annual.value = (grandTotal).toFixed(2);
    }

    // คำนวณคะแนนเต็ม (Max Possible Score) สเกล 500
    const grandTotalWeightEl = document.getElementById('grand-total-weight');
    const totalWeight = parseFloat(grandTotalWeightEl?.textContent) || 0;
    const maxPossibleScore = (totalWeight > 0) ? (totalWeight * 5) : 0;
    const grandTotalPercent = (maxPossibleScore > 0) ? ((grandTotal) / maxPossibleScore) * 80 : 0; 
    
    // ส่งค่าไปที่ Hidden Input เพื่อบันทึกลงฐานข้อมูล (Score_100_max)
    const hiddenTotal100Input = document.getElementById('grand-total-score-100-hidden');
    if (hiddenTotal100Input) {
        hiddenTotal100Input.value = grandTotalPercent.toFixed(2);
    }
}


document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('#annual-review-table .score-input').forEach(input => {
        if (input.value) { // คำนวณเฉพาะแถวที่มีค่าอยู่แล้ว
            calculateRowScore(input);
        }
    });
    calculateGrandTotal();
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
    const annualReviewForm = document.getElementById('annualReviewForm');
    if (annualReviewForm) {
        annualReviewForm.addEventListener('submit', function(event) {
            event.preventDefault();
            

            const formData = new FormData(annualReviewForm);
            const submitButton = annualReviewForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'กำลังบันทึก...';

            fetch('config/save_annual_review.php', { method: 'POST', body: formData })
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

      const prefilledInputs = document.querySelectorAll('input[type="file"][data-prefill]');
        
        prefilledInputs.forEach(input => {
            //อ่านข้อมูล JSON รายชื่อไฟล์
            const filesData = JSON.parse(input.dataset.prefill);
            
            if (filesData && filesData.length > 0) {
                const dataTransfer = new DataTransfer();
                
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
                 else if (inputElement.classList.contains('file-upload')) {
                     let existingFiles = [];
                     cellElement.querySelectorAll('.existing-files .file-link-wrapper a').forEach(a => {
                         existingFiles.push(a.textContent.trim());
                     });
                     if (existingFiles.length > 0) {
                         cellText = existingFiles.join('; ');
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

    const form = document.getElementById('annualReviewForm'); 
    const academicYearInput = form.querySelector('input[name="academic_year"]');
    const academicYear = academicYearInput ? academicYearInput.value : 0;

    if (academicYear == 0) {
        alert('ไม่พบปีการศึกษาในฟอร์ม ไม่สามารถลบได้');
        return;
    }
    fetch('config/delete_kpi_file.php', {
        method: 'POST',                      
        headers: {
            'Content-Type': 'application/json' 
        },
        body: JSON.stringify({ 
            file_path_id: fileId,
            academic_year: academicYear 
        })
    })

    .then(response => {
        if (!response.ok) {
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
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error.message);
    });
}