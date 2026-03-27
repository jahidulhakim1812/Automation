<?php
session_start();

// Include database connection
require_once 'db_connection.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST["role"];
    $email = $_POST["email"];
    $pass = $_POST["password"];

    $table = ($role == "Admin") ? "admins" : "users";
    // Note: Use prepared statements to prevent SQL injection in production
    $sql = "SELECT * FROM $table WHERE email='$email' AND password='$pass'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows === 1) {
        $_SESSION["email"] = $email;
        $_SESSION["role"] = $role;

        if ($role === "Admin") {
            header("Location:dashboard.php");
        } else {
            header("Location: user_dashboard.php");
        }
        exit();
    } else {
        $message = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Freelancing System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #1e1e2f, #2a2a40);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Unique Gradient Background with Animated Overlay */
        .splash {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at top left, #ff6a88, #ff99ac, #ffd6e0);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            animation: fadeOut 1s ease-in-out 2s forwards, gradientShift 3s ease infinite;
        }

        /* Subtle pattern overlay */
        .splash::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.1"><path fill="none" stroke="white" stroke-width="1" d="M10 10 L90 10 M10 20 L90 20 M10 30 L90 30 M10 40 L90 40 M10 50 L90 50 M10 60 L90 60 M10 70 L90 70 M10 80 L90 80 M10 90 L90 90 M10 10 L10 90 M20 10 L20 90 M30 10 L30 90 M40 10 L40 90 M50 10 L50 90 M60 10 L60 90 M70 10 L70 90 M80 10 L80 90 M90 10 L90 90"/></svg>');
            background-repeat: repeat;
            pointer-events: none;
        }

        .logo-container {
            text-align: center;
            animation: bounceIn 0.8s ease-out;
            z-index: 5;
        }

        /* Logo Image - Enlarged */
        .logo-image {
            width: 200px;  /* Increased from 120px */
            height: auto;
            filter: drop-shadow(0 8px 16px rgba(0,0,0,0.3));
            animation: pulse 1.5s infinite;
        }

        /* Optional: If you want to add back the text, uncomment below */
        /*
        .logo-text {
            font-size: 2.5rem;
            font-weight: bold;
            color: white;
            margin-top: 1rem;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        */

        /* Login Panel - Initially Hidden */
        .login-wrapper {
            display: none;
            width: 100%;
            max-width: 400px;
            padding: 20px;
            animation: slideUp 0.5s ease-out;
        }

        .login-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.3);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            width: 100%;
            margin: 15px 0;
        }

        input, select, button {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background: rgba(255,255,255,0.9);
        }

        input:focus, select:focus {
            outline: none;
            border-color: #ff6a88;
            box-shadow: 0 0 8px rgba(255,106,136,0.5);
        }

        button {
            background: linear-gradient(135deg, #ff6a88, #ff99ac);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,106,136,0.4);
        }

        .error {
            color: #e53e3e;
            text-align: center;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        /* Enhanced Animations */
        @keyframes bounceIn {
            0% {
                transform: scale(0.3);
                opacity: 0;
            }
            50% {
                transform: scale(1.1);
            }
            70% {
                transform: scale(0.95);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.2);  /* Larger scale for stronger pulse */
            }
            100% {
                transform: scale(1);
            }
        }

        @keyframes gradientShift {
            0% {
                background-position: 0% 0%;
            }
            50% {
                background-position: 100% 100%;
            }
            100% {
                background-position: 0% 0%;
            }
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                visibility: hidden;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

    <!-- Splash Screen with Animated Logo -->
    <div class="splash" id="splash">
        <div class="logo-container">
            <!-- Replace with your actual logo image -->
            <img src="logo/logo.png" alt="Logo" class="logo-image" onerror="this.src='https://via.placeholder.com/200?text=Logo'">
            <!-- Optional: Add text back if desired -->
        </div>
    </div>

    <!-- Login Panel (Hidden Initially) -->
    <div class="login-wrapper" id="loginPanel">
        <div class="login-box">
            <h2>Login to Your Account</h2>
            <?php if ($message): ?>
                <p class="error"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="Admin">Admin</option>
                        <option value="User">User</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <button type="submit">Login</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const splash = document.getElementById('splash');
        const loginPanel = document.getElementById('loginPanel');

        splash.addEventListener('animationend', function() {
            splash.style.display = 'none';
            loginPanel.style.display = 'block';
        });
    </script>

</body>
</html>