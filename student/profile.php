<?php
// student/profile.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/db_connect.php';
include ROOT_PATH . '/includes/header.php';

// Protect: Only Students
if ($_SESSION['role'] != 'Student') {
    header("Location: " . ROOT_PATH . "/index.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// Fetch student basic info
$student_info = $conn->query("
    SELECT u.*, s.department, s.level 
    FROM users u 
    LEFT JOIN students s ON u.user_id = s.user_id 
    WHERE u.user_id = $student_id
")->fetch_assoc();

// Fetch stats
$total_attempts = $conn->query("SELECT COUNT(*) as c FROM attempts WHERE student_id = $student_id AND status='Completed'")->fetch_assoc()['c'];
$avg_score = $conn->query("SELECT IFNULL(AVG(score), 0) as avg FROM attempts WHERE student_id = $student_id AND status='Completed'")->fetch_assoc()['avg'];
$avg_score = round($avg_score, 1);

// Calculate passed and failed
$passed = 0; $failed = 0;
$attempts = $conn->query("
    SELECT a.score, (SELECT IFNULL(SUM(marks),1) FROM questions q WHERE q.exam_id = a.exam_id) as total_marks 
    FROM attempts a WHERE a.student_id = $student_id AND a.status='Completed'
");
while ($a = $attempts->fetch_assoc()) {
    $pct = ($a['total_marks'] > 0) ? ($a['score'] / $a['total_marks']) * 100 : 0;
    if ($pct >= 50) $passed++; else $failed++;
}

// Fetch recent exams history
$history = $conn->query("
    SELECT a.*, e.title, e.subject, 
    (SELECT IFNULL(SUM(marks),1) FROM questions q WHERE q.exam_id = e.exam_id) as total_marks
    FROM attempts a 
    JOIN exams e ON a.exam_id = e.exam_id 
    WHERE a.student_id = $student_id AND a.status = 'Completed'
    ORDER BY a.end_time DESC LIMIT 5
");
?>

<div class="main-content">
    <!-- Header Banner -->
    <div class="page-header-box d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0 fw-bold text-white"><i class="fas fa-user-circle me-2"></i> My Profile</h4>
            <small class="text-white-50">View your personal information and academic performance.</small>
        </div>
        <div>
            <a href="../shared/settings.php" class="btn btn-light btn-sm fw-bold">
                <i class="fas fa-edit"></i> Edit Profile
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- LEFT: Profile Card -->
        <div class="col-lg-4">
            <div class="card p-4 text-center mb-4 profile-card-dark">
                
                <!-- PROFILE PICTURE LOGIC -->
                <div class="avatar-large mx-auto" style="overflow: hidden; display: flex; align-items: center; justify-content: center;">
                    <?php if (!empty($student_info['profile_image'])): ?>
                        <img src="../assets/uploads/<?php echo htmlspecialchars($student_info['profile_image']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($student_info['name'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                
                <h4 class="mt-3 mb-1 fw-bold text-white"><?php echo htmlspecialchars($student_info['name']); ?></h4>
                <p class="text-muted mb-2">@<?php echo htmlspecialchars($student_info['username']); ?></p>
                <span class="badge bg-primary mb-3" style="font-size: 0.85rem;">Student</span>
                
                <hr class="border-secondary">
                
                <div class="text-start small text-light">
                    <div class="info-item">
                        <i class="fas fa-envelope text-primary"></i>
                        <div>
                            <strong>Email</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($student_info['email']); ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-building text-primary"></i>
                        <div>
                            <strong>Department</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($student_info['department'] ?: 'Not set'); ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-layer-group text-primary"></i>
                        <div>
                            <strong>Level</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($student_info['level'] ?: 'Not set'); ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-calendar-alt text-primary"></i>
                        <div>
                            <strong>Joined</strong><br>
                            <span class="text-muted"><?php echo date('M d, Y', strtotime($student_info['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-circle text-success" style="font-size: 0.6rem;"></i>
                        <div>
                            <strong>Status</strong><br>
                            <span class="text-muted"><?php echo $student_info['status']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Performance & History -->
        <div class="col-lg-8">
            
            <!-- Performance Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card stats-card" style="background: linear-gradient(135deg, #1976d2, #2196f3);">
                        <h3><?php echo $total_attempts; ?></h3>
                        <small>Exams Taken</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card" style="background: linear-gradient(135deg, #6a1b9a, #ab47bc);">
                        <h3><?php echo $avg_score; ?>%</h3>
                        <small>Avg. Score</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card" style="background: linear-gradient(135deg, #2e7d32, #66bb6a);">
                        <h3><?php echo $passed; ?></h3>
                        <small>Passed</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card" style="background: linear-gradient(135deg, #c62828, #ef5350);">
                        <h3><?php echo $failed; ?></h3>
                        <small>Failed</small>
                    </div>
                </div>
            </div>

            <!-- Recent Exam History -->
            <div class="card p-4 dark-card-history">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="text-white mb-0"><i class="fas fa-history text-info me-2"></i> Recent Exam History</h5>
                    <a href="my_results.php" class="btn btn-sm btn-outline-info">View All</a>
                </div>
                
                <div class="table-responsive">
                    <table class="table custom-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Exam Title</th>
                                <th>Subject</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($history->num_rows > 0) {
                                $i = 1;
                                while ($h = $history->fetch_assoc()) {
                                    $pct = ($h['total_marks'] > 0) ? round(($h['score'] / $h['total_marks']) * 100) : 0;
                                    $is_passed = $pct >= 50;
                                    $status = $is_passed ? '<span class="badge bg-success">Passed</span>' : '<span class="badge bg-danger">Failed</span>';
                            ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($h['title']); ?></strong></td>
                                    <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($h['subject']); ?></span></td>
                                    <td><strong><?php echo $h['score']; ?>/<?php echo $h['total_marks']; ?></strong></td>
                                    <td><?php echo $pct; ?>%</td>
                                    <td><?php echo $status; ?></td>
                                </tr>
                            <?php } } else {
                                echo "<tr><td colspan='6' class='text-center text-muted py-4'>You haven't taken any exams yet.</td></tr>";
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Header Box */
    .page-header-box {
        background: linear-gradient(135deg, #0d47a1, #1976d2, #00897b);
        color: white;
        padding: 22px 30px;
        border-radius: 20px;
    }
    
    .profile-card-dark {
        background-color: #242936 !important;
        border: 1px solid #333a4a;
    }
    .dark-card-history {
        background-color: #242936 !important;
        border: 1px solid #333a4a;
    }

    .avatar-large {
        width: 100px; height: 100px; border-radius: 50%;
        background: linear-gradient(135deg, #1976d2, #64b5f6);
        color: white; display: flex; align-items: center; justify-content: center;
        font-weight: bold; font-size: 2.5rem;
        box-shadow: 0 4px 15px rgba(25, 118, 210, 0.4);
        border: 4px solid #242936;
    }
    
    .info-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 15px;
        gap: 12px;
    }
    .info-item i {
        font-size: 1.1rem;
        margin-top: 4px;
        width: 20px;
        text-align: center;
    }
    
    .stats-card {
        padding: 20px;
        border-radius: 12px;
        color: white;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .stats-card h3 {
        font-weight: 700;
        margin: 0 0 5px 0;
        font-size: 1.8rem;
    }
    .stats-card small {
        font-size: 0.8rem;
        opacity: 0.9;
    }

    /* --- TABLE STYLING (MATCHING MY_RESULTS.PHP) --- */
    .custom-table th { 
        font-weight: 700; 
        font-size: 0.75rem; 
        color: #8892b0 !important; 
        border-top: none;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 10px;
        background-color: transparent !important;
        border-bottom: 1px solid #4a5568;
    }
    
    /* Force ALL text in the row to be dark and visible */
    .custom-table td { 
        font-size: 0.88rem; 
        vertical-align: middle; 
        padding: 14px 10px;
        background-color: #ffffff !important; /* White background */
        border-bottom: 1px solid #eee;
    }

    /* Target every element inside the table cell to force dark blue text */
    .custom-table td,
    .custom-table td strong,
    .custom-table td span,
    .custom-table td small,
    .custom-table td a {
        color: #0d47a1 !important; 
    }

    /* Make the "IP" badge look nice against the dark blue text */
    .custom-table td .badge.bg-light {
        background-color: #f8f9fa !important;
        color: #555 !important;
        border: 1px solid #ddd !important;
    }

    /* Keep the Passed badge green with white text */
    .custom-table td .badge.bg-success {
        background-color: #198754 !important;
        color: #ffffff !important;
    }

    /* Hover Effect for Rows */
    .custom-table tbody tr:hover td { 
        background-color: #e3f2fd !important; 
        transform: scale(1.01);
        box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
    }

        /* FIX INVISIBLE TEXT ON PROFILE CARD */
    .profile-card-dark p,
    .profile-card-dark span,
    .profile-card-dark div {
        color: #e2e8f0 !important; /* Light gray color for readability */
    }
    
    /* Make the labels (Email, Department, etc.) white and bold */
    .profile-card-dark strong {
        color: #ffffff !important;
    }

    /* Make the icons pop */
    .profile-card-dark .info-item i {
        color: #64b5f6 !important; /* Light blue icons */
    }
</style>

<?php include ROOT_PATH . '/includes/footer.php'; ?>