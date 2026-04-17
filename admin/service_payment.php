<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$message = "";
$error = "";
$invoice = null;
$items = [];

// Handle search
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['search_invoice'])) {
    $search_term = trim($_POST['search_term']);
    if (empty($search_term)) {
        $error = "Please enter an invoice number or customer name.";
    } else {
        $search_term = "%$search_term%";
        $stmt = $conn->prepare("
            SELECT i.*, c.name as customer_name, c.email, c.phone 
            FROM invoices_new i 
            JOIN customers c ON i.customer_id = c.id 
            WHERE i.invoice_number LIKE ? OR c.name LIKE ?
            ORDER BY i.id DESC
            LIMIT 1
        ");
        $stmt->bind_param("ss", $search_term, $search_term);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $invoice = $result->fetch_assoc();
            // Fetch items for this invoice
            $items_sql = "SELECT ii.*, s.service_name 
                          FROM invoice_items ii 
                          LEFT JOIN services s ON ii.service_id = s.id 
                          WHERE ii.invoice_id = ?";
            $stmt2 = $conn->prepare($items_sql);
            $stmt2->bind_param("i", $invoice['id']);
            $stmt2->execute();
            $items = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt2->close();
        } else {
            $error = "No invoice found with that number or customer name.";
        }
        $stmt->close();
    }
}

