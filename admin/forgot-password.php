 <?php
session_start();
require '../connection.php';

$error = "";
$success = "";
$reset_link = "";

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if(empty($email)) {
        $error = "Please enter your email.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $stmt->execute([$token, $expires, $email]);

            $reset_link = "http://localhost/PopCart/admin/reset-password.php?token=" . $token;
            $success = "Reset link generated!";
        } else {
            $error = "No admin account found with that email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PopCart Admin - Forgot Password</title>
  <link rel="stylesheet" href="admin.css"/>
  <script src="https://kit.fontawesome.com/af69bda2f2.js" crossorigin="anonymous"></script>
</head>
<body>

<section id="adminloginPage">
  <div id="adminLoginBox">
    <h2>Forgot Password</h2>
    <p>Admin accounts only</p>

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

      <div class="adminLoginInput">
        <label>Email Address</label><br>
        <input type="email" name="email" placeholder="Enter admin email" required/>
      </div>

      <div class="adminLoginInput">
        <button type="submit">Generate Reset Link</button>
      </div>

      <div class="adminLoginInput">
        <a href="login.php" class="backLink">← Back to Admin Login</a>
      </div>

    </form>
    <?php endif; ?>

  </div>
</section>

  <script src="../index.js"></script>
</body>
</html>