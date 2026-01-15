<?php
// filepath: c:\xampp\htdocs\product\logout.php
session_start();
session_destroy();
header("Location: index.php");
exit;
?>