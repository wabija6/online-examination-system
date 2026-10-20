<?php
// student/take_exam.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/db_connect.php';

// --- 1. PROTECT PAGE & HANDLE LOGIC (BEFORE ANY HTML) ---
if ($_SESSION['role'] != 'Student') {
    header("Location: " . ROOT_PATH . "/index.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// Fetch exam
$exam = $conn->query("SELECT * FROM exams WHERE exam_id = $exam_id")->fetch_assoc();
if (!$exam) {
    header("Location: dashboard.php");
    exit;
}

// Check if student already attempted this exam
$already = $conn->query("SELECT attempt_id FROM attempts WHERE student_id = $student_id AND exam_id = $exam_id AND status='Completed'")->num_rows;
if ($already > 0) {
    header("Location: dashboard.php");
    exit;
}

// Create the attempt record (In Progress)
$conn->query("INSERT INTO attempts (student_id, exam_id, start_time, status) VALUES ($student_id, $exam_id, NOW(), 'In Progress')");
$attempt_id = $conn->insert_id;

// Fetch all questions
$questions = $conn->query("SELECT * FROM questions WHERE exam_id = $exam_id ORDER BY order_no ASC");
$total_questions = $questions->num_rows;

if ($total_questions == 0) {
    // If no questions, delete the attempt and go back
    $conn->query("DELETE FROM attempts WHERE attempt_id = $attempt_id");
    header("Location: dashboard.php");
    exit;
}

// --- 2. NOW INCLUDE THE HEADER (HTML STARTS HERE) ---
include ROOT_PATH . '/includes/header.php';
?>

<?php include ROOT_PATH . '/includes/sidebar.php'; ?>

<div class="main-content">
    <!-- Top Bar: Exam Info + Timer -->
    <div class="card p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h5 class="fw-bold mb-1"><i class="fas fa-file-alt text-primary"></i> <?php echo htmlspecialchars($exam['title']); ?></h5>
                <small class="text-muted">
                    Subject: <?php echo htmlspecialchars($exam['subject']); ?> | 
                    Type: MCQ | 
                    Total Questions: <?php echo $total_questions; ?>
                </small>
            </div>
            <div class="text-center">
                <small class="text-muted d-block">Time Remaining</small>
                <h3 class="fw-bold text-danger mb-0" id="timer">--:--:--</h3>
            </div>
            <div style="width: 200px;">
                <small class="text-muted">Question <span id="currentQNum">1</span> of <?php echo $total_questions; ?></small>
                <div class="progress mt-1" style="height: 6px;">
                    <div class="progress-bar" id="progressBar" style="width: 0%;"></div>
                </div>
            </div>
        </div>
    </div>

    <form id="examForm" method="POST" action="submit_exam.php">
        <input type="hidden" name="attempt_id" value="<?php echo $attempt_id; ?>">
        <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">

        <div class="row g-4">
            <!-- Left: Question Box -->
            <div class="col-lg-8">
                <?php
                $q_index = 1;
                $questions->data_seek(0);
                while ($q = $questions->fetch_assoc()):
                    $options = $conn->query("SELECT * FROM options WHERE question_id = {$q['question_id']} ORDER BY order_no ASC");
                ?>
                <div class="question-box" data-qnum="<?php echo $q_index; ?>" style="display: <?php echo ($q_index == 1) ? 'block' : 'none'; ?>;">
                    <div class="card p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="q-number"><?php echo $q_index; ?></div>
                            <h5 class="ms-3 mb-0 fw-bold"><?php echo htmlspecialchars($q['question_text']); ?></h5>
                        </div>
                        <p class="text-muted small mb-3"><i class="fas fa-star text-warning"></i> <?php echo $q['marks']; ?> Mark(s)</p>

                        <div class="options-list">
                            <?php $opt_letter = 'A'; while ($opt = $options->fetch_assoc()): ?>
                                <label class="option-item">
                                    <input type="radio" name="q_<?php echo $q['question_id']; ?>" value="<?php echo $opt['option_id']; ?>" class="d-none" onchange="markAnswered(<?php echo $q_index; ?>)">
                                    <span class="option-letter"><?php echo $opt_letter; ?></span>
                                    <span class="option-text"><?php echo htmlspecialchars($opt['option_text']); ?></span>
                                </label>
                            <?php $opt_letter++; endwhile; ?>
                        </div>
                    </div>
                </div>
                <?php $q_index++; endwhile; ?>

                <!-- Navigation Buttons -->
                <div class="d-flex justify-content-between mt-3">
                    <button type="button" class="btn btn-outline-secondary" onclick="prevQuestion()">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextQuestion()">
                        Next <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;" onclick="return confirm('Are you sure you want to submit your exam?');">
                        <i class="fas fa-check-circle"></i> Submit Exam
                    </button>
                </div>
            </div>

            <!-- Right: Question Navigator -->
            <div class="col-lg-4">
                <div class="card p-4 mb-3">
                    <h6 class="fw-bold mb-3"><i class="fas fa-th text-primary"></i> Question Navigator</h6>
                    <div class="navigator-grid">
                        <?php for ($i = 1; $i <= $total_questions; $i++): ?>
                            <button type="button" class="nav-btn" id="nav-<?php echo $i; ?>" onclick="goToQuestion(<?php echo $i; ?>)">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                    </div>
                    <div class="mt-3 small">
                        <div><span class="legend-box legend-answered"></span> Answered</div>
                        <div><span class="legend-box legend-current"></span> Current</div>
                        <div><span class="legend-box legend-unanswered"></span> Not Answered</div>
                    </div>
                </div>

                <div class="card p-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-info-circle text-primary"></i> Exam Info</h6>
                    <div class="small">
                        <div class="mb-2"><i class="fas fa-file-alt text-primary"></i> <?php echo htmlspecialchars($exam['title']); ?></div>
                        <div class="mb-2"><i class="fas fa-book text-primary"></i> <?php echo htmlspecialchars($exam['subject']); ?></div>
                        <div class="mb-2"><i class="fas fa-list text-primary"></i> Total Questions: <?php echo $total_questions; ?></div>
                        <div class="mb-2"><i class="fas fa-clock text-primary"></i> Duration: <?php echo $exam['duration_minutes']; ?> Minutes</div>
                    </div>
                </div>

                <div class="card p-3 mt-3" style="background:#e8f5e9;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-smile text-success me-2" style="font-size:1.5rem;"></i>
                        <div>
                            <strong class="text-success small">You're doing great!</strong>
                            <p class="mb-0 small text-muted">Keep going and complete all questions.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .q-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #1976d2;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .option-item {
        display: flex;
        align-items: center;
        padding: 14px 18px;
        border: 2px solid #e3e8f0;
        border-radius: 10px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: 0.2s;
    }
    .option-item:hover { border-color: #64b5f6; background: #f5faff; }
    .option-item.selected { border-color: #1976d2; background: #e3f2fd; }
    .option-letter {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #e3f2fd;
        color: #0d47a1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-right: 15px;
        flex-shrink: 0;
    }
    .option-item.selected .option-letter {
        background: #1976d2;
        color: white;
    }
    .option-text { font-size: 0.95rem; }

    /* Navigator */
    .navigator-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 8px;
    }
    .nav-btn {
        padding: 10px 0;
        border: 2px solid #e0e0e0;
        background: white;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: 0.2s;
    }
    .nav-btn:hover { border-color: #64b5f6; }
    .nav-btn.answered { background: #66bb6a; color: white; border-color: #66bb6a; }
    .nav-btn.current { background: #1976d2; color: white; border-color: #1976d2; box-shadow: 0 0 10px rgba(25,118,210,0.4); }

    .legend-box {
        display: inline-block;
        width: 16px;
        height: 16px;
        border-radius: 4px;
        vertical-align: middle;
        margin-right: 6px;
    }
    .legend-answered { background: #66bb6a; }
    .legend-current { background: #1976d2; }
    .legend-unanswered { background: white; border: 2px solid #e0e0e0; }
</style>

<script>
    const totalQuestions = <?php echo $total_questions; ?>;
    const durationMinutes = <?php echo $exam['duration_minutes']; ?>;
    let currentQ = 1;
    let timeLeft = durationMinutes * 60; // in seconds
    let answered = {};

    // --- TIMER ---
    function updateTimer() {
        const hours = Math.floor(timeLeft / 3600);
        const minutes = Math.floor((timeLeft % 3600) / 60);
        const seconds = timeLeft % 60;
        document.getElementById('timer').textContent = 
            String(hours).padStart(2, '0') + ':' +
            String(minutes).padStart(2, '0') + ':' +
            String(seconds).padStart(2, '0');

        if (timeLeft <= 300) {
            document.getElementById('timer').style.color = '#dc3545';
        }

        if (timeLeft <= 0) {
            document.getElementById('examForm').submit();
        }
        timeLeft--;
    }
    setInterval(updateTimer, 1000);
    updateTimer();

    // --- NAVIGATION ---
    function goToQuestion(num) {
        // Hide all
        document.querySelectorAll('.question-box').forEach(b => b.style.display = 'none');
        // Show selected
        document.querySelector('.question-box[data-qnum="' + num + '"]').style.display = 'block';
        currentQ = num;
        updateNavUI();
    }

    function nextQuestion() {
        if (currentQ < totalQuestions) {
            goToQuestion(currentQ + 1);
        }
    }

    function prevQuestion() {
        if (currentQ > 1) {
            goToQuestion(currentQ - 1);
        }
    }

    function updateNavUI() {
        // Update current highlight
        document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('current'));
        document.getElementById('nav-' + currentQ).classList.add('current');

        // Update answered highlight
        for (const q in answered) {
            if (answered[q]) {
                document.getElementById('nav-' + q).classList.add('answered');
            }
        }

        // Show submit on last question
        if (currentQ == totalQuestions) {
            document.getElementById('nextBtn').style.display = 'none';
            document.getElementById('submitBtn').style.display = 'inline-block';
        } else {
            document.getElementById('nextBtn').style.display = 'inline-block';
            document.getElementById('submitBtn').style.display = 'none';
        }

        // Update progress bar
        const progress = (Object.keys(answered).length / totalQuestions) * 100;
        document.getElementById('progressBar').style.width = progress + '%';
        document.getElementById('currentQNum').textContent = currentQ;
    }

    // --- ANSWER SELECTION ---
    document.querySelectorAll('.option-item').forEach(item => {
        item.addEventListener('click', function() {
            // Unselect siblings
            this.parentElement.querySelectorAll('.option-item').forEach(o => o.classList.remove('selected'));
            // Select this
            this.classList.add('selected');
            this.querySelector('input[type=radio]').checked = true;
        });
    });

    function markAnswered(qnum) {
        answered[qnum] = true;
        updateNavUI();
    }

    // Attach listeners to radios
    document.querySelectorAll('input[type=radio]').forEach(radio => {
        radio.addEventListener('change', function() {
            const qnum = this.closest('.question-box').dataset.qnum;
            answered[qnum] = true;
            updateNavUI();
        });
    });

    // Initialize
    updateNavUI();
</script>

<?php include ROOT_PATH . '/includes/footer.php'; ?>