<?php
session_start();
require 'connection.php';

$error = "";
$success = "";

$token = $_GET['token'] ?? null;

if(!$token) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if(!$user) {
    $error = "Invalid or expired reset link.";
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    if(empty($password) || empty($confirm)) {
        $error = "Please fill in all fields.";
    } elseif($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif(strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?");
        $stmt->execute([$hashed, $user['user_id']]);
        $success = "Password reset successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PopCart - Reset Password</title>
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
      <span></span><span></span><span></span>
    </div>
    <ul id="navMenu">
      <li><a href="index.php">Home</a></li>
      <li><a href="listings.php">Browse Listings</a></li>
      <li><a href="login.php" id="loginBtn">Login</a></li>
      <li><a href="register.php" id="registerBtn">Register</a></li>
      <li><a href="cart.php"><i class="fa-solid fa-cart-arrow-down"></i></a></li>
    </ul>
  </nav>
</header>

<section id="Login">
  <div id="LoginBox">
    <h2>Reset Password</h2>
    <p>Enter your new password below</p>

    <?php if($error): ?>
      <p class="errorMsg"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if($success): ?>
      <p class="successMsg"><?= htmlspecialchars($success) ?></p>
      <div class="loginInput">
        <a href="login.php" class="backLink">Go to Login →</a>
      </div>
    <?php elseif(!$error): ?>

    <form method="POST" action="">

      <div class="loginInput">
        <label>New Password</label><br>
        <input type="password" name="password" placeholder="Enter new password" required/>
      </div>

      <div class="loginInput">
        <label>Confirm Password</label><br>
        <input type="password" name="confirm" placeholder="Confirm new password" required/>
      </div>

      <div class="loginInput">
        <button type="submit">Reset Password</button>
      </div>

    </form>

    <?php endif; ?>

  </div>
</section>

<footer>
  <div class="col">
    <img src="ChatGPT Image May 12, 2026, 07_33_09 PM.png" class="footerLogo">
    <h4>Contact</h4>
    <p><strong>Address:</strong> 562 Wellington Road, Street 32, San Francisco</p>
    <p><strong>Phone:</strong> 0834425678</p>
    <p><strong>Hours:</strong> 10:00 - 18:00, Mon-Sat</p>
  </div>
  <div class="col">
    <h4>About</h4>
    <a href="#">About us</a>
    <a href="#">Privacy Policy</a>
    <a href="#">Terms & Conditions</a>
  </div>
  <div class="col">
    <h4>My Account</h4>
    <a href="login.php">Sign In</a>
    <a href="cart.php">View Cart</a>
  </div>
  <p id="copyright">© 2026 PopCart. All rights reserved</p>
</footer>

<script src="index.js"></script>
</body>
</html>