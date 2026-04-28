<?php
session_start();
require_once __DIR__ . '/../config.php';

// Authentication check
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM spins WHERE id = ?");
    $stmt->execute([$delete_id]);
    
    // Store flash message
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Spin record deleted successfully.'];
    // Redirect to the same page with all current parameters
    $queryParams = $_GET;
    unset($queryParams['page']); // reset to page 1 after delete
    $redirect = 'spin_history.php?' . http_build_query($queryParams);
    header('Location: ' . $redirect);
    exit;
}

// Flash message helper
function displayFlash() {
    if (isset($_SESSION['flash'])) {
        $class = $_SESSION['flash']['type'] === 'success' ? 'message' : 'error';
        echo '<div class="' . $class . '">' . htmlspecialchars($_SESSION['flash']['msg']) . '</div>';
        unset($_SESSION['flash']);
    }
}

// CSV Export (must be before any output)
if (isset($_GET['export']) && $_GET['export'] == 1) {
    $search = $_GET['search'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $whereClauses = [];
    $params = [];
    if (!empty($search)) {
        $whereClauses[] = "(name LIKE ? OR mobile LIKE ? OR prize_label LIKE ?)";
        $like = "%$search%";
        $params = [$like, $like, $like];
    }
    if (!empty($date_from)) {
        $whereClauses[] = "DATE(spin_time) >= ?";
        $params[] = $date_from;
    }
    if (!empty($date_to)) {
        $whereClauses[] = "DATE(spin_time) <= ?";
        $params[] = $date_to;
    }
    $whereSQL = empty($whereClauses) ? "" : "WHERE " . implode(" AND ", $whereClauses);
    $stmt = $pdo->prepare("SELECT id, name, mobile, prize_label, spin_time FROM spins $whereSQL ORDER BY spin_time DESC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="spins_export_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Mobile', 'Prize', 'Spin Time']);
    foreach ($rows as $row) fputcsv($output, $row);
    fclose($output);
    exit;
}

// Get filters and pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(name LIKE ? OR mobile LIKE ? OR prize_label LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if (!empty($date_from)) {
    $whereClauses[] = "DATE(spin_time) >= ?";
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $whereClauses[] = "DATE(spin_time) <= ?";
    $params[] = $date_to;
}
$whereSQL = empty($whereClauses) ? "" : "WHERE " . implode(" AND ", $whereClauses);

// Count total for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM spins $whereSQL");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Fetch records for current page
$stmt = $pdo->prepare("SELECT * FROM spins $whereSQL ORDER BY spin_time DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$spins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>SpinMaster | Spin History</title>
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
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            --hover-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.5);
        }

        body {
            font-family: 'Inter', 'Poppins', system-ui, sans-serif;
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
        }

        .sidebar-header p {
            font-size: 0.8rem;
            color: var(--text-secondary);
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

        /* Top bar with toggles */
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

        .sidebar-toggle-btn {
            background: var(--accent);
            color: #1e2c34;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .dark-mode-btn {
            background: rgba(128, 128, 128, 0.2);
            backdrop-filter: blur(4px);
        }

        .date {
            font-size: 0.9rem;
            color: var(--text-secondary);
            background: rgba(0, 0, 0, 0.05);
            padding: 5px 12px;
            border-radius: 40px;
        }

        /* History card */
        .history-card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 28px 24px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .card-header h2 {
            font-size: 1.6rem;
            color: var(--accent);
            margin-bottom: 20px;
        }

        .filters {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 25px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-secondary);
        }

        .filters input, .filters select {
            padding: 10px 16px;
            border-radius: 60px;
            border: none;
            background: rgba(255, 255, 240, 0.9);
            font-size: 0.9rem;
            min-width: 160px;
        }

        .btn {
            background: linear-gradient(115deg, #F9C74F, #F9844A);
            border: none;
            padding: 10px 20px;
            border-radius: 60px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
            color: #1e2c34;
        }

        .btn-export {
            background: #2d6a4f;
            color: white;
        }
        .btn-export:hover {
            background: #1e4a3a;
        }

        .btn-delete {
            background: #ff6b6b;
            color: white;
        }
        .btn-delete:hover {
            background: #ff4757;
        }

        .table-wrapper {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            color: var(--accent);
            font-weight: 600;
        }

        .message {
            background: #2d6a4f;
            padding: 12px 20px;
            border-radius: 60px;
            margin-bottom: 20px;
            color: white;
        }

        .error {
            background: #ba2d2d;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            padding: 8px 16px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 40px;
            text-decoration: none;
            color: var(--text-primary);
            transition: 0.2s;
        }

        .pagination a:hover {
            background: var(--accent);
            color: #1e2c34;
        }

        .pagination .active {
            background: var(--accent);
            color: #1e2c34;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px 15px;
            }
            .sidebar {
                width: 260px;
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
            .filters {
                flex-direction: column;
            }
            .filter-group input {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<button class="icon-btn sidebar-toggle-btn" id="mobileSidebarToggle" style="position: fixed; top: 15px; left: 15px; z-index: 1100; display: none;">⟫</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>🎡 SpinMaster</h2>
        <p>Spin History</p>
    </div>
    <ul class="nav-menu">
        <li class="nav-item"><a href="admin_dashboard.php" class="nav-link"><span class="icon">📊</span> Dashboard</a></li>
        <li class="nav-item"><a href="prize_management.php" class="nav-link"><span class="icon">🎯</span> Prize Management</a></li>
        <li class="nav-item"><a href="spin_history.php" class="nav-link active"><span class="icon">📜</span> Spin History</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link logout-link"><span class="icon">🚪</span> Logout</a>
    </div>
</div>

<div class="main-content" id="mainContent">
    <div class="top-bar">
        <div class="top-bar-left">
            <h3>📜 Spin History</h3>
        </div>
        <div class="top-bar-right">
            <button class="icon-btn dark-mode-btn" id="darkModeToggleBtn" title="Dark/Light mode">🌙</button>
            <button class="icon-btn sidebar-toggle-btn" id="desktopSidebarToggle" title="Toggle Sidebar">⟪</button>
            <div class="date">📅 <?php echo date('F j, Y'); ?></div>
        </div>
    </div>

    <?php displayFlash(); ?>

    <div class="history-card">
        <div class="card-header">
            <h2>🎲 All Spins</h2>
        </div>

        <form method="GET" class="filters">
            <div class="filter-group">
                <label>🔍 Search</label>
                <input type="text" name="search" placeholder="Name, Mobile or Prize..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <label>📅 From Date</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            <div class="filter-group">
                <label>📅 To Date</label>
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            <div class="filter-group">
                <button type="submit" class="btn">🔎 Filter</button>
            </div>
            <div class="filter-group">
                <a href="?export=1&<?php echo http_build_query($_GET); ?>" class="btn btn-export">📥 Export CSV</a>
            </div>
        </form>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Prize</th>
                        <th>Date & Time</th>
                        <th style="width: 80px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($spins)): ?>
                        <tr><td colspan="6" class="no-data">No spins found matching your criteria.</td></tr>
                    <?php else: foreach ($spins as $spin): ?>
                        <tr>
                            <td><?php echo $spin['id']; ?></td>
                            <td><?php echo htmlspecialchars($spin['name']); ?></td>
                            <td><?php echo htmlspecialchars($spin['mobile']); ?></td>
                            <td><?php echo htmlspecialchars($spin['prize_label']); ?></td>
                            <td><?php echo date('d M Y, h:i A', strtotime($spin['spin_time'])); ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Are you sure you want to delete this spin record? This action cannot be undone.');">
                                    <input type="hidden" name="delete_id" value="<?php echo $spin['id']; ?>">
                                    <button type="submit" class="btn btn-delete" style="padding: 6px 12px; font-size: 0.8rem;">🗑️ Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Sidebar toggle logic
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

    // Dark mode toggle
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
    });
</script>
</body>
</html>