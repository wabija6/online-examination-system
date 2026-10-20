<?php
// teacher/results.php
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

// Get exam_id from URL
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// Fetch teacher's exams for the dropdown
$exams_list = $conn->query("SELECT * FROM exams WHERE created_by = $teacher_id ORDER BY exam_id DESC");

// Default stats
$exam_info = null;
$total_attempts = 0;
$avg_score = 0;
$pass_count = 0;
$fail_count = 0;
$highest_score = 0;
$pass_rate = 0;

if ($exam_id > 0) {
    // Verify exam belongs to teacher
    $exam_info = $conn->query("SELECT * FROM exams WHERE exam_id = $exam_id AND created_by = $teacher_id")->fetch_assoc();

    if ($exam_info) {
        // Get stats
        $stats_sql = "SELECT 
            COUNT(*) as total,
            IFNULL(AVG(score), 0) as avg_score,
            IFNULL(MAX(score), 0) as highest
            FROM attempts 
            WHERE exam_id = $exam_id AND status = 'Completed'";
        $stats = $conn->query($stats_sql)->fetch_assoc();
        $total_attempts = $stats['total'];
        $avg_score = round($stats['avg_score'], 1);
        $highest_score = round($stats['highest'], 1);

        // Pass/Fail (using 50% of total exam marks as pass mark)
        // For simplicity, we'll consider a pass as 50% of total marks
        $total_marks_sql = $conn->query("SELECT IFNULL(SUM(marks), 0) as total FROM questions WHERE exam_id = $exam_id")->fetch_assoc();
        $total_marks = $total_marks_sql['total'];
        $pass_mark = $total_marks * 0.5;

        $pass_count = $conn->query("SELECT COUNT(*) as c FROM attempts WHERE exam_id = $exam_id AND status='Completed' AND score >= $pass_mark")->fetch_assoc()['c'];
        $fail_count = $total_attempts - $pass_count;

        if ($total_attempts > 0) {
            $pass_rate = round(($pass_count / $total_attempts) * 100);
        }
    }
}
?>

