<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Teachers Management';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 10;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$subjectFilter = isset($_GET['subject']) ? sanitize($_GET['subject']) : '';

$whereClause = "WHERE 1=1";
if (!empty($search)) {
    $whereClause .= " AND (name LIKE '%$search%' OR employee_id LIKE '%$search%' OR contact LIKE '%$search%')";
}
if (!empty($subjectFilter)) {
    $whereClause .= " AND subject = '$subjectFilter'";
}

$countQuery = "SELECT COUNT(*) as total FROM teachers $whereClause";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];

$pagination = paginate($totalRecords, $recordsPerPage, $page);
$offset = $pagination['offset'];

$query = "SELECT * FROM teachers $whereClause ORDER BY created_at DESC LIMIT $offset, $recordsPerPage";
$teachers = $conn->query($query);

$subjects = $conn->query("SELECT DISTINCT subject FROM teachers ORDER BY subject");

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-person-badge-fill me-2"></i>Teachers Management</h1>
        <a href="add_teacher.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add New Teacher
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
                            placeholder="Search by name, employee ID or contact"
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="subject">
                        <option value="">All Subjects</option>
                        <?php while ($subject = $subjects->fetch_assoc()): ?>
                            <option value="<?php echo $subject['subject']; ?>"
                                <?php echo $subjectFilter === $subject['subject'] ? 'selected' : ''; ?>>
                                <?php echo $subject['subject']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-funnel-fill me-1"></i>Filter
                    </button>
                    <a href="teachers.php" class="btn btn-secondary">
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
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Subject</th>
                            <th>Qualification</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($teachers->num_rows > 0): ?>
                            <?php while ($teacher = $teachers->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <img src="../assets/images/uploads/<?php echo htmlspecialchars($teacher['image']); ?>"
                                            class="avatar" alt="Teacher Photo">
                                    </td>
                                    <td><?php echo htmlspecialchars($teacher['employee_id']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($teacher['name']); ?></strong>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($teacher['email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($teacher['subject']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($teacher['qualification']); ?></td>
                                    <td>
                                        <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($teacher['contact']); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $teacher['status'] === 'Active' ? 'success' : 'danger'; ?>">
                                            <?php echo htmlspecialchars($teacher['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_teacher.php?id=<?php echo $teacher['id']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Edit Teacher">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <a href="print_teacher_credentials.php?id=<?php echo $teacher['id']; ?>"
                                            class="btn btn-sm btn-outline-info"
                                            title="Print Login Credentials"
                                            target="_blank">
                                            <i class="bi bi-printer-fill"></i>
                                        </a>

                                        <a href="delete_teacher.php?id=<?php echo $teacher['id']; ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Delete this teacher?')"
                                            title="Delete Teacher">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: #CBD5E1;"></i>
                                    <p class="text-muted mt-2">No teachers found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php echo renderPagination($pagination['total_pages'], $pagination['current_page'], 'teachers.php'); ?>
</main>

<?php include '../includes/footer.php'; ?>