<?php
if (isset($_POST['submit'])) {
    $targetDir = "uploads/";
    $fileName = basename($_FILES["image"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    // ตรวจสอบประเภทไฟล์
    $allowTypes = array('jpg','jpeg','png','gif');
    if (in_array($fileType, $allowTypes)) {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            echo "อัปโหลดสำเร็จ: <a href='$targetFilePath'>$fileName</a>";
        } else {
            echo "เกิดข้อผิดพลาดในการอัปโหลด";
        }
    } else {
        echo "ไฟล์ต้องเป็น JPG, JPEG, PNG หรือ GIF เท่านั้น";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>อัปโหลดรูปภาพ</title>
  <style>
    #preview {
      margin-top: 10px;
      max-width: 300px;
      max-height: 300px;
    }
  </style>
</head>
<body>
  <form action="testphoto.php" method="post" enctype="multipart/form-data">
    <label>เลือกรูปภาพ:</label>
    <input type="file" name="image" id="image" accept="image/*" onchange="previewImage(event)">
    <br>
    <img id="preview" src="" alt="Preview จะอยู่ตรงนี้">
    <br><br>
    <button type="submit" name="submit">อัปโหลด</button>
  </form>

  <script>
    function previewImage(event) {
      const reader = new FileReader();
      reader.onload = function(){
        const output = document.getElementById('preview');
        output.src = reader.result;
      };
      reader.readAsDataURL(event.target.files[0]);
    }
  </script>
</body>
</html>
