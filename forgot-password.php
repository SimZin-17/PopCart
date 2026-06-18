 <?php
session_start();
require 'connection.php';

$error = "";
$success = "";
$reset_link = "";

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if(empty($email)) {
        $error = "Please enter your email.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $stmt->execute([$token, $expires, $email]);

            $reset_link = "http://localhost/PopCart/reset-password.php?token=" . $token;
            $success = "Reset link generated successfully!";
        } else {
            $error = "No account found with that email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PopCart - Forgot Password</title>
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
    <h2>Forgot Password</h2>
    <p>Enter your email to reset your password</p>

    <?php if($error): ?>
      <p class="errorMsg"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if($success): ?>
      <p class="successMsg"><?= htmlspecialchars($success) ?></p>
      <div class="resetLink">
        <p>Your reset link:</p>
        <a href="<?= htmlspecialchars($reset_link) ?>">
          <?= htmlspecialchars($reset_link) ?>
        </a>
      </div>
    <?php endif; ?>

    <?php if(!$success): ?>
    <form method="POST" action="">

      <div class="loginInput">
        <label>Email Address</label><br>
        <input type="email" name="email" placeholder="Enter your email" required/>
      </div>

      <div class="loginInput">
        <button type="submit">Generate Reset Link</button>
      </div>

      <div class="loginInput">
        <a href="login.php" class="backLink">← Back to Login</a>
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