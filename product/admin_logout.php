<?php
session_start();

// Unset all admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);

// Redirect to login page
header("Location: admin_login.php");
exit();
?>