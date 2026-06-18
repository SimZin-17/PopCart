<?php
session_start();
require 'connection.php';

// 1. Security & Mode Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Force the session into seller mode
$_SESSION['app_mode'] = 'seller';
$seller_id = $_SESSION['user_id'];

try {
    // 2. Fetch Seller Metrics (MVP Payout Logic)
    $commission_rate = 0.90; // Seller keeps 90%, PopCart keeps 10%
    
    // A. Calculate Total Earned (90% of completed orders)
    $stmtEarned = $pdo->prepare("SELECT COALESCE(SUM((price * quantity) * ?), 0) FROM order_items WHERE seller_id = ? AND item_status = 'completed'");
    $stmtEarned->execute([$commission_rate, $seller_id]);
    $totalEarned = $stmtEarned->fetchColumn();

    // B. Calculate Total Paid Out (Sum of all manual EFTs you've sent them)
    $stmtPaid = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM seller_payouts WHERE seller_id = ?");
    $stmtPaid->execute([$seller_id]);
    $totalPaidOut = $stmtPaid->fetchColumn();

    // C. The final dashboard wallet balance
    $totalRevenue = $totalEarned - $totalPaidOut;

    // Pending Orders (Items awaiting acceptance/shipping)
    $stmtPending = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE seller_id = ? AND item_status = 'pending'");
    $stmtPending->execute([$seller_id]);
    $pendingOrders = $stmtPending->fetchColumn();

    // Total Active Listings
    $stmtListings = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ?");
    $stmtListings->execute([$seller_id]);
    $totalListings = $stmtListings->fetchColumn();

    // Recent 5 Order Items for quick glance (Calculates Gross vs Net)
    $stmtRecent = $pdo->prepare("
        SELECT 
            oi.order_item_id, 
            oi.quantity, 
            (oi.price * oi.quantity) as gross_amount, 
            ((oi.price * oi.quantity) * ?) as net_earnings, 
            oi.item_status, 
            p.product_name 
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.seller_id = ?
        ORDER BY oi.order_item_id DESC LIMIT 5
    ");
    $stmtRecent->execute([$commission_rate, $seller_id]);
    $recentOrders = $stmtRecent->fetchAll();

} catch (Exception $e) {
    die("Error loading dashboard: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PopCart - Seller Hub</title>
  <link rel="stylesheet" href="index.css">
  <script src="https://kit.fontawesome.com/af69bda2f2.js" crossorigin="anonymous"></script>
  <style>
      /* Quick inline styles for the dashboard cards */
      .dashGrid { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
      .dashCard { background: #fdfdfd; border: 1px solid #eee; border-radius: 8px; padding: 20px; flex: 1; min-width: 200px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
      .dashCard i { font-size: 24px; color: #333; margin-bottom: 10px; }
      .dashCard h3 { margin: 0; font-size: 16px; color: #555; }
      .dashCard p { margin: 10px 0 0; font-size: 24px; font-weight: bold; color: #000; }
  </style>
</head>
<body>

<header>
  <nav id="header">
    <a href="seller-dashboard.php">
      <img src="ChatGPT Image May 12, 2026, 07_33_09 PM.png" class="logo" alt="PopCart Logo" width="150" height="auto"/>
    </a>
    <div id="hamburger" onclick="toggleMenu()"><span></span><span></span><span></span></div>
    
    <ul id="navMenu">
      <li><a href="seller-dashboard.php" style="font-weight: bold;">Dashboard</a></li>
      <li><a href="my-listings.php">Inventory</a></li>
      <li><a href="my-orders.php">Fulfillment</a></li>
      
      <li>
        <a href="switch-mode.php?to=buyer" style="background: #3b82f6; color: white; padding: 8px 15px; border-radius: 20px; font-weight: bold;">
          <i class="fa-solid fa-cart-shopping"></i> Switch to Buying
        </a>
      </li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>
</header>

<section id="myOrdersPage" style="padding-top: 40px;">
  <div id="ordersHeader">
    <h2>Seller Dashboard</h2>
    <p>Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>. Here is your store's overview.</p>
  </div>

  <div class="dashGrid">
    <div class="dashCard">
      <i class="fa-solid fa-wallet"></i>
      <h3>Cleared Revenue</h3>
      <p style="color: #28a745;">R<?= number_format($totalRevenue, 2) ?></p>
    </div>
    <div class="dashCard">
      <i class="fa-solid fa-box"></i>
      <h3>Pending Orders</h3>
      <p><?= (int)$pendingOrders ?></p>
    </div>
    <div class="dashCard">
      <i class="fa-solid fa-tags"></i>
      <h3>Active Listings</h3>
      <p><?= (int)$totalListings ?></p>
    </div>
  </div>

  <div style="background: #fff; padding: 20px; border: 1px solid #eee; border-radius: 8px;">
    <h3 style="margin-top: 0; margin-bottom: 15px;">Recent Activity</h3>
    <?php if(empty($recentOrders)): ?>
        <p style="color: #777;">No recent sales yet.</p>
    <?php else: ?>
        <table style="width: 100%; text-align: left; border-collapse: collapse;">
            <tr style="border-bottom: 2px solid #eee;">
                <th style="padding: 10px 0;">Item</th>
                <th style="padding: 10px 0;">Qty</th>
                <th style="padding: 10px 0;">Net Earnings</th> <th style="padding: 10px 0;">Status</th>
            </tr>
            <?php foreach($recentOrders as $ro): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px 0;"><?= htmlspecialchars($ro['product_name']) ?></td>
                <td style="padding: 10px 0;"><?= (int)$ro['quantity'] ?></td>
                
                <td style="padding: 10px 0;">R<?= number_format($ro['net_earnings'], 2) ?></td>
                
                <td style="padding: 10px 0;">
                    <span class="<?= htmlspecialchars($ro['item_status']) ?>" style="font-size: 12px; padding: 4px 8px; border-radius: 4px; background: #f0f0f0;">
                        <?= ucfirst($ro['item_status']) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <a href="my-orders.php" style="display: block; margin-top: 15px; text-decoration: none; color: #007bff;">View all orders →</a>
    <?php endif; ?>
  </div>

</section>

<footer>
  <div class="col">
    <img src="ChatGPT Image May 12, 2026, 07_33_09 PM.png" class="footerLogo">
    <h4>Contact</h4>
    <p><strong>Address:</strong> 562 Wellington Road, Street 32, San Francisco</p>
    <p><strong>Phone:</strong> 0834425678</p>
    <p><strong>Hours:</strong> 10:00 - 18:00, Mon-Sat</p>

      <div class="follow">
        <h4>Follow us</h4>
        <div class="icon">
          <i class="fab fa-facebook-f"></i>
          <i class="fab fa-twitter"></i>
          <i class="fab fa-instagram"></i>
          <i class="fab fa-pinterest-p"></i>
          <i class="fab fa-youtube"></i>
        </div>
      </div>
  </div>
  <div class="col">
    <h4>About</h4>
    <a href="#">About us</a>
    <a href="#">Privacy Policy</a>
    <a href="#">Terms & Conditions</a>
  </div>
  <div class="col">
    <h4>My Account</h4>
    <a href="seller-dashboard.php">Dashboard</a>
    <a href="my-listings.php">Inventory</a>
    <a href="my-orders.php">Fulfillment</a>
  </div>
  <p id="copyright">© 2026 PopCart. All rights reserved</p>
</footer>

<script src="index.js"></script>
</body>
</html>