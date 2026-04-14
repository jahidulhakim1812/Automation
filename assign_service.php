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

// 3. Invoices_new table (used by POS – must exist for foreign key)
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

// 4. Invoice_items table (foreign key to invoices_new)
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

// 5. Customer_services pivot table (for assignments)
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

// Fetch customers and services for dropdowns
$customers = $conn->query("SELECT id, name, email, phone FROM customers ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$services  = $conn->query("SELECT id, service_name, fee FROM services ORDER BY service_name")->fetch_all(MYSQLI_ASSOC);

$message = "";
$error   = "";

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

    // Validate status
    $allowed_status = ['unpaid', 'paid', 'partial', 'cancelled'];
    if (!in_array($status, $allowed_status)) $status = 'unpaid';

    // Decode items
    $items_arr = json_decode($items_json, true);
    if (!is_array($items_arr)) $items_arr = [];

    if ($customer_id <= 0) {
        $error = "Please select a customer.";
    } elseif (empty($items_arr)) {
        $error = "Please add at least one item before saving.";
    } else {
        // Calculate totals
        $subtotal = 0;
        foreach ($items_arr as $item) {
            $subtotal += floatval($item['total'] ?? 0);
        }
        $total = max(0, $subtotal - $discount);
        $paid_amount = min($paid_amount, $total); // Can't pay more than total

        // Generate unique invoice number: INV-YYYYMMDD-XXX
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

        // Begin transaction
        $conn->begin_transaction();
        try {
            // Insert invoice
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

            if (!$stmt->execute()) {
                throw new Exception("Invoice insert failed: " . $stmt->error);
            }
            $invoice_id = $conn->insert_id;
            $stmt->close();

            // Insert each item
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

$item_stmt->bind_param("iisidd", $invoice_id, $svc_id, $desc, $qty, $unit_price, $item_total);
                // Handle nullable service_id properly
                if ($svc_id === null) {
                    $item_stmt->bind_param("iisdd d", $invoice_id, $svc_id, $desc, $qty, $unit_price, $item_total);
                    // Re-bind with null support
                    $null = null;
                    $item_stmt->bind_param("iisdd d", $invoice_id, $null, $desc, $qty, $unit_price, $item_total);
                }

                if (!$item_stmt->execute()) {
                    throw new Exception("Item insert failed: " . $item_stmt->error);
                }
            }
            $item_stmt->close();

            $conn->commit();
            $message = "Invoice <strong>{$inv_number}</strong> saved successfully! Total: ৳" . number_format($total, 2);

            // Pass the generated invoice number to JS for the receipt display
            $saved_inv_number = $inv_number;

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to save invoice: " . $e->getMessage();
        }
    }
}

