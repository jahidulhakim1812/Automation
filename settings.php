<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$message = "";
$error = "";

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Dark mode
    if (isset($_POST['update_dark_mode'])) {
        $dark_mode = isset($_POST['dark_mode']) ? '1' : '0';
        $stmt = $conn->prepare("REPLACE INTO app_settings (setting_key, setting_value) VALUES ('dark_mode', ?)");
        $stmt->bind_param("s", $dark_mode);
        if ($stmt->execute()) {
            $message = "Dark mode preference saved.";
        } else {
            $error = "Failed to save dark mode.";
        }
        $stmt->close();
    }

    // Sidebar labels
    if (isset($_POST['update_sidebar_labels'])) {
        $labels = [];
        // Collect all posted label fields
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'label_') === 0) {
                $labels[substr($key, 6)] = trim($value);
            }
        }
        $json = json_encode($labels);
        $stmt = $conn->prepare("REPLACE INTO app_settings (setting_key, setting_value) VALUES ('sidebar_labels', ?)");
        $stmt->bind_param("s", $json);
        if ($stmt->execute()) {
            $message = "Sidebar labels updated successfully.";
            // Refresh sidebar_labels for current page
            $sidebar_labels = $labels;
        } else {
            $error = "Failed to save sidebar labels.";
        }
        $stmt->close();
    }

    // Background image upload
    if (isset($_POST['update_background'])) {
        if (isset($_FILES['bg_image']) && $_FILES['bg_image']['error'] == 0) {
            $target_dir = "uploads/backgrounds/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $filename = time() . "_" . basename($_FILES['bg_image']['name']);
            $target_file = $target_dir . $filename;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($imageFileType, $allowed)) {
                if (move_uploaded_file($_FILES['bg_image']['tmp_name'], $target_file)) {
                    $bg_path = $target_dir . $filename;
                    $stmt = $conn->prepare("REPLACE INTO app_settings (setting_key, setting_value) VALUES ('background_image', ?)");
                    $stmt->bind_param("s", $bg_path);
                    if ($stmt->execute()) {
                        $message = "Background image updated.";
                        $bg_image = $bg_path;
                    } else {
                        $error = "Database error.";
                    }
                    $stmt->close();
                } else {
                    $error = "File upload failed.";
                }
            } else {
                $error = "Only JPG, PNG, GIF, WEBP allowed.";
            }
        } else {
            $error = "Please select an image file.";
        }
    }

    // Reset background to default
    if (isset($_POST['reset_background'])) {
        $default_bg = 'uploads/banner.jpg';
        $stmt = $conn->prepare("REPLACE INTO app_settings (setting_key, setting_value) VALUES ('background_image', ?)");
        $stmt->bind_param("s", $default_bg);
        if ($stmt->execute()) {
            $message = "Background reset to default.";
            $bg_image = $default_bg;
        } else {
            $error = "Reset failed.";
        }
        $stmt->close();
    }
}

// Re-fetch settings after updates
$result = $conn->query("SELECT setting_key, setting_value FROM app_settings");
$settings = [];
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$dark_mode = isset($settings['dark_mode']) && $settings['dark_mode'] == '1';
$bg_image = isset($settings['background_image']) ? $settings['background_image'] : 'uploads/banner.jpg';
$sidebar_labels = isset($settings['sidebar_labels']) ? json_decode($settings['sidebar_labels'], true) : [];

