<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Fee Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fee'])) {
    $student_id = (int)$_POST['student_id'];
    $fee_type = sanitize($_POST['fee_type']);
    $amount = (float)$_POST['amount'];
    $due_date = sanitize($_POST['due_date']);
    
    $stmt = $conn->prepare("INSERT INTO fees (student_id, fee_type, amount, due_date, status) VALUES (?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("isds", $student_id, $fee_type, $amount, $due_date);
    
    if ($stmt->execute()) {
        setAlert('Fee record created successfully', 'success');
    } else {
        setAlert('Failed to create fee record', 'danger');
    }
    $stmt->close();
    header("Location: fees.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $fee_id = (int)$_POST['fee_id'];
    $paid_amount = (float)$_POST['paid_amount'];
    $payment_method = sanitize($_POST['payment_method']);
    $transaction_id = sanitize($_POST['transaction_id']);
    
    $fee = $conn->query("SELECT amount, paid_amount FROM fees WHERE id = $fee_id")->fetch_assoc();
    $total_paid = $fee['paid_amount'] + $paid_amount;
    
    $status = 'Pending';
    if ($total_paid >= $fee['amount']) {
        $status = 'Paid';
    } elseif ($total_paid > 0) {
        $status = 'Partial';
    }
    
    $stmt = $conn->prepare("UPDATE fees SET paid_amount = ?, status = ?, payment_date = NOW(), payment_method = ?, transaction_id = ? WHERE id = ?");
    $stmt->bind_param("dsssi", $total_paid, $status, $payment_method, $transaction_id, $fee_id);
    
    if ($stmt->execute()) {
        setAlert('Payment recorded successfully', 'success');
    } else {
        setAlert('Failed to record payment', 'danger');
    }
    $stmt->close();
    header("Location: fees.php");
    exit();
}

$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$class_filter = isset($_GET['class']) ? sanitize($_GET['class']) : '';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 15;

$whereClause = "WHERE 1=1";
if (!empty($status_filter)) {
    $whereClause .= " AND f.status = '$status_filter'";
}
if (!empty($class_filter)) {
    $whereClause .= " AND s.class = '$class_filter'";
}

$countQuery = "SELECT COUNT(*) as total FROM fees f JOIN students s ON f.student_id = s.id $whereClause";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];

$pagination = paginate($totalRecords, $recordsPerPage, $page);
$offset = $pagination['offset'];

$query = "SELECT f.*, s.name, s.roll_number, s.class, s.section 
        FROM fees f 
        JOIN students s ON f.student_id = s.id 
        $whereClause 
        ORDER BY f.created_at DESC 
        LIMIT $offset, $recordsPerPage";
$fees = $conn->query($query);

$students = $conn->query("SELECT id, name, roll_number, class, section FROM students WHERE status = 'Active' ORDER BY class, roll_number");

$summary = $conn->query("
    SELECT 
        SUM(amount) as total_fees,
        SUM(paid_amount) as total_paid,
        SUM(CASE WHEN status = 'Pending' THEN amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN status = 'Overdue' THEN amount ELSE 0 END) as overdue_amount
    FROM fees
")->fetch_assoc();

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-currency-dollar me-2"></i>Fee Management</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFeeModal">
            <i class="bi bi-plus-circle me-2"></i>Add Fee Record
        </button>
    </div>

    <?php displayAlert(); ?>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-label">Total Fees</div>
                    <div class="stats-number text-primary">₹<?php echo number_format($summary['total_fees'], 2); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card success">
                <div class="card-body">
                    <div class="stats-label">Total Paid</div>
                    <div class="stats-number text-success">₹<?php echo number_format($summary['total_paid'], 2); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card warning">
                <div class="card-body">
                    <div class="stats-label">Pending</div>
                    <div class="stats-number text-warning">₹<?php echo number_format($summary['pending_amount'], 2); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card danger">
                <div class="card-body">
                    <div class="stats-label">Overdue</div>
                    <div class="stats-number text-danger">₹<?php echo number_format($summary['overdue_amount'], 2); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="Paid" <?php echo $status_filter === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Partial" <?php echo $status_filter === 'Partial' ? 'selected' : ''; ?>>Partial</option>
                        <option value="Overdue" <?php echo $status_filter === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="class">
                        <option value="">All Classes</option>
                        <?php
                        $classes = $conn->query("SELECT DISTINCT class FROM students ORDER BY class");
                        while ($class = $classes->fetch_assoc()):
                        ?>
                            <option value="<?php echo $class['class']; ?>" 
                                    <?php echo $class_filter == $class['class'] ? 'selected' : ''; ?>>
                                Class <?php echo $class['class']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-funnel-fill me-1"></i>Filter
                    </button>
                    <a href="fees.php" class="btn btn-secondary">
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
                            <th>Roll No</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Fee Type</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($fees->num_rows > 0): ?>
                            <?php while ($fee = $fees->fetch_assoc()): 
                                $balance = $fee['amount'] - $fee['paid_amount'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fee['roll_number']); ?></td>
                                <td><?php echo htmlspecialchars($fee['name']); ?></td>
                                <td><?php echo htmlspecialchars($fee['class'] . '-' . $fee['section']); ?></td>
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
                                <td>
                                    <?php if ($fee['status'] !== 'Paid'): ?>
                                    <button class="btn btn-sm btn-success" 
                                            onclick="openPaymentModal(<?php echo $fee['id']; ?>, '<?php echo htmlspecialchars($fee['name']); ?>', <?php echo $balance; ?>)">
                                        <i class="bi bi-cash"></i> Pay
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: #CBD5E1;"></i>
                                    <p class="text-muted mt-2">No fee records found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php echo renderPagination($pagination['total_pages'], $pagination['current_page'], 'fees.php'); ?>
</main>

<div class="modal fade" id="addFeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Fee Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Student <span class="text-danger">*</span></label>
                        <select class="form-select" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php while ($student = $students->fetch_assoc()): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo $student['roll_number'] . ' - ' . $student['name'] . 
                                            ' (Class ' . $student['class'] . '-' . $student['section'] . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Fee Type <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="fee_type" required 
                                placeholder="e.g., Tuition Fee - January 2024">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="amount" required step="0.01" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Due Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="due_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_fee" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Add Fee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cash me-2"></i>Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="fee_id" id="paymentFeeId">
                    
                    <div class="alert alert-info">
                        <strong>Student:</strong> <span id="paymentStudentName"></span><br>
                        <strong>Balance Amount:</strong> ₹<span id="paymentBalance"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount to Pay <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="paid_amount" required step="0.01" min="0" id="paidAmount">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select class="form-select" name="payment_method" required>
                            <option value="">Select Method</option>
                            <option value="Cash">Cash</option>
                            <option value="Online">Online Transfer</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Card">Card</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Transaction ID / Reference</label>
                        <input type="text" class="form-control" name="transaction_id" placeholder="Optional">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_payment" class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i>Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function openPaymentModal(feeId, studentName, balance) {
    document.getElementById('paymentFeeId').value = feeId;
    document.getElementById('paymentStudentName').textContent = studentName;
    document.getElementById('paymentBalance').textContent = balance.toFixed(2);
    document.getElementById('paidAmount').max = balance;
    
    var modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
}
</script>