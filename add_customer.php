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

// Create customers table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle Add / Update / Delete
$message = "";
$error = "";

// Add customer
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_customer'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    if (empty($name)) $error = "Customer name is required!";
    else {
        $stmt = $conn->prepare("INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $phone, $address);
        if ($stmt->execute()) $message = "Customer added successfully!";
        else $error = "Error: " . $stmt->error;
        $stmt->close();
    }
}

// Update customer
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_customer'])) {
    $id = intval($_POST['customer_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    if (empty($name)) $error = "Customer name is required!";
    else {
        $stmt = $conn->prepare("UPDATE customers SET name=?, email=?, phone=?, address=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $email, $phone, $address, $id);
        if ($stmt->execute()) $message = "Customer updated successfully!";
        else $error = "Error: " . $stmt->error;
        $stmt->close();
    }
}

// Delete customer
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM customers WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) $message = "Customer deleted successfully!";
    else $error = "Error deleting customer.";
    $stmt->close();
}

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT * FROM customers";
if (!empty($search)) {
    $searchTerm = "%$search%";
    $sql .= " WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? OR address LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conn->query("SELECT * FROM customers ORDER BY id DESC");
    $customers = $result->fetch_all(MYSQLI_ASSOC);
}

// For edit mode
$edit_customer = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    foreach ($customers as $cust) {
        if ($cust['id'] == $edit_id) { $edit_customer = $cust; break; }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer - AR TECH SOLUTION</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }

        /* ========== NAVBAR (centered brand + logout right) ========== */
        .navbar {
            background: linear-gradient(135deg, #1e2a3a, #0f1722);
            color: white;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            padding: 0 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .brand {
            font-size: 22px;
            font-weight: bold;
        }
        .logout-btn {
            position: absolute;
            right: 20px;
            background: #e74c3c;
            color: white;
            padding: 6px 18px;
            border-radius: 30px;
            text-decoration: none;
            transition: 0.2s;
        }
        .logout-btn:hover { background: #c0392b; transform: scale(1.02); }
        @media (max-width: 700px) { .brand { font-size: 16px; } }

        /* ========== SIDEBAR ========== */
        .side-nav {
            position: fixed;
            top: 60px;
            left: 0;
            width: 220px;
            height: calc(100% - 60px);
            background-color: #2c3e50;
            padding-top: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }
        .side-nav.collapsed { transform: translateX(-100%); }
        .side-nav a, .menu-toggle {
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            width: 100%;
            font-weight: bold;
            transition: background 0.3s ease;
            border-left: 4px solid transparent;
            cursor: pointer;
        }
        .side-nav a:hover, .menu-toggle:hover {
            background-color: #34495e;
            border-left: 4px solid #1abc9c;
        }
        .menu-group { width: 100%; }
        .submenu {
            display: none;
            flex-direction: column;
            background-color: #34495e;
        }
        .submenu a {
            color: white;
            padding: 10px 40px;
            font-weight: normal;
        }
        .menu-group.active .submenu { display: flex; }

        .toggle-arrow {
            position: fixed;
            top: 70px;
            left: 220px;
            background-color: #1abc9c;
            color: white;
            padding: 6px 10px;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            z-index: 1001;
            font-size: 18px;
            transition: left 0.3s ease;
        }
        .toggle-arrow.collapsed { left: 0; }

        /* ========== MAIN CONTAINER ========== */
        .container {
            margin-left: 240px;
            padding: 80px 30px 60px;
            transition: margin-left 0.3s ease;
        }
        .container.collapsed { margin-left: 20px; }

        h2 { text-align: center; color: #2c3e50; margin-bottom: 30px; }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .customer-wrapper {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        .form-card, .list-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 25px;
            flex: 1;
            min-width: 280px;
        }
        .form-card { flex: 1.2; }
        .list-card { flex: 2; }

        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 6px;
            color: #2c3e50;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-group textarea { height: 80px; resize: vertical; }
        .btn {
            background: linear-gradient(135deg, #1abc9c, #16a085);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
            width: 100%;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .btn-secondary {
            background: #95a5a6;
            margin-top: 10px;
            text-align: center;
            display: inline-block;
            text-decoration: none;
        }
        .btn-small {
            background: #3498db;
            padding: 5px 12px;
            font-size: 12px;
            width: auto;
            margin-right: 5px;
        }
        .btn-danger {
            background: #e74c3c;
        }
        .search-bar {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .search-bar input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 30px;
        }
        .search-bar button {
            background: #1abc9c;
            border: none;
            padding: 0 20px;
            border-radius: 30px;
            color: white;
            cursor: pointer;
        }
        .customer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .customer-table th, .customer-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .customer-table th {
            background: #f8f9fa;
            color: #2c3e50;
            border-top: 2px solid #1abc9c;
        }
        .customer-table tr:hover { background: #f5f5f5; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }

        .footer {
            background: #1a1a1a;
            color: white;
            text-align: center;
            padding: 12px;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            font-size: 14px;
            z-index: 999;
        }
        @media (max-width: 768px) {
            .container { margin-left: 0; padding: 70px 15px 50px; }
            .side-nav { transform: translateX(-100%); }
            .toggle-arrow { left: 0; }
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="brand">AR TECH SOLUTION</div>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

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
    <div class="menu-group active">
        <div class="menu-toggle">👥 Customers ▾</div>
        <div class="submenu">
            <a href="add_customer.php">Add Customer</a>
            <a href="customer_list.php">Customer List</a>
        </div>
    </div>
    <a href="delete.php">🗑️ Delete</a>
    <a href="report.php">📄 Report</a>
    <div class="menu-group">
        <div class="menu-toggle">💵 Payment ▾</div>
        <div class="submenu">
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
</div>

<div class="toggle-arrow" id="toggleBtn">◀</div>

<div class="container" id="mainContent">
    <h2>👥 Customer Management</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="customer-wrapper">
        <!-- Add / Edit Customer Form -->
        <div class="form-card">
            <h3 style="margin-top:0; border-left:4px solid #1abc9c; padding-left:12px;">
                <?= $edit_customer ? '✏️ Edit Customer' : '➕ Add New Customer' ?>
            </h3>
            <form method="POST">
                <?php if ($edit_customer): ?>
                    <input type="hidden" name="customer_id" value="<?= $edit_customer['id'] ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" value="<?= $edit_customer ? htmlspecialchars($edit_customer['name']) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= $edit_customer ? htmlspecialchars($edit_customer['email']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= $edit_customer ? htmlspecialchars($edit_customer['phone']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address"><?= $edit_customer ? htmlspecialchars($edit_customer['address']) : '' ?></textarea>
                </div>
                <button type="submit" name="<?= $edit_customer ? 'update_customer' : 'add_customer' ?>" class="btn">
                    <?= $edit_customer ? 'Update Customer' : 'Add Customer' ?>
                </button>
                <?php if ($edit_customer): ?>
                    <a href="add_customer.php" class="btn btn-secondary" style="display:block; text-align:center; margin-top:10px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Customer List + Search -->
        <div class="list-card">
            <div class="search-bar">
                <form method="GET" style="display:flex; width:100%; gap:10px;">
                    <input type="text" name="search" placeholder="Search by name, email, phone, address..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">🔍</button>
                    <?php if ($search): ?>
                        <a href="add_customer.php" style="background:#e74c3c; color:white; padding:0 15px; border-radius:30px; text-decoration:none; line-height:40px;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <h3 style="margin-top:0; border-left:4px solid #1abc9c; padding-left:12px;">
                📋 Customers List (<?= count($customers) ?>)
            </h3>
            <?php if (empty($customers)): ?>
                <p style="text-align:center; color:#666; padding:20px;">No customers found.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="customer-table">
                        <thead>
                            <tr><th>Name</th><th>Email</th><th>Phone</th><th>Address</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $cust): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($cust['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($cust['email']) ?></td>
                                    <td><?= htmlspecialchars($cust['phone']) ?></td>
                                    <td><?= htmlspecialchars(substr($cust['address'],0,50)) ?>...</td>
                                    <td class="actions">
                                        <a href="?edit=<?= $cust['id'] ?>" class="btn btn-small">Edit</a>
                                        <a href="?delete=<?= $cust['id'] ?>" class="btn btn-small btn-danger" onclick="return confirm('Delete this customer?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="footer">
    &copy; <?= date("Y") ?> Freelancing Student Management System | All Rights Reserved
</div>

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
    // Dropdown menus
    document.querySelectorAll('.menu-toggle').forEach(t => {
        t.addEventListener('click', () => t.parentElement.classList.toggle('active'));
    });
</script>
</body>