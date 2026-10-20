<?php
// student/my_results.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/db_connect.php';
include ROOT_PATH . '/includes/header.php';

// Protect: Only Students
if ($_SESSION['role'] != 'Student') {
    header("Location: " . ROOT_PATH . "/index.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// Fetch all completed results for this student
$results = $conn->query("
    SELECT a.*, e.title, e.subject,
    (SELECT IFNULL(SUM(marks), 0) FROM questions q WHERE q.exam_id = e.exam_id) as total_marks
    FROM attempts a 
    JOIN exams e ON a.exam_id = e.exam_id 
    WHERE a.student_id = $student_id AND a.status = 'Completed'
    ORDER BY a.end_time DESC
");
?>

<div class="main-content">
    
    <!-- COLORFUL GRADIENT HEADER -->
    <div class="page-header-box mb-4">
        <div class="hero-blur-bg"></div>
        <div class="hero-content position-relative" style="z-index: 2;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-1 text-white"><i class="fas fa-chart-line me-2"></i> My Exam Results</h3>
                    <p class="text-white-50 mb-0">A complete history of all the exams you have taken.</p>
                </div>
                <a href="dashboard.php" class="btn btn-light fw-bold px-4 py-2" style="border-radius: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                    <i class="fas fa-arrow-left text-primary me-1"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- RESULTS TABLE CARD (FORCED WHITE) -->
    <div class="card p-4 shadow-sm no-hover" style="background-color: #ffffff !important; border-radius: 15px;">
        <h5 class="fw-bold mb-4" style="color: #0d47a1 !important;"><i class="fas fa-list"></i> Result History</h5>
        
        <div class="table-responsive">
            <table class="table custom-table align-middle" id="resultsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Exam Title</th>
                        <th>Subject</th>
                        <th>Date Taken</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($results->num_rows > 0) {
                        $i = 1;
                        while ($r = $results->fetch_assoc()) {
                            $percent = ($r['total_marks'] > 0) ? round(($r['score'] / $r['total_marks']) * 100) : 0;
                            $passed = $percent >= 50;
                            
                            $status_badge = $passed 
                                ? '<span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill border border-success"><i class="fas fa-check-circle me-1"></i> Passed</span>' 
                                : '<span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill border border-danger"><i class="fas fa-times-circle me-1"></i> Failed</span>';
                    ?>
                        <tr>
                            <td class="fw-bold text-muted"><?php echo $i++; ?></td>
                            <td>
                                <strong style="color: #333 !important; font-size: 1rem;"><?php echo htmlspecialchars($r['title']); ?></strong>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($r['subject']); ?></span></td>
                            <td class="text-muted small"><?php echo date('M d, Y', strtotime($r['end_time'])); ?></td>
                            <td style="color: #333 !important;"><strong><?php echo $r['score']; ?></strong> / <?php echo $r['total_marks']; ?></td>
                            <td>
                                <strong class="<?php echo $passed ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $percent; ?>%
                                </strong>
                            </td>
                            <td><?php echo $status_badge; ?></td>
                            <td>
                                <a href="view_result.php?attempt_id=<?php echo $r['attempt_id']; ?>" class="btn btn-sm btn-outline-primary" style="border-radius: 20px;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php } } else {
                        echo "<tr><td colspan='8' class='text-center text-muted py-5'>
                                <i class='fas fa-folder-open fa-3x mb-3 d-block' style='opacity: 0.3;'></i>
                                You haven't taken any exams yet.
                              </td></tr>";
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Custom CSS for this page -->
<style>
    /* Force the background to be bright */
    body {
        background-color: #f3f6f9 !important;
    }

    /* The Colorful Header */
    .page-header-box {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        background: linear-gradient(135deg, #0d47a1, #1976d2, #00897b);
    }

    /* Table Styling */
    .custom-table th { 
        font-weight: 700; 
        font-size: 0.8rem; 
        color: #555 !important; 
        border-top: none;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 15px 10px;
        background-color: #f8f9fa !important;
    }
    
    /* PERMANENT LIGHT BLUE BACKGROUND FOR ROWS */
    .custom-table td { 
        font-size: 0.9rem; 
        vertical-align: middle; 
        padding: 15px 10px;
        border-bottom: 1px solid #eee;
        background-color: #e3f2fd !important; /* Permanent light blue */
        color: #0d47a1 !important; /* Permanent dark blue text */
        transition: all 0.3s ease; /* Smooth transition for movement */
    }

    /* Force text inside to be dark blue */
    .custom-table td strong,
    .custom-table td small,
    .custom-table td span,
    .custom-table td a {
        color: #0d47a1 !important; 
    }

    /* Keep the badge colors correct */
    .custom-table td .badge.bg-success {
        color: #198754 !important;
        background-color: #d1e7dd !important;
    }
    .custom-table td .badge.bg-danger {
        color: #dc3545 !important;
        background-color: #f8d7da !important;
    }

    /* SUBTLE HOVER MOVEMENT */
    .custom-table tbody tr:hover td { 
        background-color: #e3f2fd !important; /* Keep the same color */
        transform: scale(1.01); /* Slight zoom/move effect */
        box-shadow: 0 4px 10px rgba(0,0,0,0.05); /* Add a tiny shadow */
        cursor: pointer;
    }
    
    /* Action button styling so it stays clear */
    .custom-table td .btn-outline-primary {
        color: #0d47a1 !important;
        border-color: #0d47a1 !important;
    }

        /* STOP THE HOVER EFFECT ON THE BIG RESULT HISTORY CARD */
    .no-hover:hover {
        transform: none !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.04) !important; /* Keep it flat */
        cursor: default !important;
    }
</style>

<?php include ROOT_PATH . '/includes/footer.php'; ?>