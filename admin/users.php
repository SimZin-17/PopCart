<?php
session_start();
require '../connection.php';

// Only admin can access
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Delete user
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Delete related cart items first
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$id]);
    
    // Delete related orders first
    $stmt = $pdo->prepare("DELETE FROM orders WHERE user_id = ?");
    $stmt->execute([$id]);
    
    // Delete related products first
    $stmt = $pdo->prepare("DELETE FROM products WHERE seller_id = ?");
    $stmt->execute([$id]);
    
    // Now delete the user
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$id]);
    
    header("Location: users.php");
    exit;
}

// Fetch all users
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PopCart Admin - Users</title>
  <link rel="stylesheet" href="admin.css"/>
  <script src="https://kit.fontawesome.com/af69bda2f2.js" crossorigin="anonymous"></script>
</head>
<body>

<div id="adminWrapper">

  <!-- Sidebar -->
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

  <!-- Main Content -->
  <div id="mainContent">
    <div id="umWrapper">
      <h2>User Management</h2>

      <div id="addBtn">
        <a href="add-users.php">
          <button>Add New User</button>
        </a>
      </div>

      <div id="tableWrapper">
        <table id="usersTable">
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Actions</th>
          </tr>

          <?php foreach($users as $user): ?>
          <tr>
            <td><?= $user['user_id'] ?></td>
            <td><?= htmlspecialchars($user['user_name']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= htmlspecialchars($user['role']) ?></td>
            <td>
              <a href="edit-users.php?id=<?= $user['user_id'] ?>">
                <button class="editBtn">Edit</button>
              </a>
              <a href="users.php?delete=<?= $user['user_id'] ?>" onclick="return confirm('Are you sure you want to delete this user?')">
                <button class="deleteBtn">Delete</button>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>

        </table>
      </div>

    </div>
  </div>

</div>

  <script src="../index.js"></script>
</body>
</html>