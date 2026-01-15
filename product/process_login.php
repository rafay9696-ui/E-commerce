<?php
// filepath: c:\xampp\htdocs\product\process_login.php
require_once 'db_connection.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $_SESSION['error_message'] = "Please enter both email and password";
        header("Location: login.html");
        exit;
    }
    
    // Check user credentials
    $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['success_message'] = "Login successful! Welcome back, " . $user['name'] . ".";
            
            // Redirect to index page
            header("Location: welcome.php");
            exit;
        } else {
            $_SESSION['error_message'] = "Invalid password";
            header("Location: login.html");
            exit;
        }
    } else {
        $_SESSION['error_message'] = "User not found";
        header("Location: login.html");
        exit;
    }
} else {
    // If not POST request, redirect to login page
    header("Location: login.html");
    exit;
}
?>