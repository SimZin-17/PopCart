 <?php
session_start();
require '../connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error = "";
$success = "";

// Fetch user to edit
$id = $_GET['id'] ?? null;
if(!$id) {
    header("Location: users.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if(!$user) {
    header("Location: users.php");
    exit;
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['user_name']);
    $email = trim($_POST['email']);
    $role  = $_POST['role'];

    if(empty($name) || empty($email)) {
        $error = "Name and email are required.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET user_name = ?, email = ?, role = ? WHERE user_id = ?");
        $stmt->execute([$name, $email, $role, $id]);
        $success = "User updated successfully!";

        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PopCart Admin - Edit User</title>
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
      <h2>Edit User</h2>

      <?php if($error): ?>
        <p style="color:red; margin-bottom:15px;"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <?php if($success): ?>
        <p style="color:green; margin-bottom:15px;"><?= htmlspecialchars($success) ?></p>
      <?php endif; ?>

      <form method="POST" action="">

        <div class="adminLoginInput">
          <label>Full Name</label><br>
          <input type="text" name="user_name" value="<?= htmlspecialchars($user['user_name']) ?>" required/>
        </div>

        <div class="adminLoginInput">
          <label>Email Address</label><br>
          <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required/>
        </div>

        <div class="adminLoginInput">
          <label>Role</label><br>
          <select name="role">
            <option value="buyer" <?= $user['role'] === 'buyer' ? 'selected' : '' ?>>Buyer</option>
            <option value="seller" <?= $user['role'] === 'seller' ? 'selected' : '' ?>>Seller</option>
            <option value="moderator" <?= $user['role'] === 'moderator' ? 'selected' : '' ?>>Moderator</option>
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
          </select>
        </div>

        <div class="adminLoginInput">
          <button type="submit">Update User</button>
        </div>

        <div class="adminLoginInput">
          <a href="users.php" style="color:#aaaaaa;">← Back to Users</a>
        </div>

      </form>
    </div>
  </div>

</div>

  <script src="../index.js"></script>
</body>
</html>