<?php
// index.php - PUBLIC ONE-PAGE HOMEPAGE
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Examination System - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; } /* Enables smooth scrolling */
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; }

        /* --- NAVBAR --- */
        .public-navbar {
            position: fixed; top: 0; width: 100%;
            padding: 20px 50px;
            display: flex; justify-content: space-between; align-items: center;
            z-index: 1000;
            background: rgba(13, 71, 161, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        .nav-brand {
            display: flex; align-items: center;
            font-weight: 700; font-size: 1.3rem;
            color: white; text-decoration: none;
        }
        .nav-brand i { font-size: 1.8rem; margin-right: 12px; color: #64b5f6; }
        
        .nav-links { display: flex; gap: 30px; align-items: center; }
        .nav-links a {
            color: #e3f2fd; text-decoration: none;
            font-weight: 500; font-size: 1rem;
            transition: 0.3s; position: relative;
        }
        .nav-links a:hover { color: white; }
        .nav-links a::after {
            content: ''; position: absolute;
            width: 0%; height: 2px; bottom: -5px; left: 0;
            background-color: #64b5f6; transition: 0.3s;
        }
        .nav-links a:hover::after { width: 100%; }

        .btn-login-nav {
            background: #1976d2; color: white;
            padding: 10px 25px; border-radius: 30px;
            text-decoration: none; font-weight: 600;
            transition: 0.3s; border: 2px solid transparent;
        }
        .btn-login-nav:hover {
            background: transparent; border-color: #64b5f6;
            color: white; transform: translateY(-2px);
        }

        /* --- HERO SECTION (Home) --- */
        .hero-section {
            position: relative; height: 100vh;
            display: flex; align-items: center; justify-content: center;
            text-align: center; color: white;
            background-image: url('assets/login-side.jpg');
            background-size: cover; 
            background-position: center;
            background-attachment: fixed; 
        }
        .hero-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            /* Brighter, lighter overlay */
            background: linear-gradient(135deg, rgba(25, 118, 210, 0.6), rgba(100, 181, 246, 0.4));
            z-index: 1;
        }
        .hero-content {
            position: relative; z-index: 2;
            max-width: 800px; padding: 20px;
        }
        .hero-content h1 {
            font-size: 3.5rem; font-weight: 800;
            margin-bottom: 20px; line-height: 1.2;
        }
        .hero-content p {
            font-size: 1.2rem; margin-bottom: 40px; opacity: 0.9;
        }
        .btn-get-started {
            background: #1976d2; color: white;
            padding: 15px 40px; border-radius: 30px;
            font-size: 1.1rem; font-weight: 700;
            text-decoration: none; transition: 0.3s;
            display: inline-block; box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .btn-get-started:hover {
            background: #2196f3; transform: translateY(-3px); color: white;
        }

        /* --- ANIMATION CLASSES --- */
        .fade-in-up {
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
        }
        .fade-in-up.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* --- ABOUT SECTION --- */
        .about-section {
            padding: 100px 20px;
            background: #ffffff;
            text-align: center;
        }
        .about-section h2 {
            color: #0d47a1;
            font-weight: 800;
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        .about-section p {
            max-width: 700px;
            margin: 0 auto 40px;
            color: #666;
            font-size: 1.1rem;
            line-height: 1.8;
        }
        .feature-card {
            padding: 40px 20px;
            border-radius: 15px;
            background: #f8f9fa;
            transition: 0.3s;
            height: 100%;
            border-bottom: 4px solid transparent;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            border-bottom: 4px solid #1976d2;
        }
        .feature-icon {
            font-size: 3rem;
            color: #1976d2;
            margin-bottom: 20px;
        }
        .feature-card h5 { font-weight: 700; color: #0d47a1; }
        .feature-card p { font-size: 0.95rem; color: #666; margin: 0; }

        /* --- HELP SECTION --- */
        .help-section {
            padding: 100px 20px;
            background: #e3f2fd;
            text-align: center;
        }
        .help-section h2 {
            color: #0d47a1;
            font-weight: 800;
            font-size: 2.5rem;
            margin-bottom: 40px;
        }
        .help-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: left;
            height: 100%;
            transition: 0.3s;
        }
        .help-card:hover { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .help-card h5 { color: #1976d2; font-weight: 700; }
        .help-card p { color: #666; margin: 0; }

        /* --- FOOTER --- */
        .public-footer {
            background: #0d47a1;
            color: #cfd8dc;
            padding: 30px;
            text-align: center;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<!-- STICKY NAVBAR -->
<div class="public-navbar">
    <a href="#home" class="nav-brand">
        <i class="fas fa-graduation-cap"></i> Exam System
    </a>
    <div class="nav-links">
        <a href="#home">Home</a>
        <a href="#about">About</a>
        <a href="#help">Help</a>
        <a href="login.php" class="btn-login-nav"><i class="fas fa-sign-in-alt me-2"></i> Login</a>
    </div>
</div>

<!-- SECTION 1: HOME (Hero) -->
<div id="home" class="hero-section">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1 class="fade-in-up">Welcome to the <br> Online Examination System</h1>
        <p class="fade-in-up">Take your exams online, anytime, anywhere. Fast, secure, and reliable.</p>
        <a href="login.php" class="btn-get-started fade-in-up">Get Started <i class="fas fa-arrow-right ms-2"></i></a>
    </div>
</div>

<!-- SECTION 2: ABOUT -->
<div id="about" class="about-section">
    <div class="container">
        <h2 class="fade-in-up">About the System</h2>
        <p class="fade-in-up">The Online Examination System is a web-based platform designed for Ambo University to facilitate digital assessments. It allows teachers to create exams, students to take timed assessments, and administrators to manage the entire process securely.</p>
        
        <div class="row g-4 mt-5">
            <div class="col-md-4 fade-in-up">
                <div class="feature-card">
                    <i class="fas fa-laptop feature-icon"></i>
                    <h5>Easy Access</h5>
                    <p>Take your exams from any device with a web browser.</p>
                </div>
            </div>
            <div class="col-md-4 fade-in-up">
                <div class="feature-card">
                    <i class="fas fa-shield-alt feature-icon"></i>
                    <h5>Secure System</h5>
                    <p>Your data and results are fully protected with modern security.</p>
                </div>
            </div>
            <div class="col-md-4 fade-in-up">
                <div class="feature-card">
                    <i class="fas fa-clock feature-icon"></i>
                    <h5>Real-Time Results</h5>
                    <p>Get your results instantly after submitting your exam.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 3: HELP -->
<div id="help" class="help-section">
    <div class="container">
        <h2 class="fade-in-up">Help & Support</h2>
        <div class="row g-4 mt-4">
            <div class="col-md-6 fade-in-up">
                <div class="help-card">
                    <h5><i class="fas fa-user-lock text-primary"></i> How do I log in?</h5>
                    <p>Use the credentials provided by your system administrator. If you forgot your password, please contact the admin.</p>
                </div>
            </div>
            <div class="col-md-6 fade-in-up">
                <div class="help-card">
                    <h5><i class="fas fa-wifi text-primary"></i> I lost connection during an exam.</h5>
                    <p>Don't worry! Your answers are saved automatically. Log back in and continue before the timer runs out.</p>
                </div>
            </div>
            <div class="col-md-6 fade-in-up">
                <div class="help-card">
                    <h5><i class="fas fa-question-circle text-primary"></i> Can I retake an exam?</h5>
                    <p>No, most exams are limited to one attempt. Please check with your teacher for specific exam rules.</p>
                </div>
            </div>
            <div class="col-md-6 fade-in-up">
                <div class="help-card">
                    <h5><i class="fas fa-envelope text-primary"></i> Contact Us</h5>
                    <p>Email: support@ambouniversity.edu.et<br>Phone: +251 900 000 000</p>
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
<script>
    // Intersection Observer for Scroll Animations
    document.addEventListener("DOMContentLoaded", function() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.fade-in-up').forEach((el) => {
            observer.observe(el);
        });
    });
</script>
</body>
</html>