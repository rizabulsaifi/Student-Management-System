<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    if (hasRole('admin')) {
        header("Location: admin/dashboard.php");
    } elseif (hasRole('teacher')) {
        header("Location: teacher/dashboard.php");
    } elseif (hasRole('student')) {
        header("Location: student/dashboard.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = sanitize($_POST['login_id']); 
    $password = $_POST['password'];
    
    if (empty($login_id) || empty($password)) {
        $error = 'Please enter both login ID and password';
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $login_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            
            $stmt = $conn->prepare("
                SELECT u.id, u.name, u.email, u.password, u.role 
                FROM users u
                JOIN students s ON u.id = s.user_id
                WHERE s.roll_number = ? AND u.role = 'student'
            ");
            $stmt->bind_param("s", $login_id);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } elseif ($user['role'] === 'teacher') {
                    header("Location: teacher/dashboard.php");
                } elseif ($user['role'] === 'student') {
                    header("Location: student/dashboard.php");
                } else {
                    header("Location: admin/dashboard.php");
                }
                exit();
            } else {
                $error = 'Invalid login credentials';
            }
        } else {
            $error = 'Invalid login credentials';
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Management System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0d1047; /*#2563EB; */
            --secondary: #150d74;
            --background: #F8FAFC;
            --card-bg: #FFFFFF;
            --text-primary: #0F172A;
            --accent: #38BDF8;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 450px;
            margin: 0 auto;
        }
        
        .login-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 2.5rem;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header i {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .login-header h2 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #64748B;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }
        
        .btn-login {
            background: var(--primary);
            color: white;
            padding: 0.75rem;
            font-weight: 500;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }
        
        .input-group-text {
            background: var(--background);
            border-right: none;
        }
        
        .form-control {
            border-left: none;
        }
        
        .demo-credentials {
            background: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <i class="bi bi-mortarboard-fill"></i>
                    <h2>Student Management System</h2>
                    <p>Please login to continue</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm">
                    <div class="mb-3">
                        <label class="form-label">Login ID / Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                            <input type="text" class="form-control" name="login_id" 
                                placeholder="Roll Number or Email" required autofocus>
                        </div>
                        <small class="text-muted">Students: Use Roll Number | Teachers/Admin: Use Email</small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" name="password" id="password" 
                                placeholder="Enter your password" required>
                            <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword()">
                                <i class="bi bi-eye-fill" id="toggleIcon"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    
                    <button type="submit" class="btn btn-login w-100">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </button>
                </form>
                
                <!-- <div class="demo-credentials">
                    <p class="small mb-2"><strong><i class="bi bi-info-circle me-1"></i>Demo Credentials:</strong></p>
                    <div class="small">
                        <strong>Admin:</strong> admin@school.com / password<br>
                        <strong>Teacher:</strong> john@school.com / password<br>
                        <strong>Student:</strong> STU001 / password
                    </div>
                </div> -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye-fill');
                icon.classList.add('bi-eye-slash-fill');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash-fill');
                icon.classList.add('bi-eye-fill');
            }
        }
    </script>
</body>
</html>