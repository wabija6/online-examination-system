<?php
// teacher/students.php
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

// --- SEARCH FUNCTIONALITY ---
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$search_sql = "";
if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $search_sql = " AND (u.name LIKE '%$search_safe%' OR u.username LIKE '%$search_safe%' OR u.email LIKE '%$search_safe%')";
}

// --- FETCH ALL STUDENTS WITH THEIR STATS (for THIS teacher's exams) ---
$students_query = $conn->query("
    SELECT 
        u.user_id,
        u.name,
        u.username,
        u.email,
        u.status,
        (SELECT COUNT(*) FROM attempts a 
            JOIN exams e ON a.exam_id = e.exam_id 
            WHERE a.student_id = u.user_id AND e.created_by = $teacher_id AND a.status='Completed'
        ) as total_attempts,
        (SELECT IFNULL(ROUND(AVG(a.score), 1), 0) FROM attempts a 
            JOIN exams e ON a.exam_id = e.exam_id 
            WHERE a.student_id = u.user_id AND e.created_by = $teacher_id AND a.status='Completed'
        ) as avg_score
    FROM users u
    WHERE u.role = 'Student' $search_sql
    ORDER BY u.user_id DESC
");

// --- SUMMARY COUNTS ---
$total_students = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='Student'")->fetch_assoc()['c'];
$active_students = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='Student' AND status='Active'")->fetch_assoc()['c'];
?>

<div class="main-content">
    <!-- Header Banner -->
    <div class="page-header-box d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0 fw-bold"><i class="fas fa-user-graduate me-2"></i> Students</h4>
            <small>View all students and their exam performance.</small>
        </div>
        <div style="position: relative;">
            <form method="GET" action="" class="d-flex">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search students..." value="<?php echo htmlspecialchars($search); ?>" style="width: 250px; border-radius: 8px 0 0 8px;">
                <button type="submit" class="btn btn-light btn-sm" style="border-radius: 0 8px 8px 0;"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <!-- Summary Cards (Compact) -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card summary-card" style="background: linear-gradient(135deg, #1976d2, #2196f3);">
                <div>
                    <small>Total Students</small>
                    <h3><?php echo $total_students; ?></h3>
                </div>
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card summary-card" style="background: linear-gradient(135deg, #2e7d32, #66bb6a);">
                <div>
                    <small>Active Students</small>
                    <h3><?php echo $active_students; ?></h3>
                </div>
                <i class="fas fa-user-check"></i>
            </div>
        </div>
    </div>

    <!-- Main Content: Students Table + Tips -->
    <div class="row g-4">
        <!-- LEFT: STUDENTS TABLE -->
        <div class="col-lg-9">
            <div class="card p-4" style="min-height: 450px;">
                <h5 class="text-primary mb-3">
                    <i class="fas fa-list"></i> Students List
                    <?php if (!empty($search)): ?>
                        <small class="text-muted">(Search results for "<?php echo htmlspecialchars($search); ?>")</small>
                    <?php endif; ?>
                </h5>
                
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 25%;">Student Name</th>
                            <th style="width: 20%;">Username</th>
                            <th style="width: 15%;">Attempts</th>
                            <th style="width: 15%;">Avg. Score</th>
                            <th style="width: 20%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($students_query->num_rows > 0) {
                            $i = 1;
                            while ($s = $students_query->fetch_assoc()) {
                                // Score badge color
                                $avg = floatval($s['avg_score']);
                                if ($avg >= 75) { $score_color = 'success'; }
                                elseif ($avg >= 50) { $score_color = 'warning'; }
                                elseif ($avg > 0) { $score_color = 'danger'; }
                                else { $score_color = 'secondary'; }
                        ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle">
                                            <?php echo strtoupper(substr($s['name'], 0, 1)); ?>
                                        </div>
                                        <div class="ms-2">
                                            <strong style="font-size: 0.9rem;"><?php echo htmlspecialchars($s['name']); ?></strong><br>
                                            <small class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($s['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($s['username']); ?></td>
                                <td>
                                    <span class="badge bg-info text-white"><?php echo $s['total_attempts']; ?> exams</span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $score_color; ?>"><?php echo $avg; ?>%</span>
                                </td>
                                <td>
                                    <a href="results.php?student_id=<?php echo $s['user_id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View Results
                                    </a>
                                </td>
                            </tr>
                        <?php
                                $i++;
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center text-muted py-5'>
                                    <i class='fas fa-user-slash fa-3x mb-3 d-block' style='opacity: 0.3;'></i>
                                    No students found.
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
                    <li>Use the <strong>search bar</strong> to find students by name, username, or email.</li>
                    <li>See how many <strong>exams</strong> each student has taken.</li>
                    <li>Check their <strong>average score</strong> at a glance.</li>
                    <li>Click <strong>View Results</strong> for detailed performance.</li>
                </ol>
            </div>

            <div class="card p-3 mb-3" style="background:#e8f5e9;">
                <div class="d-flex align-items-start">
                    <i class="fas fa-info-circle text-success me-2 mt-1" style="font-size:1.2rem;"></i>
                    <div>
                        <strong class="text-success small d-block mb-2">Score Guide</strong>
                        <span class="badge bg-success mb-1">75%+</span> Excellent<br>
                        <span class="badge bg-warning mb-1">50-74%</span> Good<br>
                        <span class="badge bg-danger mb-1">Below 50%</span> Needs Help
                    </div>
                </div>
            </div>

            <div class="text-center py-3">
                <div style="font-size:2.5rem; color:#1976d2;">🎓</div>
                <p class="text-primary fst-italic fw-bold mt-2 mb-0" style="font-size:0.9rem;">
                    Guide Your Students<br>To Success!
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
    
    /* Avatar */
    .avatar-circle {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: linear-gradient(135deg, #1976d2, #64b5f6);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1rem;
    }
    
    /* Table Styling */
    .table th { 
        font-weight: 600; 
        font-size: 0.8rem; 
        color: #555; 
        border-top: none;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 8px;
    }
    .table td { 
        font-size: 0.9rem; 
        vertical-align: middle; 
        padding: 12px 8px;
    }
    .table tbody tr:hover { background-color: #f8f9fc; }
    
    /* Tips List */
    .tips-list { padding-left: 18px; margin-bottom: 0; }
    .tips-list li { margin-bottom: 8px; color: #555; line-height: 1.4; }
    
    .btn-sm { font-size: 0.78rem; padding: 5px 10px; }
    .badge { font-size: 0.78rem; padding: 5px 10px; font-weight: 500; }
</style>

<?php include ROOT_PATH . '/includes/footer.php'; ?>