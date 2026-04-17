<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

require_once 'config.php';


$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($invoice_id <= 0) die("Invalid invoice ID.");

// Fetch invoice & customer
$sql = "SELECT i.*, c.name as customer_name, c.email, c.phone 
        FROM invoices_new i 
        JOIN customers c ON i.customer_id = c.id 
        WHERE i.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$invoice) die("Invoice not found.");

// Fetch items
$items_sql = "SELECT ii.*, s.service_name 
              FROM invoice_items ii 
              LEFT JOIN services s ON ii.service_id = s.id 
              WHERE ii.invoice_id = ?";
$stmt = $conn->prepare($items_sql);
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$balance = $invoice['total'] - $invoice['paid_amount'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Print Invoice - <?php echo htmlspecialchars($invoice['invoice_number']); ?> — AR TECH SOLUTION</title>
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

/* Action buttons card */
.action-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 16px;
    margin-bottom: 24px;
    text-align: center;
}
.action-card button {
    background: linear-gradient(135deg, var(--accent2), #9b59b6);
    color: #fff;
    border: none;
    padding: 10px 24px;
    border-radius: 40px;
    font-weight: 700;
    cursor: pointer;
    margin: 0 8px;
}
.action-card button.close-btn {
    background: linear-gradient(135deg, #6c757d, #5a6268);
}
.action-card button:hover { opacity: .85; }

/* Invoice print wrapper (glass card for screen) */
.print-wrapper {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 28px;
    max-width: 900px;
    margin: 0 auto;
}

/* Invoice styling (text colors for screen) */
.pi-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
    border-bottom: 2px solid var(--accent);
    padding-bottom: 15px;
    flex-wrap: wrap;
    gap: 16px;
}
.pi-logo {
    font-size: 22px;
    font-weight: 800;
    color: var(--accent);
}
.pi-logo small {
    display: block;
    font-size: 12px;
    font-weight: 400;
    color: var(--muted);
    margin-top: 2px;
}
.pi-inv {
    text-align: right;
}
.inv-num {
    font-family: var(--mono);
    font-size: 18px;
    font-weight: 700;
    color: var(--accent5);
}
.inv-date {
    font-size: 12px;
    color: var(--muted);
    margin-top: 4px;
}

.pi-parties {
    display: flex;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 20px;
    flex-wrap: wrap;
}
.pi-party h4 {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    color: var(--accent);
    margin-bottom: 6px;
}
.pi-party p {
    font-size: 13px;
    line-height: 1.6;
    color: #fff;
}

.pi-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}
.pi-table th {
    background: rgba(0,0,0,0.3);
    color: var(--accent);
    padding: 10px 12px;
    text-align: left;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.pi-table td {
    padding: 9px 12px;
    border-bottom: 1px solid var(--glass-border);
    font-size: 13px;
    color: #fff;
}
.pi-table tfoot td {
    font-weight: 700;
    border-top: 2px solid var(--accent);
}
.pi-total-row td {
    background: var(--accent5);
    color: #000;
}
.pi-footer-note {
    font-size: 11px;
    color: var(--muted);
    text-align: center;
    margin-top: 30px;
    border-top: 1px dashed var(--glass-border);
    padding-top: 12px;
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

/* PRINT STYLES (clean white) */
@media print {
    .topnav, .sidebar, .sidebar-toggle-pill, .action-card, .footer {
        display: none !important;
    }
    body, .main, .print-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        background: white;
        backdrop-filter: none;
        border: none;
        box-shadow: none;
    }
    .print-wrapper {
        max-width: 100%;
        padding: 0.5cm;
    }
    .pi-header {
        border-bottom: 1px solid #000;
    }
    .pi-logo, .inv-num, .pi-party h4 {
        color: #000 !important;
    }
    .pi-table th {
        background: #ddd !important;
        color: #000 !important;
    }
    .pi-table td {
        color: #000 !important;
        border-bottom-color: #ccc;
    }
    .pi-total-row td {
        background: #eee !important;
        color: #000 !important;
    }
    .pi-footer-note {
        color: #666 !important;
        border-top-color: #ccc;
    }
    @page {
        size: A4;
        margin: 1.5cm;
    }
}

/* RESPONSIVE */
@media (max-width: 700px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.mobile-open { transform: translateX(0); }
    .sidebar-toggle-pill { display: none; }
    .hamburger { display: block; }
    .main { margin-left: 0 !important; padding-left: 16px; padding-right: 16px; }
    .print-wrapper { padding: 20px; }
    .pi-header { flex-direction: column; text-align: center; }
    .pi-inv { text-align: center; }
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
    <div class="action-card">
        <button onclick="window.print()">🖨️ Print Invoice</button>
        <button onclick="window.close()" class="close-btn">❌ Close</button>
    </div>

    <div class="print-wrapper" id="printInvoice">
        <div class="pi-header">
            <div>
                <div class="pi-logo">AR TECH SOLUTION<small>Freelancing & Training Center</small></div>
            </div>
            <div class="pi-inv">
                <div class="inv-num"><?php echo htmlspecialchars($invoice['invoice_number']); ?></div>
                <div class="inv-date">Date: <?php echo date('d M Y', strtotime($invoice['invoice_date'])); ?></div>
                <div class="inv-date">Due: <?php echo date('d M Y', strtotime($invoice['due_date'])); ?></div>
            </div>
        </div>

        <div class="pi-parties">
            <div class="pi-party">
                <h4>Billed To</h4>
                <p><strong><?php echo htmlspecialchars($invoice['customer_name']); ?></strong></p>
                <p style="font-size:12px;">
                    <?php echo htmlspecialchars($invoice['email'] ?? ''); ?><br>
                    <?php echo htmlspecialchars($invoice['phone'] ?? ''); ?>
                </p>
            </div>
            <div class="pi-party" style="text-align:right;">
                <h4>From</h4>
                <p><strong>AR TECH SOLUTION</strong><br>Freelancing & Training Center</p>
            </div>
        </div>

        <table class="pi-table">
            <thead>
                <tr><th>#</th><th>Description</th><th>Qty</th><th>Unit Price (৳)</th><th>Total (৳)</th></tr>
            </thead>
            <tbody>
                <?php $counter = 1; foreach ($items as $item): ?>
                <tr>
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo htmlspecialchars($item['description'] ?: ($item['service_name'] ?? 'N/A')); ?></td>
                    <td><?php echo $item['qty']; ?></td>
                    <td><?php echo number_format($item['unit_price'], 2); ?></td>
                    <td><?php echo number_format($item['total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="4">Subtotal</td><td><?php echo number_format($invoice['subtotal'], 2); ?></td></tr>
                <tr><td colspan="4">Discount</td><td><?php echo number_format($invoice['discount'], 2); ?></td></tr>
                <tr><td colspan="4"><strong>Total</strong></td><td><strong><?php echo number_format($invoice['total'], 2); ?></strong></td></tr>
                <tr><td colspan="4">Amount Paid</td><td><?php echo number_format($invoice['paid_amount'], 2); ?></td></tr>
                <tr class="pi-total-row"><td colspan="4"><strong>Balance Due</strong></td><td><strong><?php echo number_format($balance, 2); ?></strong></td></tr>
            </tfoot>
        </table>

        <?php if (!empty($invoice['notes'])): ?>
            <div style="margin-top:15px; font-size:12px;">
                <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($invoice['notes'])); ?>
            </div>
        <?php endif; ?>

        <div class="pi-footer-note">
            Thank you for your business! · AR TECH SOLUTION · <?php echo date('Y'); ?>
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