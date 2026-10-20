<?php
// admin/users.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/db_connect.php';
include ROOT_PATH . '/includes/header.php';

// Protect the page: Only Admin can access this
if ($_SESSION['role'] != 'Admin') {
    header("Location: " . ROOT_PATH . "/index.php");
    exit;
}

// Handle Add User Form Submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($name) || empty($email) || empty($username) || empty($password) || empty($role)) {
        $message = "<div class='alert alert-danger'>Please fill in all fields.</div>";
    } else {
        // Check if username or email already exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $message = "<div class='alert alert-danger'>Username or Email already exists.</div>";
        } else {
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (name, email, username, password, role, status) VALUES (?, ?, ?, ?, ?, 'Active')");
            $stmt->bind_param("sssss", $name, $email, $username, $password, $role);
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>User added successfully!</div>";
            } else {
                $message = "<div class='alert alert-danger'>Error adding user: " . $conn->error . "</div>";
            }
            $stmt->close();
        }
        $check->close();
    }
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Prevent deleting yourself
    if ($id == $_SESSION['user_id']) {
        $message = "<div class='alert alert-warning'>You cannot delete your own account!</div>";
    } else {
        $conn->query("DELETE FROM users WHERE user_id = $id");
        $message = "<div class='alert alert-success'>User deleted successfully!</div>";
    }
}
?>

<div class="main-content">
    <div class="top-navbar">
        <h4 class="m-0">User Management</h4>
        <div>
            <span class="fw-bold text-primary"><i class="fas fa-user-circle"></i> <?php echo $_SESSION['name']; ?></span>
        </div>
    </div>

    <?php echo $message; ?>

    <div class="row g-4">
        <!-- Add User Form -->
        <div class="col-md-4">
            <div class="card p-4">
                <h5><i class="fas fa-user-plus text-primary"></i> Add New User</h5>
                <hr>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="text" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="">Select Role</option>
                            <option value="Student">Student</option>
                            <option value="Teacher">Teacher</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" name="add_user" class="btn btn-primary w-100">Add User</button>
                </form>
            </div>
        </div>

        <!-- Users List Table -->
        <div class="col-md-8">
            <div class="card p-4">
                <h5><i class="fas fa-users text-primary"></i> All Users</h5>
                <hr>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM users ORDER BY user_id DESC";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            $i = 1;
                            while($row = $result->fetch_assoc()) {
                                $roleColor = ($row['role'] == 'Admin') ? 'primary' : (($row['role'] == 'Teacher') ? 'warning' : 'info');
                                echo "<tr>
                                        <td>{$i}</td>
                                        <td>{$row['name']}</td>
                                        <td>{$row['username']}</td>
                                        <td><span class='badge bg-{$roleColor}'>{$row['role']}</span></td>
                                        <td><span class='badge bg-success'>{$row['status']}</span></td>
                                        <td>
                                            <a href='users.php?delete={$row['user_id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this user?\");'>
                                                <i class='fas fa-trash'></i> Delete
                                            </a>
                                        </td>
                                      </tr>";
                                $i++;
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center text-muted'>No users found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/includes/footer.php'; ?>