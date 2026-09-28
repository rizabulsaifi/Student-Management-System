<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Add Student';
$errors = [];

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
    $admission_date = sanitize($_POST['admission_date']);
    $password = $_POST['password']; 
    $email = sanitize($_POST['email']);
    
    if (empty($roll_number)) $errors[] = 'Roll number is required';
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($class)) $errors[] = 'Class is required';
    if (empty($section)) $errors[] = 'Section is required';
    if (empty($password)) $errors[] = 'Password is required';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
    
    $stmt = $conn->prepare("SELECT id FROM students WHERE roll_number = ?");
    $stmt->bind_param("s", $roll_number);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = 'Roll number already exists';
    }
    $stmt->close();
    
    if (empty($email)) {
        $email = strtolower(str_replace(' ', '', $roll_number)) . '@school.com';
    }
    
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
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            $stmt->execute();
            $user_id = $conn->insert_id;
            $stmt->close();
            
            $stmt = $conn->prepare("INSERT INTO students (user_id, roll_number, name, class, section, contact, parent_contact, address, image, date_of_birth, gender, admission_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssssssss", $user_id, $roll_number, $name, $class, $section, $contact, $parent_contact, $address, $imageName, $date_of_birth, $gender, $admission_date);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            
            setAlert("Student added successfully. Login ID: <strong>$roll_number</strong>, Password: <strong>$password</strong>", 'success');
            header("Location: students.php");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Failed to add student: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-person-plus-fill me-2"></i>Add New Student</h1>
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
            <form method="POST" action="" enctype="multipart/form-data" id="studentForm">
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <h5 class="border-bottom pb-2"><i class="bi bi-person-fill me-2"></i>Personal Information</h5>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Roll Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="roll_number" required 
                            placeholder="e.g., STU001">
                        <small class="text-muted">This will be used as Login ID</small>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" name="date_of_birth">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Contact Number</label>
                        <input type="tel" class="form-control" name="contact" placeholder="Student contact">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Parent Contact <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" name="parent_contact" required>
                    </div>
                    
                    <div class="col-md-12 mb-4 mt-3">
                        <h5 class="border-bottom pb-2"><i class="bi bi-shield-lock-fill me-2"></i>Login Credentials</h5>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Email (Optional)</label>
                        <input type="email" class="form-control" name="email" 
                            placeholder="Auto-generated if empty">
                        <small class="text-muted">If empty, will use: rollnumber@school.com</small>
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
                        <small class="text-muted">Student will use Roll Number + this password to login</small>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Generate Random Password</label><br>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="generatePassword()">
                            <i class="bi bi-shuffle me-1"></i>Generate Password
                        </button>
                    </div>
                    
                    <div class="col-md-12 mb-4 mt-3">
                        <h5 class="border-bottom pb-2"><i class="bi bi-book-fill me-2"></i>Academic Information</h5>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Class <span class="text-danger">*</span></label>
                        <select class="form-select" name="class" required>
                            <option value="">Select Class</option>
                            <?php for($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Section <span class="text-danger">*</span></label>
                        <select class="form-select" name="section" required>
                            <option value="">Select Section</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Admission Date</label>
                        <input type="date" class="form-control" name="admission_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Photo</label>
                        <input type="file" class="form-control" name="image" accept="image/*" id="imageInput">
                        <img id="imagePreview" class="mt-2" style="max-width: 150px; display: none;">
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Add Student
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
    
    alert('Password generated: ' + password + '\n\nPlease note this down for the student.');
}
</script>