<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// Get filter values
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$action_filter = isset($_GET['action']) ? trim($_GET['action']) : '';
$from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';

// Build query with filters
$sql = "SELECT * FROM admin_activity_log WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (admin_email LIKE ? OR admin_name LIKE ? OR action LIKE ? OR details LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    $types .= "ssss";
}
if (!empty($action_filter)) {
    $sql .= " AND action = ?";
    $params[] = $action_filter;
    $types .= "s";
}
if (!empty($from_date)) {
    $sql .= " AND DATE(created_at) >= ?";
    $params[] = $from_date;
    $types .= "s";
}
if (!empty($to_date)) {
    $sql .= " AND DATE(created_at) <= ?";
    $params[] = $to_date;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conn->query($sql);
    $logs = $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch distinct actions for dropdown
$actions_result = $conn->query("SELECT DISTINCT action FROM admin_activity_log ORDER BY action");
$actions = [];
while ($row = $actions_result->fetch_assoc()) {
    $actions[] = $row['action'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Activity Log — AR TECH SOLUTION</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<style>
    body {
        background-image: url('<?php echo $bg_image; ?>');
        background-size: cover;
        background-attachment: fixed;
        background-position: center;
        font-family: var(--sans);
    }
    body.dark-mode {
        --bg: rgba(0,0,0,0.9);
        --glass: rgba(0,0,0,0.5);
        --glass-border: rgba(255,255,255,0.1);
        --text: #e0e0e0;
    }
    body.dark-mode::before {
        background: rgba(0,0,0,0.85);
    }
</style>
<style>
:root {
    --bg: rgba(8,12,24,0.82);
    --glass: rgba(255,255,255,0.07);
    --glass-border: rgba(255,255,255,0.13);
    --glass-hover: rgba(255,255,255,0.13);
    --accent: #00e5c8;
    --accent2: #7b5ea7;
    --accent3: #ff6b6b;
    --accent4: #ffd166;
    --accent5: #06d6a0;
    --text: #e8eaf0;
    --muted: rgba(200,210,230,0.55);
    --card-radius: 18px;
    --sans: 'Plus Jakarta Sans', sans-serif;
    --mono: 'Space Grotesk', sans-serif;
    --nav-h: 64px;
    --sidebar-w: 230px;
    --shadow: 0 8px 32px rgba(0,0,0,0.35);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body::before {
    content: '';
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg,rgba(8,10,30,0.88) 0%,rgba(15,20,50,0.78) 50%,rgba(5,15,35,0.85) 100%);
    z-index: 0;
    pointer-events: none;
}

/* TOP NAV */
.topnav {
    position: fixed; top: 0; left: 0; right: 0; height: var(--nav-h);
    background: rgba(8,10,28,0.85);
    backdrop-filter: blur(18px);
    border-bottom: 1px solid var(--glass-border);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 24px;
    z-index: 1100;
}
.topnav-brand {
    display: flex; align-items: center; gap: 12px;
    font-family: var(--mono); font-size: 18px; font-weight: 700;
    letter-spacing: 0.5px; color: #fff;
}
.topnav-brand span { color: var(--accent); }
.brand-dot { width: 8px; height: 8px; background: var(--accent); border-radius: 50%; animation: pulse 2s infinite; }
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.4)} }
.topnav-right { display: flex; align-items: center; gap: 14px; }
.topnav-time { font-family: var(--mono); font-size: 13px; color: var(--muted); }
.logout-btn {
    background: linear-gradient(135deg,#e74c3c,#c0392b);
    color: #fff; padding: 7px 20px; border-radius: 40px;
    text-decoration: none; font-size: 13px; font-weight: 700;
    transition: opacity .2s;
}
.logout-btn:hover { opacity: .85; }
.hamburger {
    background: none; border: none; color: var(--text);
    font-size: 22px; cursor: pointer; display: none; padding: 4px;
}

/* SIDEBAR */
.sidebar {
    position: fixed; top: var(--nav-h); left: 0;
    width: var(--sidebar-w); height: calc(100vh - var(--nav-h));
    background: #08121e;
    border-right: 1px solid var(--glass-border);
    overflow-y: auto;
    z-index: 1050;
    transition: transform .3s cubic-bezier(.4,0,.2,1);
    padding-bottom: 40px;
}
.sidebar.collapsed { transform: translateX(-100%); }
.sidebar a, .menu-toggle {
    display: flex; align-items: center; gap: 10px;
    color: var(--muted); text-decoration: none;
    padding: 11px 20px; font-size: 13.5px; font-weight: 500;
    border-left: 3px solid transparent;
    transition: all .2s;
    cursor: pointer;
}
.sidebar a:hover, .menu-toggle:hover { color: #fff; background: var(--glass); border-left-color: var(--accent); }
.sidebar a.active { color: var(--accent); border-left-color: var(--accent); background: rgba(0,229,200,0.07); }
.submenu { display: none; flex-direction: column; background: rgba(0,0,0,0.2); }
.submenu a { padding: 9px 20px 9px 38px; font-size: 13px; }
.menu-group.open .submenu { display: flex; }
.menu-arrow { margin-left: auto; font-size: 11px; transition: transform .25s; }
.menu-group.open .menu-arrow { transform: rotate(180deg); }
.sidebar-divider { height: 1px; background: var(--glass-border); margin: 10px 16px; }

.sidebar-toggle-pill {
    position: fixed; top: calc(var(--nav-h) + 16px); left: var(--sidebar-w);
    width: 24px; height: 44px; background: var(--accent);
    border-radius: 0 10px 10px 0;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; z-index: 1060; font-size: 13px; color: #000;
    font-weight: 900; transition: left .3s cubic-bezier(.4,0,.2,1);
}
.sidebar-toggle-pill:hover { background: #00c9b0; }
.sidebar-toggle-pill.collapsed { left: 0; }

/* MAIN CONTENT */
.main {
    margin-left: var(--sidebar-w);
    padding: calc(var(--nav-h) + 24px) 24px 80px;
    position: relative; z-index: 1;
    transition: margin-left .3s cubic-bezier(.4,0,.2,1);
    min-height: 100vh;
}
.main.collapsed { margin-left: 0; }

/* SECTION TITLE */
.section-title {
    font-family: var(--mono); font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 2px; color: var(--accent);
    margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
}
.section-title::after { content: ''; flex: 1; height: 1px; background: var(--glass-border); }

/* FILTER CARD */
.filter-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 20px;
    margin-bottom: 28px;
}
.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
}
.filter-group {
    flex: 1;
    min-width: 150px;
}
.filter-group label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--muted);
    margin-bottom: 6px;
}
.filter-group input, .filter-group select {
    width: 100%;
    padding: 10px 12px;
    background: rgba(255,255,255,0.08);
    border: 1px solid var(--glass-border);
    border-radius: 10px;
    color: var(--text);
    font-size: 13px;
}
.filter-group input:focus, .filter-group select:focus {
    border-color: var(--accent);
}
.filter-buttons {
    display: flex;
    gap: 8px;
}
.filter-buttons button, .filter-buttons a {
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}
.filter-buttons button {
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    color: #000;
    border: none;
}
.filter-buttons a {
    background: linear-gradient(135deg, var(--accent3), #c0392b);
    color: #fff;
}

/* TABLE CARD */
.table-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 20px;
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
th, td {
    padding: 12px 8px;
    text-align: left;
    border-bottom: 1px solid var(--glass-border);
}
th {
    background: rgba(0,0,0,0.3);
    color: var(--accent);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 11px;
}
td {
    color: var(--text);
}
tr:hover td {
    background: rgba(255,255,255,0.03);
}
.empty-row td {
    text-align: center;
    padding: 40px;
    color: var(--muted);
}

/* FOOTER */
.footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(6,8,20,0.9);
    backdrop-filter: blur(10px);
    border-top: 1px solid var(--glass-border);
    text-align: center;
    padding: 12px;
    font-size: 12.5px;
    color: var(--muted);
    z-index: 900;
}

