<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Students Management';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 10;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$classFilter = isset($_GET['class']) ? sanitize($_GET['class']) : '';

$whereClause = "WHERE 1=1";
if (!empty($search)) {
    $whereClause .= " AND (name LIKE '%$search%' OR roll_number LIKE '%$search%' OR contact LIKE '%$search%')";
}
if (!empty($classFilter)) {
    $whereClause .= " AND class = '$classFilter'";
}

$countQuery = "SELECT COUNT(*) as total FROM students $whereClause";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];

$pagination = paginate($totalRecords, $recordsPerPage, $page);
$offset = $pagination['offset'];

$query = "SELECT * FROM students $whereClause ORDER BY created_at DESC LIMIT $offset, $recordsPerPage";
$students = $conn->query($query);

$classes = $conn->query("SELECT DISTINCT class FROM students ORDER BY class");

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-people-fill me-2"></i>Students Management</h1>
        <a href="add_student.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add New Student
        </a>
    </div>

    <?php displayAlert(); ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-5">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control" name="search"
                            placeholder="Search by name, roll number or contact"
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="class">
                        <option value="">All Classes</option>
                        <?php while ($class = $classes->fetch_assoc()): ?>
                            <option value="<?php echo $class['class']; ?>"
                                <?php echo $classFilter === $class['class'] ? 'selected' : ''; ?>>
                                Class <?php echo $class['class']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
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
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students->num_rows > 0): ?>
                            <?php while ($student = $students->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <img src="../assets/images/uploads/<?php echo htmlspecialchars($student['image']); ?>"
                                            class="avatar" alt="Student Photo">
                                    </td>
                                    <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($student['name']); ?></strong>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($student['gender']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['class'] . '-' . $student['section']); ?></td>
                                    <td>
                                        <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($student['contact']); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $student['status'] === 'Active' ? 'success' : 'danger'; ?>">
                                            <?php echo htmlspecialchars($student['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_student.php?id=<?php echo $student['id']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Edit Student">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <a href="print_credentials.php?id=<?php echo $student['id']; ?>"
                                            class="btn btn-sm btn-outline-info"
                                            title="Print Login Credentials"
                                            target="_blank">
                                            <i class="bi bi-printer-fill"></i>
                                        </a>

                                        <a href="delete_student.php?id=<?php echo $student['id']; ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Are you sure you want to delete this student?')"
                                            title="Delete Student">
                                            <i class="bi bi-trash"></i>
                                        </a>
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

    <?php echo renderPagination($pagination['total_pages'], $pagination['current_page'], 'students.php'); ?>
</main>

<?php include '../includes/footer.php'; ?>