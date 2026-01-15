<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync_cart') {
    if (isset($_POST['cart_data'])) {
        $localCart = json_decode($_POST['cart_data'], true);
        
        // Initialize session cart if needed
        if (!isset($_SESSION['shoppingCart']) || !is_array($_SESSION['shoppingCart'])) {
            $_SESSION['shoppingCart'] = [];
        }
        
        // Merge localStorage cart with session cart
        foreach ($localCart as $localItem) {
            $found = false;
            
            foreach ($_SESSION['shoppingCart'] as &$sessionItem) {
                if ($sessionItem['id'] === $localItem['id']) {
                    // Update quantity if item already exists
                    $sessionItem['quantity'] = max($sessionItem['quantity'], $localItem['quantity']);
                    $found = true;
                    break;
                }
            }
            
            // Add new item if not found
            if (!$found) {
                $_SESSION['shoppingCart'][] = $localItem;
            }
        }
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No cart data received']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>