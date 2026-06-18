<?php
// search.php — AJAX endpoint for live search suggestions
// Returns JSON array of matching products

require 'connection.php';

header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q === '') {
    echo json_encode([]);
    exit;
}

// Split the query into words so multi-word / partial searches match
// across product name, category, location, and description.
$words      = preg_split('/\s+/', trim($q));
$conditions = [];
$params     = [];

foreach ($words as $word) {
    if ($word === '') continue;
    $conditions[] = "(product_name LIKE ? OR category LIKE ? OR location LIKE ? OR description LIKE ?)";
    $term = '%' . $word . '%';
    array_push($params, $term, $term, $term, $term);
}

if (empty($conditions)) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT product_id, product_name, price, location, image
        FROM products
        WHERE " . implode(' AND ', $conditions) . "
        ORDER BY created_at DESC
        LIMIT 6";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

echo json_encode($results);