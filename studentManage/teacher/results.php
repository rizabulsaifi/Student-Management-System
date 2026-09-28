<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'Enter Results';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_results'])) {
    $exam_type = sanitize($_POST['exam_type']);
    $exam_date = sanitize($_POST['exam_date']);
    $total_marks = (int)$_POST['total_marks'];
    $results_data = $_POST['marks'] ?? [];
    
    $success_count = 0;
    foreach ($results_data as $student_id => $obtained_marks) {
        if (empty($obtained_marks)) continue;
        
        $student_id = (int)$student_id;
        $obtained_marks = (int)$obtained_marks;
        $percentage = ($obtained_marks / $total_marks) * 100;
        $grade = calculateGrade($percentage);
        
        $stmt = $conn->prepare("INSERT INTO results (student_id, subject, exam_type, total_marks, obtained_marks, grade, exam_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issiiss", $student_id, $teacher['subject'], $exam_type, $total_marks, $obtained_marks, $grade, $exam_date);
        
        if ($stmt->execute()) {
            $success_count++;
        }
        $stmt->close();
    }
    
    setAlert("Results entered for $success_count students", 'success');
    header("Location: results.php");
    exit();
}

$selected_class = isset($_GET['class']) ? sanitize($_GET['class']) : '';
$selected_section = isset($_GET['section']) ? sanitize($_GET['section']) : '';

$students = null;
if (!empty($selected_class) && !empty($selected_section)) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE class = ? AND section = ? AND status = 'Active' ORDER BY roll_number");
    $stmt->bind_param("ss", $selected_class, $selected_section);
    $stmt->execute();
    $students = $stmt->get_result();
}

include '../includes/header.php';
include 'sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-clipboard-data-fill me-2"></i>Enter Results</h1>
    </div>

    <?php displayAlert(); ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-funnel-fill me-2"></i>Select Class
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Class <span class="text-danger">*</span></label>
                    <select class="form-select" name="class" required>
                        <option value="">Select Class</option>
                        <?php for($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $selected_class == $i ? 'selected' : ''; ?>>
                                Class <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Section <span class="text-danger">*</span></label>
                    <select class="form-select" name="section" required>
                        <option value="">Select Section</option>
                        <?php foreach(['A', 'B', 'C', 'D'] as $section): ?>
                            <option value="<?php echo $section; ?>" <?php echo $selected_section === $section ? 'selected' : ''; ?>>
                                Section <?php echo $section; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>Load Students
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($students && $students->num_rows > 0): ?>
    <form method="POST" action="">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-journal-text me-2"></i>Exam Details
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Exam Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="exam_type" required>
                            <option value="">Select Exam Type</option>
                            <option value="Mid-Term">Mid-Term</option>
                            <option value="Final">Final</option>
                            <option value="Unit Test">Unit Test</option>
                            <option value="Quiz">Quiz</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Exam Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="exam_date" required max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Total Marks <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="total_marks" required value="100" min="1">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-people-fill me-2"></i>
                Enter Marks - <?php echo $teacher['subject']; ?> | 
                Class <?php echo $selected_class . '-' . $selected_section; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="10%">Roll No</th>
                                <th width="40%">Student Name</th>
                                <th width="50%">Obtained Marks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($student = $students->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                <td>
                                    <img src="../assets/images/uploads/<?php echo htmlspecialchars($student['image']); ?>" 
                                        class="avatar me-2" alt="">
                                    <?php echo htmlspecialchars($student['name']); ?>
                                </td>
                                <td>
                                    <input type="number" class="form-control" 
                                        name="marks[<?php echo $student['id']; ?>]" 
                                        placeholder="Enter marks" 
                                        min="0" 
                                        max="100">
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" name="add_results" class="btn btn-primary btn-lg">
                    <i class="bi bi-save me-2"></i>Save Results
                </button>
            </div>
        </div>
    </form>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>