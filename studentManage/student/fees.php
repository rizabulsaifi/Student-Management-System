<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'Fee Details';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

$student_id = $student['id'];

$fees = $conn->query("
    SELECT * FROM fees 
    WHERE student_id = $student_id 
    ORDER BY created_at DESC
");

$totals = $conn->query("
    SELECT 
        SUM(amount) as total_fees,
        SUM(paid_amount) as total_paid,
        SUM(amount - paid_amount) as total_pending
    FROM fees 
    WHERE student_id = $student_id
")->fetch_assoc();

$payment_history = $conn->query("
    SELECT * FROM fees 
    WHERE student_id = $student_id 
    AND payment_date IS NOT NULL 
    ORDER BY payment_date DESC 
    LIMIT 10
");

include '../includes/header.php';
include 'sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-currency-dollar me-2"></i>Fee Details</h1>
        <button onclick="window.print()" class="btn btn-outline-primary">
            <i class="bi bi-printer me-2"></i>Print Receipt
        </button>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-label">Total Fees</div>
                    <div class="stats-number text-primary">₹<?php echo number_format($totals['total_fees'], 2); ?></div>
                    <small class="text-muted">Academic year</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stats-card success">
                <div class="card-body">
                    <div class="stats-label">Total Paid</div>
                    <div class="stats-number text-success">₹<?php echo number_format($totals['total_paid'], 2); ?></div>
                    <small class="text-muted">Paid so far</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stats-card danger">
                <div class="card-body">
                    <div class="stats-label">Pending Amount</div>
                    <div class="stats-number text-danger">₹<?php echo number_format($totals['total_pending'], 2); ?></div>
                    <small class="text-muted">Outstanding balance</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-receipt me-2"></i>Fee Records
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
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($fees->num_rows > 0): ?>
                            <?php while ($fee = $fees->fetch_assoc()): 
                                $balance = $fee['amount'] - $fee['paid_amount'];
                                $isOverdue = strtotime($fee['due_date']) < time() && $balance > 0;
                            ?>
                            <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($fee['fee_type']); ?></strong>
                                    <?php if ($isOverdue): ?>
                                        <br><small class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Overdue</small>
                                    <?php endif; ?>
                                </td>
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
                                    <?php if ($fee['status'] === 'Paid'): ?>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="viewReceipt(<?php echo $fee['id']; ?>)">
                                            <i class="bi bi-receipt"></i> Receipt
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-success" disabled>
                                            <i class="bi bi-credit-card"></i> Pay Online
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
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

    <div class="card">
        <div class="card-header">
            <i class="bi bi-clock-history me-2"></i>Payment History
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Fee Type</th>
                            <th>Amount Paid</th>
                            <th>Payment Method</th>
                            <th>Transaction ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($payment_history->num_rows > 0): ?>
                            <?php while ($payment = $payment_history->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo formatDate($payment['payment_date']); ?></td>
                                <td><?php echo htmlspecialchars($payment['fee_type']); ?></td>
                                <td><strong>₹<?php echo number_format($payment['paid_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($payment['payment_method']); ?>
                                    </span>
                                </td>
                                <td><code><?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?></code></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted">No payment history</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($totals['total_pending'] > 0): ?>
    <div class="alert alert-warning mt-4">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Note:</strong> You have a pending balance of ₹<?php echo number_format($totals['total_pending'], 2); ?>. 
        Please contact the administration office for payment.
    </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>

<script>
function viewReceipt(feeId) {
    alert('Receipt for Fee ID: ' + feeId + '\nThis feature will open a printable receipt.');
}
</script>