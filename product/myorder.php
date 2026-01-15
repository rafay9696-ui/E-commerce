<?php
require_once 'db_connection.php';
session_start();

// CSRF protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function file_exists_case_insensitive($filepath) {
    if (file_exists($filepath)) {
        return $filepath;
    }
    
    // Try with jpg extension if we have webp
    if (stripos($filepath, '.webp') !== false) {
        $jpg_path = str_replace('.webp', '.jpg', $filepath);
        if (file_exists($jpg_path)) {
            return $jpg_path;
        }
    }
    
    // Try with webp extension if we have jpg
    if (stripos($filepath, '.jpg') !== false) {
        $webp_path = str_replace('.jpg', '.webp', $filepath);
        if (file_exists($webp_path)) {
            return $webp_path;
        }
    }
    
    // Check directory contents as a last resort
    $dir = dirname($filepath);
    $filename = basename($filepath);
    $filename_noext = pathinfo($filename, PATHINFO_FILENAME);
    
    if (file_exists($dir) && is_dir($dir)) {
        foreach (scandir($dir) as $file) {
            if (stripos($file, $filename_noext) === 0) {
                return $dir . '/' . $file;
            }
        }
    }
    
    return false;
}

// Check if we're coming back from cart.php and fix cart duplication issue
if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'cart.php') !== false) {
    // If we have items in both carts, remove duplicates from shoppingCart
    if (isset($_SESSION['finalCart']) && is_array($_SESSION['finalCart']) && 
        isset($_SESSION['shoppingCart']) && is_array($_SESSION['shoppingCart'])) {
        
        // Create a temporary array to track which items to remove
        $itemsToRemove = [];
        
        // Check each item in shoppingCart
        foreach ($_SESSION['shoppingCart'] as $shopIndex => $shopItem) {
            // Look for this item in finalCart
            foreach ($_SESSION['finalCart'] as $finalItem) {
                // If item has the same ID and design (if any), mark for removal
                if ($shopItem['id'] == $finalItem['id']) {
                    $designMatch = true;
                    
                    // If both have design, check if designs match
                    if (isset($shopItem['design']) && isset($finalItem['design'])) {
                        $designMatch = ($shopItem['design'] == $finalItem['design']);
                    }
                    // If one has design and other doesn't, they're different
                    else if (isset($shopItem['design']) || isset($finalItem['design'])) {
                        $designMatch = false;
                    }
                    
                    // If this is a duplicate, add to removal list
                    if ($designMatch) {
                        $itemsToRemove[] = $shopIndex;
                        break;
                    }
                }
            }
        }
        
        // Remove duplicates from shoppingCart (starting from the end to avoid index issues)
        rsort($itemsToRemove);
        foreach ($itemsToRemove as $index) {
            array_splice($_SESSION['shoppingCart'], $index, 1);
        }
    }
}

// Initialize cart count for both shopping cart and final cart
$cartCount = 0;
if (isset($_SESSION['shoppingCart']) && is_array($_SESSION['shoppingCart'])) {
    foreach ($_SESSION['shoppingCart'] as $item) {
        $cartCount += $item['quantity'];
    }
}
if (isset($_SESSION['finalCart']) && is_array($_SESSION['finalCart'])) {
    foreach ($_SESSION['finalCart'] as $item) {
        $cartCount += $item['quantity'];
    }
}

// Check if we need to clear the cart
if (isset($_POST['action']) && $_POST['action'] === 'clear_cart') {
    $_SESSION['shoppingCart'] = [];
    header('Location: myorder.php');
    exit();
}

// Check if we need to remove an item
if (isset($_POST['action']) && $_POST['action'] === 'remove_item' && isset($_POST['index'])) {
    $index = (int)$_POST['index'];
    if (isset($_SESSION['shoppingCart'][$index])) {
        array_splice($_SESSION['shoppingCart'], $index, 1);
    }
    header('Location: myorder.php');
    exit();
}

// Check if we need to update quantity
if (isset($_POST['action']) && $_POST['action'] === 'update_quantity' && isset($_POST['index']) && isset($_POST['quantity'])) {
    $index = (int)$_POST['index'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity > 0 && isset($_SESSION['shoppingCart'][$index])) {
        $_SESSION['shoppingCart'][$index]['quantity'] = $quantity;
    }
    header('Location: myorder.php');
    exit();
}

// Check if we need to update design
if (isset($_POST['action']) && $_POST['action'] === 'save_design' && isset($_POST['index'])) {
    $index = (int)$_POST['index'];
    
    if (isset($_SESSION['shoppingCart'][$index])) {
        $collar = isset($_POST['collar']) ? $_POST['collar'] : '';
        $kurta = isset($_POST['kurta']) ? $_POST['kurta'] : '';
        $bottom = isset($_POST['bottom']) ? $_POST['bottom'] : '';
        $frontPocket = isset($_POST['front_pocket']) ? $_POST['front_pocket'] : '';
        $sidePocket = isset($_POST['side_pocket']) ? $_POST['side_pocket'] : '';
        
        if ($collar && $kurta && $bottom && $frontPocket && $sidePocket) {
            $designDescription = "$kurta with $collar, $bottom, $frontPocket, and $sidePocket";
            $_SESSION['shoppingCart'][$index]['design'] = $designDescription;
            $_SESSION['shoppingCart'][$index]['designOptions'] = [
                'collar' => $collar,
                'kurta' => $kurta,
                'bottom' => $bottom,
                'front_pocket' => $frontPocket,
                'side_pocket' => $sidePocket
            ];
        }
    }
    header('Location: myorder.php');
    exit();
}

// Save measurements - FIXED VERSION
if (isset($_POST['action']) && $_POST['action'] === 'save_measurements' && isset($_POST['index'])) {
    $index = (int)$_POST['index'];
    
    if (isset($_SESSION['shoppingCart'][$index])) {
        $fields = ['chest', 'waist', 'hip', 'shoulder', 'sleeve_length', 'trouser_length', 'kameez_length', 'neck', 'notes'];
        $validMeasurements = [];
        $measurementCount = 0;
        
        foreach ($fields as $field) {
            if (isset($_POST[$field]) && !empty($_POST[$field])) {
                $value = $field === 'notes' ? $_POST[$field] : floatval($_POST[$field]);
                $confidence = isset($_POST[$field.'_confidence']) ? floatval($_POST[$field.'_confidence']) : 0.8;
                
                // FIXED: Store as individual values, not arrays
                $validMeasurements[$field] = [
                    'value' => $value,
                    'confidence' => $confidence,
                    'source' => isset($_POST['source']) ? $_POST['source'] : 'manual'
                ];
                
                if ($field !== 'notes') {
                    $measurementCount++;
                }
            }
        }
        
        if ($measurementCount >= 3) {
            $_SESSION['shoppingCart'][$index]['measurements'] = $validMeasurements;
            $_SESSION['shoppingCart'][$index]['hasMeasurements'] = true;
            $_SESSION['shoppingCart'][$index]['measurementSource'] = 
                (isset($_POST['source']) && $_POST['source'] === 'ai_webcam') ? 'AI Webcam' : 'Manual Entry';
            
            $_SESSION['success_message'] = "Measurements saved successfully!";
        } else {
            $_SESSION['error_message'] = "Please provide at least 3 valid measurements.";
        }
    }
    
    // JSON response for AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        if (isset($_SESSION['success_message'])) {
            echo json_encode(['success' => true, 'message' => $_SESSION['success_message']]);
            unset($_SESSION['success_message']);
        } else {
            echo json_encode(['success' => false, 'message' => $_SESSION['error_message']]);
            unset($_SESSION['error_message']);
        }
        exit();
    }
    
    header('Location: myorder.php');
    exit();
}

