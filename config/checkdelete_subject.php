<?php
session_start();
include "conn.php";

if (isset($_GET['subject_id'])) {
    $subject_id = mysqli_real_escape_string($conn, $_GET['subject_id']);

    $sql = "DELETE FROM subject_topic WHERE subject_id = '$subject_id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>
                alert('ลบข้อมูลสำเร็จ!');
                window.location.href = '../subject_topic.php';
              </script>";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}
mysqli_close($conn);
?>