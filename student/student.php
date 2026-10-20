<?php
// student/dashboard.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/db_connect.php';
include ROOT_PATH . '/includes/header.php';

// Protect the page: Only Students
if ($_SESSION['role'] != 'Student') {
    header("Location: " . ROOT_PATH . "/index.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$now = date('Y-m-d H:i:s');

// --- FETCH AVAILABLE EXAMS (Currently Active) ---
$available_sql = "SELECT e.*, 
    (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.exam_id) as total_q,
    (SELECT COUNT(*) FROM attempts a WHERE a.exam_id = e.exam_id AND a.student_id = $student_id AND a.status='Completed') as attempted
    FROM exams e
    WHERE NOW() BETWEEN e.start_time AND e.end_time
    ORDER BY e.start_time ASC";
$available_exams = $conn->query($available_sql);

// --- FETCH UPCOMING EXAMS ---
$upcoming_sql = "SELECT e.*, 
    (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.exam_id) as total_q
    FROM exams e
    WHERE e.start_time > NOW()
    ORDER BY e.start_time ASC LIMIT 5";
$upcoming_exams = $conn->query($upcoming_sql);

// --- FETCH PAST RESULTS (Completed Attempts) ---
$past_sql = "SELECT a.*, e.title, e.subject,
    (SELECT SUM(marks) FROM questions q WHERE q.exam_id = e.exam_id) as total_marks
    FROM attempts a 
    JOIN exams e ON a.exam_id = e.exam_id
    WHERE a.student_id = $student_id AND a.status = 'Completed'
    ORDER BY a.end_time DESC LIMIT 5";
$past_results = $conn->query($past_sql);

// Summary counts
$total_attempts = $conn->query("SELECT COUNT(*) as c FROM attempts WHERE student_id = $student_id AND status='Completed'")->fetch_assoc()['c'];
$avg_score = $conn->query("SELECT IFNULL(AVG(score), 0) as avg FROM attempts WHERE student_id = $student_id AND status='Completed'")->fetch_assoc()['avg'];
$avg_score = round($avg_score, 1);
?>

<?php include ROOT_PATH . '/includes/sidebar.php'; ?>

<div class="main-content">
    <!-- Header Banner -->
    <div class="page-header-box d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0 fw-bold"><i class="fas fa-graduation-cap me-2"></i> Student Dashboard</h4>
            <small>Welcome back! Here are your exams and results.</small>
        </div>
        <div class="text-end">
            <small><i class="fas fa-clock"></i> <?php echo date('D, M d, Y | h:i A'); ?></small>
        </div>
    </div>

    <!-- Welcome Message -->
    <div class="mb-4">
        <h4 class="fw-bold">👋 Hello, <?php echo $_SESSION['name']; ?>!</h4>
        <p class="text-muted">Ready to take your exams? Check out what's available below.</p>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card summary-card" style="background: linear-gradient(135deg, #1976d2, #2196f3);">
                <div>
                    <small>Exams Taken</small>
                    <h3><?php echo $total_attempts; ?></h3>
                </div>
                <i class="fas fa-file-alt"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card" style="background: linear-gradient(135deg, #2e7d32, #66bb6a);">
                <div>
                    <small>Average Score</small>
                    <h3><?php echo $avg_score; ?></h3>
                </div>
                <i class="fas fa-star"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card" style="background: linear-gradient(135deg, #6a1b9a, #ab47bc);">
                <div>
                    <small>Available Now</small>
                    <h3><?php echo $available_exams->num_rows; ?></h3>
                </div>
                <i class="fas fa-bolt"></i>
            </div>
        </div>
    </div>

    <!-- Main Row: Available Exams + Tips -->
    <div class="row g-4">
        <div class="col-lg-9">
            <!-- Available Exams -->
            <div class="card p-4 mb-4">
                <h5 class="text-primary mb-3"><i class="fas fa-play-circle"></i> Available Exams</h5>
                <?php if ($available_exams->num_rows > 0): ?>
                    <div class="row g-3">
                        <?php while ($exam = $available_exams->fetch_assoc()): ?>
                            <div class="col-md-6">
                                <div class="exam-card">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($exam['title']); ?></h6>
                                        <span class="badge bg-success">Active</span>
                                    </div>
                                    <p class="text-muted small mb-2">
                                        <i class="fas fa-book"></i> <?php echo htmlspecialchars($exam['subject']); ?>
                                    </p>
                                    <div class="d-flex justify-content-between small text-muted mb-3">
                                        <span><i class="fas fa-clock"></i> <?php echo $exam['duration_minutes']; ?> min</span>
                                        <span><i class="fas fa-question-circle"></i> <?php echo $exam['total_q']; ?> questions</span>
                                    </div>
                                    <?php if ($exam['attempted'] > 0): ?>
                                        <button class="btn btn-secondary w-100 btn-sm" disabled>
                                            <i class="fas fa-check-circle"></i> Already Attempted
                                        </button>
                                    <?php elseif ($exam['total_q'] == 0): ?>
                                        <button class="btn btn-warning w-100 btn-sm" disabled>
                                            <i class="fas fa-exclamation-triangle"></i> No Questions Yet
                                        </button>
                                    <?php else: ?>
                                        <a href="take_exam.php?exam_id=<?php echo $exam['exam_id']; ?>" class="btn btn-primary w-100 btn-sm">
                                            <i class="fas fa-play"></i> Start Exam
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                        <p class="text-muted mb-0">No exams are currently active. Check back later!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Past Results -->
            <div class="card p-4">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="text-primary mb-0"><i class="fas fa-chart-line"></i> My Recent Results</h5>
                    <a href="my_results.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <table class="table table-hover align-middle mb-0">
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
                        if ($past_results->num_rows > 0) {
                            $i = 1;
                            while ($r = $past_results->fetch_assoc()) {
                                $percent = ($r['total_marks'] > 0) ? round(($r['score'] / $r['total_marks']) * 100) : 0;
                                $passed = $percent >= 50;
                                $status = $passed ? '<span class="badge bg-success">Passed</span>' : '<span class="badge bg-danger">Failed</span>';
                        ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td><?php echo htmlspecialchars($r['title']); ?></td>
                                <td><?php echo htmlspecialchars($r['subject']); ?></td>
                                <td><strong><?php echo $r['score']; ?>/<?php echo $r['total_marks']; ?></strong></td>
                                <td><?php echo $percent; ?>%</td>
                                <td><?php echo $status; ?></td>
                            </tr>
                        <?php $i++; } } else {
                            echo "<tr><td colspan='6' class='text-center text-muted py-4'>You haven't taken any exams yet.</td></tr>";
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Sidebar: Tips -->
        <div class="col-lg-3">
            <div class="card p-4 mb-3">
                <h6 class="fw-bold mb-3"><i class="fas fa-lightbulb text-warning"></i> Exam Tips</h6>
                <ol class="tips-list small">
                    <li>Read each question carefully before answering.</li>
                    <li>You can navigate between questions using the question navigator.</li>
                    <li>Your answers are saved when you click <strong>Next</strong>.</li>
                    <li>Submit before the timer runs out!</li>
                    <li>Your results will show instantly after submission.</li>
                </ol>
            </div>

            <div class="card p-3 mb-3" style="background:#e8f5e9;">
                <div class="d-flex align-items-start">
                    <i class="fas fa-shield-alt text-success me-2 mt-1" style="font-size:1.2rem;"></i>
                    <div>
                        <strong class="text-success small d-block mb-1">Exam Rules</strong>
                        <small class="text-muted">
                            • One attempt per exam<br>
                            • Timer cannot be paused<br>
                            • Auto-submits when time runs out
                        </small>
                    </div>
                </div>
            </div>

            <div class="text-center py-3">
                <div style="font-size:2.5rem; color:#1976d2;">📚</div>
                <p class="text-primary fst-italic fw-bold mt-2 mb-0" style="font-size:0.9rem;">
                    Good Luck!<br>You Can Do It! 💪
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    .page-header-box {
        background: linear-gradient(135deg, #0d47a1, #1976d2);
        color: white;
        padding: 22px 30px;
        border-radius: 12px;
    }
    .page-header-box small { opacity: 0.9; }
    .summary-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        color: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .summary-card small { font-size: 0.8rem; opacity: 0.9; display: block; margin-bottom: 2px; }
    .summary-card h3 { font-weight: 700; margin: 0; font-size: 1.8rem; }
    .summary-card i { font-size: 2.2rem; opacity: 0.35; }

    .exam-card {
        background: #f8f9fc;
        border: 2px solid #e3e8f0;
        padding: 15px;
        border-radius: 12px;
        transition: 0.3s;
    }
    .exam-card:hover {
        border-color: #1976d2;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(25, 118, 210, 0.15);
    }

    .table th { font-weight: 600; font-size: 0.8rem; color: #555; border-top: none; text-transform: uppercase; padding: 10px 8px; }
    .table td { font-size: 0.88rem; vertical-align: middle; padding: 12px 8px; }
    .table tbody tr:hover { background-color: #f8f9fc; }
    .tips-list { padding-left: 18px; margin-bottom: 0; }
    .tips-list li { margin-bottom: 8px; color: #555; line-height: 1.4; }
    .badge { font-size: 0.75rem; padding: 5px 10px; font-weight: 500; }
</style>

<?php include ROOT_PATH . '/includes/footer.php'; ?>