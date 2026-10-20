<?php
// admin/students.php
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

// --- HANDLE ADD STUDENT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $department = trim($_POST['department']);
    $level = trim($_POST['level']);

    if (empty($name) || empty($email) || empty($username) || empty($password)) {
        $message = "<div class='alert alert-danger py-2'><i class='fas fa-exclamation-circle'></i> Please fill in all required fields.</div>";
    } else {
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $message = "<div class='alert alert-danger py-2'><i class='fas fa-exclamation-circle'></i> Username or Email already exists.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, username, password, role, status) VALUES (?, ?, ?, ?, 'Student', 'Active')");
            $stmt->bind_param("ssss", $name, $email, $username, $password);
            
            if ($stmt->execute()) {
                $new_user_id = $stmt->insert_id;
                $stmt->close();

                $stmt2 = $conn->prepare("INSERT INTO students (user_id, department, level) VALUES (?, ?, ?)");
                $stmt2->bind_param("iss", $new_user_id, $department, $level);
                $stmt2->execute();
                $stmt2->close();

                $message = "<div class='alert alert-success py-2'><i class='fas fa-check-circle'></i> Student added successfully!</div>";
            } else {
                $message = "<div class='alert alert-danger py-2'>Error: " . $conn->error . "</div>";
            }
        }
        $check->close();
    }
}

// --- HANDLE UPDATE STUDENT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_student'])) {
    $edit_id = intval($_POST['edit_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);
    $level = trim($_POST['level']);
    $status = $_POST['status'];

    $profile_image = $_POST['current_image'];
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_filename = "user_" . $edit_id . "_" . time() . "." . $ext;
            $upload_dir = ROOT_PATH . "/assets/uploads/";
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . $new_filename)) {
                if (!empty($profile_image) && file_exists($upload_dir . $profile_image)) {
                    unlink($upload_dir . $profile_image);
                }
                $profile_image = $new_filename;
            }
        }
    }

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, status = ?, profile_image = ? WHERE user_id = ?");
    $stmt->bind_param("ssssi", $name, $email, $status, $profile_image, $edit_id);
    $stmt->execute();
    $stmt->close();

    $stmt2 = $conn->prepare("UPDATE students SET department = ?, level = ? WHERE user_id = ?");
    $stmt2->bind_param("ssi", $department, $level, $edit_id);
    $stmt2->execute();
    $stmt2->close();

    $message = "<div class='alert alert-success py-2'><i class='fas fa-check-circle'></i> Student updated successfully!</div>";
}

// --- HANDLE DELETE STUDENT ---
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $conn->query("DELETE FROM students WHERE user_id = $del_id");
    $conn->query("DELETE FROM users WHERE user_id = $del_id AND role = 'Student'");
    $message = "<div class='alert alert-success py-2'><i class='fas fa-check-circle'></i> Student deleted successfully!</div>";
}

// --- SEARCH ---
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$search_sql = "";
if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $search_sql = " AND (u.name LIKE '%$search_safe%' OR u.username LIKE '%$search_safe%' OR u.email LIKE '%$search_safe%')";
}

