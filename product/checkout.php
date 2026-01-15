<?php
/**
 * Checkout Page - Stitch House
 * Handles order submission and payment
 */
require_once 'db_connection.php';
require_once 'includes/functions.php';
session_start();
initializeCart();

$pageTitle = 'Checkout - Stitch House';
$pageStyles = ['checkout.css'];

$cartCount = getCartCount();
$subtotal = getCartSubtotal();
$shipping = 200;
$total = $subtotal + $shipping;
$error = null;

// Process checkout form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $paymentMethod = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($city)) {
        $error = "Please fill in all required fields.";
    } elseif (!isset($_POST['terms'])) {
        $error = "You must agree to the Terms of Service and Privacy Policy.";
    } else {
        $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
        $conn->begin_transaction();

        try {
            // Create order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, name, email, phone, address, city, total_amount, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssds", $userId, $name, $email, $phone, $address, $city, $total, $paymentMethod);

            if (!$stmt->execute()) {
                throw new Exception("Failed to create order");
            }

            $orderId = $conn->insert_id;

            // Insert order items
            foreach ($_SESSION['finalCart'] as $item) {
                $itemTotal = $item['price'] * $item['quantity'];
                $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
                $itemStmt->bind_param("isids", $orderId, $item['id'], $item['quantity'], $item['price'], $itemTotal);
                $itemStmt->execute();

                $orderItemId = $conn->insert_id;

                // Save measurements if present
                if (isset($item['measurements']) && is_array($item['measurements'])) {
                    $measurements = $item['measurements'];
                    $measurementStmt = $conn->prepare("INSERT INTO order_measurements (order_item_id, chest, waist, hip, shoulder, sleeve_length, trouser_length, kameez_length, neck, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    $chest = getMeasurementValue($measurements['chest'] ?? '')['value'];
                    $waist = getMeasurementValue($measurements['waist'] ?? '')['value'];
                    $hip = getMeasurementValue($measurements['hip'] ?? '')['value'];
                    $shoulder = getMeasurementValue($measurements['shoulder'] ?? '')['value'];
                    $sleeve = getMeasurementValue($measurements['sleeve_length'] ?? '')['value'];
                    $trouser = getMeasurementValue($measurements['trouser_length'] ?? '')['value'];
                    $kameez = getMeasurementValue($measurements['kameez_length'] ?? '')['value'];
                    $neck = getMeasurementValue($measurements['neck'] ?? '')['value'];
                    $notes = getMeasurementValue($measurements['notes'] ?? '')['value'];

                    $measurementStmt->bind_param("isssssssss", $orderItemId, $chest, $waist, $hip, $shoulder, $sleeve, $trouser, $kameez, $neck, $notes);
                    $measurementStmt->execute();
                }
            }

            // Create user account for guests
            if (!isLoggedIn()) {
                $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $checkStmt->bind_param("s", $email);
                $checkStmt->execute();
                $result = $checkStmt->get_result();

                if ($result->num_rows === 0) {
                    $tempPassword = bin2hex(random_bytes(8));
                    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

                    $userStmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
                    $userStmt->bind_param("ssss", $name, $email, $phone, $hashedPassword);
                    $userStmt->execute();
                    $userId = $conn->insert_id;
                    $_SESSION['temp_password'] = $tempPassword;
                } else {
                    $userId = $result->fetch_assoc()['id'];
                }

                $updateStmt = $conn->prepare("UPDATE orders SET user_id = ? WHERE id = ?");
                $updateStmt->bind_param("ii", $userId, $orderId);
                $updateStmt->execute();
            }

            $conn->commit();

            // Clear cart and redirect
            $_SESSION['finalCart'] = [];
            $_SESSION['shoppingCart'] = [];
            $_SESSION['order_success'] = true;
            $_SESSION['order_id'] = $orderId;

            header("Location: order-confirmation.php");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error = "An error occurred while processing your order. Please try again.";
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

    <section class="checkout-section">
        <div class="container">
            <h1>Checkout</h1>

            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if (empty($_SESSION['finalCart'])): ?>
            <div class="empty-cart-message">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your cart is empty</h2>
                <p>Browse our collection and add some fabrics to your cart</p>
                <a href="order.php" class="btn-primary">Shop Now</a>
            </div>
            <?php else: ?>

            <div class="checkout-container">
                <div class="checkout-form">
                    <h2>Shipping Information</h2>
                    <form method="post">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" required
                                   value="<?php echo isLoggedIn() ? e($_SESSION['user_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required
                                   value="<?php echo isLoggedIn() ? e($_SESSION['user_email'] ?? '') : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" required>
                        </div>

                        <h2>Payment Method</h2>
                        <div class="payment-methods">
                            <div class="payment-method">
                                <input type="radio" id="cash_on_delivery" name="payment_method" value="cash_on_delivery" checked>
                                <label for="cash_on_delivery">Cash on Delivery</label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" id="bank_transfer" name="payment_method" value="bank_transfer">
                                <label for="bank_transfer">Bank Transfer</label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" id="credit_card" name="payment_method" value="credit_card">
                                <label for="credit_card">Credit Card</label>
                            </div>
                        </div>

                        <div class="form-group terms-checkbox">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                        </div>

                        <button type="submit" class="btn-primary">Place Order</button>
                    </form>
                </div>

                <div class="order-summary">
                    <h2>Order Summary</h2>
                    <div class="cart-items">
                        <?php foreach ($_SESSION['finalCart'] as $item): ?>
                        <div class="summary-item">
                            <div class="item-image">
                                <img src="<?php echo e(getImagePath($item['image'])); ?>"
                                     alt="<?php echo e($item['name']); ?>"
                                     onerror="this.src='images/placeholder.jpg'">
                            </div>
                            <div class="item-details">
                                <h3><?php echo e($item['name']); ?></h3>
                                <p class="item-price"><?php echo formatPrice($item['price']); ?> x <?php echo $item['quantity']; ?></p>
                            </div>
                            <div class="item-total">
                                <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-totals">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span><?php echo formatPrice($shipping); ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span><?php echo formatPrice($total); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/scripts.php'; ?>
</body>
</html>
