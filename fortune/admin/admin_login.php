<?php
session_start();
require_once __DIR__ . '/../config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        // Compare plain text password
        if ($admin && $password === $admin['password']) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: admin.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Admin Login | Spin Wheel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', 'Segoe UI', system-ui, -apple-system, 'Roboto', sans-serif;
            min-height: 100vh;
            background: linear-gradient(145deg, #0f2027, #203a43, #2c5364);
            background-size: 200% 200%;
            animation: gradientShift 12s ease infinite;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 40%, rgba(255,215,100,0.08) 0%, transparent 50%);
            pointer-events: none;
        }

        .login-container {
            width: 100%;
            max-width: 460px;
            background: rgba(20, 30, 35, 0.55);
            backdrop-filter: blur(14px);
            border-radius: 48px;
            padding: 40px 32px;
            box-shadow: 0 30px 50px rgba(0, 0, 0, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 220, 120, 0.35);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeSlide 0.6s ease-out;
        }

        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 40px 60px rgba(0, 0, 0, 0.5);
        }

        /* ========= ROUND LOGO SECTION ========= */
        .logo-wrapper {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(4px);
            padding: 12px;
            border-radius: 50%;  /* circular container */
            border: 2px solid rgba(255, 215, 100, 0.6);
            transition: all 0.3s ease;
            width: 110px;
            height: 110px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .logo:hover {
            background: rgba(0, 0, 0, 0.35);
            transform: scale(1.02);
            border-color: #FFD966;
        }

        /* Logo image - round shape */
        .logo-img {
            width: 100%;
            height: 100%;
            object-fit: cover;  /* ensures image covers circle without distortion */
            display: block;
            border-radius: 50%; /* makes image round */
        }

        /* Fallback text (if image fails to load) */
        .logo-fallback {
            display: none;
            font-size: 1.4rem;
            font-weight: 800;
            background: linear-gradient(135deg, #FFE5A3, #FFB347);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-align: center;
        }

        h2 {
            text-align: center;
            font-size: 1.85rem;
            font-weight: 600;
            color: #f0e6d0;
            margin-bottom: 28px;
            margin-top: 12px;
            letter-spacing: -0.3px;
        }

        .input-group {
            margin-bottom: 24px;
        }

        input {
            width: 100%;
            padding: 16px 20px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.92);
            border: none;
            border-radius: 60px;
            outline: none;
            transition: all 0.2s;
            font-family: inherit;
            color: #1e2a2f;
            font-weight: 500;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        input:focus {
            background: white;
            box-shadow: 0 0 0 3px rgba(249, 199, 79, 0.5), 0 6px 12px rgba(0, 0, 0, 0.1);
            transform: scale(1.01);
        }

        input::placeholder {
            color: #6c7a7e;
            font-weight: 400;
        }

        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(115deg, #F9C74F, #F9844A);
            border: none;
            border-radius: 60px;
            font-size: 1.2rem;
            font-weight: 800;
            color: #1e2c34;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
            letter-spacing: 1px;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.3);
            margin-top: 8px;
        }

        button:hover {
            transform: scale(1.02);
            background: linear-gradient(115deg, #FFD970, #FFA559);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.4);
        }

        button:active {
            transform: scale(0.98);
        }

        .error {
            background: rgba(255, 80, 80, 0.2);
            backdrop-filter: blur(4px);
            color: #FFB3B3;
            text-align: center;
            padding: 12px;
            border-radius: 60px;
            margin-bottom: 20px;
            font-weight: 500;
            border: 1px solid rgba(255, 100, 100, 0.5);
            font-size: 0.9rem;
        }

        .message {
            background: rgba(100, 200, 100, 0.2);
            backdrop-filter: blur(4px);
            color: #C8FFB0;
            padding: 12px;
            border-radius: 60px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            border: 1px solid rgba(120, 220, 100, 0.5);
        }

        .footer {
            text-align: center;
            margin-top: 32px;
            font-size: 0.85rem;
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .footer a {
            color: #FFDC8F;
            text-decoration: none;
            transition: color 0.2s;
            font-weight: 500;
            border-bottom: 1px dotted transparent;
        }

        .footer a:hover {
            color: #FFC285;
            border-bottom-color: #FFC285;
        }

        @media (max-width: 500px) {
            .login-container {
                padding: 32px 20px;
            }
            h2 {
                font-size: 1.5rem;
            }
            .logo {
                width: 90px;
                height: 90px;
                padding: 8px;
            }
        }

        @keyframes fadeSlide {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="logo-wrapper">
        <!-- 
            ROUND LOGO: mockup.jpg will appear as a perfect circle
            Adjust the src path if the logo is in a subfolder (e.g., "fortune/mockup.jpg")
        -->
        <div class="logo">
            <img src="mockup.jpg" alt="Fortune Logo" class="logo-img" onerror="this.style.display='none'; this.nextSibling.style.display='inline';">
            <span class="logo-fallback">🎡</span>
        </div>
    </div>
    
    <h2>Admin Access</h2>
    
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'password_reset'): ?>
        <div class="message">✅ Password reset successfully! Log in with your new password.</div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="post">
        <div class="input-group">
            <input type="text" name="username" placeholder="👤 Username" required autofocus>
        </div>
        <div class="input-group">
            <input type="password" name="password" placeholder="🔒 Password" required>
        </div>
        <button type="submit">➤ LOGIN</button>
    </form>
    
    <div class="footer">
        <a href="../index.html">← Back to Wheel</a>
        <a href="forgot_password.php">Forgot Password?</a>
    </div>
</div>
</body>
</html>