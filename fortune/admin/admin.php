<?php
session_start();
require_once __DIR__ . '/../config.php';

// Authentication check
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Fetch dashboard metrics
$totalSpins = $pdo->query("SELECT COUNT(*) FROM spins")->fetchColumn();
$totalPlayers = $pdo->query("SELECT COUNT(DISTINCT mobile) FROM spins")->fetchColumn();
$activePrizes = $pdo->query("SELECT COUNT(*) FROM segments")->fetchColumn();

// Count 100% discount winners (if any exist)
$hundredPercent = $pdo->query("SELECT COUNT(*) FROM spins WHERE prize_label LIKE '%100%' OR prize_label LIKE '%100% discount%'")->fetchColumn();
// Count 50% discount winners
$fiftyPercent = $pdo->query("SELECT COUNT(*) FROM spins WHERE prize_label LIKE '%50%' OR prize_label LIKE '%50% off%'")->fetchColumn();

// Bar chart: winning ratio per prize (top 10)
$stmt = $pdo->query("SELECT prize_label, COUNT(*) as count FROM spins GROUP BY prize_label ORDER BY count DESC LIMIT 10");
$prizeCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$barLabels = [];
$barData = [];
foreach ($prizeCounts as $row) {
    $barLabels[] = addslashes($row['prize_label']);
    $barData[] = $row['count'];
}
$barLabelsJson = json_encode($barLabels);
$barDataJson = json_encode($barData);

