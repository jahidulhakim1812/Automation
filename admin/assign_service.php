<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// ========== CREATE TABLES IN CORRECT ORDER ==========
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// 1. Customers table
$conn->query("CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// 2. Services table
$conn->query("CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    fee DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// 3. Invoices_new table
$conn->query("CREATE TABLE IF NOT EXISTS invoices_new (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(20) UNIQUE,
    customer_id INT NOT NULL,
    invoice_date DATE,
    due_date DATE,
    subtotal DECIMAL(10,2),
    discount DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2),
    paid_amount DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    status ENUM('unpaid','paid','partial','cancelled') DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// 4. Invoice_items table
$conn->query("CREATE TABLE IF NOT EXISTS invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    service_id INT,
    description VARCHAR(255),
    qty INT DEFAULT 1,
    unit_price DECIMAL(10,2),
    total DECIMAL(10,2),
    FOREIGN KEY (invoice_id) REFERENCES invoices_new(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// 5. Customer_services pivot table
$conn->query("CREATE TABLE IF NOT EXISTS customer_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    service_id INT NOT NULL,
    assign_date DATE,
    status ENUM('active','completed','cancelled') DEFAULT 'active',
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// Fetch customers and services
$customers = $conn->query("SELECT id, name, email, phone FROM customers ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$services  = $conn->query("SELECT id, service_name, fee FROM services ORDER BY service_name")->fetch_all(MYSQLI_ASSOC);

$message = "";
$error   = "";
$saved_inv_number = null;

// ========== SAVE INVOICE ==========
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_invoice'])) {
    $customer_id  = intval($_POST['customer_id'] ?? 0);
    $invoice_date = $_POST['invoice_date'] ?? date('Y-m-d');
    $due_date     = $_POST['due_date']     ?? date('Y-m-d');
    $discount     = floatval($_POST['discount']     ?? 0);
    $paid_amount  = floatval($_POST['paid_amount']  ?? 0);
    $notes        = trim($_POST['notes']            ?? '');
    $status       = $_POST['status']                ?? 'unpaid';
    $items_json   = $_POST['items_json']            ?? '[]';

    // New customer fields
    $new_name  = trim($_POST['new_customer_name'] ?? '');
    $new_email = trim($_POST['new_customer_email'] ?? '');
    $new_phone = trim($_POST['new_customer_phone'] ?? '');

    $allowed_status = ['unpaid', 'paid', 'partial', 'cancelled'];
    if (!in_array($status, $allowed_status)) $status = 'unpaid';

    $items_arr = json_decode($items_json, true);
    if (!is_array($items_arr)) $items_arr = [];

    // Process new customer if provided
    if (!empty($new_name)) {
        $stmt = $conn->prepare("INSERT INTO customers (name, email, phone) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $new_name, $new_email, $new_phone);
        if ($stmt->execute()) {
            $customer_id = $conn->insert_id;
        } else {
            $error = "Failed to add new customer: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($customer_id <= 0) {
        $error = "Please select a customer or enter a new customer name.";
    } elseif (empty($items_arr)) {
        $error = "Please add at least one item before saving.";
    } else {
        $subtotal = 0;
        foreach ($items_arr as $item) {
            $subtotal += floatval($item['total'] ?? 0);
        }
        $total = max(0, $subtotal - $discount);
        $paid_amount = min($paid_amount, $total);

        // Generate unique invoice number
        $date_part = date('Ymd', strtotime($invoice_date));
        do {
            $rand_part   = str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
            $inv_number  = "INV-{$date_part}-{$rand_part}";
            $check = $conn->prepare("SELECT id FROM invoices_new WHERE invoice_number = ?");
            $check->bind_param("s", $inv_number);
            $check->execute();
            $check->store_result();
            $exists = $check->num_rows > 0;
            $check->close();
        } while ($exists);

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare(
                "INSERT INTO invoices_new
                    (invoice_number, customer_id, invoice_date, due_date, subtotal, discount, total, paid_amount, notes, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "sississdss",
                $inv_number, $customer_id, $invoice_date, $due_date,
                $subtotal, $discount, $total, $paid_amount, $notes, $status
            );
            if (!$stmt->execute()) throw new Exception("Invoice insert failed: " . $stmt->error);
            $invoice_id = $conn->insert_id;
            $stmt->close();

            $item_stmt = $conn->prepare(
                "INSERT INTO invoice_items (invoice_id, service_id, description, qty, unit_price, total)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            foreach ($items_arr as $item) {
                $svc_id     = !empty($item['service_id']) ? intval($item['service_id']) : null;
                $desc       = substr(trim($item['description'] ?? ''), 0, 255);
                $qty        = max(1, intval($item['qty'] ?? 1));
                $unit_price = floatval($item['unit_price'] ?? 0);
                $item_total = floatval($item['total']      ?? 0);

                if ($svc_id === null) {
                    $item_stmt->bind_param("iisddd", $invoice_id, $svc_id, $desc, $qty, $unit_price, $item_total);
                } else {
                    $item_stmt->bind_param("iisddd", $invoice_id, $svc_id, $desc, $qty, $unit_price, $item_total);
                }
                if (!$item_stmt->execute()) throw new Exception("Item insert failed: " . $item_stmt->error);
            }
            $item_stmt->close();

            $conn->commit();
            $message = "Invoice <strong>{$inv_number}</strong> saved successfully! Total: ৳" . number_format($total, 2);
            $saved_inv_number = $inv_number;
            echo json_encode(['success' => true, 'invoice_no' => $inv_number]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    if ($error) {
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }
}

// Assign service (kept for completeness)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_service'])) {
    $customer_id = intval($_POST['customer_id']);
    $service_id  = intval($_POST['service_id']);
    $assign_date = $_POST['assign_date'] ?? date('Y-m-d');
    if ($customer_id > 0 && $service_id > 0) {
        $stmt = $conn->prepare("INSERT INTO customer_services (customer_id, service_id, assign_date) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $customer_id, $service_id, $assign_date);
        if ($stmt->execute()) $message = "Service assigned successfully!";
        else $error = "Error assigning service: " . $stmt->error;
        $stmt->close();
    } else {
        $error = "Please select both customer and service.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>POS Invoice — AR TECH SOLUTION</title>
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

/* ========== POS SPECIFIC STYLES ========== */
.pos-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 24px;
    align-items: start;
}
.card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    overflow: hidden;
}
.card-header {
    background: rgba(0,0,0,0.3);
    padding: 14px 20px;
    font-size: 13px; font-weight: 700;
    color: var(--accent);
    border-bottom: 1px solid var(--glass-border);
}
.card-body { padding: 20px; }
.form-row {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
}
.form-group {
    flex: 1;
    min-width: 160px;
    margin-bottom: 14px;
}
.form-group label {
    display: block;
    font-size: 11px; font-weight: 700;
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: .6px;
    margin-bottom: 6px;
}
.form-group select, .form-group input, .form-group textarea {
    width: 100%;
    padding: 9px 12px;
    background: rgba(255,255,255,0.08);
    border: 1px solid var(--glass-border);
    border-radius: 10px;
    color: #f0f0f0;
    font-family: var(--sans);
    font-size: 14px;
    outline: none;
}
.form-group select:focus, .form-group input:focus, .form-group textarea:focus {
    border-color: var(--accent);
}
.form-group select {
    color: #ffffff;
}
.form-group select option {
    color: #000000 !important;
    background: #ffffff !important;
}
.status-select {
    width: 100%;
    padding: 9px 12px;
    background: rgba(255,255,255,0.08);
    border: 1px solid var(--glass-border);
    border-radius: 10px;
    color: #ffffff;
}
.status-select option {
    color: #000000 !important;
    background: #ffffff !important;
}
#customer-info {
    background: rgba(0,229,200,0.08);
    border: 1px dashed var(--accent);
    border-radius: 10px;
    padding: 12px 16px;
    margin-top: 8px;
    display: none;
}
#customer-info.show { display: block; }
#customer-info strong { color: var(--accent); }
#ci-email, #ci-phone, #r-customer-contact {
    color: #ffffff !important;
}
.item-builder {
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--glass-border);
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 16px;
}
.item-builder-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1.2fr 1fr auto;
    gap: 8px;
    align-items: end;
}
.add-item-btn {
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    color: #000;
    border: none;
    border-radius: 10px;
    padding: 9px 14px;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    align-self: end;
}
.items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.items-table thead tr {
    background: rgba(0,0,0,0.3);
}
.items-table th {
    padding: 10px 12px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    color: var(--accent);
}
.items-table td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--glass-border);
    vertical-align: middle;
    color: #ffffff;
}
.del-btn {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    border: none;
    border-radius: 6px;
    width: 26px;
    height: 26px;
    cursor: pointer;
}
.empty-row td { text-align: center; color: var(--muted); padding: 28px; }
.receipt-card { position: sticky; top: 76px; }
.receipt-top {
    background: linear-gradient(135deg, #0f1722, #08121e);
    padding: 22px 20px;
    text-align: center;
}
.receipt-logo {
    font-size: 18px; font-weight: 800;
    letter-spacing: 2px; color: #fff;
}
.receipt-tagline { font-size: 10px; color: var(--muted); }
.receipt-inv-num {
    margin-top: 12px;
    font-family: var(--mono);
    font-size: 20px; font-weight: 700;
    color: var(--accent);
}
.receipt-body { padding: 18px; }
.r-section { margin-bottom: 14px; }
.r-label {
    font-size: 10px; font-weight: 700;
    text-transform: uppercase;
    color: #ffffff;
    margin-bottom: 4px;
}
.r-value { font-size: 14px; font-weight: 600; color: #ffffff; }
.r-divider { border: none; border-top: 1px dashed var(--glass-border); margin: 12px 0; }
.r-line {
    display: flex; justify-content: space-between;
    font-size: 13px; padding: 4px 0;
    color: #ffffff;
}
.r-subtotal-line {
    display: flex; justify-content: space-between;
    font-size: 13px; padding: 4px 0; color: #ffffff;
}
.discount-row {
    display: flex; align-items: center; gap: 8px;
    margin: 10px 0;
}
.discount-row label { font-size: 13px; color: #ffffff; font-weight: 600; }
.discount-row input {
    flex: 1;
    padding: 8px 10px;
    background: rgba(255,255,255,0.08);
    border: 1px solid var(--glass-border);
    border-radius: 8px;
    color: #f0f0f0;
}
.payment-details {
    background: rgba(6,214,160,0.08);
    border-radius: 10px;
    padding: 12px;
    margin: 12px 0;
}
.payment-line {
    display: flex; justify-content: space-between;
    padding: 6px 0; font-size: 13px;
    color: #ffffff;
}
.balance-line {
    border-top: 1px solid var(--accent);
    margin-top: 6px;
    padding-top: 8px;
    font-weight: 700;
    color: var(--accent);
}
.paid-input {
    width: 100%;
    padding: 8px;
    background: rgba(255,255,255,0.08);
    border: 1px solid var(--glass-border);
    border-radius: 8px;
    color: #f0f0f0;
    margin-top: 6px;
}
.r-total-box {
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    border-radius: 10px;
    padding: 14px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 12px 0;
}
.r-total-label { color: #000; font-weight: 700; }
.r-total-amt { font-family: var(--mono); font-size: 22px; font-weight: 700; color: #000; }
.btn-save, .btn-print, .btn-clear {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 40px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 8px;
}
.btn-save {
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    color: #000;
}
.btn-print {
    background: linear-gradient(135deg, var(--accent2), #9b59b6);
    color: #fff;
}
.btn-clear {
    background: transparent;
    border: 1px solid var(--accent3);
    color: var(--accent3);
}
.btn-clear:hover { background: var(--accent3); color: #fff; }
.alert {
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: center;
}
.alert-success { background: rgba(6,214,160,0.15); border: 1px solid var(--accent5); color: var(--accent5); }
.alert-error   { background: rgba(255,107,107,0.15); border: 1px solid var(--accent3); color: var(--accent3); }

.boosting-card {
    margin-top: 16px;
    background: rgba(0,229,200,0.05);
    border-radius: 12px;
    padding: 12px;
}
.boosting-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr auto;
    gap: 8px;
    align-items: end;
}
.boosting-row .form-group { margin-bottom: 0; }
.boosting-row .form-group label { font-size: 9px; }
.add-boost-btn {
    background: linear-gradient(135deg, #f39c12, #e67e22);
    color: #000;
    font-weight: 700;
    border: none;
    border-radius: 10px;
    padding: 9px 12px;
    cursor: pointer;
    align-self: end;
}
.boosting-info {
    font-size: 11px;
    color: var(--accent4);
    margin-top: 8px;
    text-align: center;
}

/* ========== PERFECT A4 PRINT STYLES (only header + table) ========== */
@media print {
    .topnav, .sidebar, .sidebar-toggle-pill, .btn-save, .btn-print, .btn-clear, .add-item-btn, .del-btn, .alert, .card:not(.receipt-card), .status-select, .discount-row, .paid-input, #customer-section, .footer {
        display: none !important;
    }
    .main {
        margin: 0 !important;
        padding: 0 !important;
        background: white;
    }
    .pos-grid {
        display: block !important;
    }
    .receipt-card {
        position: static !important;
        box-shadow: none !important;
        border: none !important;
        background: white;
    }
    #print-invoice {
        display: block !important;
    }
    @page {
        size: A4;
        margin: 1.5cm;
    }
    body {
        margin: 0;
        padding: 0;
    }
}
#print-invoice {
    display: none;
    max-width: 100%;
    margin: 0 auto;
    background: white;
    font-family: var(--sans);
    color: #000;
    padding: 0;
}
.print-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #b8860b;
    flex-wrap: wrap;
}
.print-logo img {
    height: 70px;
    width: auto;
    max-width: 120px;
    object-fit: contain;
}
.print-institute {
    flex: 1;
    text-align: center;
}
.print-institute-name {
    font-size: 24px;
    font-weight: bold;
    color: #b8860b;
}
.print-address, .print-contact {
    font-size: 12px;
    color: #555;
    margin-top: 4px;
}
.print-invoice-info {
    display: flex;
    justify-content: space-between;
    margin: 15px 0 20px;
    padding: 10px 0;
    border-top: 1px dashed #ccc;
    border-bottom: 1px dashed #ccc;
    flex-wrap: wrap;
}
.pi-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}
.pi-table th, .pi-table td {
    border: 1px solid #000;
    padding: 8px;
    text-align: left;
    font-size: 12px;
}
.pi-table th {
    background: #ddd;
    font-weight: 600;
}
.pi-table tfoot td {
    font-weight: 700;
}
.pi-total-row td {
    background: #f0f0f0;
}
.print-footer-note {
    font-size: 11px;
    text-align: center;
    margin-top: 30px;
    border-top: 1px dashed #ccc;
    padding-top: 12px;
    color: #666;
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
@media (max-width: 900px) {
    .pos-grid { grid-template-columns: 1fr; }
    .receipt-card { position: static; }
}
@media (max-width: 700px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.mobile-open { transform: translateX(0); }
    .sidebar-toggle-pill { display: none; }
    .hamburger { display: block; }
    .main { margin-left: 0 !important; padding-left: 16px; padding-right: 16px; }
    .item-builder-row { grid-template-columns: 1fr 1fr; }
    .boosting-row { grid-template-columns: 1fr 1fr; }
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

<!-- SIDEBAR (modern dashboard) -->
<?php include 'navigation.php'; ?>

<div class="sidebar-toggle-pill" id="sidebarToggle">◀</div>

<!-- MAIN CONTENT -->
<main class="main" id="mainContent">
    <div class="section-title">🧾 Point of Sale — Invoice Generator</div>

    <?php if ($message): ?>
        <div class="alert alert-success">✅ <?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" id="posForm">
        <input type="hidden" name="items_json" id="items_json">

        <div class="pos-grid">
            <!-- LEFT: Items Panel -->
            <div>
                <!-- Customer Section -->
                <div class="card" style="margin-bottom:18px;" id="customer-section">
                    <div class="card-header">👤 Customer Details</div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Select Customer</label>
                                <select name="customer_id" id="customer_select">
                                    <option value="">— Choose Existing Customer —</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?php echo $c['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($c['name']); ?>"
                                            data-email="<?php echo htmlspecialchars($c['email'] ?? ''); ?>"
                                            data-phone="<?php echo htmlspecialchars($c['phone'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($c['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Invoice Date</label>
                                <input type="date" name="invoice_date" id="invoice_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Due Date</label>
                                <input type="date" name="due_date" id="due_date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                            </div>
                        </div>

                        <!-- New Customer Fields -->
                        <div class="form-row" style="margin-top:6px; border-top:1px dashed var(--glass-border); padding-top:12px;">
                            <div class="form-group" style="flex:2;">
                                <label>Or New Customer Name</label>
                                <input type="text" name="new_customer_name" id="new_customer_name" placeholder="Type new customer name">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="new_customer_email" id="new_customer_email" placeholder="Email">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="new_customer_phone" id="new_customer_phone" placeholder="Phone">
                            </div>
                        </div>

                        <div id="customer-info">
                            <strong id="ci-name">—</strong><br>
                            <span id="ci-email"></span> &nbsp;|&nbsp; <span id="ci-phone"></span>
                        </div>
                    </div>
                </div>

                <!-- Item Builder -->
                <div class="card" style="margin-bottom:18px;">
                    <div class="card-header">➕ Add Item</div>
                    <div class="card-body">
                        <div class="item-builder">
                            <div class="item-builder-row">
                                <div class="form-group">
                                    <label>Service / Description</label>
                                    <select id="svc_select" onchange="autoFillPrice()">
                                        <option value="">— Pick Service or Custom —</option>
                                        <?php foreach ($services as $s): ?>
                                            <option value="<?php echo $s['id']; ?>" data-price="<?php echo $s['fee']; ?>" data-name="<?php echo htmlspecialchars($s['service_name']); ?>">
                                                <?php echo htmlspecialchars($s['service_name']); ?> (৳<?php echo number_format($s['fee'],2); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Custom Description</label>
                                    <input type="text" id="item_desc" placeholder="Custom item name">
                                </div>
                                <div class="form-group">
                                    <label>Unit Price (৳)</label>
                                    <input type="number" id="item_price" min="0" step="0.01" placeholder="0.00">
                                </div>
                                <div class="form-group">
                                    <label>Qty</label>
                                    <input type="number" id="item_qty" value="1" min="1">
                                </div>
                                <button type="button" class="add-item-btn" onclick="addItem()">+</button>
                            </div>
                        </div>

                        <!-- Boosting Service Section -->
                        <div class="boosting-card">
                            <div class="boosting-row">
                                <div class="form-group">
                                    <label>USD Amount</label>
                                    <input type="number" id="boost_usd" step="0.01" placeholder="0.00">
                                </div>
                                <div class="form-group">
                                    <label>USD Rate (৳)</label>
                                    <input type="number" id="boost_rate" step="0.01" placeholder="110.00">
                                </div>
                                <div class="form-group">
                                    <label>Service Charge (৳)</label>
                                    <input type="number" id="boost_charge" step="0.01" placeholder="0.00">
                                </div>
                                <button type="button" class="add-boost-btn" onclick="addBoostingItem()">+ Boost</button>
                            </div>
                            <div class="boosting-info">💡 Adds: "Boosting Service: $X USD @ rate Y = Z BDT + charge"</div>
                        </div>

                        <div style="overflow-x:auto;">
                            <table class="items-table" id="itemsTable">
                                <thead><tr><th>#</th><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th><th></th><tr></thead>
                                <tbody id="itemsBody"><tr class="empty-row" id="emptyRow"><td colspan="6">📦 No items added yet. Use the form above to add items.</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Notes & Status -->
                <div class="card">
                    <div class="card-header">📝 Notes & Payment Status</div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Payment Status</label>
                                <select name="status" id="status_select" class="status-select">
                                    <option value="unpaid">🔴 Unpaid</option>
                                    <option value="paid">🟢 Paid</option>
                                    <option value="partial">🟡 Partial</option>
                                    <option value="cancelled">⚫ Cancelled</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Notes / Remarks</label>
                                <textarea name="notes" placeholder="Any additional notes..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Receipt -->
            <div class="card receipt-card">
                <div class="receipt-top">
                    <div class="receipt-logo">AR TECH SOLUTION</div>
                    <div class="receipt-tagline">FREELANCING & TRAINING CENTER</div>
                    <div class="receipt-inv-num" id="r-inv-num">INV-<?php echo date('Ymd'); ?>-???</div>
                </div>
                <div class="receipt-body">
                    <div class="r-section">
                        <div class="r-label">Bill To</div>
                        <div class="r-value" id="r-customer">—</div>
                        <div style="font-size:12px; margin-top:2px;" id="r-customer-contact">Select a customer</div>
                    </div>
                    <div class="form-row" style="gap:10px;">
                        <div><div class="r-label">Invoice Date</div><div class="r-value" id="r-idate"><?php echo date('d M Y'); ?></div></div>
                        <div><div class="r-label">Due Date</div><div class="r-value" id="r-ddate"><?php echo date('d M Y', strtotime('+7 days')); ?></div></div>
                    </div>
                    <hr class="r-divider">
                    <div class="r-label" style="margin-bottom:8px;">Items</div>
                    <div id="r-items"><div style="font-size:13px; font-style:italic;">No items yet</div></div>
                    <hr class="r-divider">
                    <div class="r-subtotal-line"><span>Subtotal</span><span class="r-mono" id="r-subtotal">৳ 0.00</span></div>
                    <div class="discount-row">
                        <label>Discount (৳)</label>
                        <input type="number" name="discount" id="discount_input" min="0" step="0.01" value="0" oninput="updateReceipt()">
                    </div>
                    <div class="payment-details">
                        <div class="payment-line"><span class="label">Total Amount:</span><span class="amount" id="r_total_amount">৳ 0.00</span></div>
                        <div class="payment-line"><span class="label">Amount Paid:</span><input type="number" name="paid_amount" id="paid_amount_input" min="0" step="0.01" value="0" class="paid-input" oninput="updatePaidAmount()"></div>
                        <div class="payment-line balance-line"><span class="label">Balance Due:</span><span class="amount" id="r_balance_due">৳ 0.00</span></div>
                    </div>
                    <div class="r-total-box"><span class="r-total-label">TOTAL DUE</span><span class="r-total-amt" id="r-total">৳ 0.00</span></div>
                    <button type="submit" name="save_invoice" class="btn-save" onclick="prepareSubmit()">💾 Save Invoice</button>
                    <button type="button" class="btn-print" onclick="printInvoice()">🖨️ Print Invoice</button>
                    <button type="button" class="btn-clear" onclick="clearAll()">✕ Clear / New Invoice</button>
                </div>
            </div>
        </div>
    </form>

    <!-- PRINT-ONLY INVOICE LAYOUT (only header + table) -->
    <div id="print-invoice">
        <div class="print-header">
            <div class="print-logo">
                <img src="uploads/logo.png" alt="Institute Logo" onerror="this.style.display='none'">
            </div>
            <div class="print-institute">
                <div class="print-institute-name">AR TECH SOLUTION</div>
                <div class="print-address">Address: South Khailkur, Shahid Siddique road, Boardbazar, Gazipur-1704.</div>
                <div class="print-contact">📞 Mobile: +880 1957-288638 | ✉️ artechsolution.online@gmail.com</div>
            </div>
        </div>
        <div class="print-invoice-info">
            <span><strong>Invoice No:</strong> <span id="pi-invnum">—</span></span>
            <span><strong>Date:</strong> <span id="pi-date">—</span></span>
            <span><strong>Due Date:</strong> <span id="pi-due">—</span></span>
        </div>
        <!-- Billed To / From section removed -->
        <table class="pi-table">
            <thead><tr><th>#</th><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
            <tbody id="pi-tbody"></tbody>
            <tfoot>
                <tr><td colspan="4">Subtotal</td><td id="pi-subtotal">৳ 0.00</td></tr>
                <tr><td colspan="4">Discount</td><td id="pi-discount">৳ 0.00</td></tr>
                <tr><td colspan="4"><strong>Total</strong></td><td id="pi-total-display">৳ 0.00</td></tr>
                <tr><td colspan="4">Amount Paid</td><td id="pi-paid">৳ 0.00</td></tr>
                <tr class="pi-total-row"><td colspan="4"><strong>Balance Due</strong></td><td id="pi-balance">৳ 0.00</td></tr>
            </tfoot>
        </table>
        <div class="print-footer-note">Thank you for your business! · AR TECH SOLUTION · <?php echo date('Y'); ?></div>
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
        clockEl.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
}
updateClock();
setInterval(updateClock, 1000);

// ========== POS JAVASCRIPT ==========
<?php if (!empty($saved_inv_number)): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('r-inv-num').textContent = '<?php echo $saved_inv_number; ?>';
    document.getElementById('pi-invnum').textContent  = '<?php echo $saved_inv_number; ?>';
});
<?php endif; ?>

let items = [];
let itemCounter = 0;

function fmt(n) { return '৳ ' + parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); }

function autoFillPrice() {
    const sel = document.getElementById('svc_select');
    const opt = sel.options[sel.selectedIndex];
    if (opt.dataset.price) {
        document.getElementById('item_price').value = parseFloat(opt.dataset.price).toFixed(2);
        document.getElementById('item_desc').value = opt.dataset.name || '';
    }
}

function addItem() {
    const svcSel = document.getElementById('svc_select');
    const desc = document.getElementById('item_desc').value.trim() || svcSel.options[svcSel.selectedIndex]?.text.split('(')[0].trim() || '';
    const price = parseFloat(document.getElementById('item_price').value) || 0;
    const qty = parseInt(document.getElementById('item_qty').value) || 1;
    const svcId = svcSel.value || null;
    if (!desc || price <= 0) { alert('Please enter a description and price.'); return; }
    itemCounter++;
    const total = price * qty;
    items.push({ id: itemCounter, service_id: svcId, description: desc, qty, unit_price: price, total });
    renderItems();
    document.getElementById('svc_select').value = '';
    document.getElementById('item_desc').value = '';
    document.getElementById('item_price').value = '';
    document.getElementById('item_qty').value = '1';
}

function addBoostingItem() {
    let usd = parseFloat(document.getElementById('boost_usd').value);
    let rate = parseFloat(document.getElementById('boost_rate').value);
    let charge = parseFloat(document.getElementById('boost_charge').value);
    if (isNaN(usd)) usd = 0;
    if (isNaN(rate)) rate = 0;
    if (isNaN(charge)) charge = 0;
    if (usd <= 0 || rate <= 0) {
        alert('Please enter valid USD amount and rate.');
        return;
    }
    const bdt = usd * rate;
    const totalWithCharge = bdt + charge;
    const description = `Boosting Service: $${usd.toFixed(2)} USD @ rate ${rate.toFixed(2)} = ${bdt.toFixed(2)} BDT + service charge ${charge.toFixed(2)} BDT`;
    itemCounter++;
    items.push({
        id: itemCounter,
        service_id: null,
        description: description,
        qty: 1,
        unit_price: totalWithCharge,
        total: totalWithCharge
    });
    renderItems();
    document.getElementById('boost_usd').value = '';
    document.getElementById('boost_rate').value = '';
    document.getElementById('boost_charge').value = '';
}

function removeItem(id) {
    items = items.filter(i => i.id !== id);
    renderItems();
}

function renderItems() {
    const tbody = document.getElementById('itemsBody');
    if (items.length === 0) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="6">📦 No items added yet. Use the form above to add items.</td></tr>`;
        updateReceipt();
        return;
    }
    let html = '';
    items.forEach((item, idx) => {
        html += `<tr>
            <td>${idx+1}</td>
            <td>${escapeHtml(item.description)}</td>
            <td>${item.qty}</td>
            <td>${fmt(item.unit_price)}</td>
            <td><strong>${fmt(item.total)}</strong></td>
            <td><button type="button" class="del-btn" onclick="removeItem(${item.id})">✕</button></td>
        </tr>`;
    });
    tbody.innerHTML = html;
    updateReceipt();
}

function escapeHtml(str) { return str.replace(/[&<>]/g, function(m) { if (m === '&') return '&amp;'; if (m === '<') return '&lt;'; if (m === '>') return '&gt;'; return m; }); }

function getTotalDue() {
    const subtotal = items.reduce((s, i) => s + i.total, 0);
    const discount = parseFloat(document.getElementById('discount_input').value) || 0;
    return Math.max(0, subtotal - discount);
}

function updatePaidAmount() {
    const totalDue = getTotalDue();
    let paid = parseFloat(document.getElementById('paid_amount_input').value) || 0;
    if (paid > totalDue) { paid = totalDue; document.getElementById('paid_amount_input').value = paid.toFixed(2); }
    const balance = totalDue - paid;
    document.getElementById('r_balance_due').textContent = fmt(balance);
    document.getElementById('r-total').textContent = fmt(balance);
    const statusSelect = document.getElementById('status_select');
    if (balance <= 0 && totalDue > 0) statusSelect.value = 'paid';
    else if (paid > 0 && balance > 0) statusSelect.value = 'partial';
    else if (paid === 0 && totalDue > 0) statusSelect.value = 'unpaid';
    updatePrintPaidFields(totalDue, paid, balance);
}

function updatePrintPaidFields(totalDue, paid, balance) {
    document.getElementById('pi-total-display').textContent = fmt(totalDue);
    document.getElementById('pi-paid').textContent = fmt(paid);
    document.getElementById('pi-balance').textContent = fmt(balance);
}

function updateReceipt() {
    const subtotal = items.reduce((s, i) => s + i.total, 0);
    const discount = parseFloat(document.getElementById('discount_input').value) || 0;
    const totalDue = Math.max(0, subtotal - discount);
    let paid = parseFloat(document.getElementById('paid_amount_input').value) || 0;
    if (paid > totalDue) { paid = totalDue; document.getElementById('paid_amount_input').value = paid.toFixed(2); }
    const balance = totalDue - paid;
    document.getElementById('r-subtotal').textContent = fmt(subtotal);
    document.getElementById('r_total_amount').textContent = fmt(totalDue);
    document.getElementById('r_balance_due').textContent = fmt(balance);
    document.getElementById('r-total').textContent = fmt(balance);
    let rHtml = '';
    if (items.length === 0) rHtml = '<div style="font-size:13px; font-style:italic;">No items yet</div>';
    else items.forEach(i => { rHtml += `<div class="r-line"><span>${escapeHtml(i.description)} ×${i.qty}</span><span class="r-mono">${fmt(i.total)}</span></div>`; });
    document.getElementById('r-items').innerHTML = rHtml;
    let piHtml = '';
    items.forEach((item, idx) => { piHtml += `<tr><td>${idx+1}</td><td>${escapeHtml(item.description)}</td><td>${item.qty}</td><td>${fmt(item.unit_price)}</td><td>${fmt(item.total)}</td></tr>`; });
    document.getElementById('pi-tbody').innerHTML = piHtml;
    document.getElementById('pi-subtotal').textContent = fmt(subtotal);
    document.getElementById('pi-discount').textContent = fmt(discount);
    document.getElementById('pi-total-display').textContent = fmt(totalDue);
    document.getElementById('pi-paid').textContent = fmt(paid);
    document.getElementById('pi-balance').textContent = fmt(balance);
    const statusSelect = document.getElementById('status_select');
    if (balance <= 0 && totalDue > 0) statusSelect.value = 'paid';
    else if (paid > 0 && balance > 0) statusSelect.value = 'partial';
    else if (paid === 0 && totalDue > 0) statusSelect.value = 'unpaid';
}

function prepareSubmit() { document.getElementById('items_json').value = JSON.stringify(items); }

function clearAll() {
    if (!confirm('Clear all items and start a new invoice?')) return;
    items = []; itemCounter = 0;
    renderItems();
    document.getElementById('customer_select').value = '';
    document.getElementById('new_customer_name').value = '';
    document.getElementById('new_customer_email').value = '';
    document.getElementById('new_customer_phone').value = '';
    document.getElementById('customer-info').classList.remove('show');
    document.getElementById('r-customer').textContent = '—';
    document.getElementById('r-customer-contact').textContent = 'Select a customer';
    document.getElementById('discount_input').value = 0;
    document.getElementById('paid_amount_input').value = 0;
    document.getElementById('status_select').value = 'unpaid';
    document.getElementById('invoice_date').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('due_date').value = '<?php echo date('Y-m-d', strtotime('+7 days')); ?>';
    document.getElementById('r-inv-num').textContent = 'INV-<?php echo date('Ymd'); ?>-' + String(Math.floor(Math.random()*900)+100);
    updateReceipt();
}

function printInvoice() {
    const customerId = document.getElementById('customer_select').value;
    const newName = document.getElementById('new_customer_name').value.trim();
    if (!customerId && !newName) {
        alert('Please select an existing customer or enter a new customer name.');
        return;
    }
    if (items.length === 0) {
        alert('Please add at least one item before printing.');
        return;
    }
    const btn = document.querySelector('.btn-print');
    btn.disabled = true;
    btn.textContent = '⏳ Saving...';
    const formData = new URLSearchParams();
    formData.append('save_invoice', '1');
    formData.append('customer_id', customerId);
    formData.append('new_customer_name', newName);
    formData.append('new_customer_email', document.getElementById('new_customer_email').value.trim());
    formData.append('new_customer_phone', document.getElementById('new_customer_phone').value.trim());
    formData.append('invoice_date', document.getElementById('invoice_date').value);
    formData.append('due_date', document.getElementById('due_date').value);
    formData.append('discount', document.getElementById('discount_input').value);
    formData.append('paid_amount', document.getElementById('paid_amount_input').value);
    formData.append('notes', document.querySelector('textarea[name="notes"]').value);
    formData.append('status', document.getElementById('status_select').value);
    formData.append('items_json', JSON.stringify(items));
    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('r-inv-num').textContent = data.invoice_no;
            document.getElementById('pi-invnum').textContent = data.invoice_no;
            updateReceipt();
            window.print();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Failed to save invoice. Check console for details.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = '🖨️ Print Invoice';
    });
}

// Customer selection handler
document.getElementById('customer_select').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const name = opt.dataset.name || '';
    const email = opt.dataset.email || '';
    const phone = opt.dataset.phone || '';
    if (name) {
        document.getElementById('customer-info').classList.add('show');
        document.getElementById('ci-name').textContent = name;
        document.getElementById('ci-email').textContent = email;
        document.getElementById('ci-phone').textContent = phone;
        document.getElementById('r-customer').textContent = name;
        document.getElementById('r-customer-contact').textContent = (email ? email + '  ' : '') + (phone || '');
    } else {
        document.getElementById('customer-info').classList.remove('show');
        document.getElementById('r-customer').textContent = '—';
        document.getElementById('r-customer-contact').textContent = 'Select a customer';
    }
});

// New customer name input – preview in receipt
document.getElementById('new_customer_name').addEventListener('input', function() {
    const name = this.value.trim();
    if (name) {
        document.getElementById('customer-info').classList.add('show');
        document.getElementById('ci-name').textContent = name;
        document.getElementById('ci-email').textContent = document.getElementById('new_customer_email').value;
        document.getElementById('ci-phone').textContent = document.getElementById('new_customer_phone').value;
        document.getElementById('r-customer').textContent = name;
        const email = document.getElementById('new_customer_email').value;
        const phone = document.getElementById('new_customer_phone').value;
        document.getElementById('r-customer-contact').textContent = (email ? email + '  ' : '') + (phone || '');
    } else {
        // Fall back to selected customer if any
        const sel = document.getElementById('customer_select');
        const opt = sel.options[sel.selectedIndex];
        if (opt.value) {
            document.getElementById('ci-name').textContent = opt.dataset.name || '';
            document.getElementById('ci-email').textContent = opt.dataset.email || '';
            document.getElementById('ci-phone').textContent = opt.dataset.phone || '';
            document.getElementById('r-customer').textContent = opt.dataset.name || '';
            document.getElementById('r-customer-contact').textContent = (opt.dataset.email || '') + '  ' + (opt.dataset.phone || '');
        } else {
            document.getElementById('customer-info').classList.remove('show');
            document.getElementById('r-customer').textContent = '—';
            document.getElementById('r-customer-contact').textContent = 'Select a customer';
        }
    }
});

document.getElementById('new_customer_email').addEventListener('input', function() {
    const name = document.getElementById('new_customer_name').value.trim();
    if (name) {
        document.getElementById('ci-email').textContent = this.value;
        const phone = document.getElementById('new_customer_phone').value;
        document.getElementById('r-customer-contact').textContent = (this.value ? this.value + '  ' : '') + (phone || '');
    }
});
document.getElementById('new_customer_phone').addEventListener('input', function() {
    const name = document.getElementById('new_customer_name').value.trim();
    if (name) {
        document.getElementById('ci-phone').textContent = this.value;
        const email = document.getElementById('new_customer_email').value;
        document.getElementById('r-customer-contact').textContent = (email ? email + '  ' : '') + (this.value || '');
    }
});

document.getElementById('invoice_date').addEventListener('change', function() {
    const d = new Date(this.value);
    document.getElementById('r-idate').textContent = d.toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('pi-date').textContent = d.toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'});
});
document.getElementById('due_date').addEventListener('change', function() {
    const d = new Date(this.value);
    document.getElementById('r-ddate').textContent = d.toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('pi-due').textContent = d.toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'});
});
document.getElementById('discount_input').addEventListener('input', function() { updatePaidAmount(); updateReceipt(); });

// Initialise
updateReceipt();
</script>
</body>
</html>