<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'Student Dashboard';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    setAlert('Student profile not found', 'danger');
    header("Location: ../logout.php");
    exit();
}

$student_id = $student['id'];

$attendance_stats = $conn->query("
    SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_days
    FROM attendance 
    WHERE student_id = $student_id
")->fetch_assoc();

$attendance_percentage = $attendance_stats['total_days'] > 0 
    ? round(($attendance_stats['present_days'] / $attendance_stats['total_days']) * 100, 2) 
    : 0;

$recent_results = $conn->query("
    SELECT * FROM results 
    WHERE student_id = $student_id 
    ORDER BY exam_date DESC 
    LIMIT 5
");

$fee_status = $conn->query("
    SELECT * FROM fees 
    WHERE student_id = $student_id 
    ORDER BY created_at DESC 
    LIMIT 5
");

$total_pending = $conn->query("
    SELECT SUM(amount - paid_amount) as pending 
    FROM fees 
    WHERE student_id = $student_id AND status != 'Paid'
")->fetch_assoc()['pending'] ?? 0;

$upcoming_assignments = $conn->query("
    SELECT * FROM assignments 
    WHERE class = '{$student['class']}' 
    AND submission_date >= CURDATE() 
    ORDER BY submission_date ASC 
    LIMIT 5
");

include '../includes/header.php';
?>

<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <img src="../assets/images/uploads/<?php echo htmlspecialchars($student['image']); ?>" 
                class="rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover;" alt="Profile">
            <h6 class="mb-0"><?php echo htmlspecialchars($student['name']); ?></h6>
            <small class="text-muted"><?php echo htmlspecialchars($student['roll_number']); ?></small>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="profile.php">
                    <i class="bi bi-person-fill me-2"></i>My Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="attendance.php">
                    <i class="bi bi-calendar-check-fill me-2"></i>My Attendance
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="results.php">
                    <i class="bi bi-clipboard-data-fill me-2"></i>My Results
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="fees.php">
                    <i class="bi bi-currency-dollar me-2"></i>Fee Details
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="assignments.php">
                    <i class="bi bi-file-earmark-text-fill me-2"></i>Assignments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="timetable.php">
                    <i class="bi bi-table me-2"></i>Time Table
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-danger" href="../logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </li>
        </ul>
    </div>
</nav>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="bi bi-speedometer2 me-2"></i>
            Welcome, <?php echo htmlspecialchars($student['name']); ?>!
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-calendar-event me-1"></i><?php echo date('d M Y'); ?>
                </button>
            </div>
        </div>
    </div>

    <?php displayAlert(); ?>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-label">Attendance</div>
                            <div class="stats-number text-success"><?php echo $attendance_percentage; ?>%</div>
                        </div>
                        <div class="text-success" style="font-size: 3rem; opacity: 0.2;">
                            <i class="bi bi-calendar-check-fill"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <?php echo $attendance_stats['present_days']; ?>/<?php echo $attendance_stats['total_days']; ?> days present
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-label">Pending Fees</div>
                            <div class="stats-number text-warning">₹<?php echo number_format($total_pending, 0); ?></div>
                        </div>
                        <div class="text-warning" style="font-size: 3rem; opacity: 0.2;">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="fees.php" class="text-warning text-decoration-none small">
                            View details <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-label">Class Rank</div>
                            <div class="stats-number text-primary">#5</div>
                        </div>
                        <div class="text-primary" style="font-size: 3rem; opacity: 0.2;">
                            <i class="bi bi-trophy-fill"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">Out of 45 students</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-label">Assignments</div>
                            <div class="stats-number text-danger"><?php echo $upcoming_assignments->num_rows; ?></div>
                        </div>
                        <div class="text-danger" style="font-size: 3rem; opacity: 0.2;">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">Pending submissions</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clipboard-data-fill me-2"></i>Recent Results</span>
                    <a href="results.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Exam</th>
                                    <th>Marks</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_results->num_rows > 0): ?>
                                    <?php while ($result = $recent_results->fetch_assoc()): 
                                        $percentage = ($result['obtained_marks'] / $result['total_marks']) * 100;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['subject']); ?></td>
                                        <td><span class="badge bg-info"><?php echo htmlspecialchars($result['exam_type']); ?></span></td>
                                        <td>
                                            <strong><?php echo $result['obtained_marks']; ?></strong>/<?php echo $result['total_marks']; ?>
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
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">No results available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-file-earmark-text-fill me-2"></i>Upcoming Assignments</span>
                    <a href="assignments.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if ($upcoming_assignments->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($assignment = $upcoming_assignments->fetch_assoc()): 
                                $daysLeft = ceil((strtotime($assignment['submission_date']) - time()) / 86400);
                            ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($assignment['title']); ?></h6>
                                        <p class="mb-1 text-muted small">
                                            <i class="bi bi-book me-1"></i><?php echo htmlspecialchars($assignment['subject']); ?>
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            <?php echo formatDate($assignment['submission_date'], 'd M'); ?>
                                        </small>
                                        <?php if ($daysLeft <= 2): ?>
                                            <span class="badge bg-danger d-block mt-1">Due Soon!</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">No pending assignments</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-currency-dollar me-2"></i>Fee Status</span>
                    <a href="fees.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Fee Type</th>
                                    <th>Amount</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($fee_status->num_rows > 0): ?>
                                    <?php while ($fee = $fee_status->fetch_assoc()): 
                                        $balance = $fee['amount'] - $fee['paid_amount'];
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($fee['fee_type']); ?></td>
                                        <td>₹<?php echo number_format($fee['amount'], 2); ?></td>
                                        <td>₹<?php echo number_format($fee['paid_amount'], 2); ?></td>
                                        <td>
                                            <strong class="<?php echo $balance > 0 ? 'text-danger' : 'text-success'; ?>">
                                                ₹<?php echo number_format($balance, 2); ?>
                                            </strong>
                                        </td>
                                        <td><?php echo formatDate($fee['due_date']); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = 'secondary';
                                            if ($fee['status'] === 'Paid') $statusClass = 'success';
                                            elseif ($fee['status'] === 'Partial') $statusClass = 'warning';
                                            elseif ($fee['status'] === 'Overdue') $statusClass = 'danger';
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php echo $fee['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">No fee records</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>