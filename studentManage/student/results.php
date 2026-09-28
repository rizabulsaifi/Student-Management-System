<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'My Results';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

$student_id = $student['id'];

$exam_filter = isset($_GET['exam_type']) ? sanitize($_GET['exam_type']) : '';

$whereClause = "WHERE student_id = $student_id";
if (!empty($exam_filter)) {
    $whereClause .= " AND exam_type = '$exam_filter'";
}

$query = "SELECT * FROM results $whereClause ORDER BY exam_date DESC, subject";
$results = $conn->query($query);

$overall_stats = $conn->query("
    SELECT 
        AVG((obtained_marks / total_marks) * 100) as avg_percentage,
        COUNT(DISTINCT subject) as total_subjects,
        COUNT(*) as total_exams
    FROM results 
    WHERE student_id = $student_id
")->fetch_assoc();

$subject_performance = $conn->query("
    SELECT 
        subject,
        AVG((obtained_marks / total_marks) * 100) as avg_percentage,
        COUNT(*) as exam_count
    FROM results 
    WHERE student_id = $student_id 
    GROUP BY subject
");

include '../includes/header.php';
include 'sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-clipboard-data-fill me-2"></i>My Results</h1>
        <button onclick="window.print()" class="btn btn-outline-primary">
            <i class="bi bi-printer me-2"></i>Print Report
        </button>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-label">Overall Average</div>
                    <div class="stats-number text-primary">
                        <?php echo number_format($overall_stats['avg_percentage'] ?? 0, 2); ?>%
                    </div>
                    <small class="text-muted">Across all exams</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stats-card success">
                <div class="card-body">
                    <div class="stats-label">Total Subjects</div>
                    <div class="stats-number text-success"><?php echo $overall_stats['total_subjects']; ?></div>
                    <small class="text-muted">subjects</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stats-card warning">
                <div class="card-body">
                    <div class="stats-label">Total Exams</div>
                    <div class="stats-number text-warning"><?php echo $overall_stats['total_exams']; ?></div>
                    <small class="text-muted">exams taken</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-bar-chart-fill me-2"></i>Subject-wise Performance
        </div>
        <div class="card-body">
            <div class="row">
                <?php while ($subject = $subject_performance->fetch_assoc()): 
                    $percentage = $subject['avg_percentage'];
                    $barColor = 'success';
                    if ($percentage < 40) $barColor = 'danger';
                    elseif ($percentage < 60) $barColor = 'warning';
                    elseif ($percentage < 75) $barColor = 'info';
                ?>
                <div class="col-md-6 mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span><?php echo htmlspecialchars($subject['subject']); ?></span>
                        <strong><?php echo number_format($percentage, 2); ?>%</strong>
                    </div>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar bg-<?php echo $barColor; ?>" role="progressbar" 
                            style="width: <?php echo $percentage; ?>%" 
                            aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                            <?php echo calculateGrade($percentage); ?>
                        </div>
                    </div>
                    <small class="text-muted"><?php echo $subject['exam_count']; ?> exam(s)</small>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <select class="form-select" name="exam_type">
                        <option value="">All Exams</option>
                        <option value="Mid-Term" <?php echo $exam_filter === 'Mid-Term' ? 'selected' : ''; ?>>Mid-Term</option>
                        <option value="Final" <?php echo $exam_filter === 'Final' ? 'selected' : ''; ?>>Final</option>
                        <option value="Unit Test" <?php echo $exam_filter === 'Unit Test' ? 'selected' : ''; ?>>Unit Test</option>
                        <option value="Quiz" <?php echo $exam_filter === 'Quiz' ? 'selected' : ''; ?>>Quiz</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel-fill me-1"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-journal-text me-2"></i>Detailed Results
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Subject</th>
                            <th>Exam Type</th>
                            <th>Total Marks</th>
                            <th>Obtained</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($results->num_rows > 0): ?>
                            <?php while ($result = $results->fetch_assoc()): 
                                $percentage = ($result['obtained_marks'] / $result['total_marks']) * 100;
                            ?>
                            <tr>
                                <td><?php echo formatDate($result['exam_date'], 'd M Y'); ?></td>
                                <td><strong><?php echo htmlspecialchars($result['subject']); ?></strong></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($result['exam_type']); ?></span></td>
                                <td><?php echo $result['total_marks']; ?></td>
                                <td><strong><?php echo $result['obtained_marks']; ?></strong></td>
                                <td><?php echo number_format($percentage, 2); ?>%</td>
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
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: #CBD5E1;"></i>
                                    <p class="text-muted mt-2">No results available</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>