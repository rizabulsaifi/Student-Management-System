<?php
if (!isset($student)) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
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
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                    <i class="bi bi-person-fill me-2"></i>My Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>" href="attendance.php">
                    <i class="bi bi-calendar-check-fill me-2"></i>My Attendance
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'results.php' ? 'active' : ''; ?>" href="results.php">
                    <i class="bi bi-clipboard-data-fill me-2"></i>My Results
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'fees.php' ? 'active' : ''; ?>" href="fees.php">
                    <i class="bi bi-currency-dollar me-2"></i>Fee Details
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'assignments.php' ? 'active' : ''; ?>" href="assignments.php">
                    <i class="bi bi-file-earmark-text-fill me-2"></i>Assignments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'timetable.php' ? 'active' : ''; ?>" href="timetable.php">
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