<?php
session_start();
require 'connection.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $pwd   = $_POST["pwd"];

    if (empty($email) || empty($pwd)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($pwd, $user['password'])) {
            // Set standard user credentials
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['user_name'] = $user['user_name'];
            $_SESSION['role']      = $user['role'];
            
            // INITIALIZE MODE: Default all new logins to Buyer Mode
            $_SESSION['app_mode']  = 'buyer';

            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PopCart - Login</title>
  <link rel="stylesheet" href="index.css"/>
  <script src="https://kit.fontawesome.com/af69bda2f2.js" crossorigin="anonymous"></script>
</head>
<body>

  <header>
    <nav id="header">
      <a href="index.php">
        <img src="ChatGPT Image May 12, 2026, 07_33_09 PM.png" class="logo" alt="PopCart Logo" width="150" height="auto"/>
      </a>
      <div id="hamburger" onclick="toggleMenu()"><span></span><span></span><span></span></div>
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
      <h2>Welcome Back</h2>
      <p>Login to PopCart</p>

      <?php if ($error): ?>
        <p style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form method="POST" action="login.php">

        <div class="loginInput">
          <label for="email">Email Address</label><br>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
        </div>

        <div class="loginInput">
          <label for="pwd">Password</label><br>
          <input type="password" id="pwd" name="pwd"/>
        </div>

        <div class="loginInput">
          <button type="submit">Log in with password</button>
        </div>

        <div class="loginInput">
          <p>Don't have an account yet? <a href="register.php">Register here</a></p>
        </div>

        <div class="loginInput">
         <a href="forgot-password.php" class="backLink">Forgot Password?</a>
      </div>

      </form>
    </div>
  </section>

  <footer>
    <div class="col">
      <img src="ChatGPT Image May 12, 2026, 07_33_09 PM.png" class="footerLogo">
      <h4>Contact</h4>
      <p><strong>Address:</strong> 562 Wellington Road, Street 32, San Francisco</p>
      <p><strong>Phone:</strong> 0834425678</p>
      <p><strong>Hours:</strong> 10:00 - 18:00, Mon-Sat</p>
      <div class="follow">
        <h4>Follow us</h4>
        <div class="icon">
          <i class="fab fa-facebook-f"></i>
          <i class="fab fa-twitter"></i>
          <i class="fab fa-instagram"></i>
          <i class="fab fa-pinterest-p"></i>
          <i class="fab fa-youtube"></i>
        </div>
      </div>
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