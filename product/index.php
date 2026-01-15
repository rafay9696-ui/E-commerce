<?php
/**
 * Homepage - Stitch House
 */
require_once 'db_connection.php';
require_once 'includes/functions.php';
session_start();
initializeCart();

$pageTitle = 'Stitch House - Premium Fabric Store';
$cartCount = getCartCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Carousel Section -->
    <section class="carousel">
        <div class="carousel-inner">
            <div class="carousel-item" style="background-image: url('images/1.jpg')">
                <div class="carousel-caption clean">
                    <h2>Premium Quality Fabrics</h2>
                    <p>Discover our exclusive collection of finest fabrics</p>
                    <a href="order.php" class="btn-shop-now">Shop Now</a>
                </div>
            </div>
            <div class="carousel-item" style="background-image: url('images/2.jpg')">
                <div class="carousel-caption clean">
                    <h2>Handcrafted Excellence</h2>
                    <p>Every thread tells a story of quality and craftsmanship</p>
                    <a href="order.php" class="btn-shop-now">Shop Now</a>
                </div>
            </div>
            <div class="carousel-item" style="background-image: url('images/3.jpg')">
                <div class="carousel-caption clean">
                    <h2>New Seasonal Collection</h2>
                    <p>Explore our latest arrivals for this season</p>
                    <a href="order.php" class="btn-shop-now">Shop Now</a>
                </div>
            </div>
        </div>
        <div class="carousel-controls">
            <div class="carousel-control prev"><i class="fas fa-chevron-left"></i></div>
            <div class="carousel-control next"><i class="fas fa-chevron-right"></i></div>
        </div>
    </section>

    <!-- Moving Text Banner -->
    <section class="moving-text-banner">
        <div class="moving-text-container">
            <div class="moving-text gold-text">
                <span>STITCH HOUSE</span>
                <i class="fas fa-square"></i>
                <span>PREMIUM FABRICS</span>
                <i class="fas fa-square"></i>
                <span>SUMMER COLLECTION 2025</span>
                <i class="fas fa-square"></i>
                <span>STITCH HOUSE</span>
                <i class="fas fa-square"></i>
                <span>PREMIUM FABRICS</span>
                <i class="fas fa-square"></i>
                <span>SUMMER COLLECTION 2025</span>
                <i class="fas fa-square"></i>
            </div>
        </div>
    </section>

    <!-- Testimonial Section -->
    <section class="testimonial-quotes">
        <div class="container">
            <div class="quote">
                <i class="fas fa-quote-left"></i>
                <p>The finest fabrics from Stitch House have transformed our wardrobe with unmatched quality and elegance. Exceptional craftsmanship and premium materials make Stitch House the ultimate destination for fabric connoisseurs.</p>
                <i class="fas fa-quote-right"></i>
            </div>
        </div>
    </section>

    <!-- Featured Fabrics -->
    <section class="featured-products">
        <div class="container">
            <h2>Featured Fabrics</h2>
            <div class="product-grid">
                <?php
                $fabrics = [
                    ['link' => 'cotton-section', 'image' => 'c1.jpg', 'name' => 'COTTON'],
                    ['link' => 'lattha-section', 'image' => 'c2.jpg', 'name' => 'LATTHA'],
                    ['link' => 'karandi-section', 'image' => 'c3.jpg', 'name' => 'KARANDI'],
                    ['link' => 'boski-section', 'image' => 'c4.jpg', 'name' => 'BOSKI'],
                    ['link' => 'linen-section', 'image' => 'c5.jpg', 'name' => 'LINEN'],
                    ['link' => 'washandwear-section', 'image' => 'c6.jpg', 'name' => 'WASH AND WEAR'],
                    ['link' => 'silk-section', 'image' => 'c7.jpg', 'name' => 'SILK'],
                    ['link' => 'khaddar-section', 'image' => 'c8.jpg', 'name' => 'KHADDAR']
                ];

                foreach ($fabrics as $fabric):
                ?>
                <a href="order.php#<?php echo $fabric['link']; ?>" class="product-card">
                    <div class="product-image">
                        <img src="images/<?php echo $fabric['image']; ?>" alt="<?php echo $fabric['name']; ?> Fabric">
                    </div>
                    <div class="product-info">
                        <h3><?php echo $fabric['name']; ?></h3>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="view-all-container">
                <a href="order.php" class="btn-view-all">View All Products</a>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/scripts.php'; ?>

    <script>
    // Carousel functionality
    document.addEventListener('DOMContentLoaded', function() {
        const carouselInner = document.querySelector('.carousel-inner');
        const slides = document.querySelectorAll('.carousel-item');
        const prevBtn = document.querySelector('.carousel-control.prev');
        const nextBtn = document.querySelector('.carousel-control.next');
        let currentSlide = 0;
        const totalSlides = slides.length;

        function goToSlide(slideIndex) {
            if (slideIndex < 0) slideIndex = totalSlides - 1;
            else if (slideIndex >= totalSlides) slideIndex = 0;
            currentSlide = slideIndex;
            carouselInner.style.transform = `translateX(-${currentSlide * 100}%)`;
        }

        if (prevBtn) prevBtn.addEventListener('click', () => goToSlide(currentSlide - 1));
        if (nextBtn) nextBtn.addEventListener('click', () => goToSlide(currentSlide + 1));

        setInterval(() => goToSlide(currentSlide + 1), 5000);
    });
    </script>
</body>
</html>
