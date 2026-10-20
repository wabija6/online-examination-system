<?php
// admin/results.php
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

// --- SEARCH & FILTERS ---
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$filter_exam = isset($_GET['exam']) ? intval($_GET['exam']) : 0;
$filter_status = isset($_GET['status']) ? $_GET['status'] : "";

$where = "WHERE a.status = 'Completed'";
if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $where .= " AND (u.name LIKE '%$search_safe%' OR u.username LIKE '%$search_safe%' OR e.title LIKE '%$search_safe%')";
}
if ($filter_exam > 0) {
    $where .= " AND a.exam_id = $filter_exam";
}
if ($filter_status == 'passed') {
    // Assuming pass = >= 50% of total marks
    $where .= " AND (a.score / (SELECT IFNULL(SUM(marks),1) FROM questions q WHERE q.exam_id = e.exam_id)) >= 0.5";
} elseif ($filter_status == 'failed') {
    $where .= " AND (a.score / (SELECT IFNULL(SUM(marks),1) FROM questions q WHERE q.exam_id = e.exam_id)) < 0.5";
}

// --- FETCH ALL RESULTS ---
$results = $conn->query("
    SELECT 
        a.*,
        u.name as student_name,
        u.username as student_username,
        e.title as exam_title,
        e.subject,
        e.created_by,
        t.name as teacher_name,
        (SELECT IFNULL(SUM(marks), 0) FROM questions q WHERE q.exam_id = e.exam_id) as total_marks
    FROM attempts a 
    JOIN users u ON a.student_id = u.user_id 
    JOIN exams e ON a.exam_id = e.exam_id 
    JOIN users t ON e.created_by = t.user_id
    $where
    ORDER BY a.end_time DESC
");

// --- FETCH EXAMS FOR FILTER ---
$exam_list = $conn->query("SELECT exam_id, title FROM exams ORDER BY title ASC");

// Summary stats
$total_results = $conn->query("SELECT COUNT(*) as c FROM attempts WHERE status='Completed'")->fetch_assoc()['c'];

$passed_count = 0;
$failed_count = 0;
$total_percent = 0;
$total_rows = 0;

$stats_query = $conn->query("
    SELECT a.score,
        (SELECT IFNULL(SUM(marks),1) FROM questions q WHERE q.exam_id = a.exam_id) as t_marks
    FROM attempts a WHERE a.status='Completed'
");
while ($row = $stats_query->fetch_assoc()) {
    $pct = ($row['t_marks'] > 0) ? ($row['score'] / $row['t_marks']) * 100 : 0;
    if ($pct >= 50) $passed_count++; else $failed_count++;
    $total_percent += $pct;
    $total_rows++;
}
$avg_percent = ($total_rows > 0) ? round($total_percent / $total_rows, 1) : 0;
?>


<div class="main-content">
    <!-- Header Banner -->
    <div class="page-header-box d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0 fw-bold"><i class="fas fa-chart-bar me-2"></i> All Exam Results</h4>
            <small>Overview of every exam attempt across the system.</small>
        </div>
        <div>
            <form method="GET" action="" class="d-flex">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search student or exam..." value="<?php echo htmlspecialchars($search); ?>" style="width: 220px; border-radius: 8px 0 0 8px;">
                <button type="submit" class="btn btn-light btn-sm" style="border-radius: 0 8px 8px 0;"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card summary-card" style="background: linear-gradient(135deg, #1976d2, #2196f3);">
                <div><small>Total Results</small><h3><?php echo $total_results; ?></h3></div>
                <i class="fas fa-clipboard-list"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card" style="background: linear-gradient(135deg, #2e7d32, #66bb6a);">
                <div><small>Passed</small><h3><?php echo $passed_count; ?></h3></div>
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card" style="background: linear-gradient(135deg, #c62828, #ef5350);">
                <div><small>Failed</small><h3><?php echo $failed_count; ?></h3></div>
                <i class="fas fa-times-circle"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card" style="background: linear-gradient(135deg, #6a1b9a, #ab47bc);">
                <div><small>Avg. Score</small><h3><?php echo $avg_percent; ?>%</h3></div>
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
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- All Status --</option>
                    <option value="passed" <?php echo ($filter_status == 'passed') ? 'selected' : ''; ?>>Passed Only</option>
                    <option value="failed" <?php echo ($filter_status == 'failed') ? 'selected' : ''; ?>>Failed Only</option>
                </select>
            </div>
            <div class="col-md-2">
                <a href="results.php" class="btn btn-outline-secondary btn-sm w-100"><i class="fas fa-redo"></i> Reset</a>
            </div>
        </form>
    </div>

    <!-- Main Row: Results Table + Tips -->
    <div class="row g-4">
        <!-- LEFT: RESULTS TABLE -->
        <div class="col-lg-9">
            <div class="card p-4" style="min-height: 450px;">
                <h5 class="text-primary mb-3">
                    <i class="fas fa-list"></i> Student Results 
                    <?php if (!empty($search) || $filter_exam > 0 || !empty($filter_status)): ?>
                        <small class="text-muted">(<?php echo $results->num_rows; ?> results)</small>
                    <?php endif; ?>
                </h5>

                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 25%;">Student</th>
                            <th style="width: 22%;">Exam</th>
                            <th style="width: 12%;">Score</th>
                            <th style="width: 10%;">Percent</th>
                            <th style="width: 11%;">Status</th>
                            <th style="width: 15%;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($results->num_rows > 0) {
                            $i = 1;
                            while ($r = $results->fetch_assoc()) {
                                $percent = ($r['total_marks'] > 0) ? round(($r['score'] / $r['total_marks']) * 100) : 0;
                                $passed = $percent >= 50;
                                $status_badge = $passed ? '<span class="badge bg-success">Passed</span>' : '<span class="badge bg-danger">Failed</span>';
                        ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle"><?php echo strtoupper(substr($r['student_name'], 0, 1)); ?></div>
                                        <div class="ms-2">
                                            <strong style="font-size: 0.85rem;"><?php echo htmlspecialchars($r['student_name']); ?></strong><br>
                                            <small class="text-muted" style="font-size: 0.72rem;">@<?php echo htmlspecialchars($r['student_username']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong style="font-size: 0.85rem;"><?php echo htmlspecialchars($r['exam_title']); ?></strong><br>
                                    <small class="text-muted" style="font-size: 0.72rem;"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($r['teacher_name']); ?></small>
                                </td>
                                <td><strong><?php echo $r['score']; ?>/<?php echo $r['total_marks']; ?></strong></td>
                                <td><?php echo $percent; ?>%</td>
                                <td><?php echo $status_badge; ?></td>
                                <td><small class="text-muted"><?php echo date('M d, Y', strtotime($r['end_time'])); ?><br><?php echo date('h:i A', strtotime($r['end_time'])); ?></small></td>
                            </tr>
                        <?php
                                $i++;
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center text-muted py-5'>
                                    <i class='fas fa-chart-pie fa-3x mb-3 d-block' style='opacity: 0.3;'></i>
                                    No results found. Try changing the filters.
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
                <h6 class="fw-bold mb-3"><i class="fas fa-lightbulb text-warning"></i> Admin Insights</h6>
                <ol class="tips-list small">
                    <li>See <strong>all results</strong> from every exam.</li>
                    <li>Use <strong>filters</strong> to find specific exams.</li>
                    <li>Filter by <strong>passed or failed</strong> status.</li>
                    <li>Search by <strong>student name</strong> or <strong>exam title</strong>.</li>
                    <li>Avg. score shows overall system performance.</li>
                </ol>
            </div>

            <div class="card p-3 mb-3" style="background:#e8f5e9;">
                <div class="d-flex align-items-start">
                    <i class="fas fa-chart-pie text-success me-2 mt-1" style="font-size:1.2rem;"></i>
                    <div>
                        <strong class="text-success small d-block mb-2">Overall Stats</strong>
                        <div class="small">
                            <span class="badge bg-success mb-1"><?php echo $passed_count; ?> Passed</span><br>
                            <span class="badge bg-danger mb-1"><?php echo $failed_count; ?> Failed</span><br>
                            <strong class="text-primary"><?php echo $avg_percent; ?>%</strong> Average
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center py-3">
                <div style="font-size:2.5rem; color:#1976d2;">📊</div>
                <p class="text-primary fst-italic fw-bold mt-2 mb-0" style="font-size:0.9rem;">
                    Monitor Results<br>Improve Outcomes!
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
    .summary-card h3 { font-weight: 700; margin: 0; font-size: 1.7rem; }
    .summary-card i { font-size: 2.2rem; opacity: 0.35; }
    .avatar-circle {
        width: 34px; height: 34px; border-radius: 50%;
        background: linear-gradient(135deg, #1976d2, #64b5f6);
        color: white; display: flex; align-items: center; justify-content: center;
        font-weight: bold; font-size: 0.85rem;
    }
    .table th { font-weight: 600; font-size: 0.75rem; color: #555; border-top: none; text-transform: uppercase; padding: 10px 6px; }
    .table td { font-size: 0.85rem; vertical-align: middle; padding: 12px 6px; }
    .table tbody tr:hover { background-color: #f8f9fc; }
    .tips-list { padding-left: 18px; margin-bottom: 0; }
    .tips-list li { margin-bottom: 8px; color: #555; line-height: 1.4; }
    .badge { font-size: 0.72rem; padding: 4px 8px; font-weight: 500; }
</style>

<?php include ROOT_PATH . '/includes/footer.php'; ?>