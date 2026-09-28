<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Add Teacher';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = sanitize($_POST['employee_id']);
    $name = sanitize($_POST['name']);
    $subject = sanitize($_POST['subject']);
    $qualification = sanitize($_POST['qualification']);
    $contact = sanitize($_POST['contact']);
    $email = sanitize($_POST['email']);
    $address = sanitize($_POST['address']);
    $date_of_joining = sanitize($_POST['date_of_joining']);
    $salary = sanitize($_POST['salary']);
    $password = $_POST['password'];

    if (empty($employee_id)) $errors[] = 'Employee ID is required';
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($subject)) $errors[] = 'Subject is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (empty($password)) $errors[] = 'Password is required';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';

    $stmt = $conn->prepare("SELECT id FROM teachers WHERE employee_id = ?");
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = 'Employee ID already exists';
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = 'Email already exists';
    }
    $stmt->close();

    $imageName = 'default.jpg';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload = uploadFile($_FILES['image'], 'uploads', ['jpg', 'jpeg', 'png']);
        if ($upload['success']) {
            $imageName = $upload['filename'];
        } else {
            $errors[] = $upload['message'];
        }
    }

    if (empty($errors)) {
        $conn->begin_transaction();

        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'teacher')");
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            $stmt->execute();
            $user_id = $conn->insert_id;
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO teachers (user_id, employee_id, name, subject, qualification, contact, email, address, image, date_of_joining, salary) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssssssd", $user_id, $employee_id, $name, $subject, $qualification, $contact, $email, $address, $imageName, $date_of_joining, $salary);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            setAlert("Teacher added successfully. Login Email: <strong>$email</strong>, Password: <strong>$password</strong>", 'success');
            header("Location: teachers.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Failed to add teacher: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-person-plus-fill me-2"></i>Add New Teacher</h1>
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
            <form method="POST" action="" enctype="multipart/form-data" id="teacherForm">
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <h5 class="border-bottom pb-2"><i class="bi bi-person-fill me-2"></i>Personal Information</h5>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Employee ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="employee_id" required
                            placeholder="e.g., TCH001">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required
                            placeholder="teacher@school.com">
                        <small class="text-muted">This will be used as Login ID</small>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Contact Number</label>
                        <input type="tel" class="form-control" name="contact">
                    </div>

                    <div class="col-md-8 mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" name="address">
                    </div>

                    <div class="col-md-12 mb-4 mt-3">
                        <h5 class="border-bottom pb-2"><i class="bi bi-shield-lock-fill me-2"></i>Login Credentials</h5>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="password" id="password"
                                required minlength="6" placeholder="Min 6 characters">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        <small class="text-muted">Teacher will use Email + this password to login</small>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Generate Random Password</label><br>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="generatePassword()">
                            <i class="bi bi-shuffle me-1"></i>Generate Password
                        </button>
                    </div>

                    <div class="col-md-12 mb-4 mt-3">
                        <h5 class="border-bottom pb-2"><i class="bi bi-briefcase-fill me-2"></i>Professional Information</h5>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="subject" required
                            placeholder="e.g., Mathematics, Physics">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Qualification</label>
                        <input type="text" class="form-control" name="qualification"
                            placeholder="e.g., M.Sc, B.Ed">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date of Joining</label>
                        <input type="date" class="form-control" name="date_of_joining" value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Monthly Salary</label>
                        <input type="number" class="form-control" name="salary" step="0.01" placeholder="0.00">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Photo</label>
                        <input type="file" class="form-control" name="image" accept="image/*" id="imageInput">
                        <img id="imagePreview" class="mt-2" style="max-width: 150px; display: none;">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Add Teacher
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
                const preview = document.getElementById('imagePreview');
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    });

    function togglePassword() {
        const passwordInput = document.getElementById('password');
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

    function generatePassword() {
        const length = 8;
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$";
        let password = "";
        for (let i = 0; i < length; i++) {
            password += charset.charAt(Math.floor(Math.random() * charset.length));
        }
        document.getElementById('password').value = password;
        document.getElementById('password').type = 'text';

        alert('Password generated: ' + password + '\n\nPlease note this down for the teacher.');
    }
</script>