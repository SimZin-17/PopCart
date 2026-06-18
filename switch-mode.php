<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check which mode the user wants to switch to
if (isset($_GET['to'])) {
    if ($_GET['to'] === 'seller') {
        $_SESSION['app_mode'] = 'seller';
        // Redirect to the new Seller Dashboard
        header("Location: seller-dashboard.php");
        exit;
    } else {
        $_SESSION['app_mode'] = 'buyer';
        // Redirect to the main storefront
        header("Location: index.php");
        exit;
    }
}

// Fallback safety
header("Location: index.php");
exit;