// --- FETCH STUDENTS ---
$students = $conn->query("
    SELECT u.*, s.department, s.level 
    FROM users u 
    LEFT JOIN students s ON u.user_id = s.user_id 
    WHERE u.role = 'Student' $search_sql 
    ORDER BY u.user_id DESC
");

$total_students = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='Student'")->fetch_assoc()['c'];
$active_students = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='Student' AND status='Active'")->fetch_assoc()['c'];
?>

<div class="main-content">
    <!-- Header Banner -->
    <div class="page-header-box d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0 fw-bold text-white"><i class="fas fa-user-graduate me-2"></i> Student Management</h4>
            <small class="text-white-50">Add, view and manage all students in the system.</small>
        </div>
        <div>
            <form method="GET" action="" class="d-flex">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search students..." value="<?php echo htmlspecialchars($search); ?>" style="width: 220px; border-radius: 8px 0 0 8px;">
                <button type="submit" class="btn btn-light btn-sm" style="border-radius: 0 8px 8px 0;"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <?php echo $message; ?>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card summary-card" style="background: linear-gradient(135deg, #1976d2, #2196f3);">
                <div><small>Total Students</small><h3><?php echo $total_students; ?></h3></div>
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card summary-card" style="background: linear-gradient(135deg, #2e7d32, #66bb6a);">
                <div><small>Active Students</small><h3><?php echo $active_students; ?></h3></div>
                <i class="fas fa-user-check"></i>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- ADD STUDENT FORM -->
        <div class="col-lg-4">
            <div class="card p-4">
                <h5 class="text-primary mb-3"><i class="fas fa-user-plus"></i> Add New Student</h5>
                <hr>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="text" name="password" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Department</label>
                            <select name="department" class="form-select">
                                <option value="">Select</option>
                                <option value="Information Systems">Information Systems</option>
                                <option value="Computer Science">Computer Science</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Level</label>
                            <select name="level" class="form-select">
                                <option value="">Select</option>
                                <option value="Year 1">Year 1</option>
                                <option value="Year 2">Year 2</option>
                                <option value="Year 3">Year 3</option>
                                <option value="Year 4">Year 4</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_student" class="btn btn-primary w-100">
                        <i class="fas fa-plus-circle"></i> Add Student
                    </button>
                </form>
            </div>
        </div>

        <!-- STUDENTS TABLE -->
        <div class="col-lg-8">
            <div class="card p-4" style="min-height: 500px;">
                <h5 class="text-primary mb-3"><i class="fas fa-list"></i> All Students</h5>
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Dept/Level</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($students->num_rows > 0) {
                            $i = 1;
                            while ($s = $students->fetch_assoc()) {
                        ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if(!empty($s['profile_image'])): ?>
                                            <img src="../assets/uploads/<?php echo $s['profile_image']; ?>" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; margin-right: 10px;">
                                        <?php else: ?>
                                            <div class="avatar-circle"><?php echo strtoupper(substr($s['name'], 0, 1)); ?></div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($s['name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($s['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($s['username']); ?></td>
                                <td><small><?php echo htmlspecialchars($s['department'] ?: '-'); ?> / <?php echo htmlspecialchars($s['level'] ?: '-'); ?></small></td>
                                <td>
                                    <!-- EDIT BUTTON -->
                                    <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $s['user_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="students.php?delete=<?php echo $s['user_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this student?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php
                                $i++;
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center text-muted py-5'>No students found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- EDIT MODALS ARE PLACED HERE (OUTSIDE TABLE)-->
<!-- ========================================== -->
<?php
// We re-run the query to loop through and create the modals
$modals = $conn->query("
    SELECT u.*, s.department, s.level 
    FROM users u 
    LEFT JOIN students s ON u.user_id = s.user_id 
    WHERE u.role = 'Student' $search_sql 
    ORDER BY u.user_id DESC
");
while ($s = $modals->fetch_assoc()):
?>
<div class="modal fade" id="editModal<?php echo $s['user_id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 12px;">
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-edit text-primary me-2"></i> Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="edit_id" value="<?php echo $s['user_id']; ?>">
                    <input type="hidden" name="current_image" value="<?php echo $s['profile_image']; ?>">
                    
                    <div class="text-center mb-3">
                        <?php if(!empty($s['profile_image'])): ?>
                            <img src="../assets/uploads/<?php echo $s['profile_image']; ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #1976d2;">
                        <?php else: ?>
                            <div class="avatar-circle mx-auto" style="width: 80px; height: 80px; font-size: 2rem;"><?php echo strtoupper(substr($s['name'], 0, 1)); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Change Picture</label>
                        <input type="file" name="profile_pic" class="form-control form-control-sm" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($s['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($s['email']); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($s['department']); ?>">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Level</label>
                            <input type="text" name="level" class="form-control" value="<?php echo htmlspecialchars($s['level']); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" <?php echo ($s['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($s['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_student" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>

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
    .avatar-circle {
        width: 36px; height: 36px; border-radius: 50%;
        background: linear-gradient(135deg, #1976d2, #64b5f6);
        color: white; display: flex; align-items: center; justify-content: center;
        font-weight: bold; margin-right: 10px;
    }
</style>

<?php include ROOT_PATH . '/includes/footer.php'; ?>