/* RESPONSIVE */
@media (max-width: 700px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.mobile-open { transform: translateX(0); }
    .sidebar-toggle-pill { display: none; }
    .hamburger { display: block; }
    .main { margin-left: 0 !important; padding-left: 16px; padding-right: 16px; }
    .filter-form { flex-direction: column; }
    .filter-group { width: 100%; }
    .filter-buttons { justify-content: stretch; }
    .filter-buttons button, .filter-buttons a { text-align: center; flex: 1; }
    th, td { padding: 8px 4px; font-size: 11px; }
}
</style>
</head>
<body class="<?php echo $dark_mode ? 'dark-mode' : ''; ?>">

<!-- TOP NAVIGATION -->
<nav class="topnav">
    <div style="display:flex;align-items:center;gap:14px;">
        <button class="hamburger" id="hamburgerBtn">☰</button>
        <div class="topnav-brand">
            <div class="brand-dot"></div>
            <span>AR TECH</span> SOLUTION
        </div>
    </div>
    <div class="topnav-right">
        <div class="topnav-time" id="liveClock"></div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<!-- SIDEBAR (dynamic labels from config) -->
<?php
include 'navigation.php';
?>

<div class="sidebar-toggle-pill" id="sidebarToggle">◀</div>

<!-- MAIN CONTENT -->
<main class="main" id="mainContent">
    <div class="section-title">📋 Admin Activity Log</div>

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="GET" action="" class="filter-form">
            <div class="filter-group">
                <label>Search Keyword</label>
                <input type="text" name="search" placeholder="Admin, action, or details..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <label>Action Type</label>
                <select name="action">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $action): ?>
                        <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $action_filter == $action ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($action); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>From Date</label>
                <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>">
            </div>
            <div class="filter-group">
                <label>To Date</label>
                <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>">
            </div>
            <div class="filter-buttons">
                <button type="submit">🔍 Apply Filters</button>
                <a href="admin_logs.php">Clear All</a>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="table-card">
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Time</th><th>Admin</th><th>Action</th><th>Details</th><th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr class="empty-row">
                            <td colspan="5">No activity logs found.<?php echo (!empty($search) || !empty($action_filter) || !empty($from_date) || !empty($to_date)) ? ' Try adjusting your filters.' : ''; ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('d M Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($log['admin_email']); ?><br>
                                    <small><?php echo htmlspecialchars($log['admin_name']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($log['details'])); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div class="footer">
    &copy; <?php echo date("Y"); ?> AR TECH SOLUTION — Freelancing Student Management System
</div>

<script>
// Sidebar toggle (desktop)
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');
const mainContent = document.getElementById('mainContent');
if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        toggleBtn.classList.toggle('collapsed');
        mainContent.classList.toggle('collapsed');
        toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '▶' : '◀';
    });
}

// Hamburger (mobile)
const hamburger = document.getElementById('hamburgerBtn');
if (hamburger) {
    hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('mobile-open');
    });
}

// Submenu toggles
document.querySelectorAll('.menu-toggle').forEach(toggle => {
    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const group = toggle.closest('.menu-group');
        if (group) group.classList.toggle('open');
    });
});

// Live clock
function updateClock() {
    const clockEl = document.getElementById('liveClock');
    if (clockEl) {
        const now = new Date();
        clockEl.textContent = now.toLocaleTimeString('en-US', {
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
    }
}
updateClock();
setInterval(updateClock, 1000);
</script>
</body>
</html>