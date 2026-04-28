<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT prize_label, spin_time, ip_address FROM spin_logs ORDER BY spin_time DESC LIMIT 10");
    echo json_encode(['success' => true, 'history' => $stmt->fetchAll()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>