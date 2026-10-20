<?php
// teacher/manage_exams.php
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

// --- HANDLE DELETE EXAM ---
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $check = $conn->query("SELECT exam_id FROM exams WHERE exam_id = $del_id AND created_by = $teacher_id");
    if ($check->num_rows > 0) {
        $conn->query("DELETE FROM exams WHERE exam_id = $del_id");
        $message = "<div class='alert alert-success py-2'><i class='fas fa-check-circle'></i> Exam deleted successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger py-2'>You cannot delete this exam.</div>";
    }
}

// --- FETCH SUMMARY STATS ---
$totalExams = $conn->query("SELECT COUNT(*) as c FROM exams WHERE created_by = $teacher_id")->fetch_assoc()['c'];
$activeExams = $conn->query("SELECT COUNT(*) as c FROM exams WHERE created_by = $teacher_id AND NOW() BETWEEN start_time AND end_time")->fetch_assoc()['c'];
$totalAttempts = $conn->query("SELECT COUNT(*) as c FROM attempts a JOIN exams e ON a.exam_id = e.exam_id WHERE e.created_by = $teacher_id AND a.status='Completed'")->fetch_assoc()['c'];

// --- FETCH ALL EXAMS ---
$exams_query = $conn->query("SELECT * FROM exams WHERE created_by = $teacher_id ORDER BY exam_id DESC");
?>

<div class="main-content">
    <!-- Header Banner -->
    <div class="page-header-box d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0 fw-bold"><i class="fas fa-file-alt me-2"></i> Manage Exams</h4>
            <small>View, edit and manage all your exams in one place.</small>
        </div>
        <a href="create_exam.php" class="btn btn-light btn-sm fw-bold"><i class="fas fa-plus"></i> Create New Exam</a>
    </div>

    <?php echo $message; ?>

    <!-- Summary Cards (Compact) -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card summary-card" style="background: linear-gradient(135deg, #1976d2, #2196f3);">
                <div>
                    <small>Total Exams</small>
                    <h3><?php echo $totalExams; ?></h3>
                </div>
                <i class="fas fa-file-alt"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card" style="background: linear-gradient(135deg, #2e7d32, #66bb6a);">
                <div>
                    <small>Active Exams</small>
                    <h3><?php echo $activeExams; ?></h3>
                </div>
                <i class="fas fa-bolt"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card" style="background: linear-gradient(135deg, #6a1b9a, #ab47bc);">
                <div>
                    <small>Total Attempts</small>
                    <h3><?php echo $totalAttempts; ?></h3>
                </div>
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>

    <!-- Main Row: Table + Tips -->
    <div class="row g-4">
        <!-- LEFT: EXAMS TABLE -->
        <div class="col-lg-9">
            <div class="card p-4" style="min-height: 440px;">
                <h5 class="text-primary mb-3"><i class="fas fa-list"></i> My Exams List</h5>
                
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 30%;">Exam Title</th>
                            <th style="width: 15%;">Subject</th>
                            <th style="width: 12%;">Duration</th>
                            <th style="width: 13%;">Status</th>
                            <th style="width: 25%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($exams_query->num_rows > 0) {
                            $i = 1;
                            while ($exam = $exams_query->fetch_assoc()) {
                                $now = date('Y-m-d H:i:s');
                                if ($now < $exam['start_time']) {
                                    $status_badge = '<span class="badge bg-warning text-dark">Upcoming</span>';
                                } elseif ($now > $exam['end_time']) {
                                    $status_badge = '<span class="badge bg-secondary">Expired</span>';
                                } else {
                                    $status_badge = '<span class="badge bg-success">Active</span>';
                                }
                        ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td><strong><?php echo htmlspecialchars($exam['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($exam['subject']); ?></td>
                                <td><?php echo $exam['duration_minutes']; ?> min</td>
                                <td><?php echo $status_badge; ?></td>
                                <td>
                                    <a href="questions.php?exam_id=<?php echo $exam['exam_id']; ?>" class="btn btn-sm btn-info text-white" title="Manage Questions">
                                        <i class="fas fa-question"></i> Questions
                                    </a>
                                    <a href="results.php?exam_id=<?php echo $exam['exam_id']; ?>" class="btn btn-sm btn-success" title="View Results">
                                        <i class="fas fa-chart-bar"></i>
                                    </a>
                                    <a href="manage_exams.php?delete=<?php echo $exam['exam_id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this exam? All questions and results will also be deleted.');">
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
                                    You haven't created any exams yet.<br>
                                    <a href='create_exam.php' class='btn btn-primary btn-sm mt-3'><i class='fas fa-plus'></i> Create Your First Exam</a>
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
                <h6 class="fw-bold mb-3"><i class="fas fa-lightbulb text-warning"></i> Quick Tips</h6>
                <ol class="tips-list small">
                    <li>Click <strong>Create New Exam</strong> to add an exam.</li>
                    <li>Use the <strong>question icon</strong> to manage questions.</li>
                    <li>Click the <strong>chart icon</strong> to view student results.</li>
                    <li>Use the <strong>trash icon</strong> to delete an exam.</li>
                    <li>Check the <strong>status badge</strong> to see if it's active.</li>
                </ol>
            </div>

            <div class="card p-3 mb-3" style="background:#e8f5e9;">
                <div class="d-flex align-items-start">
                    <i class="fas fa-info-circle text-success me-2 mt-1" style="font-size:1.2rem;"></i>
                    <div>
                        <strong class="text-success small d-block mb-1">Exam Status Guide</strong>
                        <span class="badge bg-success mb-1">Active</span> = Running Now<br>
                        <span class="badge bg-warning text-dark mb-1">Upcoming</span> = Starts Later<br>
                        <span class="badge bg-secondary">Expired</span> = Ended
                    </div>
                </div>
            </div>

            <div class="text-center py-3">
                <div style="font-size:2.5rem; color:#1976d2;">📅</div>
                <p class="text-primary fst-italic fw-bold mt-2 mb-0" style="font-size:0.9rem;">
                    Plan Your Exams<br>Inspire Your Students!
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Custom Styles -->
<style>
    .page-header-box {
        background: linear-gradient(135deg, #0d47a1, #1976d2);
        color: white;
        padding: 22px 30px;
        border-radius: 12px;
    }
    .page-header-box small { opacity: 0.9; }
    
    /* Compact Summary Cards */
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
        font-size: 1.8rem;
    }
    .summary-card i {
        font-size: 2.2rem;
        opacity: 0.35;
    }
    
    /* Table Styling */
    .table th { 
        font-weight: 600; 
        font-size: 0.82rem; 
        color: #555; 
        border-top: none;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 8px;
    }
    .table td { 
        font-size: 0.9rem; 
        vertical-align: middle; 
        padding: 14px 8px;
    }
    .table tbody tr:hover { background-color: #f8f9fc; }
    
    /* Tips List */
    .tips-list { padding-left: 18px; margin-bottom: 0; }
    .tips-list li { margin-bottom: 8px; color: #555; line-height: 1.4; }
    
    .btn-sm { font-size: 0.78rem; padding: 5px 10px; }
</style>

<?php include ROOT_PATH . '/includes/footer.php'; ?>