  <?php
session_start();
require '../connection.php';

$error = "";
$success = "";

$token = $_GET['token'] ?? null;

if(!$token) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW() AND role = 'admin'");
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
  <title>PopCart Admin - Reset Password</title>
  <link rel="stylesheet" href="admin.css"/>
  <script src="https://kit.fontawesome.com/af69bda2f2.js" crossorigin="anonymous"></script>
</head>
<body>

<section id="adminloginPage">
  <div id="adminLoginBox">
    <h2>Reset Password</h2>
    <p>Enter your new admin password</p>

    <?php if($error): ?>
      <p class="errorMsg"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if($success): ?>
      <p class="successMsg"><?= htmlspecialchars($success) ?></p>
      <div class="adminLoginInput">
        <a href="login.php" class="backLink">Go to Admin Login →</a>
      </div>
    <?php elseif(!$error): ?>

    <form method="POST" action="">

      <div class="adminLoginInput">
        <label>New Password</label><br>
        <input type="password" name="password" placeholder="Enter new password" required/>
      </div>

      <div class="adminLoginInput">
        <label>Confirm Password</label><br>
        <input type="password" name="confirm" placeholder="Confirm new password" required/>
      </div>

      <div class="adminLoginInput">
        <button type="submit">Reset Password</button>
      </div>

    </form>

    <?php endif; ?>

  </div>
</section>

  <script src="../index.js"></script>
</body>
</html>