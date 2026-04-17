<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$customers = [];
$selected_customer = null;
$invoices = [];

// If a customer is selected, fetch all their invoices
if ($customer_id > 0) {
    // Get customer details
    $stmt = $conn->prepare("SELECT id, name, email, phone FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $selected_customer = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($selected_customer) {
        // Fetch all invoices for this customer, ordered by date (oldest first)
        $sql = "SELECT i.*, 
                       (SELECT COUNT(*) FROM invoice_items WHERE invoice_id = i.id) as item_count
                FROM invoices_new i
                WHERE i.customer_id = ?
                ORDER BY i.invoice_date ASC, i.id ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $invoices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} elseif (!empty($search_term)) {
    // Search for customers by name or email
    $search_like = "%$search_term%";
    $stmt = $conn->prepare("SELECT id, name, email, phone FROM customers WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY name LIMIT 20");
    $stmt->bind_param("sss", $search_like, $search_like, $search_like);
    $stmt->execute();
    $customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Purchase History — AR TECH SOLUTION</title>
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

/* CARDS */
.card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 24px;
    margin-bottom: 24px;
}
.card h2, .card h3 {
    font-family: var(--mono);
    color: var(--accent);
    margin-bottom: 16px;
}
.search-form {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
}
.search-form input, .search-form select {
    flex: 1;
    padding: 10px 14px;
    background: rgba(255,255,255,0.08);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    color: #f0f0f0;
}
.search-form input:focus, .search-form select:focus {
    border-color: var(--accent);
}
.search-form button {
    padding: 10px 20px;
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    border: none;
    border-radius: 30px;
    color: #000;
    font-weight: 700;
    cursor: pointer;
}
.customer-list {
    display: grid;
    gap: 12px;
    margin-top: 16px;
}
.customer-item {
    background: rgba(255,255,255,0.05);
    border-radius: 12px;
    padding: 12px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    color: #fff;
}
.customer-item a {
    background: linear-gradient(135deg, var(--accent2), #9b59b6);
    padding: 6px 16px;
    border-radius: 30px;
    text-decoration: none;
    color: #fff;
    font-size: 12px;
}
.invoice-table {
    width: 100%;
    border-collapse: collapse;
}
.invoice-table th, .invoice-table td {
    padding: 10px 8px;
    text-align: left;
    border-bottom: 1px solid var(--glass-border);
}
.invoice-table th {
    color: var(--accent);
    font-weight: 600;
}
.invoice-table td {
    color: #fff;
}
.invoice-table a {
    color: var(--accent);
    text-decoration: none;
}
.invoice-table a:hover {
    text-decoration: underline;
}
.summary-box {
    background: rgba(0,229,200,0.1);
    border-radius: 12px;
    padding: 16px;
    margin-top: 20px;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
}
.summary-item {
    text-align: center;
}
.summary-label {
    font-size: 12px;
    color: var(--muted);
}
.summary-value {
    font-size: 20px;
    font-weight: 700;
    color: var(--accent);
}
.no-data {
    text-align: center;
    padding: 40px;
    color: var(--muted);
}
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
@media (max-width: 700px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.mobile-open { transform: translateX(0); }
    .sidebar-toggle-pill { display: none; }
    .hamburger { display: block; }
    .main { margin-left: 0 !important; padding-left: 16px; padding-right: 16px; }
    .search-form { flex-direction: column; }
    .search-form button { width: 100%; }
    .customer-item { flex-direction: column; text-align: center; }
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
    <div class="section-title">📦 Customer Purchase History</div>

    <!-- Search / Customer Selection Card -->
    <div class="card">
        <h2>🔍 Find Customer</h2>
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search by name, email or phone" value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit">Search</button>
        </form>

        <?php if (!empty($customers)): ?>
            <div class="customer-list">
                <?php foreach ($customers as $cust): ?>
                    <div class="customer-item">
                        <div>
                            <strong><?php echo htmlspecialchars($cust['name']); ?></strong><br>
                            <small><?php echo htmlspecialchars($cust['email']); ?> | <?php echo htmlspecialchars($cust['phone']); ?></small>
                        </div>
                        <a href="?customer_id=<?php echo $cust['id']; ?>">View Purchases →</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($search_term && empty($customers)): ?>
            <div class="no-data">No customers found matching your search.</div>
        <?php endif; ?>
    </div>

    <?php if ($selected_customer): ?>
        <div class="card">
            <h2>👤 Customer: <?php echo htmlspecialchars($selected_customer['name']); ?></h2>
            <p style="color:#fff;"><strong style="color:var(--accent);">Email:</strong> <?php echo htmlspecialchars($selected_customer['email']); ?> | <strong style="color:var(--accent);">Phone:</strong> <?php echo htmlspecialchars($selected_customer['phone']); ?></p>

            <?php if (empty($invoices)): ?>
                <div class="no-data">No purchases found for this customer.</div>
            <?php else: 
                $total_spent = 0;
                $total_paid = 0;
                $total_due = 0;
                foreach ($invoices as $inv) {
                    $total_spent += $inv['total'];
                    $total_paid += $inv['paid_amount'];
                    $total_due += ($inv['total'] - $inv['paid_amount']);
                }
            ?>
                <div style="overflow-x: auto;">
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th><th>Date</th><th>Due Date</th>
                                <th>Items</th><th>Total (৳)</th><th>Paid (৳)</th><th>Due (৳)</th><th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $inv): 
                                $balance = $inv['total'] - $inv['paid_amount'];
                                $status_badge = '';
                                if ($inv['status'] == 'paid') $status_badge = '✅ Paid';
                                elseif ($inv['status'] == 'unpaid') $status_badge = '🔴 Unpaid';
                                elseif ($inv['status'] == 'partial') $status_badge = '🟡 Partial';
                                else $status_badge = '⚫ Cancelled';
                            ?>
                            <tr>
                                <td><a href="view_invoice.php?invoice_no=<?php echo urlencode($inv['invoice_number']); ?>" target="_blank" style="color:var(--accent);"><?php echo htmlspecialchars($inv['invoice_number']); ?></a></td>
                                <td><?php echo date('d-m-Y', strtotime($inv['invoice_date'])); ?></td>
                                <td><?php echo date('d-m-Y', strtotime($inv['due_date'])); ?></td>
                                <td><?php echo $inv['item_count']; ?>项</td>
                                <td><?php echo number_format($inv['total'], 2); ?></td>
                                <td><?php echo number_format($inv['paid_amount'], 2); ?></td>
                                <td><?php echo number_format($balance, 2); ?></td>
                                <td><?php echo $status_badge; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="summary-box">
                    <div class="summary-item">
                        <div class="summary-label">Total Invoices</div>
                        <div class="summary-value"><?php echo count($invoices); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Total Spent</div>
                        <div class="summary-value">৳ <?php echo number_format($total_spent, 2); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Total Paid</div>
                        <div class="summary-value">৳ <?php echo number_format($total_paid, 2); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Total Due</div>
                        <div class="summary-value" style="color: var(--accent3);">৳ <?php echo number_format($total_due, 2); ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<div class="footer">
    &copy; <?php echo date("Y"); ?> AR TECH SOLUTION — Freelancing Student Management System
</div>

<script>
// Sidebar toggle
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
const hamburger = document.getElementById('hamburgerBtn');
if (hamburger) {
    hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('mobile-open');
    });
}
document.querySelectorAll('.menu-toggle').forEach(toggle => {
    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const group = toggle.closest('.menu-group');
        if (group) group.classList.toggle('open');
    });
});
function updateClock() {
    const clockEl = document.getElementById('liveClock');
    if (clockEl) {
        const now = new Date();
        clockEl.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
}
updateClock();
setInterval(updateClock, 1000);
</script>
</body>
</html>