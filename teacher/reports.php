<?php
// teacher/reports.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/db_connect.php';
include ROOT_PATH . '/includes/header.php';

// Protect: Only Teachers
if ($_SESSION['role'] != 'Teacher') {
    header("Location: " . ROOT_PATH . "/index.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];

// --- FILTER BY EXAM ---
$filter_exam = isset($_GET['exam']) ? intval($_GET['exam']) : 0;
$where_exam = ($filter_exam > 0) ? " AND a.exam_id = $filter_exam" : "";

// --- FETCH EXAMS FOR DROPDOWN ---
$exams_list = $conn->query("SELECT exam_id, title FROM exams WHERE created_by = $teacher_id ORDER BY title ASC");

// --- FETCH REPORT DATA ---
$report_data = $conn->query("
    SELECT 
        a.attempt_id,
        a.score,
        a.end_time,
        u.name as student_name,
        u.username,
        e.title as exam_title,
        e.subject,
        (SELECT IFNULL(SUM(marks), 1) FROM questions q WHERE q.exam_id = e.exam_id) as total_marks
    FROM attempts a 
    JOIN users u ON a.student_id = u.user_id 
    JOIN exams e ON a.exam_id = e.exam_id 
    WHERE e.created_by = $teacher_id AND a.status = 'Completed' $where_exam
    ORDER BY a.end_time DESC
");

// --- CALCULATE STATS ---
$total_attempts = 0;
$passed_count = 0;
$failed_count = 0;
$total_score_percent = 0;

// We need to loop through the data once for stats, then again for the table
// But since we are using mysqli, let's store the rows in an array first
$rows = [];
while ($row = $report_data->fetch_assoc()) {
    $rows[] = $row;
    $total_attempts++;
    $percent = ($row['total_marks'] > 0) ? ($row['score'] / $row['total_marks']) * 100 : 0;
    
    if ($percent >= 50) {
        $passed_count++;
    } else {
        $failed_count++;
    }
    $total_score_percent += $percent;
}

$avg_score = ($total_attempts > 0) ? round($total_score_percent / $total_attempts, 1) : 0;
$pass_rate = ($total_attempts > 0) ? round(($passed_count / $total_attempts) * 100) : 0;
?>

<div class="main-content">
    <!-- Header Banner -->
    <div class="page-header-box d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0 fw-bold"><i class="fas fa-file-pdf me-2"></i> My Exam Reports</h4>
            <small>Analyze student performance across your exams.</small>
        </div>
        <div>
            <button onclick="exportTableToCSV('reportTable', 'my_exam_report')" class="btn btn-light btn-sm fw-bold">
                <i class="fas fa-download"></i> Download CSV
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card summary-card" style="background: linear-gradient(135deg, #1976d2, #2196f3);">
                <div><small>Total Attempts</small><h3><?php echo $total_attempts; ?></h3></div>
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card" style="background: linear-gradient(135deg, #2e7d32, #66bb6a);">
                <div><small>Pass Rate</small><h3><?php echo $pass_rate; ?>%</h3></div>
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card" style="background: linear-gradient(135deg, #6a1b9a, #ab47bc);">
                <div><small>Avg. Score</small><h3><?php echo $avg_score; ?>%</h3></div>
                <i class="fas fa-star"></i>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card p-3 mb-4">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-3">
                <strong class="small"><i class="fas fa-filter text-primary"></i> Filter by Exam:</strong>
            </div>
            <div class="col-md-5">
                <select name="exam" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">-- All My Exams --</option>
                    <?php while ($ex = $exams_list->fetch_assoc()): ?>
                        <option value="<?php echo $ex['exam_id']; ?>" <?php echo ($filter_exam == $ex['exam_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ex['title']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <a href="reports.php" class="btn btn-outline-secondary btn-sm w-100"><i class="fas fa-redo"></i> Reset</a>
            </div>
        </form>
    </div>

    <!-- Main Content: Report Table + Tips -->
    <div class="row g-4">
        <div class="col-lg-9">
            <div class="card p-4" style="min-height: 400px;">
                <h5 class="text-primary mb-3"><i class="fas fa-list"></i> Student Performance Data</h5>
                
                <table class="table table-hover align-middle mb-0" id="reportTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Exam Title</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (count($rows) > 0) {
                            $i = 1;
                            foreach ($rows as $r) {
                                $percent = ($r['total_marks'] > 0) ? round(($r['score'] / $r['total_marks']) * 100) : 0;
                                $passed = $percent >= 50;
                                $status = $passed ? '<span class="badge bg-success">Passed</span>' : '<span class="badge bg-danger">Failed</span>';
                        ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($r['student_name']); ?></strong><br>
                                    <small class="text-muted">@<?php echo htmlspecialchars($r['username']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($r['exam_title']); ?></td>
                                <td><strong><?php echo $r['score']; ?>/<?php echo $r['total_marks']; ?></strong></td>
                                <td><?php echo $percent; ?>%</td>
                                <td><?php echo $status; ?></td>
                                <td><small><?php echo date('M d, Y', strtotime($r['end_time'])); ?></small></td>
                            </tr>
                        <?php } } else {
                            echo "<tr><td colspan='7' class='text-center text-muted py-5'>
                                    <i class='fas fa-inbox fa-3x mb-3 d-block' style='opacity: 0.3;'></i>
                                    No exam attempts found for your exams.
                                  </td></tr>";
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="col-lg-3">
            <div class="card p-4 mb-3">
                <h6 class="fw-bold mb-3"><i class="fas fa-lightbulb text-warning"></i> Report Tips</h6>
                <ol class="tips-list small">
                    <li>This report only shows data for <strong>your exams</strong>.</li>
                    <li>Use the <strong>dropdown</strong> to filter by a specific exam.</li>
                    <li>Click <strong>Download CSV</strong> to open in Excel.</li>
                    <li>Pass rate is calculated at <strong>50% or above</strong>.</li>
                </ol>
            </div>

            <div class="card p-3 mb-3" style="background:#e8f5e9;">
                <div class="d-flex align-items-start">
                    <i class="fas fa-chart-pie text-success me-2 mt-1" style="font-size:1.2rem;"></i>
                    <div>
                        <strong class="text-success small d-block mb-2">Quick Stats</strong>
                        <div class="small text-muted">
                            <span class="badge bg-success mb-1"><?php echo $passed_count; ?> Passed</span><br>
                            <span class="badge bg-danger mb-1"><?php echo $failed_count; ?> Failed</span><br>
                            Overall Avg: <strong><?php echo $avg_score; ?>%</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center py-3">
                <div style="font-size:2.5rem; color:#1976d2;">📊</div>
                <p class="text-primary fst-italic fw-bold mt-2 mb-0" style="font-size:0.9rem;">
                    Analyze & Improve<br>Teaching Outcomes!
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
    .table th { font-weight: 600; font-size: 0.78rem; color: #555; border-top: none; text-transform: uppercase; padding: 10px 8px; }
    .table td { font-size: 0.88rem; vertical-align: middle; padding: 12px 8px; }
    .table tbody tr:hover { background-color: #f8f9fc; }
    .tips-list { padding-left: 18px; margin-bottom: 0; }
    .tips-list li { margin-bottom: 8px; color: #555; line-height: 1.4; }
    .badge { font-size: 0.75rem; padding: 4px 8px; font-weight: 500; }
</style>

<script>
    // CSV Export Function
    function exportTableToCSV(tableId, filename) {
        const table = document.getElementById(tableId);
        let csv = [];
        for (let row of table.rows) {
            let cells = [];
            for (let cell of row.cells) {
                cells.push('"' + cell.innerText.replace(/"/g, '""') + '"');
            }
            csv.push(cells.join(','));
        }
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename + '.csv';
        link.click();
    }
</script>

<?php include ROOT_PATH . '/includes/footer.php'; ?>