<?php
session_start();

// Admin session check
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

// Setup PHPMailer & DB
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

require_once 'config.php';

// Handle AJAX request (Save Invoice & Email)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['store_invoice'])) {
    $student_id = $conn->real_escape_string($_POST['student_id']);
    
    $invoice_no = "INV-" . date("YmdHis");
    $issue_date = date("d M Y");

    $stmt = $conn->prepare("INSERT INTO invoices (invoice_no, student_id) VALUES (?, ?)");
    $stmt->bind_param("ss", $invoice_no, $student_id);
    $stmt->execute();
    $stmt->close();

    $stu_q = $conn->query("SELECT * FROM students WHERE student_id = '$student_id'");
    $student = $stu_q->fetch_assoc();

    $email_sent = false;

    if ($student && !empty($student['email'])) {
        $name = $student['name'];
        $email = $student['email'];
        $course = $student['course_category'];
        $fee = number_format($student['course_fee'], 2);
        $paid = number_format($student['paid_fee'], 2);
        $due = number_format($student['course_fee'] - $student['paid_fee'], 2);

        $email_body = "
        <div style='font-family: Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; background-color: #ffffff;'>
            <div style='background-color: #2c3e50; color: #ffffff; padding: 30px; text-align: center;'>
                <h1 style='margin: 0; font-size: 24px; letter-spacing: 2px;'>INVOICE</h1>
                <p style='margin: 5px 0 0; font-size: 14px; opacity: 0.8;'>AR TECH SOLUTION</p>
            </div>
            <div style='padding: 30px;'>
                <table style='width: 100%; margin-bottom: 20px;'>
                    <tr><td style='vertical-align: top;'>
                            <p style='margin: 0; color: #7f8c8d; font-size: 12px;'>BILLED TO</p>
                            <h3 style='margin: 5px 0; color: #2c3e50;'>$name</h3>
                            <p style='margin: 0; font-size: 14px;'>ID: $student_id</p>
                            <p style='margin: 0; font-size: 14px;'>$email</p>
                         </td>
                         <td style='text-align: right; vertical-align: top;'>
                            <p style='margin: 0; color: #7f8c8d; font-size: 12px;'>INVOICE NUMBER</p>
                            <h3 style='margin: 5px 0; color: #2c3e50;'>$invoice_no</h3>
                            <p style='margin: 0; color: #7f8c8d; font-size: 12px;'>DATE OF ISSUE</p>
                            <p style='margin: 0; font-size: 14px;'>$issue_date</p>
                         </td>
                    </tr>
                </table>
                <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                    <thead><tr style='background-color: #f8f9fa;'><th style='padding: 12px; text-align: left;'>Description</th><th style='padding: 12px; text-align: right;'>Amount (TK)</th></tr></thead>
                    <tbody>
                        <tr><td style='padding: 15px; border-bottom: 1px solid #eee;'>$course Course Fee</td><td style='padding: 15px; text-align: right; border-bottom: 1px solid #eee;'>$fee</td></tr>
                        <tr><td style='padding: 15px; border-bottom: 1px solid #eee;'>Paid Amount</td><td style='padding: 15px; text-align: right; border-bottom: 1px solid #eee; color: green;'>- $paid</td></tr>
                    </tbody>
                    <tfoot><tr><td style='padding: 15px; text-align: right; font-weight: bold;'>TOTAL DUE</td><td style='padding: 15px; text-align: right; font-weight: bold; color: #e74c3c; font-size: 18px;'>$due</td></tr></tfoot>
                </table>
                <div style='margin-top: 40px; padding-top: 20px; border-top: 1px dashed #ddd; text-align: center; color: #7f8c8d; font-size: 12px;'>
                    <p>Thank you for choosing AR Tech Solution.</p>
                    <p>For questions, contact: artechsolution.online@gmail.com</p>
                </div>
            </div>
        </div>";

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'artechsolution.online@gmail.com';
            $mail->Password   = 'giwr wrcr mnyi lkpf';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('admin@artechsolution.com', 'AR Tech Accounts');
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = "Invoice #$invoice_no from AR Tech Solution";
            $mail->Body    = $email_body;

            $mail->send();
            $email_sent = true;
        } catch (Exception $e) {
            $email_sent = false;
        }
    }

    echo json_encode(['invoice_no' => $invoice_no, 'email_sent' => $email_sent]);
    exit;
}

