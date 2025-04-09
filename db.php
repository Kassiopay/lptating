<?php
$username = "root"; 
$password = ""; 
$database = "LPTESTING"; 

$mysqli = new mysqli("MySQL-8.0", $username, $password, $database); 
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
?>