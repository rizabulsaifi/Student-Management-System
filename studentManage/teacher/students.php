<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'My Students';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

$class_filter = isset($_GET['class']) ? sanitize($_GET['class']) : '';
$section_filter = isset($_GET['section']) ? sanitize($_GET['section']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$whereClause = "WHERE status = 'Active'";
if (!empty($class_filter)) {
    $whereClause .= " AND class = '$class_filter'";
}
if (!empty($section_filter)) {
    $whereClause .= " AND section = '$section_filter'";
}
if (!empty($search)) {
    $whereClause .= " AND (name LIKE '%$search%' OR roll_number LIKE '%$search%')";
}

$students = $conn->query("SELECT * FROM students $whereClause ORDER BY class, section, roll_number");

include '../includes/header.php';
include 'sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-people-fill me-2"></i>My Students</h1>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <select class="form-select" name="class">
                        <option value="">All Classes</option>
                        <?php for($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $class_filter == $i ? 'selected' : ''; ?>>
                                Class <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="section">
                        <option value="">All Sections</option>
                        <?php foreach(['A', 'B', 'C', 'D'] as $section): ?>
                            <option value="<?php echo $section; ?>" <?php echo $section_filter === $section ? 'selected' : ''; ?>>
                                Section <?php echo $section; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control" name="search" 
                            placeholder="Search by name or roll number" 
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-funnel-fill me-1"></i>Filter
                    </button>
                    <a href="students.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-list-ul me-2"></i>Students List (<?php echo $students->num_rows; ?> students)
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Roll No</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Contact</th>
                            <th>Parent Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students->num_rows > 0): ?>
                            <?php while ($student = $students->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <img src="../assets/images/uploads/<?php echo htmlspecialchars($student['image']); ?>" 
                                        class="avatar" alt="">
                                </td>
                                <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($student['name']); ?></strong>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($student['gender']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($student['class'] . '-' . $student['section']); ?></td>
                                <td><?php echo htmlspecialchars($student['contact']); ?></td>
                                <td><?php echo htmlspecialchars($student['parent_contact']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="viewStudent(<?php echo $student['id']; ?>)">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: #CBD5E1;"></i>
                                    <p class="text-muted mt-2">No students found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="studentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="studentDetails">
                <div class="text-center">
                    <div class="spinner-border" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function viewStudent(studentId) {
    const modal = new bootstrap.Modal(document.getElementById('studentModal'));
    modal.show();
    
    document.getElementById('studentDetails').innerHTML = `
        <div class="alert alert-info">
            <strong>Student ID:</strong> ${studentId}<br>
            Full details would be loaded here via AJAX in production.
        </div>
    `;
}
</script>