// Regular page display - search student
$student = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_student'])) {
    $student_id = $_POST['student_id'];
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
    } else {
        echo "<script>alert('Student ID not found');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice & Email — AR TECH SOLUTION</title>
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

/* ========== INVOICE PRINT WRAPPER (from print_invoice.php) ========== */
.print-wrapper {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 28px;
    max-width: 900px;
    margin: 0 auto;
}

/* Report Header */
.report-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--accent);
    flex-wrap: wrap;
}
.header-logo img {
    height: 70px;
    width: auto;
    max-width: 120px;
    object-fit: contain;
}
.header-text {
    flex: 1;
    text-align: center;
}
.institute-name {
    font-size: 24px;
    font-weight: bold;
    color: var(--accent);
}
.address, .contact {
    font-size: 12px;
    color: var(--muted);
    margin-top: 4px;
}

/* Invoice Info (number, dates) */
.invoice-info {
    display: flex;
    justify-content: space-between;
    margin: 15px 0 20px;
    padding: 10px 0;
    border-top: 1px dashed var(--glass-border);
    border-bottom: 1px dashed var(--glass-border);
    flex-wrap: wrap;
    gap: 12px;
}
.invoice-info-item {
    font-size: 13px;
}
.invoice-info-item strong {
    color: var(--accent);
}

/* Parties */
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

/* Table (perfect) */
.pi-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    font-size: 13px;
}
.pi-table th,
.pi-table td {
    border: 1px solid rgba(255,255,255,0.2);
    padding: 10px 8px;
    vertical-align: top;
}
.pi-table th {
    background: rgba(0,0,0,0.3);
    color: var(--accent);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
}
.pi-table td {
    color: #fff;
}
.pi-table tfoot td {
    font-weight: 700;
    background: rgba(0,0,0,0.2);
}
.pi-total-row td {
    background: var(--accent5);
    color: #000;
    font-weight: 700;
}

/* Footer note */
.pi-footer-note {
    font-size: 11px;
    color: var(--muted);
    text-align: center;
    margin-top: 30px;
    border-top: 1px dashed var(--glass-border);
    padding-top: 12px;
}

