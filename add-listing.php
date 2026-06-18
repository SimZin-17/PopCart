<?php
session_start();
require 'connection.php';

// Security & Mode Check
if (!isset($_SESSION['user_id']) || (isset($_SESSION['app_mode']) && $_SESSION['app_mode'] !== 'seller')) {
    header("Location: switch-mode.php?to=seller");
    exit;
}

$seller_id = $_SESSION['user_id'];
$error = "";
$success = "";
$allowed_categories = ['electronics', 'fashion', 'vehicles', 'furniture', 'books', 'other'];
$allowed_locations = ['Johannesburg', 'Cape Town', 'Durban', 'Pretoria'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name']);
    $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    // NEW: Capture stock quantity
    $stock_quantity = max(1, (int)$_POST['stock_quantity']); 
    $category = $_POST['category'];
    $location = $_POST['location'];
    $description = trim($_POST['description']);
    $image = "";

    if (empty($product_name) || empty($price) || empty($category) || empty($location) || empty($description)) {
        $error = "Please fill in all required fields.";
    } elseif ($price <= 0) {
        $error = "Price must be greater than zero.";
    } elseif (!in_array($category, $allowed_categories)) {
        $error = "Invalid category selected.";
    } elseif (!in_array($location, $allowed_locations)) {
        $error = "Invalid location selected.";
    } else {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = 'prod_' . uniqid() . '.' . $file_extension;
            $target_file = $upload_dir . $image;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $error = "Failed to upload image.";
            }
        }

        if (empty($error)) {
            try {
                // NEW: Added stock_quantity to the INSERT query
                $stmt = $pdo->prepare("INSERT INTO products (product_name, price, stock_quantity, category, location, description, image, seller_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$product_name, $price, $stock_quantity, $category, $location, $description, $image, $seller_id]);
                $success = "Listing added successfully!";
            } catch (Exception $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PopCart - Add Listing</title>
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

<section id="checkoutPage" style="padding-top: 40px; justify-content: center;">
  <div id="checkoutLeft" style="max-width: 600px; width: 100%;">
    <div class="checkoutBox">
      <h2>Create a New Listing</h2>
      
      <?php if($error): ?><div style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 4px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if($success): ?><div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px;"><?= htmlspecialchars($success) ?> <a href="my-listings.php" style="font-weight: bold; color: #155724; text-decoration: underline;">View Inventory</a></div><?php endif; ?>

      <form method="POST" action="add-listing.php" enctype="multipart/form-data">
        <div class="loginInput">
          <label>Product Title</label><br>
          <input type="text" name="product_name" required/>
        </div>
        
        <div style="display: flex; gap: 15px;">
            <div class="loginInput" style="flex: 1;">
              <label>Selling Price (R)</label><br>
              <input type="number" step="0.01" name="price" required/>
            </div>
            <div class="loginInput" style="flex: 1;">
              <label>Stock Quantity</label><br>
              <input type="number" name="stock_quantity" value="1" min="1" required/>
            </div>
        </div>

        <div class="loginInput">
          <label>Category</label><br>
          <select name="category" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ccc; font-family: inherit;">
            <option value="" disabled selected>-- Select --</option>
            <?php foreach ($allowed_categories as $cat): ?><option value="<?= $cat ?>"><?= ucfirst($cat) ?></option><?php endforeach; ?>
          </select>
        </div>

        <div class="loginInput">
          <label>Location</label><br>
          <select name="location" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ccc; font-family: inherit;">
            <option value="" disabled selected>-- Select --</option>
            <?php foreach ($allowed_locations as $loc): ?><option value="<?= $loc ?>"><?= htmlspecialchars($loc) ?></option><?php endforeach; ?>
          </select>
        </div>

        <div class="loginInput">
          <label>Product Description</label><br>
          <textarea name="description" rows="4" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ccc; font-family: inherit; resize: vertical;"></textarea>
        </div>

        <div class="loginInput">
          <label>Product Image</label><br>
          <input type="file" name="image" accept="image/png, image/jpeg, image/jpg" style="padding: 10px 0;"/>
        </div>

        <button type="submit" class="cartBtn" style="margin-top: 10px;">Post Listing</button>
      </form>
    </div>
  </div>
</section>

<script src="index.js"></script>
</body>
</html>