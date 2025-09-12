<?php
// app/db.php

$host = "localhost";
$user = "root"; // apna MySQL username
$pass = "";     // apna MySQL password
$dbname = "pushtimarg_shringar"; // tumhara database name

// Connection banate hain
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// (optional) character set set karna
$conn->set_charset("utf8");
?>