/* Action buttons (on screen) */
.action-buttons {
    text-align: center;
    margin-top: 30px;
}
.print-btn {
    background: linear-gradient(135deg, var(--accent2), #9b59b6);
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 40px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity .2s;
    font-size: 16px;
}
.print-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.print-btn:hover:not(:disabled) { opacity: .85; }

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

/* ===== PRINT STYLES (clean A4) ===== */
@media print {
    .topnav, .sidebar, .sidebar-toggle-pill, .search-card, .action-buttons, .footer {
        display: none !important;
    }
    body, .main, .print-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
        backdrop-filter: none !important;
        border: none !important;
        box-shadow: none !important;
    }
    .main {
        padding: 0 !important;
    }
    .print-wrapper {
        max-width: 100% !important;
        padding: 0.5cm !important;
        border-radius: 0 !important;
        background: white !important;
    }
    /* Force black text everywhere inside the wrapper */
    .print-wrapper * {
        color: black !important;
    }
    .report-header {
        border-bottom: 1px solid #000 !important;
    }
    .institute-name, .invoice-info-item strong, .pi-party h4 {
        color: #000 !important;
    }
    .address, .contact, .invoice-info-item {
        color: #333 !important;
    }
    .pi-table th, .pi-table td {
        border: 1px solid #000 !important;
        color: #000 !important;
    }
    .pi-table th {
        background: #ddd !important;
    }
    .pi-table td {
        background: white !important;
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
    .search-card form { flex-direction: column; }
    .search-card button { width: 100%; }
    .report-header { flex-direction: column; text-align: center; }
    .invoice-info { flex-direction: column; align-items: center; text-align: center; }
    .pi-table th, .pi-table td { padding: 6px 4px; font-size: 11px; }
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
    <!-- Search Card -->
    <div class="search-card">
        <form method="POST">
            <input type="text" name="student_id" placeholder="Enter Student ID" required>
            <button type="submit" name="search_student">🔍 Find Student</button>
        </form>
    </div>

    <?php if ($student): ?>
    <div class="print-wrapper" id="printInvoice">
        <!-- Header -->
        <div class="report-header">
            <div class="header-logo">
                <img src="uploads/logo.png" alt="Institute Logo" onerror="this.style.display='none'">
            </div>
            <div class="header-text">
                <div class="institute-name">AR TECH SOLUTION</div>
                <div class="address">Address: South Khailkur, Shahid Siddique road, Boardbazar, Gazipur-1704.</div>
                <div class="contact">📞 Mobile: +880 1957-288638 | ✉️ artechsolution.online@gmail.com</div>
            </div>
        </div>

        <!-- Invoice Info Row -->
        <div class="invoice-info">
            <div class="invoice-info-item"><strong>Invoice No:</strong> <span id="displayInvoiceNo">PENDING...</span></div>
            <div class="invoice-info-item"><strong>Date:</strong> <?php echo date('d M Y'); ?></div>
            <div class="invoice-info-item"><strong>Due Date:</strong> <?php echo date('d M Y', strtotime('+15 days')); ?></div>
        </div>

        <!-- Parties -->
        <div class="pi-parties">
            <div class="pi-party">
                <h4>Billed To</h4>
                <p><strong><?php echo htmlspecialchars($student['name']); ?></strong></p>
                <p style="font-size:12px;">
                    ID: <?php echo htmlspecialchars($student['student_id']); ?><br>
                    <?php echo htmlspecialchars($student['email'] ?? ''); ?><br>
                    <?php echo htmlspecialchars($student['phone'] ?? ''); ?>
                </p>
            </div>
            <div class="pi-party" style="text-align:right;">
                <h4>From</h4>
                <p><strong>AR TECH SOLUTION</strong><br>Freelancing & Training Center</p>
            </div>
        </div>

        <!-- Table – NO DISCOUNT -->
        <table class="pi-table">
            <thead>
                <tr>
                    <th style="width:5%;">#</th>
                    <th style="width:55%;">Description</th>
                    <th style="width:20%;">Amount (৳)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td><?php echo htmlspecialchars($student['course_category']); ?> Course Fee</td>
                    <td><?php echo number_format($student['course_fee'], 2); ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr><td colspan="2"><strong>Total</strong></td><td><strong><?php echo number_format($student['course_fee'], 2); ?></strong></td></tr>
                <tr><td colspan="2"><strong>Amount Paid</strong></td><td><?php echo number_format($student['paid_fee'], 2); ?></td></tr>
                <tr class="pi-total-row"><td colspan="2"><strong>Balance Due</strong></td><td><strong><?php echo number_format($student['course_fee'] - $student['paid_fee'], 2); ?></strong></td></tr>
            </tfoot>
        </table>

        <?php if (!empty($student['notes'])): ?>
        <div style="margin-top:15px; font-size:12px;">
            <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($student['notes'])); ?>
        </div>
        <?php endif; ?>

        <div class="pi-footer-note">
            Thank you for your business! · AR TECH SOLUTION · <?php echo date('Y'); ?>
        </div>
    </div>

    <div class="action-buttons">
        <button class="print-btn" id="printBtn">
            🖨️ Save, Email & Print
        </button>
    </div>
    <?php endif; ?>
</main>

<div class="footer">
    &copy; <?php echo date("Y"); ?> AR TECH SOLUTION — Freelancing Student Management System
</div>

<script>
// Print & Email Logic
const printBtn = document.getElementById('printBtn');
if (printBtn) {
    printBtn.addEventListener('click', function() {
        const studentId = "<?php echo $student['student_id'] ?? ''; ?>";
        const btnText = printBtn.innerHTML;

        printBtn.innerHTML = "⏳ Sending Email...";
        printBtn.disabled = true;

        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'store_invoice=1&student_id=' + studentId
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('displayInvoiceNo').textContent = data.invoice_no;
            if (data.email_sent) {
                alert("✅ Invoice Saved!\n📧 Email sent successfully to the student.");
            } else {
                alert("⚠️ Invoice Saved, but Email Failed (Check SMTP settings).");
            }
            window.print();
            printBtn.innerHTML = btnText;
            printBtn.disabled = false;
        })
        .catch(err => {
            console.error(err);
            alert("Error processing request.");
            printBtn.innerHTML = btnText;
            printBtn.disabled = false;
        });
    });
}

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