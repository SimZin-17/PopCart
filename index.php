<?php
session_start();
require 'connection.php';

// Initialize the default app mode for logged-in users if it hasn't been set yet
if (isset($_SESSION['user_id']) && !isset($_SESSION['app_mode'])) {
    $_SESSION['app_mode'] = 'buyer'; 
}

$app_mode = $_SESSION['app_mode'] ?? 'buyer';

// Fetch 4 latest products
$stmt = $pdo->prepare("SELECT * FROM products ORDER BY created_at DESC LIMIT 4");
$stmt->execute();
$featuredProducts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PopCart - Home</title>
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

  <section id="searchSection">
    <h2>Search Anything</h2>
    <form id="searchForm" action="listings.php" method="GET" autocomplete="off">
      <div id="searchBar">
        <input type="text" id="searchInput" name="search" placeholder="Search for anything..."/>
        <button type="submit" id="searchBtn">
          <i class="fa-solid fa-magnifying-glass"></i> Search
        </button>
      </div>
      <div id="searchResults"></div>
    </form>
  </section>

  <section id="Hero">
    <h4>Buy and Sell Anything!!</h4>
    <h2>Super Value deals</h2>
    <h1>On all products</h1>
    <p>South Africa's fastest growing C2C platform</p><br>
    <div id="HeroButtons">
      <a href="listings.php">
        <button>Browse Listings</button>
      </a>
      <a href="switch-mode.php?to=seller">
        <button>Start Selling</button>
      </a>
    </div>
  </section>

  <section id="categories">
    <h2>Browse by Category</h2>
    <div class="catbox">
      <img src="Electronics Category.jpg">
      <p>Electronics</p>
    </div>
    <div class="catbox">
      <img src="Fashion Category.jpg">
      <p>Fashion</p>
    </div>
    <div class="catbox">
      <img src="Vehicles Category.jpg">
      <p>Vehicles</p>
    </div>
    <div class="catbox">
      <img src="Furniture Category.jpg">
      <p>Furniture</p>
    </div>
    <div class="catbox">
      <img src="Books Category.jpg">
      <p>Books</p>
    </div>
    <div class="catbox">
      <i class="fa-solid fa-tag" style="font-size: 50px; color: #3b82f6;"></i>
      <p>Other</p>
    </div>
  </section>

  <section id="featuredproducts">
    <br>
    <h2>Featured Products</h2>
    <div id="productgrid">
      <?php foreach($featuredProducts as $product): ?>
        <div class="products">
          <img src="uploads/<?= htmlspecialchars($product['image']) ?>"
               alt="<?= htmlspecialchars($product['product_name']) ?>"/>
          <h3><?= htmlspecialchars($product['product_name']) ?></h3>
          <p>R<?= htmlspecialchars($product['price']) ?></p>
          <p class="location"><?= htmlspecialchars($product['category']) ?></p>
          <a href="product.php?id=<?= $product['product_id'] ?>">
            <button>View Product</button>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section id="howItWorks">
    <h2>How It Works</h2>
    <div id="stepsGrid">
      <div class="step">
        <div class="stepNumber">1</div>
        <h3>Register</h3>
        <p>Create your free account in minutes</p>
      </div>
      <div class="step">
        <div class="stepNumber">2</div>
        <h3>List It</h3>
        <p>Post your item with photos and a price</p>
      </div>
      <div class="step">
        <div class="stepNumber">3</div>
        <h3>Sell It</h3>
        <p>Buyer contacts you directly and you deal</p>
      </div>
    </div>
  </section>

  <footer>
    <div class="col">
      <img src="ChatGPT Image May 12, 2026, 07_33_09 PM.png" class="footerLogo">
      <h4>Contact</h4>
      <p><strong>Address:</strong> 562 Wellington Road, Street 32 San Francisco</p>
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
      <a href="#">Delivery Information</a>
      <a href="#">Privacy Policy</a>
      <a href="#">Terms & Conditions</a>
      <a href="#">Contact us</a>
    </div>
    <div class="col">
      <h4>My Account</h4>
      <?php if(isset($_SESSION['user_id'])): ?>
        <a href="track-orders.php">Track My Orders</a>
        <a href="cart.php">View Cart</a>
        <a href="switch-mode.php?to=seller">Seller Dashboard</a>
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