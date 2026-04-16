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

// Ensure services table exists
$conn->query("CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    duration VARCHAR(50),
    fee DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Insert default services if table empty
$check = $conn->query("SELECT COUNT(*) as cnt FROM services");
$row = $check->fetch_assoc();
if ($row['cnt'] == 0) {
    $defaults = [
        ['Graphic Design', 'Logo design, banner creation, social media graphics', 'Graphic Design', '2 months', 8000],
        ['Video Editing', 'Professional video editing and motion graphics', 'Video Editing', '3 months', 12000],
        ['Social Media Marketing', 'Social media strategy and content management', 'Social Media Marketing', '2 months', 10000],
        ['Digital Marketing', 'SEO, SEM, and digital advertising', 'Digital Marketing', '4 months', 15000],
        ['Microsoft Office', 'Word, Excel, PowerPoint training', 'Office Application', '1 month', 5000]
    ];
    $stmt = $conn->prepare("INSERT INTO services (service_name, description, category, duration, fee) VALUES (?, ?, ?, ?, ?)");
    foreach ($defaults as $svc) {
        $stmt->bind_param("ssssd", $svc[0], $svc[1], $svc[2], $svc[3], $svc[4]);
        $stmt->execute();
    }
    $stmt->close();
}

// Handle Add / Update / Delete
$message = "";
$error = "";

// Add
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_service'])) {
    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $duration = trim($_POST['duration']);
    $fee = floatval($_POST['fee']);
    
    if (empty($service_name)) $error = "Service name is required!";
    elseif (empty($category)) $error = "Category is required!";
    else {
        $stmt = $conn->prepare("INSERT INTO services (service_name, description, category, duration, fee) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssd", $service_name, $description, $category, $duration, $fee);
        if ($stmt->execute()) $message = "Service added successfully!";
        else $error = "Error: " . $stmt->error;
        $stmt->close();
    }
}

// Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_service'])) {
    $id = intval($_POST['service_id']);
    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $duration = trim($_POST['duration']);
    $fee = floatval($_POST['fee']);
    
    if (empty($service_name)) $error = "Service name is required!";
    else {
        $stmt = $conn->prepare("UPDATE services SET service_name=?, description=?, category=?, duration=?, fee=? WHERE id=?");
        $stmt->bind_param("ssssdi", $service_name, $description, $category, $duration, $fee, $id);
        if ($stmt->execute()) $message = "Service updated successfully!";
        else $error = "Error: " . $stmt->error;
        $stmt->close();
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM services WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) $message = "Service deleted successfully!";
    else $error = "Error deleting service.";
    $stmt->close();
}

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT * FROM services";
if (!empty($search)) {
    $searchTerm = "%$search%";
    $sql .= " WHERE service_name LIKE ? OR category LIKE ? OR description LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conn->query("SELECT * FROM services ORDER BY category, service_name");
    $services = $result->fetch_all(MYSQLI_ASSOC);
}

// Get distinct categories for dropdown
$catResult = $conn->query("SELECT DISTINCT category FROM services ORDER BY category");
$existing_categories = [];
while ($row = $catResult->fetch_assoc()) $existing_categories[] = $row['category'];