// Add to final cart functionality - ENHANCED VERSION
if (isset($_POST['action']) && $_POST['action'] === 'add_to_final_cart' && isset($_POST['index'])) {
    $index = (int)$_POST['index'];
    
    if (isset($_SESSION['shoppingCart'][$index])) {
        $item = $_SESSION['shoppingCart'][$index];
        
        // Check that both design and measurements exist
        if (!isset($item['design']) || !isset($item['hasMeasurements']) || !$item['hasMeasurements']) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Please complete both design and measurements before adding to cart.'
            ]);
            exit();
        }
        
        // Ensure measurements are in correct format for cart display
        if (isset($item['measurements']) && is_array($item['measurements'])) {
            foreach ($item['measurements'] as $key => $measurement) {
                // Convert old format to new format if needed
                if (!is_array($measurement) && !empty($measurement)) {
                    $item['measurements'][$key] = [
                        'value' => $measurement,
                        'confidence' => 0.8,
                        'source' => 'manual'
                    ];
                }
            }
        }
        
        // Ensure design options are properly included
        if (!isset($_SESSION['finalCart'])) {
            $_SESSION['finalCart'] = [];
        }
        
        // Check if item already exists in finalCart
        $found = false;
        foreach ($_SESSION['finalCart'] as $key => $cartItem) {
            if ($cartItem['id'] == $item['id'] && 
                isset($cartItem['design']) && isset($item['design']) && 
                $cartItem['design'] == $item['design']) {
                // If same product with same design is found, just update quantity
                $_SESSION['finalCart'][$key]['quantity'] += $item['quantity'];
                
                // Update measurements if they're newer
                if (isset($item['measurements'])) {
                    $_SESSION['finalCart'][$key]['measurements'] = $item['measurements'];
                    $_SESSION['finalCart'][$key]['hasMeasurements'] = $item['hasMeasurements'];
                    $_SESSION['finalCart'][$key]['measurementSource'] = $item['measurementSource'] ?? 'Manual Entry';
                    $_SESSION['finalCart'][$key]['accuracy'] = $item['accuracy'] ?? 80;
                }
                
                $found = true;
                break;
            }
        }
        
        // If not found in finalCart, add it
        if (!$found) {
            $_SESSION['finalCart'][] = $item; // This should include all design and measurement data
        }
        
        // Save to database if user is logged in
        if (isset($_SESSION['user_id']) && isset($item['measurements'])) {
            $userId = $_SESSION['user_id'];
            $productId = $item['id'];
            
            // Check if cart item exists
            $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("is", $userId, $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $cartItem = $result->fetch_assoc();
                $cartItemId = $cartItem['id'];
                
                // Delete existing measurements
                $stmt = $conn->prepare("DELETE FROM cart_measurements WHERE cart_id = ?");
                $stmt->bind_param("i", $cartItemId);
                $stmt->execute();
                
                // FIXED: Extract and insert new measurements properly
                $measurementFields = ['chest', 'waist', 'hip', 'shoulder', 'sleeve_length', 'trouser_length', 'kameez_length', 'neck', 'notes'];
                $measurementValues = [];
                
                foreach ($measurementFields as $field) {
                    if (isset($item['measurements'][$field])) {
                        $measurement = $item['measurements'][$field];
                        
                        // Extract actual value from array format
                        if (is_array($measurement)) {
                            $value = isset($measurement['value']) ? $measurement['value'] : '';
                        } else {
                            $value = $measurement;
                        }
                        
                        // Convert to proper decimal format (except for notes)
                        if ($field !== 'notes' && !empty($value) && is_numeric($value)) {
                            $value = number_format((float)$value, 1, '.', '');
                        } elseif ($field === 'notes') {
                            $value = !empty($value) ? $value : '';
                        } else {
                            $value = '';
                        }
                        
                        $measurementValues[] = $value;
                        error_log("Processed $field: '$value' (from " . (is_array($measurement) ? 'array' : 'direct') . ")");
                    } else {
                        $measurementValues[] = '';
                    }
                }
                
                $measurementSql = "INSERT INTO cart_measurements (cart_id, chest, waist, hip, shoulder, sleeve_length, trouser_length, kameez_length, neck, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $measurementStmt = $conn->prepare($measurementSql);
                
                if ($measurementStmt) {
                    $measurementStmt->bind_param("isssssssss", $cartItemId, ...$measurementValues);
                    
                    if ($measurementStmt->execute()) {
                        error_log("✅ Cart measurements saved successfully");
                        error_log("Saved values: " . implode(', ', $measurementValues));
                    } else {
                        error_log("❌ Failed to save cart measurements: " . $measurementStmt->error);
                    }
                    $measurementStmt->close();
                } else {
                    error_log("❌ Failed to prepare cart measurement statement: " . $conn->error);
                }
            }
        }
        
        // Remove from shopping cart
        unset($_SESSION['shoppingCart'][$index]);
        $_SESSION['shoppingCart'] = array_values($_SESSION['shoppingCart']);
        
        // Calculate new cart count
        $newCartCount = 0;
        if (isset($_SESSION['finalCart']) && is_array($_SESSION['finalCart'])) {
            foreach ($_SESSION['finalCart'] as $cartItem) {
                $newCartCount += $cartItem['quantity'];
            }
        }
        if (isset($_SESSION['shoppingCart']) && is_array($_SESSION['shoppingCart'])) {
            foreach ($_SESSION['shoppingCart'] as $cartItem) {
                $newCartCount += $cartItem['quantity'];
            }
        }
        
        // Return JSON response for AJAX
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Item added to cart successfully with measurements',
            'cartCount' => $newCartCount
        ]);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Item not found'
        ]);
        exit();
    }
}

// Check if user is logged in
$logged_in = isset($_SESSION['user_id']);
$user_name = $logged_in ? $_SESSION['user_name'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Order - Stitch House</title>
    <link rel="stylesheet" href="variables.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="order.css">
    <link rel="stylesheet" href="shop.css">
    <link rel="stylesheet" href="myorder.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Montserrat:wght@400;500;600;700&family=Dancing+Script:wght@600;700&display=swap" rel="stylesheet">
    <style>
        /* Additional styles for the new buttons */
        .measurement-btn, .add-to-cart-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            background-color: #d4af37;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 8px;
            transition: all 0.3s ease;
        }
        
        .measurement-btn {
            margin-left: 10px;
            background-color: #333;
        }
        
        .measurement-btn:hover, .add-to-cart-btn:hover {
            opacity: 0.85;
            transform: translateY(-2px);
        }
        
        .add-to-cart-btn {
            margin-top: 10px;
            width: 100%;
        }
        
        .add-to-cart-btn.disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        .design-btn-group {
            display: flex;
            align-items: center;
            margin-top: 8px;
        }
        
        .cart-item-total {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }
        
        /* Success message styling */
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 5px solid #155724;
            animation: fadeOut 5s forwards;
        }
        
        @keyframes fadeOut {
            0% { opacity: 1; }
            90% { opacity: 1; }
            100% { opacity: 0; }
        }
        
        /* Measurement Modal Styling */
        .measurement-modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 2000; /* Ensure it's above everything */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        
        .measurement-modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            animation: modalFadeIn 0.3s;
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Improve styling of measurement display */
        .measurements-display {
            background-color: #f9f9f9;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
            margin-bottom: 10px;
            border: 1px solid #eee;
        }
        
        .measurements-display h4 {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        
        .edit-measurements {
            background: none;
            border: none;
            color: #d4af37;
            cursor: pointer;
            font-size: 12px;
        }
        
        /* Back to top button */
        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #d4af37;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .back-to {
            opacity: 1;
        }
        
        .back-to-top i {
            font-size: 24px;
        }

        /* Measurement success notification */
        .measurement-success-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
            z-index: 9999;
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        /* Accuracy indicator styling */
        #accuracy-indicator {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        /* Confidence colors */
        .confidence-high { 
            background-color: #d4edda !important; 
            border-color: #28a745 !important; 
        }

        .confidence-medium { 
            background-color: #fff3cd !important; 
            border-color: #ffc107 !important; 
        }

        .confidence-low { 
            background-color: #f8d7da !important; 
            border-color: #dc3545 !important; 
        }
    </style>
    
    <!-- Add these scripts before closing </head> tag -->
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/control_utils/control_utils.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/drawing_utils/drawing_utils.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/pose/pose.js"></script>

    <!-- Add your custom MediaPipe files -->
    <link rel="stylesheet" href="mediapipe-pose/css/mediapipe-styles.css">
    <script src="mediapipe-pose/js/pose-detector.js"></script>
    <script src="mediapipe-pose/js/measurement-calculator.js"></script>
    <script src="mediapipe-pose/js/mediapipe-pose.js"></script>
