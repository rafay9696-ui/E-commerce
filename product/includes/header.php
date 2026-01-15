<?php
/**
 * Common Header Component
 * Include this at the top of each page after session_start()
 */

// Get common variables
$logged_in = isLoggedIn();
$user_name = getUserName();
$cartCount = getCartCount();
?>
<!-- Side Navigation -->
<div class="side-nav">
    <div class="side-nav-content">
        <div class="side-nav-header">
            <div class="logo-container">
                <h2 class="logo-text">Stitch House</h2>
            </div>
            <button class="close-sidenav">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="side-nav-links">
            <ul>
                <li><a href="order.php" class="shop-link"><i class="fas fa-shopping-bag"></i>Shop Now</a></li>
                <li><a href="order.php#cotton-section"><i class="fas fa-tshirt"></i>Cotton Fabrics</a></li>
                <li><a href="order.php#lattha-section"><i class="fas fa-scroll"></i>Lattha Fabrics</a></li>
                <li><a href="order.php#karandi-section"><i class="fas fa-layer-group"></i>Karandi Fabrics</a></li>
                <li><a href="order.php#boski-section"><i class="fas fa-square"></i>Boski Fabrics</a></li>
                <li><a href="order.php#linen-section"><i class="fas fa-wind"></i>Linen Fabrics</a></li>
                <li><a href="order.php#washandwear-section"><i class="fas fa-tshirt"></i>Wash and Wear</a></li>
                <li><a href="order.php#silk-section"><i class="fas fa-stream"></i>Silk Fabrics</a></li>
                <li><a href="order.php#khaddar-section"><i class="fas fa-border-all"></i>Khaddar Fabrics</a></li>
            </ul>
        </div>
        <div class="side-nav-footer">
            <div class="social-icons">
                <a href="#" class="facebook"><i class="fab fa-facebook"></i></a>
                <a href="#" class="instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" class="twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" class="pinterest"><i class="fab fa-pinterest"></i></a>
                <a href="#" class="youtube"><i class="fab fa-youtube"></i></a>
            </div>
        </div>
    </div>
</div>
<div class="side-nav-backdrop"></div>

<header>
    <div class="container">
        <div class="menu-toggle">
            <i class="fas fa-bars"></i>
        </div>
        <div class="site-title">
            <a href="index.php">
                <h1 class="logo-text">Stitch House</h1>
            </a>
        </div>
        <nav>
            <ul>
                <li><a href="index.php" class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? ' active' : ''; ?>">Home</a></li>
                <li><a href="order.php" class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'order.php' ? ' active' : ''; ?>">Shop Now</a></li>
                <li><a href="myorder.php" class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'myorder.php' ? ' active' : ''; ?>">My Order</a></li>
                <li><a href="contact.php" class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'contact.php' ? ' active' : ''; ?>">Contact Us</a></li>
            </ul>
        </nav>
        <div class="header-actions">
            <?php if (!$logged_in): ?>
            <div class="login-link">
                <a href="login.php">
                    <i class="fas fa-user"></i>
                </a>
            </div>
            <?php endif; ?>

            <div class="cart-link">
                <a href="cart.php">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cartCount; ?></span>
                </a>
            </div>

            <?php if ($logged_in): ?>
            <div class="user-info">
                <span class="user-name"><?php echo e($user_name); ?></span>
                <a href="logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</header>
