<?php
session_start();
require 'connection.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

if (isset($_SESSION['app_mode']) && $_SESSION['app_mode'] === 'seller') {
  header("Location: switch-mode.php?to=buyer");
  exit;
}

$app_mode = $_SESSION['app_mode'] ?? 'buyer';
$user_id = $_SESSION['user_id'];

// Fetch cart items
$stmt = $pdo->prepare("
    SELECT c.*, p.product_name, p.price, p.image, p.seller_id 
    FROM cart c 
    JOIN products p ON c.product_id = p.product_id 
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

if (empty($items)) {
  header("Location: cart.php");
  exit;
}

$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));

// If returning from PayFast successfully, trigger the original success UI
$success = "";
$msg = "";
if (isset($_GET['payment']) && $_GET['payment'] === 'success') {
    $success = "Payment Complete! Sellers have been notified and are preparing your items.";
    $msg = "Sellers have been notified and are preparing your items.";
    
    // Optional: You can explicitly clear the items array here so the UI doesn't 
    // accidentally render cart items if the background ITN is running slightly behind.
    $items = []; 
} elseif (isset($_GET['payment']) && $_GET['payment'] === 'cancelled') {
    // Optional: Add a subtle error message if they canceled
    $success = "Payment was cancelled. Your items are still in your cart.";
    $msg = "Continue to cart";
}

// Handle Order Generation & PayFast Redirect
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // 1. Create a PENDING order. Do NOT deduct stock or clear the cart yet.
  $stmtOrder = $pdo->prepare("INSERT INTO orders (user_id, total_amount, order_status) VALUES (?, ?, 'pending_payment')");
  $stmtOrder->execute([$user_id, $total]);
  $new_order_id = $pdo->lastInsertId();

  foreach ($items as $item) {
    $stmtItems = $pdo->prepare("INSERT INTO order_items (order_id, product_id, seller_id, quantity, price) VALUES (?, ?, ?, ?, ?)");
    $stmtItems->execute([$new_order_id, $item['product_id'], $item['seller_id'], $item['quantity'], $item['price']]);
  }

  // 2. Setup PayFast Variables
  $payfast_url = 'https://sandbox.payfast.co.za/eng/process'; // Change to www.payfast.co.za for Live
  $merchant_id = '10050399'; // Replace with your Merchant ID
  $merchant_key = '01alm6ktrkvmj'; // Replace with your Merchant Key
  $return_url = 'http://popcart.rf.gd/checkout.php?payment=success';
  $cancel_url = 'http://popcart.rf.gd/checkout.php?payment=cancelled';
  $notify_url = 'http://popcart.rf.gd/payfast_itn.php';

  // Construct the PayFast HTML Form
  $htmlForm = '<form action="' . $payfast_url . '" method="post" id="payfast-form">';
  $htmlForm .= '<input type="hidden" name="merchant_id" value="' . $merchant_id . '">';
  $htmlForm .= '<input type="hidden" name="merchant_key" value="' . $merchant_key . '">';
  $htmlForm .= '<input type="hidden" name="return_url" value="' . $return_url . '">';
  $htmlForm .= '<input type="hidden" name="cancel_url" value="' . $cancel_url . '">';
  $htmlForm .= '<input type="hidden" name="notify_url" value="' . $notify_url . '">';
  $htmlForm .= '<input type="hidden" name="m_payment_id" value="' . $new_order_id . '">'; // Passes your Order ID
  $htmlForm .= '<input type="hidden" name="amount" value="' . number_format($total, 2, '.', '') . '">';
  $htmlForm .= '<input type="hidden" name="item_name" value="PopCart Order #' . $new_order_id . '">';
  $htmlForm .= '</form>';
  $htmlForm .= '<script>document.getElementById("payfast-form").submit();</script>';

  echo "Redirecting to secure payment gateway...";
  echo $htmlForm;
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PopCart - Checkout</title>
  <link rel="stylesheet" href="index.css" />
  <script src="https://kit.fontawesome.com/af69bda2f2.js" crossorigin="anonymous"></script>
</head>

