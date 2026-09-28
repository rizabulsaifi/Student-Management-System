<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'Mark Attendance';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $date = sanitize($_POST['attendance_date']);
    $attendance_data = $_POST['attendance'] ?? [];

    $success_count = 0;
    foreach ($attendance_data as $student_id => $status) {
        $student_id = (int)$student_id;
        $status = sanitize($status);

        $check = $conn->prepare("SELECT id FROM attendance WHERE student_id = ? AND date = ?");
        $check->bind_param("is", $student_id, $date);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE attendance SET status = ?, marked_by = ? WHERE student_id = ? AND date = ?");
            $stmt->bind_param("siis", $status, $user_id, $student_id, $date);
        } else {
            $stmt = $conn->prepare("INSERT INTO attendance (student_id, date, status, marked_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $student_id, $date, $status, $user_id);
        }

        if ($stmt->execute()) {
            $success_count++;
        }
        $stmt->close();
    }

    setAlert("Attendance marked for $success_count students", 'success');
    header("Location: attendance.php");
    exit();
}

$selected_class = isset($_GET['class']) ? sanitize($_GET['class']) : '';
$selected_section = isset($_GET['section']) ? sanitize($_GET['section']) : '';
$selected_date = isset($_GET['date']) ? sanitize($_GET['date']) : date('Y-m-d');

$students = null;
if (!empty($selected_class) && !empty($selected_section)) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE class = ? AND section = ? AND status = 'Active' ORDER BY roll_number");
    $stmt->bind_param("ss", $selected_class, $selected_section);
    $stmt->execute();
    $students = $stmt->get_result();
}

$existing_attendance = [];
if ($students && $students->num_rows > 0) {
    $student_ids = [];
    while ($student = $students->fetch_assoc()) {
        $student_ids[] = $student['id'];
    }
    $students->data_seek(0);

    if (!empty($student_ids)) {
        $ids_placeholder = implode(',', array_fill(0, count($student_ids), '?'));
        $types = str_repeat('i', count($student_ids));

        $query = "SELECT student_id, status FROM attendance WHERE date = ? AND student_id IN ($ids_placeholder)";
        $stmt = $conn->prepare($query);
        $params = array_merge([$selected_date], $student_ids);
        $stmt->bind_param("s" . $types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $existing_attendance[$row['student_id']] = $row['status'];
        }
    }
}

include '../includes/header.php';
include 'sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-calendar-check-fill me-2"></i>Mark Attendance</h1>
        <a href="attendance_report.php" class="btn btn-outline-primary">
            <i class="bi bi-file-earmark-bar-graph me-2"></i>View Reports
        </a>
    </div>

    <?php displayAlert(); ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-funnel-fill me-2"></i>Select Class & Date
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Class <span class="text-danger">*</span></label>
                    <select class="form-select" name="class" required>
                        <option value="">Select Class</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $selected_class == $i ? 'selected' : ''; ?>>
                                Class <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Section <span class="text-danger">*</span></label>
                    <select class="form-select" name="section" required>
                        <option value="">Select Section</option>
                        <?php foreach (['A', 'B', 'C', 'D'] as $section): ?>
                            <option value="<?php echo $section; ?>" <?php echo $selected_section === $section ? 'selected' : ''; ?>>
                                Section <?php echo $section; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="date"
                        value="<?php echo $selected_date; ?>" required max="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>Load Students
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($students && $students->num_rows > 0): ?>
        <form method="POST" action="">
            <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-people-fill me-2"></i>
                        Class <?php echo $selected_class . '-' . $selected_section; ?> |
                        <?php echo formatDate($selected_date); ?>
                    </span>
                    <div>
                        <button type="button" class="btn btn-sm btn-success" onclick="markAll('Present')">
                            <i class="bi bi-check-all me-1"></i>All Present
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="markAll('Absent')">
                            <i class="bi bi-x-circle me-1"></i>All Absent
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">Roll No</th>
                                    <th width="30%">Student Name</th>
                                    <th width="50%">Attendance Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $counter = 1;
                                while ($student = $students->fetch_assoc()):
                                    $current_status = $existing_attendance[$student['id']] ?? 'Present';
                                ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                        <td>
                                            <img src="../assets/images/uploads/<?php echo htmlspecialchars($student['image']); ?>"
                                                class="avatar me-2" alt="">
                                            <?php echo htmlspecialchars($student['name']); ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <input type="radio" class="btn-check"
                                                    name="attendance[<?php echo $student['id']; ?>]"
                                                    value="Present"
                                                    id="present_<?php echo $student['id']; ?>"
                                                    <?php echo $current_status === 'Present' ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-success" for="present_<?php echo $student['id']; ?>">
                                                    <i class="bi bi-check-circle me-1"></i>Present
                                                </label>

                                                <input type="radio" class="btn-check"
                                                    name="attendance[<?php echo $student['id']; ?>]"
                                                    value="Absent"
                                                    id="absent_<?php echo $student['id']; ?>"
                                                    <?php echo $current_status === 'Absent' ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-danger" for="absent_<?php echo $student['id']; ?>">
                                                    <i class="bi bi-x-circle me-1"></i>Absent
                                                </label>

                                                <input type="radio" class="btn-check"
                                                    name="attendance[<?php echo $student['id']; ?>]"
                                                    value="Late"
                                                    id="late_<?php echo $student['id']; ?>"
                                                    <?php echo $current_status === 'Late' ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-warning" for="late_<?php echo $student['id']; ?>">
                                                    <i class="bi bi-clock me-1"></i>Late
                                                </label>

                                                <input type="radio" class="btn-check"
                                                    name="attendance[<?php echo $student['id']; ?>]"
                                                    value="Half-Day"
                                                    id="half_<?php echo $student['id']; ?>"
                                                    <?php echo $current_status === 'Half-Day' ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-info" for="half_<?php echo $student['id']; ?>">
                                                    <i class="bi bi-hourglass-split me-1"></i>Half-Day
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" name="mark_attendance" class="btn btn-primary btn-lg">
                        <i class="bi bi-save me-2"></i>Save Attendance
                    </button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>

<script>
    function markAll(status) {
        const radios = document.querySelectorAll(`input[type="radio"][value="${status}"]`);
        radios.forEach(radio => {
            radio.checked = true;
        });
    }
</script>