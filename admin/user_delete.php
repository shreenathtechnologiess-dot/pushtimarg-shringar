<?php
// admin/user_delete.php
session_start();
require_once __DIR__ . '/../app/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
  die("Database connection not available.");
}

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['flash_success'] = "User #{$id} deleted successfully.";
    } else {
        $_SESSION['flash_error'] = "Failed to delete user: " . $stmt->error;
    }
    $stmt->close();
}

header("Location: users.php");
exit;
