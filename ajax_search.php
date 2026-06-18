<?php
require 'connection.php';

if (isset($_GET['q'])) {
    $searchTerm = trim($_GET['q']);
    
    if (strlen($searchTerm) < 2) {
        echo json_encode([]);
        exit;
    }

    // Search Name, Category, and Location for partial matches
    $stmt = $pdo->prepare("
        SELECT product_id, product_name, price, image, category 
        FROM products 
        WHERE product_name LIKE ? OR category LIKE ? OR location LIKE ?
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    
    $term = "%" . $searchTerm . "%";
    $stmt->execute([$term, $term, $term]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the results as JSON to the Javascript
    echo json_encode($results);
}
?>