<?php
session_start();
require 'connection.php';

// 1. Security & Mode Enforcement
if(!isset($_SESSION['user_id']) || (isset($_SESSION['app_mode']) && $_SESSION['app_mode'] !== 'seller')) {
    header("Location: switch-mode.php?to=seller");
    exit;
}

$app_mode = $_SESSION['app_mode'];
$seller_id = $_SESSION['user_id'];

// Accept order item
if(isset($_GET['accept'])) {
    $tracking = 'TRK' . strtoupper(uniqid());
    // Update the specific ITEM, not the whole master order
    $stmt = $pdo->prepare("UPDATE order_items SET item_status = 'accepted', tracking_number = ? WHERE order_item_id = ? AND seller_id = ?");
    $stmt->execute([$tracking, (int)$_GET['accept'], $seller_id]);
    header("Location: my-orders.php");
    exit;
}

// Reject order item
if(isset($_GET['reject'])) {
    // 1. Get quantity to restore
    $stmtGet = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_item_id = ? AND seller_id = ? AND item_status = 'pending'");
    $stmtGet->execute([(int)$_GET['reject'], $seller_id]);
    $item = $stmtGet->fetch();

    if ($item) {
        // 2. Restore stock
        $stmtRestore = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?");
        $stmtRestore->execute([$item['quantity'], $item['product_id']]);

        // 3. Update status
        $stmtStatus = $pdo->prepare("UPDATE order_items SET item_status = 'rejected' WHERE order_item_id = ?");
        $stmtStatus->execute([(int)$_GET['reject']]);
    }
    header("Location: my-orders.php");
    exit;
}

// Send message to buyer regarding a specific item
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $stmt = $pdo->prepare("UPDATE order_items SET seller_message = ? WHERE order_item_id = ? AND seller_id = ?");
    $stmt->execute([$_POST['message'], (int)$_POST['order_item_id'], $seller_id]);
    header("Location: my-orders.php");
    exit;
}

// Fetch specific order items for this seller's listings
// NEW: Added oi.buyer_message to the SELECT statement
$stmt = $pdo->prepare("
    SELECT 
        o.order_id, 
        o.created_at,
        oi.order_item_id,
        oi.quantity, 
        oi.item_status,
        oi.tracking_number,
        oi.seller_message,
        oi.buyer_message,
        (oi.quantity * oi.price) AS seller_earnings,
        p.product_name, 
        p.image, 
        u.user_name AS buyer_name, 
        u.email AS buyer_email
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    JOIN products p ON oi.product_id = p.product_id
    JOIN users u ON o.user_id = u.user_id
    WHERE oi.seller_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$seller_id]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PopCart - My Orders</title>
  <link rel="stylesheet" href="index.css"/>
  <script src="https://kit.fontawesome.com/af69bda2f2.js" crossorigin="anonymous"></script>
</head>
<body>

