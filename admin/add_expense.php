<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$message = "";
$error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_expense'])) {
    $category = trim($_POST['category']);
    $amount = floatval($_POST['amount']);
    $expense_date = $_POST['expense_date'];
    $description = trim($_POST['description']);
    $created_by = $_SESSION['email'] ?? 'Admin';
    $receipt_file = null;

    // Validate
    if (empty($category)) $error = "Category is required.";
    elseif ($amount <= 0) $error = "Amount must be greater than zero.";
    elseif (empty($expense_date)) $error = "Expense date is required.";
    else {
        // Handle receipt upload (optional)
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
            $target_dir = "uploads/expenses/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $file_ext = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
            if (in_array($file_ext, $allowed)) {
                $filename = time() . "_" . preg_replace('/[^a-zA-Z0-9]/', '_', $category) . "." . $file_ext;
                $target_file = $target_dir . $filename;
                if (move_uploaded_file($_FILES['receipt']['tmp_name'], $target_file)) {
                    $receipt_file = $filename;
                } else {
                    $error = "Failed to upload receipt.";
                }
            } else {
                $error = "Invalid file type. Allowed: JPG, PNG, GIF, PDF.";
            }
        }

        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO expenses (category, amount, expense_date, description, receipt_file, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sdssss", $category, $amount, $expense_date, $description, $receipt_file, $created_by);
            if ($stmt->execute()) {
                $message = "Expense added successfully!";
                // Clear form (optional)
                $category = $amount = $expense_date = $description = '';
            } else {
                $error = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch categories for dropdown (from existing expenses or predefined list)
$categories = $conn->query("SELECT DISTINCT category FROM expenses ORDER BY category")->fetch_all(MYSQLI_ASSOC);
$predefined_categories = ['Rent', 'Utilities', 'Salary', 'Marketing', 'Office Supplies', 'Travel', 'Maintenance', 'Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Expense — AR TECH SOLUTION</title>
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
    max-width: 600px;
    width: 100%;
}
.form-card h2 {
    font-family: var(--mono);
    font-size: 22px;
    color: var(--accent);
    text-align: center;
    margin-bottom: 24px;
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
    color: #ffffff;
    margin-bottom: 6px;
}
.form-group input, .form-group select, .form-group textarea {
    width: 100%;
    padding: 10px 12px;
    background: rgba(255,255,255,0.08);
    border: 1px solid var(--glass-border);
    border-radius: 10px;
    color: #f0f0f0;
    font-family: var(--sans);
    font-size: 14px;
    outline: none;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: var(--accent);
}
/* Improve dropdown option readability in all modes */
.form-group select option {
    background-color: #1e2a3a;
    color: #ffffff;
}
body.dark-mode .form-group select option {
    background-color: #0a0e1a;
    color: #e0e0e0;
}
.submit-btn {
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    color: #000;
    font-weight: 700;
    padding: 12px;
    border: none;
    border-radius: 40px;
    cursor: pointer;
    width: 100%;
}
.alert {
    padding: 12px;
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
    .form-card { padding: 20px; }
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

<main class="main" id="mainContent">
    <div class="form-card">
        <h2>💰 Add Expense</h2>
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Category</label>
                <select name="category" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach ($predefined_categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                    <?php foreach ($categories as $cat): ?>
                        <?php if (!in_array($cat['category'], $predefined_categories)): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>"><?php echo htmlspecialchars($cat['category']); ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <option value="Other">Other (custom)</option>
                </select>
                <input type="text" name="custom_category" id="custom_category" placeholder="Enter custom category" style="display:none; margin-top:8px;">
            </div>
            <div class="form-group">
                <label>Amount (৳)</label>
                <input type="number" step="0.01" name="amount" placeholder="0.00" required>
            </div>
            <div class="form-group">
                <label>Expense Date</label>
                <input type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label>Description (Optional)</label>
                <textarea name="description" rows="3" placeholder="Enter description..."></textarea>
            </div>
            <div class="form-group">
                <label>Receipt (Optional, JPG/PNG/PDF)</label>
                <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf">
            </div>
            <button type="submit" name="add_expense" class="submit-btn">➕ Add Expense</button>
        </form>
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

// Show custom category input when "Other" is selected
const catSelect = document.querySelector('select[name="category"]');
const customCatInput = document.getElementById('custom_category');
catSelect.addEventListener('change', function() {
    if (this.value === 'Other') {
        customCatInput.style.display = 'block';
        customCatInput.required = true;
        customCatInput.name = 'category';
        this.name = 'old_category';
    } else {
        customCatInput.style.display = 'none';
        customCatInput.required = false;
        customCatInput.name = 'custom_category';
        this.name = 'category';
    }
});
</script>
</body>
</html>