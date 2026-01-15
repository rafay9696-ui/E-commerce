<?php
/**
 * Shopping Cart Page - Stitch House
 * Handles cart display and cart actions (add, remove, update quantity)
 */
require_once 'db_connection.php';
require_once 'includes/functions.php';
session_start();
initializeCart();

$pageTitle = 'Shopping Cart - Stitch House';
$pageStyles = ['cart.css', 'checkout.css'];

// Process cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'clear_cart':
            $_SESSION['finalCart'] = [];
            $_SESSION['shoppingCart'] = [];
            if (isLoggedIn()) {
                $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
            }
            break;

        case 'remove_item':
            $index = (int)($_POST['index'] ?? -1);
            if (isset($_SESSION['finalCart'][$index])) {
                if (isLoggedIn() && isset($_SESSION['finalCart'][$index]['id'])) {
                    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                    $stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['finalCart'][$index]['id']);
                    $stmt->execute();
                }
                array_splice($_SESSION['finalCart'], $index, 1);
                $_SESSION['shoppingCart'] = $_SESSION['finalCart'];
            }
            break;

        case 'update_quantity':
            $index = (int)($_POST['index'] ?? -1);
            $quantity = max(1, (int)($_POST['quantity'] ?? 1));
            if (isset($_SESSION['finalCart'][$index])) {
                $_SESSION['finalCart'][$index]['quantity'] = $quantity;
                if (isLoggedIn() && isset($_SESSION['finalCart'][$index]['id'])) {
                    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                    $stmt->bind_param("iii", $quantity, $_SESSION['user_id'], $_SESSION['finalCart'][$index]['id']);
                    $stmt->execute();
                }
                $_SESSION['shoppingCart'] = $_SESSION['finalCart'];
            }
            break;

        case 'add_to_cart':
            $id = $_POST['id'] ?? '';
            $name = $_POST['name'] ?? '';
            $price = floatval($_POST['price'] ?? 0);
            $image = $_POST['image'] ?? '';

            if ($id && $name && $price && $image) {
                $found = false;
                foreach ($_SESSION['finalCart'] as $key => $cartItem) {
                    if ($cartItem['id'] == $id) {
                        $_SESSION['finalCart'][$key]['quantity']++;
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $_SESSION['finalCart'][] = [
                        'id' => $id,
                        'name' => $name,
                        'price' => $price,
                        'image' => $image,
                        'quantity' => 1
                    ];
                }
                $_SESSION['shoppingCart'] = $_SESSION['finalCart'];
            }
            break;
    }

    header('Location: cart.php');
    exit;
}

// Calculate totals
$cartItems = $_SESSION['finalCart'] ?? [];
$cartCount = getCartCount();
$subtotal = getCartSubtotal();
$shipping = 200;
$total = $subtotal + $shipping;

// Get recommended products
$recommendedProducts = [];
$stmt = $conn->prepare("SELECT * FROM products ORDER BY RAND() LIMIT 4");
if ($stmt && $stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recommendedProducts[] = $row;
    }
}

