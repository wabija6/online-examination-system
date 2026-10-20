<?php
// teacher/questions.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/db_connect.php';
include ROOT_PATH . '/includes/header.php';

// Protect the page: Only Teachers
if ($_SESSION['role'] != 'Teacher') {
    header("Location: " . ROOT_PATH . "/index.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$message = "";

// Get the exam_id from URL (default to 0)
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// --- HANDLE ADD QUESTION FORM ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_question'])) {
    $q_exam_id = intval($_POST['exam_id']);
    $question_text = trim($_POST['question_text']);
    $option_a = trim($_POST['option_a']);
    $option_b = trim($_POST['option_b']);
    $option_c = trim($_POST['option_c']);
    $option_d = trim($_POST['option_d']);
    $correct_option = $_POST['correct_option'];
    $marks = intval($_POST['marks']);
    $explanation = trim($_POST['explanation']);

    if (empty($question_text) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d) || empty($correct_option)) {
        $message = "<div class='alert alert-danger'>Please fill in all required fields.</div>";
    } else {
        $order_sql = $conn->query("SELECT COUNT(*) as count FROM questions WHERE exam_id = $q_exam_id");
        $order_no = $order_sql->fetch_assoc()['count'] + 1;

        $stmt = $conn->prepare("INSERT INTO questions (exam_id, question_text, question_type, marks, explanation, order_no) VALUES (?, ?, 'MCQ', ?, ?, ?)");
        $stmt->bind_param("isssi", $q_exam_id, $question_text, $marks, $explanation, $order_no);
        
        if ($stmt->execute()) {
            $new_question_id = $stmt->insert_id;
            $stmt->close();

            $options = ['A' => $option_a, 'B' => $option_b, 'C' => $option_c, 'D' => $option_d];
            $opt_stmt = $conn->prepare("INSERT INTO options (question_id, option_text, is_correct, order_no) VALUES (?, ?, ?, ?)");
            $order = 1;
            foreach ($options as $key => $text) {
                $is_correct = ($key == $correct_option) ? 1 : 0;
                $opt_stmt->bind_param("isii", $new_question_id, $text, $is_correct, $order);
                $opt_stmt->execute();
                $order++;
            }
            $opt_stmt->close();

            $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Question added successfully!</div>";
            $exam_id = $q_exam_id;
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        }
    }
}

// --- HANDLE DELETE ---
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $check = $conn->query("SELECT q.question_id FROM questions q JOIN exams e ON q.exam_id = e.exam_id WHERE q.question_id = $del_id AND e.created_by = $teacher_id");
    if ($check->num_rows > 0) {
        $conn->query("DELETE FROM questions WHERE question_id = $del_id");
        $message = "<div class='alert alert-success'>Question deleted.</div>";
    }
}

// --- FETCH TEACHER'S EXAMS ---
$exams_result = $conn->query("SELECT * FROM exams WHERE created_by = $teacher_id ORDER BY exam_id DESC");
?>

