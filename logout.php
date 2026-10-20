<?php
// logout.php
session_start();
session_unset();
session_destroy();

// Redirect to the main index.php regardless of which folder we are in
header("Location: /online_exam/index.php");
exit;
?>