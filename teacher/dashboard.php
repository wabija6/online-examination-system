<?php
// teacher/dashboard.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/db_connect.php';

// Protect the page: Only Teachers
if ($_SESSION['role'] != 'Teacher') {
    header("Location: " . ROOT_PATH . "/index.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$message = "";

// --- HANDLE STATUS CHANGE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_status'])) {
    $change_exam_id = intval($_POST['exam_id']);
    $new_status = $_POST['new_status'];

    $check = $conn->query("SELECT exam_id, title FROM exams WHERE exam_id = $change_exam_id AND created_by = $teacher_id");
    if ($check->num_rows > 0) {
        $exam_data = $check->fetch_assoc();
        $conn->query("UPDATE exams SET status = '$new_status' WHERE exam_id = $change_exam_id");
        
        // If publishing, send notification to all students
        if ($new_status == 'Published') {
            $students = $conn->query("SELECT user_id FROM users WHERE role = 'Student'");
            
            // We now include the link in the SQL statement
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
            
            $notif_msg = "A new exam '" . $exam_data['title'] . "' has been published. Click here to take it!";
            
            // Create the dynamic link for this specific exam
            $notif_link = "take_exam.php?exam_id=" . $change_exam_id;  
            
            while($s = $students->fetch_assoc()) {
                $notif_stmt->bind_param("iss", $s['user_id'], $notif_msg, $notif_link);
                $notif_stmt->execute();
            }
            $notif_stmt->close();
        }

        $message = "<div class='alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4' role='alert'>
                        <i class='fas fa-check-circle me-2'></i> Exam status changed to <strong>$new_status</strong> successfully!
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
    }
}

// --- HANDLE DELETE DRAFT EXAM ---
if (isset($_GET['delete_draft'])) {
    $del_id = intval($_GET['delete_draft']);
    // Verify ownership and that it is a Draft
    $check_del = $conn->query("SELECT exam_id FROM exams WHERE exam_id = $del_id AND created_by = $teacher_id AND status = 'Draft'");
    if ($check_del->num_rows > 0) {
        // Delete questions and options first (Foreign Key constraints)
        $q_res = $conn->query("SELECT question_id FROM questions WHERE exam_id = $del_id");
        while($q = $q_res->fetch_assoc()) {
            $conn->query("DELETE FROM options WHERE question_id = " . $q['question_id']);
        }
        $conn->query("DELETE FROM questions WHERE exam_id = $del_id");
        $conn->query("DELETE FROM exams WHERE exam_id = $del_id");
        
        $message = "<div class='alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4' role='alert'>
                        <i class='fas fa-trash me-2'></i> Draft exam deleted successfully!
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
    }
}

include ROOT_PATH . '/includes/header.php';

// Fetch summary stats
$totalExams = $conn->query("SELECT COUNT(*) as count FROM exams WHERE created_by = $teacher_id")->fetch_assoc()['count'];
$totalQuestions = $conn->query("SELECT COUNT(*) as count FROM questions q JOIN exams e ON q.exam_id = e.exam_id WHERE e.created_by = $teacher_id")->fetch_assoc()['count'];
$totalStudents = $conn->query("SELECT COUNT(DISTINCT a.student_id) as count FROM attempts a JOIN exams e ON a.exam_id = e.exam_id WHERE e.created_by = $teacher_id")->fetch_assoc()['count'];

$attempts_sql = "SELECT COUNT(*) as count, IFNULL(AVG(a.score), 0) as avg_score FROM attempts a JOIN exams e ON a.exam_id = e.exam_id WHERE e.created_by = $teacher_id AND a.status='Completed'";
$stats = $conn->query($attempts_sql)->fetch_assoc();
$totalAttempts = $stats['count'];
$avg_score = round($stats['avg_score'], 1);
?>

<div class="main-content">
    
    <!-- Header Banner -->
    <div class="page-header-box d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0 fw-bold text-white"><i class="fas fa-chalkboard-teacher me-2"></i> Teacher Dashboard</h4>
            <small class="text-white-50">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</small>
        </div>
        <div>
            <a href="create_exam.php" class="btn btn-light fw-bold px-4 py-2" style="border-radius: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                <i class="fas fa-plus-circle text-primary me-1"></i> Create New Exam
            </a>
        </div>
    </div>

    <?php echo $message; ?>

    <!-- SUMMARY CARDS -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #1976d2, #2196f3);">
                <div class="d-flex justify-content-between align-items-center">
                    <div><small>My Exams</small><h3><?php echo $totalExams; ?></h3></div>
                    <i class="fas fa-file-alt"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #6a1b9a, #ab47bc);">
                <div class="d-flex justify-content-between align-items-center">
                    <div><small>Total Questions</small><h3><?php echo $totalQuestions; ?></h3></div>
                    <i class="fas fa-question-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #2e7d32, #66bb6a);">
                <div class="d-flex justify-content-between align-items-center">
                    <div><small>Students Attempted</small><h3><?php echo $totalStudents; ?></h3></div>
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #ef6c00, #ff9800);">
                <div class="d-flex justify-content-between align-items-center">
                    <div><small>Avg. Score</small><h3><?php echo $avg_score; ?>%</h3></div>
                    <i class="fas fa-star"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT ROW -->
    <div class="row g-4">
        <!-- LEFT: MY EXAMS TABLE -->
        <div class="col-lg-12"> <!-- Changed to full width to fix scroll issue -->
            <div class="card p-4" style="min-height: 400px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-primary mb-0"><i class="fas fa-list"></i> My Recent Exams</h5>
                    <a href="manage_exams.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 custom-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 30%;">Exam Title</th>
                                <th style="width: 15%;">Subject</th>
                                <th style="width: 10%;">Duration</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 25%;">Actions</th> <!-- Increased width -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM exams WHERE created_by = $teacher_id ORDER BY exam_id DESC LIMIT 5";
                            $result = $conn->query($sql);
                            if ($result->num_rows > 0) {
                                $i = 1;
                                while($row = $result->fetch_assoc()) {
                                    
                                    // Logic for Status and Links
                                    if ($row['status'] == 'Draft') {
                                        $status_badge = '<span class="badge bg-warning text-dark"><i class="fas fa-pencil-alt"></i> Draft</span>';
                                        $status_click = "onclick=\"window.location='build_exam.php?exam_id={$row['exam_id']}'\";";
                                        $title_link = "build_exam.php?exam_id=" . $row['exam_id'];
                                        $delete_btn = "<a href='dashboard.php?delete_draft={$row['exam_id']}' class='btn btn-sm btn-outline-danger ms-1' onclick='return confirm(\"Delete this draft? All questions will be lost.\");' title='Delete Draft'><i class='fas fa-trash'></i></a>";
                                    } elseif ($row['status'] == 'Published') {
                                        $is_active_now = (date('Y-m-d H:i:s') >= $row['start_time'] && date('Y-m-d H:i:s') <= $row['end_time']);
                                        if($is_active_now) {
                                            $status_badge = '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Active</span>';
                                        } else {
                                            $status_badge = '<span class="badge bg-primary"><i class="fas fa-clock"></i> Published</span>';
                                        }
                                        $status_click = "data-bs-toggle='modal' data-bs-target='#statusModal{$row['exam_id']}' style='cursor:pointer;'";
                                        $title_link = "questions.php?exam_id=" . $row['exam_id'];
                                        $delete_btn = "";
                                    } else {
                                        $status_badge = '<span class="badge bg-secondary"><i class="fas fa-ban"></i> Inactive</span>';
                                        $status_click = "data-bs-toggle='modal' data-bs-target='#statusModal{$row['exam_id']}' style='cursor:pointer;'";
                                        $title_link = "questions.php?exam_id=" . $row['exam_id'];
                                        $delete_btn = "";
                                    }
                                    
                                    echo "<tr>
                                            <td>{$i}</td>
                                            <td>
                                                <a href='{$title_link}' class='text-decoration-none fw-bold' style='color: #0d47a1;'>
                                                    " . htmlspecialchars($row['title']) . "
                                                </a>
                                            </td>
                                            <td>" . htmlspecialchars($row['subject']) . "</td>
                                            <td>{$row['duration_minutes']} min</td>
                                            <td><span {$status_click}>{$status_badge}</span></td>
                                            <td>
                                                <a href='questions.php?exam_id={$row['exam_id']}' class='btn btn-sm btn-info text-white' title='Questions'><i class='fas fa-question'></i></a>
                                                <a href='results.php?exam_id={$row['exam_id']}' class='btn btn-sm btn-success ms-1' title='Results'><i class='fas fa-chart-bar'></i></a>
                                                {$delete_btn}
                                            </td>
                                          </tr>";
                                    $i++;
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center text-muted py-5'>No exams found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- BOTTOM ROW: QUICK ACTIONS -->
    <div class="row g-4 mt-2">
        <div class="col-md-8">
            <div class="card p-4">
                <h5 class="fw-bold text-primary mb-3"><i class="fas fa-bolt"></i> Quick Actions</h5>
                <div class="row g-2">
                    <div class="col-md-6"><a href="create_exam.php" class="btn btn-primary w-100 text-start py-2 px-3"><i class="fas fa-plus-circle me-2"></i> Create New Exam</a></div>
                    <div class="col-md-6"><a href="questions.php" class="btn btn-outline-primary w-100 text-start py-2 px-3"><i class="fas fa-question-circle me-2"></i> Manage Questions</a></div>
                    <div class="col-md-6"><a href="students.php" class="btn btn-outline-success w-100 text-start py-2 px-3"><i class="fas fa-user-graduate me-2"></i> View Students</a></div>
                    <div class="col-md-6"><a href="reports.php" class="btn btn-outline-warning w-100 text-start py-2 px-3"><i class="fas fa-file-pdf me-2"></i> Download Reports</a></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 h-100">
                <h6 class="fw-bold mb-3"><i class="fas fa-lightbulb text-warning"></i> Teaching Tips</h6>
                <ul class="tips-list small text-muted">
                    <li class="mb-2">Create questions with clear wording.</li>
                    <li class="mb-2">Assign higher marks to difficult questions.</li>
                    <li>Review exam results to identify struggling students.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- STATUS CHANGE MODALS (OUTSIDE TABLE)       -->
<!-- ========================================== -->
<?php
$modals = $conn->query("SELECT * FROM exams WHERE created_by = $teacher_id ORDER BY exam_id DESC LIMIT 5");
while ($m = $modals->fetch_assoc()):
    if ($m['status'] == 'Draft') continue;
    
    $is_currently_active = ($m['status'] == 'Published');
    $new_status = $is_currently_active ? 'Inactive' : 'Published';
    $action_text = $is_currently_active ? 'Deactivate' : 'Activate (Publish)';
    $action_color = $is_currently_active ? 'danger' : 'success';
?>
<div class="modal fade" id="statusModal<?php echo $m['exam_id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 12px;">
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold"><i class="fas fa-exchange-alt text-primary me-2"></i> Change Exam Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body p-4 text-center">
                    <input type="hidden" name="exam_id" value="<?php echo $m['exam_id']; ?>">
                    <input type="hidden" name="new_status" value="<?php echo $new_status; ?>">
                    
                    <h5 class="mb-3"><?php echo htmlspecialchars($m['title']); ?></h5>
                    
                    <?php if($is_currently_active): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <p class="mb-0">This exam is currently <strong>Active</strong>.<br>Do you want to <strong>Deactivate</strong> it?<br><small>Students will no longer be able to see it.</small></p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <p class="mb-0">This exam is currently <strong>Inactive</strong>.<br>Do you want to <strong>Activate (Publish)</strong> it?<br><small>Students will be notified and can take it.</small></p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-top bg-light d-flex justify-content-center">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="change_status" class="btn btn-<?php echo $action_color; ?> px-4"><?php echo $action_text; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>

<style>
    .page-header-box { background: linear-gradient(135deg, #0d47a1, #1976d2, #00897b); padding: 22px 30px; border-radius: 20px; }
    .summary-card { padding: 20px; color: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); transition: transform 0.3s ease; border: none; }
    .summary-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
    .summary-card small { font-size: 0.85rem; opacity: 0.9; display: block; margin-bottom: 5px; font-weight: 600; text-transform: uppercase; }
    .summary-card h3 { font-weight: 800; margin: 0; font-size: 2rem; }
    .summary-card i { font-size: 2.5rem; opacity: 0.3; }
    .custom-table th { font-weight: 700; font-size: 0.75rem; color: #666; border-top: none; text-transform: uppercase; padding: 12px 8px; }
    .custom-table td { font-size: 0.9rem; vertical-align: middle; padding: 14px 8px; }
    .custom-table tbody tr:hover { background-color: #f8f9fc; }
    .tips-list { padding-left: 18px; margin-bottom: 0; }
    .tips-list li { margin-bottom: 8px; line-height: 1.4; }
    .badge { transition: 0.2s; }
    .badge:hover { transform: scale(1.1); opacity: 0.8; }
</style>

<?php include ROOT_PATH . '/includes/footer.php'; ?>