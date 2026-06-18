<?php
session_start();
require '../connection.php';

if(isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['mail']);
    $pass  = $_POST['pass'];

    if(empty($email) || empty($pass)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if($user && password_verify($pass, $user['password'])) {
            if($user['role'] === 'admin') {
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['user_name'] = $user['user_name'];
                $_SESSION['role']      = $user['role'];
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Access denied. Admins only.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PopCart Admin - Login</title>
  <link rel="stylesheet" href="admin.css"/>
  <script src="https://kit.fontawesome.com/af69bda2f2.js" crossorigin="anonymous"></script>
</head>
<body>

  <section id="adminloginPage">
    <div id="adminLoginBox">
      <h2>Admin Login</h2>
      <p>PopCart Admin</p>

      <?php if($error): ?>
        <p style="color:red; margin-bottom:10px;"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form method="POST" action="login.php">

        <div class="adminLoginInput">
          <label for="mail">Email Address</label><br>
          <input type="email" id="mail" name="mail"/>
        </div>

        <div class="adminLoginInput">
          <label for="pass">Password</label><br>
          <input type="password" id="pass" name="pass"/>
        </div>

        <div class="adminLoginInput">
          <button type="submit">Login</button>
        </div>

        <div class="adminLoginInput">
        <a href="forgot-password.php" class="backLink">Forgot Password?</a>
        </div>

      </form>
    </div>
  </section>

  <script src="../index.js"></script>
</body>
</html>