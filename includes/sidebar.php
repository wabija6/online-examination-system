<?php 
// includes/sidebar.php
$role = $_SESSION['role']; 
$current_page = basename($_SERVER['PHP_SELF']); 
?>

<!-- ================= ADMIN LINKS ================= -->
<?php if ($role == 'Admin'): ?>
    <a href="/online_exam/admin/dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a>
    
    <div class="nav-dropdown">
        <a href="#" class="dropdown-toggle <?php echo (in_array($current_page, ['users.php', 'students.php', 'teachers.php'])) ? 'active' : ''; ?>" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
            <i class="fas fa-users"></i> Users
        </a>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="/online_exam/admin/users.php"><i class="fas fa-users-cog text-primary"></i> Manage Users</a></li>
            <li><a class="dropdown-item" href="/online_exam/admin/students.php"><i class="fas fa-user-graduate text-success"></i> Students</a></li>
            <li><a class="dropdown-item" href="/online_exam/admin/teachers.php"><i class="fas fa-chalkboard-teacher text-warning"></i> Teachers</a></li>
        </ul>
    </div>

    <a href="/online_exam/admin/exams.php" class="<?php echo ($current_page == 'exams.php') ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> Exams</a>
    <a href="/online_exam/admin/results.php" class="<?php echo ($current_page == 'results.php') ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i> Results</a>
    <a href="/online_exam/admin/reports.php" class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-file-pdf"></i> Reports</a>
    <a href="/online_exam/shared/settings.php" class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a>
<?php endif; ?>

<!-- ================= TEACHER LINKS ================= -->
<?php if ($role == 'Teacher'): ?>
    <a href="/online_exam/teacher/dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a>
    <a href="/online_exam/teacher/manage_exams.php" class="<?php echo ($current_page == 'manage_exams.php') ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> Exams</a>
    <a href="/online_exam/teacher/students.php" class="<?php echo ($current_page == 'students.php') ? 'active' : ''; ?>"><i class="fas fa-user-graduate"></i> Students</a>
    <a href="/online_exam/teacher/questions.php" class="<?php echo ($current_page == 'questions.php') ? 'active' : ''; ?>"><i class="fas fa-question-circle"></i> Questions</a>
    <a href="/online_exam/teacher/results.php" class="<?php echo ($current_page == 'results.php') ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i> Results</a>
    <a href="/online_exam/teacher/reports.php" class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-file-pdf"></i> Reports</a>
    <a href="/online_exam/shared/settings.php" class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a>
<?php endif; ?>

<!-- ================= STUDENT LINKS ================= -->
<?php if ($role == 'Student'): ?>
    <a href="/online_exam/student/dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a>
    <a href="/online_exam/student/my_results.php" class="<?php echo ($current_page == 'my_results.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> My Results</a>
    <a href="/online_exam/student/profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>"><i class="fas fa-user"></i> Profile</a>
    <a href="/online_exam/shared/settings.php" class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a>
<?php endif; ?>