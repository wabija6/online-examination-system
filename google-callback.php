<?php
// google-callback.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'vendor/autoload.php';

// Load local keys if they exist, otherwise load the template
if (file_exists(__DIR__ . '/google-config.local.php')) {
    require_once 'google-config.local.php';
} else {
    require_once 'google-config.php';
}

require_once 'db_connect.php'; // This also starts the session

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);

if (isset($_GET['code'])) {
    try {
        // Get the access token from Google
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (isset($token['error'])) {
            die("Google Login Error: " . $token['error_description']);
        }
        
        $client->setAccessToken($token['access_token']);

        // Get the user's profile info from Google
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        $email = $google_account_info->email;
        $name = $google_account_info->name;

        // Check if this user already exists in our database
        $stmt = $conn->prepare("SELECT user_id, name, role, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User exists! Log them in.
            $user = $result->fetch_assoc();
            
            if ($user['status'] !== 'Active') {
                die("Your account is inactive. Please contact admin.");
            }

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] == 'Admin') {
                header("Location: admin/dashboard.php");
            } elseif ($user['role'] == 'Teacher') {
                header("Location: teacher/dashboard.php");
            } else {
                header("Location: student/dashboard.php");
            }
            exit;

        } else {
            // User does NOT exist. Create a new Student account.
            $username = explode('@', $email)[0] . rand(100, 999);
            $password = bin2hex(random_bytes(8)); // Random secure password
            $role = 'Student';
            $status = 'Active';

            $insert = $conn->prepare("INSERT INTO users (name, email, username, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->bind_param("ssssss", $name, $email, $username, $password, $role, $status);
            
            if ($insert->execute()) {
                $new_user_id = $insert->insert_id;
                
                // Also insert into the students table
                $insert_student = $conn->prepare("INSERT INTO students (user_id) VALUES (?)");
                $insert_student->bind_param("i", $new_user_id);
                $insert_student->execute();

                // Log them in automatically
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;

                header("Location: student/dashboard.php");
                exit;
            } else {
                die("Error creating your account: " . $conn->error);
            }
        }

    } catch (Exception $e) {
        die("Authentication failed: " . $e->getMessage());
    }
} else {
    // No code provided by Google, redirect back to login
    header("Location: login.php");
    exit;
}
?>