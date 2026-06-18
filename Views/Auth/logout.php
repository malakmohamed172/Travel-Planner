<?php

require_once '../../Controllers/AuthController.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auth = new AuthController();
$auth->logout();

// Redirect after logout
header("Location: signin.php");
exit;

?>