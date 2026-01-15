<?php
/**
 * Common Head Component
 * Include this in the <head> section of each page
 *
 * Usage:
 * $pageTitle = "Page Title";
 * $pageStyles = ['cart.css', 'checkout.css']; // optional additional CSS
 * include 'includes/head.php';
 */

$pageTitle = $pageTitle ?? 'Stitch House - Premium Fabric Store';
$pageStyles = $pageStyles ?? [];
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo e($pageTitle); ?></title>
<link rel="stylesheet" href="variables.css">
<link rel="stylesheet" href="styles.css">
<?php foreach ($pageStyles as $style): ?>
<link rel="stylesheet" href="<?php echo e($style); ?>">
<?php endforeach; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Montserrat:wght@400;500;600;700&family=Dancing+Script:wght@600;700&display=swap" rel="stylesheet">
