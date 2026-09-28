<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Edit Teacher';
$errors = [];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT t.*, u.email FROM teachers t LEFT JOIN users u ON t.user_id = u.id WHERE t.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setAlert('Teacher not found', 'danger');
    header("Location: teachers.php");
    exit();
}

$teacher = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = sanitize($_POST['employee_id']);
    $name = sanitize($_POST['name']);
    $subject = sanitize($_POST['subject']);
    $qualification = sanitize($_POST['qualification']);
    $contact = sanitize($_POST['contact']);
    $email = sanitize($_POST['email']);
    $address = sanitize($_POST['address']);
    $salary = sanitize($_POST['salary']);
    $status = sanitize($_POST['status']);
    $new_password = $_POST['new_password'] ?? '';

    if (empty($employee_id)) $errors[] = 'Employee ID is required';
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($subject)) $errors[] = 'Subject is required';
    if (empty($email)) $errors[] = 'Email is required';

    $imageName = $teacher['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload = uploadFile($_FILES['image'], 'uploads', ['jpg', 'jpeg', 'png']);
        if ($upload['success']) {
            if ($teacher['image'] !== 'default.jpg') {
                @unlink("../assets/images/uploads/" . $teacher['image']);
            }
            $imageName = $upload['filename'];
        }
    }

    if (empty($errors)) {
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("UPDATE teachers SET employee_id=?, name=?, subject=?, qualification=?, contact=?, email=?, address=?, image=?, salary=?, status=? WHERE id=?");
            $stmt->bind_param("ssssssssdsi", $employee_id, $name, $subject, $qualification, $contact, $email, $address, $imageName, $salary, $status, $id);
            $stmt->execute();
            $stmt->close();

            if ($teacher['user_id']) {
                $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
                $stmt->bind_param("ssi", $name, $email, $teacher['user_id']);
                $stmt->execute();
                $stmt->close();

                if (!empty($new_password)) {
                    if (strlen($new_password) < 6) {
                        throw new Exception('Password must be at least 6 characters');
                    }
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
                    $stmt->bind_param("si", $hashed_password, $teacher['user_id']);
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                $password = !empty($new_password) ? $new_password : 'password123';
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'teacher')");
                $stmt->bind_param("sss", $name, $email, $hashed_password);
                $stmt->execute();
                $user_id = $conn->insert_id;
                $stmt->close();

                $stmt = $conn->prepare("UPDATE teachers SET user_id=? WHERE id=?");
                $stmt->bind_param("ii", $user_id, $id);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();

            $message = 'Teacher updated successfully';
            if (!empty($new_password)) {
                $message .= ". New password: <strong>$new_password</strong>";
            }

            setAlert($message, 'success');
            header("Location: teachers.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Failed to update teacher: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-pencil-square me-2"></i>Edit Teacher</h1>
        <a href="teachers.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Teachers
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Error!</strong>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <h5 class="border-bottom pb-2"><i class="bi bi-person-fill me-2"></i>Personal Information</h5>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Employee ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="employee_id"
                            value="<?php echo htmlspecialchars($teacher['employee_id']); ?>" required readonly>
                        <small class="text-muted">Cannot be changed</small>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name"
                            value="<?php echo htmlspecialchars($teacher['name']); ?>" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email"
                            value="<?php echo htmlspecialchars($teacher['email']); ?>" required>
                        <small class="text-muted">Login ID</small>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Contact Number</label>
                        <input type="tel" class="form-control" name="contact"
                            value="<?php echo htmlspecialchars($teacher['contact']); ?>">
                    </div>

                    <div class="col-md-8 mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" name="address"
                            value="<?php echo htmlspecialchars($teacher['address']); ?>">
                    </div>

                    <div class="col-md-12 mb-4 mt-3">
                        <h5 class="border-bottom pb-2"><i class="bi bi-key-fill me-2"></i>Change Password (Optional)</h5>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="new_password" id="newPassword"
                                placeholder="Leave empty to keep current">
                            <button class="btn btn-outline-secondary" type="button" onclick="toggleNewPassword()">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        <small class="text-muted">Min 6 characters</small>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Generate Password</label><br>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="generateNewPassword()">
                            <i class="bi bi-shuffle me-1"></i>Generate New Password
                        </button>
                    </div>

                    <div class="col-md-12 mb-4 mt-3">
                        <h5 class="border-bottom pb-2"><i class="bi bi-briefcase-fill me-2"></i>Professional Information</h5>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="subject"
                            value="<?php echo htmlspecialchars($teacher['subject']); ?>" required>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Qualification</label>
                        <input type="text" class="form-control" name="qualification"
                            value="<?php echo htmlspecialchars($teacher['qualification']); ?>">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Salary</label>
                        <input type="number" class="form-control" name="salary" step="0.01"
                            value="<?php echo $teacher['salary']; ?>">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="Active" <?php echo $teacher['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo $teacher['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Photo</label>
                        <input type="file" class="form-control" name="image" accept="image/*" id="imageInput">
                        <img src="../assets/images/uploads/<?php echo htmlspecialchars($teacher['image']); ?>"
                            id="imagePreview" class="mt-2" style="max-width: 150px;">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Update Teacher
                    </button>
                    <a href="teachers.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

<script>
    document.getElementById('imageInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });

    function toggleNewPassword() {
        const passwordInput = document.getElementById('newPassword');
        const icon = document.getElementById('toggleIcon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }

    function generateNewPassword() {
        const length = 8;
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$";
        let password = "";
        for (let i = 0; i < length; i++) {
            password += charset.charAt(Math.floor(Math.random() * charset.length));
        }
        document.getElementById('newPassword').value = password;
        document.getElementById('newPassword').type = 'text';

        alert('New password generated: ' + password + '\n\nPlease note this down for the teacher.');
    }
</script>