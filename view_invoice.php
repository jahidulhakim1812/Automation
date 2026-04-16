<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "freelancing");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$invoice = null;
$student = null;

if (isset($_POST['invoice_no'])) {
    $invoice_no = $_POST['invoice_no'];

    $stmt = $conn->prepare("SELECT * FROM invoices WHERE invoice_no=?");
    $stmt->bind_param("s", $invoice_no);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $invoice = $res->fetch_assoc();

        $stmt2 = $conn->prepare("SELECT * FROM students WHERE student_id=?");
        $stmt2->bind_param("s", $invoice['student_id']);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $student = $result2->fetch_assoc();
        $stmt2->close();
    } else {
        echo "<script>alert('Invoice not found');</script>";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Invoice — AR TECH SOLUTION</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
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

body {
    font-family: var(--sans);
    color: var(--text);
    min-height: 100vh;
    background: url('uploads/banner.jpg') no-repeat center center fixed;
    background-size: cover;
    overflow-x: hidden;
}

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
    justify-content: center;
}
.search-card form {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
    width: 100%;
    max-width: 500px;
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

/* INVOICE CARD */
.invoice-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 28px;
    max-width: 800px;
    margin: 0 auto;
}
.invoice-card h2 {
    font-family: var(--mono);
    color: var(--accent);
    margin-bottom: 20px;
}
.invoice-card p {
    margin: 8px 0;
    font-size: 14px;
}
.invoice-card strong {
    color: var(--accent);
}
.invoice-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}
.invoice-table th, .invoice-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid var(--glass-border);
}
.invoice-table th {
    background: rgba(0,0,0,0.3);
    color: var(--accent);
    font-weight: 600;
    font-size: 12px;
}
.invoice-table td {
    color: var(--text);
}
.total {
    margin-top: 16px;
    font-size: 13px;
    color: var(--muted);
    text-align: right;
}
.print-btn {
    background: linear-gradient(135deg, var(--accent2), #9b59b6);
    color: white;
    padding: 10px 24px;
    border: none;
    border-radius: 40px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity .2s;
    margin-top: 20px;
}
.print-btn:hover { opacity: .85; }

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

/* PRINT STYLES */
@media print {
    .topnav, .sidebar, .sidebar-toggle-pill, .search-card, .print-btn, .footer {
        display: none !important;
    }
    .main { margin: 0; padding: 0; background: white; }
    .invoice-card { background: white; backdrop-filter: none; border: none; box-shadow: none; padding: 20px; }
    .invoice-card * { color: black !important; }
    .invoice-card h2 { color: #1abc9c !important; }
    .invoice-table th { background: #ddd; color: black; }
}

/* RESPONSIVE */
@media (max-width: 700px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.mobile-open { transform: translateX(0); }
    .sidebar-toggle-pill { display: none; }
    .hamburger { display: block; }
    .main { margin-left: 0 !important; padding-left: 16px; padding-right: 16px; }
    .search-card form { flex-direction: column; }
    .search-card button { width: 100%; }
    .invoice-card { padding: 20px; }
}
</style>
</head>
<body>

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

<!-- SIDEBAR (modern dashboard) -->
<?php
include 'navigation.php';
?>

<div class="sidebar-toggle-pill" id="sidebarToggle">◀</div>

<!-- MAIN CONTENT -->
<main class="main" id="mainContent">
    <div class="section-title">🔍 Verify Invoice</div>

    <!-- Search Card -->
    <div class="search-card">
        <form method="POST">
            <input type="text" name="invoice_no" placeholder="Enter Invoice Number" required>
            <button type="submit">Search Invoice</button>
        </form>
    </div>

    <?php if ($invoice && $student): ?>
    <div class="invoice-card">
        <h2>Invoice: <?php echo htmlspecialchars($invoice['invoice_no']); ?></h2>
        <p><strong>Student Name:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
        <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
        <p><strong>Course Name:</strong> <?php echo htmlspecialchars($student['course_category']); ?></p>
        
        <table class="invoice-table">
            <thead>
                <tr><th>Description</th><th style="text-align:right;">Amount (৳)</th></tr>
            </thead>
            <tbody>
                <tr><td>Total Course Fee</td><td style="text-align:right;"><?php echo number_format($student['course_fee'], 2); ?></td></tr>
                <tr><td>Paid Fee</td><td style="text-align:right;"><?php echo number_format($student['paid_fee'], 2); ?></td></tr>
                <tr><td>Due Fee</td><td style="text-align:right; color: var(--accent3);"><?php echo number_format($student['course_fee'] - $student['paid_fee'], 2); ?></td></tr>
            </tbody>
        </table>
        <div class="total">Last Updated: <?php echo htmlspecialchars($student['last_updated']); ?></div>
        <button class="print-btn" onclick="window.print()">🖨️ Print Invoice</button>
    </div>
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