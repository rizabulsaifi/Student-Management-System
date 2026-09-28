<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("
    SELECT s.*, u.email 
    FROM students s 
    LEFT JOIN users u ON s.user_id = u.id 
    WHERE s.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    die('Student not found');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login Credentials - <?php echo htmlspecialchars($student['name']); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
            }
        }

        body {
            background: #F8FAFC;
            padding: 20px;
        }

        .credentials-card {
            max-width: 700px;
            margin: 50px auto;
            background: white;
            border: 3px solid #2563EB;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .school-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #2563EB;
        }

        .school-logo {
            font-size: 4rem;
            color: #2563EB;
        }

        .credentials-table th {
            background: #F1F5F9;
            font-weight: 600;
            color: #0F172A;
        }

        .login-details {
            background: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 20px;
            margin: 20px 0;
        }

        .qr-placeholder {
            width: 150px;
            height: 150px;
            background: #F1F5F9;
            border: 2px dashed #CBD5E1;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <div class="credentials-card">
        <div class="school-header">
            <i class="bi bi-mortarboard-fill school-logo"></i>
            <h2 class="mt-3 mb-0">Student Management System</h2>
            <p class="text-muted mb-0">Student Login Credentials Card</p>
        </div>

        <div class="text-center mb-4">
            <img src="../assets/images/uploads/<?php echo htmlspecialchars($student['image']); ?>"
                class="rounded-circle border border-3 border-primary"
                style="width: 120px; height: 120px; object-fit: cover;"
                alt="Student Photo">
        </div>

        <table class="table table-bordered credentials-table">
            <tbody>
                <tr>
                    <th width="40%"><i class="bi bi-person-fill text-primary me-2"></i>Student Name:</th>
                    <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                </tr>
                <tr>
                    <th><i class="bi bi-hash text-primary me-2"></i>Roll Number:</th>
                    <td><strong class="text-primary"><?php echo htmlspecialchars($student['roll_number']); ?></strong></td>
                </tr>
                <tr>
                    <th><i class="bi bi-mortarboard text-primary me-2"></i>Class & Section:</th>
                    <td><?php echo htmlspecialchars($student['class'] . ' - ' . $student['section']); ?></td>
                </tr>
                <tr>
                    <th><i class="bi bi-envelope text-primary me-2"></i>Email:</th>
                    <td><?php echo htmlspecialchars($student['email'] ?? 'Not provided'); ?></td>
                </tr>
                <tr>
                    <th><i class="bi bi-telephone text-primary me-2"></i>Contact:</th>
                    <td><?php echo htmlspecialchars($student['contact'] ?? 'Not provided'); ?></td>
                </tr>
                <tr>
                    <th><i class="bi bi-person-hearts text-primary me-2"></i>Parent Contact:</th>
                    <td><?php echo htmlspecialchars($student['parent_contact']); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="login-details">
            <h5 class="mb-3"><i class="bi bi-key-fill me-2"></i>Portal Login Details</h5>
            <div class="row">
                <div class="col-md-6 mb-2">
                    <strong>Login ID (Username):</strong>
                    <div class="alert alert-success mb-0 mt-1">
                        <h4 class="mb-0"><?php echo htmlspecialchars($student['roll_number']); ?></h4>
                    </div>
                </div>
                <div class="col-md-6 mb-2">
                    <strong>Password:</strong>
                    <div class="alert alert-warning mb-0 mt-1">
                        <h6 class="mb-0">As provided by Admin</h6>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            <h6 class="alert-heading"><i class="bi bi-info-circle-fill me-2"></i>Login Instructions:</h6>
            <ol class="mb-0">
                <li>Visit: <strong><?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/login.php'; ?></strong></li>
                <li>Enter your <strong>Roll Number</strong> as Login ID</li>
                <li>Enter the <strong>Password</strong> given by your school admin</li>
                <li>Click <strong>Login</strong> button</li>
            </ol>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-primary text-white">
                <strong><i class="bi bi-unlock-fill me-2"></i>What You Can Access:</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>View Dashboard</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>View Attendance</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Check Results</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Fee Status</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Submit Assignments</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>View Time Table</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">
                <i class="bi bi-calendar-event me-1"></i>Issued on: <?php echo date('d F Y'); ?>
            </small>
        </div>

        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-primary btn-lg">
                <i class="bi bi-printer-fill me-2"></i>Print Credentials
            </button>
            <a href="students.php" class="btn btn-secondary btn-lg ms-2">
                <i class="bi bi-arrow-left me-2"></i>Back to Students
            </a>
        </div>

        <div class="alert alert-danger mt-4">
            <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Important:</strong>
            Keep this credential card safe and secure. Do not share your password with anyone.
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>