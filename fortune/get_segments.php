<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $stmt = $pdo->query("SELECT id, label, color FROM segments ORDER BY id ASC");
    $segments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Remove any segment that contains "100% discount" or similar
    $filtered = array_filter($segments, function($seg) {
        $label = strtolower($seg['label']);
        return !(
            strpos($label, '100% discount') !== false ||
            strpos($label, '100% off') !== false ||
            (strpos($label, '100%') !== false && strpos($label, 'discount') !== false)
        );
    });
    
    echo json_encode(['success' => true, 'segments' => array_values($filtered)]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>