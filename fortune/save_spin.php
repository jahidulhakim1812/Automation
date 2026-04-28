<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$segment_id = $input['segment_id'] ?? null;
$name = trim($input['name'] ?? '');
$rawMobile = trim($input['mobile'] ?? '');
$mobile = preg_replace('/\D/', '', $rawMobile);

if (!$segment_id || !$name || strlen($mobile) < 7) {
    echo json_encode(['success' => false, 'error' => 'missing_data']);
    exit;
}

try {
    // Get prize label from segments
    $stmt = $pdo->prepare("SELECT label FROM segments WHERE id = ?");
    $stmt->execute([$segment_id]);
    $prize = $stmt->fetch(PDO::FETCH_ASSOC);
    $prize_label = $prize ? $prize['label'] : 'Unknown Prize';
    
    // 🔽 THIS IS WHERE NAME & MOBILE ARE SAVED 🔽
    $insert = $pdo->prepare("INSERT INTO spins (segment_id, name, mobile, prize_label, spin_time) VALUES (?, ?, ?, ?, NOW())");
    $insert->execute([$segment_id, $name, $mobile, $prize_label]);
    // ☝️ The `name` and `mobile` columns receive the user's data ☝️
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) { // duplicate mobile error
        echo json_encode(['success' => false, 'error' => 'mobile_already_used']);
    } else {
        echo json_encode(['success' => false, 'error' => 'database_error']);
    }
}
?>