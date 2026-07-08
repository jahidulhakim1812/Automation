<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

require_once 'config.php';   // Database connection

// ========== DELETE INVOICE ==========
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM invoices_new WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $delete_msg = "Invoice deleted successfully.";
    } else {
        $delete_msg = "Error deleting invoice.";
    }
    $stmt->close();
    header("Location: invoice_list.php?msg=" . urlencode($delete_msg));
    exit();
}

// ========== ADD PAYMENT (AJAX) ==========
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_payment'])) {
    $invoice_id = intval($_POST['invoice_id']);
    $amount = floatval($_POST['amount']);
    if ($invoice_id > 0 && $amount > 0) {
        // Fetch current paid_amount and total
        $stmt = $conn->prepare("SELECT paid_amount, total FROM invoices_new WHERE id = ?");
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $inv = $result->fetch_assoc();
        $stmt->close();
        if ($inv) {
            $new_paid = min($inv['paid_amount'] + $amount, $inv['total']);
            // Update paid_amount
            $stmt = $conn->prepare("UPDATE invoices_new SET paid_amount = ? WHERE id = ?");
            $stmt->bind_param("di", $new_paid, $invoice_id);
            if ($stmt->execute()) {
                // Update status if needed
                if ($new_paid >= $inv['total']) {
                    $stmt2 = $conn->prepare("UPDATE invoices_new SET status = 'paid' WHERE id = ?");
                    $stmt2->bind_param("i", $invoice_id);
                    $stmt2->execute();
                    $stmt2->close();
                } elseif ($new_paid > 0) {
                    $stmt2 = $conn->prepare("UPDATE invoices_new SET status = 'partial' WHERE id = ?");
                    $stmt2->bind_param("i", $invoice_id);
                    $stmt2->execute();
                    $stmt2->close();
                }
                echo json_encode(['success' => true, 'message' => 'Payment added successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error updating payment.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invoice not found.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid amount or invoice ID.']);
    }
    exit;
}

// Display delete message if present
$delete_message = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';

// Search parameters
$search = trim($_GET['search'] ?? '');
$where = "";
$params = [];
$types = "";

if (!empty($search)) {
    $where = "WHERE (i.invoice_number LIKE ? OR c.name LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like];
    $types = "ss";
}

$sql = "SELECT i.*, c.name as customer_name 
        FROM invoices_new i 
        JOIN customers c ON i.customer_id = c.id 
        $where 
        ORDER BY i.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($search)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$invoices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice List — AR TECH SOLUTION</title>
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
}
.search-card form {
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
.search-card .clear-btn {
    background: linear-gradient(135deg, var(--accent3), #c0392b);
    color: white;
    padding: 12px 24px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 700;
    display: inline-block;
}
.search-card .clear-btn:hover { opacity: .85; }

/* ALERT MESSAGE */
.alert {
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: center;
    font-size: 14px;
}
.alert-success {
    background: rgba(6,214,160,0.15);
    border: 1px solid var(--accent5);
    color: var(--accent5);
}
.alert-error {
    background: rgba(255,107,107,0.15);
    border: 1px solid var(--accent3);
    color: var(--accent3);
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
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.badge-paid { background: rgba(6,214,160,0.2); color: #06d6a0; }
.badge-unpaid { background: rgba(255,107,107,0.2); color: #ff6b6b; }
.badge-partial { background: rgba(255,209,102,0.2); color: #ffd166; }
.badge-cancelled { background: rgba(200,210,230,0.2); color: var(--muted); }
.btn-print, .btn-delete, .btn-pay {
    border: none;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity .2s;
    margin: 0 2px;
    display: inline-block;
}
.btn-print {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
}
.btn-pay {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    color: white;
}
.btn-delete {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
}
.btn-print:hover, .btn-pay:hover, .btn-delete:hover { opacity: .85; }
.empty-row td {
    text-align: center;
    padding: 40px;
    color: var(--muted);
}
.actions {
    white-space: nowrap;
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
    .search-card form { flex-direction: column; }
    .search-card button, .search-card .clear-btn { width: 100%; text-align: center; }
    th, td { padding: 8px 6px; font-size: 11px; }
    .btn-print, .btn-pay, .btn-delete { padding: 4px 10px; font-size: 10px; }
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

<!-- SIDEBAR -->
<?php include 'navigation.php'; ?>
<div class="sidebar-toggle-pill" id="sidebarToggle">◀</div>

<!-- MAIN CONTENT -->
<main class="main" id="mainContent">
    <div class="section-title">📋 All Invoices</div>

    <?php if ($delete_message): ?>
        <div class="alert alert-success"><?php echo $delete_message; ?></div>
    <?php endif; ?>

    <!-- Search Card -->
    <div class="search-card">
        <form method="GET" action="">
            <input type="text" name="search" placeholder="Search by Invoice Number or Customer Name" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">🔍 Search</button>
            <?php if (!empty($search)): ?>
                <a href="invoice_list.php" class="clear-btn">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table Card -->
    <div class="table-card">
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th><th>Customer</th><th>Date</th><th>Due Date</th>
                        <th>Total (৳)</th><th>Paid (৳)</th><th>Balance</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr class="empty-row"><td colspan="9">No invoices found.<?php echo !empty($search) ? ' Try a different search.' : ''; ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $inv): 
                            $balance = $inv['total'] - $inv['paid_amount'];
                            $statusClass = '';
                            if ($inv['status'] == 'paid') $statusClass = 'badge-paid';
                            elseif ($inv['status'] == 'unpaid') $statusClass = 'badge-unpaid';
                            elseif ($inv['status'] == 'partial') $statusClass = 'badge-partial';
                            else $statusClass = 'badge-cancelled';
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($inv['invoice_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($inv['customer_name']); ?></td>
                                <td><?php echo date('d-m-Y', strtotime($inv['invoice_date'])); ?></td>
                                <td><?php echo date('d-m-Y', strtotime($inv['due_date'])); ?></td>
                                <td><?php echo number_format($inv['total'], 2); ?></td>
                                <td><?php echo number_format($inv['paid_amount'], 2); ?></td>
                                <td><?php echo number_format($balance, 2); ?></td>
                                <td><span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($inv['status']); ?></span></td>
                                <td class="actions">
                                    <button class="btn-print" onclick="printInvoice(<?php echo $inv['id']; ?>)">🖨️ Print</button>
                                    <?php if ($inv['status'] !== 'paid' && $inv['status'] !== 'cancelled' && $balance > 0): ?>
                                        <button class="btn-pay" onclick="addPayment(<?php echo $inv['id']; ?>, <?php echo $balance; ?>)">💰 Pay</button>
                                    <?php endif; ?>
                                    <button class="btn-delete" onclick="deleteInvoice(<?php echo $inv['id']; ?>, '<?php echo htmlspecialchars($inv['invoice_number']); ?>')">🗑️ Delete</button>
                                </td>
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

// Print invoice
function printInvoice(invoiceId) {
    const printWindow = window.open(`print_invoice.php?id=${invoiceId}`, '_blank', 'width=800,height=600');
    if (printWindow) printWindow.focus();
}

// Delete invoice
function deleteInvoice(invoiceId, invoiceNumber) {
    if (confirm(`Are you sure you want to delete invoice #${invoiceNumber}? This action cannot be undone.`)) {
        window.location.href = `?delete_id=${invoiceId}`;
    }
}

// Add payment
function addPayment(invoiceId, balance) {
    const amount = prompt(`Enter payment amount (max ৳${balance.toFixed(2)}):`, '');
    if (amount === null) return; // cancelled
    const parsed = parseFloat(amount);
    if (isNaN(parsed) || parsed <= 0) {
        alert('Please enter a valid amount greater than zero.');
        return;
    }
    if (parsed > balance) {
        alert(`Amount cannot exceed the balance of ৳${balance.toFixed(2)}.`);
        return;
    }
    
    // Send AJAX request
    const formData = new URLSearchParams();
    formData.append('add_payment', '1');
    formData.append('invoice_id', invoiceId);
    formData.append('amount', parsed);
    
    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload(); // refresh page to update table
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('An unexpected error occurred. Please try again.');
    });
}
</script>
</body>
</html>