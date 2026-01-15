<?php
// filepath: c:\xampp\htdocs\product\db_connection.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection parameters
$host = "localhost";
$username = "root";
$password = "";  // Default XAMPP MySQL password is empty
$database = "stitch_house_db"; // Make sure this database exists

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8mb4");

// You can uncomment this section if you need to test the connection during development
/* 
if ($conn->ping()) {
    echo "Database connection is working!";
} else {
    echo "Database connection failed!";
}
*/
?>