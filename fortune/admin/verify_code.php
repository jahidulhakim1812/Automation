<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['reset_admin_id'])) {
    header('Location: forgot_password.php');
    exit;
}

$adminId = $_SESSION['reset_admin_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    $newPassword = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($newPassword !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Fetch valid code
        $stmt = $pdo->prepare("SELECT id FROM password_resets 
                                WHERE admin_id = ? AND code = ? AND used = 0 AND expires_at > NOW() 
                                ORDER BY id DESC LIMIT 1");
        $stmt->execute([$adminId, $code]);
        $reset = $stmt->fetch();

        if ($reset) {
            // Update password in plain text (no hash)
            $update = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $update->execute([$newPassword, $adminId]);

            // Mark reset as used
            $mark = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
            $mark->execute([$reset['id']]);

            session_unset();
            session_destroy();
            header('Location: admin_login.php?msg=password_reset');
            exit;
        } else {
            $error = "Invalid or expired verification code.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Code</title>
    <style>/* same as before */</style>
</head>
<body>
<div class="container">
    <h2>Enter Verification Code</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="text" name="code" placeholder="6-digit code" maxlength="6" required>
        <input type="password" name="new_password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Reset Password</button>
    </form>
    <div class="footer"><a href="forgot_password.php">← Request New Code</a></div>
</div>
</body>
</html>