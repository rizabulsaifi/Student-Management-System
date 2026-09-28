<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'Dashboard';

$stats = [];

$result = $conn->query("SELECT COUNT(*) as total FROM students WHERE status = 'Active'");
$stats['students'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM teachers WHERE status = 'Active'");
$stats['teachers'] = $result->fetch_assoc()['total'];

$today = date('Y-m-d');
$result = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE date = '$today' AND status = 'Present'");
$stats['attendance'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM fees WHERE status IN ('Pending', 'Partial')");
$stats['pending_fees'] = $result->fetch_assoc()['total'];

$recent_students = $conn->query("SELECT * FROM students ORDER BY created_at DESC LIMIT 5");

$recent_attendance = $conn->query("
    SELECT a.*, s.name, s.roll_number, s.class, s.section 
    FROM attendance a 
    JOIN students s ON a.student_id = s.id 
    ORDER BY a.created_at DESC 
    LIMIT 5
");

$upcoming_assignments = $conn->query("
    SELECT * FROM assignments 
    WHERE submission_date >= CURDATE() 
    ORDER BY submission_date ASC 
    LIMIT 5
");

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
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
                            <div class="stats-label"><b> Total Students </b></div>
                            <div class="stats-number"><?php echo $stats['students']; ?></div>
                        </div>
                        <div class="text-primary" style="font-size: 3rem; opacity: 0.2;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="students.php" class="text-primary text-decoration-none">
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
                            <div class="stats-label"><b> Total Teachers </b></div>
                            <div class="stats-number"><?php echo $stats['teachers']; ?></div>
                        </div>
                        <div class="text-success" style="font-size: 3rem; opacity: 0.2;">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="teachers.php" class="text-success text-decoration-none">
                            View all <i class="bi bi-arrow-right"></i>
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
                            <div class="stats-label"><b> Today's Attendance</b></div>
                            <div class="stats-number"><?php echo $stats['attendance']; ?></div>
                        </div>
                        <div class="text-warning" style="font-size: 3rem; opacity: 0.2;">
                            <i class="bi bi-calendar-check-fill"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="attendance.php" class="text-warning text-decoration-none">
                            Mark attendance <i class="bi bi-arrow-right"></i>
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
                            <div class="stats-label"><b> Pending Fees </b></div>
                            <div class="stats-number"><?php echo $stats['pending_fees']; ?></div>
                        </div>
                        <div class="text-danger" style="font-size: 3rem; opacity: 0.2;">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="fees.php" class="text-danger text-decoration-none">
                            View details <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people-fill me-2"></i>Recent Students</span>
                    <a href="students.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Roll No</th>
                                    <th>Name</th>
                                    <th>Class</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($student = $recent_students->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                    <td>
                                        <img src="../assets/images/uploads/<?php echo htmlspecialchars($student['image']); ?>" 
                                            class="avatar me-2" alt="">
                                        <?php echo htmlspecialchars($student['name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['class'] . '-' . $student['section']); ?></td>
                                    <td>
                                        <span class="badge bg-success">
                                            <?php echo htmlspecialchars($student['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar-check-fill me-2"></i>Recent Attendance</span>
                    <a href="attendance.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($attendance = $recent_attendance->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($attendance['name']); ?>
                                        <small class="text-muted d-block">
                                            <?php echo htmlspecialchars($attendance['roll_number']); ?>
                                        </small>
                                    </td>
                                    <td><?php echo formatDate($attendance['date']); ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = 'success';
                                        if ($attendance['status'] === 'Absent') $badgeClass = 'danger';
                                        if ($attendance['status'] === 'Late') $badgeClass = 'warning';
                                        ?>
                                        <span class="badge bg-<?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($attendance['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-file-earmark-text-fill me-2"></i>Upcoming Assignments</span>
                    <a href="assignments.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Due Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($assignment = $upcoming_assignments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['class']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['subject']); ?></td>
                                    <td>
                                        <i class="bi bi-calendar-event me-1"></i>
                                        <?php echo formatDate($assignment['submission_date']); ?>
                                    </td>
                                    <td>
                                        <a href="assignments.php?id=<?php echo $assignment['id']; ?>" 
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>