<?php
/**
 * Shop Page - Stitch House
 * Browse and add products to cart
 */
require_once 'db_connection.php';
require_once 'includes/functions.php';
require_once 'includes/products.php';
session_start();
initializeCart();

$pageTitle = 'Shop Now - Stitch House';
$pageStyles = ['order.css'];

// Handle add to cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_to_cart') {
    $item = [
        'id' => $_POST['id'] ?? '',
        'name' => $_POST['name'] ?? '',
        'price' => floatval($_POST['price'] ?? 0),
        'image' => $_POST['image'] ?? '',
        'quantity' => 1
    ];

    $found = false;
    foreach ($_SESSION['shoppingCart'] as $key => $cartItem) {
        if ($cartItem['id'] == $item['id']) {
            $_SESSION['shoppingCart'][$key]['quantity']++;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['shoppingCart'][] = $item;
    }

    // Sync carts
    $_SESSION['finalCart'] = $_SESSION['shoppingCart'];

    // AJAX response
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode([
            'success' => true,
            'id' => $item['id'],
            'name' => $item['name'],
            'image' => $item['image'],
            'cartCount' => getCartCount()
        ]);
        exit;
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "#" . ($_POST['section'] ?? ''));
    exit;
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
            <h1>Shop Fabrics</h1>
            <div class="breadcrumb">
                <a href="index.php">Home</a> / <span>Shop Now</span>
            </div>
        </div>
    </section>

    <!-- Fabric Types Slider -->
    <section class="fabric-slider-section">
        <div class="container">
            <div class="fabric-slider">
                <?php
                $sliderItems = [
                    ['href' => 'cotton', 'image' => 'c1.1.jpg', 'name' => 'Cotton'],
                    ['href' => 'lattha', 'image' => 'l1.3.jpg', 'name' => 'Lattha'],
                    ['href' => 'karandi', 'image' => 'k1.1.jpg', 'name' => 'Karandi'],
                    ['href' => 'boski', 'image' => 'b1.1.jpg', 'name' => 'Boski'],
                    ['href' => 'linen', 'image' => 'ln1.1.jpg', 'name' => 'Linen'],
                    ['href' => 'washandwear', 'image' => 'w1.1.jpg', 'name' => 'Wash & Wear'],
                    ['href' => 'silk', 'image' => 's1.1.jpg', 'name' => 'Silk'],
                    ['href' => 'khaddar', 'image' => 'kh1.1.jpg', 'name' => 'Khaddar']
                ];
                foreach ($sliderItems as $item):
                ?>
                <a href="#<?php echo $item['href']; ?>-section" class="slider-item">
                    <img src="images/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?> Fabric">
                    <div class="fabric-name"><?php echo $item['name']; ?></div>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="slider-controls">
                <button class="slider-control prev"><i class="fas fa-chevron-left"></i></button>
                <button class="slider-control next"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </section>

    <!-- Product Sections -->
    <?php foreach ($products as $key => $section): ?>
        <?php renderProductSection($key, $section); ?>
    <?php endforeach; ?>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/scripts.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Slider functionality
        const slider = document.querySelector('.fabric-slider');
        const prevBtn = document.querySelector('.slider-control.prev');
        const nextBtn = document.querySelector('.slider-control.next');
        const itemWidth = document.querySelector('.slider-item')?.offsetWidth + 15 || 100;

        if (prevBtn) prevBtn.addEventListener('click', () => slider.scrollBy({ left: -itemWidth * 3, behavior: 'smooth' }));
        if (nextBtn) nextBtn.addEventListener('click', () => slider.scrollBy({ left: itemWidth * 3, behavior: 'smooth' }));

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const section = document.querySelector(this.getAttribute('href'));
                if (section) window.scrollTo({ top: section.offsetTop - 20, behavior: 'smooth' });
            });
        });

        // Add to cart functionality
        document.querySelectorAll('.btn-add-to-order').forEach(btn => {
            btn.addEventListener('click', function() {
                const formData = new FormData();
                formData.append('action', 'add_to_cart');
                formData.append('id', this.dataset.id);
                formData.append('name', this.dataset.name);
                formData.append('price', this.dataset.price);
                formData.append('image', this.dataset.image);
                formData.append('section', this.dataset.section);

                fetch('order.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(data => {
                    document.querySelector('.cart-count').textContent = data.cartCount;
                    showNotification(data.name + ' added to cart!');
                })
                .catch(error => console.error('Error:', error));
            });
        });

        function showNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'toast-notification';
            notification.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
            notification.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#28a745;color:#fff;padding:15px 25px;border-radius:5px;z-index:9999;animation:fadeIn 0.3s';
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }
    });
    </script>
</body>
</html>
