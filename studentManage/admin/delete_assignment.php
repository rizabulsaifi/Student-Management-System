<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $conn->prepare("SELECT file_path FROM assignments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $assignment = $result->fetch_assoc();
        
        $stmt = $conn->prepare("DELETE FROM assignments WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            if ($assignment['file_path']) {
                @unlink("../assets/images/assignments/" . $assignment['file_path']);
            }
            setAlert('Assignment deleted successfully', 'success');
        } else {
            setAlert('Failed to delete assignment', 'danger');
        }
        $stmt->close();
    }
}

header("Location: assignments.php");
exit();
?>