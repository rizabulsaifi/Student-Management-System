<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Results Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_result'])) {
    $student_id = (int)$_POST['student_id'];
    $subject = sanitize($_POST['subject']);
    $exam_type = sanitize($_POST['exam_type']);
    $total_marks = (int)$_POST['total_marks'];
    $obtained_marks = (int)$_POST['obtained_marks'];
    $exam_date = sanitize($_POST['exam_date']);

    $percentage = ($obtained_marks / $total_marks) * 100;
    $grade = calculateGrade($percentage);

    $stmt = $conn->prepare("INSERT INTO results (student_id, subject, exam_type, total_marks, obtained_marks, grade, exam_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issiiiss", $student_id, $subject, $exam_type, $total_marks, $obtained_marks, $grade, $exam_date);

    if ($stmt->execute()) {
        setAlert('Result added successfully', 'success');
    } else {
        setAlert('Failed to add result', 'danger');
    }
    $stmt->close();
    header("Location: results.php");
    exit();
}

$class_filter = isset($_GET['class']) ? sanitize($_GET['class']) : '';
$exam_filter = isset($_GET['exam_type']) ? sanitize($_GET['exam_type']) : '';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 15;

$whereClause = "WHERE 1=1";
if (!empty($class_filter)) {
    $whereClause .= " AND s.class = '$class_filter'";
}
if (!empty($exam_filter)) {
    $whereClause .= " AND r.exam_type = '$exam_filter'";
}

$countQuery = "SELECT COUNT(*) as total FROM results r JOIN students s ON r.student_id = s.id $whereClause";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];

$pagination = paginate($totalRecords, $recordsPerPage, $page);
$offset = $pagination['offset'];

$query = "SELECT r.*, s.name, s.roll_number, s.class, s.section 
        FROM results r 
        JOIN students s ON r.student_id = s.id 
        $whereClause 
        ORDER BY r.exam_date DESC, s.class, s.roll_number 
        LIMIT $offset, $recordsPerPage";
$results = $conn->query($query);

$students = $conn->query("SELECT id, name, roll_number, class, section FROM students WHERE status = 'Active' ORDER BY class, roll_number");

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-clipboard-data-fill me-2"></i>Results Management</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addResultModal">
            <i class="bi bi-plus-circle me-2"></i>Add Result
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
                <div class="col-md-4">
                    <select class="form-select" name="exam_type">
                        <option value="">All Exams</option>
                        <option value="Mid-Term" <?php echo $exam_filter === 'Mid-Term' ? 'selected' : ''; ?>>Mid-Term</option>
                        <option value="Final" <?php echo $exam_filter === 'Final' ? 'selected' : ''; ?>>Final</option>
                        <option value="Unit Test" <?php echo $exam_filter === 'Unit Test' ? 'selected' : ''; ?>>Unit Test</option>
                        <option value="Quiz" <?php echo $exam_filter === 'Quiz' ? 'selected' : ''; ?>>Quiz</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-funnel-fill me-1"></i>Filter
                    </button>
                    <a href="results.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Roll No</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Exam Type</th>
                            <th>Marks</th>
                            <th>Grade</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($results->num_rows > 0): ?>
                            <?php while ($result = $results->fetch_assoc()):
                                $percentage = ($result['obtained_marks'] / $result['total_marks']) * 100;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($result['roll_number']); ?></td>
                                    <td><?php echo htmlspecialchars($result['name']); ?></td>
                                    <td><?php echo htmlspecialchars($result['class'] . '-' . $result['section']); ?></td>
                                    <td><?php echo htmlspecialchars($result['subject']); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($result['exam_type']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo $result['obtained_marks']; ?></strong> / <?php echo $result['total_marks']; ?>
                                        <small class="text-muted d-block"><?php echo number_format($percentage, 2); ?>%</small>
                                    </td>
                                    <td>
                                        <?php
                                        $gradeClass = 'success';
                                        if ($result['grade'] === 'F') $gradeClass = 'danger';
                                        elseif (in_array($result['grade'], ['D', 'C'])) $gradeClass = 'warning';
                                        ?>
                                        <span class="badge bg-<?php echo $gradeClass; ?>">
                                            <?php echo $result['grade']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($result['exam_date']); ?></td>
                                    <td>
                                        <a href="delete_result.php?id=<?php echo $result['id']; ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Delete this result?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: #CBD5E1;"></i>
                                    <p class="text-muted mt-2">No results found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php echo renderPagination($pagination['total_pages'], $pagination['current_page'], 'results.php'); ?>
</main>

<div class="modal fade" id="addResultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Result</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Student <span class="text-danger">*</span></label>
                            <select class="form-select" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php while ($student = $students->fetch_assoc()): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo $student['roll_number'] . ' - ' . $student['name'] .
                                            ' (Class ' . $student['class'] . '-' . $student['section'] . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="subject" required placeholder="e.g., Mathematics">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Exam Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="exam_type" required>
                                <option value="">Select Exam Type</option>
                                <option value="Mid-Term">Mid-Term</option>
                                <option value="Final">Final</option>
                                <option value="Unit Test">Unit Test</option>
                                <option value="Quiz">Quiz</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Exam Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="exam_date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Total Marks <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="total_marks" required min="1" value="100">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Obtained Marks <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="obtained_marks" required min="0" id="obtainedMarks">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_result" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Add Result
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>