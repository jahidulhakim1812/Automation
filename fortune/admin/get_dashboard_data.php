<?php
require_once __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    exit;
}

// Statistics
$totalSpins = $pdo->query("SELECT COUNT(*) FROM spin_logs")->fetchColumn();
$totalSegments = $pdo->query("SELECT COUNT(*) FROM wheel_segments")->fetchColumn();
$topPrize = $pdo->query("SELECT prize_label, COUNT(*) as cnt FROM spin_logs GROUP BY prize_label ORDER BY cnt DESC LIMIT 1")->fetch();

// Chart data (top 5 prizes)
$chartData = $pdo->query("SELECT prize_label, COUNT(*) as cnt FROM spin_logs GROUP BY prize_label ORDER BY cnt DESC LIMIT 5")->fetchAll();

header('Content-Type: application/json');
echo json_encode([
    'totalSpins' => $totalSpins,
    'totalSegments' => $totalSegments,
    'topPrize' => $topPrize,
    'chartData' => $chartData
]);