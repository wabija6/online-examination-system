<?php
// teacher/publish_exam.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/db_connect.php';

// Protect: Only Teachers
if ($_SESSION['role'] != 'Teacher') {
    header("Location: " . ROOT_PATH . "/index.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$message = "";

// Fetch exam
$exam = $conn->query("SELECT * FROM exams WHERE exam_id = $exam_id AND created_by = $teacher_id")->fetch_assoc();
if (!$exam) {
    header("Location: dashboard.php");
    exit;
}

// Handle Publish Action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['publish_exam'])) {
    // Check if all questions are added
    $q_count = $conn->query("SELECT COUNT(*) as count FROM questions WHERE exam_id = $exam_id")->fetch_assoc()['count'];
    if ($q_count < $exam['total_questions']) {
        $message = "<div class='alert alert-danger'>Cannot publish! You only added $q_count out of {$exam['total_questions']} questions.</div>";
    } else {
        // Update status to Published
        $conn->query("UPDATE exams SET status = 'Published' WHERE exam_id = $exam_id");
        
        // Send Notification to all Students
        $students = $conn->query("SELECT user_id FROM users WHERE role = 'Student'");
        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $notif_msg = "A new exam '" . $exam['title'] . "' has been published. Check your dashboard!";
        while($s = $students->fetch_assoc()) {
            $notif_stmt->bind_param("is", $s['user_id'], $notif_msg);
            $notif_stmt->execute();
        }
        $notif_stmt->close();

        header("Location: dashboard.php?published=1");
        exit;
    }
}

// Fetch all questions
$questions = $conn->query("SELECT * FROM questions WHERE exam_id = $exam_id ORDER BY order_no ASC");

include ROOT_PATH . '/includes/header.php';
?>

<div class="main-content">
    <!-- Header Banner -->
    <div class="page-header-box d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0 fw-bold text-white"><i class="fas fa-check-double me-2"></i> Review & Publish</h4>
            <small class="text-white-50">Step 3 of 3: Final review before students can see this exam.</small>
        </div>
        <a href="dashboard.php" class="btn btn-light btn-sm fw-bold"><i class="fas fa-times"></i> Cancel</a>
    </div>

    <!-- WIZARD PROGRESS BAR -->
    <div class="wizard-progress mb-4">
        <div class="step active"><div class="step-icon"><i class="fas fa-check"></i></div><div class="step-text">Exam Details</div></div>
        <div class="step-line active"></div>
        <div class="step active"><div class="step-icon"><i class="fas fa-check"></i></div><div class="step-text">Add Questions</div></div>
        <div class="step-line active"></div>
        <div class="step active"><div class="step-icon">3</div><div class="step-text">Review & Publish</div></div>
    </div>

    <?php echo $message; ?>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <!-- Exam Summary Card -->
            <div class="card p-4 mb-4">
                <h5 class="text-primary mb-3"><i class="fas fa-info-circle"></i> Exam Summary</h5>
                <div class="row">
                    <div class="col-md-4 mb-2"><strong>Title:</strong> <?php echo htmlspecialchars($exam['title']); ?></div>
                    <div class="col-md-4 mb-2"><strong>Subject:</strong> <?php echo htmlspecialchars($exam['subject']); ?></div>
                    <div class="col-md-4 mb-2"><strong>Duration:</strong> <?php echo $exam['duration_minutes']; ?> min</div>
                    <div class="col-md-4 mb-2"><strong>Total Questions:</strong> <?php echo $exam['total_questions']; ?></div>
                    <div class="col-md-4 mb-2"><strong>Start:</strong> <?php echo date('M d, Y H:i', strtotime($exam['start_time'])); ?></div>
                    <div class="col-md-4 mb-2"><strong>End:</strong> <?php echo date('M d, Y H:i', strtotime($exam['end_time'])); ?></div>
                </div>
            </div>

            <!-- Questions Review List -->
            <div class="card p-4 mb-4">
                <h5 class="text-primary mb-3"><i class="fas fa-list"></i> Questions (<?php echo $questions->num_rows; ?>)</h5>
                
                <?php if($questions->num_rows > 0): ?>
                    <?php $i = 1; while($q = $questions->fetch_assoc()): ?>
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>Q<?php echo $i; ?>. <?php echo htmlspecialchars($q['question_text']); ?></strong>
                                <span class="badge bg-primary"><?php echo $q['marks']; ?> Mark(s)</span>
                            </div>
                            <?php
                            $opts = $conn->query("SELECT * FROM options WHERE question_id = {$q['question_id']} ORDER BY order_no ASC");
                            while($opt = $opts->fetch_assoc()) {
                                $is_correct = $opt['is_correct'];
                                $badge = $is_correct ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-circle text-muted" style="font-size:0.5rem;"></i>';
                                $class = $is_correct ? 'text-success fw-bold' : 'text-muted';
                                echo "<div class='{$class} mb-1'>$badge " . htmlspecialchars($opt['option_text']) . "</div>";
                            }
                            ?>
                        </div>
                    <?php $i++; endwhile; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-4">No questions found.</p>
                <?php endif; ?>
            </div>

            <!-- Publish Action -->
            <div class="card p-4 text-center bg-white border-primary" style="border-width: 2px;">
                <h5 class="text-primary mb-2"><i class="fas fa-rocket"></i> Ready to Publish?</h5>
                <p class="text-muted mb-4">Once published, students will see this exam on their dashboard and receive a notification.</p>
                
                <form method="POST" action="">
                    <a href="build_exam.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-outline-secondary me-2"><i class="fas fa-arrow-left"></i> Go Back & Edit</a>
                    <button type="submit" name="publish_exam" class="btn btn-success px-5 py-2 fs-5" onclick="return confirm('Are you sure you want to publish this exam? Students will be able to see it immediately.');">
                        <i class="fas fa-check-circle me-2"></i> Publish Exam
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .page-header-box { background: linear-gradient(135deg, #0d47a1, #1976d2, #00897b); padding: 22px 30px; border-radius: 20px; }
    .wizard-progress { display: flex; justify-content: space-between; align-items: center; max-width: 800px; margin: 0 auto; padding: 20px; background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .step { display: flex; flex-direction: column; align-items: center; opacity: 0.5; }
    .step.active { opacity: 1; }
    .step-icon { width: 40px; height: 40px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; margin-bottom: 8px; }
    .step.active .step-icon { background: #1976d2; color: white; }
    .step-text { font-size: 0.85rem; font-weight: 600; color: #64748b; }
    .step.active .step-text { color: #1976d2; }
    .step-line { flex: 1; height: 3px; background: #e2e8f0; margin: 0 15px; margin-bottom: 25px; }
    .step-line.active { background: #1976d2; }
</style>

<?php include ROOT_PATH . '/includes/footer.php'; ?>