<?php
// Products API - returns product data as JSON
// Supports ?category=X and ?featured=1 filters

// Initialise DB (creates schema + seeds data if needed)
require_once __DIR__ . '/../db/init.php';

header('Content-Type: application/json; charset=utf-8');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = getDB();

    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
    $featured = isset($_GET['featured']) ? (int)$_GET['featured'] : 0;

    // Build query dynamically
    $sql    = 'SELECT * FROM products';
    $params = [];
    $where  = [];

    if ($category !== '') {
        $where[]  = 'category = :category';
        $params[':category'] = $category;
    }

    if ($featured === 1) {
        $where[]  = 'is_featured = 1';
    }

    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY category, name';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Cast types for clean JSON output
    foreach ($products as &$p) {
        $p['id']          = (int)$p['id'];
        $p['price']       = (float)$p['price'];
        $p['is_featured'] = (int)$p['is_featured'];
    }
    unset($p);

    echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database unavailable']);
}
