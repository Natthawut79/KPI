document.addEventListener("DOMContentLoaded", function () {
    const profileImage = document.getElementById("profileImage");
    const imageUpload = document.getElementById("imageUpload");

    if (profileImage && imageUpload) {
        profileImage.addEventListener("click", function (event) {
            event.stopPropagation();
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

    const tabContainer = document.querySelector('.tab-container');
    if (tabContainer) {
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
    }
});

function logout() {
  window.location.href = 'login.php';
}
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

    var csvFile = new Blob(["\uFEFF" + csv.join("\n")], {type: "text/csv;charset=utf-8;"});
    var downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
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