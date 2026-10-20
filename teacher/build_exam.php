<?php
// teacher/build_exam.php
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

// Fetch exam details
$exam_query = $conn->query("SELECT * FROM exams WHERE exam_id = $exam_id AND created_by = $teacher_id");

if (!$exam_query || $exam_query->num_rows === 0) {
    // If the exam doesn't exist, send them back to dashboard
    header("Location: dashboard.php?error=exam_not_found");
    exit;
}

$exam = $exam_query->fetch_assoc();

// Get current question count
$q_count_result = $conn->query("SELECT COUNT(*) as count FROM questions WHERE exam_id = $exam_id");
$current_q_count = $q_count_result ? $q_count_result->fetch_assoc()['count'] : 0;
$total_needed = isset($exam['total_questions']) && $exam['total_questions'] > 0 ? $exam['total_questions'] : 1;

// --- HANDLE ADDING A QUESTION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_question'])) {
    if ($current_q_count >= $total_needed) {
        $message = "<div class='alert alert-warning'>You have already added all required questions.</div>";
    } else {
        $q_text = trim($_POST['question_text']);
        $opt_a = trim($_POST['option_a']);
        $opt_b = trim($_POST['option_b']);
        $opt_c = trim($_POST['option_c']);
        $opt_d = trim($_POST['option_d']);
        $correct = $_POST['correct_option'];
        $marks = intval($_POST['marks']);

        if (empty($q_text) || empty($opt_a) || empty($opt_b) || empty($opt_c) || empty($opt_d) || empty($correct)) {
            $message = "<div class='alert alert-danger'>Please fill in all fields.</div>";
        } else {
            $order_no = $current_q_count + 1;
            
            // Insert Question
            $stmt = $conn->prepare("INSERT INTO questions (exam_id, question_text, question_type, marks, order_no) VALUES (?, ?, 'MCQ', ?, ?)");
            $stmt->bind_param("isii", $exam_id, $q_text, $marks, $order_no);
            if($stmt->execute()) {
                $new_q_id = $stmt->insert_id;
                $stmt->close();

                // Insert Options
                $options = ['A' => $opt_a, 'B' => $opt_b, 'C' => $opt_c, 'D' => $opt_d];
                $opt_stmt = $conn->prepare("INSERT INTO options (question_id, option_text, is_correct, order_no) VALUES (?, ?, ?, ?)");
                $o_order = 1;
                foreach ($options as $key => $text) {
                    $is_correct = ($key == $correct) ? 1 : 0;
                    $opt_stmt->bind_param("isii", $new_q_id, $text, $is_correct, $o_order);
                    $opt_stmt->execute();
                    $o_order++;
                }
                $opt_stmt->close();

                $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Question added successfully!</div>";
                $current_q_count++;
            } else {
                $message = "<div class='alert alert-danger'>Database error: " . $conn->error . "</div>";
            }
        }
    }
}

// --- HANDLE DELETING A QUESTION ---
if (isset($_GET['delete_q'])) {
    $del_q_id = intval($_GET['delete_q']);
    $check = $conn->query("SELECT question_id FROM questions WHERE question_id = $del_q_id AND exam_id = $exam_id");
    if ($check && $check->num_rows > 0) {
        $conn->query("DELETE FROM questions WHERE question_id = $del_q_id");
        $message = "<div class='alert alert-success'>Question deleted.</div>";
        $current_q_count = $conn->query("SELECT COUNT(*) as count FROM questions WHERE exam_id = $exam_id")->fetch_assoc()['count'];
    }
}

// Fetch all existing questions for this exam
$questions = $conn->query("SELECT * FROM questions WHERE exam_id = $exam_id ORDER BY order_no ASC");
$is_complete = ($current_q_count >= $total_needed);

// --- NOW INCLUDE THE HEADER ---
include ROOT_PATH . '/includes/header.php';
?>

