<?php
session_start();

// Database connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "freelancing";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST["role"];
    $email = $_POST["email"];
    $pass = $_POST["password"];

    $table = ($role == "Admin") ? "admins" : "users";
    $sql = "SELECT * FROM $table WHERE email='$email' AND password='$pass'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows === 1) {
        $_SESSION["email"] = $email;
        $_SESSION["role"] = $role;

        if ($role === "Admin") {
            header("Location: admin/dashboard.php");
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
    <title>Login — AR TECH SOLUTION</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #0a0e1a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 20% 30%, rgba(0,229,200,0.15), rgba(8,12,24,0.95));
            z-index: 0;
        }
        .splash {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0a0e1a, #08121e, #0f1722);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite, fadeOut 1s ease-in-out 2s forwards;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .splash::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.05"><path fill="none" stroke="white" stroke-width="1" d="M10 10 L90 10 M10 20 L90 20 M10 30 L90 30 M10 40 L90 40 M10 50 L90 50 M10 60 L90 60 M10 70 L90 70 M10 80 L90 80 M10 90 L90 90 M10 10 L10 90 M20 10 L20 90 M30 10 L30 90 M40 10 L40 90 M50 10 L50 90 M60 10 L60 90 M70 10 L70 90 M80 10 L80 90 M90 10 L90 90"/></svg>');
            background-repeat: repeat;
            pointer-events: none;
        }
        .logo-container {
            text-align: center;
            animation: bounceIn 0.8s ease-out;
            z-index: 5;
        }
        .logo-image {
            width: 200px;
            height: auto;
            filter: drop-shadow(0 8px 16px rgba(0,229,200,0.3));
            animation: pulse 1.5s infinite;
        }
        .login-wrapper {
            display: none;
            width: 100%;
            max-width: 420px;
            padding: 20px;
            z-index: 10;
            animation: slideUp 0.5s ease-out;
        }
        .login-box {
            background: rgba(8,18,30,0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(0,229,200,0.3);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            transition: transform 0.3s ease;
        }
        .login-box:hover {
            transform: translateY(-5px);
            border-color: rgba(0,229,200,0.6);
        }
        h2 {
            text-align: center;
            color: #00e5c8;
            font-family: 'Space Grotesk', monospace;
            font-size: 28px;
            margin-bottom: 24px;
            letter-spacing: 1px;
        }
        .form-group {
            width: 100%;
            margin: 18px 0;
        }
        input, select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(0,229,200,0.3);
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #fff;
            transition: all 0.3s;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #00e5c8;
            background: rgba(255,255,255,0.12);
            box-shadow: 0 0 8px rgba(0,229,200,0.5);
        }
        select option {
            background: #0a0e1a;
            color: #fff;
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #00e5c8, #00b894);
            border: none;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #000;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 8px;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,229,200,0.4);
            opacity: 0.9;
        }
        .error {
            color: #ff6b6b;
            text-align: center;
            margin-bottom: 15px;
            font-size: 13px;
            background: rgba(255,107,107,0.1);
            padding: 8px;
            border-radius: 8px;
            border-left: 3px solid #ff6b6b;
        }
        .forgot-link {
            text-align: right;
            margin-top: 8px;
            font-size: 12px;
        }
        .forgot-link a {
            color: #00e5c8;
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        .forgot-link a:hover {
            opacity: 1;
            text-decoration: underline;
        }
        /* Modals */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(8px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: rgba(8,18,30,0.95);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(0,229,200,0.4);
            border-radius: 24px;
            padding: 28px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        .modal-content h3 {
            color: #00e5c8;
            margin-bottom: 16px;
        }
        .modal-buttons {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }
        .modal-buttons button {
            margin-top: 0;
        }
        .close-modal {
            background: #6c757d;
        }
        .modal-message {
            font-size: 13px;
            margin-top: 12px;
            padding: 8px;
            border-radius: 8px;
        }
        .modal-message.success {
            background: rgba(6,214,160,0.15);
            color: #06d6a0;
        }
        .modal-message.error {
            background: rgba(255,107,107,0.15);
            color: #ff6b6b;
        }
        /* Animations */
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.1); }
            70% { transform: scale(0.95); }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        @keyframes gradientShift {
            0% { background-position: 0% 0%; }
            50% { background-position: 100% 100%; }
            100% { background-position: 0% 0%; }
        }
        @keyframes fadeOut {
            to { opacity: 0; visibility: hidden; }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 480px) {
            .login-wrapper { padding: 16px; }
            .login-box { padding: 24px; }
            h2 { font-size: 24px; }
            .logo-image { width: 150px; }
        }
    </style>
