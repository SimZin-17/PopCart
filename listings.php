<?php
session_start();
require 'connection.php';

// Navigation State Variable
$app_mode = $_SESSION['app_mode'] ?? 'buyer';

// Build query with optional filters
$where  = ["1=1"];
$params = [];

// NEW: Exclude the logged-in user's own products
if (!empty($_SESSION['user_id'])) {
    $where[]  = "p.seller_id != ?";
    $params[] = $_SESSION['user_id'];
}

if (!empty($_GET['category'])) {
    $where[]  = "category = ?";
    $params[] = $_GET['category'];
}

if (!empty($_GET['location'])) {
    $where[]  = "location LIKE ?";
    $params[] = "%" . $_GET['location'] . "%";
}

if (!empty($_GET['max_price']) && is_numeric($_GET['max_price'])) {
    $where[]  = "price <= ?";
    $params[] = (float)$_GET['max_price'];
}

if (!empty($_GET['search'])) {
    // Split the query into words so multi-word / partial searches work
    $words = preg_split('/\s+/', trim($_GET['search']));
    foreach ($words as $word) {
        if ($word === '') continue;
        
        // Restored: Now safely searches across all 4 columns
        $where[]  = "(product_name LIKE ? OR category LIKE ? OR location LIKE ? OR description LIKE ?)";
        $term     = "%" . $word . "%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }
}

// Join users table to display the seller's name
$sql      = "SELECT p.*, u.user_name AS seller_name FROM products p JOIN users u ON p.seller_id = u.user_id WHERE " . implode(" AND ", $where) . " ORDER BY p.created_at DESC";
$stmt     = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PopCart - Listings</title>
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
            <li><a href="listings.php" style="font-weight: bold;">Browse Listings</a></li>
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
          <li><a href="listings.php" style="font-weight: bold;">Browse Listings</a></li>
          <li><a href="login.php" id="loginBtn">Login</a></li>
          <li><a href="register.php" id="registerBtn">Register</a></li>
          <li><a href="cart.php"><i class="fa-solid fa-cart-arrow-down"></i></a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <section id="listingsPage">

    <div id="filterBox">
      <h2>Filter</h2>
      <form method="GET" action="listings.php">

        <div class="filterinput">
          <label>Category</label><br>
          <select name="category" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
            <option value="">All Categories</option>
            <?php foreach (['electronics','fashion','vehicles','furniture','books','other'] as $cat): ?>
              <option value="<?= $cat ?>" <?= (($_GET['category'] ?? '') === $cat) ? 'selected' : '' ?>>
                <?= ucfirst($cat) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filterinput">
          <label>Location</label><br>
          <select name="location" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
            <option value="">All Locations</option>
            <?php foreach (['Johannesburg','Cape Town','Durban','Pretoria'] as $loc): ?>
              <option value="<?= $loc ?>" <?= (($_GET['location'] ?? '') === $loc) ? 'selected' : '' ?>>
                <?= $loc ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filterinput">
          <label>Max Price (R)</label><br>
          <input type="number" name="max_price" value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
        </div>

        <div class="filterinput">
          <button type="submit" style="width: 100%; padding: 10px; background: #3b82f6; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Apply Filter</button>
        </div>

      </form>
    </div>

   <div id="listingsRight">
      <h2>Browse Listings</h2>
      <div id="productsGrid">
        <?php foreach ($products as $p): ?>
          <div class="products">
            <img src="uploads/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>"/>
            <h3><?= htmlspecialchars($p['product_name']) ?></h3>
            <p>R<?= number_format($p['price'], 2) ?></p>
            <p class="location"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($p['location']) ?></p>
            <a href="product.php?id=<?= $p['product_id'] ?>">
              <button style="width:100%;">View Product</button>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
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
</body>
</html>