<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("
    SELECT t.*, u.email 
    FROM teachers t 
    LEFT JOIN users u ON t.user_id = u.id 
    WHERE t.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$teacher) {
    die('Teacher not found');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Login Credentials - <?php echo htmlspecialchars($teacher['name']); ?></title>

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
            border: 3px solid #10B981;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .school-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #10B981;
        }

        .school-logo {
            font-size: 4rem;
            color: #10B981;
        }

        .credentials-table th {
            background: #F1F5F9;
            font-weight: 600;
            color: #0F172A;
        }

        .login-details {
            background: #D1FAE5;
            border-left: 4px solid #10B981;
            padding: 20px;
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <div class="credentials-card">
        <div class="school-header">
            <i class="bi bi-person-badge-fill school-logo"></i>
            <h2 class="mt-3 mb-0">Student Management System</h2>
            <p class="text-muted mb-0">Teacher Login Credentials Card</p>
        </div>

        <div class="text-center mb-4">
            <img src="../assets/images/uploads/<?php echo htmlspecialchars($teacher['image']); ?>"
                class="rounded-circle border border-3 border-success"
                style="width: 120px; height: 120px; object-fit: cover;"
                alt="Teacher Photo">
        </div>

        <table class="table table-bordered credentials-table">
            <tbody>
                <tr>
                    <th width="40%"><i class="bi bi-person-fill text-success me-2"></i>Teacher Name:</th>
                    <td><strong><?php echo htmlspecialchars($teacher['name']); ?></strong></td>
                </tr>
                <tr>
                    <th><i class="bi bi-hash text-success me-2"></i>Employee ID:</th>
                    <td><strong class="text-success"><?php echo htmlspecialchars($teacher['employee_id']); ?></strong></td>
                </tr>
                <tr>
                    <th><i class="bi bi-book text-success me-2"></i>Subject:</th>
                    <td><?php echo htmlspecialchars($teacher['subject']); ?></td>
                </tr>
                <tr>
                    <th><i class="bi bi-award text-success me-2"></i>Qualification:</th>
                    <td><?php echo htmlspecialchars($teacher['qualification']); ?></td>
                </tr>
                <tr>
                    <th><i class="bi bi-envelope text-success me-2"></i>Email:</th>
                    <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                </tr>
                <tr>
                    <th><i class="bi bi-telephone text-success me-2"></i>Contact:</th>
                    <td><?php echo htmlspecialchars($teacher['contact']); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="login-details">
            <h5 class="mb-3"><i class="bi bi-key-fill me-2"></i>Portal Login Details</h5>
            <div class="row">
                <div class="col-md-6 mb-2">
                    <strong>Login Email:</strong>
                    <div class="alert alert-success mb-0 mt-1">
                        <h6 class="mb-0"><?php echo htmlspecialchars($teacher['email']); ?></h6>
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
                <li>Enter your <strong>Email Address</strong> as Login ID</li>
                <li>Enter the <strong>Password</strong> given by admin</li>
                <li>Click <strong>Login</strong> button</li>
            </ol>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-success text-white">
                <strong><i class="bi bi-unlock-fill me-2"></i>Teacher Portal Access:</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Mark Attendance</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Enter Results</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Create Assignments</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Grade Submissions</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>View Students</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Manage Schedule</li>
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
            <button onclick="window.print()" class="btn btn-success btn-lg">
                <i class="bi bi-printer-fill me-2"></i>Print Credentials
            </button>
            <a href="teachers.php" class="btn btn-secondary btn-lg ms-2">
                <i class="bi bi-arrow-left me-2"></i>Back to Teachers
            </a>
        </div>

        <div class="alert alert-danger mt-4">
            <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Important:</strong>
            Keep this credential card safe and secure. Do not share your password with anyone.
        </div>
    </div>
</body>

</html>