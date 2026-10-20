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

// --- FETCH AVAILABLE EXAMS ---
$available_sql = "SELECT e.*, 
    (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.exam_id) as total_q,
    (SELECT COUNT(*) FROM attempts a WHERE a.exam_id = e.exam_id AND a.student_id = $student_id AND a.status='Completed') as attempted
    FROM exams e
    WHERE NOW() BETWEEN e.start_time AND e.end_time
    ORDER BY e.start_time ASC";
$available_exams = $conn->query($available_sql);

// --- FETCH PAST RESULTS ---
$past_sql = "SELECT a.*, e.title,
    (SELECT IFNULL(SUM(marks), 0) FROM questions q WHERE q.exam_id = e.exam_id) as total_marks
    FROM attempts a 
    JOIN exams e ON a.exam_id = e.exam_id
    WHERE a.student_id = $student_id AND a.status = 'Completed'
    ORDER BY a.end_time DESC LIMIT 4";
$past_results = $conn->query($past_sql);

// Summary counts
$total_attempts = $conn->query("SELECT COUNT(*) as c FROM attempts WHERE student_id = $student_id AND status='Completed'")->fetch_assoc()['c'];
$avg_score = $conn->query("SELECT IFNULL(AVG(score), 0) as avg FROM attempts WHERE student_id = $student_id AND status='Completed'")->fetch_assoc()['avg'];
$avg_score = round($avg_score, 1);
?>


