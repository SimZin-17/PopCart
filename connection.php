<?php
$host = "sql103.infinityfree.com";
$dbname = "if0_42224709_PopCart";
$username = "if0_42224709";
$password = "rkh9GE3IKh";

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  die("Connection failed: " . $e->getMessage());
}
?>
