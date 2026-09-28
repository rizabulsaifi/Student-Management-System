<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'Teacher Dashboard';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$teacher) {
    setAlert('Teacher profile not found', 'danger');
    header("Location: ../logout.php");
    exit();
}

$teacher_id = $teacher['id'];

$total_students = $conn->query("SELECT COUNT(DISTINCT id) as total FROM students WHERE status = 'Active'")->fetch_assoc()['total'];

$my_classes = ['10-A', '10-B', '9-A'];

$today = date('Y-m-d');
$today_attendance = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE date = '$today' AND marked_by = $user_id")->fetch_assoc()['total'];

$pending_grading = $conn->query("
    SELECT COUNT(*) as total 
    FROM assignment_submissions s
    JOIN assignments a ON s.assignment_id = a.id
    WHERE a.created_by = $user_id AND s.marks IS NULL
")->fetch_assoc()['total'];

$recent_submissions = $conn->query("
    SELECT s.*, st.name as student_name, st.roll_number, a.title as assignment_title
    FROM assignment_submissions s
    JOIN students st ON s.student_id = st.id
    JOIN assignments a ON s.assignment_id = a.id
    WHERE a.created_by = $user_id
    ORDER BY s.submission_date DESC
    LIMIT 5
");

$my_assignments = $conn->query("
    SELECT * FROM assignments 
    WHERE created_by = $user_id 
    ORDER BY created_at DESC 
    LIMIT 5
");

include '../includes/header.php';
?>

<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <img src="../assets/images/uploads/<?php echo htmlspecialchars($teacher['image']); ?>"
                class="rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover;" alt="Profile">
            <h6 class="mb-0"><?php echo htmlspecialchars($teacher['name']); ?></h6>
            <small class="text-muted"><?php echo htmlspecialchars($teacher['subject']); ?> Teacher</small>
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
                <a class="nav-link" href="students.php">
                    <i class="bi bi-people-fill me-2"></i>My Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="attendance.php">
                    <i class="bi bi-calendar-check-fill me-2"></i>Mark Attendance
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="results.php">
                    <i class="bi bi-clipboard-data-fill me-2"></i>Enter Results
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="assignments.php">
                    <i class="bi bi-file-earmark-text-fill me-2"></i>Assignments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="timetable.php">
                    <i class="bi bi-table me-2"></i>My Schedule
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
            Welcome, <?php echo htmlspecialchars($teacher['name']); ?>!
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
                            <div class="stats-label">My Students</div>
                            <div class="stats-number text-primary"><?php echo $total_students; ?></div>
                        </div>
                        <div class="text-primary" style="font-size: 3rem; opacity: 0.2;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="students.php" class="text-primary text-decoration-none small">
                            View all <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-label">Today's Attendance</div>
                            <div class="stats-number text-success"><?php echo $today_attendance; ?></div>
                        </div>
                        <div class="text-success" style="font-size: 3rem; opacity: 0.2;">
                            <i class="bi bi-calendar-check-fill"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="attendance.php" class="text-success text-decoration-none small">
                            Mark now <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-label">Pending Grading</div>
                            <div class="stats-number text-warning"><?php echo $pending_grading; ?></div>
                        </div>
                        <div class="text-warning" style="font-size: 3rem; opacity: 0.2;">
                            <i class="bi bi-clipboard-check"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="assignments.php" class="text-warning text-decoration-none small">
                            Grade now <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-label">My Classes</div>
                            <div class="stats-number text-danger"><?php echo count($my_classes); ?></div>
                        </div>
                        <div class="text-danger" style="font-size: 3rem; opacity: 0.2;">
                            <i class="bi bi-journal-text"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted"><?php echo implode(', ', $my_classes); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning-fill me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="attendance.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-calendar-check d-block mb-2" style="font-size: 2rem;"></i>
                                Mark Attendance
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="results.php" class="btn btn-outline-success w-100">
                                <i class="bi bi-clipboard-data d-block mb-2" style="font-size: 2rem;"></i>
                                Enter Results
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="assignments.php" class="btn btn-outline-warning w-100">
                                <i class="bi bi-file-earmark-plus d-block mb-2" style="font-size: 2rem;"></i>
                                Create Assignment
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="students.php" class="btn btn-outline-info w-100">
                                <i class="bi bi-people d-block mb-2" style="font-size: 2rem;"></i>
                                View Students
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-upload me-2"></i>Recent Submissions</span>
                    <a href="assignments.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if ($recent_submissions->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($submission = $recent_submissions->fetch_assoc()): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($submission['assignment_title']); ?></h6>
                                            <p class="mb-1 text-muted small">
                                                By: <?php echo htmlspecialchars($submission['student_name']); ?>
                                                (<?php echo htmlspecialchars($submission['roll_number']); ?>)
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">
                                                <?php echo formatDate($submission['submission_date'], 'd M'); ?>
                                            </small>
                                            <?php if (!$submission['marks']): ?>
                                                <span class="badge bg-warning d-block mt-1">Pending</span>
                                            <?php else: ?>
                                                <span class="badge bg-success d-block mt-1">Graded</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">No recent submissions</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-file-earmark-text me-2"></i>My Assignments</span>
                    <a href="assignments.php" class="btn btn-sm btn-primary">Create New</a>
                </div>
                <div class="card-body">
                    <?php if ($my_assignments->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($assignment = $my_assignments->fetch_assoc()):
                                $daysLeft = ceil((strtotime($assignment['submission_date']) - time()) / 86400);
                            ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($assignment['title']); ?></h6>
                                            <p class="mb-1 text-muted small">
                                                Class <?php echo htmlspecialchars($assignment['class']); ?> -
                                                <?php echo htmlspecialchars($assignment['subject']); ?>
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">
                                                Due: <?php echo formatDate($assignment['submission_date'], 'd M'); ?>
                                            </small>
                                            <?php if ($daysLeft > 0): ?>
                                                <span class="badge bg-info d-block mt-1"><?php echo $daysLeft; ?> days</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary d-block mt-1">Closed</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">No assignments created yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>