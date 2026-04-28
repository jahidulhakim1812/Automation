<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_id'])) {
    die('Unauthorized. Please login via admin_login.php');
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
            break;

        case 'edit':
            if (!$id) throw new Exception('ID required');
            $stmt = $pdo->prepare("UPDATE wheel_segments SET label=?, color=?, sort_order=? WHERE id=?");
            $stmt->execute([$label, $color, $sort_order, $id]);
            break;

        case 'delete':
            if (!$id) throw new Exception('ID required');
            $stmt = $pdo->prepare("DELETE FROM wheel_segments WHERE id=?");
            $stmt->execute([$id]);
            break;

        default:
            throw new Exception('Invalid action');
    }
    header('Location: admin.php?msg=success');
} catch (Exception $e) {
    header('Location: admin.php?error=' . urlencode($e->getMessage()));
}
exit;
?>