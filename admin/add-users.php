<?php
session_start();
require '../connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error = "";
$success = "";

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['user_name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    if(empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Check if email already exists
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->execute([$email]);

        if($check->fetch()) {
            $error = "Email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (user_name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed, $role]);
            $success = "User created successfully!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PopCart Admin - Add User</title>
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
      <h2>Add New User</h2>

      <?php if($error): ?>
        <p style="color:red; margin-bottom:15px;"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <?php if($success): ?>
        <p style="color:green; margin-bottom:15px;"><?= htmlspecialchars($success) ?> <a href="users.php" style="color:#ffffff;">Back to Users</a></p>
      <?php endif; ?>

      <form method="POST" action="" id="addUserForm">

        <div class="adminLoginInput">
          <label>Full Name</label><br>
          <input type="text" name="user_name" required/>
        </div>

        <div class="adminLoginInput">
          <label>Email Address</label><br>
          <input type="email" name="email" required/>
        </div>

        <div class="adminLoginInput">
          <label>Password</label><br>
          <input type="password" name="password" required/>
        </div>

        <div class="adminLoginInput">
          <label>Role</label><br>
          <select name="role">
            <option value="buyer">Buyer</option>
            <option value="seller">Seller</option>
            <option value="moderator">Moderator</option>
            <option value="admin">Admin</option>
          </select>
        </div>

        <div class="adminLoginInput">
          <button type="submit">Create User</button>
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