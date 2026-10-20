<?php
// admin/questions.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/db_connect.php';
include ROOT_PATH . '/includes/header.php';

// Protect: Only Admin
if ($_SESSION['role'] != 'Admin') {
    header("Location: " . ROOT_PATH . "/index.php");
    exit;
}

// --- HANDLE DELETE QUESTION ---
$message = "";
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $conn->query("DELETE FROM questions WHERE question_id = $del_id");
    $message = "<div class='alert alert-success py-2'><i class='fas fa-check-circle'></i> Question deleted successfully!</div>";
}

// --- SEARCH & FILTERS ---
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$filter_exam = isset($_GET['exam']) ? intval($_GET['exam']) : 0;
$filter_teacher = isset($_GET['teacher']) ? intval($_GET['teacher']) : 0;

$where = "WHERE 1=1";
if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $where .= " AND q.question_text LIKE '%$search_safe%'";
}
if ($filter_exam > 0) {
    $where .= " AND q.exam_id = $filter_exam";
}
if ($filter_teacher > 0) {
    $where .= " AND e.created_by = $filter_teacher";
}

// --- FETCH ALL QUESTIONS ---
$questions = $conn->query("
    SELECT 
        q.*,
        e.title as exam_title,
        e.subject,
        u.name as teacher_name,
        (SELECT option_text FROM options o WHERE o.question_id = q.question_id AND o.is_correct = 1 LIMIT 1) as correct_answer
    FROM questions q
    JOIN exams e ON q.exam_id = e.exam_id
    JOIN users u ON e.created_by = u.user_id
    $where
    ORDER BY q.question_id DESC
");

// --- FETCH EXAMS FOR FILTER ---
$exam_list = $conn->query("SELECT exam_id, title FROM exams ORDER BY title ASC");

// --- FETCH TEACHERS FOR FILTER ---
$teacher_list = $conn->query("SELECT user_id, name FROM users WHERE role='Teacher' ORDER BY name ASC");

// Summary stats
$total_questions = $conn->query("SELECT COUNT(*) as c FROM questions")->fetch_assoc()['c'];
$exams_with_q = $conn->query("SELECT COUNT(DISTINCT exam_id) as c FROM questions")->fetch_assoc()['c'];
$avg_marks = $conn->query("SELECT IFNULL(ROUND(AVG(marks), 1), 0) as avg FROM questions")->fetch_assoc()['avg'];
?>


<div class="main-content">
    <!-- Header Banner -->
    <div class="page-header-box d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0 fw-bold"><i class="fas fa-question-circle me-2"></i> Question Bank</h4>
            <small>View and manage all questions created across the system.</small>
        </div>
        <div>
            <form method="GET" action="" class="d-flex">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search questions..." value="<?php echo htmlspecialchars($search); ?>" style="width: 220px; border-radius: 8px 0 0 8px;">
                <button type="submit" class="btn btn-light btn-sm" style="border-radius: 0 8px 8px 0;"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <?php echo $message; ?>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card summary-card" style="background: linear-gradient(135deg, #1976d2, #2196f3);">
                <div><small>Total Questions</small><h3><?php echo $total_questions; ?></h3></div>
                <i class="fas fa-question-circle"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card" style="background: linear-gradient(135deg, #2e7d32, #66bb6a);">
                <div><small>Exams with Questions</small><h3><?php echo $exams_with_q; ?></h3></div>
                <i class="fas fa-file-alt"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card" style="background: linear-gradient(135deg, #ef6c00, #ff9800);">
                <div><small>Avg. Marks / Question</small><h3><?php echo $avg_marks; ?></h3></div>
                <i class="fas fa-star"></i>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card p-3 mb-4">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-2">
                <strong class="small"><i class="fas fa-filter text-primary"></i> Filters:</strong>
            </div>
            <div class="col-md-3">
                <select name="exam" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">-- All Exams --</option>
                    <?php while ($ex = $exam_list->fetch_assoc()): ?>
                        <option value="<?php echo $ex['exam_id']; ?>" <?php echo ($filter_exam == $ex['exam_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ex['title']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="teacher" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">-- All Teachers --</option>
                    <?php while ($t = $teacher_list->fetch_assoc()): ?>
                        <option value="<?php echo $t['user_id']; ?>" <?php echo ($filter_teacher == $t['user_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <a href="questions.php" class="btn btn-outline-secondary btn-sm w-100"><i class="fas fa-redo"></i> Reset</a>
            </div>
        </form>
    </div>

    <!-- Main Row: Questions Table + Tips -->
    <div class="row g-4">
        <!-- LEFT: QUESTIONS TABLE -->
        <div class="col-lg-9">
            <div class="card p-4" style="min-height: 450px;">
                <h5 class="text-primary mb-3">
                    <i class="fas fa-list"></i> All Questions 
                    <?php if (!empty($search) || $filter_exam > 0 || $filter_teacher > 0): ?>
                        <small class="text-muted">(<?php echo $questions->num_rows; ?> results)</small>
                    <?php endif; ?>
                </h5>
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 35%;">Question</th>
                            <th style="width: 20%;">Exam</th>
                            <th style="width: 15%;">Correct Answer</th>
                            <th style="width: 8%;">Marks</th>
                            <th style="width: 17%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($questions->num_rows > 0) {
                            $i = 1;
                            while ($q = $questions->fetch_assoc()) {
                                $short_q = strlen($q['question_text']) > 60 ? substr($q['question_text'], 0, 60) . '...' : $q['question_text'];
                        ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td>
                                    <strong style="font-size: 0.85rem;"><?php echo htmlspecialchars($short_q); ?></strong><br>
                                    <small class="text-muted" style="font-size: 0.72rem;">
                                        <i class="fas fa-user-tie text-primary"></i> <?php echo htmlspecialchars($q['teacher_name']); ?>
                                    </small>
                                </td>
                                <td>
                                    <small><strong><?php echo htmlspecialchars($q['exam_title']); ?></strong></small><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($q['subject']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check"></i> <?php echo htmlspecialchars($q['correct_answer'] ?: 'N/A'); ?>
                                    </span>
                                </td>
                                <td><span class="badge bg-info text-white"><?php echo $q['marks']; ?> mark(s)</span></td>
                                <td>
                                    <a href="questions.php?delete=<?php echo $q['question_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this question?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php
                                $i++;
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center text-muted py-5'>
                                    <i class='fas fa-folder-open fa-3x mb-3 d-block' style='opacity: 0.3;'></i>
                                    No questions found.
                                  </td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RIGHT: TIPS PANEL -->
        <div class="col-lg-3">
            <div class="card p-4 mb-3">
                <h6 class="fw-bold mb-3"><i class="fas fa-lightbulb text-warning"></i> Admin Controls</h6>
                <ol class="tips-list small">
                    <li>See <strong>every question</strong> across all exams.</li>
                    <li>Use the <strong>filter</strong> to see questions from a specific exam or teacher.</li>
                    <li>The <strong>correct answer</strong> is highlighted in green.</li>
                    <li>Click the <strong>trash icon</strong> to remove a question.</li>
                    <li>Search by question text.</li>
                </ol>
            </div>

            <div class="card p-3 mb-3" style="background:#e8f5e9;">
                <div class="d-flex align-items-start">
                    <i class="fas fa-info-circle text-success me-2 mt-1" style="font-size:1.2rem;"></i>
                    <div>
                        <strong class="text-success small d-block mb-1">Note</strong>
                        <small class="text-muted">
                            Deleting a question removes it permanently from the exam.
                        </small>
                    </div>
                </div>
            </div>

            <div class="text-center py-3">
                <div style="font-size:2.5rem; color:#1976d2;">📚</div>
                <p class="text-primary fst-italic fw-bold mt-2 mb-0" style="font-size:0.9rem;">
                    Question Bank<br>Oversight!
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
    .table th { font-weight: 600; font-size: 0.75rem; color: #555; border-top: none; text-transform: uppercase; padding: 10px 6px; }
    .table td { font-size: 0.85rem; vertical-align: middle; padding: 12px 6px; }
    .table tbody tr:hover { background-color: #f8f9fc; }
    .tips-list { padding-left: 18px; margin-bottom: 0; }
    .tips-list li { margin-bottom: 8px; color: #555; line-height: 1.4; }
    .badge { font-size: 0.72rem; padding: 4px 8px; font-weight: 500; }
</style>

<?php include ROOT_PATH . '/includes/footer.php'; ?>