<div class="main-content">
    
    <!-- COLORFUL GRADIENT HEADER -->
    <div class="student-hero mb-4">
        <div class="hero-blur-bg"></div>
        <div class="hero-content position-relative" style="z-index: 2;">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h3 class="fw-bold mb-1 text-white"><i class="fas fa-graduation-cap me-2"></i> Student Dashboard</h3>
                    <p class="text-white-50 mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>! Let's achieve great things today.</p>
                </div>
                <div class="mt-3 mt-md-0">
                    <a href="my_results.php" class="btn btn-light fw-bold px-4 py-2" style="border-radius: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                        <i class="fas fa-chart-line text-success me-1"></i> View My Results
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- SUMMARY CARDS (Now Clickable) -->
    <div class="row g-4 mb-4 animate-up">
        <div class="col-md-4">
            <a href="my_results.php" class="text-decoration-none">
                <div class="card summary-card h-100" style="background: linear-gradient(135deg, #1976d2, #2196f3);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small>Exams Taken</small>
                            <h2 class="fw-bold mb-0"><?php echo $total_attempts; ?></h2>
                        </div>
                        <i class="fas fa-file-alt fa-3x" style="opacity: 0.3;"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="my_results.php" class="text-decoration-none">
                <div class="card summary-card h-100" style="background: linear-gradient(135deg, #2e7d32, #66bb6a);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small>Average Score</small>
                            <h2 class="fw-bold mb-0"><?php echo $avg_score; ?>%</h2>
                        </div>
                        <i class="fas fa-star fa-3x" style="opacity: 0.3;"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="#available-exams" class="text-decoration-none">
                <div class="card summary-card h-100" style="background: linear-gradient(135deg, #6a1b9a, #ab47bc);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small>Available Now</small>
                            <h2 class="fw-bold mb-0"><?php echo $available_exams->num_rows; ?></h2>
                        </div>
                        <i class="fas fa-bolt fa-3x" style="opacity: 0.3;"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT ROW -->
    <div class="row g-4">
        <!-- LEFT: AVAILABLE EXAMS -->
        <div class="col-lg-8 animate-up" style="animation-delay: 0.1s;">
            <div class="card p-4" style="min-height: 400px;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold text-primary mb-0"><i class="fas fa-play-circle me-2"></i> Available Exams</h5>
                </div>

                <?php if ($available_exams->num_rows > 0): ?>
                    <div class="row g-3">
                        <?php while ($exam = $available_exams->fetch_assoc()): ?>
                            <div class="col-md-12">
                                <div class="exam-card-new">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-primary bg-opacity-10 text-primary mb-2"><?php echo htmlspecialchars($exam['subject']); ?></span>
                                            <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($exam['title']); ?></h5>
                                            <div class="text-muted small">
                                                <span class="me-3"><i class="far fa-clock text-primary"></i> <?php echo $exam['duration_minutes']; ?> mins</span>
                                                <span><i class="fas fa-question-circle text-primary"></i> <?php echo $exam['total_q']; ?> questions</span>
                                            </div>
                                        </div>
                                        <div>
                                            <?php if ($exam['attempted'] > 0): ?>
                                                <button class="btn btn-secondary" disabled><i class="fas fa-check"></i> Done</button>
                                            <?php elseif ($exam['total_q'] == 0): ?>
                                                <button class="btn btn-warning text-dark" disabled><i class="fas fa-clock"></i> Pending</button>
                                            <?php else: ?>
                                                <a href="take_exam.php?exam_id=<?php echo $exam['exam_id']; ?>" class="btn btn-primary px-4 py-2" style="border-radius: 20px; box-shadow: 0 4px 10px rgba(25,118,210,0.3);">
                                                    Start <i class="fas fa-arrow-right ms-1"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-coffee text-muted mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
                        <h5 class="text-muted">No exams available right now.</h5>
                        <p class="text-muted small">Check back later!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT: RECENT RESULTS & TIPS -->
        <div class="col-lg-4 animate-up" style="animation-delay: 0.2s;">
            <div class="card p-4 mb-4">
                <h5 class="fw-bold text-primary mb-3"><i class="fas fa-chart-line me-2"></i> Recent Results</h5>
                <div class="result-list">
                    <?php
                    if ($past_results->num_rows > 0) {
                        while ($r = $past_results->fetch_assoc()) {
                            $percent = ($r['total_marks'] > 0) ? round(($r['score'] / $r['total_marks']) * 100) : 0;
                            $color = ($percent >= 50) ? 'success' : 'danger';
                    ?>
                        <div class="result-item d-flex justify-content-between align-items-center p-3 mb-2 rounded" style="background: #f8f9fa;">
                            <div>
                                <h6 class="fw-bold mb-1" style="font-size: 0.9rem;"><?php echo htmlspecialchars($r['title']); ?></h6>
                                <small class="text-muted" style="font-size: 0.75rem;"><?php echo date('M d, Y', strtotime($r['end_time'])); ?></small>
                            </div>
                            <span class="badge bg-<?php echo $color; ?> rounded-pill" style="font-size: 0.9rem; padding: 6px 12px;"><?php echo $percent; ?>%</span>
                        </div>
                    <?php } } else {
                        echo "<p class='text-muted small text-center'>No results yet.</p>";
                    } ?>
                </div>
            </div>

            <div class="card p-4" style="background: #e3f2fd; border: none;">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-lightbulb text-warning me-2"></i> Exam Tips</h6>
                <ul class="list-unstyled mb-0 small text-muted" style="line-height: 1.8;">
                    <li><i class="fas fa-check-circle text-success me-2"></i> Read questions carefully.</li>
                    <li><i class="fas fa-check-circle text-success me-2"></i> Watch the timer.</li>
                    <li><i class="fas fa-check-circle text-success me-2"></i> Submit before time runs out!</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
    /* --- COLORFUL BLUR HERO SECTION --- */
    .student-hero {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .hero-blur-bg {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(135deg, #0d47a1, #1976d2, #00897b);
        z-index: 1;
    }
    
    .hero-blur-bg::before, .hero-blur-bg::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        filter: blur(40px);
    }
    .hero-blur-bg::before {
        width: 300px; height: 300px;
        background: rgba(100, 181, 246, 0.4);
        top: -100px; left: -50px;
    }
    .hero-blur-bg::after {
        width: 250px; height: 250px;
        background: rgba(0, 137, 123, 0.4);
        bottom: -80px; right: -50px;
    }

    /* --- CARDS --- */
    .summary-card {
        padding: 20px;
        color: white;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        transition: transform 0.3s;
    }
    .summary-card:hover { transform: translateY(-5px); }
    .summary-card small { font-size: 0.85rem; font-weight: 600; text-transform: uppercase; opacity: 0.9; }

    /* --- EXAM LIST CARD --- */
    .exam-card-new {
        background: #f8f9fc;
        border: 2px solid #e3e8f0;
        border-radius: 15px;
        padding: 20px;
        transition: 0.3s;
    }
    .exam-card-new:hover {
        border-color: #1976d2;
        background: white;
        box-shadow: 0 5px 15px rgba(25, 118, 210, 0.1);
    }

    /* Animations */
    .animate-up {
        animation: slideUp 0.6s ease-out forwards;
        opacity: 0;
        transform: translateY(20px);
    }
    @keyframes slideUp {
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<?php include ROOT_PATH . '/includes/footer.php'; ?>