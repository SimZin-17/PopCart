<?php
session_start();
require '../connection.php';

// 1. SECURITY: Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// 2. CSRF TOKEN GENERATION: To maintain parity with your secure products.php logic
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$success = "";

// Authorized ENUM values matching your database constraints
$allowed_categories = ['electronics', 'fashion', 'vehicles', 'furniture', 'books', 'other'];

// 3. ACTION: Handle product submission form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    $name     = trim($_POST['product_name']);
    $price    = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $category = $_POST['category'];

    // Validation checks
    if (empty($name) || $price === false || $price === "" || empty($category)) {
        $error = "All fields are required and must contain valid values.";
    } elseif ($price <= 0) {
        $error = "Price must be a value greater than zero.";
    } elseif (!in_array($category, $allowed_categories)) {
        $error = "Selected category is invalid.";
    } else {
        try {
            // Save product to database
            // Note: If your schema requires a default seller_id for admin creations, add it here
            $stmt = $pdo->prepare("INSERT INTO products (product_name, price, category) VALUES (?, ?, ?)");
            $stmt->execute([$name, $price, $category]);
            
            $success = "Product created successfully!";
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PopCart Admin - Add Product</title>
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
      <h2>Add New Product</h2>

      <?php if($error): ?>
        <p style="color:red; margin-bottom:15px;"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <?php if($success): ?>
        <p style="color:green; margin-bottom:15px;">
            <?= htmlspecialchars($success) ?> 
            <a href="products.php" style="color:#28a745; text-decoration: underline; margin-left: 5px;">Back to Products</a>
        </p>
      <?php endif; ?>

      <form method="POST" action="" id="addProductForm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="adminLoginInput">
          <label>Product Title</label><br>
          <input type="text" name="product_name" value="<?= isset($_POST['product_name']) && !$success ? htmlspecialchars($_POST['product_name']) : '' ?>" required/>
        </div>

        <div class="adminLoginInput">
          <label>Price (R)</label><br>
          <input type="number" step="0.01" name="price" value="<?= isset($_POST['price']) && !$success ? htmlspecialchars($_POST['price']) : '' ?>" required/>
        </div>

        <div class="adminLoginInput">
          <label>Category</label><br>
          <select name="category" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
            <option value="" disabled selected>-- Select a Category --</option>
            <?php foreach ($allowed_categories as $cat): ?>
              <option value="<?= $cat ?>" <?= isset($_POST['category']) && $_POST['category'] === $cat && !$success ? 'selected' : '' ?>>
                <?= ucfirst($cat) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="adminLoginInput">
          <button type="submit">Create Product</button>
        </div>

        <div class="adminLoginInput">
          <a href="products.php" style="color:#aaaaaa;">← Back to Products</a>
        </div>

      </form>
    </div>
  </div>

</div>

  <script src="index.js"></script>
</body>
</html>