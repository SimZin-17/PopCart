<?php
session_start();
require 'connection.php';

// Security & Mode Check
if(!isset($_SESSION['user_id']) || (isset($_SESSION['app_mode']) && $_SESSION['app_mode'] !== 'seller')) {
    header("Location: switch-mode.php?to=seller");
    exit;
}

$app_mode = $_SESSION['app_mode'];

if(isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ? AND seller_id = ?");
    $stmt->execute([(int)$_GET['delete'], $_SESSION['user_id']]);
    header("Location: my-listings.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$listings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PopCart - My Listings</title>
  <link rel="stylesheet" href="index.css"/>
  <script src="https://kit.fontawesome.com/af69bda2f2.js" crossorigin="anonymous"></script>
</head>
<body>

<header>
    <nav id="header">
      <a href="seller-dashboard.php"><img src="ChatGPT Image May 12, 2026, 07_33_09 PM.png" class="logo" width="150"/></a>
      <div id="hamburger" onclick="toggleMenu()"><span></span><span></span><span></span></div>
      <ul id="navMenu">
        <li><a href="seller-dashboard.php">Dashboard</a></li>
        <li><a href="my-listings.php" style="font-weight: bold;">Inventory</a></li>
        <li><a href="my-orders.php">Fulfillment</a></li>
        <li><a href="switch-mode.php?to=buyer" style="background: #3b82f6; color: white; padding: 8px 15px; border-radius: 20px;"><i class="fa-solid fa-cart-shopping"></i> Switch to Buying</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
    </nav>
  </header>

  <section id="listingsPage" style="padding-top: 40px;">
    <div id="listingsRight" style="width: 100%;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
          <h2>My Inventory</h2>
          <a href="add-listing.php" class="cartBtn"><i class="fa-solid fa-plus"></i> Add New Listing</a>
      </div>

      <?php if(empty($listings)): ?>
        <p>No listings found.</p>
      <?php else: ?>
        <div id="productsGrid">
          <?php foreach($listings as $l): ?>
            <div class="products" style="position: relative;">
              <img src="uploads/<?= htmlspecialchars($l['image']) ?>" alt="<?= htmlspecialchars($l['product_name']) ?>"/>
              <span style="position: absolute; top: 10px; right: 10px; background: <?= $l['stock_quantity'] > 0 ? '#10b981' : '#dc3545' ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                Stock: <?= (int)$l['stock_quantity'] ?>
              </span>
              <h3><?= htmlspecialchars($l['product_name']) ?></h3>
              <p>R<?= number_format($l['price'], 2) ?></p>
              <div style="margin-top:auto; display:flex; gap:5px;">
                <a href="product.php?id=<?= $l['product_id'] ?>" style="flex:1;"><button style="width:100%;">View</button></a>
                <a href="edit-listing.php?id=<?= $l['product_id'] ?>" style="flex:1;"><button style="width:100%; background:#3b82f6;">Edit</button></a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
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