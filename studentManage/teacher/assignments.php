<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'My Assignments';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $class = sanitize($_POST['class']);
    $submission_date = sanitize($_POST['submission_date']);
    
    $fileName = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $upload = uploadFile($_FILES['file'], 'assignments', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);
        if ($upload['success']) {
            $fileName = $upload['filename'];
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO assignments (title, description, class, subject, file_path, submission_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $title, $description, $class, $teacher['subject'], $fileName, $submission_date, $user_id);
    
    if ($stmt->execute()) {
        setAlert('Assignment created successfully', 'success');
    } else {
        setAlert('Failed to create assignment', 'danger');
    }
    $stmt->close();
    header("Location: assignments.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_submission'])) {
    $submission_id = (int)$_POST['submission_id'];
    $marks = (int)$_POST['marks'];
    $feedback = sanitize($_POST['feedback']);
    
    $stmt = $conn->prepare("UPDATE assignment_submissions SET marks = ?, feedback = ?, status = 'Graded' WHERE id = ?");
    $stmt->bind_param("isi", $marks, $feedback, $submission_id);
    
    if ($stmt->execute()) {
        setAlert('Submission graded successfully', 'success');
    }
    $stmt->close();
    header("Location: assignments.php");
    exit();
}

$my_assignments = $conn->query("
    SELECT a.*, 
        COUNT(s.id) as total_submissions,
        SUM(CASE WHEN s.marks IS NULL THEN 1 ELSE 0 END) as pending_grading
    FROM assignments a
    LEFT JOIN assignment_submissions s ON a.id = s.assignment_id
    WHERE a.created_by = $user_id
    GROUP BY a.id
    ORDER BY a.created_at DESC
");

include '../includes/header.php';
include 'sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-file-earmark-text-fill me-2"></i>My Assignments</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAssignmentModal">
            <i class="bi bi-plus-circle me-2"></i>Create Assignment
        </button>
    </div>

    <?php displayAlert(); ?>

    <div class="row">
        <?php if ($my_assignments->num_rows > 0): ?>
            <?php while ($assignment = $my_assignments->fetch_assoc()): 
                $isPast = strtotime($assignment['submission_date']) < time();
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-<?php echo $isPast ? 'secondary' : 'primary'; ?> text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-journal-text me-2"></i>
                            <?php echo htmlspecialchars($assignment['title']); ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-2">
                            <i class="bi bi-book me-1"></i> <?php echo htmlspecialchars($assignment['subject']); ?> |
                            <i class="bi bi-mortarboard me-1"></i> Class <?php echo htmlspecialchars($assignment['class']); ?>
                        </p>
                        
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                        
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="bi bi-calendar-event me-1"></i>
                                Due: <?php echo formatDate($assignment['submission_date'], 'd M Y'); ?>
                            </small>
                        </div>
                        
                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <h5 class="text-primary mb-0"><?php echo $assignment['total_submissions']; ?></h5>
                                <small class="text-muted">Submissions</small>
                            </div>
                            <div class="col-6">
                                <h5 class="text-warning mb-0"><?php echo $assignment['pending_grading']; ?></h5>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                        
                        <?php if ($assignment['file_path']): ?>
                        <a href="../assets/images/assignments/<?php echo htmlspecialchars($assignment['file_path']); ?>" 
                            class="btn btn-sm btn-outline-primary mb-2" target="_blank">
                            <i class="bi bi-download me-1"></i>Download File
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <button class="btn btn-sm btn-success w-100" 
                                onclick="viewSubmissions(<?php echo $assignment['id']; ?>)">
                            <i class="bi bi-eye me-1"></i>View Submissions
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-2">No assignments created yet</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAssignmentModal">
                        <i class="bi bi-plus-circle me-2"></i>Create Your First Assignment
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<div class="modal fade" id="createAssignmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Create New Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Class <span class="text-danger">*</span></label>
                            <select class="form-select" name="class" required>
                                <option value="">Select Class</option>
                                <?php for($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Submission Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="submission_date" required 
                                min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($teacher['subject']); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Attachment (Optional)</label>
                        <input type="file" class="form-control" name="file" 
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <small class="text-muted">Max 5MB</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_assignment" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Create Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="submissionsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student Submissions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="submissionsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function viewSubmissions(assignmentId) {
    const modal = new bootstrap.Modal(document.getElementById('submissionsModal'));
    modal.show();
    
    fetch(`get_submissions.php?assignment_id=${assignmentId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('submissionsContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('submissionsContent').innerHTML = 
                '<div class="alert alert-danger">Failed to load submissions</div>';
        });
}
</script>