// Default sidebar structure (keys for labels)
$default_menus = [
    'dashboard' => 'Dashboard',
    'account' => 'Account',
    'account_overview' => 'Account Overview',
    'account_report' => 'Account Report',
    'change_password' => 'Change Password',
    'student_info' => 'Student Info',
    'add_student' => 'Add Student',
    'total_student_list' => 'Total Student List',
    'student_form' => 'Student Form',
    'course_complete' => 'Course Complete',
    'course_incomplete' => 'Course Incomplete',
    'ongoing' => 'Ongoing',
    'customers' => 'Customers',
    'add_customer' => 'Add Customer',
    'customer_list' => 'Customer List',
    'services' => 'Services',
    'manage_services' => 'Manage Services',
    'assign_service' => 'Assign Service',
    'delete' => 'Delete',
    'report' => 'Report',
    'payment' => 'Payment',
    'pos_invoice' => 'POS Invoice',
    'invoice_list' => 'Invoice List',
    'print_invoice' => 'Print Invoice',
    'verify_invoice' => 'Verify Invoice',
    'add_payment' => 'Add Payment',
    'due_payment_list' => 'Due Payment List',
    'attendance' => 'Attendance',
    'take_attendance' => 'Take Attendance',
    'attendance_report' => 'View Report',
    'certificate' => 'Certificate',
    'upload_certificate' => 'Upload Certificate',
    'view_certificate' => 'View Certificate',
    'video' => 'Video',
    'upload_video' => 'Upload Video',
    'view_videos' => 'View Videos',
    'routine' => 'Routine'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings — AR TECH SOLUTION</title>
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
    background: url('<?php echo $bg_image; ?>') no-repeat center center fixed;
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

/* Dark mode overrides */
body.dark-mode {
    --bg: rgba(0,0,0,0.9);
    --glass: rgba(0,0,0,0.5);
    --glass-border: rgba(255,255,255,0.1);
    --text: #e0e0e0;
}
body.dark-mode::before {
    background: rgba(0,0,0,0.85);
}

/* TOP NAV (same as dashboard) */
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

/* SETTINGS CARDS */
.settings-container {
    max-width: 900px;
    width: 100%;
}
.card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 24px;
    margin-bottom: 24px;
}
.card h2 {
    font-family: var(--mono);
    font-size: 18px;
    color: var(--accent);
    margin-bottom: 20px;
    border-left: 3px solid var(--accent);
    padding-left: 12px;
}
.form-group {
    margin-bottom: 16px;
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
.form-group input, .form-group select {
    width: 100%;
    padding: 10px 12px;
    background: rgba(255,255,255,0.08);
    border: 1px solid var(--glass-border);
    border-radius: 10px;
    color: var(--text);
    font-size: 14px;
}
.form-group input:focus {
    border-color: var(--accent);
}
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}
.checkbox-label input {
    width: auto;
}
.submit-btn {
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    color: #000;
    font-weight: 700;
    padding: 10px 20px;
    border: none;
    border-radius: 30px;
    cursor: pointer;
    margin-top: 8px;
}
.submit-btn:hover { opacity: .85; }
.reset-btn {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
}
.message {
    background: rgba(6,214,160,0.15);
    border: 1px solid var(--accent5);
    color: var(--accent5);
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 20px;
}
.error {
    background: rgba(255,107,107,0.15);
    border-color: var(--accent3);
    color: var(--accent3);
}
.sidebar-labels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
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
    .sidebar-labels-grid { grid-template-columns: 1fr; }
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

