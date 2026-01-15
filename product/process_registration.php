<?php
// filepath: c:\xampp\htdocs\product\process_registration.php
require_once 'db_connection.php';
session_start();

// Handle registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $_SESSION['error_message'] = "Please fill in all fields";
        header("Location: login.html");
        exit;
    } else if ($password !== $confirm_password) {
        $_SESSION['error_message'] = "Passwords do not match";
        header("Location: login.html");
        exit;
    } else {

        // Check if email is already registered
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email is already registered. Please use a different email or login.";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $phone, $hashedPassword);
            
          if ($stmt->execute()) {
                // Get the new user ID
                $user_id = $conn->insert_id;
                
                // Set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['success_message'] = "Registration successful! Welcome to Stitch House.";
                
                // Redirect to index page
                header("Location: welcome.php");
                exit;
            } else {
                $_SESSION['error_message'] = "Registration failed: " . $conn->error;
                header("Location: login.html");
                exit;
            }
        }
    }
} else {
    // If not POST request, redirect to login page
    header("Location: login.html");
    exit;
}
?>