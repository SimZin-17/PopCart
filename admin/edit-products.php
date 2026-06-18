<?php
session_start();
require '../connection.php';

// 1. SECURITY: Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// 2. CSRF TOKEN GENERATION
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$success = "";
$allowed_categories = ['electronics', 'fashion', 'vehicles', 'furniture', 'books', 'other'];

// 3. RETRIEVAL
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: products.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit;
}

// 4. ACTION: Process update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    $name = trim($_POST['product_name']);
    $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $category = trim($_POST['category']);

    if (empty($name) || empty($category) || $price === false || $price === "") {
        $error = "All fields are required.";
    } elseif ($price <= 0) {
        $error = "Price must be greater than zero.";
    } elseif (!in_array($category, $allowed_categories)) {
        $error = "Selected category is invalid.";
    } else {
        $stmt = $pdo->prepare("UPDATE products SET product_name = ?, price = ?, category = ? WHERE product_id = ?");
        if ($stmt->execute([$name, $price, $category, $id])) {
            $success = "Product updated successfully!";
            // Update local product array so the form shows the saved values
            $product['product_name'] = $name;
            $product['price'] = $price;
            $product['category'] = $category;
        } else {
            $error = "Failed to write changes to database.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PopCart Admin - Edit Product</title>
  <link rel="stylesheet" href="admin.css"/>
  <script src="https://kit.fontawesome.com/af69bda2f2.js" crossorigin="anonymous"></script>
</head>
<body>

<div id="adminWrapper">
  <div id="sidebar">
    <h2 id="adminLogo">PopCart <span>Admin</span></h2>
    <ul id="sideMenu">
      <li><a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
      <li><a href="users.php"><i class="fa-solid fa-users"></i> Users</a></li>
      <li><a href="products.php"><i class="fa-solid fa-box"></i> Products</a></li>
      <li><a href="../index.php"><i class="fa-solid fa-house"></i> Main Site</a></li>
      <li><a href="login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
  </div>

  <div id="mainContent">
    <div id="umWrapper">
      <h2>Edit Product</h2>

      <?php if ($error): ?>
        <p style="color:red; margin-bottom:15px;"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>
      <?php if ($success): ?>
        <p style="color:green; margin-bottom:15px;"><?= htmlspecialchars($success) ?></p>
      <?php endif; ?>

      <form method="POST" action="edit-product.php?id=<?= (int)$id ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="adminLoginInput">
          <label>Product Title</label><br>
          <input type="text" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required/>
        </div>

        <div class="adminLoginInput">
          <label>Price (R)</label><br>
          <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price']) ?>" required/>
        </div>

        <div class="adminLoginInput">
          <label>Category</label><br>
          <select name="category" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ccc; font-size: 14px;" required>
            <?php foreach ($allowed_categories as $cat): ?>
              <option value="<?= $cat ?>" <?= $product['category'] === $cat ? 'selected' : '' ?>>
                <?= ucfirst($cat) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="adminLoginInput" style="display: flex; gap: 10px;">
          <button type="submit" style="background:#28a745;">Update Product</button>
          <a href="products.php" style="background:#6c757d; color:white; padding:10px 20px; text-decoration:none; border-radius:4px;">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="index.js"></script>
</body>
</html>