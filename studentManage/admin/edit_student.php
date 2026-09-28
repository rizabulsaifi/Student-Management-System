<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Edit Student';
$errors = [];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT s.*, u.email FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setAlert('Student not found', 'danger');
    header("Location: students.php");
    exit();
}

$student = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roll_number = sanitize($_POST['roll_number']);
    $name = sanitize($_POST['name']);
    $class = sanitize($_POST['class']);
    $section = sanitize($_POST['section']);
    $contact = sanitize($_POST['contact']);
    $parent_contact = sanitize($_POST['parent_contact']);
    $address = sanitize($_POST['address']);
    $date_of_birth = sanitize($_POST['date_of_birth']);
    $gender = sanitize($_POST['gender']);
    $status = sanitize($_POST['status']);
    $new_password = $_POST['new_password'] ?? '';

    if (empty($roll_number)) $errors[] = 'Roll number is required';
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($class)) $errors[] = 'Class is required';
    if (empty($section)) $errors[] = 'Section is required';

    $stmt = $conn->prepare("SELECT id FROM students WHERE roll_number = ? AND id != ?");
    $stmt->bind_param("si", $roll_number, $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = 'Roll number already exists';
    }
    $stmt->close();

    $imageName = $student['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload = uploadFile($_FILES['image'], 'uploads', ['jpg', 'jpeg', 'png']);
        if ($upload['success']) {
            if ($student['image'] !== 'default.jpg') {
                @unlink("../assets/images/uploads/" . $student['image']);
            }
            $imageName = $upload['filename'];
        } else {
            $errors[] = $upload['message'];
        }
    }

    if (empty($errors)) {
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("UPDATE students SET roll_number=?, name=?, class=?, section=?, contact=?, parent_contact=?, address=?, image=?, date_of_birth=?, gender=?, status=? WHERE id=?");
            $stmt->bind_param("sssssssssssi", $roll_number, $name, $class, $section, $contact, $parent_contact, $address, $imageName, $date_of_birth, $gender, $status, $id);
            $stmt->execute();
            $stmt->close();

            if ($student['user_id']) {
                $stmt = $conn->prepare("UPDATE users SET name=? WHERE id=?");
                $stmt->bind_param("si", $name, $student['user_id']);
                $stmt->execute();
                $stmt->close();

                if (!empty($new_password)) {
                    if (strlen($new_password) < 6) {
                        throw new Exception('Password must be at least 6 characters');
                    }
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
                    $stmt->bind_param("si", $hashed_password, $student['user_id']);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            $conn->commit();

            $message = 'Student updated successfully';
            if (!empty($new_password)) {
                $message .= ". New password: <strong>$new_password</strong>";
            }

            setAlert($message, 'success');
            header("Location: students.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Failed to update student: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-pencil-square me-2"></i>Edit Student</h1>
        <a href="students.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Students
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
                        <label class="form-label">Roll Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="roll_number"
                            value="<?php echo htmlspecialchars($student['roll_number']); ?>" required readonly>
                        <small class="text-muted">Login ID (cannot be changed)</small>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name"
                            value="<?php echo htmlspecialchars($student['name']); ?>" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" name="date_of_birth"
                            value="<?php echo $student['date_of_birth']; ?>">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo $student['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $student['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $student['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Contact Number</label>
                        <input type="tel" class="form-control" name="contact"
                            value="<?php echo htmlspecialchars($student['contact']); ?>">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Parent Contact</label>
                        <input type="tel" class="form-control" name="parent_contact"
                            value="<?php echo htmlspecialchars($student['parent_contact']); ?>">
                    </div>

                    <div class="col-md-12 mb-4 mt-3">
                        <h5 class="border-bottom pb-2"><i class="bi bi-key-fill me-2"></i>Change Password (Optional)</h5>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Current Login ID</label>
                        <input type="text" class="form-control"
                            value="<?php echo htmlspecialchars($student['roll_number']); ?>" readonly>
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
                        <small class="text-muted">Min 6 characters. Leave empty to keep current password</small>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Generate Password</label><br>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="generateNewPassword()">
                            <i class="bi bi-shuffle me-1"></i>Generate New Password
                        </button>
                    </div>

                    <div class="col-md-12 mb-4 mt-3">
                        <h5 class="border-bottom pb-2"><i class="bi bi-book-fill me-2"></i>Academic Information</h5>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Class <span class="text-danger">*</span></label>
                        <select class="form-select" name="class" required>
                            <option value="">Select Class</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $student['class'] == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Section <span class="text-danger">*</span></label>
                        <select class="form-select" name="section" required>
                            <option value="">Select Section</option>
                            <?php foreach (['A', 'B', 'C', 'D'] as $sec): ?>
                                <option value="<?php echo $sec; ?>" <?php echo $student['section'] === $sec ? 'selected' : ''; ?>>
                                    <?php echo $sec; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="Active" <?php echo $student['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo $student['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-md-9 mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($student['address']); ?></textarea>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Photo</label>
                        <input type="file" class="form-control" name="image" accept="image/*" id="imageInput">
                        <img src="../assets/images/uploads/<?php echo htmlspecialchars($student['image']); ?>"
                            id="imagePreview" class="mt-2" style="max-width: 150px;">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Update Student
                    </button>
                    <a href="students.php" class="btn btn-secondary">
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

        alert('New password generated: ' + password + '\n\nPlease note this down for the student.');
    }
</script>