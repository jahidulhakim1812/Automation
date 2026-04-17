<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'db_connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Ensure table exists with correct columns
$conn->query("CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    role ENUM('Admin','User') NOT NULL,
    token VARCHAR(255) NOT NULL,
    reset_code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? '');

if (!$email || !$role) {
    echo json_encode(['success' => false, 'message' => 'Email and role are required.']);
    exit;
}
if (!in_array($role, ['Admin', 'User'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role selected.']);
    exit;
}

$table = ($role === 'Admin') ? 'admins' : 'users';
$stmt = $conn->prepare("SELECT id FROM $table WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'No account found with that email and role.']);
    exit;
}
$stmt->close();

// Generate 6-digit numeric code
$reset_code = sprintf("%06d", mt_rand(1, 999999));
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

// Delete old requests
$stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ? AND role = ?");
$stmt->bind_param("ss", $email, $role);
$stmt->execute();
$stmt->close();

// Insert new request
$stmt = $conn->prepare("INSERT INTO password_resets (email, role, token, reset_code, expires_at) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $email, $role, $token, $reset_code, $expires);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    exit;
}
$stmt->close();

// Send email with OTP
$subject = "Password Reset Code - AR TECH SOLUTION";
$body = "
    <div style='font-family: Arial, sans-serif;'>
        <h3>Password Reset Request</h3>
        <p>You requested to reset your password for your $role account associated with <strong>$email</strong>.</p>
        <p>Your 6-digit verification code is:</p>
        <h2 style='background:#f0f0f0; display:inline-block; padding:10px 20px; letter-spacing:5px; border-radius:8px;'>$reset_code</h2>
        <p>This code is valid for 15 minutes.</p>
        <p>If you did not request this, please ignore this email.</p>
        <br>
        <p>Regards,<br>AR Tech Solution</p>
    </div>";

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'artechsolution.online@gmail.com';
    $mail->Password   = 'giwr wrcr mnyi lkpf';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('noreply@artechsolution.com', 'AR Tech Admin');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $body;

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'A 6-digit code has been sent to your email.']);
} catch (Exception $e) {
    error_log("Mail error: " . $mail->ErrorInfo);
    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again later.']);
}
?>