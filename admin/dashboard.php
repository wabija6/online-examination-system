<?php
// admin/dashboard.php
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

// Fetch counts
$totalStudents = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='Student'")->fetch_assoc()['count'];
$totalTeachers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='Teacher'")->fetch_assoc()['count'];
$totalExams = $conn->query("SELECT COUNT(*) as count FROM exams")->fetch_assoc()['count'];
$totalQuestions = $conn->query("SELECT COUNT(*) as count FROM questions")->fetch_assoc()['count'];
$totalAttempts = $conn->query("SELECT COUNT(*) as count FROM attempts WHERE status='Completed'")->fetch_assoc()['count'];
?>

<div class="main-content">
    <!-- Header Banner -->
    <div class="page-header-box d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0 fw-bold text-white"><i class="fas fa-tachometer-alt me-2"></i> Admin Dashboard</h4>
            <small class="text-white-50">Overview of the entire system.</small>
        </div>
        <div class="text-white-50 small"><i class="fas fa-clock"></i> <?php echo date('D, M d, Y | h:i A'); ?></div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-sm-4">
            <div class="card summary-card" style="background: linear-gradient(135deg, #1976d2, #2196f3);">
                <div>
                    <small>Total Students</small>
                    <h3><?php echo $totalStudents; ?></h3>
                </div>
                <i class="fas fa-user-graduate"></i>
            </div>
        </div>
        <div class="col-md-2 col-sm-4">
            <div class="card summary-card" style="background: linear-gradient(135deg, #2e7d32, #66bb6a);">
                <div>
                    <small>Total Teachers</small>
                    <h3><?php echo $totalTeachers; ?></h3>
                </div>
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
        </div>
        <div class="col-md-2 col-sm-4">
            <div class="card summary-card" style="background: linear-gradient(135deg, #6a1b9a, #ab47bc);">
                <div>
                    <small>Total Exams</small>
                    <h3><?php echo $totalExams; ?></h3>
                </div>
                <i class="fas fa-file-alt"></i>
            </div>
        </div>
        <div class="col-md-2 col-sm-4">
            <div class="card summary-card" style="background: linear-gradient(135deg, #ef6c00, #ff9800);">
                <div>
                    <small>Total Questions</small>
                    <h3><?php echo $totalQuestions; ?></h3>
                </div>
                <i class="fas fa-question-circle"></i>
            </div>
        </div>
        <div class="col-md-2 col-sm-4">
            <div class="card summary-card" style="background: linear-gradient(135deg, #00897b, #26a69a);">
                <div>
                    <small>Total Results</small>
                    <h3><?php echo $totalAttempts; ?></h3>
                </div>
                <i class="fas fa-chart-bar"></i>
            </div>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="card p-4 mb-4">
        <h5 class="fw-bold text-primary mb-3"><i class="fas fa-bolt"></i> Quick Actions</h5>
        <div class="row g-3">
            <div class="col-md-3">
                <a href="students.php" class="btn btn-outline-primary w-100 text-start py-2">
                    <i class="fas fa-user-plus me-2"></i> Add Student
                </a>
            </div>
            <div class="col-md-3">
                <a href="teachers.php" class="btn btn-outline-success w-100 text-start py-2">
                    <i class="fas fa-chalkboard-teacher me-2"></i> Add Teacher
                </a>
            </div>
            <div class="col-md-3">
                <a href="users.php" class="btn btn-outline-info w-100 text-start py-2">
                    <i class="fas fa-users-cog me-2"></i> Manage Users
                </a>
            </div>
            <div class="col-md-3">
                <a href="reports.php" class="btn btn-outline-warning w-100 text-start py-2">
                    <i class="fas fa-file-pdf me-2"></i> Download Reports
                </a>
            </div>
        </div>
    </div>

    <!-- CHARTS ROW -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card p-4 h-100">
                <h5><i class="fas fa-chart-bar text-primary"></i> Exam Performance</h5>
                <canvas id="performanceChart" height="100"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 h-100">
                <h5><i class="fas fa-chart-pie text-primary"></i> Results Summary</h5>
                <canvas id="resultsChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- RECENT TABLES -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card p-4">
                <h5><i class="fas fa-list text-primary"></i> Recent Exams</h5>
                <table class="table table-hover mt-3">
                    <thead><tr><th>#</th><th>Exam Title</th><th>Subject</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php
                        $exam_result = $conn->query("SELECT * FROM exams ORDER BY exam_id DESC LIMIT 5");
                        if ($exam_result->num_rows > 0) {
                            $i = 1;
                            while($row = $exam_result->fetch_assoc()) {
                                echo "<tr><td>{$i}</td><td>{$row['title']}</td><td>{$row['subject']}</td><td><a href='exams.php' class='btn btn-sm btn-primary'>View</a></td></tr>";
                                $i++;
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center text-muted'>No exams created yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-4">
                <h5><i class="fas fa-user-graduate text-primary"></i> Recent Students</h5>
                <table class="table table-hover mt-3">
                    <thead><tr><th>#</th><th>Name</th><th>Username</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php
                        $student_result = $conn->query("SELECT * FROM users WHERE role='Student' ORDER BY user_id DESC LIMIT 5");
                        if ($student_result->num_rows > 0) {
                            $i = 1;
                            while($row = $student_result->fetch_assoc()) {
                                echo "<tr><td>{$i}</td><td>{$row['name']}</td><td>{$row['username']}</td><td><span class='badge bg-success'>{$row['status']}</span></td></tr>";
                                $i++;
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center text-muted'>No students registered yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .page-header-box {
        background: linear-gradient(135deg, #0d47a1, #1976d2, #00897b);
        padding: 22px 30px;
        border-radius: 20px;
    }
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
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Bar Chart
    const ctxBar = document.getElementById('performanceChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ['Math', 'English', 'Computer', 'Physics', 'Chemistry'],
            datasets: [
                { label: 'Passed', data: [20, 18, 16, 14, 12], backgroundColor: '#1976d2' },
                { label: 'Failed', data: [5, 7, 9, 11, 13], backgroundColor: '#ffc107' }
            ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    // Donut Chart
    const ctxPie = document.getElementById('resultsChart').getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: ['Passed (78%)', 'Failed (22%)'],
            datasets: [{ data: [78, 22], backgroundColor: ['#2e7d32', '#dc3545'] }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
</script>

<?php include ROOT_PATH . '/includes/footer.php'; ?>