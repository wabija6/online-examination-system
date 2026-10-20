<?php
// login.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please enter your username and password.";
    } else {
        // Query ONLY by username. The system will find the role.
        $stmt = $conn->prepare("SELECT user_id, name, password, role, status FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($password === $user['password']) {
                if ($user['status'] === 'Active') {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = $user['role'];

                    // Auto-redirect based on the role from the database
                    if ($user['role'] == 'Admin') {
                        header("Location: admin/dashboard.php");
                    } elseif ($user['role'] == 'Teacher') {
                        header("Location: teacher/dashboard.php");
                    } else {
                        header("Location: student/dashboard.php");
                    }
                    exit;
                } else {
                    $error = "Your account is inactive. Please contact admin.";
                }
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
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
    <title>Login - Online Examination System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            background-color: #f0f4f8;
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* --- MAIN WRAPPER --- */
        .login-wrapper {
            flex: 1;
            display: flex;
            width: 100%;
            position: relative;
        }

        /* --- LEFT PANEL (65%) --- */
        .left-panel {
            flex: 65;
            position: relative;
            background-image: url('assets/login-side.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px;
        }
        .left-overlay {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: linear-gradient(135deg, rgba(13, 71, 161, 0.92), rgba(25, 118, 210, 0.75));
            z-index: 1;
        }
        .left-content {
            position: relative;
            z-index: 2;
            color: white;
            max-width: 550px;
            animation: slideInLeft 0.8s ease-out;
        }
        
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .left-content h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 15px;
            line-height: 1.2;
        }
        .left-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 40px;
        }
        .feature-list { list-style: none; padding: 0; margin: 0; }
        .feature-list li {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }
        .feature-list li i {
            font-size: 1.2rem;
            margin-right: 18px;
            background: rgba(255,255,255,0.15);
            padding: 14px;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .feature-list li strong { display: block; font-size: 1rem; }
        .feature-list li span { font-size: 0.85rem; opacity: 0.85; }

        /* --- TOP LEFT BRAND & HOME BUTTON --- */
        .brand-container {
            position: absolute;
            top: 25px;
            left: 30px;
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .home-btn {
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
            background: rgba(255, 255, 255, 0.15);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.2);
            transition: 0.3s;
        }
        .home-btn:hover {
            background: white;
            color: #0d47a1;
            transform: scale(1.1);
        }

        .brand-text {
            color: white;
            font-weight: 700;
            font-size: 1.3rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            letter-spacing: 0.5px;
        }
        .brand-text i {
            margin-right: 10px;
            font-size: 1.6rem;
            color: #64b5f6;
        }

        /* --- RIGHT PANEL (35%) --- */
        .right-panel {
            flex: 35;
            background: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: -10px 0 30px rgba(0,0,0,0.08);
            animation: slideInRight 0.8s ease-out;
            z-index: 5;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .login-logo {
            text-align: center;
            color: #0d47a1;
            margin-bottom: 30px;
        }
        .login-logo i { font-size: 3rem; margin-bottom: 10px; }
        .login-logo h4 {
            font-weight: 800;
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        .form-label { font-weight: 600; color: #333; font-size: 0.85rem; }
        .input-group-text {
            background-color: #e3f2fd;
            border: none;
            color: #0d47a1;
            border-radius: 10px 0 0 10px;
            padding: 10px 15px;
        }
        .form-control {
            border-radius: 0 10px 10px 0;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 0.95rem;
            background: #f8f9fa;
        }
        .form-control:focus {
            border-color: #1976d2;
            box-shadow: 0 0 0 0.2rem rgba(25, 118, 210, 0.15);
            background: white;
        }
        .eye-toggle {
            cursor: pointer;
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-left: none;
            color: #888;
            padding: 0 15px;
            border-radius: 0 10px 10px 0;
            transition: 0.3s;
        }
        .eye-toggle:hover { color: #0d47a1; }
        
        .btn-login {
            background: #0d47a1;
            color: white;
            border-radius: 10px;
            padding: 12px;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            border: none;
            transition: 0.3s;
            margin-top: 15px;
            box-shadow: 0 4px 15px rgba(13, 71, 161, 0.3);
        }
        .btn-login:hover { 
            background: #1976d2; 
            color: white;
            transform: translateY(-2px);
        }
        
        .divider {
            display: flex; align-items: center; text-align: center;
            color: #aaa; margin: 25px 0; font-size: 0.8rem;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1; border-bottom: 1px solid #e0e0e0;
        }
        .divider::before { margin-right: 15px; }
        .divider::after { margin-left: 15px; }
        
        .btn-google {
            border: 1px solid #e0e0e0; border-radius: 10px;
            padding: 12px; width: 100%; background: white;
            font-weight: 600; color: #555; transition: 0.3s;
            font-size: 0.95rem;
        }
        .btn-google:hover { 
            background: #f8f9fa; 
            border-color: #ccc;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .security-note {
            background: #e3f2fd; padding: 15px; border-radius: 10px;
            font-size: 0.8rem; color: #0d47a1;
            display: flex; align-items: center; margin-top: 25px;
            border-left: 4px solid #1976d2;
        }
        .security-note i { font-size: 1.2rem; margin-right: 12px; }

        /* --- FOOTER --- */
        .bottom-footer {
            background-color: #0d47a1;
            color: #cfd8dc;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            z-index: 10;
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 900px) {
            .login-wrapper { flex-direction: column; }
            .left-panel { min-height: 450px; padding: 90px 25px 40px; }
            .left-content h1 { font-size: 2rem; }
            .right-panel { padding: 40px 25px; }
            .bottom-footer { flex-direction: column; text-align: center; gap: 5px; }
            .brand-container { top: 15px; left: 15px; }
            .brand-text { font-size: 1.1rem; }
            .brand-text i { font-size: 1.3rem; }
        }
    </style>
</head>
<body>

<!-- MAIN LOGIN AREA -->
<div class="login-wrapper">

    <!-- LEFT PANEL (65%) -->
    <div class="left-panel">
        <div class="left-overlay"></div>
        
        <!-- Top Left Brand & Home Button -->
        <div class="brand-container">
            <a href="index.php" class="home-btn" title="Go to Homepage">
                <i class="fas fa-home"></i>
            </a>
            <a href="index.php" class="brand-text">
                <i class="fas fa-graduation-cap"></i> Online Examination System
            </a>
        </div>

        <div class="left-content">
            <div style="color:#64b5f6; font-weight:700; letter-spacing:1px; margin-bottom:15px; text-transform:uppercase;">Learn • Test • Grow</div>
            <h1>Welcome to <br><span style="color:#64b5f6;">Online Examination System</span></h1>
            <p>Take your exams online, anytime, anywhere.<br>Fast, secure and reliable.</p>

            <ul class="feature-list">
                <li>
                    <i class="fas fa-laptop"></i>
                    <div><strong>Easy Access</strong><span>Login and take your exam from any device.</span></div>
                </li>
                <li>
                    <i class="fas fa-shield-alt"></i>
                    <div><strong>Secure System</strong><span>Your data and results are fully protected.</span></div>
                </li>
                <li>
                    <i class="fas fa-clock"></i>
                    <div><strong>Real-Time Results</strong><span>Get your results instantly after submission.</span></div>
                </li>
            </ul>
        </div>
    </div>

    <!-- RIGHT PANEL (35%) -->
    <div class="right-panel">
        <div class="login-logo">
            <i class="fas fa-graduation-cap"></i>
            <h4>Welcome Back</h4>
            <p class="text-muted small mb-0">Please enter your credentials to login</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 small"><i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Enter your username" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Enter your password" required>
                    <button type="button" class="eye-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt me-2"></i> Login
            </button>
        </form>

        <div class="divider">OR</div>

            <a href="google-login.php" class="btn-google d-flex align-items-center justify-content-center text-decoration-none">
                <i class="fab fa-google text-danger me-2"></i> Login with Google
            </a>

        <div class="security-note">
            <i class="fas fa-shield-alt"></i>
            <span>For security reasons, always keep your account information confidential.</span>
        </div>
    </div>
</div>

<!-- FOOTER -->
<div class="bottom-footer">
    <div>© 2025 Online Examination System. All rights reserved.</div>
    <div>Your Success is Our Priority</div>
</div>

<script>
    function togglePassword() {
        const input = document.getElementById('passwordInput');
        const icon = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>