<div class="main-content">
    <!-- Header Banner -->
    <div class="page-header-box d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0 fw-bold text-white"><i class="fas fa-tools me-2"></i> Build Exam: <?php echo htmlspecialchars($exam['title']); ?></h4>
            <small class="text-white-50">Step 2 of 3: Add your questions (Draft Mode)</small>
        </div>
        <a href="dashboard.php" class="btn btn-light btn-sm fw-bold"><i class="fas fa-save"></i> Save Draft & Exit</a>
    </div>

    <!-- WIZARD PROGRESS BAR -->
    <div class="wizard-progress mb-4">
        <div class="step active"><div class="step-icon"><i class="fas fa-check"></i></div><div class="step-text">Exam Details</div></div>
        <div class="step-line active"></div>
        <div class="step active"><div class="step-icon">2</div><div class="step-text">Add Questions</div></div>
        <div class="step-line <?php echo $is_complete ? 'active' : ''; ?>"></div>
        <div class="step <?php echo $is_complete ? 'active' : ''; ?>"><div class="step-icon">3</div><div class="step-text">Review & Publish</div></div>
    </div>

    <?php echo $message; ?>

    <div class="row g-4">
        <!-- LEFT: QUESTION NAVIGATOR -->
        <div class="col-lg-3">
            <div class="card p-4 sticky-top" style="top: 90px;">
                <h6 class="fw-bold mb-3"><i class="fas fa-list-ol text-primary"></i> Question Progress</h6>
                <div class="progress mb-3" style="height: 10px;">
                    <div class="progress-bar bg-success" style="width: <?php echo ($total_needed > 0) ? ($current_q_count / $total_needed) * 100 : 0; ?>%;"></div>
                </div>
                <p class="text-muted small mb-3">Added: <strong><?php echo $current_q_count; ?></strong> / <?php echo $total_needed; ?></p>
                
                <div class="question-nav-grid">
                    <?php for($i = 1; $i <= $total_needed; $i++): ?>
                        <?php if($i <= $current_q_count): ?>
                            <div class="q-nav-box completed"><i class="fas fa-check"></i></div>
                        <?php else: ?>
                            <div class="q-nav-box pending"><?php echo $i; ?></div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>

                <?php if($is_complete): ?>
                    <div class="alert alert-success mt-4 text-center py-2 small">
                        <i class="fas fa-check-circle"></i> All questions added!<br>
                        <a href="publish_exam.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-success btn-sm mt-2 w-100">Go to Step 3 <i class="fas fa-arrow-right"></i></a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mt-4 text-center py-2 small">
                        <i class="fas fa-info-circle"></i> Add <?php echo ($total_needed - $current_q_count); ?> more question(s) to publish.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT: ADD QUESTION FORM -->
        <div class="col-lg-9">
            <?php if(!$is_complete): ?>
            <div class="card p-4 mb-4">
                <h5 class="text-primary mb-3 border-bottom pb-2">Add Question <?php echo $current_q_count + 1; ?></h5>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Question Text *</label>
                        <textarea name="question_text" class="form-control" rows="3" required placeholder="Type the question here..."></textarea>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6"><div class="input-group"><span class="input-group-text fw-bold">A</span><input type="text" name="option_a" class="form-control" required></div></div>
                        <div class="col-md-6"><div class="input-group"><span class="input-group-text fw-bold">B</span><input type="text" name="option_b" class="form-control" required></div></div>
                        <div class="col-md-6"><div class="input-group"><span class="input-group-text fw-bold">C</span><input type="text" name="option_c" class="form-control" required></div></div>
                        <div class="col-md-6"><div class="input-group"><span class="input-group-text fw-bold">D</span><input type="text" name="option_d" class="form-control" required></div></div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Correct Answer *</label>
                            <select name="correct_option" class="form-select" required>
                                <option value="">Select Option</option>
                                <option value="A">Option A</option><option value="B">Option B</option>
                                <option value="C">Option C</option><option value="D">Option D</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Marks *</label>
                            <input type="number" name="marks" class="form-control" value="1" min="1" required>
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" name="add_question" class="btn btn-primary px-4"><i class="fas fa-save"></i> Save Question</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- LIST OF ADDED QUESTIONS -->
            <div class="card p-4">
                <h5 class="text-primary mb-3"><i class="fas fa-list"></i> Added Questions</h5>
                <?php if($questions && $questions->num_rows > 0): ?>
                    <?php $i = 1; while($q = $questions->fetch_assoc()): ?>
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="d-flex justify-content-between">
                                <strong>Q<?php echo $i; ?>. <?php echo htmlspecialchars($q['question_text']); ?></strong>
                                <a href="build_exam.php?exam_id=<?php echo $exam_id; ?>&delete_q=<?php echo $q['question_id']; ?>" class="text-danger" onclick="return confirm('Delete this question?');"><i class="fas fa-trash"></i></a>
                            </div>
                            <?php
                            $opts = $conn->query("SELECT * FROM options WHERE question_id = {$q['question_id']} ORDER BY order_no ASC");
                            if($opts) {
                                while($opt = $opts->fetch_assoc()) {
                                    $check = $opt['is_correct'] ? 'text-success fw-bold' : 'text-muted';
                                    echo "<div class='small {$check}'><i class='fas fa-circle' style='font-size:0.5rem;'></i> " . htmlspecialchars($opt['option_text']) . "</div>";
                                }
                            }
                            ?>
                        </div>
                    <?php $i++; endwhile; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-4">No questions added yet.</p>
                <?php endif; ?>
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
    .question-nav-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
    .q-nav-box { aspect-ratio: 1; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-weight: bold; font-size: 0.9rem; }
    .q-nav-box.completed { background: #2e7d32; color: white; }
    .q-nav-box.pending { background: #f1f5f9; color: #64748b; border: 1px dashed #cbd5e1; }
</style>

<?php include ROOT_PATH . '/includes/footer.php'; ?>