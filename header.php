<?php
// header.php
session_start();
// Check if user is logged in (optional, but include for security)
if (!isset($_SESSION["role"])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo isset($pageTitle) ? $pageTitle : "Dashboard"; ?></title>
    <style>
        /* Global CSS Variables for Theme */
        :root {
            --bg-primary: #f0f2f5;
            --bg-secondary: #ffffff;
            --text-primary: #333;
            --text-secondary: #555;
            --navbar-bg: #1e1e2f;
            --navbar-text: #fff;
            --sidebar-bg: #2c3e50;
            --sidebar-text: #fff;
            --card-bg: #ffffff;
            --card-shadow: 0 4px 6px rgba(0,0,0,0.1);
            --border-color: #ddd;
            --footer-bg: #1a1a1a;
            --footer-text: #fff;
            --toggle-bg: #1abc9c;
            --toggle-color: #fff;
        }
        body.dark {
            --bg-primary: #121212;
            --bg-secondary: #1e1e2e;
            --text-primary: #e0e0e0;
            --text-secondary: #b0b0b0;
            --navbar-bg: #0a0a0f;
            --navbar-text: #fff;
            --sidebar-bg: #1e1e2a;
            --sidebar-text: #e0e0e0;
            --card-bg: #2a2a3a;
            --card-shadow: 0 4px 6px rgba(0,0,0,0.3);
            --border-color: #3a3a4a;
            --footer-bg: #0a0a0f;
            --footer-text: #e0e0e0;
            --toggle-bg: #ff9800;
            --toggle-color: #121212;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: background 0.3s, color 0.3s;
        }

        /* Navbar */
        .navbar {
            background-color: var(--navbar-bg);
            color: var(--navbar-text);
            padding: 15px 20px;
            font-size: 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .navbar .logo {
            font-weight: bold;
            letter-spacing: 1px;
        }
        .navbar .actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .logout-btn, .dark-mode-toggle {
            background: linear-gradient(135deg, #ff4d4d, #cc0000);
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 25px;
            font-size: 14px;
            transition: transform 0.2s, background 0.2s;
            cursor: pointer;
            border: none;
        }
        .dark-mode-toggle {
            background: var(--toggle-bg);
            color: var(--toggle-color);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .logout-btn:hover, .dark-mode-toggle:hover {
            transform: scale(1.05);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .navbar {
                padding: 12px 15px;
                font-size: 18px;
            }
            .logout-btn, .dark-mode-toggle {
                padding: 6px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo">AR TECH SOLUTION</div>
    <div class="actions">
        <button id="darkModeToggle" class="dark-mode-toggle">🌙 Dark</button>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<script>
    // Dark mode functionality
    const toggle = document.getElementById('darkModeToggle');
    const body = document.body;
    // Check localStorage for dark mode preference
    if (localStorage.getItem('darkMode') === 'enabled') {
        body.classList.add('dark');
        toggle.innerHTML = '☀️ Light';
    }
    toggle.addEventListener('click', () => {
        body.classList.toggle('dark');
        const isDark = body.classList.contains('dark');
        localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
        toggle.innerHTML = isDark ? '☀️ Light' : '🌙 Dark';
    });
</script>