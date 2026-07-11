<?php
// --- DEBUG (remove after testing) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// --- Check DB connection ---
if (!isset($conn) || !$conn) {
    die("Database connection not established in config.php");
}

// --- Search logic ---
$search_term = '';
$students = [];
$total_due = 0;
$total_students = 0;

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = trim($_GET['search']);
    $search_escaped = $conn->real_escape_string($search_term);
    $sql = "SELECT * FROM students WHERE 
            student_id LIKE '%$search_escaped%' OR 
            name LIKE '%$search_escaped%' OR
            email LIKE '%$search_escaped%'
            ORDER BY name ASC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['due_amount'] = $row['course_fee'] - $row['paid_fee'];
            $total_due += $row['due_amount'];
            $students[] = $row;
        }
        $total_students = count($students);
    }
} else {
    // If no search, show all students (optional - you can limit or paginate)
    $sql = "SELECT * FROM students ORDER BY name ASC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['due_amount'] = $row['course_fee'] - $row['paid_fee'];
            $total_due += $row['due_amount'];
            $students[] = $row;
        }
        $total_students = count($students);
    }
}

// --- Default background ---
$bg_image = $bg_image ?? 'default-bg.jpg';
$dark_mode = $dark_mode ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Summary — AR TECH SOLUTION</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<style>
    /* ========== CSS (same as before) ========== */
    body {
        background-image: url('<?php echo htmlspecialchars($bg_image); ?>');
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
        transition: opacity .2s; border: none; cursor: pointer;
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
        overflow-y: auto; overflow-x: hidden;
        z-index: 1050;
        transition: transform .3s cubic-bezier(.4,0,.2,1);
        padding-bottom: 40px;
    }
    .sidebar::-webkit-scrollbar { width: 4px; }
    .sidebar::-webkit-scrollbar-track { background: transparent; }
    .sidebar::-webkit-scrollbar-thumb { background: var(--glass-border); border-radius: 4px; }
    .sidebar.collapsed { transform: translateX(-100%); }
    .sidebar a, .menu-toggle {
        display: flex; align-items: center; gap: 10px;
        color: var(--muted); text-decoration: none;
        padding: 11px 20px; font-size: 13.5px; font-weight: 500;
        border-left: 3px solid transparent;
        transition: all .2s; cursor: pointer; user-select: none;
        white-space: nowrap;
    }
    .sidebar a:hover, .menu-toggle:hover { color: #fff; background: var(--glass); border-left-color: var(--accent); }
    .sidebar a.active { color: var(--accent); border-left-color: var(--accent); background: rgba(0,229,200,0.07); }
    .submenu { display: none; flex-direction: column; background: rgba(0,0,0,0.2); }
    .submenu a { padding: 9px 20px 9px 38px; font-size: 13px; }
    .menu-group.open .submenu { display: flex; }
    .menu-arrow { margin-left: auto; font-size: 11px; transition: transform .25s; }
    .menu-group.open .menu-arrow { transform: rotate(180deg); }
    .sidebar-divider { height: 1px; background: var(--glass-border); margin: 10px 16px; }
    /* SIDEBAR TOGGLE PILL */
    .sidebar-toggle-pill {
        position: fixed; top: calc(var(--nav-h) + 16px); left: var(--sidebar-w);
        width: 24px; height: 44px; background: var(--accent);
        border-radius: 0 10px 10px 0;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; z-index: 1060; font-size: 13px; color: #000;
        font-weight: 900; transition: left .3s cubic-bezier(.4,0,.2,1), background .2s;
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
    /* SEARCH CARD */
    .search-card {
        background: var(--glass);
        backdrop-filter: blur(16px);
        border: 1px solid var(--glass-border);
        border-radius: var(--card-radius);
        padding: 20px;
        margin-bottom: 28px;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
    }
    .search-card input {
        flex: 1;
        padding: 12px 16px;
        background: rgba(255,255,255,0.08);
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        color: var(--text);
        font-family: var(--sans);
        font-size: 14px;
        outline: none;
    }
    .search-card input:focus {
        border-color: var(--accent);
        background: rgba(255,255,255,0.12);
    }
    .search-card button {
        padding: 12px 24px;
        background: linear-gradient(135deg, var(--accent), #00c9b0);
        border: none;
        border-radius: 12px;
        color: #000;
        font-weight: 700;
        cursor: pointer;
        transition: opacity .2s;
    }
    .search-card button:hover { opacity: .85; }

    /* TABLE CARD */
    .table-card {
        background: var(--glass);
        backdrop-filter: blur(16px);
        border: 1px solid var(--glass-border);
        border-radius: var(--card-radius);
        padding: 20px;
        overflow-x: auto;
        margin-bottom: 28px;
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
    .due-amount {
        color: var(--accent3);
        font-weight: 700;
    }
    .paid-amount {
        color: var(--accent5);
        font-weight: 600;
    }
    .summary-box {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        background: var(--glass);
        backdrop-filter: blur(16px);
        border: 1px solid var(--glass-border);
        border-radius: var(--card-radius);
        padding: 16px 20px;
        margin-top: 20px;
        font-size: 14px;
        color: var(--text);
    }
    .summary-box span {
        font-weight: 600;
    }
    .summary-box .total-due {
        color: var(--accent3);
        font-size: 18px;
    }
    .no-data {
        text-align: center;
        padding: 40px;
        color: var(--muted);
    }
    .alert-warning {
        background: rgba(255,209,102,0.15);
        border: 1px solid var(--accent4);
        color: var(--accent4);
        padding: 12px 16px;
        border-radius: 12px;
        margin-bottom: 20px;
        text-align: center;
        font-size: 14px;
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
        .search-card { flex-direction: column; }
        .search-card button { width: 100%; }
        th, td { padding: 8px 6px; font-size: 11px; }
        .summary-box { flex-direction: column; align-items: center; text-align: center; }
    }
</style>
</head>
<body class="<?php echo $dark_mode ? 'dark-mode' : ''; ?>">

<!-- TOP NAV -->
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

<!-- SIDEBAR -->
<?php include 'navigation.php'; ?>

<div class="sidebar-toggle-pill" id="sidebarToggle">◀</div>

<!-- MAIN CONTENT -->
<main class="main" id="mainContent">
    <div class="section-title">📊 Student Course Summary</div>

    <!-- Search -->
    <div class="search-card">
        <form method="GET" style="display: flex; gap: 12px; width: 100%; flex-wrap: wrap;">
            <input type="text" name="search" placeholder="Search by ID, Name, or Email..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit">🔍 Search</button>
        </form>
    </div>

    <!-- Display table -->
    <?php if (!empty($students)): ?>
        <div class="table-card">
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Course</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Course Fee</th>
                            <th>Paid Fee</th>
                            <th>Due Fee</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['student_id'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['phone_number'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['course_category'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['course_start_date'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['course_end_date'] ?? ''); ?></td>
                                <td>TK <?php echo number_format($row['course_fee'] ?? 0, 2); ?></td>
                                <td class="paid-amount">TK <?php echo number_format($row['paid_fee'] ?? 0, 2); ?></td>
                                <td class="due-amount">TK <?php echo number_format($row['due_amount'] ?? 0, 2); ?></td>
                                <td><?php echo ucfirst($row['course_status'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- Summary -->
            <div class="summary-box">
                <span>👥 Total Students: <strong><?php echo $total_students; ?></strong></span>
                <span>💰 Total Due: <span class="total-due">TK <?php echo number_format($total_due, 2); ?></span></span>
            </div>
        </div>
    <?php elseif (!empty($search_term)): ?>
        <div class="alert-warning">⚠️ No students found matching your search.</div>
    <?php else: ?>
        <div class="no-data">🔍 Enter a search term above to filter students, or leave blank to see all.</div>
    <?php endif; ?>
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