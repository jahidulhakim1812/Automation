<?php
session_start();
require_once __DIR__ . '/../config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    die('Invalid request. No token provided.');
}

// Verify token
$stmt = $pdo->prepare("SELECT pr.id, pr.admin_id, pr.expires_at, a.username 
                        FROM password_resets pr 
                        JOIN admins a ON pr.admin_id = a.id 
                        WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    die('This reset link is invalid or has expired.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if (empty($password) || $password !== $confirm) {
        $error = 'Passwords do not match or are empty.';
    } else {
        // Update password
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $update->execute([$hashed, $reset['admin_id']]);
        
        // Mark token as used
        $useToken = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
        $useToken->execute([$reset['id']]);
        
        $success = 'Password has been reset successfully. You can now login.';
        // Redirect after 3 seconds
        header("refresh:3;url=admin_login.php");
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a2a3a, #0f1a24);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }
        h2 {
            color: #f9c74f;
            text-align: center;
            margin-bottom: 20px;
        }
        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 8px;
            background: rgba(255,255,255,0.9);
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #f9c74f;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #ccc;
        }
        .footer a {
            color: #f9c74f;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Set New Password</h2>
    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?> Redirecting to login...</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (!$success): ?>
    <form method="post">
        <input type="password" name="password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Reset Password</button>
    </form>
    <?php endif; ?>
    <div class="footer">
        <a href="admin_login.php">Back to Login</a>
    </div>
</div>
</body>
</html>