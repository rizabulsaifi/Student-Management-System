<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $conn->prepare("SELECT image FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();

        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            if ($student['image'] !== 'default.jpg') {
                @unlink("../assets/images/uploads/" . $student['image']);
            }
            setAlert('Student deleted successfully', 'success');
        } else {
            setAlert('Failed to delete student', 'danger');
        }
        $stmt->close();
    } else {
        setAlert('Student not found', 'danger');
    }
}

header("Location: students.php");
exit();
