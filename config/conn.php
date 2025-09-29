<?php 
 $servername="localhost";
 $userName="root";
 $password="";
 $db="parking_me";
 $conn=mysqli_connect($servername,$userName,$password,$db);
 if($conn){
 } else {
    echo "Cannot connect";
 }

?>