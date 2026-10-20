<?php
// teacher/create_exam.php
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
$message = "";

// --- HANDLE EXAM CREATION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_exam_step1'])) {
    $title = trim($_POST['title']);
    $subject = trim($_POST['subject']);
    $duration = intval($_POST['duration']);
    $total_questions = intval($_POST['total_questions']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $description = trim($_POST['description']);

    if (empty($title) || empty($subject) || empty($duration) || empty($total_questions) || empty($start_time) || empty($end_time)) {
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Please fill in all required fields.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO exams (title, subject, exam_type, duration_minutes, total_questions, start_time, end_time, description, created_by, status) VALUES (?, ?, 'MCQ', ?, ?, ?, ?, ?, ?, 'Draft')");
        $stmt->bind_param("ssiisssi", $title, $subject, $duration, $total_questions, $start_time, $end_time, $description, $teacher_id);
        
        if ($stmt->execute()) {
            $new_exam_id = $stmt->insert_id;
            // Redirect to the builder page
            header("Location: build_exam.php?exam_id=" . $new_exam_id);
            exit;
        } else {
            die("SQL ERROR: " . $conn->error);
        }
        $stmt->close();
    }
}

// --- NOW INCLUDE THE HEADER ---
include ROOT_PATH . '/includes/header.php';
?>

<div class="main-content">
    <!-- Header Banner -->
    <div class="page-header-box d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0 fw-bold text-white"><i class="fas fa-magic me-2"></i> Create New Exam</h4>
            <small class="text-white-50">Step 1 of 3: Set up the basic exam details.</small>
        </div>
        <a href="dashboard.php" class="btn btn-light btn-sm fw-bold"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <?php echo $message; ?>

    <!-- WIZARD PROGRESS BAR -->
    <div class="wizard-progress mb-4">
        <div class="step active">
            <div class="step-icon">1</div>
            <div class="step-text">Exam Details</div>
        </div>
        <div class="step-line"></div>
        <div class="step">
            <div class="step-icon">2</div>
            <div class="step-text">Add Questions</div>
        </div>
        <div class="step-line"></div>
        <div class="step">
            <div class="step-icon">3</div>
            <div class="step-text">Review & Publish</div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card p-5">
                <h5 class="text-primary mb-4 border-bottom pb-3"><i class="fas fa-info-circle"></i> Basic Information</h5>
                
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Exam Title *</label>
                            <input type="text" name="title" class="form-control form-control-lg" placeholder="e.g., Final Mathematics Exam" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Subject *</label>
                            <input type="text" name="subject" class="form-control" placeholder="e.g., Mathematics" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Exam Type</label>
                            <input type="text" class="form-control" value="Multiple Choice (MCQ)" disabled>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Duration (Minutes) *</label>
                            <input type="number" name="duration" class="form-control" placeholder="e.g., 60" min="1" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-danger">Total Number of Questions *</label>
                            <input type="number" name="total_questions" class="form-control border-danger" placeholder="e.g., 10" min="1" required>
                            <small class="text-muted">You must add exactly this many questions in Step 2.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Start Date & Time *</label>
                            <input type="datetime-local" name="start_time" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">End Date & Time *</label>
                            <input type="datetime-local" name="end_time" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Description (Optional)</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Enter instructions for students..."></textarea>
                        </div>
                    </div>

                    <div class="alert alert-info mt-4 py-2">
                        <i class="fas fa-info-circle me-1"></i> This exam will be saved as a <strong>Draft</strong>. Students will not see it until you publish it in Step 3.
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" name="create_exam_step1" class="btn btn-primary btn-lg px-5">
                            Next: Add Questions <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .page-header-box { background: linear-gradient(135deg, #0d47a1, #1976d2, #00897b); padding: 22px 30px; border-radius: 20px; }
    .wizard-progress { display: flex; justify-content: space-between; align-items: center; max-width: 800px; margin: 0 auto; padding: 20px; background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .step { display: flex; flex-direction: column; align-items: center; opacity: 0.5; transition: 0.3s; }
    .step.active { opacity: 1; }
    .step-icon { width: 40px; height: 40px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; margin-bottom: 8px; }
    .step.active .step-icon { background: #1976d2; color: white; box-shadow: 0 0 15px rgba(25, 118, 210, 0.4); }
    .step-text { font-size: 0.85rem; font-weight: 600; color: #64748b; }
    .step.active .step-text { color: #1976d2; }
    .step-line { flex: 1; height: 3px; background: #e2e8f0; margin: 0 15px; margin-bottom: 25px; }
</style>

<?php include ROOT_PATH . '/includes/footer.php'; ?>