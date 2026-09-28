<?php



function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}


function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}


function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}


function requireAdmin() {
    requireLogin();
    if (!hasRole('admin')) {
        header("Location: ../login.php");
        exit();
    }
}


function setAlert($message, $type = 'success') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}


function displayAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        $icon = $alert['type'] === 'success' ? 'check-circle' : 'exclamation-triangle';
        echo '<div class="alert alert-' . $alert['type'] . ' alert-dismissible fade show" role="alert">
                <i class="bi bi-' . $icon . '-fill me-2"></i>' . $alert['message'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        unset($_SESSION['alert']);
    }
}


function uploadFile($file, $folder = 'uploads', $allowed = ['jpg', 'jpeg', 'png', 'pdf']) {
    $targetDir = "../assets/images/" . $folder . "/";
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = basename($file["name"]);
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $newFileName = uniqid() . '_' . time() . '.' . $fileExtension;
    $targetFile = $targetDir . $newFileName;
    
    if (!in_array($fileExtension, $allowed)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return ['success' => true, 'filename' => $newFileName];
    } else {
        return ['success' => false, 'message' => 'Upload failed'];
    }
}


function calculateGrade($percentage) {
    if ($percentage >= 90) return 'A+';
    if ($percentage >= 80) return 'A';
    if ($percentage >= 70) return 'B+';
    if ($percentage >= 60) return 'B';
    if ($percentage >= 50) return 'C';
    if ($percentage >= 40) return 'D';
    return 'F';
}


function formatDate($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}


function getAcademicYear() {
    $currentMonth = date('m');
    $currentYear = date('Y');
    
    if ($currentMonth >= 4) {
        return $currentYear . '-' . ($currentYear + 1);
    } else {
        return ($currentYear - 1) . '-' . $currentYear;
    }
}


function paginate($totalRecords, $recordsPerPage, $currentPage) {
    $totalPages = ceil($totalRecords / $recordsPerPage);
    $offset = ($currentPage - 1) * $recordsPerPage;
    
    return [
        'total_pages' => $totalPages,
        'offset' => $offset,
        'current_page' => $currentPage
    ];
}


function renderPagination($totalPages, $currentPage, $url) {
    if ($totalPages <= 1) return '';
    
    $html = '<nav><ul class="pagination justify-content-center">';
    
    $disabled = $currentPage <= 1 ? 'disabled' : '';
    $html .= '<li class="page-item ' . $disabled . '">
                <a class="page-link" href="' . $url . '?page=' . ($currentPage - 1) . '">Previous</a>
            </li>';
    
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '">
                    <a class="page-link" href="' . $url . '?page=' . $i . '">' . $i . '</a>
                </li>';
    }
    
    $disabled = $currentPage >= $totalPages ? 'disabled' : '';
    $html .= '<li class="page-item ' . $disabled . '">
                <a class="page-link" href="' . $url . '?page=' . ($currentPage + 1) . '">Next</a>
            </li>';
    
    $html .= '</ul></nav>';
    
    return $html;
}
?>