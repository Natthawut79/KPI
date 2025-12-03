document.addEventListener("DOMContentLoaded", function () {
    // Profile image handler
    const profileImage = document.getElementById("profileImage");
    const imageUpload = document.getElementById("imageUpload");

    if (profileImage && imageUpload) {
        profileImage.addEventListener("click", function (event) { // <--- เพิ่ม event parameter
            event.stopPropagation(); // <--- เพิ่มบรรทัดนี้ เพื่อหยุดการกระจาย event
            imageUpload.click();
        });

        imageUpload.addEventListener("change", function () {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    profileImage.src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // --- KPI FORM TABS ---
    const tabContainer = document.querySelector('.tab-container');
    if (tabContainer) {
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');

        tabLinks.forEach(link => {
            link.addEventListener('click', () => {
                const tabId = link.getAttribute('data-tab');

                // Remove active class from all links and content
                tabLinks.forEach(item => item.classList.remove('active'));
                tabContents.forEach(item => item.classList.remove('active'));

                // Add active class to the clicked link and corresponding content
                link.classList.add('active');
                const activeTab = document.getElementById(tabId);
                if(activeTab) {
                    activeTab.classList.add('active');
                }
            });
        });
    }
});

// --- ฟังก์ชัน Logout (คงเดิม) ---
function logout() {
  window.location.href = 'login.php';
}

// --- ฟังก์ชัน Export CSV (คงเดิม) ---
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
                 if (inputElement.type === 'radio' || inputElement.type === 'checkbox') {
                     cellText = inputElement.checked ? inputElement.value : '';
                 } else {
                     cellText = inputElement.value;
                 }
             } else {
                 cellText = cellElement.innerText;
             }

            let cleanText = cellText.trim().replace(/\s+/g, ' ').replace(/"/g, '""');
            row.push('"' + cleanText + '"');
        }
        csv.push(row.join(","));
    }

    // Download CSV file
    var csvFile = new Blob(["\uFEFF" + csv.join("\n")], {type: "text/csv;charset=utf-8;"}); // Added BOM for Excel UTF-8 compatibility
    var downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink); // Clean up
}


function previewImage(event) {
    const reader = new FileReader();
    const imageField = document.getElementById("profileImage");

    reader.onload = function(){
        if(reader.readyState == 2){
            imageField.src = reader.result;
        }
    }
    if(event.target.files[0]){
        reader.readAsDataURL(event.target.files[0]);
    }
}