<body>

  <header>
    <nav id="header">
      <a href="index.php">
        <img src="ChatGPT Image May 12, 2026, 07_33_09 PM.png" class="logo" alt="PopCart Logo" width="150"
          height="auto" />
      </a>
      <div id="hamburger" onclick="toggleMenu()"><span></span><span></span><span></span></div>
      <ul id="navMenu">
        <?php if (isset($_SESSION['user_id'])): ?>

          <?php if ($app_mode === 'seller'): ?>
            <li><a href="seller-dashboard.php">Dashboard</a></li>
            <li><a href="my-listings.php">Inventory</a></li>
            <li><a href="my-orders.php">Fulfillment</a></li>
            <li>
              <a href="switch-mode.php?to=buyer"
                style="background: #3b82f6; color: white; padding: 8px 15px; border-radius: 20px; font-weight: bold;">
                <i class="fa-solid fa-cart-shopping"></i> Switch to Buying
              </a>
            </li>
            <li><a href="logout.php">Logout</a></li>

          <?php else: ?>
            <li><a href="index.php">Home</a></li>
            <li><a href="listings.php">Browse Listings</a></li>
            <li><a href="track-orders.php">Track Orders</a></li>
            <li>
              <a href="switch-mode.php?to=seller"
                style="background: #10b981; color: white; padding: 8px 15px; border-radius: 20px; font-weight: bold;">
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

  <section id="checkoutPage">

    <?php if ($success): ?>
      <div id="successMsg" style="text-align: center; width: 100%; padding: 40px 20px;">
        <h2>✅ <?= htmlspecialchars($success) ?></h2>
        <p style="margin-bottom: 20px; color: #555;"><?= htmlspecialchars($success) ?></p>
        <a href="index.php" class="cartBtn" style="text-decoration: none; display: inline-block;">Continue Shopping</a>
      </div>
    <?php else: ?>

      <div id="checkoutLeft">

        <div class="checkoutBox">
          <h3>Delivery Details</h3>
          <form method="POST" action="checkout.php" id="checkoutForm">

            <div class="loginInput">
              <label>Full Name</label><br>
              <input type="text" name="fullname" placeholder="Enter your full name" required />
            </div>

            <div class="loginInput">
              <label>Email Address</label><br>
              <input type="email" name="email" placeholder="Enter your email" required />
            </div>

            <div class="loginInput">
              <label>Phone Number</label><br>
              <input type="text" name="phone" placeholder="Enter your phone number" required />
            </div>

            <div class="loginInput">
              <label>Street Address</label><br>
              <input type="text" name="address" placeholder="Enter your street address" required />
            </div>

            <div class="loginInput">
              <label>City</label><br>
              <input type="text" name="city" placeholder="Enter your city" required />
            </div>

            <div class="loginInput">
              <label>Postal Code</label><br>
              <input type="text" name="postal" placeholder="Enter your postal code" required />
            </div>

            <input type="hidden" name="payment_method" id="hiddenPaymentMethod" value="cod">

          </form>
        </div>

        <div class="checkoutBox">
          <h3>Payment Method</h3>
          <h2>Payfast is only payment method, Other forms of payment coming soon.</h2>

          <div class="paymentOption">
            <input type="radio" name="payment" value="eft" id="eft"
              onclick="document.getElementById('hiddenPaymentMethod').value='eft'" />
            <label for="eft">🏦 EFT Payment (PayFast)</label>
          </div>

        </div>

      </div>

      <div id="checkoutRight">
        <div class="checkoutBox">
          <h3>Order Summary</h3>

          <?php foreach ($items as $item): ?>
            <div class="checkoutItem">
              <?php if ($item['image']): ?>
                <img src="uploads/<?= htmlspecialchars($item['image']) ?>"
                  alt="<?= htmlspecialchars($item['product_name']) ?>" />
              <?php else: ?>
                <img src="Iphone 15.jpg" alt="product" />
              <?php endif; ?>
              <div>
                <p><?= htmlspecialchars($item['product_name']) ?></p>
                <p>Qty: <?= (int) $item['quantity'] ?></p>
              </div>
              <p>R<?= number_format($item['price'] * $item['quantity'], 2) ?></p>
            </div>
          <?php endforeach; ?>

          <div class="summaryItem">
            <h3>Subtotal</h3>
            <p>R<?= number_format($total, 2) ?></p>
          </div>

          <div class="summaryItem">
            <h3>Shipping</h3>
            <p>Free</p>
          </div>

          <div class="summaryItem">
            <h3>Total</h3>
            <p class="totalPrice">R<?= number_format($total, 2) ?></p>
          </div>

          <button type="submit" form="checkoutForm" class="cartBtn">
            Place Order
          </button>

        </div>
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
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="track-orders.php">Track Orders</a>
        <a href="cart.php">View Cart</a>
      <?php else: ?>
        <a href="login.php">Sign In</a>
        <a href="register.php">Register</a>
      <?php endif; ?>
    </div>
    <p id="copyright">© 2026 PopCart. All rights reserved</p>
  </footer>

  <script src="index.js"></script>
</body>

</html>