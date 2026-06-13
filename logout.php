<?php
// logout.php
// Secure administrative session termination script

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all admin sessions
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_username']);

// Fully destroy session if empty
if (empty($_SESSION)) {
    session_destroy();
}

header("Location: login.php");
exit();
?>