<div class="main-content">
    <!-- Header Banner -->
    <div class="page-header-box d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0 fw-bold"><i class="fas fa-chart-bar me-2"></i> View Exam Results</h4>
            <small>Check and analyze the performance of students in your exams.</small>
        </div>
        <?php if ($exam_info): ?>
            <a href="manage_exams.php" class="btn btn-light btn-sm fw-bold"><i class="fas fa-arrow-left"></i> Back</a>
        <?php endif; ?>
    </div>

    <!-- Exam Selector -->
    <div class="card p-3 mb-4">
        <div class="row align-items-center">
            <div class="col-md-3">
                <strong><i class="fas fa-filter text-primary"></i> Select Exam:</strong>
            </div>
            <div class="col-md-9">
                <form method="GET" action="">
                    <select name="exam_id" class="form-select" onchange="this.form.submit()">
                        <option value="0">-- Choose an Exam to View Results --</option>
                        <?php while ($e = $exams_list->fetch_assoc()): ?>
                            <option value="<?php echo $e['exam_id']; ?>" <?php echo ($exam_id == $e['exam_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($e['title']); ?> (<?php echo htmlspecialchars($e['subject']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>

    <?php if ($exam_id == 0): ?>
        <!-- No Exam Selected -->
        <div class="card p-5 text-center">
            <i class="fas fa-chart-pie fa-4x text-muted mb-3" style="opacity: 0.3;"></i>
            <h5 class="text-muted">Please select an exam from the dropdown above to view results.</h5>
        </div>
    <?php elseif (!$exam_info): ?>
        <div class="alert alert-warning">You don't have permission to view this exam's results.</div>
    <?php else: ?>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card summary-card" style="background: linear-gradient(135deg, #1976d2, #2196f3);">
                    <div>
                        <small>Total Attempts</small>
                        <h3><?php echo $total_attempts; ?></h3>
                    </div>
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card" style="background: linear-gradient(135deg, #6a1b9a, #ab47bc);">
                    <div>
                        <small>Average Score</small>
                        <h3><?php echo $avg_score; ?>/<?php echo $total_marks; ?></h3>
                    </div>
                    <i class="fas fa-star"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card" style="background: linear-gradient(135deg, #2e7d32, #66bb6a);">
                    <div>
                        <small>Pass Rate</small>
                        <h3><?php echo $pass_rate; ?>%</h3>
                    </div>
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card" style="background: linear-gradient(135deg, #ef6c00, #ff9800);">
                    <div>
                        <small>Highest Score</small>
                        <h3><?php echo $highest_score; ?>/<?php echo $total_marks; ?></h3>
                    </div>
                    <i class="fas fa-trophy"></i>
                </div>
            </div>
        </div>

        <!-- Main Row: Table + Chart -->
        <div class="row g-4">
            <!-- LEFT: RESULTS TABLE -->
            <div class="col-lg-8">
                <div class="card p-4" style="min-height: 450px;">
                    <h5 class="text-primary mb-3">
                        <i class="fas fa-list"></i> Exam Results — 
                        <span style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($exam_info['title']); ?></span>
                    </h5>
                    <div class="mb-3 small text-muted">
                        <i class="fas fa-calendar"></i> Date: <?php echo date('M d, Y', strtotime($exam_info['start_time'])); ?> | 
                        <i class="fas fa-clock"></i> Duration: <?php echo $exam_info['duration_minutes']; ?> min | 
                        <i class="fas fa-question-circle"></i> Total Questions: <?php echo $conn->query("SELECT COUNT(*) as c FROM questions WHERE exam_id=$exam_id")->fetch_assoc()['c']; ?>
                    </div>

                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 30%;">Student Name</th>
                                <th style="width: 15%;">Username</th>
                                <th style="width: 15%;">Score</th>
                                <th style="width: 15%;">Percentage</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $results = $conn->query("
                                SELECT a.*, u.name, u.username 
                                FROM attempts a 
                                JOIN users u ON a.student_id = u.user_id 
                                WHERE a.exam_id = $exam_id AND a.status = 'Completed' 
                                ORDER BY a.score DESC
                            ");
                            if ($results->num_rows > 0) {
                                $i = 1;
                                while ($r = $results->fetch_assoc()) {
                                    $percent = ($total_marks > 0) ? round(($r['score'] / $total_marks) * 100) : 0;
                                    $passed = ($r['score'] >= $pass_mark);
                                    $status_badge = $passed ? '<span class="badge bg-success">Passed</span>' : '<span class="badge bg-danger">Failed</span>';
                            ?>
                                <tr>
                                    <td><?php echo $i; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle"><?php echo strtoupper(substr($r['name'], 0, 1)); ?></div>
                                            <div class="ms-2">
                                                <strong style="font-size: 0.9rem;"><?php echo htmlspecialchars($r['name']); ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($r['username']); ?></td>
                                    <td><strong><?php echo $r['score']; ?>/<?php echo $total_marks; ?></strong></td>
                                    <td><?php echo $percent; ?>%</td>
                                    <td><?php echo $status_badge; ?></td>
                                    <td>
                                        <a href="student_result.php?attempt_id=<?php echo $r['attempt_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php
                                    $i++;
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center text-muted py-5'>
                                        <i class='fas fa-inbox fa-3x mb-3 d-block' style='opacity: 0.3;'></i>
                                        No students have taken this exam yet.
                                      </td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RIGHT: CHART + TIPS -->
            <div class="col-lg-4">
                <!-- Donut Chart -->
                <div class="card p-4 mb-3">
                    <h6 class="fw-bold mb-3"><i class="fas fa-chart-pie text-primary"></i> Performance Overview</h6>
                    <canvas id="resultsChart" height="200"></canvas>
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <span class="badge bg-success"><?php echo $pass_count; ?> Passed</span>
                            <span class="badge bg-danger"><?php echo $fail_count; ?> Failed</span>
                        </small>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card p-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-bolt text-warning"></i> Quick Actions</h6>
                    <a href="questions.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-outline-primary btn-sm mb-2 w-100 text-start">
                        <i class="fas fa-question-circle"></i> Manage Questions
                    </a>
                    <a href="manage_exams.php" class="btn btn-outline-secondary btn-sm w-100 text-start">
                        <i class="fas fa-arrow-left"></i> All My Exams
                    </a>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    <?php if ($exam_id > 0 && $exam_info): ?>
    // Donut Chart with Real Data
    const ctxPie = document.getElementById('resultsChart').getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: ['Passed', 'Failed'],
            datasets: [{
                data: [<?php echo $pass_count; ?>, <?php echo $fail_count; ?>],
                backgroundColor: ['#2e7d32', '#dc3545'],
                borderWidth: 3,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = <?php echo $total_attempts; ?>;
                            const val = context.raw;
                            const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                            return context.label + ': ' + val + ' (' + pct + '%)';
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
</script>

<!-- Custom Styles -->
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
    .summary-card small {
        font-size: 0.8rem;
        opacity: 0.9;
        display: block;
        margin-bottom: 2px;
    }
    .summary-card h3 {
        font-weight: 700;
        margin: 0;
        font-size: 1.6rem;
    }
    .summary-card i {
        font-size: 2rem;
        opacity: 0.35;
    }
    
    .avatar-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #1976d2, #64b5f6);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.95rem;
    }
    
    .table th { 
        font-weight: 600; 
        font-size: 0.78rem; 
        color: #555; 
        border-top: none;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 10px 8px;
    }
    .table td { 
        font-size: 0.88rem; 
        vertical-align: middle; 
        padding: 12px 8px;
    }
    .table tbody tr:hover { background-color: #f8f9fc; }
    .badge { font-size: 0.75rem; padding: 5px 10px; font-weight: 500; }
</style>

<?php include ROOT_PATH . '/includes/footer.php'; ?>