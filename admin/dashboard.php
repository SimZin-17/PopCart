<?php
session_start();
require '../connection.php';

// 1. SECURITY: Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

try {
    // 2. DATA AGGREGATION: Fetch live metrics from the database
    $commission_rate = 0.10; // PopCart's 10% fee

    // Count total users
    $stmtUsers = $pdo->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmtUsers->fetchColumn();

    // Count total products
    $stmtProducts = $pdo->query("SELECT COUNT(*) FROM products");
    $totalProducts = $stmtProducts->fetchColumn();

    // Count total orders (all time)
    $stmtOrders = $pdo->query("SELECT COUNT(*) FROM orders");
    $totalOrders = $stmtOrders->fetchColumn();

    // Gross Merchandise Volume (GMV) - Total value of all completed items
    $stmtGMV = $pdo->query("SELECT COALESCE(SUM(price * quantity), 0) FROM order_items WHERE item_status = 'completed'");
    $totalGMV = $stmtGMV->fetchColumn();

    // PopCart's Actual Revenue (10% of completed items)
    $stmtRevenue = $pdo->prepare("SELECT COALESCE(SUM((price * quantity) * ?), 0) FROM order_items WHERE item_status = 'completed'");
    $stmtRevenue->execute([$commission_rate]);
    $platformRevenue = $stmtRevenue->fetchColumn();

    // Fetch Top 5 Sellers
    $stmtTopSellers = $pdo->query("
        SELECT u.user_name, COALESCE(SUM(oi.price * oi.quantity), 0) as total_sales, COUNT(oi.order_item_id) as items_sold
        FROM order_items oi
        JOIN users u ON oi.seller_id = u.user_id
        WHERE oi.item_status = 'completed'
        GROUP BY u.user_id
        ORDER BY total_sales DESC
        LIMIT 5
    ");
    $topSellers = $stmtTopSellers->fetchAll();

    // Fetch Top 5 Buyers
    $stmtTopBuyers = $pdo->query("
        SELECT u.user_name, COALESCE(SUM(o.total_amount), 0) as total_spent, COUNT(o.order_id) as orders_placed
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE o.order_status IN ('paid', 'completed')
        GROUP BY u.user_id
        ORDER BY total_spent DESC
        LIMIT 5
    ");
    $topBuyers = $stmtTopBuyers->fetchAll();

} catch (PDOException $e) {
    die("Error loading dashboard metrics: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PopCart Admin - Dashboard</title>
  <link rel="stylesheet" href="admin.css"/>
  <script src="https://kit.fontawesome.com/af69bda2f2.js" crossorigin="anonymous"></script>
  <style>
      /* Inline styles to handle the new tables cleanly */
      .dashboardGrid { display: flex; gap: 20px; flex-wrap: wrap; margin-top: 30px; }
      .dataPanel { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; flex: 1; min-width: 300px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
      .dataPanel h3 { margin-top: 0; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px; color: #333; }
      .dataTable { width: 100%; border-collapse: collapse; text-align: left; }
      .dataTable th { padding: 10px 0; color: #666; font-size: 14px; border-bottom: 1px solid #eee; }
      .dataTable td { padding: 12px 0; border-bottom: 1px solid #f9f9f9; font-size: 14px; color: #333; }
      .dataTable tr:last-child td { border-bottom: none; }
      .highlight { font-weight: bold; color: #28a745; }
  </style>
</head>
<body>

<div id="adminWrapper">

  <div id="sidebar">
    <h2 id="adminLogo">PopCart <span>Admin</span></h2>
    <ul id="sideMenu">
      <li><a href="dashboard.php" style="font-weight: bold; color: #3b82f6;"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
      <li><a href="users.php"><i class="fa-solid fa-users"></i> Users</a></li>
      <li><a href="products.php" style="font-weight: bold; color: #3b82f6;"><i class="fa-solid fa-shield-halved"></i> Moderation</a></li>
      <li><a href="../index.php"><i class="fa-solid fa-house"></i> Main Site</a></li>
      <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
  </div>

  <div id="mainContent">

    <h2>Welcome, Admin</h2>
    <p>Here is your platform's financial and performance overview.</p>

    <div id="statCards">
      <div class="statCard">
        <i class="fa-solid fa-users"></i>
        <h3>Total Users</h3>
        <p><?= number_format((int)$totalUsers) ?></p>
      </div>

      <div class="statCard">
        <i class="fa-solid fa-cart-shopping"></i>
        <h3>Total Orders</h3>
        <p><?= number_format((int)$totalOrders) ?></p>
      </div>

      <div class="statCard">
        <i class="fa-solid fa-money-bill-transfer"></i>
        <h3>Gross Volume</h3>
        <p>R<?= number_format((float)$totalGMV, 2) ?></p>
      </div>

      <div class="statCard" style="border-left: 4px solid #28a745;">
        <i class="fa-solid fa-wallet" style="color: #28a745;"></i>
        <h3>Platform Revenue</h3>
        <p style="color: #28a745;">R<?= number_format((float)$platformRevenue, 2) ?></p>
      </div>
    </div>

    <div class="dashboardGrid">
        
        <div class="dataPanel">
            <h3><i class="fa-solid fa-store"></i> Top Sellers</h3>
            <?php if(empty($topSellers)): ?>
                <p style="color:#777;">No completed sales yet.</p>
            <?php else: ?>
                <table class="dataTable">
                    <tr>
                        <th>Seller Name</th>
                        <th>Items Sold</th>
                        <th>Total Sales (Gross)</th>
                    </tr>
                    <?php foreach($topSellers as $seller): ?>
                    <tr>
                        <td><?= htmlspecialchars($seller['user_name']) ?></td>
                        <td><?= (int)$seller['items_sold'] ?></td>
                        <td class="highlight">R<?= number_format($seller['total_sales'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <div class="dataPanel">
            <h3><i class="fa-solid fa-bag-shopping"></i> Top Buyers</h3>
            <?php if(empty($topBuyers)): ?>
                <p style="color:#777;">No paid orders yet.</p>
            <?php else: ?>
                <table class="dataTable">
                    <tr>
                        <th>Buyer Name</th>
                        <th>Orders Placed</th>
                        <th>Total Spent</th>
                    </tr>
                    <?php foreach($topBuyers as $buyer): ?>
                    <tr>
                        <td><?= htmlspecialchars($buyer['user_name']) ?></td>
                        <td><?= (int)$buyer['orders_placed'] ?></td>
                        <td class="highlight">R<?= number_format($buyer['total_spent'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

    </div>

  </div>

</div>

<script src="index.js"></script>
</body>
</html>