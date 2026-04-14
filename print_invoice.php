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
    <title>Print Invoice - <?= htmlspecialchars($invoice['invoice_number']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=Sora:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sora', sans-serif;
            background: #f0f2f5;
            color: #2d3436;
        }

        /* Navbar & Sidebar (identical to POS) */
        .navbar {
            background: linear-gradient(135deg, #1e2a3a, #0f1722);
            color: #fff;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .brand { font-size: 20px; font-weight: 700; letter-spacing: 1px; }
        .logout-btn {
            position: absolute;
            right: 20px;
            background: #e74c3c;
            color: #fff;
            padding: 6px 18px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
        .logout-btn:hover { background: #c0392b; }

        .side-nav {
            position: fixed;
            top: 60px;
            left: 0;
            width: 220px;
            height: calc(100% - 60px);
            background: #2c3e50;
            padding-top: 20px;
            z-index: 1000;
            overflow-y: auto;
            transition: transform .3s ease;
        }
        .side-nav.collapsed { transform: translateX(-100%); }
        .side-nav a, .menu-toggle {
            color: #fff;
            text-decoration: none;
            padding: 12px 25px;
            display: block;
            font-weight: 600;
            font-size: 13px;
            border-left: 4px solid transparent;
            cursor: pointer;
        }
        .side-nav a:hover, .menu-toggle:hover {
            background: #34495e;
            border-left-color: #1abc9c;
        }
        .menu-group .submenu {
            display: none;
            flex-direction: column;
            background: #34495e;
        }
        .menu-group.active .submenu { display: block; }
        .submenu a { padding: 10px 40px; font-weight: 400; }

        .toggle-arrow {
            position: fixed;
            top: 70px;
            left: 220px;
            background: #1abc9c;
            color: #fff;
            padding: 6px 10px;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            z-index: 1001;
            font-size: 18px;
            transition: left .3s ease;
        }
        .toggle-arrow.collapsed { left: 0; }

        .container {
            margin-left: 240px;
            padding: 76px 28px 70px;
            transition: margin-left .3s ease;
        }
        .container.collapsed { margin-left: 20px; }

        /* Invoice print area */
        .print-wrapper {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.1);
            padding: 30px;
        }

        /* Invoice styling (matches your POS receipt style) */
        .pi-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            border-bottom: 2px solid #1e2a3a;
            padding-bottom: 15px;
        }
        .pi-logo { font-size: 22px; font-weight: 800; color: #1e2a3a; }
        .pi-logo small { display: block; font-size: 12px; font-weight: 400; color: #636e72; margin-top: 2px; }
        .pi-inv { text-align: right; }
        .inv-num {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 18px;
            font-weight: 700;
            color: #00b894;
        }
        .inv-date { font-size: 12px; color: #636e72; margin-top: 4px; }

        .pi-parties {
            display: flex;
            justify-content: space-between;
            margin-bottom: 24px;
            gap: 20px;
        }
        .pi-party { flex: 1; }
        .pi-party h4 {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: #636e72;
            margin-bottom: 6px;
        }
        .pi-party p { font-size: 13px; line-height: 1.6; }

        .pi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .pi-table th {
            background: #1e2a3a;
            color: #fff;
            padding: 9px 12px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .pi-table td {
            padding: 9px 12px;
            border-bottom: 1px solid #e2e6ea;
            font-size: 13px;
        }
        .pi-table tfoot td {
            font-weight: 700;
            border-top: 2px solid #1e2a3a;
        }
        .pi-total-row td {
            background: #00b894;
            color: #fff;
            font-size: 15px;
        }
        .pi-footer-note {
            font-size: 11px;
            color: #636e72;
            text-align: center;
            margin-top: 30px;
            border-top: 1px dashed #e2e6ea;
            padding-top: 12px;
        }

        /* Action buttons */
        .action-buttons {
            text-align: center;
            margin-bottom: 20px;
        }
        .action-buttons button {
            background: #0984e3;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            margin: 0 6px;
            transition: background 0.2s;
        }
        .action-buttons button:hover { background: #0766b5; }
        .action-buttons .close-btn { background: #6c757d; }
        .action-buttons .close-btn:hover { background: #5a6268; }

        /* Footer */
        .footer {
            background: #1a1a1a;
            color: #aaa;
            text-align: center;
            padding: 12px;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            font-size: 13px;
            z-index: 999;
        }

        /* Print styles */
        @media print {
            /* Hide everything except the invoice wrapper */
            .navbar, .side-nav, .toggle-arrow, .footer, .action-buttons, .container > *:not(.print-wrapper) {
                display: none !important;
            }
            body, .container, .print-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                background: white;
                box-shadow: none;
            }
            .container {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            .print-wrapper {
                max-width: 100%;
                padding: 0.5cm;
            }
            .pi-header {
                border-bottom-width: 1px;
            }
            .pi-table th, .pi-table td {
                border: 1px solid #ddd;
            }
            @page {
                size: A4;
                margin: 1.5cm;
            }
        }

        @media (max-width: 768px) {
            .container { margin-left: 0; padding: 70px 12px 60px; }
            .side-nav { transform: translateX(-100%); }
            .toggle-arrow { left: 0; }
            .print-wrapper { padding: 15px; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="brand">🔌 AR TECH SOLUTION</div>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<!-- SIDEBAR (same as other pages) -->
<div class="side-nav" id="sidebar">
    <a href="dashboard.php">📊 Dashboard</a>
    <div class="menu-group">
        <div class="menu-toggle">💵 Account ▾</div>
        <div class="submenu">
            <a href="account.php">Account Overview</a>
            <a href="account_report.php">Account Report</a>
            <a href="change_password.php">Change Password</a>
        </div>
    </div>
    <div class="menu-group">
        <div class="menu-toggle">👤 Student Information ▾</div>
        <div class="submenu">
            <a href="insert.php">Add Student</a>
            <a href="student_list.php">Total Student List</a>
            <a href="form_view.php">Student Form</a>
            <a href="completed_students.php">Course Complete</a>
            <a href="incomplete_students.php">Course Incomplete</a>
            <a href="ongoing_students.php">Ongoing</a>
        </div>
    </div>
    <div class="menu-group">
        <div class="menu-toggle">👥 Customers ▾</div>
        <div class="submenu">
            <a href="add_customer.php">Add Customer</a>
            <a href="customer_list.php">Customer List</a>
        </div>
    </div>
    <div class="menu-group">
        <div class="menu-toggle">🛠️ Services ▾</div>
        <div class="submenu">
            <a href="services.php">Manage Services</a>
            <a href="assign_service.php">Assign Service</a>
        </div>
    </div>
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
    <div class="menu-group">
        <div class="menu-toggle">📆 Attendance ▾</div>
        <div class="submenu">
            <a href="attendance.php">Take Attendance</a>
            <a href="attendance_report.php">View Attendance Report</a>
        </div>
    </div>
    <div class="menu-group">
        <div class="menu-toggle">📜 Certificate ▾</div>
        <div class="submenu">
            <a href="upload_certificate.php">Upload Certificate</a>
            <a href="certificate_list.php">View Certificate</a>
        </div>
    </div>
    <div class="menu-group">
        <div class="menu-toggle">🎬 Video ▾</div>
        <div class="submenu">
            <a href="upload_video.php">Upload Video</a>
            <a href="view_videos.php">View Videos</a>
        </div>
    </div>
    <a href="routine_generator.php">🕒 Routine</a>
</div>
<div class="toggle-arrow" id="toggleBtn">◀</div>

<!-- MAIN CONTENT -->
<div class="container" id="mainContent">
    <div class="action-buttons">
        <button onclick="window.print()">🖨️ Print Invoice</button>
        <button onclick="window.close()" class="close-btn">❌ Close</button>
    </div>

    <div class="print-wrapper" id="printInvoice">
        <div class="pi-header">
            <div>
                <div class="pi-logo">AR TECH SOLUTION<small>Freelancing & Training Center</small></div>
            </div>
            <div class="pi-inv">
                <div class="inv-num"><?= htmlspecialchars($invoice['invoice_number']) ?></div>
                <div class="inv-date">Date: <?= date('d M Y', strtotime($invoice['invoice_date'])) ?></div>
                <div class="inv-date">Due: <?= date('d M Y', strtotime($invoice['due_date'])) ?></div>
            </div>
        </div>

        <div class="pi-parties">
            <div class="pi-party">
                <h4>Billed To</h4>
                <p><strong><?= htmlspecialchars($invoice['customer_name']) ?></strong></p>
                <p style="font-size:12px;">
                    <?= htmlspecialchars($invoice['email'] ?? '') ?><br>
                    <?= htmlspecialchars($invoice['phone'] ?? '') ?>
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
                    <td><?= $counter++ ?></td>
                    <td><?= htmlspecialchars($item['description'] ?: ($item['service_name'] ?? 'N/A')) ?></td>
                    <td><?= $item['qty'] ?></td>
                    <td><?= number_format($item['unit_price'], 2) ?></td>
                    <td><?= number_format($item['total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="4">Subtotal</td><td><?= number_format($invoice['subtotal'], 2) ?></td></tr>
                <tr><td colspan="4">Discount</td><td><?= number_format($invoice['discount'], 2) ?></td></tr>
                <tr><td colspan="4"><strong>Total</strong></td><td><strong><?= number_format($invoice['total'], 2) ?></strong></td></tr>
                <tr><td colspan="4">Amount Paid</td><td><?= number_format($invoice['paid_amount'], 2) ?></td></tr>
                <tr class="pi-total-row"><td colspan="4"><strong>Balance Due</strong></td><td><strong><?= number_format($balance, 2) ?></strong></td></tr>
            </tfoot>
        </table>

        <?php if (!empty($invoice['notes'])): ?>
            <div style="margin-top:15px; font-size:12px; color:#555;">
                <strong>Notes:</strong> <?= nl2br(htmlspecialchars($invoice['notes'])) ?>
            </div>
        <?php endif; ?>

        <div class="pi-footer-note">
            Thank you for your business! · AR TECH SOLUTION · <?= date('Y') ?>
        </div>
    </div>
</div>

<div class="footer">
    &copy; <?= date("Y") ?> AR TECH SOLUTION | Freelancing Student Management System
</div>

<script>
    // Sidebar toggle functionality
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
</script>
</body>
</html>