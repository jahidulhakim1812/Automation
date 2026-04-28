<?php
header('Content-Type: application/json');
require_once 'config.php';

$rawMobile = $_GET['mobile'] ?? '';
$mobile = preg_replace('/\D/', '', $rawMobile);

if (strlen($mobile) < 7) {
    echo json_encode(['available' => false, 'error' => 'Invalid mobile']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM spins WHERE mobile = ? LIMIT 1");
    $stmt->execute([$mobile]);
    $exists = $stmt->fetch() !== false;
    
    echo json_encode(['available' => !$exists]);
} catch (PDOException $e) {
    echo json_encode(['available' => false, 'error' => 'Database error']);
}
?>