if (empty($recommendedProducts)) {
    $recommendedProducts = [
        ['id' => 'cotton1', 'name' => 'Premium Cotton - Blue', 'price' => 1500, 'image' => 'c1.1.jpg'],
        ['id' => 'lattha1', 'name' => 'Premium Lattha - Black', 'price' => 1500, 'image' => 'l1.3.jpg'],
        ['id' => 'boski1', 'name' => 'Premium Boski - White', 'price' => 1900, 'image' => 'b1.1.jpg'],
        ['id' => 'linen1', 'name' => 'Premium Linen - Natural', 'price' => 2200, 'image' => 'ln1.1.jpg']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Banner -->
    <section class="page-banner">
        <div class="container">
            <h1>Shopping Cart</h1>
            <div class="breadcrumb">
                <a href="index.php">Home</a> / <span>Shopping Cart</span>
            </div>
        </div>
    </section>

    <!-- Cart Section -->
    <section class="cart-section">
        <div class="container">
            <div class="cart-container">
                <?php if (empty($cartItems)): ?>
                <div class="empty-cart-message">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Your cart is empty</h2>
                    <p>Browse our collection and add some fabrics to your cart</p>
                    <a href="order.php" class="btn-primary">Shop Now</a>
                </div>
                <?php else: ?>
                <div class="cart-content">
                    <div class="cart-items">
                        <h2>Cart Items</h2>
                        <div class="cart-items-container">
                            <?php foreach ($cartItems as $index => $item): ?>
                            <div class="cart-item">
                                <div class="cart-item-image">
                                    <img src="<?php echo e(getImagePath($item['image'])); ?>"
                                         alt="<?php echo e($item['name']); ?>"
                                         onerror="this.src='images/placeholder.jpg'">
                                </div>
                                <div class="cart-item-details">
                                    <h3><?php echo e($item['name']); ?></h3>
                                    <p class="price"><?php echo formatPrice($item['price']); ?></p>

                                    <?php if (isset($item['measurements']) && is_array($item['measurements'])): ?>
                                    <div class="measurements-display">
                                        <h4>Measurements</h4>
                                        <table class="measurements-table">
                                            <?php
                                            $labels = [
                                                'chest' => 'Chest', 'waist' => 'Waist', 'hip' => 'Hip',
                                                'shoulder' => 'Shoulder', 'sleeve_length' => 'Sleeve Length',
                                                'trouser_length' => 'Trouser Length', 'kameez_length' => 'Kameez Length',
                                                'neck' => 'Neck'
                                            ];
                                            foreach ($labels as $key => $label):
                                                if (!empty($item['measurements'][$key])):
                                                    $m = getMeasurementValue($item['measurements'][$key]);
                                                    if (!empty($m['value'])):
                                            ?>
                                            <tr>
                                                <td><?php echo $label; ?>:</td>
                                                <td><?php echo e($m['value']); ?> inches</td>
                                                <td style="color: <?php echo getConfidenceColor($m['confidence']); ?>">
                                                    (<?php echo round($m['confidence'] * 100); ?>%)
                                                </td>
                                            </tr>
                                            <?php
                                                    endif;
                                                endif;
                                            endforeach;
                                            ?>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="cart-item-quantity">
                                    <form method="post" class="quantity-form">
                                        <input type="hidden" name="action" value="update_quantity">
                                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                                        <input type="hidden" name="quantity" value="<?php echo max(1, $item['quantity'] - 1); ?>">
                                        <button type="submit" class="quantity-btn decrease">-</button>
                                    </form>
                                    <span class="quantity"><?php echo $item['quantity']; ?></span>
                                    <form method="post" class="quantity-form">
                                        <input type="hidden" name="action" value="update_quantity">
                                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                                        <input type="hidden" name="quantity" value="<?php echo $item['quantity'] + 1; ?>">
                                        <button type="submit" class="quantity-btn increase">+</button>
                                    </form>
                                </div>
                                <div class="cart-item-total">
                                    <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                </div>
                                <form method="post">
                                    <input type="hidden" name="action" value="remove_item">
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <button type="submit" class="remove-item"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="cart-summary">
                        <h2>Order Summary</h2>
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
                        <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                        <div class="continue-shopping">
                            <a href="order.php">Continue Shopping</a>
                        </div>
                        <form method="post" style="margin-top: 15px;">
                            <input type="hidden" name="action" value="clear_cart">
                            <button type="submit" class="btn-clear-cart-small"
                                    onclick="return confirm('Are you sure you want to clear your cart?')">
                                <i class="fas fa-trash"></i> Clear Cart
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Recommended Products -->
    <section class="product-section">
        <div class="container">
            <div class="section-header">
                <h2>YOU MIGHT ALSO LIKE</h2>
                <p>Other popular fabrics from our collection</p>
            </div>
            <div class="product-grid">
                <?php foreach ($recommendedProducts as $product): ?>
                <div class="shop-product-card">
                    <div class="product-image">
                        <img src="images/<?php echo e(pathinfo($product['image'], PATHINFO_FILENAME) . '.jpg'); ?>"
                             alt="<?php echo e($product['name']); ?>"
                             onerror="this.src='images/placeholder.jpg'">
                        <div class="product-actions">
                            <form method="post">
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="id" value="<?php echo e($product['id']); ?>">
                                <input type="hidden" name="name" value="<?php echo e($product['name']); ?>">
                                <input type="hidden" name="price" value="<?php echo $product['price']; ?>">
                                <input type="hidden" name="image" value="<?php echo e($product['image']); ?>">
                                <button type="submit" class="btn-add-to-order">
                                    <i class="fas fa-shopping-cart"></i> Add to Order
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="product-info">
                        <h3><?php echo e($product['name']); ?></h3>
                        <span class="price"><?php echo formatPrice($product['price']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/scripts.php'; ?>
</body>
</html>
