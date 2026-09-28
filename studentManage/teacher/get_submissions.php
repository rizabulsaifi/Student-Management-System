<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;

if ($assignment_id > 0) {
    $submissions = $conn->query("
        SELECT s.*, st.name, st.roll_number, st.image
        FROM assignment_submissions s
        JOIN students st ON s.student_id = st.id
        WHERE s.assignment_id = $assignment_id
        ORDER BY s.submission_date DESC
    ");

    if ($submissions->num_rows > 0) {
        echo '<div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Submitted On</th>
                            <th>File</th>
                            <th>Marks</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>';

        while ($sub = $submissions->fetch_assoc()) {
            echo '<tr>
                    <td>
                        <img src="../assets/images/uploads/' . htmlspecialchars($sub['image']) . '" class="avatar me-2" alt="">
                        ' . htmlspecialchars($sub['name']) . '<br>
                        <small class="text-muted">' . htmlspecialchars($sub['roll_number']) . '</small>
                    </td>
                    <td>' . formatDate($sub['submission_date'], 'd M Y H:i') . '</td>
                    <td>
                        <a href="../assets/images/submissions/' . htmlspecialchars($sub['file_path']) . '" 
                            class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="bi bi-download"></i> Download
                        </a>
                    </td>
                    <td>' . ($sub['marks'] ?? '<span class="text-muted">Not graded</span>') . '</td>
                    <td>
                        <span class="badge bg-' . ($sub['marks'] ? 'success' : 'warning') . '">
                            ' . htmlspecialchars($sub['status']) . '
                        </span>
                    </td>
                    <td>';

            if (!$sub['marks']) {
                echo '<button class="btn btn-sm btn-success" onclick="gradeSubmission(' . $sub['id'] . ')">
                        <i class="bi bi-pencil"></i> Grade
                        </button>';
            } else {
                echo '<button class="btn btn-sm btn-info" onclick="viewFeedback(' . $sub['id'] . ', \'' . addslashes($sub['feedback']) . '\')">
                        <i class="bi bi-eye"></i> View
                        </button>';
            }

            echo '</td></tr>';
        }

        echo '</tbody></table></div>';
    } else {
        echo '<div class="alert alert-info">No submissions yet</div>';
    }
} else {
    echo '<div class="alert alert-danger">Invalid assignment</div>';
}
