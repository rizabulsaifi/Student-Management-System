<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Assignments Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_assignment'])) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $class = sanitize($_POST['class']);
    $subject = sanitize($_POST['subject']);
    $submission_date = sanitize($_POST['submission_date']);

    $fileName = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $upload = uploadFile($_FILES['file'], 'assignments', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);
        if ($upload['success']) {
            $fileName = $upload['filename'];
        }
    }

    $stmt = $conn->prepare("INSERT INTO assignments (title, description, class, subject, file_path, submission_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $title, $description, $class, $subject, $fileName, $submission_date, $_SESSION['user_id']);

    if ($stmt->execute()) {
        setAlert('Assignment created successfully', 'success');
    } else {
        setAlert('Failed to create assignment', 'danger');
    }
    $stmt->close();
    header("Location: assignments.php");
    exit();
}

$class_filter = isset($_GET['class']) ? sanitize($_GET['class']) : '';

$whereClause = "WHERE 1=1";
if (!empty($class_filter)) {
    $whereClause .= " AND class = '$class_filter'";
}

$query = "SELECT a.*, u.name as created_by_name 
        FROM assignments a 
        LEFT JOIN users u ON a.created_by = u.id 
        $whereClause 
        ORDER BY a.submission_date DESC";
$assignments = $conn->query($query);

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-file-earmark-text-fill me-2"></i>Assignments Management</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAssignmentModal">
            <i class="bi bi-plus-circle me-2"></i>Create Assignment
        </button>
    </div>

    <?php displayAlert(); ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <select class="form-select" name="class">
                        <option value="">All Classes</option>
                        <?php
                        $classes = $conn->query("SELECT DISTINCT class FROM students ORDER BY class");
                        while ($class = $classes->fetch_assoc()):
                        ?>
                            <option value="<?php echo $class['class']; ?>"
                                <?php echo $class_filter == $class['class'] ? 'selected' : ''; ?>>
                                Class <?php echo $class['class']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-funnel-fill me-1"></i>Filter
                    </button>
                    <a href="assignments.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <?php if ($assignments->num_rows > 0): ?>
            <?php while ($assignment = $assignments->fetch_assoc()):
                $isPast = strtotime($assignment['submission_date']) < time();
            ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
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
                                    <?php if ($isPast): ?>
                                        <span class="badge bg-danger ms-2">Overdue</span>
                                    <?php else: ?>
                                        <span class="badge bg-success ms-2">Active</span>
                                    <?php endif; ?>
                                </small>
                            </div>

                            <?php if ($assignment['file_path']): ?>
                                <a href="../assets/images/assignments/<?php echo htmlspecialchars($assignment['file_path']); ?>"
                                    class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-download me-1"></i>Download File
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent">
                            <small class="text-muted">
                                Created by <?php echo htmlspecialchars($assignment['created_by_name'] ?? 'Admin'); ?>
                            </small>
                            <div class="float-end">
                                <a href="delete_assignment.php?id=<?php echo $assignment['id']; ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Delete this assignment?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-2">No assignments found</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<div class="modal fade" id="addAssignmentModal" tabindex="-1">
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
                        <input type="text" class="form-control" name="title" required
                            placeholder="e.g., Mathematics Chapter 5 Assignment">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"
                            placeholder="Assignment details and instructions"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Class <span class="text-danger">*</span></label>
                            <select class="form-select" name="class" required>
                                <option value="">Select Class</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="subject" required
                                placeholder="e.g., Mathematics">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Submission Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="submission_date" required
                            min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Attachment File (Optional)</label>
                        <input type="file" class="form-control" name="file"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <small class="text-muted">Allowed: PDF, DOC, DOCX, Images (Max 5MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_assignment" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Create Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>