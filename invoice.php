<?php
session_start();

// ✅ 1. SETUP PHPMAILER & DB
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "freelancing";

$conn = new mysqli($servername, $username, "", $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// ====================================================
// ✅ 2. HANDLE AJAX REQUEST (Save Invoice & Email)
// ====================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['store_invoice'])) {
    $student_id = $conn->real_escape_string($_POST['student_id']);
    
    // A. Generate Invoice Number
    $invoice_no = "INV-" . date("YmdHis");
    $issue_date = date("d M Y");

    // B. Insert into Database
    $stmt = $conn->prepare("INSERT INTO invoices (invoice_no, student_id) VALUES (?, ?)");
    $stmt->bind_param("ss", $invoice_no, $student_id);
    $stmt->execute();
    $stmt->close();

    // C. Get Student Details for Email
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

        // D. Create "Perfect" HTML Invoice Design for Email
        $email_body = "
        <div style='font-family: Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; background-color: #ffffff;'>
            <div style='background-color: #2c3e50; color: #ffffff; padding: 30px; text-align: center;'>
                <h1 style='margin: 0; font-size: 24px; letter-spacing: 2px;'>INVOICE</h1>
                <p style='margin: 5px 0 0; font-size: 14px; opacity: 0.8;'>AR TECH SOLUTION</p>
            </div>

            <div style='padding: 30px;'>
                <table style='width: 100%; margin-bottom: 20px;'>
                    <tr>
                        <td style='vertical-align: top;'>
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
                    <thead>
                        <tr style='background-color: #f8f9fa;'>
                            <th style='padding: 12px; text-align: left; border-bottom: 2px solid #ddd; color: #2c3e50;'>Description</th>
                            <th style='padding: 12px; text-align: right; border-bottom: 2px solid #ddd; color: #2c3e50;'>Amount (TK)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style='padding: 15px; border-bottom: 1px solid #eee;'>$course Course Fee</td>
                            <td style='padding: 15px; text-align: right; border-bottom: 1px solid #eee;'>$fee</td>
                        </tr>
                        <tr>
                            <td style='padding: 15px; border-bottom: 1px solid #eee;'>Paid Amount</td>
                            <td style='padding: 15px; text-align: right; border-bottom: 1px solid #eee; color: green;'>- $paid</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td style='padding: 15px; text-align: right; font-weight: bold; color: #2c3e50;'>TOTAL DUE</td>
                            <td style='padding: 15px; text-align: right; font-weight: bold; color: #e74c3c; font-size: 18px;'>$due</td>
                        </tr>
                    </tfoot>
                </table>

                <div style='margin-top: 40px; padding-top: 20px; border-top: 1px dashed #ddd; text-align: center; color: #7f8c8d; font-size: 12px;'>
                    <p>Thank you for choosing AR Tech Solution.</p>
                    <p>For questions, contact: artechsolution.online@gmail.com</p>
                </div>
            </div>
        </div>";

        // E. Send Email via SMTP
        $mail = new PHPMailer(true);
        try {
            // 🔴 SMTP CONFIGURATION (FILL THIS IN) 🔴
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'artechsolution.online@gmail.com'; // <--- PUT YOUR EMAIL HERE
            $mail->Password   = 'giwr wrcr mnyi lkpf';    // <--- PUT APP PASSWORD HERE
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
            // Log error if needed: $mail->ErrorInfo
            $email_sent = false;
        }
    }

    echo json_encode(['invoice_no' => $invoice_no, 'email_sent' => $email_sent]);
    exit;
}

// ✅ 3. REGULAR PAGE DISPLAY
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
<title>Invoice & Email</title>
<style>
/* CSS RESET & CORE STYLES */
* { box-sizing: border-box; }
body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; background-color: #f4f7f6; color: #333; }

