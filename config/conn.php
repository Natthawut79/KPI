<?php 
 $servername="localhost";
 $userName="root";
 $password="";
 $db="kpi_project";
 $conn=mysqli_connect($servername,$userName,$password,$db);
 if($conn){
 } else {
    echo "Cannot connect";
 }

?>