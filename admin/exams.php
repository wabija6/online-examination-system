<?php
// admin/exams.php
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

$message = "";

// --- HANDLE DELETE EXAM ---
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $conn->query("DELETE FROM exams WHERE exam_id = $del_id");
    $message = "<div class='alert alert-success py-2'><i class='fas fa-check-circle'></i> Exam deleted successfully!</div>";
}

// --- SEARCH & FILTER ---
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$filter_teacher = isset($_GET['teacher']) ? intval($_GET['teacher']) : 0;

$where = "WHERE 1=1";
if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $where .= " AND (e.title LIKE '%$search_safe%' OR e.subject LIKE '%$search_safe%')";
}
if ($filter_teacher > 0) {
    $where .= " AND e.created_by = $filter_teacher";
}

// --- FETCH ALL EXAMS WITH TEACHER NAME AND COUNTS ---
$exams = $conn->query("
    SELECT 
        e.*, 
        u.name as teacher_name,
        (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.exam_id) as total_q,
        (SELECT COUNT(*) FROM attempts a WHERE a.exam_id = e.exam_id AND a.status='Completed') as total_attempts
    FROM exams e 
    JOIN users u ON e.created_by = u.user_id 
    $where
    ORDER BY e.exam_id DESC
");

// --- FETCH ALL TEACHERS FOR FILTER DROPDOWN ---
$teachers = $conn->query("SELECT user_id, name FROM users WHERE role='Teacher' ORDER BY name ASC");

// Summary counts
$total_exams = $conn->query("SELECT COUNT(*) as c FROM exams")->fetch_assoc()['c'];
$active_exams = $conn->query("SELECT COUNT(*) as c FROM exams WHERE NOW() BETWEEN start_time AND end_time")->fetch_assoc()['c'];
$total_attempts = $conn->query("SELECT COUNT(*) as c FROM attempts WHERE status='Completed'")->fetch_assoc()['c'];
$total_questions = $conn->query("SELECT COUNT(*) as c FROM questions")->fetch_assoc()['c'];
?>

<div class="main-content">
    <!-- Header Banner -->
    <div class="page-header-box d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0 fw-bold"><i class="fas fa-file-alt me-2"></i> Manage All Exams</h4>
            <small>View and manage all exams created across the system.</small>
        </div>
        <div>
            <form method="GET" action="" class="d-flex">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search exams..." value="<?php echo htmlspecialchars($search); ?>" style="width: 220px; border-radius: 8px 0 0 8px;">
                <button type="submit" class="btn btn-light btn-sm" style="border-radius: 0 8px 8px 0;"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <?php echo $message; ?>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card summary-card" style="background: linear-gradient(135deg, #1976d2, #2196f3);">
                <div><small>Total Exams</small><h3><?php echo $total_exams; ?></h3></div>
                <i class="fas fa-file-alt"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card" style="background: linear-gradient(135deg, #2e7d32, #66bb6a);">
                <div><small>Active Now</small><h3><?php echo $active_exams; ?></h3></div>
                <i class="fas fa-bolt"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card" style="background: linear-gradient(135deg, #6a1b9a, #ab47bc);">
                <div><small>Total Questions</small><h3><?php echo $total_questions; ?></h3></div>
                <i class="fas fa-question-circle"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card" style="background: linear-gradient(135deg, #ef6c00, #ff9800);">
                <div><small>Total Attempts</small><h3><?php echo $total_attempts; ?></h3></div>
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card p-3 mb-4">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-3">
                <strong><i class="fas fa-filter text-primary"></i> Filter by Teacher:</strong>
            </div>
            <div class="col-md-4">
                <select name="teacher" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">-- All Teachers --</option>
                    <?php while ($t = $teachers->fetch_assoc()): ?>
                        <option value="<?php echo $t['user_id']; ?>" <?php echo ($filter_teacher == $t['user_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <a href="exams.php" class="btn btn-outline-secondary btn-sm w-100"><i class="fas fa-redo"></i> Reset Filters</a>
            </div>
        </form>
    </div>

    <!-- Main Row: Exams Table + Tips -->
    <div class="row g-4">
        <!-- LEFT: EXAMS TABLE -->
        <div class="col-lg-9">
            <div class="card p-4" style="min-height: 450px;">
                <h5 class="text-primary mb-3">
                    <i class="fas fa-list"></i> All Exams 
                    <?php if (!empty($search) || $filter_teacher > 0): ?>
                        <small class="text-muted">(<?php echo $exams->num_rows; ?> results)</small>
                    <?php endif; ?>
                </h5>
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 22%;">Exam Title</th>
                            <th style="width: 13%;">Subject</th>
                            <th style="width: 15%;">Teacher</th>
                            <th style="width: 10%;">Duration</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 8%;">Q / A</th>
                            <th style="width: 17%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($exams->num_rows > 0) {
                            $i = 1;
                            while ($e = $exams->fetch_assoc()) {
                                $now = date('Y-m-d H:i:s');
                                if ($now < $e['start_time']) {
                                    $status_badge = '<span class="badge bg-warning text-dark">Upcoming</span>';
                                } elseif ($now > $e['end_time']) {
                                    $status_badge = '<span class="badge bg-secondary">Expired</span>';
                                } else {
                                    $status_badge = '<span class="badge bg-success">Active</span>';
                                }
                        ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td><strong style="font-size: 0.85rem;"><?php echo htmlspecialchars($e['title']); ?></strong></td>
                                <td><small><?php echo htmlspecialchars($e['subject']); ?></small></td>
                                <td><small><i class="fas fa-user-tie text-primary"></i> <?php echo htmlspecialchars($e['teacher_name']); ?></small></td>
                                <td><small><?php echo $e['duration_minutes']; ?> min</small></td>
                                <td><?php echo $status_badge; ?></td>
                                <td>
                                    <span class="badge bg-info text-white" style="font-size:0.7rem;"><?php echo $e['total_q']; ?> Q</span>
                                    <span class="badge bg-secondary" style="font-size:0.7rem;"><?php echo $e['total_attempts']; ?> A</span>
                                </td>
                                <td>
                                    <a href="questions.php?exam_id=<?php echo $e['exam_id']; ?>" class="btn btn-sm btn-info text-white" title="View Questions">
                                        <i class="fas fa-question"></i>
                                    </a>
                                    <a href="results.php?exam_id=<?php echo $e['exam_id']; ?>" class="btn btn-sm btn-success" title="View Results">
                                        <i class="fas fa-chart-bar"></i>
                                    </a>
                                    <a href="exams.php?delete=<?php echo $e['exam_id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this exam? All questions and results will be deleted.');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php
                                $i++;
                            }
                        } else {
                            echo "<tr><td colspan='8' class='text-center text-muted py-5'>
                                    <i class='fas fa-folder-open fa-3x mb-3 d-block' style='opacity: 0.3;'></i>
                                    No exams found.
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
                    <li>See <strong>all exams</strong> created by every teacher.</li>
                    <li>Use the <strong>filter</strong> to see exams by a specific teacher.</li>
                    <li>Click the <strong>question icon</strong> to view an exam's questions.</li>
                    <li>Click the <strong>chart icon</strong> to see exam results.</li>
                    <li>Use the <strong>trash icon</strong> to delete any exam.</li>
                    <li>Search by exam title or subject.</li>
                </ol>
            </div>

            <div class="card p-3 mb-3" style="background:#e8f5e9;">
                <div class="d-flex align-items-start">
                    <i class="fas fa-info-circle text-success me-2 mt-1" style="font-size:1.2rem;"></i>
                    <div>
                        <strong class="text-success small d-block mb-1">Legend</strong>
                        <small class="text-muted">
                            <span class="badge bg-info" style="font-size:0.65rem;">Q</span> = Questions<br>
                            <span class="badge bg-secondary" style="font-size:0.65rem;">A</span> = Attempts
                        </small>
                    </div>
                </div>
            </div>

            <div class="text-center py-3">
                <div style="font-size:2.5rem; color:#1976d2;">🗂️</div>
                <p class="text-primary fst-italic fw-bold mt-2 mb-0" style="font-size:0.9rem;">
                    Monitor All Exams<br>In One Place!
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
    .btn-sm { font-size: 0.75rem; padding: 4px 8px; }
</style>

<?php include ROOT_PATH . '/includes/footer.php'; ?>