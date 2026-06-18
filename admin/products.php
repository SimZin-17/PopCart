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

// 3. ACTION: Delete (Moderate) Product safely via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    $product_id = (int)$_POST['delete_id'];

    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM cart WHERE product_id = ?")->execute([$product_id]);
        $pdo->prepare("DELETE FROM products WHERE product_id = ?")->execute([$product_id]);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error deleting product: " . $e->getMessage());
    }

    // Redirect back to the same view they were just on
    $return_view = isset($_POST['current_filter']) ? $_POST['current_filter'] : 'all';
    header("Location: products.php?filter=" . urlencode($return_view));
    exit;
}

// 4. THE MODERATION ENGINE: Define your rules here
// Add any words here that violate your Terms of Service
$prohibited_keywords = ['fake', 'replica', 'weapon', 'drug', 'gun', 'counterfeit', 'hack'];

// Dynamically build the SQL for the keyword scan
$keyword_conditions = [];
foreach ($prohibited_keywords as $word) {
    // We use LOWER() to ensure the scan catches "Weapon", "WEAPON", and "weapon"
    $keyword_conditions[] = "LOWER(p.product_name) LIKE LOWER('%$word%')";
}
$keyword_sql = implode(' OR ', $keyword_conditions);

// Determine the current view mode (All vs Flagged)
$current_filter = $_GET['filter'] ?? 'all';
$having_clause = ($current_filter === 'flagged') ? "HAVING is_flagged = 1" : "";

// 5. DATA RETRIEVAL: Scan and fetch
$stmt = $pdo->prepare("
    SELECT 
        p.product_id, 
        p.product_name, 
        p.price, 
        p.category, 
        u.user_name AS seller_name,
        -- The database will output a '1' if the item hits a keyword OR has a suspicious price (<= 0)
        ( ($keyword_sql) OR p.price <= 0 ) AS is_flagged
    FROM products p 
    JOIN users u ON p.seller_id = u.user_id 
    $having_clause
    -- Sort flagged items to the very top, then by newest
    ORDER BY is_flagged DESC, p.product_id DESC
");
$stmt->execute();
$products = $stmt->fetchAll();

// Count how many total items are flagged for the UI badge
$flagged_count = 0;
foreach ($products as $p) {
    if ($p['is_flagged']) $flagged_count++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PopCart Admin - Moderation</title>
  <link rel="stylesheet" href="admin.css"/>
  <script src="https://kit.fontawesome.com/af69bda2f2.js" crossorigin="anonymous"></script>
  <style>
      /* Toggle Button Styles */
      .mod-tabs { margin-bottom: 20px; display: flex; gap: 10px; }
      .mod-tab { padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; border: 1px solid #ccc; color: #555; background: #fff; }
      .mod-tab.active { background: #3b82f6; color: white; border-color: #3b82f6; }
      .mod-tab.active-danger { background: #dc3545; color: white; border-color: #dc3545; }
      
      /* Flag Badge */
      .badge-flagged { background: #ffebee; color: #dc3545; border: 1px solid #ffcdd2; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-block; margin-top: 5px; }
      .row-flagged { background-color: #fffafb; }
  </style>
</head>
<body>

<div id="adminWrapper">

  <div id="sidebar">
    <h2 id="adminLogo">PopCart <span>Admin</span></h2>
    <ul id="sideMenu">
      <li><a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
      <li><a href="users.php"><i class="fa-solid fa-users"></i> Users</a></li>
      <li><a href="products.php" style="font-weight: bold; color: #3b82f6;"><i class="fa-solid fa-shield-halved"></i> Moderation</a></li>
      <li><a href="../index.php"><i class="fa-solid fa-house"></i> Main Site</a></li>
      <li><a href="login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
  </div>

  <div id="mainContent">
    <div id="umWrapper">
      <h2>Automated Moderation Hub</h2>
      <p style="color: #666; margin-bottom: 20px;">The system automatically scans listings for prohibited keywords and anomalous pricing.</p>

      <div class="mod-tabs">
          <a href="products.php?filter=all" class="mod-tab <?= $current_filter === 'all' ? 'active' : '' ?>">
              <i class="fa-solid fa-list"></i> All Listings
          </a>
          <a href="products.php?filter=flagged" class="mod-tab <?= $current_filter === 'flagged' ? 'active-danger' : '' ?>">
              <i class="fa-solid fa-triangle-exclamation"></i> Needs Review 
              <?php if($current_filter === 'all' && $flagged_count > 0): ?>
                  <span style="background: #dc3545; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px; margin-left: 5px;"><?= $flagged_count ?></span>
              <?php endif; ?>
          </a>
      </div>

      <div id="tableWrapper">
        <table id="usersTable" style="width: 100%; text-align: left; border-collapse: collapse;">
          <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
            <th style="padding: 12px;">ID</th>
            <th style="padding: 12px;">Listing Details</th>
            <th style="padding: 12px;">Seller</th>
            <th style="padding: 12px;">Price</th>
            <th style="padding: 12px;">Actions</th>
          </tr>

          <?php if (empty($products)): ?>
          <tr>
            <td colspan="5" style="text-align: center; padding: 20px;">No listings found in this view.</td>
          </tr>
          <?php else: ?>
            <?php foreach($products as $product): ?>
            <tr class="<?= $product['is_flagged'] ? 'row-flagged' : '' ?>" style="border-bottom: 1px solid #eee;">
              <td style="padding: 12px;"><?= (int)$product['product_id'] ?></td>
              <td style="padding: 12px;">
                  <strong><?= htmlspecialchars($product['product_name']) ?></strong><br>
                  <span style="font-size: 13px; color: #666;"><?= htmlspecialchars($product['category']) ?></span><br>
                  <?php if($product['is_flagged']): ?>
                      <div class="badge-flagged"><i class="fa-solid fa-ban"></i> Auto-Flagged for Review</div>
                  <?php endif; ?>
              </td>
              <td style="padding: 12px; color: #007bff;"><i class="fa-solid fa-store"></i> <?= htmlspecialchars($product['seller_name']) ?></td>
              <td style="padding: 12px; <?= $product['price'] <= 0 ? 'color: #dc3545; font-weight: bold;' : '' ?>">
                  R<?= htmlspecialchars($product['price']) ?>
              </td>
              <td style="padding: 12px;">
                
                <form action="products.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete this listing?')">
                  <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="delete_id" value="<?= (int)$product['product_id'] ?>">
                  <input type="hidden" name="current_filter" value="<?= htmlspecialchars($current_filter) ?>">
                  <button type="submit" class="deleteBtn" style="background: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;">
                    <i class="fa-solid fa-trash"></i> Delete
                  </button>
                </form>
                
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>

        </table>
      </div>
    </div>
  </div>

</div>

  <script src="index.js"></script>
</body>
</html>