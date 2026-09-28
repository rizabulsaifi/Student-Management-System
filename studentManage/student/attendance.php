<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'My Attendance';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

$student_id = $student['id'];

$selected_month = isset($_GET['month']) ? sanitize($_GET['month']) : date('Y-m');

$query = "SELECT * FROM attendance 
        WHERE student_id = $student_id 
        AND DATE_FORMAT(date, '%Y-%m') = '$selected_month' 
        ORDER BY date DESC";
$attendance_records = $conn->query($query);

$stats = $conn->query("
    SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN status = 'Half-Day' THEN 1 ELSE 0 END) as half_day
    FROM attendance 
    WHERE student_id = $student_id 
    AND DATE_FORMAT(date, '%Y-%m') = '$selected_month'
")->fetch_assoc();

$attendance_percentage = $stats['total_days'] > 0 
    ? round(($stats['present'] / $stats['total_days']) * 100, 2) 
    : 0;

include '../includes/header.php';
include 'sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-calendar-check-fill me-2"></i>My Attendance</h1>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Select Month</label>
                    <input type="month" class="form-control" name="month" 
                        value="<?php echo $selected_month; ?>" max="<?php echo date('Y-m'); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel-fill me-1"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-label">Attendance Rate</div>
                    <div class="stats-number text-<?php echo $attendance_percentage >= 75 ? 'success' : 'danger'; ?>">
                        <?php echo $attendance_percentage; ?>%
                    </div>
                    <small class="text-muted">
                        <?php echo $attendance_percentage >= 75 ? 'Good' : 'Below Required'; ?>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card success">
                <div class="card-body">
                    <div class="stats-label">Present</div>
                    <div class="stats-number text-success"><?php echo $stats['present']; ?></div>
                    <small class="text-muted">days</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card danger">
                <div class="card-body">
                    <div class="stats-label">Absent</div>
                    <div class="stats-number text-danger"><?php echo $stats['absent']; ?></div>
                    <small class="text-muted">days</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card warning">
                <div class="card-body">
                    <div class="stats-label">Late/Half-Day</div>
                    <div class="stats-number text-warning"><?php echo $stats['late'] + $stats['half_day']; ?></div>
                    <small class="text-muted">days</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-calendar3 me-2"></i>Attendance Records - <?php echo date('F Y', strtotime($selected_month . '-01')); ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($attendance_records->num_rows > 0): ?>
                            <?php while ($record = $attendance_records->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo formatDate($record['date'], 'd M Y'); ?></td>
                                <td><?php echo date('l', strtotime($record['date'])); ?></td>
                                <td>
                                    <?php
                                    $statusClass = 'success';
                                    if ($record['status'] === 'Absent') $statusClass = 'danger';
                                    elseif ($record['status'] === 'Late') $statusClass = 'warning';
                                    elseif ($record['status'] === 'Half-Day') $statusClass = 'info';
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo $record['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: #CBD5E1;"></i>
                                    <p class="text-muted mt-2">No attendance records for this month</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($attendance_percentage < 75): ?>
    <div class="alert alert-warning mt-4">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Warning:</strong> Your attendance is below the required 75%. Please maintain regular attendance.
    </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>