<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "ceit_complaint_system";

$conn = mysqli_connect($host, $username, $password, $database);

if(!$conn){
  die("Connection Failed: " . mysqli_connect_error());
}

//echo "Database connected successfully!";

?>
