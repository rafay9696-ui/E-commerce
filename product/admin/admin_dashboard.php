
<?php
require_once '../db_connection.php';
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

// Get admin data
$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];

// Get overall statistics
$totalOrders = 0;
$totalProducts = 0;
$totalCustomers = 0;
$recentOrders = [];

// Get total products count
$sql = "SELECT COUNT(*) AS total FROM products";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    $totalProducts = $row['total'];
}

// Get total customers count
$sql = "SELECT COUNT(*) AS total FROM users";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    $totalCustomers = $row['total'];
}

// Get total orders count
$sql = "SELECT COUNT(*) AS total FROM orders";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    $totalOrders = $row['total'];
}

// Get recent orders
$sql = "SELECT o.id, o.order_date, o.total_amount, o.status, u.name as customer_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.order_date DESC 
        LIMIT 5";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentOrders[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Stitch House</title>
    <link rel="stylesheet" href="../admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Montserrat:wght@400;500;600;700&family=Dancing+Script:wght@600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-sidebar-header">
                <h2>Stitch <span>House</span></h2>
            </div>
            <div class="admin-menu">
                <div class="admin-menu-item active">
                    <a href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="admin-menu-item">
                    <a href="admin_products.php">
                        <i class="fas fa-box"></i>
                        <span>Products</span>
                    </a>
                </div>
                <div class="admin-menu-item">
                    <a href="admin_orders.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Orders</span>
                    </a>
                </div>
                <div class="admin-menu-item">
                    <a href="admin_customers.php">
                        <i class="fas fa-users"></i>
                        <span>Customers</span>
                    </a>
                </div>
                <div class="admin-menu-item">
                    <a href="admin_settings.php">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </div>
                <div class="admin-menu-item">
                    <a href="../admin_logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="admin-content">
            <div class="admin-header">
                <div class="admin-title">
                    <h1>Dashboard</h1>
                </div>
                <div class="admin-user-info">
                    <div class="admin-user-name">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($admin_username); ?>
                    </div>
                    <div class="admin-logout">
                        <a href="../admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Products</h3>
                        <p><?php echo $totalProducts; ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Orders</h3>
                        <p><?php echo $totalOrders; ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Customers</h3>
                        <p><?php echo $totalCustomers; ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Revenue</h3>
                        <p>₨ <?php echo number_format(15000, 0); ?></p>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>Recent Orders</h2>
                    <a href="admin_orders.php">View All</a>
                </div>

                <?php if (!empty($recentOrders)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                            <td>₨ <?php echo number_format($order['total_amount'], 0); ?></td>
                            <td>
                                <?php
                                $statusClass = '';
                                switch (strtolower($order['status'])) {
                                    case 'pending':
                                        $statusClass = 'status-pending';
                                        break;
                                    case 'processing':
                                        $statusClass = 'status-processing';
                                        break;
                                    case 'completed':
                                        $statusClass = 'status-completed';
                                        break;
                                    case 'cancelled':
                                        $statusClass = 'status-cancelled';
                                        break;
                                }
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="admin-action-btn view" onclick="location.href='admin_orders.php?view=<?php echo $order['id']; ?>'">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No recent orders found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>