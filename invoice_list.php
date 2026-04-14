<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "freelancing";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Invoices - AR TECH SOLUTION</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=Sora:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f0f2f5;
            --surface: #ffffff;
            --border: #e2e6ea;
            --accent: #00b894;
            --danger: #d63031;
            --dark: #1e2a3a;
            --muted: #636e72;
            --mono: 'IBM Plex Mono', monospace;
            --sans: 'Sora', sans-serif;
            --radius: 12px;
            --shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: var(--sans); background: var(--bg); color: #2d3436; }

        /* Navbar, Sidebar, Toggle (same as POS page) */
        .navbar {
            background: linear-gradient(135deg, #1e2a3a, #0f1722);
            color: #fff; height: 60px; display: flex;
            align-items: center; justify-content: center;
            position: fixed; top:0; left:0; width:100%; z-index:1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .brand { font-size: 20px; font-weight: 700; letter-spacing:1px; }
        .logout-btn {
            position: absolute; right:20px;
            background:#e74c3c; color:#fff; padding:6px 18px;
            border-radius:30px; text-decoration:none; font-size:13px; font-weight:600;
        }
        .logout-btn:hover { background:#c0392b; }

        .side-nav {
            position: fixed; top:60px; left:0; width:220px;
            height:calc(100% - 60px); background:#2c3e50;
            padding-top:20px; z-index:1000; overflow-y:auto;
            transition: transform .3s ease;
        }
        .side-nav.collapsed { transform:translateX(-100%); }
        .side-nav a, .menu-toggle {
            color:#fff; text-decoration:none; padding:12px 25px;
            display:block; font-weight:600; font-size:13px;
            border-left:4px solid transparent; cursor:pointer;
        }
        .side-nav a:hover, .menu-toggle:hover { background:#34495e; border-left-color:#1abc9c; }
        .menu-group .submenu { display:none; flex-direction:column; background:#34495e; }
        .menu-group.active .submenu { display:block; }
        .submenu a { padding:10px 40px; font-weight:400; }

        .toggle-arrow {
            position: fixed; top:70px; left:220px;
            background:#1abc9c; color:#fff; padding:6px 10px;
            border-radius:0 5px 5px 0; cursor:pointer; z-index:1001;
            font-size:18px; transition:left .3s ease;
        }
        .toggle-arrow.collapsed { left:0; }

        .container {
            margin-left:240px; padding:76px 28px 70px;
            transition:margin-left .3s ease;
        }
        .container.collapsed { margin-left:20px; }

        /* Page specific */
        .page-title { font-size:22px; font-weight:700; color:var(--dark); margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .search-bar { background:var(--surface); border-radius:var(--radius); padding:15px 20px; margin-bottom:25px; box-shadow:var(--shadow); display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
        .search-bar input { flex:2; min-width:200px; padding:10px 15px; border:1.5px solid var(--border); border-radius:40px; font-family:var(--sans); font-size:14px; }
        .search-bar button { background:var(--accent); color:#fff; border:none; padding:10px 25px; border-radius:40px; font-weight:700; cursor:pointer; }
        .search-bar button:hover { background:#00a07a; }

        .invoice-table { width:100%; background:var(--surface); border-radius:var(--radius); box-shadow:var(--shadow); border-collapse:collapse; overflow:hidden; }
        .invoice-table th { background:var(--dark); color:#fff; padding:14px 12px; text-align:left; font-size:13px; font-weight:600; }
        .invoice-table td { padding:12px; border-bottom:1px solid var(--border); font-size:13px; vertical-align:middle; }
        .invoice-table tr:hover { background:#f8f9fb; }
        .badge {
            display:inline-block; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700;
        }
        .badge-paid { background:#d4edda; color:#155724; }
        .badge-unpaid { background:#f8d7da; color:#721c24; }
        .badge-partial { background:#fff3cd; color:#856404; }
        .badge-cancelled { background:#e2e3e5; color:#383d41; }
        .btn-print {
            background:#0984e3; color:#fff; border:none; padding:6px 12px; border-radius:6px;
            font-size:12px; cursor:pointer; font-weight:600; text-decoration:none; display:inline-block;
        }
        .btn-print:hover { background:#0766b5; }
        .empty-row td { text-align:center; padding:40px; color:var(--muted); font-style:italic; }

        .footer {
            background:#1a1a1a; color:#aaa; text-align:center; padding:12px;
            position:fixed; bottom:0; left:0; width:100%; font-size:13px; z-index:999;
        }
        @media (max-width:768px) {
            .container { margin-left:0; padding:70px 12px 60px; }
            .side-nav { transform:translateX(-100%); }
            .toggle-arrow { left:0; }
        }
    </style>
</head>
<body>
<div class="navbar">
    <div class="brand">🔌 AR TECH SOLUTION</div>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="side-nav" id="sidebar">
    <a href="dashboard.php">📊 Dashboard</a>
    <div class="menu-group"><div class="menu-toggle">💵 Account ▾</div><div class="submenu"><a href="account.php">Account Overview</a><a href="account_report.php">Account Report</a><a href="change_password.php">Change Password</a></div></div>
    <div class="menu-group"><div class="menu-toggle">👤 Student Information ▾</div><div class="submenu"><a href="insert.php">Add Student</a><a href="student_list.php">Total Student List</a><a href="form_view.php">Student Form</a><a href="completed_students.php">Course Complete</a><a href="incomplete_students.php">Course Incomplete</a><a href="ongoing_students.php">Ongoing</a></div></div>
    <div class="menu-group"><div class="menu-toggle">👥 Customers ▾</div><div class="submenu"><a href="add_customer.php">Add Customer</a><a href="customer_list.php">Customer List</a></div></div>
    <div class="menu-group"><div class="menu-toggle">🛠️ Services ▾</div><div class="submenu"><a href="services.php">Manage Services</a><a href="assign_service.php">Assign Service</a></div></div>
    <a href="delete.php">🗑️ Delete</a>
    <a href="report.php">📄 Report</a>
    <div class="menu-group active">
        <div class="menu-toggle">💵 Payment ▾</div>
        <div class="submenu">
            <a href="invoice_pos.php">🧾 POS Invoice</a>
            <a href="invoice_list.php">📋 Invoice List</a>
            <a href="invoice.php">Print Invoice</a>
            <a href="view_invoice.php">Verify Invoice</a>
            <a href="input_payment.php">Add Payment</a>
            <a href="payment_due.php">Due Payment List</a>
        </div>
    </div>
    <div class="menu-group"><div class="menu-toggle">📆 Attendance ▾</div><div class="submenu"><a href="attendance.php">Take Attendance</a><a href="attendance_report.php">View Attendance Report</a></div></div>
    <div class="menu-group"><div class="menu-toggle">📜 Certificate ▾</div><div class="submenu"><a href="upload_certificate.php">Upload Certificate</a><a href="certificate_list.php">View Certificate</a></div></div>
    <div class="menu-group"><div class="menu-toggle">🎬 Video ▾</div><div class="submenu"><a href="upload_video.php">Upload Video</a><a href="view_videos.php">View Videos</a></div></div>
    <a href="routine_generator.php">🕒 Routine</a>
</div>
<div class="toggle-arrow" id="toggleBtn">◀</div>

<div class="container" id="mainContent">
    <div class="page-title"><span>📋</span> All Invoices</div>

    <!-- Search Form -->
    <div class="search-bar">
        <form method="GET" style="display:flex; gap:12px; width:100%; flex-wrap:wrap;">
            <input type="text" name="search" placeholder="Search by Invoice Number or Customer Name" value="<?= htmlspecialchars($search) ?>">
            <button type="submit">🔍 Search</button>
            <?php if (!empty($search)): ?>
                <a href="invoice_list.php" style="background:#6c757d; color:#fff; padding:10px 25px; border-radius:40px; text-decoration:none; font-weight:700;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <table class="invoice-table">
        <thead>
            <tr><th>Invoice #</th><th>Customer</th><th>Date</th><th>Due Date</th><th>Total (৳)</th><th>Paid (৳)</th><th>Balance</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php if (empty($invoices)): ?>
                <tr class="empty-row"><td colspan="9">No invoices found.</td></tr>
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
                        <td><strong><?= htmlspecialchars($inv['invoice_number']) ?></strong></td>
                        <td><?= htmlspecialchars($inv['customer_name']) ?></td>
                        <td><?= date('d-m-Y', strtotime($inv['invoice_date'])) ?></td>
                        <td><?= date('d-m-Y', strtotime($inv['due_date'])) ?></td>
                        <td><?= number_format($inv['total'], 2) ?></td>
                        <td><?= number_format($inv['paid_amount'], 2) ?></td>
                        <td><?= number_format($balance, 2) ?></td>
                        <td><span class="badge <?= $statusClass ?>"><?= ucfirst($inv['status']) ?></span></td>
                        <td>
                            <button class="btn-print" onclick="printInvoice(<?= $inv['id'] ?>)">🖨️ Print</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="footer">&copy; <?= date("Y") ?> AR TECH SOLUTION | Freelancing Student Management System</div>

<script>
    // Sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleBtn');
    const mainContent = document.getElementById('mainContent');
    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        toggleBtn.classList.toggle('collapsed');
        mainContent.classList.toggle('collapsed');
        toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '▶' : '◀';
    });
    document.querySelectorAll('.menu-toggle').forEach(t => {
        t.addEventListener('click', () => t.parentElement.classList.toggle('active'));
    });

    function printInvoice(invoiceId) {
        // Open print_invoice.php in a new window that automatically prints
        const printWindow = window.open(`print_invoice.php?id=${invoiceId}`, '_blank', 'width=800,height=600');
        printWindow.focus();
    }
</script>
</body>
</html>