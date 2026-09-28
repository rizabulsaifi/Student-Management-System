<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'My Profile';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT s.*, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE s.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $contact = sanitize($_POST['contact']);
    $address = sanitize($_POST['address']);
    
    $imageName = $student['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload = uploadFile($_FILES['image'], 'uploads', ['jpg', 'jpeg', 'png']);
        if ($upload['success']) {
            if ($student['image'] !== 'default.jpg') {
                @unlink("../assets/images/uploads/" . $student['image']);
            }
            $imageName = $upload['filename'];
        }
    }
    
    $stmt = $conn->prepare("UPDATE students SET contact=?, address=?, image=? WHERE id=?");
    $stmt->bind_param("sssi", $contact, $address, $imageName, $student['id']);
    
    if ($stmt->execute()) {
        setAlert('Profile updated successfully', 'success');
        header("Location: profile.php");
        exit();
    }
    $stmt->close();
}

include '../includes/header.php';
include 'sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-person-fill me-2"></i>My Profile</h1>
    </div>

    <?php displayAlert(); ?>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="../assets/images/uploads/<?php echo htmlspecialchars($student['image']); ?>" 
                        class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;" alt="Profile">
                    <h4><?php echo htmlspecialchars($student['name']); ?></h4>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($student['roll_number']); ?></p>
                    <p class="text-muted">Class <?php echo htmlspecialchars($student['class'] . '-' . $student['section']); ?></p>
                    
                    <div class="mt-3">
                        <span class="badge bg-<?php echo $student['status'] === 'Active' ? 'success' : 'danger'; ?> mb-2">
                            <?php echo $student['status']; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Full Name:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($student['name']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Roll Number:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($student['roll_number']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Class:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($student['class'] . '-' . $student['section']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Email:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($student['email']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Date of Birth:</strong></div>
                        <div class="col-sm-8"><?php echo formatDate($student['date_of_birth']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Gender:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($student['gender']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Contact:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($student['contact']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Parent Contact:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($student['parent_contact']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Address:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($student['address']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Admission Date:</strong></div>
                        <div class="col-sm-8"><?php echo formatDate($student['admission_date']); ?></div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Update Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" name="contact" 
                                value="<?php echo htmlspecialchars($student['contact']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($student['address']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Profile Photo</label>
                            <input type="file" class="form-control" name="image" accept="image/*" id="imageInput">
                            <img id="imagePreview" src="../assets/images/uploads/<?php echo htmlspecialchars($student['image']); ?>" 
                                class="mt-2" style="max-width: 150px;">
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Update Profile
                        </button>
                    </form>
                </div>
            </div>
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
</script>