<header>
  <nav id="header">
    <a href="seller-dashboard.php">
      <img src="ChatGPT Image May 12, 2026, 07_33_09 PM.png" class="logo" alt="PopCart Logo" width="150" height="auto"/>
    </a>
    <div id="hamburger" onclick="toggleMenu()">
      <span></span><span></span><span></span>
    </div>
    <ul id="navMenu">
        <?php if(isset($_SESSION['user_id'])): ?>

          <?php if($app_mode === 'seller'): ?>
            <li><a href="seller-dashboard.php">Dashboard</a></li>
            <li><a href="my-listings.php">Inventory</a></li>
            <li><a href="my-orders.php" style="font-weight: bold;">Fulfillment</a></li>
            <li>
              <a href="switch-mode.php?to=buyer" style="background: #3b82f6; color: white; padding: 8px 15px; border-radius: 20px; font-weight: bold;">
                <i class="fa-solid fa-cart-shopping"></i> Switch to Buying
              </a>
            </li>
            <li><a href="logout.php">Logout</a></li>

          <?php else: ?>
            <li><a href="index.php">Home</a></li>
            <li><a href="listings.php">Browse Listings</a></li>
            <li><a href="track-orders.php">Track Orders</a></li>
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
    <h2>Orders on My Listings</h2>
    <p>Manage orders, track shipments and communicate with buyers</p>
  </div>

  <?php if(empty($orders)): ?>
    <div id="emptyOrders" style="text-align: center; padding: 50px 20px; background: #fdfdfd; border: 1px dashed #ccc; border-radius: 8px;">
      <i class="fa-solid fa-box-open" style="font-size: 40px; color: #ccc; margin-bottom: 15px;"></i>
      <p style="color:#555; margin-bottom: 15px; font-size: 18px;">No orders yet on your listings.</p>
      <a href="add-listing.php" style="color: #10b981; font-weight: bold; text-decoration: underline;">Add more products</a>
    </div>
  <?php else: ?>

    <div id="ordersList">
      <?php foreach($orders as $order): ?>
        <div class="orderCard" style="border: 1px solid #eee; margin-bottom: 20px; border-radius: 8px; overflow: hidden;">

          <div class="orderCardHeader" style="background: #f9f9f9; padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
            <div>
              <span class="orderID" style="font-weight: bold;">Order #<?= (int)$order['order_id'] ?> (Item Ref: <?= (int)$order['order_item_id'] ?>)</span><br>
              <span class="orderDate" style="font-size: 12px; color: #777;"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></span>
            </div>
            <span class="orderStatus <?= $order['item_status'] ?>" style="padding: 5px 10px; border-radius: 4px; font-size: 14px; background: #e2e8f0;">
              <?= ucfirst($order['item_status']) ?>
            </span>
          </div>

          <div class="orderCardBody" style="padding: 15px;">

            <div class="orderProduct" style="display: flex; gap: 15px; margin-bottom: 15px;">
              <img src="uploads/<?= htmlspecialchars($order['image']) ?>"
                   alt="<?= htmlspecialchars($order['product_name']) ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;"/>
              <div>
                <h3 style="margin: 0 0 5px 0;"><?= htmlspecialchars($order['product_name']) ?> (Qty: <?= (int)$order['quantity'] ?>)</h3>
                <p class="orderPrice" style="color: #28a745; font-weight: bold; margin: 0;">Earnings: R<?= number_format($order['seller_earnings'], 2) ?></p>
              </div>
            </div>

            <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 15px;">
                <div class="orderInfo" style="flex: 1; min-width: 200px;">
                  <h4 style="margin: 0 0 5px 0; font-size: 14px;"><i class="fa-solid fa-user"></i> Buyer Details</h4>
                  <p style="margin: 0; color: #555;"><?= htmlspecialchars($order['buyer_name']) ?></p>
                  <p style="margin: 0; color: #555;"><a href="mailto:<?= htmlspecialchars($order['buyer_email']) ?>" style="color: #3b82f6; text-decoration: none;"><?= htmlspecialchars($order['buyer_email']) ?></a></p>
                </div>

                <div class="orderInfo" style="flex: 1; min-width: 200px;">
                  <h4 style="margin: 0 0 5px 0; font-size: 14px;"><i class="fa-solid fa-truck"></i> Tracking</h4>
                  <?php if($order['tracking_number']): ?>
                    <p class="trackingNumber" style="margin: 0; color: #007bff; font-weight: bold;"><?= htmlspecialchars($order['tracking_number']) ?></p>
                  <?php else: ?>
                    <p style="margin: 0; color:#999;">No tracking yet</p>
                  <?php endif; ?>
                </div>
            </div>

            <div style="background: #fdfdfd; padding: 15px; border-radius: 8px; border: 1px solid #eee; margin-bottom: 15px;">
              <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 14px;"><i class="fa-solid fa-comments"></i> Messages</h4>
              
              <?php if($order['seller_message']): ?>
                <p style="margin-bottom: 10px; font-size: 14px; color: #333;"><strong>You:</strong> <?= htmlspecialchars($order['seller_message']) ?></p>
              <?php else: ?>
                <p style="margin-bottom: 10px; font-size: 14px; color: #999;"><em>You haven't sent a message yet.</em></p>
              <?php endif; ?>

              <?php if($order['buyer_message']): ?>
                <p style="font-size: 14px; color: #3b82f6; margin: 0;"><strong>Buyer:</strong> <?= htmlspecialchars($order['buyer_message']) ?></p>
              <?php endif; ?>
            </div>

            <div class="orderActions" style="display: flex; gap: 10px;">
              <?php if($order['item_status'] === 'pending'): ?>
                <a href="my-orders.php?accept=<?= $order['order_item_id'] ?>"
                   onclick="return confirm('Accept this order?')">
                  <button class="acceptBtn" style="background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;"><i class="fa-solid fa-check"></i> Accept & Ship</button>
                </a>
                <a href="my-orders.php?reject=<?= $order['order_item_id'] ?>"
                   onclick="return confirm('Reject this order?')">
                  <button class="rejectBtn" style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;"><i class="fa-solid fa-x"></i> Reject</button>
                </a>
              <?php endif; ?>

              <?php if($order['item_status'] !== 'completed' && $order['item_status'] !== 'rejected'): ?>
                  <button class="messageBtn" onclick="toggleMessage(<?= $order['order_item_id'] ?>)" style="background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                    <i class="fa-solid fa-envelope"></i> Message Buyer
                  </button>
              <?php endif; ?>
            </div>

            <div id="msgForm<?= $order['order_item_id'] ?>" class="msgForm" style="display:none; margin-top: 15px;">
              <form method="POST" action="my-orders.php">
                <input type="hidden" name="order_item_id" value="<?= $order['order_item_id'] ?>"/>
                <textarea name="message" placeholder="Write a message or update to the buyer regarding this item..."
                          rows="3" style="width:100%; padding:10px; border-radius:8px;
                          border:1px solid #dde3f0; margin-bottom:10px; font-family: inherit; box-sizing: border-box;" required></textarea>
                <button type="submit" name="send_message" class="acceptBtn" style="background: #3b82f6; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                  <i class="fa-solid fa-paper-plane"></i> Send Message
                </button>
              </form>
            </div>

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
    <a href="seller-dashboard.php">Dashboard</a>
    <a href="my-listings.php">Inventory</a>
    <a href="my-orders.php">Fulfillment</a>
  </div>
  <p id="copyright">© 2026 PopCart. All rights reserved</p>
</footer>

<script src="index.js"></script>
<script>
function toggleMessage(id) {
  const form = document.getElementById('msgForm' + id);
  form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
</script>
</body>
</html>