<?php
// includes/mark_notifications_read.php
session_start();
if (isset($_SESSION['user_id']) && isset($_POST['mark_read'])) {
    // Assuming db_connect is in the parent folder (change path if needed)
    require_once dirname(__DIR__) . '/db_connect.php';
    $uid = $_SESSION['user_id'];
    $conn->query("UPDATE notifications SET is_read = TRUE WHERE user_id = $uid");
    echo "success";
}
?>