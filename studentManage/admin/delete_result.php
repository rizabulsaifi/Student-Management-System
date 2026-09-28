<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM results WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        setAlert('Result deleted successfully', 'success');
    } else {
        setAlert('Failed to delete result', 'danger');
    }
    $stmt->close();
}

header("Location: results.php");
exit();
