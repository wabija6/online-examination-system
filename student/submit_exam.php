<?php
// student/submit_exam.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/db_connect.php';

if ($_SESSION['role'] != 'Student') {
    header("Location: " . ROOT_PATH . "/index.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$attempt_id = intval($_POST['attempt_id']);
$exam_id = intval($_POST['exam_id']);

// Verify attempt belongs to this student
$check = $conn->query("SELECT * FROM attempts WHERE attempt_id = $attempt_id AND student_id = $student_id")->fetch_assoc();
if (!$check) {
    header("Location: dashboard.php");
    exit;
}

// Fetch all questions for this exam
$questions = $conn->query("SELECT * FROM questions WHERE exam_id = $exam_id");

$total_score = 0;
$total_marks = 0;

while ($q = $questions->fetch_assoc()) {
    $qid = $q['question_id'];
    $total_marks += $q['marks'];

    // Get selected option from POST
    $selected_option_id = isset($_POST["q_$qid"]) ? intval($_POST["q_$qid"]) : 0;

    // Check if selected option is correct
    $is_correct = 0;
    if ($selected_option_id > 0) {
        $check_opt = $conn->query("SELECT is_correct FROM options WHERE option_id = $selected_option_id")->fetch_assoc();
        if ($check_opt && $check_opt['is_correct']) {
            $is_correct = 1;
            $total_score += $q['marks'];
        }
    }

    // Save the answer
    $stmt = $conn->prepare("INSERT INTO answers (attempt_id, question_id, selected_option_id, is_correct) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiii", $attempt_id, $qid, $selected_option_id, $is_correct);
    $stmt->execute();
    $stmt->close();
}

// Update the attempt with the final score
$conn->query("UPDATE attempts SET end_time = NOW(), score = $total_score, status = 'Completed' WHERE attempt_id = $attempt_id");

// Redirect to results page
header("Location: view_result.php?attempt_id=$attempt_id");
exit;
?>