<?php
/**
 * Contact Page - Stitch House
 */
require_once 'db_connection.php';
require_once 'includes/functions.php';
session_start();
initializeCart();

$pageTitle = 'Contact Us - Stitch House';
$pageStyles = ['contact.css'];

$message = '';
$messageType = '';

// Process contact form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $userMessage = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (empty($name) || empty($email) || empty($subject) || empty($userMessage)) {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $phone, $subject, $userMessage);

        if ($stmt->execute()) {
            $message = "Thank you for your message! We'll get back to you soon.";
            $messageType = "success";
            $name = $email = $phone = $subject = $userMessage = "";
        } else {
            $message = "Sorry, there was an error sending your message. Please try again.";
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
        .alert-success { color: #3c763d; background-color: #dff0d8; border-color: #d6e9c6; }
        .alert-error { color: #a94442; background-color: #f2dede; border-color: #ebccd1; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Banner -->
    <section class="page-banner">
        <div class="container">
            <h1>Contact Us</h1>
            <div class="breadcrumb">
                <a href="index.php">Home</a> / <span>Contact Us</span>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <div class="contact-container">
                <div class="contact-info">
                    <h2>Get in Touch</h2>
                    <p>Have questions about our fabrics or services? We'd love to hear from you.</p>

                    <div class="info-item">
                        <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="details">
                            <h3>Our Location</h3>
                            <p>Main Gulberg, Lahore, Pakistan</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="icon"><i class="fas fa-phone"></i></div>
                        <div class="details">
                            <h3>Phone Number</h3>
                            <p>0304-2292813</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="icon"><i class="fas fa-envelope"></i></div>
                        <div class="details">
                            <h3>Email Address</h3>
                            <p>info@stitchhouse.com</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="icon"><i class="fas fa-clock"></i></div>
                        <div class="details">
                            <h3>Working Hours</h3>
                            <p>Monday - Friday: 9am - 6pm<br>Saturday: 10am - 4pm<br>Sunday: Closed</p>
                        </div>
                    </div>

                    <div class="social-links">
                        <h3>Follow Us</h3>
                        <div class="social-icons">
                            <a href="#" class="facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="pinterest"><i class="fab fa-pinterest"></i></a>
                            <a href="#" class="youtube"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>

                <div class="contact-form">
                    <h2>Send us a Message</h2>

                    <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo e($message); ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <input type="text" id="name" name="name" value="<?php echo e($name ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Your Email</label>
                            <input type="email" id="email" name="email" value="<?php echo e($email ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Your Phone</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo e($phone ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" value="<?php echo e($subject ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Your Message</label>
                            <textarea id="message" name="message" rows="5" required><?php echo e($userMessage ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn-submit">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="map-container">
            <iframe width="100%" height="450" frameborder="0" scrolling="no"
                src="https://maps.google.com/maps?width=100%25&amp;height=450&amp;hl=en&amp;q=The%20University%20of%20Lahore,%20Defence%20Road,%20Lahore+(Stitch%20House)&amp;t=&amp;z=15&amp;ie=UTF8&amp;iwloc=B&amp;output=embed">
            </iframe>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/scripts.php'; ?>
</body>
</html>
