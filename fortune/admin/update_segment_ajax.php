<?php
require_once __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    exit;
}

$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? null;
$label = trim($_POST['label'] ?? '');
$color = trim($_POST['color'] ?? '#CCCCCC');
$sort_order = (int)($_POST['sort_order'] ?? 0);

try {
    switch ($action) {
        case 'add':
            if (empty($label)) throw new Exception('Label required');
            $stmt = $pdo->prepare("INSERT INTO wheel_segments (label, color, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$label, $color, $sort_order]);
            echo json_encode(['success' => true, 'message' => 'Segment added']);
            break;

        case 'edit':
            if (!$id) throw new Exception('ID required');
            $stmt = $pdo->prepare("UPDATE wheel_segments SET label=?, color=?, sort_order=? WHERE id=?");
            $stmt->execute([$label, $color, $sort_order, $id]);
            echo json_encode(['success' => true, 'message' => 'Segment updated']);
            break;

        case 'delete':
            if (!$id) throw new Exception('ID required');
            $stmt = $pdo->prepare("DELETE FROM wheel_segments WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Segment deleted']);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}