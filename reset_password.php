<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connection.php';

$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? '');
$code = trim($_POST['code'] ?? '');
$new_password = trim($_POST['new_password'] ?? '');

// Validation
if (!$email || !$role || !$code || !$new_password) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}
if (strlen($new_password) < 4) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 4 characters.']);
    exit;
}
if (!in_array($role, ['Admin', 'User'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role.']);
    exit;
}

// Verify code (case-insensitive email, but code is numeric)
$stmt = $conn->prepare("SELECT id FROM password_resets WHERE email = ? AND role = ? AND reset_code = ? AND expires_at > NOW()");
$stmt->bind_param("sss", $email, $role, $code);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    // Optional: log the attempted values for debugging
    error_log("Password reset failed for email: $email, role: $role, code: $code");
    echo json_encode(['success' => false, 'message' => 'Invalid or expired code. Please request a new one.']);
    exit;
}
$stmt->close();

// Update password
$table = ($role === 'Admin') ? 'admins' : 'users';
$update = $conn->prepare("UPDATE $table SET password = ? WHERE email = ?");
$update->bind_param("ss", $new_password, $email);
if ($update->execute()) {
    // Delete used reset entry
    $del = $conn->prepare("DELETE FROM password_resets WHERE email = ? AND role = ?");
    $del->bind_param("ss", $email, $role);
    $del->execute();
    echo json_encode(['success' => true, 'message' => 'Password updated successfully. You can now login.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $update->error]);
}
$update->close();
?>