/* NAVBAR */
.navbar { background-color: #1a1a1a; color: white; padding: 15px 30px; font-size: 22px; display: flex; justify-content: center; align-items: center; position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
.logout-btn { position: absolute; right: 20px; background: #e74c3c; color: white; padding: 8px 20px; text-decoration: none; border-radius: 20px; font-size: 14px; transition: 0.3s; }
.logout-btn:hover { background: #c0392b; }

/* SIDEBAR */
.side-nav { position: fixed; top: 60px; left: 0; width: 220px; height: 100%; background: #2c3e50; padding-top: 20px; z-index: 999; transition: transform 0.3s ease; }
.side-nav.collapsed { transform: translateX(-220px); }
.side-nav a, .menu-toggle { color: #ecf0f1; padding: 12px 20px; display: block; text-decoration: none; font-weight: 500; cursor: pointer; border-left: 4px solid transparent; }
.side-nav a:hover, .menu-toggle:hover { background: #34495e; border-left-color: #1abc9c; }
.submenu { display: none; background: #233342; }
.submenu a { padding-left: 40px; font-size: 14px; }
.menu-group.active .submenu { display: block; }
.toggle-arrow { position: fixed; top: 75px; left: 220px; background: #1abc9c; color: white; padding: 5px 10px; cursor: pointer; z-index: 1001; transition: left 0.3s; }
.toggle-arrow.collapsed { left: 0; }

/* CONTENT AREA */
.container { margin-left: 240px; padding: 100px 30px 50px; transition: margin-left 0.3s; }
.container.collapsed { margin-left: 40px; }

/* FORM & INVOICE */
.search-box { text-align: center; margin-bottom: 30px; }
input[type="text"] { padding: 10px; width: 250px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
button { padding: 10px 20px; background: #1abc9c; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
button:hover { background: #16a085; }

.invoice-wrapper { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border: 1px solid #e0e0e0; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
.invoice-header { display: flex; justify-content: space-between; border-bottom: 2px solid #1abc9c; padding-bottom: 20px; margin-bottom: 30px; }
.invoice-header h1 { margin: 0; color: #1abc9c; font-size: 32px; }
.company-details { text-align: right; font-size: 14px; color: #555; }

table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th { background: #1abc9c; color: white; padding: 12px; text-align: left; }
td { padding: 12px; border-bottom: 1px solid #eee; }
.total-row td { font-weight: bold; font-size: 18px; border-top: 2px solid #333; }
.red-text { color: #e74c3c; }

.action-buttons { text-align: center; margin-top: 30px; }
.print-btn { background: #3498db; padding: 12px 30px; font-size: 18px; display: inline-flex; align-items: center; gap: 10px; }
.print-btn:disabled { background: #95a5a6; cursor: not-allowed; }

/* PRINT MODE */
@media print {
    body * { visibility: hidden; }
    .invoice-wrapper, .invoice-wrapper * { visibility: visible; }
    .invoice-wrapper { position: absolute; top: 0; left: 0; width: 100%; box-shadow: none; border: none; }
    .action-buttons, .navbar, .side-nav, .toggle-arrow, .search-box { display: none !important; }
}
</style>
</head>
<body>

<div class="navbar">AR TECH SOLUTION <a href="logout.php" class="logout-btn">Logout</a></div>
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
        <div class="submenu horizontal-submenu">
            <a href="insert.php">Add Student</a>
            <a href="student_list.php">Total Student List</a>
            <a href="form_view.php">Student Form</a>
            <a href="completed_students.php">Course Complete</a>
            <a href="incomplete_students.php">Course Incomplete</a>
            <a href="ongoing_students.php">Ongoing</a>
        </div>
    </div>

    <a href="delete.php">🗑️ Delete</a>
    <a href="report.php">📄 Report</a>

    <div class="menu-group">
        <div class="menu-toggle">💵 Payment ▾</div>
        <div class="submenu horizontal-submenu">
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
            <a href="attendance_report.php">View attendance Report</a>
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
    
    <a href="account_info.php">🕒 Account</a>
</div>

<div class="toggle-arrow" id="toggleBtn">◀</div>


<div class="container" id="mainContent">
    <div class="search-box">
        <form method="POST">
            <input type="text" name="student_id" placeholder="Enter Student ID" required>
            <button type="submit" name="search_student">🔍 Find Student</button>
        </form>
    </div>

    <?php if($student): ?>
    <div class="invoice-wrapper">
        <div class="invoice-header">
            <h1>INVOICE</h1>
            <div class="company-details">
                <strong>AR TECH SOLUTION</strong><br>
                Dhaka, Bangladesh<br>
                Email: artechsolution.online@gmail.com<br>
                Date: <?= date("d M Y") ?>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; margin-bottom:30px;">
            <div>
                <span style="color:#7f8c8d; font-size:12px;">BILL TO:</span><br>
                <strong><?= htmlspecialchars($student['name']) ?></strong><br>
                ID: <?= htmlspecialchars($student['student_id']) ?><br>
                <?= htmlspecialchars($student['email']) ?>
            </div>
            <div style="text-align:right;">
                <span style="color:#7f8c8d; font-size:12px;">INVOICE NO:</span><br>
                <strong id="displayInvoiceNo" style="color:#e74c3c;">PENDING...</strong>
            </div>
        </div>

        <table>
            <thead>
                <tr><th>Description</th><th style="text-align:right;">Amount</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($student['course_category']) ?> Course Fee</td>
                    <td style="text-align:right;"><?= number_format($student['course_fee'], 2) ?> ৳</td>
                </tr>
                <tr>
                    <td>Paid Amount</td>
                    <td style="text-align:right; color:green;">- <?= number_format($student['paid_fee'], 2) ?> ৳</td>
                </tr>
                <tr class="total-row">
                    <td>Total Due</td>
                    <td style="text-align:right;" class="red-text"><?= number_format($student['course_fee'] - $student['paid_fee'], 2) ?> ৳</td>
                </tr>
            </tbody>
        </table>

        <div class="action-buttons">
            <button class="print-btn" id="printBtn">
                🖨️ Save, Email & Print
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Print & Email Logic
const printBtn = document.getElementById('printBtn');
if(printBtn) {
    printBtn.addEventListener('click', function() {
        const studentId = "<?= $student['student_id'] ?? '' ?>";
        const btnText = printBtn.innerHTML;
        
        // 1. Change Button State
        printBtn.innerHTML = "⏳ Sending Email...";
        printBtn.disabled = true;

        // 2. AJAX Request
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'store_invoice=1&student_id=' + studentId
        })
        .then(response => response.json())
        .then(data => {
            // 3. Update UI
            document.getElementById('displayInvoiceNo').textContent = data.invoice_no;
            
            // 4. Alert Result
            if(data.email_sent) {
                alert("✅ Invoice Saved!\n📧 Email sent successfully to the student.");
            } else {
                alert("⚠️ Invoice Saved, but Email Failed (Check SMTP settings).");
            }

            // 5. Trigger Print
            window.print();

            // 6. Reset Button
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

// Sidebar Logic
const toggleBtn = document.getElementById('toggleBtn');
const sidebar = document.getElementById('sidebar');
const main = document.getElementById('mainContent');
toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    main.classList.toggle('collapsed');
    toggleBtn.classList.toggle('collapsed');
    toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '▶' : '◀';
});
document.querySelectorAll('.menu-toggle').forEach(t => t.addEventListener('click', () => t.parentElement.classList.toggle('active')));
</script>

</body>
</html>