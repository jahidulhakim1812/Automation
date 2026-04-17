<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Email sending function
function sendDueReminder($to_email, $customer_name, $invoice_number, $due_amount, $subject = null, $message_body = null, $attachment_tmp = null, $attachment_name = null) {
    $my_email    = 'artechsolution.online@gmail.com';
    $app_password = 'giwr wrcr mnyi lkpf';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $my_email;
        $mail->Password   = $app_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($my_email, 'AR Tech Admin');
        $mail->addAddress($to_email, $customer_name);

        if ($attachment_tmp && $attachment_name) {
            $mail->addAttachment($attachment_tmp, $attachment_name);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject ?: "Payment Reminder - Invoice #$invoice_number";
        $body = $message_body ?: "
            <div style='font-family: Arial, sans-serif;'>
                <h3>Dear $customer_name,</h3>
                <p>This is a friendly reminder that you have an outstanding balance of:</p>
                <h2 style='color:#e74c3c;'>৳ " . number_format($due_amount, 2) . "</h2>
                <p>on invoice <strong>$invoice_number</strong>.</p>
                <p>Please clear the due amount at your earliest convenience.</p>
                <br>
                <p>Regards,<br>AR Tech Solution</p>
            </div>";
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Handle single email send
$message_status = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_single'])) {
    $email = $_POST['email'];
    $name = $_POST['name'];
    $inv_num = $_POST['invoice_number'];
    $due = $_POST['due_amount'];
    $subject = trim($_POST['subject'] ?? '');
    $custom_message = trim($_POST['custom_message'] ?? '');
    if (sendDueReminder($email, $name, $inv_num, $due, $subject, $custom_message)) {
        $message_status = "<div class='alert alert-success'>✅ Reminder sent to <strong>$name</strong>.</div>";
    } else {
        $message_status = "<div class='alert alert-error'>❌ Failed to send reminder to <strong>$name</strong>.</div>";
    }
}

// Handle bulk email send
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_bulk'])) {
    $subject = trim($_POST['bulk_subject'] ?? '');
    $custom_message = trim($_POST['bulk_message'] ?? '');
    $filter = trim($_POST['filter_category'] ?? '');
    
    // Build query for due customers
    $sql = "SELECT i.id, i.invoice_number, i.total, i.paid_amount, 
                   (i.total - i.paid_amount) as due_amount,
                   c.id as customer_id, c.name as customer_name, c.email
            FROM invoices_new i
            JOIN customers c ON i.customer_id = c.id
            WHERE i.total > i.paid_amount AND c.email IS NOT NULL AND c.email != ''";
    if (!empty($filter)) {
        $filter = "%$filter%";
        $sql .= " AND (c.name LIKE ? OR i.invoice_number LIKE ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $filter, $filter);
    } else {
        $stmt = $conn->prepare($sql);
    }
    $stmt->execute();
    $due_customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($due_customers)) {
        $message_status = "<div class='alert alert-warning'>⚠️ No due customers found with valid email addresses.</div>";
    } else {
        $total = count($due_customers);
        $sent_count = 0;
        $fail_count = 0;
        
        // Show progress UI
        echo "<!DOCTYPE html><html><head><title>Sending Reminders</title><style>
                body{font-family:Arial;background:#1e2a3a;color:#fff;text-align:center;padding:50px;}
                .progress-box{background:rgba(255,255,255,0.1);padding:20px;border-radius:10px;max-width:500px;margin:auto;}
                .counter{font-size:24px;margin:15px 0;color:#00e5c8;}
                </style></head><body>
                <div class='progress-box'>
                <h2>📧 Sending Due Reminders</h2>
                <p>Total recipients: <strong>$total</strong></p>
                <div class='counter' id='counter'>Sent: 0 (Failed: 0)</div>
                </div>
                <script>
                function updateCounter(sent, failed) {
                    document.getElementById('counter').innerHTML = 'Sent: ' + sent + ' (Failed: ' + failed + ')';
                }
                </script>";
        flush();

        foreach ($due_customers as $row) {
            $due = $row['due_amount'];
            if (sendDueReminder($row['email'], $row['customer_name'], $row['invoice_number'], $due, $subject, $custom_message)) {
                $sent_count++;
            } else {
                $fail_count++;
            }
            echo "<script>updateCounter($sent_count, $fail_count);</script>";
            flush();
            usleep(300000);
        }

        echo "<script>alert('✅ Bulk reminders sent: $sent_count successful, $fail_count failed.'); window.location.href='customer_due_list.php';</script>";
        exit;
    }
}

// Fetch due customers for display
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT i.id, i.invoice_number, i.invoice_date, i.due_date, i.total, i.paid_amount, 
               (i.total - i.paid_amount) as due_amount, i.status,
               c.id as customer_id, c.name as customer_name, c.email, c.phone
        FROM invoices_new i
        JOIN customers c ON i.customer_id = c.id
        WHERE i.total > i.paid_amount";
if (!empty($search)) {
    $search_term = "%$search%";
    $sql .= " AND (c.name LIKE ? OR i.invoice_number LIKE ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $due_customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conn->query($sql);
    $due_customers = $result->fetch_all(MYSQLI_ASSOC);
}

$total_due_sum = array_sum(array_column($due_customers, 'due_amount'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Due Payment List — AR TECH SOLUTION</title>
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

/* SEARCH & BULK CARD */
.action-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 20px;
    margin-bottom: 24px;
}
.search-form {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
}
.search-form input {
    flex: 1;
    padding: 10px 16px;
    background: rgba(255,255,255,0.08);
    border: 1px solid var(--glass-border);
    border-radius: 30px;
    color: #fff;
}
.search-form button {
    padding: 10px 24px;
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    border: none;
    border-radius: 30px;
    color: #000;
    font-weight: 700;
    cursor: pointer;
}
.bulk-form {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--glass-border);
}
.bulk-form textarea, .bulk-form input {
    width: 100%;
    margin-bottom: 10px;
    padding: 10px;
    background: rgba(255,255,255,0.08);
    border: 1px solid var(--glass-border);
    border-radius: 10px;
    color: #fff;
}
.bulk-form button {
    background: linear-gradient(135deg, #d35400, #e67e22);
    color: white;
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
    font-size: 11px;
}
td {
    color: #fff;
}
.due-amount {
    color: var(--accent3);
    font-weight: 700;
}
.btn-send {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    color: white;
    border: none;
    padding: 4px 12px;
    border-radius: 20px;
    cursor: pointer;
}
.total-due {
    text-align: right;
    margin-top: 16px;
    font-family: var(--mono);
    font-size: 18px;
    color: var(--accent3);
}
.alert {
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 20px;
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
.alert-warning {
    background: rgba(255,209,102,0.15);
    border: 1px solid var(--accent4);
    color: var(--accent4);
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
}
</style>
</head>
<body class="<?php echo $dark_mode ? 'dark-mode' : ''; ?>">

<nav class="topnav">
    <div style="display:flex;align-items:center;gap:14px;">
        <button class="hamburger" id="hamburgerBtn">☰</button>
        <div class="topnav-brand"><div class="brand-dot"></div><span>AR TECH</span> SOLUTION</div>
    </div>
    <div class="topnav-right">
        <div class="topnav-time" id="liveClock"></div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<!-- SIDEBAR (dynamic labels) -->
<?php
include 'navigation.php';
?>

<div class="sidebar-toggle-pill" id="sidebarToggle">◀</div>

<main class="main" id="mainContent">
    <div class="section-title">📋 Customer Due Payment List</div>
    <?php echo $message_status; ?>

    <div class="action-card">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search by Customer Name or Invoice Number" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">🔍 Search</button>
            <?php if ($search): ?>
                <a href="customer_due_list.php" style="background:#e74c3c; color:#fff; padding:10px 20px; border-radius:30px; text-decoration:none;">Clear</a>
            <?php endif; ?>
        </form>

        <div class="bulk-form">
            <form method="POST" onsubmit="return confirm('Send reminders to ALL due customers shown below? This may take a few moments.');">
                <input type="text" name="bulk_subject" placeholder="Email Subject (optional)" value="Payment Reminder - Due Balance">
                <textarea name="bulk_message" rows="3" placeholder="Custom message (optional). Leave empty for default template. The customer name, invoice number and due amount will be auto-added."></textarea>
                <input type="text" name="filter_category" placeholder="Filter by name/invoice (optional) - leave blank to send to all" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" name="send_bulk">📧 Send Bulk Reminders</button>
            </form>
        </div>
    </div>

    <div class="table-card">
        <?php if (empty($due_customers)): ?>
            <div style="text-align:center; padding:40px;">✅ No customers with due payments found.</div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice #</th><th>Customer</th><th>Email</th><th>Phone</th>
                            <th>Invoice Date</th><th>Due Date</th><th>Total (৳)</th>
                            <th>Paid (৳)</th><th>Due (৳)</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($due_customers as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['invoice_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo date('d-m-Y', strtotime($row['invoice_date'])); ?></td>
                                <td><?php echo date('d-m-Y', strtotime($row['due_date'])); ?></td>
                                <td><?php echo number_format($row['total'], 2); ?></td>
                                <td><?php echo number_format($row['paid_amount'], 2); ?></td>
                                <td class="due-amount">৳ <?php echo number_format($row['due_amount'], 2); ?></td>
                                <td>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($row['email']); ?>">
                                        <input type="hidden" name="name" value="<?php echo htmlspecialchars($row['customer_name']); ?>">
                                        <input type="hidden" name="invoice_number" value="<?php echo htmlspecialchars($row['invoice_number']); ?>">
                                        <input type="hidden" name="due_amount" value="<?php echo $row['due_amount']; ?>">
                                        <input type="hidden" name="subject" value="Payment Reminder - Invoice <?php echo htmlspecialchars($row['invoice_number']); ?>">
                                        <textarea name="custom_message" style="display:none;"></textarea>
                                        <button type="submit" name="send_single" class="btn-send">📧 Send Reminder</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="total-due">Total Due: ৳ <?php echo number_format($total_due_sum, 2); ?></div>
        <?php endif; ?>
    </div>
</main>

<div class="footer">
    &copy; <?php echo date("Y"); ?> AR TECH SOLUTION — Freelancing Student Management System
</div>

<script>
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