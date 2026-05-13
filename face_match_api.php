<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['photo'])) {
    echo json_encode(['error' => 'No photo uploaded']);
    exit();
}

$file = $_FILES['photo'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Upload error']);
    exit();
}

$allowed = ['image/jpeg', 'image/png', 'image/webp'];
$detected = mime_content_type($file['tmp_name']);
if (!in_array($detected, $allowed)) {
    echo json_encode(['error' => 'Invalid image format']);
    exit();
}

$imageData = base64_encode(file_get_contents($file['tmp_name']));
$projectRoot = __DIR__;
$pythonScript = $projectRoot . '/face_matcher.py';

// ADD: change working directory before running
$command = 'cd ' . escapeshellarg($projectRoot) . ' && ' . 
           escapeshellcmd($pythonPath . ' ' . escapeshellarg($pythonScript) . ' ' . escapeshellarg($imageData));
$output = shell_exec($command . ' 2>&1');
if (!file_exists($pythonScript)) {
    echo json_encode(['error' => 'face_matcher.py not found']);
    exit();
}

// Adjust path if python not in PATH
$pythonPath = 'C:\\Python310\\python.exe';
// or Python 3.11:
$pythonPath = 'C:\\Python311\\python.exe';
$command = escapeshellcmd($pythonPath . " " . escapeshellarg($pythonScript) . " " . escapeshellarg($imageData));
$output = shell_exec($command . " 2>&1");
$output = trim($output);

if (strpos($output, 'MATCH:') === 0) {
    $parts = explode(':', $output);
    $student_id = $parts[1];
    $similarity = isset($parts[2]) ? floatval($parts[2]) : 0;
    echo json_encode(['success' => true, 'student_id' => $student_id, 'similarity' => $similarity]);
} else {
    echo json_encode(['success' => false, 'error' => $output]);
}
?>