// Handle payment submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['make_payment'])) {
    $invoice_id = intval($_POST['invoice_id']);
    $payment_amount = floatval($_POST['payment_amount']);
    
    if ($payment_amount <= 0) {
        $error = "Payment amount must be greater than zero.";
    } else {
        // Fetch current invoice details
        $stmt = $conn->prepare("SELECT total, paid_amount FROM invoices_new WHERE id = ?");
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$current) {
            $error = "Invoice not found.";
        } else {
            $new_paid = $current['paid_amount'] + $payment_amount;
            if ($new_paid > $current['total']) {
                $error = "Payment amount exceeds the remaining due.";
            } else {
                // Determine new status
                $status = ($new_paid >= $current['total']) ? 'paid' : (($new_paid > 0) ? 'partial' : 'unpaid');
                $stmt = $conn->prepare("UPDATE invoices_new SET paid_amount = ?, status = ? WHERE id = ?");
                $stmt->bind_param("dsi", $new_paid, $status, $invoice_id);
                if ($stmt->execute()) {
                    $message = "Payment of ৳" . number_format($payment_amount, 2) . " recorded successfully!";
                    // Refresh invoice data
                    $stmt2 = $conn->prepare("
                        SELECT i.*, c.name as customer_name, c.email, c.phone 
                        FROM invoices_new i 
                        JOIN customers c ON i.customer_id = c.id 
                        WHERE i.id = ?
                    ");
                    $stmt2->bind_param("i", $invoice_id);
                    $stmt2->execute();
                    $invoice = $stmt2->get_result()->fetch_assoc();
                    // Refresh items
                    $items_sql = "SELECT ii.*, s.service_name 
                                  FROM invoice_items ii 
                                  LEFT JOIN services s ON ii.service_id = s.id 
                                  WHERE ii.invoice_id = ?";
                    $stmt3 = $conn->prepare($items_sql);
                    $stmt3->bind_param("i", $invoice_id);
                    $stmt3->execute();
                    $items = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt3->close();
                    $stmt2->close();
                } else {
                    $error = "Database error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Service Payment — AR TECH SOLUTION</title>
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
    display: flex;
    justify-content: center;
}
.main.collapsed { margin-left: 0; }

/* FORM CARD */
.form-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 28px;
    max-width: 800px;
    width: 100%;
}
.form-card h2 {
    font-family: var(--mono);
    font-size: 22px;
    color: var(--accent);
    margin-bottom: 24px;
    text-align: center;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--muted);
    margin-bottom: 6px;
}
.form-group input {
    width: 100%;
    padding: 12px 14px;
    background: rgba(255,255,255,0.08);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    color: var(--text);
    font-size: 14px;
    outline: none;
}
.form-group input:focus {
    border-color: var(--accent);
}
.search-btn {
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    color: #000;
    font-weight: 700;
    padding: 10px;
    border: none;
    border-radius: 40px;
    cursor: pointer;
    width: 100%;
}
.alert {
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: center;
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
.invoice-details {
    margin-top: 24px;
    padding: 20px;
    background: rgba(255,255,255,0.05);
    border-radius: 12px;
}
.invoice-details h3 {
    color: var(--accent);
    margin-bottom: 16px;
}
.detail-row {
    display: flex;
    margin-bottom: 8px;
}
.detail-label {
    width: 120px;
    font-weight: 600;
    color: var(--accent);
}
.detail-value {
    flex: 1;
    color: #fff;
}
.items-table {
    width: 100%;
    border-collapse: collapse;
    margin: 16px 0;
}
.items-table th, .items-table td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid var(--glass-border);
}
.items-table th {
    color: var(--accent);
    font-size: 11px;
    text-transform: uppercase;
}
.items-table td {
    color: #fff;
}
.payment-form {
    margin-top: 20px;
}
.payment-form input {
    margin-bottom: 12px;
}
.pay-btn {
    background: linear-gradient(135deg, var(--accent5), #06d6a0);
    color: #000;
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
    .detail-row { flex-direction: column; }
    .detail-label { width: auto; margin-bottom: 4px; }
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
<aside class="sidebar" id="sidebar">
    <a href="dashboard.php"><?php echo get_sidebar_label('dashboard', '📊 Dashboard'); ?></a>
    <div class="sidebar-divider"></div>
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('account', '💵 Account'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="account.php"><?php echo get_sidebar_label('account_overview', 'Account Overview'); ?></a>
            <a href="account_report.php"><?php echo get_sidebar_label('account_report', 'Account Report'); ?></a>
            <a href="change_password.php"><?php echo get_sidebar_label('change_password', 'Change Password'); ?></a>
        </div>
    </div>
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('student_info', '👤 Student Info'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="insert.php"><?php echo get_sidebar_label('add_student', 'Add Student'); ?></a>
            <a href="student_list.php"><?php echo get_sidebar_label('total_student_list', 'Total Student List'); ?></a>
            <a href="form_view.php"><?php echo get_sidebar_label('student_form', 'Student Form'); ?></a>
            <a href="completed_students.php"><?php echo get_sidebar_label('course_complete', 'Course Complete'); ?></a>
            <a href="incomplete_students.php"><?php echo get_sidebar_label('course_incomplete', 'Course Incomplete'); ?></a>
            <a href="ongoing_students.php"><?php echo get_sidebar_label('ongoing', 'Ongoing'); ?></a>
        </div>
    </div>
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('customers', '👥 Customers'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="add_customer.php"><?php echo get_sidebar_label('add_customer', 'Add Customer'); ?></a>
            <a href="customer_list.php"><?php echo get_sidebar_label('customer_list', 'Customer List'); ?></a>
        </div>
    </div>
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('services', '🛠️ Services'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="services.php"><?php echo get_sidebar_label('manage_services', 'Manage Services'); ?></a>
            <a href="assign_service.php"><?php echo get_sidebar_label('assign_service', 'Assign Service'); ?></a>
        </div>
    </div>
    <a href="delete.php"><?php echo get_sidebar_label('delete', '🗑️ Delete'); ?></a>
    <a href="report.php"><?php echo get_sidebar_label('report', '📄 Report'); ?></a>
    <div class="menu-group open">
        <div class="menu-toggle"><?php echo get_sidebar_label('payment', '💵 Payment'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="invoice_pos.php"><?php echo get_sidebar_label('pos_invoice', '🧾 POS Invoice'); ?></a>
            <a href="invoice_list.php"><?php echo get_sidebar_label('invoice_list', '📋 Invoice List'); ?></a>
            <a href="invoice.php"><?php echo get_sidebar_label('print_invoice', 'Print Invoice'); ?></a>
            <a href="view_invoice.php"><?php echo get_sidebar_label('verify_invoice', 'Verify Invoice'); ?></a>
            <a href="input_payment.php"><?php echo get_sidebar_label('add_payment', 'Add Payment'); ?></a>
            <a href="payment_due.php"><?php echo get_sidebar_label('due_payment_list', 'Due Payment List'); ?></a>
            <a href="service_payment.php" class="active">💳 Service Payment</a>
        </div>
    </div>
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('attendance', '📆 Attendance'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="attendance.php"><?php echo get_sidebar_label('take_attendance', 'Take Attendance'); ?></a>
            <a href="attendance_report.php"><?php echo get_sidebar_label('attendance_report', 'View Report'); ?></a>
        </div>
    </div>
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('certificate', '📜 Certificate'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="upload_certificate.php"><?php echo get_sidebar_label('upload_certificate', 'Upload Certificate'); ?></a>
            <a href="certificate_list.php"><?php echo get_sidebar_label('view_certificate', 'View Certificate'); ?></a>
        </div>
    </div>
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('video', '🎬 Video'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="upload_video.php"><?php echo get_sidebar_label('upload_video', 'Upload Video'); ?></a>
            <a href="view_videos.php"><?php echo get_sidebar_label('view_videos', 'View Videos'); ?></a>
        </div>
    </div>
    <a href="routine_generator.php"><?php echo get_sidebar_label('routine', '🕒 Routine'); ?></a>
</aside>

<div class="sidebar-toggle-pill" id="sidebarToggle">◀</div>

<main class="main" id="mainContent">
    <div class="form-card">
        <h2>💳 Service Payment (Partial / Full)</h2>
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Search Form -->
        <form method="POST">
            <div class="form-group">
                <label>Search by Invoice Number or Customer Name</label>
                <input type="text" name="search_term" placeholder="e.g., INV-20250416-123 or John Doe" required>
            </div>
            <button type="submit" name="search_invoice" class="search-btn">🔍 Find Invoice</button>
        </form>

        <?php if ($invoice): ?>
            <div class="invoice-details">
                <h3>📄 Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?></h3>
                <div class="detail-row">
                    <div class="detail-label">Customer:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($invoice['customer_name']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Email / Phone:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($invoice['email'] ?? 'N/A'); ?> / <?php echo htmlspecialchars($invoice['phone'] ?? 'N/A'); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Invoice Date:</div>
                    <div class="detail-value"><?php echo date('d M Y', strtotime($invoice['invoice_date'])); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Due Date:</div>
                    <div class="detail-value"><?php echo date('d M Y', strtotime($invoice['due_date'])); ?></div>
                </div>

                <?php if (!empty($items)): ?>
                    <table class="items-table">
                        <thead><tr><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['description'] ?: ($item['service_name'] ?? 'N/A')); ?></td>
                                <td><?php echo $item['qty']; ?></td>
                                <td>৳ <?php echo number_format($item['unit_price'], 2); ?></td>
                                <td>৳ <?php echo number_format($item['total'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <div class="detail-row">
                    <div class="detail-label">Subtotal:</div>
                    <div class="detail-value">৳ <?php echo number_format($invoice['subtotal'], 2); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Discount:</div>
                    <div class="detail-value">- ৳ <?php echo number_format($invoice['discount'], 2); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Total:</div>
                    <div class="detail-value"><strong>৳ <?php echo number_format($invoice['total'], 2); ?></strong></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Already Paid:</div>
                    <div class="detail-value">৳ <?php echo number_format($invoice['paid_amount'], 2); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Remaining Due:</div>
                    <div class="detail-value" style="color: var(--accent3);">৳ <?php echo number_format($invoice['total'] - $invoice['paid_amount'], 2); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">
                        <?php
                        $status_class = '';
                        if ($invoice['status'] == 'paid') $status_class = 'badge-paid';
                        elseif ($invoice['status'] == 'unpaid') $status_class = 'badge-unpaid';
                        elseif ($invoice['status'] == 'partial') $status_class = 'badge-partial';
                        else $status_class = 'badge-cancelled';
                        ?>
                        <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($invoice['status']); ?></span>
                    </div>
                </div>

                <?php if (($invoice['total'] - $invoice['paid_amount']) > 0): ?>
                    <form method="POST" class="payment-form">
                        <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                        <div class="form-group">
                            <label>Payment Amount (৳)</label>
                            <input type="number" name="payment_amount" step="0.01" min="0.01" max="<?php echo $invoice['total'] - $invoice['paid_amount']; ?>" required>
                        </div>
                        <button type="submit" name="make_payment" class="search-btn pay-btn">💰 Record Payment</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success">✅ This invoice is fully paid.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
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