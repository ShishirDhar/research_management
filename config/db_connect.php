<?php
$servername = "127.0.0.1";
//So I created a servername variable that stores the ip address of the localhost(my laptop)
//This tells PHP that MYSQL is running on my laptop
$username = "root";
//root is the default mysql admin in xampp
$password = ""; // no password
$database = "research_management";
//this is the name of the database that I am connecting to

// Create connection
$conn = new mysqli($servername, $username, $password, $database);
// new mysqli creates a new connection object and stores it into the conn variable
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

?>