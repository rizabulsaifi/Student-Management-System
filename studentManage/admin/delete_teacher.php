<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $conn->prepare("SELECT image FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $teacher = $result->fetch_assoc();

        $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            if ($teacher['image'] !== 'default.jpg') {
                @unlink("../assets/images/uploads/" . $teacher['image']);
            }
            setAlert('Teacher deleted successfully', 'success');
        } else {
            setAlert('Failed to delete teacher', 'danger');
        }
        $stmt->close();
    } else {
        setAlert('Teacher not found', 'danger');
    }
}

header("Location: teachers.php");
exit();
