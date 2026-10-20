<?php
// admin/reports.php
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

// Report type
$report_type = isset($_GET['type']) ? $_GET['type'] : 'overview';

// Summary numbers
$total_students = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='Student'")->fetch_assoc()['c'];
$total_teachers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='Teacher'")->fetch_assoc()['c'];
$total_exams = $conn->query("SELECT COUNT(*) as c FROM exams")->fetch_assoc()['c'];
$total_questions = $conn->query("SELECT COUNT(*) as c FROM questions")->fetch_assoc()['c'];
$total_attempts = $conn->query("SELECT COUNT(*) as c FROM attempts WHERE status='Completed'")->fetch_assoc()['c'];

// Pass/Fail stats
$passed = 0; $failed = 0;
$stats = $conn->query("
    SELECT a.score, 
    (SELECT IFNULL(SUM(marks),1) FROM questions q WHERE q.exam_id = a.exam_id) as t_marks
    FROM attempts a WHERE a.status='Completed'
");
while ($r = $stats->fetch_assoc()) {
    $pct = ($r['t_marks'] > 0) ? ($r['score'] / $r['t_marks']) * 100 : 0;
    if ($pct >= 50) $passed++; else $failed++;
}
?>


<div class="main-content">
    <!-- Header Banner -->
    <div class="page-header-box d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0 fw-bold"><i class="fas fa-file-pdf me-2"></i> Reports & Analytics</h4>
            <small>Download system-wide reports and analytics.</small>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-light btn-sm">
                <i class="fas fa-print"></i> Print Page
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card summary-card" style="background: linear-gradient(135deg, #1976d2, #2196f3);">
                <div><small>Students</small><h3><?php echo $total_students; ?></h3></div>
                <i class="fas fa-user-graduate"></i>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card summary-card" style="background: linear-gradient(135deg, #6a1b9a, #ab47bc);">
                <div><small>Teachers</small><h3><?php echo $total_teachers; ?></h3></div>
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card summary-card" style="background: linear-gradient(135deg, #2e7d32, #66bb6a);">
                <div><small>Exams</small><h3><?php echo $total_exams; ?></h3></div>
                <i class="fas fa-file-alt"></i>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card summary-card" style="background: linear-gradient(135deg, #ef6c00, #ff9800);">
                <div><small>Questions</small><h3><?php echo $total_questions; ?></h3></div>
                <i class="fas fa-question-circle"></i>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card summary-card" style="background: linear-gradient(135deg, #00897b, #26a69a);">
                <div><small>Attempts</small><h3><?php echo $total_attempts; ?></h3></div>
                <i class="fas fa-clipboard-list"></i>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card summary-card" style="background: linear-gradient(135deg, #c62828, #ef5350);">
                <div><small>Pass / Fail</small><h3 style="font-size:1.3rem;"><?php echo $passed; ?>/<?php echo $failed; ?></h3></div>
                <i class="fas fa-chart-pie"></i>
            </div>
        </div>
    </div>

    <!-- Report Type Tabs -->
    <div class="card p-3 mb-4">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link <?php echo ($report_type == 'overview') ? 'active' : ''; ?>" href="?type=overview">
                    <i class="fas fa-chart-line"></i> System Overview
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($report_type == 'students') ? 'active' : ''; ?>" href="?type=students">
                    <i class="fas fa-user-graduate"></i> Student Performance
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($report_type == 'teachers') ? 'active' : ''; ?>" href="?type=teachers">
                    <i class="fas fa-chalkboard-teacher"></i> Teacher-wise Report
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($report_type == 'exams') ? 'active' : ''; ?>" href="?type=exams">
                    <i class="fas fa-file-alt"></i> Exam-wise Report
                </a>
            </li>
        </ul>
    </div>

    <!-- Report Content -->
    <div class="row g-4">
        <div class="col-lg-9">
            <div class="card p-4" style="min-height: 450px;">

                <!-- OVERVIEW REPORT -->
                <?php if ($report_type == 'overview'): ?>
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="text-primary mb-0"><i class="fas fa-chart-line"></i> System Overview Report</h5>
                        <button onclick="exportTableToCSV('overviewTable', 'system_overview_report')" class="btn btn-sm btn-success">
                            <i class="fas fa-download"></i> Download CSV
                        </button>
                    </div>
                    <table class="table table-hover align-middle" id="overviewTable">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Count</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td><i class="fas fa-user-graduate text-primary"></i> Total Students</td><td><strong><?php echo $total_students; ?></strong></td><td>Registered in the system</td></tr>
                            <tr><td><i class="fas fa-chalkboard-teacher text-purple"></i> Total Teachers</td><td><strong><?php echo $total_teachers; ?></strong></td><td>Can create exams</td></tr>
                            <tr><td><i class="fas fa-file-alt text-success"></i> Total Exams</td><td><strong><?php echo $total_exams; ?></strong></td><td>Created across all subjects</td></tr>
                            <tr><td><i class="fas fa-question-circle text-warning"></i> Total Questions</td><td><strong><?php echo $total_questions; ?></strong></td><td>MCQ questions in question bank</td></tr>
                            <tr><td><i class="fas fa-clipboard-list text-info"></i> Total Attempts</td><td><strong><?php echo $total_attempts; ?></strong></td><td>Exams completed by students</td></tr>
                            <tr><td><i class="fas fa-check-circle text-success"></i> Passed</td><td><strong><?php echo $passed; ?></strong></td><td>Score >= 50%</td></tr>
                            <tr><td><i class="fas fa-times-circle text-danger"></i> Failed</td><td><strong><?php echo $failed; ?></strong></td><td>Score &lt; 50%</td></tr>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- STUDENT PERFORMANCE REPORT -->
                <?php if ($report_type == 'students'): ?>
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="text-primary mb-0"><i class="fas fa-user-graduate"></i> Student Performance Report</h5>
                        <button onclick="exportTableToCSV('studentsTable', 'student_performance_report')" class="btn btn-sm btn-success">
                            <i class="fas fa-download"></i> Download CSV
                        </button>
                    </div>
                    <table class="table table-hover align-middle" id="studentsTable">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Student</th>
                                <th>Exams Taken</th>
                                <th>Avg. Percent</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $students = $conn->query("
                                SELECT u.user_id, u.name, u.username,
                                COUNT(a.attempt_id) as total_attempts,
                                IFNULL(AVG(CASE WHEN (SELECT SUM(marks) FROM questions q WHERE q.exam_id = a.exam_id) > 0 
                                    THEN (a.score / (SELECT SUM(marks) FROM questions q WHERE q.exam_id = a.exam_id)) * 100 
                                    ELSE 0 END), 0) as avg_pct
                                FROM users u 
                                LEFT JOIN attempts a ON u.user_id = a.student_id AND a.status='Completed'
                                WHERE u.role='Student'
                                GROUP BY u.user_id
                                ORDER BY avg_pct DESC
                            ");
                            $rank = 1;
                            while ($s = $students->fetch_assoc()):
                                $avg = round($s['avg_pct'], 1);
                                $status = $avg >= 75 ? '<span class="badge bg-success">Excellent</span>' : 
                                         ($avg >= 50 ? '<span class="badge bg-warning text-dark">Good</span>' : 
                                         ($avg > 0 ? '<span class="badge bg-danger">Needs Help</span>' : '<span class="badge bg-secondary">No Data</span>'));
                            ?>
                                <tr>
                                    <td><strong>#<?php echo $rank++; ?></strong></td>
                                    <td>
                                        <strong style="font-size:0.9rem;"><?php echo htmlspecialchars($s['name']); ?></strong><br>
                                        <small class="text-muted">@<?php echo htmlspecialchars($s['username']); ?></small>
                                    </td>
                                    <td><span class="badge bg-info text-white"><?php echo $s['total_attempts']; ?> exams</span></td>
                                    <td><strong><?php echo $avg; ?>%</strong></td>
                                    <td><?php echo $status; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- TEACHER-WISE REPORT -->
                <?php if ($report_type == 'teachers'): ?>
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="text-primary mb-0"><i class="fas fa-chalkboard-teacher"></i> Teacher-wise Report</h5>
                        <button onclick="exportTableToCSV('teachersTable', 'teacher_report')" class="btn btn-sm btn-success">
                            <i class="fas fa-download"></i> Download CSV
                        </button>
                    </div>
                    <table class="table table-hover align-middle" id="teachersTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Teacher</th>
                                <th>Exams Created</th>
                                <th>Questions Added</th>
                                <th>Total Attempts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $teachers = $conn->query("
                                SELECT u.user_id, u.name, u.username,
                                (SELECT COUNT(*) FROM exams e WHERE e.created_by = u.user_id) as total_exams,
                                (SELECT COUNT(*) FROM questions q JOIN exams e ON q.exam_id = e.exam_id WHERE e.created_by = u.user_id) as total_questions,
                                (SELECT COUNT(*) FROM attempts a JOIN exams e ON a.exam_id = e.exam_id WHERE e.created_by = u.user_id AND a.status='Completed') as total_attempts
                                FROM users u WHERE u.role='Teacher' ORDER BY total_exams DESC
                            ");
                            $i = 1;
                            while ($t = $teachers->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td>
                                        <strong style="font-size:0.9rem;"><?php echo htmlspecialchars($t['name']); ?></strong><br>
                                        <small class="text-muted">@<?php echo htmlspecialchars($t['username']); ?></small>
                                    </td>
                                    <td><span class="badge bg-primary"><?php echo $t['total_exams']; ?> exams</span></td>
                                    <td><span class="badge bg-warning text-dark"><?php echo $t['total_questions']; ?> questions</span></td>
                                    <td><span class="badge bg-info text-white"><?php echo $t['total_attempts']; ?> attempts</span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- EXAM-WISE REPORT -->
                <?php if ($report_type == 'exams'): ?>
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="text-primary mb-0"><i class="fas fa-file-alt"></i> Exam-wise Report</h5>
                        <button onclick="exportTableToCSV('examsTable', 'exam_report')" class="btn btn-sm btn-success">
                            <i class="fas fa-download"></i> Download CSV
                        </button>
                    </div>
                    <table class="table table-hover align-middle" id="examsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Exam Title</th>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Attempts</th>
                                <th>Pass Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $exams = $conn->query("
                                SELECT e.exam_id, e.title, e.subject, u.name as teacher_name,
                                (SELECT COUNT(*) FROM attempts a WHERE a.exam_id = e.exam_id AND a.status='Completed') as total_attempts,
                                (SELECT COUNT(*) FROM attempts a WHERE a.exam_id = e.exam_id AND a.status='Completed' AND (a.score / (SELECT IFNULL(SUM(marks),1) FROM questions q WHERE q.exam_id = e.exam_id)) >= 0.5) as passed_count,
                                (SELECT IFNULL(SUM(marks),1) FROM questions q WHERE q.exam_id = e.exam_id) as t_marks
                                FROM exams e JOIN users u ON e.created_by = u.user_id ORDER BY e.exam_id DESC
                            ");
                            $i = 1;
                            while ($e = $exams->fetch_assoc()):
                                $pass_rate = ($e['total_attempts'] > 0) ? round(($e['passed_count'] / $e['total_attempts']) * 100) : 0;
                            ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><strong style="font-size:0.9rem;"><?php echo htmlspecialchars($e['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($e['subject']); ?></td>
                                    <td><small><i class="fas fa-user-tie text-primary"></i> <?php echo htmlspecialchars($e['teacher_name']); ?></small></td>
                                    <td><span class="badge bg-info text-white"><?php echo $e['total_attempts']; ?></span></td>
                                    <td>
                                        <?php if ($e['total_attempts'] > 0): ?>
                                            <strong class="text-<?php echo ($pass_rate >= 50) ? 'success' : 'danger'; ?>"><?php echo $pass_rate; ?>%</strong>
                                        <?php else: ?>
                                            <span class="text-muted small">No attempts</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            </div>
        </div>

        <!-- RIGHT: TIPS PANEL -->
        <div class="col-lg-3">
            <div class="card p-4 mb-3">
                <h6 class="fw-bold mb-3"><i class="fas fa-lightbulb text-warning"></i> Report Tips</h6>
                <ol class="tips-list small">
                    <li>Use the <strong>tabs</strong> to switch between reports.</li>
                    <li>Click <strong>Download CSV</strong> to export data to Excel.</li>
                    <li>Click <strong>Print Page</strong> to save as PDF.</li>
                    <li>Student Performance ranks students by average score.</li>
                    <li>Exam-wise Report shows pass rate per exam.</li>
                </ol>
            </div>

            <div class="card p-3 mb-3" style="background:#e8f5e9;">
                <div class="d-flex align-items-start">
                    <i class="fas fa-info-circle text-success me-2 mt-1" style="font-size:1.2rem;"></i>
                    <div>
                        <strong class="text-success small d-block mb-1">Export Formats</strong>
                        <small class="text-muted">
                            <i class="fas fa-file-csv text-success"></i> CSV (Excel)<br>
                            <i class="fas fa-file-pdf text-danger"></i> PDF (via Print)
                        </small>
                    </div>
                </div>
            </div>

            <div class="text-center py-3">
                <div style="font-size:2.5rem; color:#1976d2;">📈</div>
                <p class="text-primary fst-italic fw-bold mt-2 mb-0" style="font-size:0.9rem;">
                    Data-Driven Decisions<br>Better Education!
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
        padding: 12px 15px;
        color: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .summary-card small { font-size: 0.75rem; opacity: 0.9; display: block; margin-bottom: 2px; }
    .summary-card h3 { font-weight: 700; margin: 0; font-size: 1.5rem; }
    .summary-card i { font-size: 1.8rem; opacity: 0.35; }
    .table th { font-weight: 600; font-size: 0.78rem; color: #555; border-top: none; text-transform: uppercase; padding: 12px 8px; }
    .table td { font-size: 0.88rem; vertical-align: middle; padding: 12px 8px; }
    .table tbody tr:hover { background-color: #f8f9fc; }
    .tips-list { padding-left: 18px; margin-bottom: 0; }
    .tips-list li { margin-bottom: 8px; color: #555; line-height: 1.4; }
    .nav-pills .nav-link { color: #555; font-size: 0.9rem; font-weight: 500; margin-right: 5px; }
    .nav-pills .nav-link.active { background: linear-gradient(135deg, #1976d2, #2196f3); color: white; }
    .nav-pills .nav-link i { margin-right: 5px; }
    .badge { font-size: 0.72rem; padding: 4px 8px; font-weight: 500; }
    .text-purple { color: #6a1b9a; }
    @media print {
        .sidebar, .top-navbar, .page-header-box button, .nav-pills, .btn-success, .col-lg-3 { display: none !important; }
        .main-content { margin-left: 0 !important; }
    }
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