<?php
// student/view_result.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/db_connect.php';
include ROOT_PATH . '/includes/header.php';

if ($_SESSION['role'] != 'Student') {
    header("Location: " . ROOT_PATH . "/index.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$attempt_id = isset($_GET['attempt_id']) ? intval($_GET['attempt_id']) : 0;

$attempt = $conn->query("SELECT a.*, e.title, e.subject FROM attempts a JOIN exams e ON a.exam_id = e.exam_id WHERE a.attempt_id = $attempt_id AND a.student_id = $student_id")->fetch_assoc();

if (!$attempt) {
    header("Location: dashboard.php");
    exit;
}

$total_marks = $conn->query("SELECT IFNULL(SUM(marks), 0) as t FROM questions WHERE exam_id = {$attempt['exam_id']}")->fetch_assoc()['t'];
$percent = ($total_marks > 0) ? round(($attempt['score'] / $total_marks) * 100) : 0;
$passed = $percent >= 50;
?>

<?php include ROOT_PATH . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="card p-5 text-center mb-4">
        <?php if ($passed): ?>
            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
            <h2 class="text-success fw-bold mt-3">Congratulations! You Passed! 🎉</h2>
        <?php else: ?>
            <i class="fas fa-times-circle text-danger" style="font-size: 4rem;"></i>
            <h2 class="text-danger fw-bold mt-3">Better Luck Next Time</h2>
        <?php endif; ?>
        <p class="text-muted"><?php echo htmlspecialchars($attempt['title']); ?></p>

        <div class="row justify-content-center mt-4">
            <div class="col-md-3">
                <div class="result-stat" style="background: linear-gradient(135deg, #1976d2, #2196f3);">
                    <small>Score</small>
                    <h3><?php echo $attempt['score']; ?>/<?php echo $total_marks; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="result-stat" style="background: linear-gradient(135deg, <?php echo $passed ? '#2e7d32, #66bb6a' : '#c62828, #ef5350'; ?>);">
                    <small>Percentage</small>
                    <h3><?php echo $percent; ?>%</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="result-stat" style="background: linear-gradient(135deg, <?php echo $passed ? '#2e7d32, #66bb6a' : '#c62828, #ef5350'; ?>);">
                    <small>Status</small>
                    <h3><?php echo $passed ? 'PASSED' : 'FAILED'; ?></h3>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="dashboard.php" class="btn btn-primary"><i class="fas fa-home"></i> Back to Dashboard</a>
            <a href="my_results.php" class="btn btn-outline-primary"><i class="fas fa-list"></i> All My Results</a>
        </div>
    </div>
</div>

<style>
    .result-stat { padding: 20px; border-radius: 12px; color: white; text-align: center; margin: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .result-stat small { font-size: 0.85rem; opacity: 0.9; display: block; margin-bottom: 5px; }
    .result-stat h3 { font-weight: 700; margin: 0; font-size: 1.8rem; }
</style>

<?php include ROOT_PATH . '/includes/footer.php'; ?>