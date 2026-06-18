<?php
session_start();
require 'connection.php';

// Security Check
if (!isset($_SESSION['user_id']) || (isset($_SESSION['app_mode']) && $_SESSION['app_mode'] !== 'seller')) {
    header("Location: switch-mode.php?to=seller");
    exit;
}

$seller_id = $_SESSION['user_id'];
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$error = "";
$success = "";
$allowed_categories = ['electronics', 'fashion', 'vehicles', 'furniture', 'books', 'other'];
$allowed_locations = ['Johannesburg', 'Cape Town', 'Durban', 'Pretoria'];

// 1. Fetch existing product data from DB
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ? AND seller_id = ?");
$stmt->execute([$product_id, $seller_id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: my-listings.php");
    exit;
}

// 2. Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // We use $_POST values to validate and persist
    $product_name = trim($_POST['product_name']);
    $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $stock_quantity = max(0, (int) $_POST['stock_quantity']);
    $category = $_POST['category'];
    $location = $_POST['location'];
    $description = trim($_POST['description']);
    $image = $product['image'];

    if (empty($product_name) || empty($price) || empty($category) || empty($location) || empty($description)) {
        $error = "Please fill in all required fields.";
    } elseif ($price <= 0) {
        $error = "Price must be greater than zero.";
    } elseif (!in_array($category, $allowed_categories)) {
        $error = "Invalid category.";
    } elseif (!in_array($location, $allowed_locations)) {
        $error = "Invalid location.";
    } else {
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = 'prod_' . uniqid() . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $image);
        }

        $stmt = $pdo->prepare("UPDATE products SET product_name=?, price=?, stock_quantity=?, category=?, location=?, description=?, image=? WHERE product_id=? AND seller_id=?");
        $stmt->execute([$product_name, $price, $stock_quantity, $category, $location, $description, $image, $product_id, $seller_id]);
        $success = "Listing updated successfully!";

        // Update local $product array with the new data so the form shows the saved changes
        $product = ['product_name' => $product_name, 'price' => $price, 'stock_quantity' => $stock_quantity, 'category' => $category, 'location' => $location, 'description' => $description, 'image' => $image];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PopCart - Add Listing</title>
    <link rel="stylesheet" href="index.css" />
    <script src="https://kit.fontawesome.com/af69bda2f2.js" crossorigin="anonymous"></script>
</head>

<body>

    <header>
        <nav id="header">
            <a href="seller-dashboard.php"><img src="ChatGPT Image May 12, 2026, 07_33_09 PM.png" class="logo"
                    width="150" /></a>
            <div id="hamburger" onclick="toggleMenu()"><span></span><span></span><span></span></div>
            <ul id="navMenu">
                <li><a href="seller-dashboard.php">Dashboard</a></li>
                <li><a href="my-listings.php" style="font-weight: bold;">Inventory</a></li>
                <li><a href="my-orders.php">Fulfillment</a></li>
                <li><a href="switch-mode.php?to=buyer"
                        style="background: #3b82f6; color: white; padding: 8px 15px; border-radius: 20px;"><i
                            class="fa-solid fa-cart-shopping"></i> Switch to Buying</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <section id="checkoutPage" style="padding-top: 40px; justify-content: center;">
        <div id="checkoutLeft" style="max-width: 600px; width: 100%;">
            <div class="checkoutBox">
                <h2>Edit Listing</h2>

                <?php if ($error): ?>
                    <div style="color:red;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <?php if ($success): ?>
                    <div style="color:green;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

                <form method="POST" action="edit-listing.php?id=<?= $product_id ?>" enctype="multipart/form-data">
                    <div class="loginInput">
                        <label>Product Title</label><br>
                        <input type="text" name="product_name"
                            value="<?= htmlspecialchars($_POST['product_name'] ?? $product['product_name']) ?>"
                            required />
                    </div>

                    <div style="display: flex; gap: 15px;">
                        <div class="loginInput" style="flex: 1;">
                            <label>Price (R)</label><br>
                            <input type="number" step="0.01" name="price"
                                value="<?= htmlspecialchars($_POST['price'] ?? $product['price']) ?>" required />
                        </div>
                        <div class="loginInput" style="flex: 1;">
                            <label>Stock</label><br>
                            <input type="number" name="stock_quantity"
                                value="<?= (int) ($_POST['stock_quantity'] ?? $product['stock_quantity']) ?>" min="0"
                                required />
                        </div>
                    </div>

                    <div class="loginInput">
                        <label>Category</label><br>
                        <select name="category" required>
                            <?php foreach ($allowed_categories as $cat): ?>
                                <option value="<?= $cat ?>" <?= (($_POST['category'] ?? $product['category']) === $cat) ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="loginInput">
                        <label>Location</label><br>
                        <select name="location" required>
                            <?php foreach ($allowed_locations as $loc): ?>
                                <option value="<?= $loc ?>" <?= (($_POST['location'] ?? $product['location']) === $loc) ? 'selected' : '' ?>><?= htmlspecialchars($loc) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="loginInput">
                        <label>Description</label><br>
                        <textarea name="description" rows="4"
                            required><?= htmlspecialchars($_POST['description'] ?? $product['description']) ?></textarea>
                    </div>

                    <div class="loginInput">
                        <label>Update Image</label><br>
                        <?php if ($product['image']): ?><img src="uploads/<?= htmlspecialchars($product['image']) ?>"
                                style="height:50px;"><br><?php endif; ?>
                        <input type="file" name="image" />
                    </div>

                    <button type="submit" class="cartBtn">Save Changes</button>
                    <a href="my-listings.php" class="cartBtn"
                        style="background:#6c757d; text-decoration:none;">Cancel</a>
                </form>
            </div>
        </div>
    </section>

    <script src="index.js"></script>
</body>

</html>