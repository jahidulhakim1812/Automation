<?php
session_start();
if (!isset($_SESSION["email"]) || $_SESSION["role"] !== "User") {
  header("Location: login.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>User Dashboard</title>
  <style>
   * { box-sizing: border-box; }
    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      background: url('uploads/banner.jpg') no-repeat center center fixed;
      background-size: cover;
    }

    /* Navbar */
    .navbar {
      background-color: #1a1a1a;
      color: white;
      padding: 15px 30px;
      font-size: 22px;
      display: flex;
      justify-content: center;
      align-items: center;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1000;
      box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    }
    .logout-btn {
      position: absolute;
      right: 10px;
      background: linear-gradient(135deg, #ff4d4d, #cc0000);
      color: white;
      padding: 8px 15px;
      text-decoration: none;
      border-radius: 25px;
      font-size: 15px;
      box-shadow: 0 3px 6px rgba(0,0,0,0.2);
      transition: all 0.3s ease;
    }
    .logout-btn:hover {
      background: linear-gradient(135deg, #ff6666, #e60000);
      transform: scale(1.05);
    }

    /* Sidebar */
    .side-nav {
      position: fixed;
      top: 60px;
      left: 0;
      width: 220px;
      height: calc(100% - 60px);
      background-color: #2c3e50;
      padding-top: 20px;
      z-index: 999;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      box-shadow: 2px 0 5px rgba(0,0,0,0.2);
      transition: transform 0.3s ease;
      overflow-y: auto;
    }
    .side-nav.collapsed {
      transform: translateX(-220px);
    }
    .side-nav a, .menu-toggle {
      color: white;
      text-decoration: none;
      padding: 12px 25px;
      width: 100%;
      font-weight: bold;
      transition: background 0.3s ease;
      border-left: 4px solid transparent;
      cursor: pointer;
    }
    .side-nav a:hover, .menu-toggle:hover {
      background-color: #34495e;
      border-left: 4px solid #1abc9c;
    }

    /* Submenus */
    .menu-group { width: 100%; }
    .submenu {
      display: none;
      flex-direction: column;
      background-color: #34495e;
    }
    .submenu a {
      color: white;
      padding: 10px 40px;
      text-decoration: none;
      font-weight: normal;
      transition: background 0.3s ease;
    }
    .submenu a:hover {
      background-color: #3d566e;
    }
    .menu-group.active .submenu {
      display: flex;
    }

    /* Sidebar toggle arrow */
    .toggle-arrow {
      position: fixed;
      top: 70px;
      left: 220px;
      background-color: #1abc9c;
      color: white;
      padding: 6px 10px;
      border-radius: 0 5px 5px 0;
      cursor: pointer;
      z-index: 1001;
      font-size: 18px;
      transition: left 0.3s ease;
    }
    .toggle-arrow.collapsed {
      left: 0;
    }

    /* Main Content (empty background only) */
    .main-content {
      margin-left: 240px;
      padding: 80px 20px 80px;
      transition: margin-left 0.3s ease;
      color: white;
      text-align: center;
      min-height: 100vh;
    }
    .main-content.collapsed {
      margin-left: 40px;
    }

    /* Footer */
    .footer {
      background-color: #1a1a1a;
      color: white;
      text-align: center;
      padding: 15px;
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      font-weight: bold;
      box-shadow: 0 -2px 5px rgba(0,0,0,0.2);
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <div class="navbar">
    <strong>AR TECH SOLUTION</strong>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>

  <!-- Sidebar -->
  <div class="side-nav" id="sidebar">
    <a href="user_dashboard.php">📊 Dashboard</a>

    <div class="menu-group">
      <div class="menu-toggle">💵 Account ▾</div>
      <div class="submenu">
        
        <a href="user_account_report.php">Account Report</a>
        
      </div>
    </div>
    <div class="menu-group">
      <div class="menu-toggle">📆 Attendance ▾</div>
      <div class="submenu">
        
        <a href="user_attendance_report.php">View Attendance Report</a>
      </div>
    </div>

    <div class="menu-group">
      <div class="menu-toggle">📜 Certificate ▾</div>
      <div class="submenu">
        
        <a href="user_certificate.php">View Certificate</a>
      </div>
    </div>

    <a href="routine_generator.php">🕒 Routine</a>
  </div>

  <!-- Sidebar Toggle Button -->
  <div class="toggle-arrow" id="toggleBtn">◀</div>

  <!-- Main Content (empty) -->
  <div class="main-content" id="mainContent">
    <!-- No content here, only background image will show -->
  </div>

  <!-- Footer -->
  <div class="footer">
    &copy; <?php echo date("Y"); ?> Freelancing Students Management System | All Rights Reserved
  </div>

  <script>
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleBtn');
    const mainContent = document.getElementById('mainContent');

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      toggleBtn.classList.toggle('collapsed');
      mainContent.classList.toggle('collapsed');
      toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '▶' : '◀';
    });

    document.querySelectorAll('.menu-toggle').forEach(toggle => {
      toggle.addEventListener('click', () => {
        toggle.parentElement.classList.toggle('active');
      });
    });
  </script>

</body>
</html>