// For edit mode: fetch the service to edit
$edit_service = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    foreach ($services as $svc) {
        if ($svc['id'] == $edit_id) { $edit_service = $svc; break; }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Services — AR TECH SOLUTION</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
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

body {
    font-family: var(--sans);
    color: var(--text);
    min-height: 100vh;
    background: url('uploads/banner.jpg') no-repeat center center fixed;
    background-size: cover;
    overflow-x: hidden;
}

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

/* SECTION TITLE */
.section-title {
    font-family: var(--mono); font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 2px; color: var(--accent);
    margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
}
.section-title::after { content: ''; flex: 1; height: 1px; background: var(--glass-border); }

/* TWO COLUMN WRAPPER */
.services-wrapper {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
}
.form-card, .list-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 24px;
    flex: 1;
    min-width: 280px;
}
.form-card { flex: 1.2; }
.list-card { flex: 2; }
.form-card h3, .list-card h3 {
    font-family: var(--mono);
    font-size: 16px;
    color: var(--accent);
    margin-bottom: 20px;
    border-left: 3px solid var(--accent);
    padding-left: 12px;
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
.form-group input, .form-group textarea, .form-group select {
    width: 100%;
    padding: 10px 12px;
    background: rgba(255,255,255,0.08);
    border: 1px solid var(--glass-border);
    border-radius: 10px;
    color: var(--text);
    font-family: var(--sans);
    font-size: 14px;
    outline: none;
}
.form-group input:focus, .form-group textarea:focus, .form-group select:focus {
    border-color: var(--accent);
    background: rgba(255,255,255,0.12);
}
.form-group textarea {
    resize: vertical;
    min-height: 80px;
}
.btn {
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    color: #000;
    font-weight: 700;
    padding: 10px;
    border: none;
    border-radius: 40px;
    font-size: 14px;
    cursor: pointer;
    transition: opacity .2s;
    width: 100%;
}
.btn:hover { opacity: .85; }
.btn-secondary {
    background: linear-gradient(135deg, #95a5a6, #7f8c8d);
    color: white;
    display: block;
    text-align: center;
    margin-top: 10px;
    text-decoration: none;
}
.btn-small {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
    text-decoration: none;
    margin-right: 5px;
}
.btn-danger {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
}
.search-bar {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}
.search-bar input {
    flex: 1;
    padding: 10px 16px;
    background: rgba(255,255,255,0.08);
    border: 1px solid var(--glass-border);
    border-radius: 30px;
    color: var(--text);
}
.search-bar button, .search-bar a {
    padding: 0 20px;
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    border: none;
    border-radius: 30px;
    color: #000;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}
.services-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.services-table th, .services-table td {
    padding: 12px 8px;
    text-align: left;
    border-bottom: 1px solid var(--glass-border);
}
.services-table th {
    background: rgba(0,0,0,0.3);
    color: var(--accent);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 11px;
}
.services-table tr:hover td {
    background: rgba(255,255,255,0.03);
}
.category-badge, .fee-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.category-badge {
    background: rgba(52,152,219,0.2);
    color: #3498db;
}
.fee-badge {
    background: rgba(22,160,133,0.2);
    color: #16a085;
}
.actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.alert {
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: center;
    font-size: 14px;
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
.no-data {
    text-align: center;
    padding: 40px;
    color: var(--muted);
}

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

/* RESPONSIVE */
@media (max-width: 700px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.mobile-open { transform: translateX(0); }
    .sidebar-toggle-pill { display: none; }
    .hamburger { display: block; }
    .main { margin-left: 0 !important; padding-left: 16px; padding-right: 16px; }
    .services-wrapper { flex-direction: column; }
    .search-bar { flex-direction: column; }
    .search-bar button, .search-bar a { width: 100%; text-align: center; justify-content: center; }
    .actions { flex-direction: column; }
    .btn-small { text-align: center; }
}
</style>
</head>
<body>

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
<?php
include 'navigation.php';
?>

<div class="sidebar-toggle-pill" id="sidebarToggle">◀</div>

<!-- MAIN CONTENT -->
<main class="main" id="mainContent">
    <div class="section-title">🛠️ Manage Services</div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="services-wrapper">
        <!-- Add / Edit Form -->
        <div class="form-card">
            <h3><?php echo $edit_service ? '✏️ Edit Service' : '➕ Add New Service'; ?></h3>
            <form method="POST">
                <?php if ($edit_service): ?>
                    <input type="hidden" name="service_id" value="<?php echo $edit_service['id']; ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label>Service Name *</label>
                    <input type="text" name="service_name" value="<?php echo $edit_service ? htmlspecialchars($edit_service['service_name']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"><?php echo $edit_service ? htmlspecialchars($edit_service['description']) : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category" id="categorySelect" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($existing_categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($edit_service && $edit_service['category'] == $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="other">Other (new category)</option>
                    </select>
                    <input type="text" name="new_category" id="newCategoryInput" placeholder="Enter new category" style="margin-top:8px; display:none;">
                </div>
                <div class="form-group">
                    <label>Duration</label>
                    <input type="text" name="duration" placeholder="e.g., 2 months" value="<?php echo $edit_service ? htmlspecialchars($edit_service['duration']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Fee (৳)</label>
                    <input type="number" step="0.01" name="fee" placeholder="0.00" value="<?php echo $edit_service ? htmlspecialchars($edit_service['fee']) : ''; ?>">
                </div>
                <button type="submit" name="<?php echo $edit_service ? 'update_service' : 'add_service'; ?>" class="btn">
                    <?php echo $edit_service ? 'Update Service' : 'Add Service'; ?>
                </button>
                <?php if ($edit_service): ?>
                    <a href="services.php" class="btn btn-secondary" style="display:block; text-align:center; margin-top:10px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- List + Search -->
        <div class="list-card">
            <div class="search-bar">
                <form method="GET" style="display:flex; width:100%; gap:10px;">
                    <input type="text" name="search" placeholder="Search by name, category, description..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">🔍 Search</button>
                    <?php if ($search): ?>
                        <a href="services.php">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <h3>📋 Services List (<?php echo count($services); ?>)</h3>
            <?php if (empty($services)): ?>
                <div class="no-data">No services found.</div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="services-table">
                        <thead>
                            <tr>
                                <th>Service Name</th><th>Category</th><th>Duration</th><th>Fee</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $svc): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($svc['service_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars(substr($svc['description'], 0, 50)) . (strlen($svc['description']) > 50 ? '...' : ''); ?></small>
                                    </td>
                                    <td><span class="category-badge"><?php echo htmlspecialchars($svc['category']); ?></span></td>
                                    <td><?php echo htmlspecialchars($svc['duration']); ?></td>
                                    <td><span class="fee-badge">৳ <?php echo number_format($svc['fee'], 2); ?></span></td>
                                    <td class="actions">
                                        <a href="?edit=<?php echo $svc['id']; ?>" class="btn-small">Edit</a>
                                        <a href="?delete=<?php echo $svc['id']; ?>" class="btn-small btn-danger" onclick="return confirm('Delete this service?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
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
        clockEl.textContent = now.toLocaleTimeString('en-US', {
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
    }
}
updateClock();
setInterval(updateClock, 1000);

// Category "Other" handling
const catSelect = document.getElementById('categorySelect');
const newCatInput = document.getElementById('newCategoryInput');
function handleCategory() {
    if (catSelect.value === 'other') {
        newCatInput.style.display = 'block';
        newCatInput.required = true;
        newCatInput.name = 'category';
        catSelect.name = 'old_category';
    } else {
        newCatInput.style.display = 'none';
        newCatInput.required = false;
        newCatInput.name = 'new_category';
        catSelect.name = 'category';
    }
}
catSelect.addEventListener('change', handleCategory);
<?php if ($edit_service && !in_array($edit_service['category'], $existing_categories)): ?>
    catSelect.value = 'other';
    newCatInput.style.display = 'block';
    newCatInput.value = '<?php echo htmlspecialchars($edit_service['category']); ?>';
    newCatInput.required = true;
    newCatInput.name = 'category';
    catSelect.name = 'old_category';
<?php endif; ?>
handleCategory();
</script>
</body>
</html>