// ========== ASSIGN SERVICE (existing handler) ==========
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_service'])) {
    $customer_id = intval($_POST['customer_id']);
    $service_id  = intval($_POST['service_id']);
    $assign_date = $_POST['assign_date'] ?? date('Y-m-d');

    if ($customer_id > 0 && $service_id > 0) {
        $stmt = $conn->prepare("INSERT INTO customer_services (customer_id, service_id, assign_date) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $customer_id, $service_id, $assign_date);
        if ($stmt->execute()) {
            $message = "Service assigned successfully!";
        } else {
            $error = "Error assigning service: " . $stmt->error;
        }
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
<title>POS Invoice - AR TECH SOLUTION</title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=Sora:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #f0f2f5;
    --surface: #ffffff;
    --surface2: #f8f9fb;
    --border: #e2e6ea;
    --accent: #00b894;
    --accent2: #0984e3;
    --danger: #d63031;
    --warn: #fdcb6e;
    --dark: #1e2a3a;
    --dark2: #2c3e50;
    --text: #2d3436;
    --muted: #636e72;
    --mono: 'IBM Plex Mono', monospace;
    --sans: 'Sora', sans-serif;
    --radius: 12px;
    --shadow: 0 4px 24px rgba(0,0,0,0.08);
}
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
body { font-family: var(--sans); background: var(--bg); color: var(--text); min-height: 100vh; }

/* NAVBAR */
.navbar {
    background: linear-gradient(135deg, #1e2a3a, #0f1722);
    color: #fff;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: fixed;
    top: 0; left: 0; width: 100%;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.brand { font-size: 20px; font-weight: 700; letter-spacing: 1px; }
.logout-btn {
    position: absolute; right: 20px;
    background: #e74c3c; color: #fff;
    padding: 6px 18px; border-radius: 30px;
    text-decoration: none; font-size: 13px; font-weight: 600;
    transition: background .2s;
}
.logout-btn:hover { background: #c0392b; }

/* SIDEBAR */
.side-nav {
    position: fixed; top: 60px; left: 0;
    width: 220px; height: calc(100% - 60px);
    background: #2c3e50;
    padding-top: 20px; z-index: 1000;
    overflow-y: auto; transition: transform .3s ease;
}
.side-nav.collapsed { transform: translateX(-100%); }
.side-nav a, .menu-toggle {
    color: #fff; text-decoration: none;
    padding: 12px 25px; display: block;
    font-weight: 600; font-size: 13px;
    border-left: 4px solid transparent; cursor: pointer;
    transition: background .2s, border-color .2s;
}
.side-nav a:hover, .menu-toggle:hover { background: #34495e; border-left-color: #1abc9c; }
.menu-group .submenu { display: none; flex-direction: column; background: #34495e; }
.menu-group.active .submenu { display: block; }
.submenu a { padding: 10px 40px; font-weight: 400; }
.toggle-arrow {
    position: fixed; top: 70px; left: 220px;
    background: #1abc9c; color: #fff;
    padding: 6px 10px; border-radius: 0 5px 5px 0;
    cursor: pointer; z-index: 1001; font-size: 18px;
    transition: left .3s ease;
}
.toggle-arrow.collapsed { left: 0; }

/* MAIN */
.container {
    margin-left: 240px;
    padding: 76px 28px 70px;
    transition: margin-left .3s ease;
}
.container.collapsed { margin-left: 20px; }

.page-title {
    font-size: 22px; font-weight: 700;
    color: var(--dark); margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
}
.page-title span { font-size: 26px; }

.alert {
    padding: 12px 18px; border-radius: 8px;
    margin-bottom: 18px; font-size: 14px; font-weight: 600;
    display: flex; align-items: center; gap: 8px;
}
.alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
.alert-error   { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

/* POS GRID */
.pos-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 20px;
    align-items: start;
}

/* CARD */
.card {
    background: var(--surface);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    overflow: hidden;
}
.card-header {
    background: var(--dark);
    color: #fff;
    padding: 14px 20px;
    font-size: 13px; font-weight: 700;
    letter-spacing: .5px;
    display: flex; align-items: center; gap: 8px;
}
.card-body { padding: 20px; }

/* CUSTOMER SECTION */
.form-row { display: flex; gap: 14px; flex-wrap: wrap; }
.form-group { flex: 1; min-width: 160px; margin-bottom: 14px; }
.form-group label {
    display: block; font-size: 11px; font-weight: 700;
    color: var(--muted); text-transform: uppercase;
    letter-spacing: .6px; margin-bottom: 6px;
}
.form-group select, .form-group input, .form-group textarea {
    width: 100%; padding: 9px 12px;
    border: 1.5px solid var(--border); border-radius: 8px;
    font-family: var(--sans); font-size: 14px;
    color: var(--text); background: var(--surface2);
    transition: border-color .2s;
}
.form-group select:focus, .form-group input:focus, .form-group textarea:focus {
    outline: none; border-color: var(--accent);
}
.form-group textarea { height: 64px; resize: vertical; }
#customer-info {
    background: linear-gradient(135deg, #00b89410, #0984e310);
    border: 1.5px dashed var(--accent);
    border-radius: 10px;
    padding: 12px 16px; margin-top: -4px;
    font-size: 13px; color: var(--dark2);
    display: none;
}
#customer-info.show { display: block; }
#customer-info strong { display: block; font-size: 15px; color: var(--accent); margin-bottom: 4px; }

/* ITEM BUILDER */
.item-builder {
    background: var(--surface2);
    border: 1.5px solid var(--border);
    border-radius: 10px; padding: 14px; margin-bottom: 16px;
}
.item-builder-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1.2fr 1fr auto;
    gap: 8px; align-items: end;
}
.item-builder .form-group { margin-bottom: 0; }
.add-item-btn {
    background: var(--accent); color: #fff;
    border: none; border-radius: 8px;
    padding: 9px 14px; font-size: 18px;
    cursor: pointer; font-weight: 700;
    transition: background .2s, transform .1s;
    align-self: end;
}
.add-item-btn:hover { background: #00a07a; transform: scale(1.05); }

/* ITEMS TABLE */
.items-table {
    width: 100%; border-collapse: collapse;
    font-size: 13px;
}
.items-table thead tr {
    background: var(--dark); color: #fff;
}
.items-table th {
    padding: 10px 12px; text-align: left;
    font-size: 11px; font-weight: 700;
    letter-spacing: .5px; text-transform: uppercase;
}
.items-table td {
    padding: 10px 12px; border-bottom: 1px solid var(--border);
    vertical-align: middle;
}
.items-table tbody tr:hover { background: #f0faf7; }
.items-table tbody tr:last-child td { border-bottom: none; }
.del-btn {
    background: var(--danger); color: #fff;
    border: none; border-radius: 6px;
    width: 26px; height: 26px;
    cursor: pointer; font-size: 13px;
    display: flex; align-items: center; justify-content: center;
    transition: background .2s;
}
.del-btn:hover { background: #b71c1c; }
.empty-row td {
    text-align: center; color: var(--muted);
    padding: 28px; font-size: 13px; font-style: italic;
}
.item-desc-cell { font-weight: 600; }
.item-price-cell { font-family: var(--mono); color: var(--accent2); }

/* RECEIPT CARD */
.receipt-card {
    position: sticky; top: 76px;
}

/* RECEIPT HEADER LOGO AREA */
.receipt-top {
    background: linear-gradient(135deg, var(--dark), #0f1722);
    color: #fff; padding: 22px 20px;
    text-align: center; border-radius: var(--radius) var(--radius) 0 0;
}
.receipt-logo {
    font-size: 18px; font-weight: 800;
    letter-spacing: 2px; margin-bottom: 4px;
}
.receipt-tagline { font-size: 10px; color: #aab; letter-spacing: 1px; opacity:.8; }
.receipt-inv-num {
    margin-top: 12px;
    font-family: var(--mono);
    font-size: 20px; font-weight: 700;
    color: var(--accent);
    letter-spacing: 2px;
}

/* RECEIPT BODY */
.receipt-body { padding: 18px; }
.r-section { margin-bottom: 14px; }
.r-label {
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .6px;
    color: var(--muted); margin-bottom: 4px;
}
.r-value { font-size: 14px; font-weight: 600; color: var(--text); }
.r-divider {
    border: none; border-top: 1px dashed var(--border);
    margin: 12px 0;
}
.r-line {
    display: flex; justify-content: space-between;
    align-items: center; font-size: 13px;
    padding: 4px 0; color: var(--text);
}
.r-line .r-mono { font-family: var(--mono); }
.r-subtotal-line {
    display: flex; justify-content: space-between;
    font-size: 13px; padding: 4px 0; color: var(--muted);
}
.r-total-box {
    background: linear-gradient(135deg, var(--accent), #00a07a);
    border-radius: 10px; padding: 14px 16px;
    display: flex; justify-content: space-between;
    align-items: center; margin-bottom: 14px;
}
.r-total-label { color: rgba(255,255,255,.85); font-size: 13px; font-weight: 700; }
.r-total-amt { font-family: var(--mono); font-size: 22px; font-weight: 700; color: #fff; }

/* PAYMENT SECTION */
.payment-details {
    background: #f0f9f4;
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 14px;
}
.payment-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    font-size: 13px;
}
.payment-line .label { color: var(--muted); font-weight: 600; }
.payment-line .amount { font-family: var(--mono); font-weight: 700; color: var(--dark2); }
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
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-family: var(--mono);
    font-size: 14px;
    margin-top: 6px;
}

/* STATUS BADGES */
.status-select {
    width: 100%; padding: 9px 12px;
    border: 1.5px solid var(--border); border-radius: 8px;
    font-family: var(--sans); font-size: 14px;
    background: var(--surface2); margin-bottom: 12px;
}

/* DISCOUNT ROW */
.discount-row {
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 10px;
}
.discount-row label { font-size: 13px; color: var(--muted); font-weight: 600; white-space: nowrap; }
.discount-row input {
    flex: 1; padding: 8px 10px;
    border: 1.5px solid var(--border); border-radius: 8px;
    font-family: var(--mono); font-size: 14px;
}

/* BUTTONS */
.btn-save {
    width: 100%; padding: 13px;
    background: linear-gradient(135deg, var(--accent), #00a07a);
    color: #fff; border: none; border-radius: 10px;
    font-family: var(--sans); font-size: 15px;
    font-weight: 700; cursor: pointer;
    letter-spacing: .5px; margin-bottom: 10px;
    transition: opacity .2s, transform .1s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-save:hover { opacity: .92; transform: translateY(-1px); }
.btn-print {
    width: 100%; padding: 11px;
    background: var(--accent2); color: #fff;
    border: none; border-radius: 10px;
    font-family: var(--sans); font-size: 14px;
    font-weight: 700; cursor: pointer;
    transition: opacity .2s; display: flex;
    align-items: center; justify-content: center; gap: 8px;
}
.btn-print:hover { opacity: .88; }
.btn-clear {
    width: 100%; padding: 9px;
    background: transparent; color: var(--danger);
    border: 1.5px solid var(--danger); border-radius: 10px;
    font-family: var(--sans); font-size: 13px;
    font-weight: 700; cursor: pointer; margin-top: 8px;
    transition: background .2s, color .2s;
}
.btn-clear:hover { background: var(--danger); color: #fff; }

/* FOOTER */
.footer {
    background: #1a1a1a; color: #aaa;
    text-align: center; padding: 12px;
    position: fixed; bottom: 0; left: 0;
    width: 100%; font-size: 13px; z-index: 999;
}

/* PRINT INVOICE STYLES - A4 PERFECT */
@media print {
    body * { visibility: hidden; }
    #print-invoice, #print-invoice * { visibility: visible; }
    #print-invoice {
        position: absolute; left: 0; top: 0;
        width: 100%; max-width: 210mm;
        margin: 0 auto; padding: 10mm;
        box-sizing: border-box; background: white;
    }
    @page { size: A4; margin: 1.5cm; }
    .pi-table td, .pi-table th { font-size: 12px; padding: 8px 6px; }
    .pi-header, .pi-parties, .pi-footer-note { page-break-inside: avoid; }
    .pi-table { page-break-inside: auto; }
    .pi-table tr { page-break-inside: avoid; page-break-after: auto; }
    .navbar, .side-nav, .toggle-arrow, .footer,
    .item-builder, .btn-save, .btn-print, .btn-clear,
    .add-item-btn, .del-btn, .alert, .card:not(.receipt-card),
    .status-select, .discount-row, #customer-section, .payment-details .paid-input {
        display: none !important;
    }
    .container { margin: 0 !important; padding: 0 !important; }
    .pos-grid { display: block !important; }
    .receipt-card { position: static !important; box-shadow: none !important; border: none !important; }
    .receipt-top { border-radius: 0 !important; }
    #print-invoice { display: block !important; }
}

#print-invoice { display: none; }

/* PRINT INVOICE LAYOUT */
#print-invoice {
    max-width: 700px; margin: 0 auto;
    padding: 30px; font-family: var(--sans);
}
.pi-header {
    display: flex; justify-content: space-between;
    align-items: flex-start; margin-bottom: 24px;
}
.pi-logo { font-size: 22px; font-weight: 800; color: var(--dark); }
.pi-logo small { display: block; font-size: 12px; font-weight: 400; color: var(--muted); margin-top: 2px; }
.pi-inv { text-align: right; }
.pi-inv .inv-num { font-family: var(--mono); font-size: 18px; font-weight: 700; color: var(--accent); }
.pi-inv .inv-date { font-size: 12px; color: var(--muted); margin-top: 4px; }
.pi-parties { display: flex; justify-content: space-between; margin-bottom: 24px; gap: 20px; }
.pi-party { flex: 1; }
.pi-party h4 { font-size: 10px; text-transform: uppercase; letter-spacing:.7px; color:var(--muted); margin-bottom: 6px; }
.pi-party p { font-size: 13px; line-height: 1.6; }
.pi-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.pi-table th { background: var(--dark); color: #fff; padding: 9px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; }
.pi-table td { padding: 9px 12px; border-bottom: 1px solid var(--border); font-size: 13px; }
.pi-table tfoot td { font-weight: 700; border-top: 2px solid var(--dark); }
.pi-total-row { background: var(--accent); color: #fff; }
.pi-total-row td { font-size: 15px; }
.pi-footer-note { font-size: 11px; color: var(--muted); text-align: center; margin-top: 30px; border-top: 1px dashed var(--border); padding-top: 12px; }

@media (max-width: 900px) {
    .pos-grid { grid-template-columns: 1fr; }
    .receipt-card { position: static; }
}
@media (max-width: 768px) {
    .container { margin-left: 0; padding: 70px 12px 60px; }
    .side-nav { transform: translateX(-100%); }
    .toggle-arrow { left: 0; }
    .item-builder-row { grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="brand">🔌 AR TECH SOLUTION</div>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<!-- SIDEBAR -->
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
            <a href="invoice_list.php">Invoice List</a>
        </div>
    </div>
    <a href="delete.php">🗑️ Delete</a>
    <a href="report.php">📄 Report</a>
    <div class="menu-group active">
        <div class="menu-toggle">💵 Payment ▾</div>
        <div class="submenu">
            <a href="invoice_pos.php">🧾 POS Invoice</a>
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
    <div class="page-title"><span>🧾</span> Point of Sale — Invoice Generator</div>

    <?php if ($message): ?>
        <div class="alert alert-success">✅ <?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
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
                                <label>Select Customer *</label>
                                <select name="customer_id" id="customer_select" required>
                                    <option value="">— Choose Customer —</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?= $c['id'] ?>"
                                            data-name="<?= htmlspecialchars($c['name']) ?>"
                                            data-email="<?= htmlspecialchars($c['email'] ?? '') ?>"
                                            data-phone="<?= htmlspecialchars($c['phone'] ?? '') ?>">
                                            <?= htmlspecialchars($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Invoice Date</label>
                                <input type="date" name="invoice_date" id="invoice_date" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="form-group">
                                <label>Due Date</label>
                                <input type="date" name="due_date" id="due_date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                            </div>
                        </div>
                        <div id="customer-info">
                            <strong id="ci-name">—</strong>
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
                                            <option value="<?= $s['id'] ?>" data-price="<?= $s['fee'] ?>" data-name="<?= htmlspecialchars($s['service_name']) ?>">
                                                <?= htmlspecialchars($s['service_name']) ?> (৳<?= number_format($s['fee'],2) ?>)
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
                        <div style="overflow-x:auto;">
                            <table class="items-table" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Description</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <tr class="empty-row" id="emptyRow">
                                        <td colspan="6">📦 No items added yet. Use the form above to add items.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
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
                    <div class="receipt-inv-num" id="r-inv-num">INV-<?= date('Ymd') ?>-???</div>
                </div>
                <div class="receipt-body">
                    <div class="r-section">
                        <div class="r-label">Bill To</div>
                        <div class="r-value" id="r-customer">—</div>
                        <div style="font-size:12px; color:var(--muted); margin-top:2px;" id="r-customer-contact">Select a customer</div>
                    </div>
                    <div class="form-row" style="gap:10px;">
                        <div>
                            <div class="r-label">Invoice Date</div>
                            <div class="r-value" id="r-idate"><?= date('d M Y') ?></div>
                        </div>
                        <div>
                            <div class="r-label">Due Date</div>
                            <div class="r-value" id="r-ddate"><?= date('d M Y', strtotime('+7 days')) ?></div>
                        </div>
                    </div>
                    <hr class="r-divider">
                    <div class="r-label" style="margin-bottom:8px;">Items</div>
                    <div id="r-items">
                        <div style="font-size:13px; color:var(--muted); font-style:italic;">No items yet</div>
                    </div>
                    <hr class="r-divider">
                    <div class="r-subtotal-line"><span>Subtotal</span><span class="r-mono" id="r-subtotal">৳ 0.00</span></div>
                    <div class="discount-row">
                        <label>Discount (৳)</label>
                        <input type="number" name="discount" id="discount_input" min="0" step="0.01" value="0" placeholder="0.00" oninput="updateReceipt()">
                    </div>

                    <!-- Amount Paid Section -->
                    <div class="payment-details">
                        <div class="payment-line">
                            <span class="label">Total Amount:</span>
                            <span class="amount" id="r_total_amount">৳ 0.00</span>
                        </div>
                        <div class="payment-line">
                            <span class="label">Amount Paid:</span>
                            <input type="number" name="paid_amount" id="paid_amount_input" min="0" step="0.01" value="0" class="paid-input" oninput="updatePaidAmount()">
                        </div>
                        <div class="payment-line balance-line">
                            <span class="label">Balance Due:</span>
                            <span class="amount" id="r_balance_due">৳ 0.00</span>
                        </div>
                    </div>

                    <div class="r-total-box">
                        <span class="r-total-label">TOTAL DUE</span>
                        <span class="r-total-amt" id="r-total">৳ 0.00</span>
                    </div>
                    <button type="submit" name="save_invoice" class="btn-save" onclick="prepareSubmit()">
                        💾 Save Invoice
                    </button>
                    <button type="button" class="btn-print" onclick="printInvoice()">
                        🖨️ Print Invoice
                    </button>
                    <button type="button" class="btn-clear" onclick="clearAll()">✕ Clear / New Invoice</button>
                </div>
            </div>
        </div>
    </form>

    <!-- PRINT-ONLY INVOICE LAYOUT (A4 OPTIMIZED) -->
    <div id="print-invoice">
        <div class="pi-header">
            <div>
                <div class="pi-logo">AR TECH SOLUTION<small>Freelancing & Training Center</small></div>
            </div>
            <div class="pi-inv">
                <div class="inv-num" id="pi-invnum">—</div>
                <div class="inv-date">Date: <span id="pi-date">—</span></div>
                <div class="inv-date">Due: <span id="pi-due">—</span></div>
            </div>
        </div>
        <div class="pi-parties">
            <div class="pi-party">
                <h4>Billed To</h4>
                <p id="pi-customer">—</p>
                <p id="pi-contact" style="font-size:12px; color:var(--muted);"></p>
            </div>
            <div class="pi-party" style="text-align:right;">
                <h4>From</h4>
                <p><strong>AR TECH SOLUTION</strong></p>
                <p style="font-size:12px; color:var(--muted);">Freelancing & Training Center</p>
            </div>
        </div>
        <table class="pi-table">
            <thead>
                <tr><th>#</th><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr>
            </thead>
            <tbody id="pi-tbody"></tbody>
            <tfoot>
                <tr><td colspan="4">Subtotal</td><td id="pi-subtotal">৳ 0.00</td></tr>
                <tr><td colspan="4">Discount</td><td id="pi-discount">৳ 0.00</td></tr>
                <tr><td colspan="4"><strong>Total</strong></td><td id="pi-total-display">৳ 0.00</td></tr>
                <tr><td colspan="4">Amount Paid</td><td id="pi-paid">৳ 0.00</td></tr>
                <tr class="pi-total-row"><td colspan="4"><strong>Balance Due</strong></td><td id="pi-balance">৳ 0.00</td></tr>
            </tfoot>
        </table>
        <div class="pi-footer-note">Thank you for your business! · AR TECH SOLUTION · <?= date('Y') ?></div>
    </div>
</div>

<div class="footer">
    &copy; <?= date("Y") ?> AR TECH SOLUTION | Freelancing Student Management System
</div>

<!-- ========== JAVASCRIPT ========== -->
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

// ── After a successful save, update the receipt invoice number ──
<?php if (!empty($saved_inv_number)): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('r-inv-num').textContent = '<?= $saved_inv_number ?>';
    document.getElementById('pi-invnum').textContent  = '<?= $saved_inv_number ?>';
});
<?php endif; ?>

// Customer selection
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
        document.getElementById('pi-customer').textContent = name;
        document.getElementById('pi-contact').textContent = (email ? email + '   ' : '') + (phone || '');
    } else {
        document.getElementById('customer-info').classList.remove('show');
        document.getElementById('r-customer').textContent = '—';
        document.getElementById('r-customer-contact').textContent = 'Select a customer';
    }
});

// Date sync
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

// Items array
let items = [];
let itemCounter = 0;

function autoFillPrice() {
    const sel = document.getElementById('svc_select');
    const opt = sel.options[sel.selectedIndex];
    if (opt.dataset.price) {
        document.getElementById('item_price').value = parseFloat(opt.dataset.price).toFixed(2);
        document.getElementById('item_desc').value = opt.dataset.name || '';
    }
}

function fmt(n) { return '৳ ' + parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); }

function addItem() {
    const svcSel = document.getElementById('svc_select');
    const desc   = document.getElementById('item_desc').value.trim() || svcSel.options[svcSel.selectedIndex].text.split('(')[0].trim();
    const price  = parseFloat(document.getElementById('item_price').value) || 0;
    const qty    = parseInt(document.getElementById('item_qty').value) || 1;
    const svcId  = svcSel.value || null;

    if (!desc || price <= 0) { alert('Please enter a description and price.'); return; }

    itemCounter++;
    const total = price * qty;
    items.push({ id: itemCounter, service_id: svcId, description: desc, qty, unit_price: price, total });
    renderItems();

    // Reset form
    document.getElementById('svc_select').value = '';
    document.getElementById('item_desc').value = '';
    document.getElementById('item_price').value = '';
    document.getElementById('item_qty').value = '1';
}

function removeItem(id) {
    items = items.filter(i => i.id !== id);
    renderItems();
}

function renderItems() {
    const tbody = document.getElementById('itemsBody');
    if (items.length === 0) {
        tbody.innerHTML = `<tr class="empty-row" id="emptyRow"><td colspan="6">📦 No items added yet. Use the form above to add items.</td></tr>`;
        updateReceipt();
        return;
    }
    let html = '';
    items.forEach((item, idx) => {
        html += `<tr>
            <td>${idx+1}</td>
            <td class="item-desc-cell">${escapeHtml(item.description)}</td>
            <td>${item.qty}</td>
            <td class="item-price-cell">${fmt(item.unit_price)}</td>
            <td class="item-price-cell"><strong>${fmt(item.total)}</strong></td>
            <td><button type="button" class="del-btn" onclick="removeItem(${item.id})">✕</button></td>
        </tr>`;
    });
    tbody.innerHTML = html;
    updateReceipt();
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function getTotalDue() {
    const subtotal = items.reduce((s, i) => s + i.total, 0);
    const discount = parseFloat(document.getElementById('discount_input').value) || 0;
    return Math.max(0, subtotal - discount);
}

function updatePaidAmount() {
    const totalDue = getTotalDue();
    let paid = parseFloat(document.getElementById('paid_amount_input').value) || 0;
    if (paid > totalDue) {
        paid = totalDue;
        document.getElementById('paid_amount_input').value = paid.toFixed(2);
    }
    const balance = totalDue - paid;
    document.getElementById('r_balance_due').textContent = fmt(balance);
    document.getElementById('r-total').textContent = fmt(balance);

    // Auto-update status based on paid amount
    const statusSelect = document.getElementById('status_select');
    if (balance <= 0 && totalDue > 0) {
        statusSelect.value = 'paid';
    } else if (paid > 0 && balance > 0) {
        statusSelect.value = 'partial';
    } else if (paid === 0 && totalDue > 0) {
        statusSelect.value = 'unpaid';
    }

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
    if (paid > totalDue) {
        paid = totalDue;
        document.getElementById('paid_amount_input').value = paid.toFixed(2);
    }
    const balance = totalDue - paid;

    document.getElementById('r-subtotal').textContent = fmt(subtotal);
    document.getElementById('r_total_amount').textContent = fmt(totalDue);
    document.getElementById('r_balance_due').textContent = fmt(balance);
    document.getElementById('r-total').textContent = fmt(balance);

    // Update receipt items list
    let rHtml = '';
    if (items.length === 0) {
        rHtml = '<div style="font-size:13px; color:var(--muted); font-style:italic;">No items yet</div>';
    } else {
        items.forEach(i => {
            rHtml += `<div class="r-line"><span>${escapeHtml(i.description)} ×${i.qty}</span><span class="r-mono">${fmt(i.total)}</span></div>`;
        });
    }
    document.getElementById('r-items').innerHTML = rHtml;

    // Update print invoice table
    let piHtml = '';
    items.forEach((item, idx) => {
        piHtml += `<tr>
            <td>${idx+1}</td>
            <td>${escapeHtml(item.description)}</td>
            <td>${item.qty}</td>
            <td>${fmt(item.unit_price)}</td>
            <td>${fmt(item.total)}</td>
        </tr>`;
    });
    document.getElementById('pi-tbody').innerHTML = piHtml;
    document.getElementById('pi-subtotal').textContent = fmt(subtotal);
    document.getElementById('pi-discount').textContent = fmt(discount);
    document.getElementById('pi-total-display').textContent = fmt(totalDue);
    document.getElementById('pi-paid').textContent = fmt(paid);
    document.getElementById('pi-balance').textContent = fmt(balance);

    // Auto-update status
    const statusSelect = document.getElementById('status_select');
    if (balance <= 0 && totalDue > 0) {
        statusSelect.value = 'paid';
    } else if (paid > 0 && balance > 0) {
        statusSelect.value = 'partial';
    } else if (paid === 0 && totalDue > 0) {
        statusSelect.value = 'unpaid';
    }
}

function prepareSubmit() {
    document.getElementById('items_json').value = JSON.stringify(items);
}

function clearAll() {
    if (!confirm('Clear all items and start a new invoice?')) return;
    items = []; itemCounter = 0;
    renderItems();
    document.getElementById('customer_select').value = '';
    document.getElementById('customer-info').classList.remove('show');
    document.getElementById('r-customer').textContent = '—';
    document.getElementById('r-customer-contact').textContent = 'Select a customer';
    document.getElementById('discount_input').value = 0;
    document.getElementById('paid_amount_input').value = 0;
    document.getElementById('status_select').value = 'unpaid';
    document.getElementById('invoice_date').value = '<?= date('Y-m-d') ?>';
    document.getElementById('due_date').value = '<?= date('Y-m-d', strtotime('+7 days')) ?>';
    // Generate fresh invoice number
    document.getElementById('r-inv-num').textContent = 'INV-<?= date('Ymd') ?>-' + String(Math.floor(Math.random()*900)+100);
    updateReceipt();
}

function printInvoice() {
    const custSel = document.getElementById('customer_select');
    const custOpt = custSel.options[custSel.selectedIndex];
    document.getElementById('pi-invnum').textContent = document.getElementById('r-inv-num').textContent;
    const idate = document.getElementById('invoice_date').value;
    const ddate = document.getElementById('due_date').value;
    document.getElementById('pi-date').textContent = new Date(idate).toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('pi-due').textContent  = new Date(ddate).toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'});
    if (custOpt.dataset.name) {
        document.getElementById('pi-customer').textContent = custOpt.dataset.name;
        document.getElementById('pi-contact').textContent = (custOpt.dataset.email || '') + '   ' + (custOpt.dataset.phone || '');
    }
    updateReceipt();
    window.print();
}

// Generate random invoice number on page load
document.getElementById('r-inv-num').textContent = 'INV-<?= date('Ymd') ?>-' + String(Math.floor(Math.random()*900)+100);

// Event listener for discount
document.getElementById('discount_input').addEventListener('input', function() {
    updatePaidAmount();
    updateReceipt();
});

// Initialize
updateReceipt();
</script>
</body>
</html>