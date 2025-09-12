<?php
// app/db.php
// Saaf aur safe MySQLi connection helper for local dev.
// Edit $DB_* values if needed.

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';            // XAMPP default is empty
$DB_NAME = 'pushtimarg_shringar';  // tumhara DB name

// Optional: turn mysqli exceptions on (helpful while debugging)
// mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    // For production don't echo details; die for dev is ok
    die("Database connection failed: " . $conn->connect_error);
}

// Ensure proper charset
$conn->set_charset("utf8mb4");

// Export $conn for other scripts
// (file simply sets $conn variable)
