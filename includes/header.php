<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Protect the page: if not logged in, go back to login
if (!isset($_SESSION['user_id'])) {
    $path_to_root = (
        strpos($_SERVER['PHP_SELF'], '/admin/') !== false || 
        strpos($_SERVER['PHP_SELF'], '/teacher/') !== false || 
        strpos($_SERVER['PHP_SELF'], '/student/') !== false ||
        strpos($_SERVER['PHP_SELF'], '/shared/') !== false
    ) ? "../" : "";
    header("Location: " . $path_to_root . "index.php");
    exit;
}

// Fetch User Profile Image
$header_img = null;
if (isset($conn) && isset($_SESSION['user_id'])) {
    $header_uid = $_SESSION['user_id'];
    $header_img_q = $conn->query("SELECT profile_image FROM users WHERE user_id = $header_uid");
    $header_img = ($header_img_q && $header_img_q->num_rows > 0) ? $header_img_q->fetch_assoc()['profile_image'] : null;
}

// Fetch Notifications
$unread_count = 0;
$recent_notifications = null;
if (isset($conn) && isset($_SESSION['user_id'])) {
    $notif_uid = $_SESSION['user_id'];
    $count_q = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id = $notif_uid AND is_read = FALSE");
    if($count_q) $unread_count = $count_q->fetch_assoc()['c'];
    $recent_notifications = $conn->query("SELECT * FROM notifications WHERE user_id = $notif_uid ORDER BY created_at DESC LIMIT 5");
}

