<?php
session_start();
require 'connection.php';

// 1. Security Check
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Mode Enforcement: Tracking is a buyer feature.
if (isset($_SESSION['app_mode']) && $_SESSION['app_mode'] === 'seller') {
    header("Location: switch-mode.php?to=buyer");
    exit;
}

$app_mode = $_SESSION['app_mode'] ?? 'buyer';
$user_id = $_SESSION['user_id'];
$success_msg = "";

// ACTION 1: Buyer confirms receipt of the item
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_receipt'])) {
    $stmt = $pdo->prepare("
        UPDATE order_items oi 
        JOIN orders o ON oi.order_id = o.order_id 
        SET oi.item_status = 'completed' 
        WHERE oi.order_item_id = ? AND o.user_id = ?
    ");
    $stmt->execute([(int)$_POST['order_item_id'], $user_id]);
    $success_msg = "Item marked as completed. Thank you!";
}

// ACTION 2: Buyer sends a message to the seller
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_buyer_message'])) {
    $stmt = $pdo->prepare("
        UPDATE order_items oi 
        JOIN orders o ON oi.order_id = o.order_id 
        SET oi.buyer_message = ? 
        WHERE oi.order_item_id = ? AND o.user_id = ?
    ");
    $stmt->execute([trim($_POST['message']), (int)$_POST['order_item_id'], $user_id]);
    $success_msg = "Message sent to seller!";
}

