<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'My Assignments';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

$student_id = $student['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assignment'])) {
    $assignment_id = (int)$_POST['assignment_id'];

    $fileName = null;
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === 0) {
        $upload = uploadFile($_FILES['submission_file'], 'submissions', ['pdf', 'doc', 'docx', 'zip']);
        if ($upload['success']) {
            $fileName = $upload['filename'];
        } else {
            setAlert($upload['message'], 'danger');
            header("Location: assignments.php");
            exit();
        }
    }

    $check = $conn->prepare("SELECT id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
    $check->bind_param("ii", $assignment_id, $student_id);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();

    if ($exists) {
        $stmt = $conn->prepare("UPDATE assignment_submissions SET file_path = ?, submission_date = NOW(), status = 'Submitted' WHERE assignment_id = ? AND student_id = ?");
        $stmt->bind_param("sii", $fileName, $assignment_id, $student_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO assignment_submissions (assignment_id, student_id, file_path, status) VALUES (?, ?, ?, 'Submitted')");
        $stmt->bind_param("iis", $assignment_id, $student_id, $fileName);
    }

    if ($stmt->execute()) {
        setAlert('Assignment submitted successfully', 'success');
    } else {
        setAlert('Failed to submit assignment', 'danger');
    }
    $stmt->close();
    header("Location: assignments.php");
    exit();
}

$assignments = $conn->query("
    SELECT a.*, 
        s.file_path as submission_file,
        s.submission_date as submitted_at,
        s.marks,
        s.feedback,
        s.status as submission_status
    FROM assignments a
    LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = $student_id
    WHERE a.class = '{$student['class']}'
    ORDER BY a.submission_date DESC
");

include '../includes/header.php';
include 'sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-file-earmark-text-fill me-2"></i>My Assignments</h1>
    </div>

    <?php displayAlert(); ?>

    <div class="row">
        <?php if ($assignments->num_rows > 0): ?>
            <?php while ($assignment = $assignments->fetch_assoc()):
                $isPast = strtotime($assignment['submission_date']) < time();
                $isSubmitted = !empty($assignment['submission_file']);
                $daysLeft = ceil((strtotime($assignment['submission_date']) - time()) / 86400);
            ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 <?php echo $isPast && !$isSubmitted ? 'border-danger' : ''; ?>">
                        <div class="card-header bg-<?php echo $isSubmitted ? 'success' : ($isPast ? 'danger' : 'primary'); ?> text-white">
                            <h6 class="mb-0">
                                <i class="bi bi-journal-text me-2"></i>
                                <?php echo htmlspecialchars($assignment['title']); ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-2">
                                <i class="bi bi-book me-1"></i> <?php echo htmlspecialchars($assignment['subject']); ?>
                            </p>

                            <p class="card-text"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>

                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="bi bi-calendar-event me-1"></i>
                                    <strong>Due:</strong> <?php echo formatDate($assignment['submission_date'], 'd M Y'); ?>
                                    <?php if (!$isSubmitted && !$isPast): ?>
                                        <span class="badge bg-warning text-dark ms-2">
                                            <?php echo $daysLeft; ?> day(s) left
                                        </span>
                                    <?php endif; ?>
                                </small>
                            </div>

                            <?php if ($assignment['file_path']): ?>
                                <a href="../assets/images/assignments/<?php echo htmlspecialchars($assignment['file_path']); ?>"
                                    class="btn btn-sm btn-outline-primary mb-2" target="_blank">
                                    <i class="bi bi-download me-1"></i>Download Instructions
                                </a>
                            <?php endif; ?>

                            <?php if ($isSubmitted): ?>
                                <div class="alert alert-success mb-2">
                                    <strong><i class="bi bi-check-circle-fill me-1"></i>Submitted</strong>
                                    <br>
                                    <small>on <?php echo formatDate($assignment['submitted_at'], 'd M Y'); ?></small>

                                    <?php if ($assignment['marks']): ?>
                                        <hr class="my-2">
                                        <strong>Marks:</strong> <?php echo $assignment['marks']; ?>/100
                                        <?php if ($assignment['feedback']): ?>
                                            <br><strong>Feedback:</strong> <?php echo htmlspecialchars($assignment['feedback']); ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <a href="../assets/images/submissions/<?php echo htmlspecialchars($assignment['submission_file']); ?>"
                                    class="btn btn-sm btn-outline-success" target="_blank">
                                    <i class="bi bi-file-earmark-check me-1"></i>View Submission
                                </a>
                            <?php elseif ($isPast): ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                    <strong>Missed</strong> - Deadline passed
                                </div>
                            <?php else: ?>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#submitModal<?php echo $assignment['id']; ?>">
                                    <i class="bi bi-upload me-1"></i>Submit Assignment
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent">
                            <small class="text-muted">
                                Posted: <?php echo formatDate($assignment['created_at'], 'd M Y'); ?>
                            </small>
                        </div>
                    </div>
                </div>

                <?php if (!$isSubmitted && !$isPast): ?>
                    <div class="modal fade" id="submitModal<?php echo $assignment['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Submit Assignment</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <div class="modal-body">
                                        <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">

                                        <div class="alert alert-info">
                                            <strong><?php echo htmlspecialchars($assignment['title']); ?></strong>
                                            <br>Due: <?php echo formatDate($assignment['submission_date'], 'd M Y'); ?>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Upload File <span class="text-danger">*</span></label>
                                            <input type="file" class="form-control" name="submission_file" required
                                                accept=".pdf,.doc,.docx,.zip">
                                            <small class="text-muted">Allowed: PDF, DOC, DOCX, ZIP (Max 5MB)</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="submit_assignment" class="btn btn-primary">
                                            <i class="bi bi-upload me-2"></i>Submit
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-2">No assignments assigned yet</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>