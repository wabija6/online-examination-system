<?php
// admin/teachers.php
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

// --- HANDLE ADD TEACHER ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_teacher'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($username) || empty($password)) {
        $message = "<div class='alert alert-danger py-2'><i class='fas fa-exclamation-circle'></i> Please fill in all required fields.</div>";
    } else {
        // Check if username or email exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $message = "<div class='alert alert-danger py-2'><i class='fas fa-exclamation-circle'></i> Username or Email already exists.</div>";
        } else {
            // Insert new Teacher
            $stmt = $conn->prepare("INSERT INTO users (name, email, username, password, role, status) VALUES (?, ?, ?, ?, 'Teacher', 'Active')");
            $stmt->bind_param("ssss", $name, $email, $username, $password);
            
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success py-2'><i class='fas fa-check-circle'></i> Teacher added successfully!</div>";
            } else {
                $message = "<div class='alert alert-danger py-2'>Error: " . $conn->error . "</div>";
            }
            $stmt->close();
        }
        $check->close();
    }
}

// --- HANDLE DELETE TEACHER ---
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    // Delete from users (exams will be deleted due to ON DELETE CASCADE if set, or we should delete them manually)
    $conn->query("DELETE FROM exams WHERE created_by = $del_id");
    $conn->query("DELETE FROM users WHERE user_id = $del_id AND role = 'Teacher'");
    $message = "<div class='alert alert-success py-2'><i class='fas fa-check-circle'></i> Teacher deleted successfully!</div>";
}

// --- SEARCH ---
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$search_sql = "";
if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $search_sql = " AND (name LIKE '%$search_safe%' OR username LIKE '%$search_safe%' OR email LIKE '%$search_safe%')";
}

// --- FETCH TEACHERS ---
$teachers = $conn->query("SELECT * FROM users WHERE role = 'Teacher' $search_sql ORDER BY user_id DESC");

// Summary counts
$total_teachers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='Teacher'")->fetch_assoc()['c'];
$active_teachers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='Teacher' AND status='Active'")->fetch_assoc()['c'];
?>

<div class="main-content">
    <!-- Header Banner -->
    <div class="page-header-box d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0 fw-bold"><i class="fas fa-chalkboard-teacher me-2"></i> Teacher Management</h4>
            <small>Add, view and manage all teachers in the system.</small>
        </div>
        <div>
            <form method="GET" action="" class="d-flex">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search teachers..." value="<?php echo htmlspecialchars($search); ?>" style="width: 220px; border-radius: 8px 0 0 8px;">
                <button type="submit" class="btn btn-light btn-sm" style="border-radius: 0 8px 8px 0;"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <?php echo $message; ?>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card summary-card" style="background: linear-gradient(135deg, #1976d2, #2196f3);">
                <div><small>Total Teachers</small><h3><?php echo $total_teachers; ?></h3></div>
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card summary-card" style="background: linear-gradient(135deg, #2e7d32, #66bb6a);">
                <div><small>Active Teachers</small><h3><?php echo $active_teachers; ?></h3></div>
                <i class="fas fa-user-check"></i>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- LEFT: ADD TEACHER FORM -->
        <div class="col-lg-4">
            <div class="card p-4">
                <h5 class="text-primary mb-3"><i class="fas fa-user-plus"></i> Add New Teacher</h5>
                <hr>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Mr. Tolera Yadata" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" placeholder="e.g., teacher@example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" placeholder="e.g., teacher1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="text" name="password" class="form-control" placeholder="Enter a password" required>
                    </div>
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="fas fa-info-circle"></i> Teachers can create exams and manage questions once they log in.
                    </div>
                    <button type="submit" name="add_teacher" class="btn btn-primary w-100">
                        <i class="fas fa-plus-circle"></i> Add Teacher
                    </button>
                </form>
            </div>
        </div>

        <!-- RIGHT: TEACHERS TABLE -->
        <div class="col-lg-8">
            <div class="card p-4" style="min-height: 500px;">
                <h5 class="text-primary mb-3">
                    <i class="fas fa-list"></i> All Teachers 
                    <?php if (!empty($search)): ?>
                        <small class="text-muted">(Search: "<?php echo htmlspecialchars($search); ?>")</small>
                    <?php endif; ?>
                </h5>

                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 40%;">Name</th>
                            <th style="width: 20%;">Username</th>
                            <th style="width: 20%;">Email</th>
                            <th style="width: 15%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($teachers->num_rows > 0) {
                            $i = 1;
                            while ($t = $teachers->fetch_assoc()) {
                        ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle teacher-avatar"><?php echo strtoupper(substr($t['name'], 0, 1)); ?></div>
                                        <div class="ms-2">
                                            <strong style="font-size: 0.9rem;"><?php echo htmlspecialchars($t['name']); ?></strong><br>
                                            <small class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($t['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($t['username']); ?></td>
                                <td><small><?php echo htmlspecialchars($t['email']); ?></small></td>
                                <td>
                                    <a href="teachers.php?delete=<?php echo $t['user_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this teacher? All their exams will also be deleted.');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php
                                $i++;
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center text-muted py-5'>
                                    <i class='fas fa-user-slash fa-3x mb-3 d-block' style='opacity: 0.3;'></i>
                                    No teachers found. Add your first teacher using the form on the left.
                                  </td></tr>";
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
    .avatar-circle {
        width: 36px; height: 36px; border-radius: 50%;
        background: linear-gradient(135deg, #1976d2, #64b5f6);
        color: white; display: flex; align-items: center; justify-content: center;
        font-weight: bold; font-size: 0.95rem;
    }
    .teacher-avatar {
        background: linear-gradient(135deg, #6a1b9a, #ab47bc);
    }
    .table th { font-weight: 600; font-size: 0.78rem; color: #555; border-top: none; text-transform: uppercase; padding: 10px 8px; }
    .table td { font-size: 0.88rem; vertical-align: middle; padding: 12px 8px; }
    .table tbody tr:hover { background-color: #f8f9fc; }
</style>

<?php include ROOT_PATH . '/includes/footer.php'; ?>