<?php
// db_connect.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost:3307"; // <--- CHANGED FROM "localhost" TO MATCH XAMPP PORT
$username = "root";
$password = ""; // Leave blank for XAMPP default
$database = "online_exam_db";

// Create connection using MySQLi
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set timezone to East Africa Time (Ethiopia)
date_default_timezone_set('Africa/Addis_Ababa');

// Start session for user login management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>