</head>
<body>

    <div class="splash" id="splash">
        <div class="logo-container">
            <img src="logo/logo.png" alt="AR TECH SOLUTION" class="logo-image" onerror="this.src='https://via.placeholder.com/200?text=AR+TECH'">
        </div>
    </div>

    <div class="login-wrapper" id="loginPanel">
        <div class="login-box">
            <h2>Welcome Back</h2>
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
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <button type="submit">Login →</button>
                </div>
                <div class="forgot-link">
                    <a href="#" id="forgotPasswordLink">Forgot Password?</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 1: Request OTP -->
    <div class="modal" id="otpRequestModal">
        <div class="modal-content">
            <h3>Reset Password</h3>
            <p style="font-size:13px; color:#aaa; margin-bottom:12px;">Enter your email and role. A 6-digit code will be sent to your email.</p>
            <div class="form-group">
                <input type="email" id="reset_email" placeholder="Email Address">
            </div>
            <div class="form-group">
                <select id="reset_role">
                    <option value="">Select Role</option>
                    <option value="Admin">Admin</option>
                    <option value="User">User</option>
                </select>
            </div>
            <div class="modal-buttons">
                <button id="sendCodeBtn">Send Code</button>
                <button class="close-modal" onclick="closeRequestModal()">Cancel</button>
            </div>
            <div id="requestMessage" style="display:none;"></div>
        </div>
    </div>

    <!-- Modal 2: Verify OTP & Set New Password -->
    <div class="modal" id="otpVerifyModal">
        <div class="modal-content">
            <h3>Enter Verification Code</h3>
            <p style="font-size:13px; color:#aaa; margin-bottom:12px;">A 6-digit code was sent to your email. Enter it below and set a new password.</p>
            <div class="form-group">
                <input type="text" id="reset_code" placeholder="6-digit code" maxlength="6" pattern="[0-9]{6}">
            </div>
            <div class="form-group">
                <input type="password" id="new_password" placeholder="New Password">
            </div>
            <div class="form-group">
                <input type="password" id="confirm_password" placeholder="Confirm Password">
            </div>
            <input type="hidden" id="verify_email">
            <input type="hidden" id="verify_role">
            <div class="modal-buttons">
                <button id="verifyCodeBtn">Reset Password</button>
                <button class="close-modal" onclick="closeVerifyModal()">Cancel</button>
            </div>
            <div id="verifyMessage" style="display:none;"></div>
        </div>
    </div>

    <script>
        const splash = document.getElementById('splash');
        const loginPanel = document.getElementById('loginPanel');

        splash.addEventListener('animationend', function() {
            splash.style.display = 'none';
            loginPanel.style.display = 'block';
        });

        // Modal references
        const requestModal = document.getElementById('otpRequestModal');
        const verifyModal = document.getElementById('otpVerifyModal');
        const forgotLink = document.getElementById('forgotPasswordLink');
        const sendCodeBtn = document.getElementById('sendCodeBtn');
        const verifyCodeBtn = document.getElementById('verifyCodeBtn');

        let storedEmail = '';
        let storedRole = '';

        // Step 1: Open request modal
        forgotLink.addEventListener('click', function(e) {
            e.preventDefault();
            requestModal.classList.add('active');
        });

        // Step 1: Send OTP request
        sendCodeBtn.addEventListener('click', function() {
            const email = document.getElementById('reset_email').value.trim();
            const role = document.getElementById('reset_role').value;
            const msgDiv = document.getElementById('requestMessage');

            if (!email || !role) {
                showMessage(msgDiv, 'Please fill both email and role.', 'error');
                return;
            }

            sendCodeBtn.disabled = true;
            sendCodeBtn.textContent = 'Sending...';

            fetch('forgot_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent(email) + '&role=' + encodeURIComponent(role)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Store email and role for verification step
                    storedEmail = email;
                    storedRole = role;
                    showMessage(msgDiv, data.message, 'success');
                    // Close request modal and open verify modal after short delay
                    setTimeout(() => {
                        closeRequestModal();
                        document.getElementById('verify_email').value = email;
                        document.getElementById('verify_role').value = role;
                        verifyModal.classList.add('active');
                        document.getElementById('reset_code').value = '';
                        document.getElementById('new_password').value = '';
                        document.getElementById('confirm_password').value = '';
                    }, 1500);
                } else {
                    showMessage(msgDiv, data.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showMessage(msgDiv, 'Network error. Please try again.', 'error');
            })
            .finally(() => {
                sendCodeBtn.disabled = false;
                sendCodeBtn.textContent = 'Send Code';
            });
        });

        // Step 2: Verify OTP and update password
        verifyCodeBtn.addEventListener('click', function() {
            const code = document.getElementById('reset_code').value.trim();
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            const email = document.getElementById('verify_email').value;
            const role = document.getElementById('verify_role').value;
            const msgDiv = document.getElementById('verifyMessage');

            if (!code || code.length !== 6 || !/^\d+$/.test(code)) {
                showMessage(msgDiv, 'Please enter a valid 6-digit code.', 'error');
                return;
            }
            if (!newPass || newPass.length < 4) {
                showMessage(msgDiv, 'Password must be at least 4 characters.', 'error');
                return;
            }
            if (newPass !== confirmPass) {
                showMessage(msgDiv, 'Passwords do not match.', 'error');
                return;
            }

            verifyCodeBtn.disabled = true;
            verifyCodeBtn.textContent = 'Processing...';

            fetch('reset_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent(email) + '&role=' + encodeURIComponent(role) +
                      '&code=' + encodeURIComponent(code) + '&new_password=' + encodeURIComponent(newPass)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showMessage(msgDiv, data.message, 'success');
                    setTimeout(() => {
                        closeVerifyModal();
                        // Optionally auto-fill login form with email
                        document.querySelector('input[name="email"]').value = email;
                        document.querySelector('select[name="role"]').value = role;
                    }, 2000);
                } else {
                    showMessage(msgDiv, data.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showMessage(msgDiv, 'Network error. Please try again.', 'error');
            })
            .finally(() => {
                verifyCodeBtn.disabled = false;
                verifyCodeBtn.textContent = 'Reset Password';
            });
        });

        function showMessage(container, msg, type) {
            container.textContent = msg;
            container.className = 'modal-message ' + type;
            container.style.display = 'block';
            setTimeout(() => {
                container.style.display = 'none';
            }, 5000);
        }

        function closeRequestModal() {
            requestModal.classList.remove('active');
            document.getElementById('requestMessage').style.display = 'none';
            document.getElementById('reset_email').value = '';
            document.getElementById('reset_role').value = '';
        }

        function closeVerifyModal() {
            verifyModal.classList.remove('active');
            document.getElementById('verifyMessage').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target === requestModal) closeRequestModal();
            if (event.target === verifyModal) closeVerifyModal();
        }
    </script>
</body>
</html>