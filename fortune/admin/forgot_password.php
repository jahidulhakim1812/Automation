<?php
session_start();
require_once __DIR__ . '/../config.php';

// PHPMailer setup (as before) – include the same code to send email
// ... (same PHPMailer include block)

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']);

    $stmt = $pdo->prepare("SELECT id, username, email FROM admins WHERE username = ? OR email = ?");
    $stmt->execute([$identifier, $identifier]);
    $admin = $stmt->fetch();

    if ($admin && !empty($admin['email'])) {
        // Clean up old resets
        $clean = $pdo->prepare("DELETE FROM password_resets WHERE admin_id = ? AND (expires_at < NOW() OR used = 1)");
        $clean->execute([$admin['id']]);

        $code = sprintf("%06d", mt_rand(0, 999999));
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $token = bin2hex(random_bytes(16));

        $insert = $pdo->prepare("INSERT INTO password_resets (admin_id, token, code, expires_at) VALUES (?, ?, ?, ?)");
        $insert->execute([$admin['id'], $token, $code, $expires]);

        // Send email with code (same as before)
        $mail = new PHPMailer(true);
        try {
            // SMTP settings (update with your credentials)
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'artechsolution.online@gmail.com';
            $mail->Password   = 'giwr wrcr mnyi lkpf';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('noreply@yourdomain.com', 'Spin Wheel Admin');
            $mail->addAddress($admin['email'], $admin['username']);

            $mail->isHTML(false);
            $mail->Subject = 'Password Reset Code - Spin Wheel Admin';
            $mail->Body    = "Hello {$admin['username']},\n\n"
                            . "Your verification code is: {$code}\n\n"
                            . "This code is valid for 15 minutes.\n\n"
                            . "Enter it on the verification page to reset your password.\n\n"
                            . "If you did not request this, ignore this email.\n";

            $mail->send();

            $_SESSION['reset_admin_id'] = $admin['id'];
            header('Location: verify_code.php');
            exit;
        } catch (Exception $e) {
            $error = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $error = "No account found with that username or email, or account has no email set.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <style>/* same as before */</style>
</head>
<body>
<div class="container">
    <h2>Reset Password</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="text" name="identifier" placeholder="Username or Email" required>
        <button type="submit">Send Verification Code</button>
    </form>
    <div class="footer"><a href="admin_login.php">← Back to Login</a></div>
</div>
</body>
</html>