// Line chart: spins per day (last 30 days)
$stmt = $pdo->query("
    SELECT DATE(spin_time) as date, COUNT(*) as count
    FROM spins
    WHERE spin_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(spin_time)
    ORDER BY date ASC
");
$lineLabels = [];
$lineCounts = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $lineLabels[] = $row['date'];
    $lineCounts[] = $row['count'];
}
$lineLabelsJson = json_encode($lineLabels);
$lineCountsJson = json_encode($lineCounts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>SpinMaster | Professional Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Light mode (default) */
        :root {
            --bg-gradient: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            --sidebar-bg: rgba(255, 255, 255, 0.96);
            --sidebar-border: rgba(0, 0, 0, 0.08);
            --card-bg: rgba(255, 255, 255, 0.75);
            --text-primary: #1a2c3e;
            --text-secondary: #4a627a;
            --accent: #f9c74f;
            --accent-dark: #f9844a;
            --border-color: rgba(0, 0, 0, 0.08);
            --chart-grid: rgba(0, 0, 0, 0.1);
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
            --hover-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.1);
        }

        body.dark {
            --bg-gradient: radial-gradient(circle at 10% 20%, #0f1f2a, #050e14);
            --sidebar-bg: rgba(10, 20, 28, 0.96);
            --sidebar-border: rgba(255, 215, 100, 0.2);
            --card-bg: rgba(20, 35, 45, 0.65);
            --text-primary: #eef5ff;
            --text-secondary: #b0c4de;
            --accent: #FFD966;
            --accent-dark: #ffb347;
            --border-color: rgba(255, 215, 100, 0.2);
            --chart-grid: rgba(255, 255, 255, 0.1);
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            --hover-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.5);
        }

        body {
            font-family: 'Inter', 'Poppins', system-ui, -apple-system, sans-serif;
            background: var(--bg-gradient);
            overflow-x: hidden;
            color: var(--text-primary);
            transition: background 0.3s ease, color 0.2s ease;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100%;
            background: var(--sidebar-bg);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--sidebar-border);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            transform: translateX(-280px);
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 20px;
        }

        .sidebar-header h2 {
            font-size: 1.8rem;
            background: linear-gradient(135deg, #FFE5A3, #FFB347);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
        }

        .sidebar-header p {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 5px;
        }

        .nav-menu {
            flex: 1;
            list-style: none;
        }

        .nav-item {
            margin: 8px 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 20px;
            border-radius: 60px;
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(249, 199, 79, 0.15);
            color: var(--accent);
        }

        .nav-link .icon {
            font-size: 1.3rem;
        }

        .sidebar-footer {
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
            margin-top: auto;
        }

        .logout-link {
            color: #ff8a8a;
        }

        .logout-link:hover {
            background: rgba(255, 100, 100, 0.15);
            color: #ff7b7b;
        }

        /* Main content */
        .main-content {
            margin-left: 280px;
            transition: margin-left 0.3s;
            padding: 25px 35px;
            min-height: 100vh;
        }

        .main-content.expanded {
            margin-left: 0;
        }

        /* Top bar with toggle buttons */
        .top-bar {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border-radius: 60px;
            padding: 12px 25px;
            margin-bottom: 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .top-bar-left h3 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .top-bar-right {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        /* Distinct button styles */
        .icon-btn {
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.2s;
            color: var(--text-primary);
            width: 40px;
            height: 40px;
        }

        .icon-btn:hover {
            background: rgba(249, 199, 79, 0.2);
            transform: scale(1.05);
        }

        /* Sidebar toggle – distinct chevron style */
        .sidebar-toggle-btn {
            background: var(--accent);
            color: #1e2c34;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .sidebar-toggle-btn:hover {
            background: var(--accent-dark);
        }

        /* Dark mode toggle – distinct styling */
        .dark-mode-btn {
            background: rgba(128, 128, 128, 0.2);
            backdrop-filter: blur(4px);
        }

        .dark-mode-btn:hover {
            background: rgba(249, 199, 79, 0.3);
        }

        .date {
            font-size: 0.9rem;
            color: var(--text-secondary);
            background: rgba(0, 0, 0, 0.05);
            padding: 5px 12px;
            border-radius: 40px;
        }

        /* Stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
            margin-bottom: 45px;
        }

        .stat-card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border-radius: 28px;
            padding: 24px 16px;
            text-align: center;
            border: 1px solid var(--border-color);
            transition: all 0.25s;
            box-shadow: var(--shadow);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            box-shadow: var(--hover-shadow);
        }

        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--accent);
            margin-bottom: 8px;
        }

        .stat-card .label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Charts */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .chart-container {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 20px 20px 15px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
            transition: transform 0.2s;
        }

        .chart-container:hover {
            transform: translateY(-3px);
            box-shadow: var(--hover-shadow);
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        canvas {
            max-height: 320px;
            width: 100%;
        }

        /* Responsive */
        @media (max-width: 1000px) {
            .charts-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px 15px;
            }
            .sidebar {
                width: 260px;
            }
            .stats-grid {
                gap: 15px;
            }
            .top-bar {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
                text-align: center;
            }
            .top-bar-right {
                justify-content: center;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: var(--border-color);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--accent);
            border-radius: 10px;
        }
    </style>
</head>
<body>
<button class="icon-btn sidebar-toggle-btn" id="mobileSidebarToggle" style="position: fixed; top: 15px; left: 15px; z-index: 1100; display: none;">⟫</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>🎡 SpinMaster</h2>
        <p>Enterprise Admin</p>
    </div>
    <ul class="nav-menu">
        <li class="nav-item"><a href="admin_dashboard.php" class="nav-link active"><span class="icon">📊</span> Dashboard</a></li>
        <li class="nav-item"><a href="prize_management.php" class="nav-link"><span class="icon">🎯</span> Prize Management</a></li>
        <li class="nav-item"><a href="spin_history.php" class="nav-link"><span class="icon">📜</span> Spin History</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link logout-link"><span class="icon">🚪</span> Logout</a>
    </div>
</div>

<div class="main-content" id="mainContent">
    <div class="top-bar">
        <div class="top-bar-left">
            <h3>👋 Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></h3>
        </div>
        <div class="top-bar-right">
            <button class="icon-btn dark-mode-btn" id="darkModeToggleBtn" title="Dark/Light mode">🌙</button>
            <button class="icon-btn sidebar-toggle-btn" id="desktopSidebarToggle" title="Toggle Sidebar">⟪</button>
            <div class="date">📅 <?php echo date('F j, Y'); ?></div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card"><div class="number"><?php echo $totalSpins; ?></div><div class="label">Total Spins</div></div>
        <div class="stat-card"><div class="number"><?php echo $totalPlayers; ?></div><div class="label">Unique Players</div></div>
        <div class="stat-card"><div class="number"><?php echo $activePrizes; ?></div><div class="label">Active Prizes</div></div>
        <div class="stat-card"><div class="number"><?php echo $hundredPercent; ?></div><div class="label">100% Price Holders</div></div>
        <div class="stat-card"><div class="number"><?php echo $fiftyPercent; ?></div><div class="label">50% Price Holders</div></div>
    </div>

    <!-- Charts -->
    <div class="charts-grid">
        <div class="chart-container">
            <div class="chart-title">🏆 Winning Ratio (Top prizes)</div>
            <canvas id="barChart"></canvas>
        </div>
        <div class="chart-container">
            <div class="chart-title">📈 Spins Over Time (Last 30 days)</div>
            <canvas id="lineChart"></canvas>
        </div>
    </div>
</div>

<script>
    // Sidebar Toggle Logic
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const desktopToggle = document.getElementById('desktopSidebarToggle');
    const mobileToggle = document.getElementById('mobileSidebarToggle');

    function toggleSidebar() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
        const collapsed = sidebar.classList.contains('collapsed');
        if (desktopToggle) desktopToggle.innerHTML = collapsed ? '⟫' : '⟪';
        if (mobileToggle) mobileToggle.innerHTML = collapsed ? '⟫' : '⟪';
    }

    if (desktopToggle) desktopToggle.addEventListener('click', toggleSidebar);
    if (mobileToggle) mobileToggle.addEventListener('click', toggleSidebar);

    // Responsive: auto-collapse on mobile, show/hide appropriate toggle buttons
    function handleResponsive() {
        const isMobile = window.innerWidth <= 768;
        if (isMobile) {
            if (!sidebar.classList.contains('collapsed')) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
                if (desktopToggle) desktopToggle.innerHTML = '⟫';
                if (mobileToggle) mobileToggle.innerHTML = '⟫';
            }
            if (mobileToggle) mobileToggle.style.display = 'flex';
            if (desktopToggle) desktopToggle.style.display = 'none';
        } else {
            if (sidebar.classList.contains('collapsed')) {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
                if (desktopToggle) desktopToggle.innerHTML = '⟪';
                if (mobileToggle) mobileToggle.innerHTML = '⟪';
            }
            if (mobileToggle) mobileToggle.style.display = 'none';
            if (desktopToggle) desktopToggle.style.display = 'flex';
        }
    }

    window.addEventListener('resize', handleResponsive);
    handleResponsive();

    // Dark Mode Toggle
    const darkModeToggle = document.getElementById('darkModeToggleBtn');
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark');
        darkModeToggle.innerHTML = '☀️';
    } else {
        darkModeToggle.innerHTML = '🌙';
    }
    darkModeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark');
        const isDark = document.body.classList.contains('dark');
        localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
        darkModeToggle.innerHTML = isDark ? '☀️' : '🌙';
        refreshChartColors();
    });

    // Helper for CSS variables
    function getCssVar(name) {
        return getComputedStyle(document.body).getPropertyValue(name).trim();
    }

    let barChart, lineChart;

    function refreshChartColors() {
        const textColor = getCssVar('--text-primary');
        const gridColor = getCssVar('--chart-grid');
        if (barChart) {
            barChart.options.scales.y.ticks.color = textColor;
            barChart.options.scales.x.ticks.color = textColor;
            barChart.options.plugins.legend.labels.color = textColor;
            barChart.update();
        }
        if (lineChart) {
            lineChart.options.scales.y.ticks.color = textColor;
            lineChart.options.scales.x.ticks.color = textColor;
            lineChart.options.plugins.legend.labels.color = textColor;
            lineChart.update();
        }
    }

    // Bar Chart
    const barCtx = document.getElementById('barChart').getContext('2d');
    barChart = new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: <?php echo $barLabelsJson; ?>,
            datasets: [{
                label: 'Wins',
                data: <?php echo $barDataJson; ?>,
                backgroundColor: '#F9C74F',
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { labels: { color: getCssVar('--text-primary') } },
                tooltip: { backgroundColor: '#1e2f3a', titleColor: '#FFD966', bodyColor: '#fff' }
            },
            scales: {
                y: { ticks: { color: getCssVar('--text-secondary') }, grid: { color: getCssVar('--chart-grid') } },
                x: { ticks: { color: getCssVar('--text-secondary'), maxRotation: 45, minRotation: 45 }, grid: { display: false } }
            }
        }
    });

    // Line Chart
    const lineCtx = document.getElementById('lineChart').getContext('2d');
    lineChart = new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: <?php echo $lineLabelsJson; ?>,
            datasets: [{
                label: 'Spins',
                data: <?php echo $lineCountsJson; ?>,
                borderColor: '#FFD966',
                backgroundColor: 'rgba(255,217,102,0.1)',
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#FFA559',
                pointBorderColor: '#fff',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { labels: { color: getCssVar('--text-primary') } },
                tooltip: { backgroundColor: '#1e2f3a', titleColor: '#FFD966', bodyColor: '#fff' }
            },
            scales: {
                y: { ticks: { color: getCssVar('--text-secondary') }, grid: { color: getCssVar('--chart-grid') } },
                x: { ticks: { color: getCssVar('--text-secondary'), maxRotation: 45, minRotation: 45 }, grid: { display: false } }
            }
        }
    });
</script>
</body>
</html>