$base_path = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || 
              strpos($_SERVER['PHP_SELF'], '/teacher/') !== false || 
              strpos($_SERVER['PHP_SELF'], '/student/') !== false ||
              strpos($_SERVER['PHP_SELF'], '/shared/') !== false) ? "../" : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Examination System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- GLOBAL RESET & FONTS --- */
        * { box-sizing: border-box; }
        body { 
            background-color: #f3f6f9; 
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; 
            margin: 0;
            padding: 0;
            color: #333;
        }

        /* --- PROFESSIONAL TOP NAVBAR --- */
        .top-navbar {
            background: #ffffff;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 1050; 
            border-bottom: 3px solid #1976d2;
            width: 100%;
            max-width: 1600px;
            margin: 0 auto;
        }
        
        .nav-brand {
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 1.15rem;
            color: #0d47a1;
            text-decoration: none;
            white-space: nowrap;
            margin-right: 15px;
        }
        .nav-brand i { font-size: 1.6rem; margin-right: 10px; color: #1976d2; }

        /* --- NAV MENU LINKS (Compressed & Centered) --- */
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2px;
            list-style: none;
            margin: 0;
            padding: 0;
            flex: 1;
            justify-content: center;
        }

        .nav-menu > a, .nav-menu > .nav-dropdown > a {
            color: #555;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .nav-menu > a i, .nav-menu > .nav-dropdown > a i {
            margin-right: 6px;
            font-size: 0.95rem;
            color: #888;
            transition: 0.3s;
        }

        .nav-menu > a:hover, .nav-menu > .nav-dropdown > a:hover {
            background-color: #f0f4f8;
            color: #1976d2;
        }
        .nav-menu > a:hover i, .nav-menu > .nav-dropdown > a:hover i { color: #1976d2; }

        .nav-menu > a.active, .nav-menu > .nav-dropdown > a.active {
            background-color: #e3f2fd;
            color: #0d47a1;
        }
        .nav-menu > a.active i, .nav-menu > .nav-dropdown > a.active i { color: #0d47a1; }

        /* --- PROFESSIONAL DROPDOWN --- */
        .nav-dropdown {
            position: relative;
        }

        .nav-dropdown .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 8px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            padding: 8px;
            min-width: 220px;
            display: none;
            z-index: 9999;
        }

        .nav-dropdown .dropdown-menu.show {
            display: block;
            animation: slideDown 0.2s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dropdown-item {
            font-size: 0.9rem;
            font-weight: 600;
            padding: 12px 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            transition: 0.2s;
            color: #444;
            text-decoration: none;
        }
        .dropdown-item i { 
            margin-right: 12px; 
            font-size: 1.1rem; 
            width: 20px; 
            text-align: center;
        }
        .dropdown-item:hover {
            background-color: #e3f2fd;
            color: #0d47a1;
            transform: translateX(3px);
        }

        /* --- USER MENU (Right side) --- */
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
            white-space: nowrap;
            margin-left: 15px;
            flex-shrink: 0;
        }
        
        .user-badge {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #333;
        }
        
        .user-badge img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            border: 2px solid #1976d2;
        }
        .user-badge i.fa-user-circle { 
            font-size: 1.4rem; 
            color: #1976d2; 
            margin-right: 8px; 
        }

        .logout-btn {
            color: #dc3545;
            background: #ffebee;
            border: 1px solid #ffcdd2;
            padding: 7px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: 0.3s;
            display: flex;
            align-items: center;
        }
        .logout-btn i { margin-right: 6px; }
        .logout-btn:hover { background: #dc3545; color: white; }

        /* --- BELL ICON STYLING --- */
        .bell-icon::after { display: none; }
        .bell-icon:hover { color: #1976d2 !important; }
        .notif-badge {
            position: absolute;
            top: 0;
            left: 18px;
            font-size: 0.6rem;
            padding: 3px 5px;
            border-radius: 50%;
        }

        /* --- MAIN CONTENT --- */
        .main-content {
            padding: 30px;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            animation: fadeIn 0.4s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* --- PROFESSIONAL CARDS --- */
        .card { 
            border: none; 
            border-radius: 12px; 
            background: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04); 
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        /* Buttons */
        .btn { font-weight: 600; border-radius: 8px; transition: all 0.3s ease; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.15); }

        /* Tables */
        .table th { font-size: 0.8rem; text-transform: uppercase; color: #777; font-weight: 700; border-bottom: 2px solid #e0e0e0; }
        .table td { vertical-align: middle; }

        /* Responsive */
        @media (max-width: 992px) {
            .top-navbar { flex-direction: column; height: auto; padding: 15px; gap: 15px; }
            .nav-menu { flex-wrap: wrap; justify-content: center; }
            .user-menu { width: 100%; justify-content: center; margin-left: 0; }
            .main-content { padding: 15px; }
        }

        /* --- ANIMATED FLOWING NAVBAR (FOR STUDENTS AND ADMINS) --- */
        <?php if (isset($_SESSION['role']) && ($_SESSION['role'] == 'Student' || $_SESSION['role'] == 'Admin')): ?>
            .top-navbar {
                background: linear-gradient(-45deg, #0d47a1, #1976d2, #00897b, #6a1b9a);
                background-size: 400% 400%;
                animation: waterFlow 15s ease infinite;
                border-bottom: 3px solid rgba(255,255,255,0.2);
            }
            .top-navbar .nav-brand, 
            .top-navbar .nav-menu a, 
            .top-navbar .user-badge,
            .top-navbar .nav-brand i {
                color: white !important;
            }
            .top-navbar .nav-menu a i { color: rgba(255,255,255,0.7) !important; }
            .top-navbar .nav-menu a:hover {
                background-color: rgba(255, 255, 255, 0.2) !important;
                color: white !important;
            }
            .top-navbar .nav-menu a.active {
                background-color: rgba(255, 255, 255, 0.3) !important;
                border-bottom: 3px solid white !important;
                color: white !important;
            }
            .top-navbar .user-badge {
                background: rgba(255,255,255,0.15) !important;
                border: 1px solid rgba(255,255,255,0.3) !important;
                color: white !important;
            }
            .top-navbar .logout-btn {
                background: rgba(220, 53, 69, 0.8) !important;
                color: white !important;
                border: none;
            }
            .top-navbar .bell-icon { color: white !important; }
            .top-navbar .bell-icon:hover { color: #e3f2fd !important; }
            @keyframes waterFlow {
                0% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
        <?php endif; ?>
    </style>
</head>
<body>

<!-- TOP NAVBAR -->
<div class="top-navbar">
    <!-- Logo -->
    <a href="<?php echo $base_path; ?>index.php" class="nav-brand">
        <i class="fas fa-graduation-cap"></i> Exam System
    </a>

    <!-- Navigation Links -->
    <div class="nav-menu">
        <?php include ROOT_PATH . '/includes/sidebar.php'; ?>
    </div>

    <!-- User Profile, Notifications & Logout -->
    <div class="user-menu">
        
        <!-- NOTIFICATION BELL -->
        <div class="nav-dropdown" style="margin-right: 10px;">
            <a href="#" class="dropdown-toggle bell-icon" data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration: none; color: #555; position: relative; padding: 5px;">
                <i class="fas fa-bell fa-lg"></i>
                <?php if($unread_count > 0): ?>
                    <span class="position-absolute notif-badge badge rounded-pill bg-danger">
                        <?php echo $unread_count; ?>
                    </span>
                <?php endif; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow" style="width: 300px; padding: 0;">
                <li class="p-3 border-bottom bg-light">
                    <h6 class="m-0 fw-bold"><i class="fas fa-bell text-primary me-2"></i> Notifications</h6>
                </li>
                <?php if($recent_notifications && $recent_notifications->num_rows > 0): ?>
                    <?php while($n = $recent_notifications->fetch_assoc()): ?>
                        <li class="border-bottom">
                        <a class="dropdown-item py-3" href="<?php echo $n['link'] ?: '#'; ?>" style="white-space: normal; line-height: 1.4;">                                <div class="d-flex align-items-start">
                                    <i class="fas fa-info-circle text-primary mt-1 me-3"></i>
                                    <div>
                                        <p class="mb-1 text-dark" style="font-size: 0.85rem;"><?php echo htmlspecialchars($n['message']); ?></p>
                                        <small class="text-muted" style="font-size: 0.7rem;"><?php echo date('M d, H:i A', strtotime($n['created_at'])); ?></small>
                                    </div>
                                </div>
                            </a>
                        </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li class="p-4 text-center text-muted small">
                        <i class="fas fa-check-circle fa-2x mb-2 text-success"></i><br>
                        You have no new notifications.
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- USER PROFILE -->
        <div class="user-badge">
            <?php if (!empty($header_img)): ?>
                <img src="<?php echo $base_path; ?>assets/uploads/<?php echo htmlspecialchars($header_img); ?>" alt="Profile">
            <?php else: ?>
                <i class="fas fa-user-circle"></i>
            <?php endif; ?>
            <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
        </div>
        <a href="<?php echo $base_path; ?>logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<script>
    // Mark notifications as read when the bell is clicked
    document.addEventListener('DOMContentLoaded', function() {
        const bellIcon = document.querySelector('.bell-icon');
        if(bellIcon) {
            bellIcon.addEventListener('click', function() {
                fetch('<?php echo $base_path; ?>includes/mark_notifications_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'mark_read=1'
                }).then(() => {
                    const badge = this.querySelector('.notif-badge');
                    if(badge) badge.remove();
                });
            });
        }
    });
</script>