// FETCH DATA
$stmt = $pdo->prepare("
    SELECT 
        o.order_id, 
        o.created_at,
        oi.order_item_id, 
        oi.quantity, 
        oi.price, 
        oi.item_status, 
        oi.tracking_number, 
        oi.seller_message, 
        oi.buyer_message,
        p.product_name, 
        p.image, 
        s.user_name AS seller_name
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    JOIN users s ON oi.seller_id = s.user_id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$raw_items = $stmt->fetchAll();

// Group items by Master Order ID
$orders_grouped = [];
foreach($raw_items as $item) {
    $orders_grouped[$item['order_id']][] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PopCart - Track Orders</title>
  <link rel="stylesheet" href="index.css"/>
  <script src="https://kit.fontawesome.com/af69bda2f2.js" crossorigin="anonymous"></script>
</head>
<body>

<header>
    <nav id="header">
      <a href="index.php">
        <img src="ChatGPT Image May 12, 2026, 07_33_09 PM.png" class="logo" alt="PopCart Logo" width="150" height="auto"/>
      </a>
      <div id="hamburger" onclick="toggleMenu()">
        <span></span>
        <span></span>
        <span></span>
      </div>
      <ul id="navMenu">
        <?php if(isset($_SESSION['user_id'])): ?>

          <?php if($app_mode === 'seller'): ?>
            <li><a href="seller-dashboard.php">Dashboard</a></li>
            <li><a href="my-listings.php">Inventory</a></li>
            <li><a href="my-orders.php">Fulfillment</a></li>
            <li>
              <a href="switch-mode.php?to=buyer" style="background: #3b82f6; color: white; padding: 8px 15px; border-radius: 20px; font-weight: bold;">
                <i class="fa-solid fa-cart-shopping"></i> Switch to Buying
              </a>
            </li>
            <li><a href="logout.php">Logout</a></li>

          <?php else: ?>
            <li><a href="index.php">Home</a></li>
            <li><a href="listings.php">Browse Listings</a></li>
            <li><a href="track-orders.php" style="font-weight: bold;">Track Orders</a></li>
            <li>
              <a href="switch-mode.php?to=seller" style="background: #10b981; color: white; padding: 8px 15px; border-radius: 20px; font-weight: bold;">
                <i class="fa-solid fa-store"></i> Switch to Selling
              </a>
            </li>
            <li><a href="logout.php">Logout (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a></li>
            <li><a href="cart.php"><i class="fa-solid fa-cart-arrow-down"></i></a></li>
          <?php endif; ?>

        <?php else: ?>
          <li><a href="index.php">Home</a></li>
          <li><a href="listings.php">Browse Listings</a></li>
          <li><a href="login.php" id="loginBtn">Login</a></li>
          <li><a href="register.php" id="registerBtn">Register</a></li>
          <li><a href="cart.php"><i class="fa-solid fa-cart-arrow-down"></i></a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

<section id="myOrdersPage">

  <div id="ordersHeader">
    <h2>Track My Orders</h2>
    <p>View your purchase history, track shipments, and confirm deliveries.</p>
  </div>

  <?php if($success_msg): ?>
    <div style="background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 8px; text-align: center;">
      <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success_msg) ?>
    </div>
  <?php endif; ?>

  <?php if(empty($orders_grouped)): ?>
    <div id="emptyOrders">
      <i class="fa-solid fa-bag-shopping"></i>
      <p>You haven't placed any orders yet.</p>
      <a href="listings.php">Start Shopping</a>
    </div>
  <?php else: ?>

    <div id="ordersList">
      <?php foreach($orders_grouped as $order_id => $items): ?>
        
        <div style="border: 2px solid #333; border-radius: 10px; margin-bottom: 30px; overflow: hidden;">
          <div style="background: #333; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;">Order #<?= (int)$order_id ?></h3>
            <span style="font-size: 14px;"><?= date('d M Y', strtotime($items[0]['created_at'])) ?></span>
          </div>

          <div style="padding: 20px;">
            <?php foreach($items as $item): ?>
              <div class="orderCard" style="box-shadow: none; border: 1px solid #eee; margin-bottom: 15px;">
                
                <div class="orderCardHeader">
                  <span class="orderID">Item from: <strong><?= htmlspecialchars($item['seller_name']) ?></strong></span>
                  <span class="orderStatus <?= $item['item_status'] ?>">
                    <?= ucfirst($item['item_status']) ?>
                  </span>
                </div>

                <div class="orderCardBody">
                  <div class="orderProduct">
                    <img src="uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>"/>
                    <div>
                      <h3><?= htmlspecialchars($item['product_name']) ?></h3>
                      <p>Qty: <?= (int)$item['quantity'] ?> | R<?= number_format($item['price'], 2) ?> each</p>
                    </div>
                  </div>

                  <div class="orderInfo">
                    <h4><i class="fa-solid fa-truck-fast"></i> Tracker</h4>
                    <?php if($item['tracking_number']): ?>
                      <p class="trackingNumber" style="color: #007bff; font-weight: bold;"><?= htmlspecialchars($item['tracking_number']) ?></p>
                    <?php else: ?>
                      <p style="color:#999;">Awaiting shipment</p>
                    <?php endif; ?>
                  </div>
                </div>

                <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin: 15px 0;">
                  <h4 style="margin-bottom: 10px; font-size: 14px;"><i class="fa-solid fa-comments"></i> Messages</h4>
                  
                  <?php if($item['seller_message']): ?>
                    <p style="margin-bottom: 10px; font-size: 14px;"><strong>Seller:</strong> <?= htmlspecialchars($item['seller_message']) ?></p>
                  <?php else: ?>
                    <p style="margin-bottom: 10px; font-size: 14px; color: #999;"><em>No messages from seller yet.</em></p>
                  <?php endif; ?>

                  <?php if($item['buyer_message']): ?>
                    <p style="font-size: 14px; color: #555;"><strong>You:</strong> <?= htmlspecialchars($item['buyer_message']) ?></p>
                  <?php endif; ?>
                </div>

                <div class="orderActions">
                  <?php if($item['item_status'] === 'accepted'): ?>
                    <form method="POST" action="track-orders.php" style="display:inline;" onsubmit="return confirm('Have you received this item in good condition?');">
                      <input type="hidden" name="order_item_id" value="<?= $item['order_item_id'] ?>">
                      <button type="submit" name="confirm_receipt" class="acceptBtn" style="background: #28a745;">
                        <i class="fa-solid fa-box-check"></i> Confirm Receipt
                      </button>
                    </form>
                  <?php endif; ?>

                  <?php if($item['item_status'] !== 'completed' && $item['item_status'] !== 'rejected'): ?>
                    <button class="messageBtn" onclick="toggleBuyerMessage(<?= $item['order_item_id'] ?>)">
                      <i class="fa-solid fa-reply"></i> Reply to Seller
                    </button>
                  <?php endif; ?>
                </div>

                <div id="replyForm<?= $item['order_item_id'] ?>" class="msgForm" style="display:none; margin-top: 15px;">
                  <form method="POST" action="track-orders.php">
                    <input type="hidden" name="order_item_id" value="<?= $item['order_item_id'] ?>"/>
                    <textarea name="message" placeholder="Write your message to the seller here..." required
                              rows="2" style="width:100%; padding:10px; border-radius:8px; border:1px solid #dde3f0; margin-bottom:10px;"></textarea>
                    <button type="submit" name="send_buyer_message" class="acceptBtn" style="background: #333;">
                      Send Message
                    </button>
                  </form>
                </div>

              </div>
            <?php endforeach; ?>
          </div>
        </div>

      <?php endforeach; ?>
    </div>

  <?php endif; ?>

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
    <?php if(isset($_SESSION['user_id'])): ?>
      <?php if($app_mode === 'seller'): ?>
        <a href="seller-dashboard.php">Dashboard</a>
        <a href="my-listings.php">Inventory</a>
      <?php else: ?>
        <a href="track-orders.php">Track Orders</a>
        <a href="cart.php">View Cart</a>
      <?php endif; ?>
    <?php else: ?>
      <a href="login.php">Sign In</a>
      <a href="register.php">Register</a>
    <?php endif; ?>
  </div>
  <p id="copyright">© 2026 PopCart. All rights reserved</p>
</footer>

<script src="index.js"></script>
<script>
function toggleBuyerMessage(id) {
  const form = document.getElementById('replyForm' + id);
  form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
</script>
</body>
</html>