</head>
<body>
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
                    <li><a href="index.php"><i class="fas fa-home"></i>Home</a></li>
                    <li><a href="order.php"><i class="fas fa-shopping-bag"></i>Shop Now</a></li>
                    <li><a href="myorder.php" class="active"><i class="fas fa-shopping-cart"></i>My Order</a></li>
                    <li><a href="contact.php"><i class="fas fa-envelope"></i>Contact Us</a></li>
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
                    <li><a href="index.php" class="nav-link">Home</a></li>
                    <li><a href="order.php" class="nav-link">Shop Now</a></li>
                    <li><a href="myorder.php" class="nav-link active">My Order</a></li>
                    <li><a href="contact.php" class="nav-link">Contact Us</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <?php if (!$logged_in): ?>
                    <div class="login-link">
                        <a href="login.html">
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
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <a href="logout.php" class="logout-link">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Page Banner -->
    <section class="page-banner">
        <div class="container">
            <h1>My Order</h1>
            <div class="breadcrumb">
                <a href="index.php">Home</a> / <a href="order.php">Shop</a> / <span>My Order</span>
            </div>
        </div>
    </section>

    <!-- My Order Section -->
    <section class="my-order-section">
        <div class="container">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <div class="order-container">
                <?php if (empty($_SESSION['shoppingCart'])): ?>
                <div class="empty-cart-message">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Your cart is empty</h2>
                    <p>Browse our collection and add some fabrics to your cart</p>
                    <a href="order.php" class="btn-primary">Shop Now</a>
                </div>
                <?php else: ?>
                <div class="order-content">
                    <div class="order-items">
                        <h2>Order Items</h2>
                        <div class="cart-items-container">
                            <?php foreach ($_SESSION['shoppingCart'] as $index => $item): 
                                $itemTotal = $item['price'] * $item['quantity'];
                                $hasDesign = isset($item['design']) ? true : false;
                            ?>
                            <div class="cart-item">
                                <div class="cart-item-image">
                                    <?php 
                                    // Make sure path has images/ prefix
                                    if (!strpos($item['image'], '/') && !strpos($item['image'], '\\')) {
                                        $item['image'] = "images/" . $item['image'];
                                    }
                                    
                                    // Create placeholder for fallback
                                    $placeholderImage = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHgAAAB4CAMAAAAOusbgAAAAM1BMVEX////y8vL8/Pz19fX5+fnr6+vl5eXo6Oj29vbs7Ozv7+/6+vrm5ubz8/Px8fHt7e3q6uqS/1kuAAAA/UlEQVRoge3Z2w6CMBBAUQQRuYj//7cGNUZlpjTpZA77vJI9bQrEVhUAAAAAAAAAALCw8g1b/72+netbcapvzXx9ut5DJ4+194arEVdPdb3h/qoTy8QmPE+4qMQNM+VGdCK+YWa8V4kZ4jadd1aJHeIWmkbsnyjEIzPEbJP3+xZ7wznifcQjd7xPuOSO91e8S7jf4jOgFPE5PKsQhRbHFi+fwx1ihnj7HK4QOw5LiBniVXhYIXYbFhCzxM5DiNliz2L22HkIMVvsOoSYKfYsZo89ixlj32LG2LuYL/YuZoz9i/li/2K22L+YLd5QzBXvKOaKtxQzxXuKeeJdxTzxvmKWeF8xS7yxmCHeWMwQAwAAAAAAAMCf3QCn1RfZW0ho0QAAAABJRU5ErkJggg==";
                                    
                                    // Try to find best image match
                                    $bestPath = file_exists_case_insensitive($item['image']);
                                    
                                    if ($bestPath) {
                                        echo '<img src="' . htmlspecialchars($bestPath) . '" alt="' . htmlspecialchars($item['name']) . '">';
                                    } else {
                                        // First try with original extension
                                        echo '<img src="' . htmlspecialchars($item['image']) . '" 
                                            alt="' . htmlspecialchars($item['name']) . '" 
                                            onerror="if(this.src!=\'images/' . htmlspecialchars($item['image']) . '\') {
                                                this.src=\'images/' . htmlspecialchars($item['image']) . '\';
                                            } else if(this.src.includes(\'.webp\')) {
                                                this.src=this.src.replace(\'.webp\', \'.jpg\');
                                            } else if(this.src.includes(\'.jpg\')) {
                                                this.src=this.src.replace(\'.jpg\', \'.webp\');
                                            } else {
                                                this.src=\'' . $placeholderImage . '\';
                                                this.style.padding=\'10px\';
                                            }">';
                                    }
                                    ?>
                                </div>
                                <div class="cart-item-details">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <p class="price">PKR. <?php echo number_format($item['price'], 0); ?></p>
                                    
                                    <?php if ($hasDesign): ?>
                                    <p class="design-details"><?php echo htmlspecialchars($item['design']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($item['measurements']) && isset($item['hasMeasurements']) && $item['hasMeasurements']): ?>
                                    <div class="measurements-display">
                                        <h4>
                                            <?php echo isset($item['measurementSource']) ? $item['measurementSource'] : 'Measurements'; ?>
                                            <?php if (isset($item['accuracy'])): ?>
                                                <span style="font-size: 12px; color: 
                                                    <?php echo $item['accuracy'] >= 85 ? '#28a745' : ($item['accuracy'] >= 75 ? '#ffc107' : '#dc3545'); ?>">
                                                    (<?php echo $item['accuracy']; ?>% accuracy)
                                                </span>
                                            <?php endif; ?>
                                            <button type="button" class="edit-measurements" data-index="<?php echo $index; ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        </h4>
                                        
                                        <table style="width: 100%; font-size: 12px;">
                                            <?php 
                                            $measurementLabels = [
                                                'chest' => 'Chest', 'waist' => 'Waist', 'hip' => 'Hip', 'shoulder' => 'Shoulder',
                                                'sleeve_length' => 'Sleeve Length', 'trouser_length' => 'Trouser Length', 
                                                'kameez_length' => 'Kameez Length', 'neck' => 'Neck'
                                            ];
                                            
                                            foreach ($measurementLabels as $key => $label):
                                                if (isset($item['measurements'][$key])):
                                                    $measurement = $item['measurements'][$key];
                                                    $value = is_array($measurement) ? $measurement['value'] : $measurement;
                                                    $confidence = is_array($measurement) ? ($measurement['confidence'] ?? 0.8) : 0.8;
                                            ?>
                                            <tr>
                                                <td style="padding: 2px 5px; font-weight: 500;"><?php echo $label; ?>:</td>
                                                <td style="padding: 2px 5px;"><?php echo htmlspecialchars($value); ?> inches</td>
                                                <td style="padding: 2px 5px; font-size: 10px; color: 
                                                    <?php echo $confidence >= 0.85 ? '#28a745' : ($confidence >= 0.75 ? '#ffc107' : '#dc3545'); ?>">
                                                    (<?php echo round($confidence * 100); ?>%)
                                                </td>
                                            </tr>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="design-btn-group">
                                        <button class="customize-item-btn" data-index="<?php echo $index; ?>">
                                            <i class="fas fa-tshirt"></i> <?php echo $hasDesign ? 'Edit Design' : 'Customize Design'; ?>
                                        </button>
                                        <button class="measurement-btn" data-index="<?php echo $index; ?>">
                                            <i class="fas fa-ruler"></i> 
                                            <?php echo (isset($item['hasMeasurements']) && $item['hasMeasurements']) ? 'Edit Measurements' : 'Add Measurements'; ?>
                                        </button>
                                    </div>
                                </div>
                                <div class="cart-item-quantity">
                                    <form method="post" class="quantity-form" id="decrease-form-<?php echo $index; ?>">
                                        <input type="hidden" name="action" value="update_quantity">
                                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                                        <input type="hidden" name="quantity" value="<?php echo max(1, $item['quantity'] - 1); ?>">
                                        <button type="button" class="quantity-btn decrease" data-index="<?php echo $index; ?>">-</button>
                                    </form>
                                    <span class="quantity"><?php echo $item['quantity']; ?></span>
                                    <form method="post" class="quantity-form" id="increase-form-<?php echo $index; ?>">
                                        <input type="hidden" name="action" value="update_quantity">
                                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                                        <input type="hidden" name="quantity" value="<?php echo $item['quantity'] + 1; ?>">
                                        <button type="button" class="quantity-btn increase" data-index="<?php echo $index; ?>">+</button>
                                    </form>
                                </div>
                                <div class="cart-item-total">
                                    <p>PKR. <?php echo number_format($itemTotal, 0); ?></p>
                                    <button class="add-to-cart-btn <?php echo ($hasDesign && isset($item['hasMeasurements']) && $item['hasMeasurements']) ? '' : 'disabled'; ?>" data-index="<?php echo $index; ?>">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </div>
                                <form method="post" class="remove-form">
                                    <input type="hidden" name="action" value="remove_item">
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <button type="submit" class="remove-item" data-index="<?php echo $index; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <div class="item-design-container" data-index="<?php echo $index; ?>"></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="cart-actions">
                            <a href="cart.php" class="btn-view-cart">View Full Cart</a>
                            <form method="post">
                                <input type="hidden" name="action" value="clear_cart">
                                <button type="submit" class="btn-clear-cart">Clear Cart</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Item Design Template (Hidden) -->
    <div id="design-options-template" class="item-design-options" style="display: none;">
        <div class="design-header">
            <h4>Customize Your Design</h4>
            <button class="close-design-btn" title="Close"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="post" class="design-form">
            <input type="hidden" name="action" value="save_design">
            <input type="hidden" name="index" value="ITEMID">
            
            <div class="design-section collar-style">
                <h5>Collar Style</h5>
                <div class="design-options">
                    <div class="design-option">
                        <input type="radio" id="collar-band-ITEMID" name="collar" value="Band Collar">
                        <label for="collar-band-ITEMID">Band Collar</label>
                    </div>
                    <div class="design-option">
                        <input type="radio" id="collar-straight-ITEMID" name="collar" value="Straight Collar">
                        <label for="collar-straight-ITEMID">Straight Collar</label>
                    </div>
                </div>
            </div>
            
            <div class="design-section kurta-style">
                <h5>Kameez Style</h5>
                <div class="design-options">
                    <div class="design-option">
                        <input type="radio" id="kurta-style-ITEMID" name="kurta" value="Kurta Style">
                        <label for="kurta-style-ITEMID">Kurta Style</label>
                    </div>
                    <div class="design-option">
                        <input type="radio" id="simple-kameez-ITEMID" name="kurta" value="Simple Kameez">
                        <label for="simple-kameez-ITEMID">Simple Kameez</label>
                    </div>
                </div>
            </div>
            
            <div class="design-section bottom-style">
                <h5>Bottom Style</h5>
                <div class="design-options">
                    <div class="design-option">
                        <input type="radio" id="bottom-shalwar-ITEMID" name="bottom" value="Shalwar">
                        <label for="bottom-shalwar-ITEMID">Shalwar</label>
                    </div>
                    <div class="design-option">
                        <input type="radio" id="bottom-trouser-ITEMID" name="bottom" value="Trouser">
                        <label for="bottom-trouser-ITEMID">Trouser</label>
                    </div>
                </div>
            </div>
            
            <div class="design-section front-pocket-style">
                <h5>Front Pocket</h5>
                <div class="design-options">
                    <div class="design-option">
                        <input type="radio" id="front-pocket-yes-ITEMID" name="front_pocket" value="With Front Pocket">
                        <label for="front-pocket-yes-ITEMID">With Front Pocket</label>
                    </div>
                    <div class="design-option">
                        <input type="radio" id="front-pocket-no-ITEMID" name="front_pocket" value="No Front Pocket">
                        <label for="front-pocket-no-ITEMID">No Front Pocket</label>
                    </div>
                </div>
            </div>
            
            <div class="design-section side-pocket-style">
                <h5>Side Pockets</h5>
                <div class="design-options">
                    <div class="design-option">
                        <input type="radio" id="side-pocket-two-ITEMID" name="side_pocket" value="Two Side Pockets">
                        <label for="side-pocket-two-ITEMID">Two Side Pockets</label>
                    </div>
                    <div class="design-option">
                        <input type="radio" id="side-pocket-one-ITEMID" name="side_pocket" value="One Side Pocket">
                        <label for="side-pocket-one-ITEMID">One Side Pocket</label>
                    </div>
                    <div class="design-option">
                        <input type="radio" id="side-pocket-none-ITEMID" name="side_pocket" value="No Side Pockets">
                        <label for="side-pocket-none-ITEMID">No Side Pockets</label>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-confirm-design" data-item-id="ITEMID">Confirm Design</button>
        </form>
    </div>

    <!-- MODALS SECTION - Yahan update karna hai -->
    
    <!-- Measurement Options Modal -->
    <div id="measurement-options-modal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div class="modal-content" style="background: white; padding: 30px; border-radius: 12px; max-width: 400px; width: 90%; position: relative;">
            <span class="close-modal" style="position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: #999;">&times;</span>
            
            <h3 style="margin-bottom: 20px; color: #333;">
                <i class="fas fa-ruler"></i> Choose Measurement Method
            </h3>
            
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <button id="webcam-measurement-option" class="measurement-option-btn" style="
                    background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%);
                    color: white;
                    border: none;
                    padding: 15px 20px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 16px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                    transition: all 0.3s ease;
                ">
                    <i class="fas fa-camera"></i>
                    AI Webcam Measurement (Recommended)
                </button>
                
                <button id="manual-measurement-option" class="measurement-option-btn" style="
                    background: #6c757d;
                    color: white;
                    border: none;
                    padding: 15px 20px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 16px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                    transition: all 0.3s ease;
                ">
                    <i class="fas fa-edit"></i>
                    Manual Entry
                </button>
            </div>
        </div>
    </div>
    
    <!-- Webcam Measurement Modal -->
    <div id="webcam-measurement-modal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1001; justify-content: center; align-items: center;">
        <div class="modal-content" style="background: white; padding: 20px; border-radius: 12px; max-width: 900px; width: 95%; max-height: 90vh; overflow-y: auto; position: relative;">
            <span class="close-modal" style="position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: #999; z-index: 10;">&times;</span>
            
            <h3 style="margin-bottom: 20px; color: #333; text-align: center;">
                <i class="fas fa-robot"></i> AI-Powered Body Measurement
            </h3>
            
            <!-- Hidden input for measurement index -->
            <input type="hidden" id="webcam-measurement-item-index" value="">
            
            <!-- Camera Section -->
            <div class="camera-section" style="position: relative; width: 100%; max-width: 640px; margin: 0 auto 20px; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);">
                <video id="webcam-video" width="640" height="480" autoplay muted playsinline style="width: 100%; height: auto; display: block; background: #000;"></video>
                <!-- Canvas will be added here by JavaScript -->
            </div>
            
            <!-- Pose Indicator -->
            <div id="pose-indicator" class="pose-indicator" style="
                text-align: center;
                padding: 15px;
                margin: 15px 0;
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                border-radius: 8px;
                border-left: 4px solid #007bff;
                font-size: 14px;
                color: #495057;
            ">
                📷 Position yourself in the frame
            </div>
            
            <!-- Controls -->
            <div class="webcam-controls" style="text-align: center; margin: 20px 0;">
                <button id="start-camera-btn" class="webcam-btn" style="
                    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                    color: white;
                    border: none;
                    padding: 12px 20px;
                    border-radius: 8px;
                    cursor: pointer;
                    margin: 5px;
                    font-size: 14px;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                ">
                    <i class="fas fa-camera"></i> Start Camera
                </button>
                
                <button id="stop-camera-btn" class="webcam-btn" style="
                    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
                    color: white;
                    border: none;
                    padding: 12px 20px;
                    border-radius: 8px;
                    cursor: pointer;
                    margin: 5px;
                    font-size: 14px;
                    display: none;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                ">
                    <i class="fas fa-stop"></i> Stop Camera
                </button>
                
                <button id="capture-measurement-btn" class="webcam-btn" style="
                    background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%);
                    color: white;
                    border: none;
                    padding: 12px 20px;
                    border-radius: 8px;
                    cursor: pointer;
                    margin: 5px;
                    font-size: 14px;
                    display: none;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                ">
                    <i class="fas fa-check"></i> Capture Measurements
                </button>
                
                <button id="retake-measurement-btn" class="webcam-btn" style="
                    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
                    color: white;
                    border: none;
                    padding: 12px 20px;
                    border-radius: 8px;
                    cursor: pointer;
                    margin: 5px;
                    font-size: 14px;
                    display: none;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                ">
                    <i class="fas fa-redo"></i> Retake
                </button>
            </div>
            
            <!-- Measurement Results (initially hidden) -->
            <div id="webcam-results" style="display: none; margin-top: 20px;">
                <h4 style="color: #28a745; text-align: center;">
                    <i class="fas fa-check-circle"></i> AI Measurements Detected
                </h4>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 15px 0;">
                    <input type="number" id="webcam-shoulder" placeholder="Shoulder Width" step="0.1" readonly style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="number" id="webcam-chest" placeholder="Chest" step="0.1" readonly style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="number" id="webcam-waist" placeholder="Waist" step="0.1" readonly style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="number" id="webcam-hip" placeholder="Hip" step="0.1" readonly style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="number" id="webcam-sleeve_length" placeholder="Sleeve Length" step="0.1" readonly style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="number" id="webcam-trouser_length" placeholder="Trouser Length" step="0.1" readonly style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="number" id="webcam-kameez_length" placeholder="Kameez Length" step="0.1" readonly style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="number" id="webcam-neck" placeholder="Neck" step="0.1" readonly style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>
            
            <!-- Instructions -->
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 15px; font-size: 12px; color: #6c757d;">
                <strong>Instructions:</strong>
                <ul style="margin: 5px 0 0 20px;">
                    <li>Stand 3-4 feet away from camera</li>
                    <li>Keep arms slightly away from body</li>
                    <li>Face the camera directly</li>
                    <li>Ensure good lighting</li>
                    <li>Stay still during measurement</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Manual Measurement Modal -->
    <div id="manual-measurement-modal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1001; justify-content: center; align-items: center;">
        <div class="modal-content" style="background: white; padding: 20px; border-radius: 12px; max-width: 600px; width: 95%; max-height: 90vh; overflow-y: auto; position: relative;">
            <span class="close-modal" style="position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: #999; z-index: 10;">&times;</span>
            
            <h3 style="margin-bottom: 20px; color: #333; text-align: center;">
                <i class="fas fa-ruler"></i> Enter Your Measurements
            </h3>
            
            <!-- Hidden input for measurement index -->
            <input type="hidden" id="measurement-item-index" value="">
            
            <form method="post" id="measurement-form">
                <input type="hidden" name="action" value="save_measurements">
                <input type="hidden" name="index" id="measurement-form-index" value="">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px;">
                    <div class="measurement-field">
                        <label for="chest" style="display: block; margin-bottom: 5px; font-weight: 500;">
                            <i class="fas fa-expand-arrows-alt"></i> Chest (inches)
                        </label>
                        <input type="number" id="chest" name="chest" step="0.1" placeholder="e.g. 40.5" 
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <small style="color: #666; font-size: 12px;">Measure around fullest part of chest</small>
                    </div>
                    
                    <div class="measurement-field">
                        <label for="waist" style="display: block; margin-bottom: 5px; font-weight: 500;">
                            <i class="fas fa-expand-arrows-alt"></i> Waist (inches)
                        </label>
                        <input type="number" id="waist" name="waist" step="0.1" placeholder="e.g. 32.0"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <small style="color: #666; font-size: 12px;">Measure around natural waistline</small>
                    </div>
                    
                    <div class="measurement-field">
                        <label for="hip" style="display: block; margin-bottom: 5px; font-weight: 500;">
                            <i class="fas fa-expand-arrows-alt"></i> Hip (inches)
                        </label>
                        <input type="number" id="hip" name="hip" step="0.1" placeholder="e.g. 38.0"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <small style="color: #666; font-size: 12px;">Measure around fullest part of hips</small>
                    </div>
                    
                    <div class="measurement-field">
                        <label for="shoulder" style="display: block; margin-bottom: 5px; font-weight: 500;">
                            <i class="fas fa-expand-arrows-alt"></i> Shoulder (inches)
                        </label>
                        <input type="number" id="shoulder" name="shoulder" step="0.1" placeholder="e.g. 16.5"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <small style="color: #666; font-size: 12px;">Measure from shoulder edge to edge</small>
                    </div>
                    
                    <div class="measurement-field">
                        <label for="sleeve_length" style="display: block; margin-bottom: 5px; font-weight: 500;">
                            <i class="fas fa-expand-arrows-alt"></i> Sleeve Length (inches)
                        </label>
                        <input type="number" id="sleeve_length" name="sleeve_length" step="0.1" placeholder="e.g. 24.0"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <small style="color: #666; font-size: 12px;">Measure from shoulder to wrist</small>
                    </div>
                    
                    <div class="measurement-field">
                        <label for="trouser_length" style="display: block; margin-bottom: 5px; font-weight: 500;">
                            <i class="fas fa-expand-arrows-alt"></i> Trouser Length (inches)
                        </label>
                        <input type="number" id="trouser_length" name="trouser_length" step="0.1" placeholder="e.g. 42.0"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <small style="color: #666; font-size: 12px;">Measure from waist to ankle</small>
                    </div>
                    
                    <div class="measurement-field">
                        <label for="kameez_length" style="display: block; margin-bottom: 5px; font-weight: 500;">
                            <i class="fas fa-expand-arrows-alt"></i> Kameez Length (inches)
                        </label>
                        <input type="number" id="kameez_length" name="kameez_length" step="0.1" placeholder="e.g. 36.0"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <small style="color: #666; font-size: 12px;">Measure from shoulder to desired length</small>
                    </div>
                    
                    <div class="measurement-field">
                        <label for="neck" style="display: block; margin-bottom: 5px; font-weight: 500;">
                            <i class="fas fa-expand-arrows-alt"></i> Neck (inches)
                        </label>
                        <input type="number" id="neck" name="neck" step="0.1" placeholder="e.g. 15.0"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <small style="color: #666; font-size: 12px;">Measure around the base of your neck</small>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <label for="notes" style="display: block; margin-bottom: 5px; font-weight: 500;">
                        <i class="fas fa-sticky-note"></i> Additional Notes (Optional)
                    </label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Any special requirements or notes..." 
                             style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"></textarea>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" style="
                        padding: 12px 30px;
                        border-radius: 8px;
                        cursor: pointer;
                        font-size: 16px;
                        font-weight: 500;
                    ">
                        <i class="fas fa-save"></i> Save Measurements
                    </button>
                </div>
            </form>
            
            <!-- Measurement Guide -->
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 20px; font-size: 12px; color: #6c757d;">
                <strong>📏 Measurement Tips:</strong>
                <ul style="margin: 5px 0 0 20px;">
                    <li>Use a flexible measuring tape</li>
                    <li>Wear fitted clothing while measuring</li>
                    <li>Keep the tape snug but not tight</li>
                    <li>Ask someone to help for better accuracy</li>
                    <li>Measure twice to confirm accuracy</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Back to top button -->
    <div class="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section about">
                    <div class="footer-logo">
                        <a href="index.php" class="logo-button">
                            <h3 class="logo-text">Stitch House</h3>
                        </a>
                    </div>
                    <p>Your premium destination for high-quality fabrics and exceptional service.</p>
                    <div class="social-icons">
                        <a href="#" class="facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="pinterest"><i class="fab fa-pinterest"></i></a>
                        <a href="#" class="youtube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-section links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="order.php">Shop Now</a></li>
                        <li><a href="myorder.php">My Order</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-section contact">
                    <h3>Contact Info</h3>
                    <p><i class="fas fa-map-marker-alt"></i>Main Gulberg, Lahore, Pakistan</p>
                    <p><i class="fas fa-phone"></i> +0304-2292813</p>
                    <p><i class="fas fa-envelope"></i> info@stitchhouse.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Stitch House. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Global variables
        let currentMeasurementIndex = null;
        let mediaPipeMeasurement = null;

        // Utility Functions (Outside DOMContentLoaded)
        function showCameraError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.style.cssText = `
                position: fixed; top: 20px; right: 20px; background: #dc3545;
                color: white; padding: 15px; border-radius: 8px; z-index: 9999;
                box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
            `;
            errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
            document.body.appendChild(errorDiv);
            
            setTimeout(() => errorDiv.remove(), 5000);
        }

        function disableWebcamOption(reason) {
            const webcamOption = document.getElementById('webcam-measurement-option');
            if (webcamOption) {
                webcamOption.disabled = true;
                webcamOption.innerHTML = '<i class="fas fa-exclamation-triangle"></i> AI Measurement Unavailable';
                webcamOption.style.background = '#dc3545';
                webcamOption.title = `Disabled: ${reason}`;
            }
        }

        function checkMediaPipeScripts() {
            const requiredScripts = [
                { name: 'Pose', check: () => typeof Pose !== 'undefined' },
                { name: 'Camera', check: () => typeof Camera !== 'undefined' },
                { name: 'MediaPipeMeasurement', check: () => typeof MediaPipeMeasurement !== 'undefined' }
            ];
            
            const missing = requiredScripts.filter(script => !script.check());
            
            if (missing.length > 0) {
                console.error('Missing MediaPipe components:', missing.map(s => s.name));
                disableWebcamOption(`Missing components: ${missing.map(s => s.name).join(', ')}`);
                return false;
            }
            
            console.log('✅ All MediaPipe components loaded successfully');
            return true;
        }

        // Single MediaPipe Initialization Function
        async function initializeMediaPipe() {
            if (mediaPipeMeasurement) {
                console.log('⚠️ MediaPipe already initialized');
                return true;
            }

            try {
                if (typeof Pose === 'undefined' || typeof Camera === 'undefined') {
                    throw new Error('MediaPipe libraries not loaded');
                }
                
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error('Camera not supported in this browser');
                }
                
                if (window.MediaPipeMeasurement) {
                    mediaPipeMeasurement = new MediaPipeMeasurement();
                    
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({ 
                            video: { width: 640, height: 480 } 
                        });
                        stream.getTracks().forEach(track => track.stop());
                        
                        await mediaPipeMeasurement.initialize();
                        console.log('✅ MediaPipe initialized successfully');
                        window.mediaPipeMeasurement = mediaPipeMeasurement;
                        return true;
                    } catch (cameraError) {
                        console.warn('⚠️ Camera test failed:', cameraError);
                        disableWebcamOption('Camera access failed');
                        return false;
                    }
                } else {
                    throw new Error('MediaPipeMeasurement class not found');
                }
            } catch (error) {
                console.error('❌ MediaPipe initialization failed:', error);
                disableWebcamOption(error.message);
                return false;
            }
        }

        async function checkCameraPermissions() {
            try {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error('Camera not supported on this device/browser');
                }
                
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: 'user'
                    },
                    audio: false
                });
                
                stream.getTracks().forEach(track => track.stop());
                console.log('✅ Camera permission granted');
                return true;
                
            } catch (error) {
                console.error('❌ Camera permission error:', error);
                
                if (error.name === 'NotAllowedError') {
                    showCameraError('Camera permission denied. Please click "Allow" when prompted.');
                } else if (error.name === 'NotFoundError') {
                    showCameraError('No camera found. Please connect a camera.');
                } else if (error.name === 'NotSupportedError') {
                    showCameraError('Camera not supported on this browser.');
                } else {
                    showCameraError('Camera access error: ' + error.message);
                }
                
                return false;
            }
        }

        // Helper Functions for Design Panel
        function addDesignPanelEventListeners(index) {
            const designContainer = document.querySelector(`.item-design-container[data-index="${index}"]`);
            if (!designContainer) return;
            
            const closeBtn = designContainer.querySelector('.close-design-btn');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    designContainer.innerHTML = '';
                });
            }
            
            const designForm = designContainer.querySelector('.design-form');
            if (designForm) {
                designForm.addEventListener('submit', function(e) {
                    const requiredFields = ['collar', 'kurta', 'bottom', 'front_pocket', 'side_pocket'];
                    const missingFields = [];
                    
                    requiredFields.forEach(field => {
                        const selected = designForm.querySelector(`input[name="${field}"]:checked`);
                        if (!selected) {
                            missingFields.push(field.replace('_', ' '));
                        }
                    });
                    
                    if (missingFields.length > 0) {
                        e.preventDefault();
                        alert(`Please select: ${missingFields.join(', ')}`);
                        return false;
                    }
                });
            }
        }

        function preSelectDesignOptions(index, designOptions) {
            const designContainer = document.querySelector(`.item-design-container[data-index="${index}"]`);
            if (!designContainer) return;
            
            Object.keys(designOptions).forEach(key => {
                const input = designContainer.querySelector(`input[name="${key}"][value="${designOptions[key]}"]`);
                if (input) {
                    input.checked = true;
                }
            });
        }

        function showSuccessMessage(message) {
            const successDiv = document.createElement('div');
            successDiv.className = 'success-message';
            successDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
            
            const container = document.querySelector('.order-container');
            if (container) {
                container.insertBefore(successDiv, container.firstChild);
            }
            
            setTimeout(() => {
                successDiv.style.display = 'none';
            }, 5000);
        }

        function showMeasurementSuccessNotification(message, accuracy) {
            const notification = document.createElement('div');
            notification.className = 'measurement-success-notification';
            notification.innerHTML = `
                <div style="font-weight: 600;">
                    <i class="fas fa-check-circle"></i> ${message}
                </div>
                ${accuracy ? `<div style="font-size: 12px; margin-top: 5px;">Accuracy: ${accuracy}%</div>` : ''}
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function displayWebcamMeasurements(measurements) {
            const resultsDiv = document.getElementById('webcam-results');
            if (!resultsDiv) {
                console.error('❌ Webcam results div not found');
                return;
            }
            
            console.log('📊 Displaying measurements:', measurements);
            
            // Fill measurement inputs
            Object.entries(measurements).forEach(([key, data]) => {
                const input = document.getElementById(`webcam-${key}`);
                if (input && data && data.value) {
                    input.value = data.value.toFixed(1);
                    console.log(`✅ Set ${key}: ${data.value.toFixed(1)}`);
                }
            });
            
            resultsDiv.style.display = 'block';
            
            // Remove existing save button to avoid duplicates
            const existingBtn = document.getElementById('save-webcam-measurements-btn');
            if (existingBtn) {
                existingBtn.remove();
            }
            
            // Add new save button
            const saveBtn = document.createElement('button');
            saveBtn.id = 'save-webcam-measurements-btn';
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Measurements';
            saveBtn.style.cssText = `
                background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%);
                color: white;
                border: none;
                padding: 12px 20px;
                border-radius: 8px;
                cursor: pointer;
                margin: 15px auto;
                display: block;
                font-size: 16px;
                transition: all 0.3s ease;
            `;
            saveBtn.onclick = () => saveWebcamMeasurements(measurements);
            resultsDiv.appendChild(saveBtn);
        }

        function saveWebcamMeasurements(measurements) {
            console.log('💾 Starting save process...');
            
            if (!measurements || typeof measurements !== 'object') {
                alert('No valid measurements to save');
                return;
            }
            
            if (!currentMeasurementIndex && currentMeasurementIndex !== 0) {
                alert('Error: No item selected for measurements');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'save_measurements');
            formData.append('index', currentMeasurementIndex);
            formData.append('source', 'ai_webcam');
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            
            let validMeasurementCount = 0;
            
            // FIXED: Extract individual values from measurement objects
            Object.entries(measurements).forEach(([key, data]) => {
                if (data && typeof data === 'object' && data.value && data.value > 0) {
                    // Send only the value, not the entire object
                    formData.append(key, data.value.toString());
                    formData.append(`${key}_confidence`, (data.confidence || 0.8).toString());
                    validMeasurementCount++;
                }
            });
            
            if (validMeasurementCount < 3) {
                alert(`Only ${validMeasurementCount} valid measurements found. At least 3 required.`);
                return;
            }
            
            const saveBtn = document.getElementById('save-webcam-measurements-btn');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            }
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.text())
            .then(responseText => {
                try {
                    const data = JSON.parse(responseText.trim());
                    if (data.success) {
                        alert('AI measurements saved successfully!');
                        document.getElementById('webcam-measurement-modal').style.display = 'none';
                        location.reload();
                    } else {
                        alert('Save failed: ' + (data.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('JSON Parse Error:', error);
                    alert('Server error. Please try again.');
                }
            })
            .catch(error => {
                console.error('Request failed:', error);
                alert('Failed to save measurements: ' + error.message);
            })
            .finally(() => {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Measurements';
                }
            });
        }

        // DOM Content Loaded Event (Main Initialization)
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 DOM Content Loaded - Starting initialization...');
            
            // Single MediaPipe initialization with delay
            setTimeout(() => {
                console.log('⏰ Starting MediaPipe check and init...');
                if (checkMediaPipeScripts()) {
                    initializeMediaPipe();
                }
            }, 2000);
            
            // Side navigation functionality
            const sideNavToggle = document.querySelector('.menu-toggle');
            const sideNav = document.querySelector('.side-nav');
            const sideNavBackdrop = document.querySelector('.side-nav-backdrop');
            const sideNavClose = document.querySelector('.close-sidenav');
            
            // Get design template
            const designOptionsTemplate = document.getElementById('design-options-template');
            let designTemplateHTML = '';
            if (designOptionsTemplate) {
                designTemplateHTML = designOptionsTemplate.innerHTML;
            }
            
            // Quantity buttons
            document.querySelectorAll('.quantity-btn.decrease').forEach(btn => {
                btn.addEventListener('click', function() {
                    const index = this.dataset.index;
                    const form = document.getElementById(`decrease-form-${index}`);
                    if (form) form.submit();
                });
            });
            
            document.querySelectorAll('.quantity-btn.increase').forEach(btn => {
                btn.addEventListener('click', function() {
                    const index = this.dataset.index;
                    const form = document.getElementById(`increase-form-${index}`);
                    if (form) form.submit();
                });
            });
            
            // Customize item buttons
            document.querySelectorAll('.customize-item-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const index = parseInt(btn.dataset.index);
                    const itemContainer = document.querySelector(`.item-design-container[data-index="${index}"]`);
                    
                    if (!itemContainer) {
                        console.error('Design container not found for index:', index);
                        return;
                    }
                    
                    const existingPanel = itemContainer.querySelector('.item-design-options');
                    if (existingPanel) {
                        existingPanel.style.display = existingPanel.style.display === 'none' ? 'block' : 'none';
                    } else {
                        if (designTemplateHTML) {
                            let designPanel = designTemplateHTML.replace(/ITEMID/g, index);
                            itemContainer.innerHTML = designPanel;
                            
                            addDesignPanelEventListeners(index);
                            
                            <?php if (!empty($_SESSION['shoppingCart'])): ?>
                            const cartItems = <?php echo json_encode($_SESSION['shoppingCart']); ?>;
                            if (cartItems[index] && cartItems[index].designOptions) {
                                preSelectDesignOptions(index, cartItems[index].designOptions);
                            }
                            <?php endif; ?>
                        }
                    }
                });
            });

            // Measurement modal functionality
            const measurementOptionsModal = document.getElementById('measurement-options-modal');
            const manualMeasurementModal = document.getElementById('manual-measurement-modal');
            const webcamMeasurementModal = document.getElementById('webcam-measurement-modal');
            
            // Open measurement options
            document.querySelectorAll('.measurement-btn, .edit-measurements').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    currentMeasurementIndex = this.getAttribute('data-index');
                    console.log('📏 Opening measurement modal for index:', currentMeasurementIndex);
                    
                    if (measurementOptionsModal) {
                        measurementOptionsModal.style.display = 'flex';
                    }
                });
            });
            
            // Manual measurement option
            const manualMeasurementOption = document.getElementById('manual-measurement-option');
            if (manualMeasurementOption) {
                manualMeasurementOption.addEventListener('click', function() {
                    if (measurementOptionsModal) measurementOptionsModal.style.display = 'none';
                    if (manualMeasurementModal) {
                        manualMeasurementModal.style.display = 'flex';
                        
                        const itemIndexField = document.getElementById('measurement-item-index');
                        const formIndexField = document.getElementById('measurement-form-index');
                        
                        if (itemIndexField) itemIndexField.value = currentMeasurementIndex;
                        if (formIndexField) formIndexField.value = currentMeasurementIndex;
                        
                        <?php if (!empty($_SESSION['shoppingCart'])): ?>
                        const cartItems = <?php echo json_encode($_SESSION['shoppingCart']); ?>;
                        if (cartItems[currentMeasurementIndex] && cartItems[currentMeasurementIndex].measurements) {
                            const measurements = cartItems[currentMeasurementIndex].measurements;
                            
                            Object.keys(measurements).forEach(key => {
                                const input = manualMeasurementModal.querySelector(`[name="${key}"]`);
                                if (input && measurements[key].value) {
                                    input.value = measurements[key].value;
                                }
                            });
                        }
                        <?php endif; ?>
                    }
                });
            }
            
            // Webcam measurement option
            const webcamMeasurementOption = document.getElementById('webcam-measurement-option');
            if (webcamMeasurementOption) {
                webcamMeasurementOption.addEventListener('click', async function() {
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                    this.disabled = true;
                    
                    try {
                        console.log('📷 Starting webcam measurement process...');
                        
                        const hasPermission = await checkCameraPermissions();
                        if (!hasPermission) {
                            throw new Error('Camera permission denied');
                        }
                        
                        if (measurementOptionsModal) {
                            measurementOptionsModal.style.display = 'none';
                        }
                        
                        if (webcamMeasurementModal) {
                            webcamMeasurementModal.style.display = 'flex';
                            
                            const webcamIndexField = document.getElementById('webcam-measurement-item-index');
                            if (webcamIndexField) {
                                webcamIndexField.value = currentMeasurementIndex;
                                console.log('📏 Set webcam measurement index:', currentMeasurementIndex);
                            }
                            
                            if (!mediaPipeMeasurement) {
                                console.log('🔄 Initializing MediaPipe for webcam...');
                                mediaPipeMeasurement = new MediaPipeMeasurement();
                                await mediaPipeMeasurement.initialize();
                            }
                            
                            console.log('✅ Webcam modal opened successfully');
                        }
                    } catch (error) {
                        console.error('❌ Error opening webcam modal:', error);
                        showCameraError('Failed to initialize camera: ' + error.message);
                    } finally {
                        this.innerHTML = originalHTML;
                        this.disabled = false;
                    }
                });
            }

            // Camera control buttons
            document.getElementById('start-camera-btn')?.addEventListener('click', async function() {
                try {
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting Camera...';
                    
                    console.log('📹 Starting camera...');

                    const hasPermission = await checkCameraPermissions();
                    if (!hasPermission) {
                        throw new Error('Camera permission denied');
                    }
                    
                    if (!mediaPipeMeasurement) {
                        console.log('🔄 Creating new MediaPipe instance...');
                        mediaPipeMeasurement = new MediaPipeMeasurement();
                        await mediaPipeMeasurement.initialize();
                    }
                    
                    await mediaPipeMeasurement.startCamera('webcam-video');
                    
                    this.style.display = 'none';
                    document.getElementById('stop-camera-btn').style.display = 'inline-flex';
                    document.getElementById('capture-measurement-btn').style.display = 'inline-flex';
                    
                    console.log('✅ Camera started successfully');
                    
                } catch (error) {
                    console.error('❌ Camera start failed:', error);
                    
                    if (error.message.includes('permission') || error.name === 'NotAllowedError') {
                        showCameraError('Camera access denied. Please allow camera permissions and refresh the page.');
                    } else if (error.name === 'NotFoundError') {
                        showCameraError('No camera found. Please connect a camera and try again.');
                    } else if (error.name === 'NotSupportedError') {
                        showCameraError('Camera not supported on this browser. Please try Chrome or Firefox.');
                    } else {
                        showCameraError('Camera error: ' + error.message);
                    }
                    
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-camera"></i> Start Camera';
                }
            });

            document.getElementById('stop-camera-btn')?.addEventListener('click', function() {
                console.log('🛑 Stopping camera...');
                
                if (mediaPipeMeasurement) {
                    mediaPipeMeasurement.stopCamera();
                }
                
                document.getElementById('start-camera-btn').style.display = 'inline-flex';
                document.getElementById('start-camera-btn').disabled = false;
                document.getElementById('start-camera-btn').innerHTML = '<i class="fas fa-camera"></i> Start Camera';
                
                this.style.display = 'none';
                document.getElementById('capture-measurement-btn').style.display = 'none';
                document.getElementById('retake-measurement-btn').style.display = 'none';
                
                const resultsDiv = document.getElementById('webcam-results');
                if (resultsDiv) {
                    resultsDiv.style.display = 'none';
                }
                
                console.log('✅ Camera stopped and UI reset');
            });

            document.getElementById('capture-measurement-btn')?.addEventListener('click', function() {
                console.log('📸 Capture button clicked');
                console.log('MediaPipe exists:', !!mediaPipeMeasurement);
                console.log('Is calibrated:', mediaPipeMeasurement?.isCalibrated);
                
                if (!mediaPipeMeasurement) {
                    alert('AI system not initialized. Please restart camera.');
                    return;
                }
                
                if (!mediaPipeMeasurement.isCalibrated) {
                    alert('Please wait for calibration to complete. Stand still in front of camera for 5 seconds.');
                    return;
                }
                
                const measurements = mediaPipeMeasurement.getCurrentMeasurements();
                console.log('� Current measurements:', measurements);
                
                if (measurements && Object.keys(measurements).length > 0) {
                    const validMeasurements = Object.entries(measurements).filter(([key, data]) => 
                        data && data.value && data.value > 0
                    );
                    
                    console.log(`✅ Valid measurements found: ${validMeasurements.length}`);
                    
                    if (validMeasurements.length >= 3) {
                        displayWebcamMeasurements(measurements);
                        this.style.display = 'none';
                        document.getElementById('retake-measurement-btn').style.display = 'inline-flex';
                    } else {
                        alert(`Only ${validMeasurements.length} valid measurements detected. Please ensure proper pose and try again.`);
                    }
                } else {
                    alert('No measurements detected. Please ensure proper pose, good lighting, and stand still.');
                }
            });

            document.getElementById('retake-measurement-btn')?.addEventListener('click', function() {
                console.log('🔄 Retaking measurements...');
                document.getElementById('webcam-results').style.display = 'none';
                document.getElementById('capture-measurement-btn').style.display = 'inline-flex';
                this.style.display = 'none';
            });

            // Close modal functionality
            document.querySelectorAll('.close-modal').forEach(closeBtn => {
                closeBtn.addEventListener('click', function() {
                    const modal = this.closest('.modal');
                    if (modal) {
                        if (mediaPipeMeasurement) {
                            mediaPipeMeasurement.stopCamera();
                        }
                        modal.style.display = 'none';
                    }
                });
            });

            // Manual measurement form submission
            const measurementForm = document.getElementById('measurement-form');
            // Manual measurement form submission
            if (measurementForm) {
                measurementForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    console.log('📝 Submitting manual measurements...');
                    
                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                    }
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                        return response.text();
                    })
                    .then(responseText => {
                        try {
                            const data = JSON.parse(responseText.trim());
                            
                            if (data.success) {
                                showMeasurementSuccessNotification(data.message, data.accuracy);
                                manualMeasurementModal.style.display = 'none';
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                alert(data.message || 'Error saving measurements');
                            }
                        } catch (jsonError) {
                            console.error('JSON Parse Error:', jsonError);
                            alert('Server error. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error saving measurements:', error);
                        alert('Failed to save measurements. Please try again.');
                    })
                    .finally(() => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Measurements';
                        }
                    });
                });
            }

            // Add to cart functionality
            document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const index = this.dataset.index;
                    const hasDesign = !this.classList.contains('disabled');
                    
                    if (!hasDesign) {
                        alert('Please complete both design and measurements before adding to cart.');
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append('action', 'add_to_final_cart');
                    formData.append('index', index);
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSuccessMessage(data.message);
                            const cartCountElement = document.querySelector('.cart-count');
                            if (cartCountElement) {
                                cartCountElement.textContent = data.cartCount;
                            }
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error adding to cart:', error);
                        alert('Failed to add item to cart');
                    });
                });
            });

            // Back to top functionality
            const backToTopBtn = document.querySelector('.back-to-top');
            if (backToTopBtn) {
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > 300) {
                        backToTopBtn.classList.add('show');
                    } else {
                        backToTopBtn.classList.remove('show');
                    }
                });
                
                backToTopBtn.addEventListener('click', function() {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }

            console.log('✅ All event listeners initialized successfully');
        });
    </script>
</body>
</html>