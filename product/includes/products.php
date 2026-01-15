<?php
/**
 * Product Data - Stitch House
 * Centralized product definitions (DRY principle)
 */

$products = [
    'cotton' => [
        'title' => 'COTTON FABRICS',
        'description' => 'High-quality cotton fabrics for comfortable everyday wear',
        'items' => [
            ['id' => 'cotton1', 'name' => 'Premium Cotton - Blue', 'price' => 1500, 'image' => 'c1.1.jpg'],
            ['id' => 'cotton2', 'name' => 'Fine Cotton - Light Brown', 'price' => 1500, 'image' => 'c1.4.jpg'],
            ['id' => 'cotton3', 'name' => 'Egyptian Cotton - Grey', 'price' => 1500, 'image' => 'c1.2.jpg'],
            ['id' => 'cotton4', 'name' => 'Organic Cotton - Brown', 'price' => 1500, 'image' => 'c1.3.jpg']
        ]
    ],
    'lattha' => [
        'title' => 'LATTHA FABRICS',
        'description' => 'Traditional lattha fabrics for classic looks',
        'items' => [
            ['id' => 'lattha1', 'name' => 'Premium Lattha - Black', 'price' => 1500, 'image' => 'l1.3.jpg'],
            ['id' => 'lattha2', 'name' => 'Silk Lattha - Navy Blue', 'price' => 1800, 'image' => 'l1.4.jpg'],
            ['id' => 'lattha3', 'name' => 'Silk Lattha - Beige', 'price' => 1800, 'image' => 'l1.1.jpg'],
            ['id' => 'lattha4', 'name' => 'Premium Lattha - White', 'price' => 1600, 'image' => 'l1.2.jpg']
        ]
    ],
    'karandi' => [
        'title' => 'KARANDI FABRICS',
        'description' => 'Premium karandi fabrics for luxurious comfort',
        'items' => [
            ['id' => 'karandi1', 'name' => 'Premium Karandi - Beige', 'price' => 2000, 'image' => 'k1.1.jpg'],
            ['id' => 'karandi2', 'name' => 'Soft Karandi - Cream', 'price' => 2200, 'image' => 'k1.2.jpg'],
            ['id' => 'karandi3', 'name' => 'Fine Karandi - Blue', 'price' => 2100, 'image' => 'k1.3.jpg'],
            ['id' => 'karandi4', 'name' => 'Luxury Karandi - White', 'price' => 2300, 'image' => 'k1.4.jpg']
        ]
    ],
    'boski' => [
        'title' => 'BOSKI FABRICS',
        'description' => 'Fine boski fabrics for elegant traditional wear',
        'items' => [
            ['id' => 'boski1', 'name' => 'Premium Boski - Brown', 'price' => 1900, 'image' => 'b1.1.jpg'],
            ['id' => 'boski2', 'name' => 'Soft Boski - Cream', 'price' => 2000, 'image' => 'b1.2.jpg'],
            ['id' => 'boski3', 'name' => 'Fine Boski - White', 'price' => 1950, 'image' => 'b1.3.jpg'],
            ['id' => 'boski4', 'name' => 'Premium Boski - Black', 'price' => 2050, 'image' => 'b1.4.jpg']
        ]
    ],
    'linen' => [
        'title' => 'LINEN FABRICS',
        'description' => 'Premium linen fabrics for breathable comfort',
        'items' => [
            ['id' => 'linen1', 'name' => 'Premium Linen - Black', 'price' => 2200, 'image' => 'ln1.1.jpg'],
            ['id' => 'linen2', 'name' => 'Irish Linen - Light Grey', 'price' => 2400, 'image' => 'ln1.2.jpg'],
            ['id' => 'linen3', 'name' => 'Pure Linen - White', 'price' => 2300, 'image' => 'ln1.3.jpg'],
            ['id' => 'linen4', 'name' => 'Fine Linen - Blue', 'price' => 2250, 'image' => 'ln1.4.jpg']
        ]
    ],
    'washandwear' => [
        'title' => 'WASH AND WEAR FABRICS',
        'description' => 'Low-maintenance fabrics for everyday use',
        'items' => [
            ['id' => 'washandwear1', 'name' => 'Classic Wash and Wear - Green', 'price' => 1800, 'image' => 'w1.1.jpg'],
            ['id' => 'washandwear2', 'name' => 'Premium Wash and Wear - Navy Blue', 'price' => 1900, 'image' => 'w1.2.jpg'],
            ['id' => 'washandwear3', 'name' => 'Quality Wash and Wear - Blue', 'price' => 1850, 'image' => 'w1.3.jpg'],
            ['id' => 'washandwear4', 'name' => 'Textured Wash and Wear - White', 'price' => 2000, 'image' => 'w1.4.jpg']
        ]
    ],
    'silk' => [
        'title' => 'SILK FABRICS',
        'description' => 'Luxurious silk fabrics for special occasions',
        'items' => [
            ['id' => 'silk1', 'name' => 'Pure Silk - Green', 'price' => 3000, 'image' => 's1.1.jpg'],
            ['id' => 'silk2', 'name' => 'Mulberry Silk - White', 'price' => 3200, 'image' => 's1.2.jpg'],
            ['id' => 'silk3', 'name' => 'Raw Silk - Red', 'price' => 2800, 'image' => 's1.3.jpg'],
            ['id' => 'silk4', 'name' => 'Premium Silk - Beige', 'price' => 3100, 'image' => 's1.4.jpg']
        ]
    ],
    'khaddar' => [
        'title' => 'KHADDAR FABRICS',
        'description' => 'Traditional khaddar fabrics for winter wear',
        'items' => [
            ['id' => 'khaddar1', 'name' => 'Premium Khaddar - Navy Blue', 'price' => 1800, 'image' => 'kh1.1.jpg'],
            ['id' => 'khaddar2', 'name' => 'Soft Khaddar - Black', 'price' => 1900, 'image' => 'kh1.2.jpg'],
            ['id' => 'khaddar3', 'name' => 'Quality Khaddar - Brown', 'price' => 1850, 'image' => 'kh1.3.jpg'],
            ['id' => 'khaddar4', 'name' => 'Textured Khaddar - Blue', 'price' => 2000, 'image' => 'kh1.4.jpg']
        ]
    ]
];

/**
 * Render a product section
 */
function renderProductSection($sectionKey, $section) {
    $sectionId = $sectionKey . '-section';
    ?>
    <section class="product-section" id="<?php echo $sectionId; ?>">
        <div class="container">
            <div class="section-header">
                <h2><?php echo htmlspecialchars($section['title']); ?></h2>
                <p><?php echo htmlspecialchars($section['description']); ?></p>
            </div>
            <div class="product-grid">
                <?php foreach ($section['items'] as $product): ?>
                <div class="shop-product-card">
                    <div class="product-image">
                        <img src="images/<?php echo htmlspecialchars($product['image']); ?>"
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             loading="lazy">
                        <div class="product-actions">
                            <button class="btn-add-to-order"
                                    data-id="<?php echo htmlspecialchars($product['id']); ?>"
                                    data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                    data-price="<?php echo $product['price']; ?>"
                                    data-image="<?php echo htmlspecialchars($product['image']); ?>"
                                    data-section="<?php echo $sectionId; ?>">
                                <i class="fas fa-shopping-cart"></i> Add to Order
                            </button>
                        </div>
                    </div>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-meta">
                            <span class="price">PKR. <?php echo number_format($product['price']); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}
