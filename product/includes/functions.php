<?php
/**
 * Common Functions for Stitch House
 * Following DRY principle - centralized utility functions
 */

/**
 * Calculate cart item count from session
 * @return int Total number of items in cart
 */
function getCartCount() {
    $count = 0;

    if (isset($_SESSION['finalCart']) && is_array($_SESSION['finalCart'])) {
        foreach ($_SESSION['finalCart'] as $item) {
            $count += isset($item['quantity']) ? (int)$item['quantity'] : 0;
        }
    }

    return $count;
}

/**
 * Calculate cart subtotal
 * @return float Subtotal amount
 */
function getCartSubtotal() {
    $subtotal = 0;

    if (isset($_SESSION['finalCart']) && is_array($_SESSION['finalCart'])) {
        foreach ($_SESSION['finalCart'] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
    }

    return $subtotal;
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user name
 * @return string
 */
function getUserName() {
    return isLoggedIn() ? ($_SESSION['user_name'] ?? '') : '';
}

/**
 * Get current user ID
 * @return int|null
 */
function getUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Sanitize output for HTML
 * @param string $string
 * @return string
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get correct image path with fallback
 * @param string $imagePath
 * @return string
 */
function getImagePath($imagePath) {
    $imageName = basename($imagePath);
    $cleanImageName = str_replace(['images/', 'images\\'], '', $imageName);
    $filename = pathinfo($cleanImageName, PATHINFO_FILENAME);

    $variants = [
        "images/{$cleanImageName}",
        "images/{$filename}.jpg",
        "images/{$filename}.webp",
        "images/{$filename}.png"
    ];

    foreach ($variants as $variant) {
        if (file_exists($variant)) {
            return $variant;
        }
    }

    return "images/{$filename}.jpg";
}

/**
 * Format price in PKR
 * @param float $amount
 * @return string
 */
function formatPrice($amount) {
    return 'PKR ' . number_format($amount, 0);
}

/**
 * Initialize session arrays if not set
 */
function initializeCart() {
    if (!isset($_SESSION['finalCart']) || !is_array($_SESSION['finalCart'])) {
        $_SESSION['finalCart'] = [];
    }
    if (!isset($_SESSION['shoppingCart']) || !is_array($_SESSION['shoppingCart'])) {
        $_SESSION['shoppingCart'] = [];
    }
}

/**
 * Get measurement value from array or direct value
 * @param mixed $measurement
 * @return array ['value' => string, 'confidence' => float]
 */
function getMeasurementValue($measurement) {
    if (is_array($measurement)) {
        return [
            'value' => $measurement['value'] ?? '',
            'confidence' => $measurement['confidence'] ?? 0.8
        ];
    }
    return [
        'value' => $measurement,
        'confidence' => 0.8
    ];
}

/**
 * Get confidence color based on value
 * @param float $confidence
 * @return string CSS color
 */
function getConfidenceColor($confidence) {
    if ($confidence >= 0.85) return '#28a745';
    if ($confidence >= 0.75) return '#ffc107';
    return '#dc3545';
}
