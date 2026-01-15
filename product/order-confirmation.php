<?php
/**
 * Order Confirmation Page - Stitch House
 */
require_once 'includes/functions.php';
session_start();

$pageTitle = 'Order Confirmation - Stitch House';

// Check if order was placed
if (!isset($_SESSION['order_success'])) {
    header('Location: cart.php');
    exit();
}

$orderId = $_SESSION['order_id'] ?? 'N/A';

// Clear cart and order data
$_SESSION['finalCart'] = [];
$_SESSION['shoppingCart'] = [];
unset($_SESSION['order_success'], $_SESSION['order_id'], $_SESSION['order_details']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .confirmation-section { padding: 60px 0; text-align: center; }
        .confirmation-container { max-width: 600px; margin: 0 auto; padding: 40px; background: #fff; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .confirmation-icon { font-size: 80px; color: #28a745; margin-bottom: 20px; }
        .confirmation-container h2 { font-family: 'Playfair Display', serif; margin-bottom: 15px; }
        .order-number { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .order-number strong { color: #d4af37; font-size: 1.2em; }
        .confirmation-details { color: #666; line-height: 1.8; margin: 20px 0; }
        .confirmation-actions { margin-top: 30px; }
        .confirmation-actions a { display: inline-block; padding: 12px 30px; margin: 5px; border-radius: 5px; text-decoration: none; }
        .btn-primary { background: #000; color: #fff; }
        .btn-secondary { background: #f8f9fa; color: #000; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="page-banner">
        <div class="container">
            <h1>Order Confirmation</h1>
            <div class="breadcrumb">
                <a href="index.php">Home</a> / <span>Order Confirmation</span>
            </div>
        </div>
    </section>

    <section class="confirmation-section">
        <div class="container">
            <div class="confirmation-container">
                <div class="confirmation-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Thank You For Your Order!</h2>
                <p>Your order has been placed successfully.</p>

                <?php if ($orderId !== 'N/A'): ?>
                <div class="order-number">
                    <p>Order #: <strong><?php echo e($orderId); ?></strong></p>
                </div>
                <?php endif; ?>

                <div class="confirmation-details">
                    <p>We've received your order and will begin processing it right away.</p>
                    <p>You should receive an email confirmation shortly.</p>
                </div>

                <div class="confirmation-actions">
                    <a href="order.php" class="btn-primary">Continue Shopping</a>
                    <?php if (isLoggedIn()): ?>
                    <a href="myorder.php" class="btn-secondary">View My Orders</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/scripts.php'; ?>
</body>
</html>
