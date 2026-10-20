<?php
// home.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Online Examination System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        /* --- GLASSMORPHISM PUBLIC NAVBAR --- */
        .public-navbar {
            position: absolute;
            top: 0;
            width: 100%;
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            background: rgba(13, 71, 161, 0.85);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .nav-brand {
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 1.3rem;
            color: white;
            text-decoration: none;
        }
        .nav-brand i { font-size: 1.8rem; margin-right: 12px; color: #64b5f6; }
        
        .nav-links { display: flex; gap: 30px; align-items: center; }
        .nav-links a {
            color: #e3f2fd;
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            transition: 0.3s;
            position: relative;
        }
        .nav-links a:hover { color: white; }
        .nav-links a::after {
            content: '';
            position: absolute;
            width: 0%;
            height: 2px;
            bottom: -5px;
            left: 0;
            background-color: #64b5f6;
            transition: 0.3s;
        }
        .nav-links a:hover::after { width: 100%; }

        .btn-login-nav {
            background: #1976d2;
            color: white;
            padding: 10px 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            border: 2px solid transparent;
        }
        .btn-login-nav:hover {
            background: transparent;
            border-color: #64b5f6;
            color: white;
            transform: translateY(-2px);
        }

        /* --- HERO SECTION --- */
        .hero-section {
            position: relative;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            background-image: url('assets/login-side.jpg');
            background-size: cover;
            background-position: center;
        }
        .hero-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, rgba(13, 71, 161, 0.9), rgba(25, 118, 210, 0.8));
            z-index: 1;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            padding: 20px;
            animation: fadeIn 1s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        .btn-get-started {
            background: #1976d2;
            color: white;
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
            transition: 0.3s;
            display: inline-block;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .btn-get-started:hover {
            background: #2196f3;
            transform: translateY(-3px);
            color: white;
        }

        /* --- FEATURES SECTION --- */
        .features-section {
            padding: 80px 20px;
            background: #ffffff;
            text-align: center;
        }
        .features-section h2 {
            color: #0d47a1;
            font-weight: 700;
            margin-bottom: 50px;
        }
        .feature-card {
            padding: 30px;
            border-radius: 15px;
            background: #f8f9fa;
            transition: 0.3s;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        .feature-icon {
            font-size: 2.5rem;
            color: #1976d2;
            margin-bottom: 20px;
        }
        .feature-card h5 {
            font-weight: 700;
            color: #0d47a1;
        }

        /* --- FOOTER --- */
        .public-footer {
            background: #0d47a1;
            color: #cfd8dc;
            padding: 20px;
            text-align: center;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<!-- PUBLIC NAVBAR -->
<div class="public-navbar">
    <a href="home.php" class="nav-brand">
        <i class="fas fa-graduation-cap"></i> Exam System
    </a>
    <div class="nav-links">
        <a href="home.php">Home</a>
        <a href="about.php">About</a>
        <a href="help.php">Help</a>
        <a href="index.php" class="btn-login-nav"><i class="fas fa-sign-in-alt me-2"></i> Login</a>
    </div>
</div>

<!-- HERO SECTION -->
<div class="hero-section">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>Welcome to the <br> Online Examination System</h1>
        <p>Take your exams online, anytime, anywhere. Fast, secure, and reliable.</p>
        <a href="index.php" class="btn-get-started">Get Started <i class="fas fa-arrow-right ms-2"></i></a>
    </div>
</div>

<!-- FEATURES SECTION -->
<div class="features-section">
    <h2>Why Choose Our System?</h2>
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card">
                    <i class="fas fa-laptop feature-icon"></i>
                    <h5>Easy Access</h5>
                    <p class="text-muted">Take your exams from any device with an internet connection.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <i class="fas fa-shield-alt feature-icon"></i>
                    <h5>Secure System</h5>
                    <p class="text-muted">Your data and results are fully protected with modern security.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <i class="fas fa-clock feature-icon"></i>
                    <h5>Real-Time Results</h5>
                    <p class="text-muted">Get your results instantly after submitting your exam.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FOOTER -->
<div class="public-footer">
    © 2025 Online Examination System | Ambo University
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>