<div class="main-content">
    <!-- Header Bar -->
    <div class="page-header-box d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0 fw-bold"><i class="fas fa-question-circle me-2"></i> Add New Question</h4>
            <small>Create a new question for your exam.</small>
        </div>
        <a href="dashboard.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php echo $message; ?>

    <!-- Exam Selector (if no exam chosen) -->
    <?php if ($exam_id == 0): ?>
        <div class="card p-4 mb-4">
            <h5><i class="fas fa-list text-primary"></i> Select an Exam to Add Questions</h5>
            <form method="GET" action="">
                <select name="exam_id" class="form-select" onchange="this.form.submit()" required>
                    <option value="0">-- Choose an Exam --</option>
                    <?php while($exam = $exams_result->fetch_assoc()): ?>
                        <option value="<?php echo $exam['exam_id']; ?>">
                            <?php echo $exam['title']; ?> (<?php echo $exam['subject']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>
    <?php else: ?>
        <?php 
        $exam_info = $conn->query("SELECT * FROM exams WHERE exam_id = $exam_id AND created_by = $teacher_id")->fetch_assoc();
        if ($exam_info): 
        ?>

        <div class="row g-4">
            <!-- LEFT: MAIN FORM -->
            <div class="col-lg-9">
                <div class="card p-4">
                    <form method="POST" action="">
                        <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">

                        <!-- Row 1: Exam, Type, Subject -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Select Exam *</label>
                                <select name="exam_select_display" class="form-select" disabled>
                                    <option><?php echo $exam_info['title']; ?></option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Question Type *</label>
                                <select class="form-select" disabled>
                                    <option>Multiple Choice (MCQ)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Subject *</label>
                                <select class="form-select" disabled>
                                    <option><?php echo $exam_info['subject']; ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Question Text -->
                        <div class="mb-4">
                            <label class="form-label">Question *</label>
                            <textarea name="question_text" class="form-control" rows="3" placeholder="Enter your question here..." required></textarea>
                        </div>

                        <!-- Options + Correct Answer (2 Columns) -->
                        <div class="row">
                            <!-- Options Column -->
                            <div class="col-md-7">
                                <label class="form-label">Options *</label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text option-badge">A</span>
                                    <input type="text" name="option_a" class="form-control" placeholder="Enter option A" required>
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text option-badge">B</span>
                                    <input type="text" name="option_b" class="form-control" placeholder="Enter option B" required>
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text option-badge">C</span>
                                    <input type="text" name="option_c" class="form-control" placeholder="Enter option C" required>
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text option-badge">D</span>
                                    <input type="text" name="option_d" class="form-control" placeholder="Enter option D" required>
                                </div>
                            </div>

                            <!-- Correct Answer + Marks Column -->
                            <div class="col-md-5">
                                <label class="form-label">Correct Answer *</label>
                                <select name="correct_option" class="form-select mb-3" required>
                                    <option value="">Select correct option</option>
                                    <option value="A">Option A</option>
                                    <option value="B">Option B</option>
                                    <option value="C">Option C</option>
                                    <option value="D">Option D</option>
                                </select>

                                <label class="form-label">Marks *</label>
                                <input type="number" name="marks" class="form-control mb-3" value="1" min="1" required>

                                <div class="info-tip">
                                    <i class="fas fa-info-circle"></i>
                                    You can assign more marks for difficult questions.
                                </div>
                            </div>
                        </div>

                        <!-- Explanation -->
                        <div class="mb-4 mt-3">
                            <label class="form-label"><i class="fas fa-lightbulb text-warning"></i> Explanation (Optional)</label>
                            <textarea name="explanation" class="form-control" rows="2" placeholder="Enter explanation for the correct answer (optional)..."></textarea>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex justify-content-between">
                            <button type="reset" class="btn btn-outline-secondary px-4">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                            <button type="submit" name="add_question" class="btn btn-primary px-4">
                                <i class="fas fa-plus"></i> Save Question
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Questions List Below -->
                <div class="card p-4 mt-4">
                    <h5 class="text-primary"><i class="fas fa-question-circle"></i> Questions in this Exam (<?php echo $conn->query("SELECT COUNT(*) as c FROM questions WHERE exam_id=$exam_id")->fetch_assoc()['c']; ?>)</h5>
                    <hr>
                    <?php
                    $q_result = $conn->query("SELECT * FROM questions WHERE exam_id = $exam_id ORDER BY order_no ASC");
                    if ($q_result->num_rows > 0):
                        $i = 1;
                        while($q = $q_result->fetch_assoc()):
                            $opts = $conn->query("SELECT * FROM options WHERE question_id = {$q['question_id']} ORDER BY order_no ASC");
                    ?>
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="d-flex justify-content-between">
                                <strong>Q<?php echo $i; ?>. <?php echo htmlspecialchars($q['question_text']); ?></strong>
                                <a href="questions.php?exam_id=<?php echo $exam_id; ?>&delete=<?php echo $q['question_id']; ?>" class="text-danger" onclick="return confirm('Delete this question?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                            <ul class="list-unstyled mt-2 mb-2 small">
                                <?php while($opt = $opts->fetch_assoc()): ?>
                                    <li class="<?php echo $opt['is_correct'] ? 'text-success fw-bold' : 'text-muted'; ?>">
                                        <?php echo $opt['is_correct'] ? '✅' : '○'; ?> <?php echo htmlspecialchars($opt['option_text']); ?>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                            <small class="text-muted">
                                <i class="fas fa-star text-warning"></i> Marks: <?php echo $q['marks']; ?>
                            </small>
                        </div>
                    <?php $i++; endwhile; else: ?>
                        <p class="text-center text-muted py-4">No questions added yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT SIDEBAR: QUICK TIPS -->
            <div class="col-lg-3">
                <div class="card p-4 mb-4">
                    <h6 class="fw-bold"><i class="fas fa-lightbulb text-warning"></i> Quick Tips</h6>
                    <hr>
                    <ol class="tips-list small">
                        <li>Select the exam and subject before adding a question.</li>
                        <li>Choose the correct question type (MCQ).</li>
                        <li>Add all options (A, B, C, D).</li>
                        <li>Select the correct answer.</li>
                        <li>Give appropriate marks.</li>
                        <li>Add explanation (optional) to help students learn.</li>
                    </ol>
                </div>

                <div class="card p-3 mb-4" style="background:#e8f5e9;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle text-success me-2" style="font-size:1.5rem;"></i>
                        <div>
                            <strong class="text-success small">Well done!</strong>
                            <p class="mb-0 small text-muted">Your question will be saved and added to the exam.</p>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <div style="font-size:3rem; color:#1976d2;">📋</div>
                    <p class="text-primary fst-italic fw-bold mt-2">Good Questions<br>Build Great Learning!</p>
                </div>
            </div>
        </div>

        <?php else: ?>
            <div class="alert alert-warning">You do not have permission to edit this exam.</div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Custom Styles for this page -->
<style>
    .page-header-box {
        background: linear-gradient(135deg, #0d47a1, #1976d2);
        color: white;
        padding: 20px 30px;
        border-radius: 12px;
    }
    .page-header-box small { opacity: 0.9; }
    .option-badge {
        background: #1976d2 !important;
        color: white !important;
        font-weight: bold;
        border-radius: 50% !important;
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none !important;
    }
    .input-group-text.option-badge {
        border-radius: 50% !important;
        margin: 4px 8px 4px 0;
        padding: 0;
    }
    .info-tip {
        background: #e3f2fd;
        padding: 10px;
        border-radius: 8px;
        font-size: 0.8rem;
        color: #0d47a1;
    }
    .tips-list { padding-left: 20px; }
    .tips-list li { margin-bottom: 10px; color: #555; }
    .form-label { font-weight: 600; font-size: 0.85rem; }
</style>

<?php include ROOT_PATH . '/includes/footer.php'; ?>