<!-- SIDEBAR (dynamic labels) -->
<aside class="sidebar" id="sidebar">
    <a href="dashboard.php"><?php echo get_sidebar_label('dashboard', '📊 Dashboard'); ?></a>
    <div class="sidebar-divider"></div>

    <!-- Account Group -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('account', '💵 Account'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="account.php"><?php echo get_sidebar_label('account_overview', 'Account Overview'); ?></a>
            <a href="account_report.php"><?php echo get_sidebar_label('account_report', 'Account Report'); ?></a>
            <a href="change_password.php"><?php echo get_sidebar_label('change_password', 'Change Password'); ?></a>
        </div>
    </div>

    <!-- Student Info Group -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('student_info', '👤 Student Info'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="insert.php"><?php echo get_sidebar_label('add_student', 'Add Student'); ?></a>
            <a href="student_list.php"><?php echo get_sidebar_label('total_student_list', 'Total Student List'); ?></a>
            <a href="form_view.php"><?php echo get_sidebar_label('student_form', 'Student Form'); ?></a>
            <a href="completed_students.php"><?php echo get_sidebar_label('course_complete', 'Course Complete'); ?></a>
            <a href="incomplete_students.php"><?php echo get_sidebar_label('course_incomplete', 'Course Incomplete'); ?></a>
            <a href="ongoing_students.php"><?php echo get_sidebar_label('ongoing', 'Ongoing'); ?></a>
        </div>
    </div>

    <!-- Customers Group -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('customers', '👥 Customers'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="add_customer.php"><?php echo get_sidebar_label('add_customer', 'Add Customer'); ?></a>
            <a href="customer_list.php"><?php echo get_sidebar_label('customer_list', 'Customer List'); ?></a>
        </div>
    </div>

    <!-- Services Group -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('services', '🛠️ Services'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="services.php"><?php echo get_sidebar_label('manage_services', 'Manage Services'); ?></a>
            <a href="assign_service.php"><?php echo get_sidebar_label('assign_service', 'Assign Service'); ?></a>
        </div>
    </div>

    <a href="delete.php"><?php echo get_sidebar_label('delete', '🗑️ Delete'); ?></a>
    <a href="report.php"><?php echo get_sidebar_label('report', '📄 Report'); ?></a>

    <!-- Payment Group -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('payment', '💵 Payment'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="invoice_pos.php"><?php echo get_sidebar_label('pos_invoice', '🧾 POS Invoice'); ?></a>
            <a href="invoice_list.php"><?php echo get_sidebar_label('invoice_list', '📋 Invoice List'); ?></a>
            <a href="invoice.php"><?php echo get_sidebar_label('print_invoice', 'Print Invoice'); ?></a>
            <a href="view_invoice.php"><?php echo get_sidebar_label('verify_invoice', 'Verify Invoice'); ?></a>
            <a href="input_payment.php"><?php echo get_sidebar_label('add_payment', 'Add Payment'); ?></a>
            <a href="payment_due.php"><?php echo get_sidebar_label('due_payment_list', 'Due Payment List'); ?></a>
        </div>
    </div>

    <!-- Attendance Group -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('attendance', '📆 Attendance'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="attendance.php"><?php echo get_sidebar_label('take_attendance', 'Take Attendance'); ?></a>
            <a href="attendance_report.php"><?php echo get_sidebar_label('attendance_report', 'View Report'); ?></a>
        </div>
    </div>

    <!-- Certificate Group -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('certificate', '📜 Certificate'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="upload_certificate.php"><?php echo get_sidebar_label('upload_certificate', 'Upload Certificate'); ?></a>
            <a href="certificate_list.php"><?php echo get_sidebar_label('view_certificate', 'View Certificate'); ?></a>
        </div>
    </div>

    <!-- Video Group -->
    <div class="menu-group">
        <div class="menu-toggle"><?php echo get_sidebar_label('video', '🎬 Video'); ?> <span class="menu-arrow">▾</span></div>
        <div class="submenu">
            <a href="upload_video.php"><?php echo get_sidebar_label('upload_video', 'Upload Video'); ?></a>
            <a href="view_videos.php"><?php echo get_sidebar_label('view_videos', 'View Videos'); ?></a>
        </div>
    </div>

    <a href="routine_generator.php"><?php echo get_sidebar_label('routine', '🕒 Routine'); ?></a>
</aside>

<div class="sidebar-toggle-pill" id="sidebarToggle">◀</div>

<main class="main" id="mainContent">
    <div class="settings-container">
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Dark Mode Section -->
        <div class="card">
            <h2>🌓 Dark Mode</h2>
            <form method="POST">
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="dark_mode" value="1" <?php echo $dark_mode ? 'checked' : ''; ?>> Enable Dark Mode
                    </label>
                </div>
                <button type="submit" name="update_dark_mode" class="submit-btn">Save Dark Mode Preference</button>
            </form>
        </div>

        <!-- Sidebar Labels Section -->
        <div class="card">
            <h2>✏️ Customize Sidebar Menu Names</h2>
            <form method="POST">
                <div class="sidebar-labels-grid">
                    <?php foreach ($default_menus as $key => $default_label): ?>
                        <?php $current = isset($sidebar_labels[$key]) ? $sidebar_labels[$key] : ''; ?>
                        <div class="form-group">
                            <label><?php echo htmlspecialchars($default_label); ?></label>
                            <input type="text" name="label_<?php echo $key; ?>" value="<?php echo htmlspecialchars($current); ?>" placeholder="Custom name (leave empty for default)">
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" name="update_sidebar_labels" class="submit-btn">Save Sidebar Labels</button>
            </form>
        </div>

        <!-- Background Image Section -->
        <div class="card">
            <h2>🖼️ Background Image</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Upload New Background (JPG, PNG, GIF, WEBP)</label>
                    <input type="file" name="bg_image" accept="image/*">
                </div>
                <button type="submit" name="update_background" class="submit-btn">Upload & Set</button>
                <button type="submit" name="reset_background" class="submit-btn reset-btn" style="margin-left:10px;">Reset to Default</button>
            </form>
            <div style="margin-top: 16px;">
                <strong>Current Preview:</strong><br>
                <img src="<?php echo $bg_image; ?>" alt="Background Preview" style="max-width: 100%; max-height: 150px; border-radius: 8px; margin-top: 8px; border: 1px solid var(--glass-border);">
            </div>
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

// Apply dark mode immediately on toggle (without page reload)
const darkModeCheckbox = document.querySelector('input[name="dark_mode"]');
if (darkModeCheckbox) {
    darkModeCheckbox.addEventListener('change', function() {
        if (this.checked) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
    });
}
// Initial dark mode state
if (<?php echo $dark_mode ? 'true' : 'false'; ?>) {
    document.body.classList.add('dark-mode');
}
</script>
</body>
</html>