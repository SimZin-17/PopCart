<?php
session_start();
require 'connection.php';

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

// 2. Mode Enforcement: Cart is a buyer feature. 
// If they are in seller mode, bounce them through the switch router to safely convert their session.
if (isset($_SESSION['app_mode']) && $_SESSION['app_mode'] === 'seller') {
  header("Location: switch-mode.php?to=buyer");
  exit;
}

$app_mode = $_SESSION['app_mode'] ?? 'buyer';
$user_id = $_SESSION['user_id'];

// Add item to cart
if (isset($_GET['add'])) {
  $product_id = (int) $_GET['add'];

  // 1. Get current stock and current cart quantity
  $stmt = $pdo->prepare("
        SELECT p.stock_quantity, (SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?) as cart_qty 
        FROM products p WHERE p.product_id = ?
    ");
  $stmt->execute([$user_id, $product_id, $product_id]);
  $data = $stmt->fetch();

  if ($data) {
    $stock = $data['stock_quantity'];
    $in_cart = $data['cart_qty'] ?? 0;

    // 2. Only add if there is enough stock
    if ($in_cart < $stock) {
      if ($in_cart > 0) {
        $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?")->execute([$user_id, $product_id]);
      } else {
        $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)")->execute([$user_id, $product_id]);
      }
    } else {
      // Optional: You could pass a 'full' flag back to the UI to show an error message
      $_SESSION['error'] = "Sorry, there is no more stock available for this item.";
    }
  }
  header("Location: cart.php");
  exit;
}

// Remove item
if (isset($_GET['remove'])) {
  $pdo->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?")->execute([(int) $_GET['remove'], $user_id]);
  header("Location: cart.php");
  exit;
}

// Update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id'])) {
  $qty = max(1, (int) $_POST['quantity']);
  $cart_id = (int) $_POST['cart_id'];

  // Check current stock limit
  $stmt = $pdo->prepare("SELECT p.stock_quantity FROM products p JOIN cart c ON p.product_id = c.product_id WHERE c.cart_id = ?");
  $stmt->execute([$cart_id]);
  $product = $stmt->fetch();

  if ($product && $qty <= $product['stock_quantity']) {
    $pdo->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?")->execute([$qty, $cart_id, $user_id]);
  } else {
    // You could add an error variable here to notify the user if stock is insufficient
  }
  header("Location: cart.php");
  exit;
}

// Fetch cart items
$stmt = $pdo->prepare(
  "SELECT c.cart_id AS cart_id, c.quantity, p.product_name, p.price, p.image, p.product_id AS product_id
     FROM cart c JOIN products p ON c.product_id = p.product_id
     WHERE c.user_id = ?"
);
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();
$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PopCart - Cart</title>
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
      <div id="hamburger" onclick="toggleMenu()">
        <span></span>
        <span></span>
        <span></span>
      </div>
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

  <section id="cartPage">

    <div id="cartBox">
      <h2>Your Cart</h2>

      <?php if (empty($items)): ?>
        <p>Your cart is empty. <a href="listings.php">Browse listings</a></p>
      <?php else: ?>
        <?php foreach ($items as $item): ?>
          <div class="cartCard">
            <?php if ($item['image']): ?>
              <img src="uploads/<?= htmlspecialchars($item['image']) ?>"
                alt="<?= htmlspecialchars($item['product_name']) ?>" />
            <?php else: ?>
              <img src="Iphone 15.jpg" alt="product" />
            <?php endif; ?>

            <div class="cartCardDetails">
              <h3><?= htmlspecialchars($item['product_name']) ?></h3>
              <p class="cartPrice">R<?= number_format($item['price'], 2) ?></p>

              <div class="cartCardBottom">
                <form method="POST" action="cart.php" style="display:inline-flex; align-items:center; gap:6px;">
                  <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                  <label>Qty:</label>

                  <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" style="width:55px;"
                    onchange="this.form.submit()">
                </form>

                <a href="cart.php?remove=<?= $item['cart_id'] ?>">
                  <button type="button" class="removeBtn">Remove</button>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div id="OrderSumBox">
      <h2>Order Summary</h2>

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

      <a href="checkout.php" class="cartBtn">Proceed to Checkout</a>
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