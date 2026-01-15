<?php
/**
 * Login/Register Page - Stitch House
 */
require_once 'db_connection.php';
require_once 'includes/functions.php';
session_start();
initializeCart();

$pageTitle = 'Login - Stitch House';
$pageStyles = ['login.css'];

$error = '';
$success = '';
$activeTab = 'login';

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'login') {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = "Please fill in all fields";
        } else {
            $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Invalid password";
                }
            } else {
                $error = "No account found with that email";
            }
        }
    } elseif ($formType === 'register') {
        $activeTab = 'register';
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($email) || empty($phone) || empty($password)) {
            $error = "Please fill in all fields";
        } elseif ($password !== $confirmPassword) {
            $error = "Passwords do not match";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address";
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();

            if ($stmt->get_result()->num_rows > 0) {
                $error = "Email is already registered";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $email, $phone, $hashedPassword);

                if ($stmt->execute()) {
                    $success = "Account created successfully! Please login.";
                    $activeTab = 'login';
                } else {
                    $error = "Error creating account. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="page-banner">
        <div class="container">
            <h1>Account Access</h1>
            <div class="breadcrumb">
                <a href="index.php">Home</a> / <span>Sign In</span>
            </div>
        </div>
    </section>

    <section class="login-section">
        <div class="container">
            <div class="login-container">
                <div class="form-tabs">
                    <button class="tab-btn <?php echo $activeTab === 'login' ? 'active' : ''; ?>" data-tab="login">Sign In</button>
                    <button class="tab-btn <?php echo $activeTab === 'register' ? 'active' : ''; ?>" data-tab="register">Register</button>
                </div>

                <div class="form-content">
                    <!-- Login Form -->
                    <div id="login-form" class="form-panel <?php echo $activeTab === 'login' ? 'active' : ''; ?>">
                        <h2>Welcome Back</h2>
                        <p class="form-intro">Sign in to your account to continue shopping.</p>

                        <?php if ($error && $activeTab === 'login'): ?>
                        <div class="alert alert-danger"><?php echo e($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo e($success); ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="form_type" value="login">
                            <div class="form-group">
                                <label for="login-email">Email Address</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" id="login-email" name="email" required placeholder="Enter your email">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="login-password">Password</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" id="login-password" name="password" required placeholder="Enter your password">
                                    <button type="button" class="toggle-password"><i class="far fa-eye"></i></button>
                                </div>
                            </div>
                            <div class="form-options">
                                <div class="remember-me">
                                    <input type="checkbox" id="remember-me" name="remember">
                                    <label for="remember-me">Remember me</label>
                                </div>
                                <a href="#" class="forgot-password">Forgot password?</a>
                            </div>
                            <button type="submit" class="btn-primary btn-login">
                                <i class="fas fa-sign-in-alt"></i> Sign In
                            </button>
                        </form>
                    </div>

                    <!-- Register Form -->
                    <div id="register-form" class="form-panel <?php echo $activeTab === 'register' ? 'active' : ''; ?>">
                        <h2>Create an Account</h2>
                        <p class="form-intro">Register for personalized shopping experience.</p>

                        <?php if ($error && $activeTab === 'register'): ?>
                        <div class="alert alert-danger"><?php echo e($error); ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="form_type" value="register">
                            <div class="form-group">
                                <label for="register-name">Full Name</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-user"></i>
                                    <input type="text" id="register-name" name="name" required placeholder="Enter your full name">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="register-email">Email Address</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" id="register-email" name="email" required placeholder="Enter your email">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="register-phone">Phone Number</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-phone"></i>
                                    <input type="tel" id="register-phone" name="phone" required placeholder="Enter your phone number">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="register-password">Password</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" id="register-password" name="password" required placeholder="Create a password" minlength="8">
                                    <button type="button" class="toggle-password"><i class="far fa-eye"></i></button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="register-confirm-password">Confirm Password</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" id="register-confirm-password" name="confirm_password" required placeholder="Confirm your password">
                                </div>
                            </div>
                            <div class="form-options">
                                <div class="terms">
                                    <input type="checkbox" id="terms" name="terms" required>
                                    <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                                </div>
                            </div>
                            <button type="submit" class="btn-primary btn-register">
                                <i class="fas fa-user-plus"></i> Create Account
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/scripts.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.form-panel').forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(btn.dataset.tab + '-form').classList.add('active');
            });
        });

        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'far fa-eye-slash';
                } else {
                    input.type = 'password';
                    icon.className = 'far fa-eye';
                }
            });
        });

        // Auto-hide alerts
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => alert.style.display = 'none', 5000);
        });
    });
    </script>
</body>
</html>
