<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}
require_once 'config.php';   // <-- ADD THIS LINE

$student_id = $_GET["student_id"] ?? null;
$show_image = $_GET["show_image"] ?? null;
$data = null;

if ($student_id && !$show_image) {
    $student_id_escaped = $conn->real_escape_string($student_id);
    $sql = "SELECT * FROM students WHERE student_id = '$student_id_escaped'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows) {
        $data = $result->fetch_assoc();
    }
}

if ($show_image) {
    $filename = basename($_GET["show_image"]);
    $path = __DIR__ . "/uploads/" . $filename;
    if (file_exists($path)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);
        header("Content-Type: " . $mime);
        readfile($path);
    } else {
        header("Content-Type: image/jpeg");
        readfile(__DIR__ . "/uploads/placeholder.png");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admission Form — AR TECH SOLUTION</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<style>
    body {
        background-image: url('<?php echo $bg_image; ?>');
        background-size: cover;
        background-attachment: fixed;
        background-position: center;
        font-family: var(--sans);
    }
    /* Keep all your existing styles */
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

/* FORM CARD */
.form-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 28px;
    max-width: 900px;
    margin: 0 auto;
}
.header-box {
    display: flex;
    justify-content: space-between;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}
.logo-box, .photo-box {
    width: 130px;
    height: 120px;
    background: rgba(255,255,255,0.05);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.logo-box img, .photo-box img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: 8px;
}
.form-card h2, .form-card h3 {
    font-family: var(--mono);
    color: var(--accent);
    margin: 20px 0 12px 0;
}
.form-card h2 { font-size: 24px; margin-top: 0; }
.form-card h3 { font-size: 18px; border-left: 3px solid var(--accent); padding-left: 12px; }
.field {
    display: flex;
    flex-wrap: wrap;
    margin-bottom: 10px;
    padding: 4px 0;
    border-bottom: 1px dashed var(--glass-border);
}
.field label {
    width: 180px;
    font-weight: 600;
    color: var(--accent);
}
.field span {
    flex: 1;
    color: var(--text);
}
.undertaking {
    margin-top: 20px;
    padding: 12px;
    background: rgba(255,255,255,0.03);
    border-radius: 12px;
    font-size: 12px;
    font-style: italic;
}
.signature {
    text-align: right;
    margin-top: 30px;
    font-weight: bold;
    padding-top: 15px;
    border-top: 1px dashed var(--glass-border);
}
.print-btn {
    text-align: center;
    margin-top: 20px;
}
.print-btn button {
    background: linear-gradient(135deg, var(--accent2), #9b59b6);
    color: white;
    padding: 10px 28px;
    border: none;
    border-radius: 40px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity .2s;
}
.print-btn button:hover { opacity: .85; }
.error-msg {
    text-align: center;
    color: var(--accent3);
    padding: 20px;
    background: var(--glass);
    border-radius: var(--card-radius);
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
    .field { flex-direction: column; }
    .field label { width: auto; margin-bottom: 4px; }
    .header-box { justify-content: center; }
}

/* ========== ENHANCED A4 PRINT (FITS ONE PAGE) ========== */
@media print {
    /* Hide all non-print elements */
    .topnav, .sidebar, .sidebar-toggle-pill, .search-card, .print-btn, .footer, .hamburger, .topnav-right {
        display: none !important;
    }

    /* Reset main area */
    .main {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
        min-height: auto !important;
        display: block !important;
    }

    /* Remove background overlay */
    body, body::before {
        background: white !important;
        background-color: white !important;
        content: none !important;
    }

    /* Form card - optimized for A4 */
    .form-card {
        background: white !important;
        backdrop-filter: none !important;
        border: 1px solid #ccc !important;
        border-radius: 0 !important;
        padding: 8mm !important;          /* Reduced padding for better fit */
        margin: 0 auto !important;
        max-width: 100% !important;
        width: 100% !important;
        box-shadow: none !important;
        color: black !important;
        page-break-after: avoid;
        page-break-inside: avoid;
        font-size: 10pt !important;       /* Slightly smaller for A4 */
    }

    /* Ensure all text is black/dark */
    .form-card, .form-card * {
        color: #000 !important;
    }

    .form-card h2 {
        font-size: 16pt !important;
        margin: 5px 0 10px 0 !important;
    }
    .form-card h3 {
        font-size: 12pt !important;
        margin: 10px 0 6px 0 !important;
    }

    .field label {
        width: 150px !important;
        color: #333 !important;
        font-weight: 700;
    }
    .field {
        margin-bottom: 6px !important;
        padding: 2px 0 !important;
    }
    .undertaking {
        margin-top: 10px !important;
        padding: 8px !important;
        font-size: 9pt !important;
    }
    .signature {
        margin-top: 15px !important;
        padding-top: 8px !important;
    }

    /* Images */
    .logo-box, .photo-box {
        width: 100px !important;
        height: 90px !important;
    }

    /* Page setup */
    @page {
        size: A4;
        margin: 10mm;   /* Safe margins for all printers */
    }

    body {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
    }

    /* Prevent any internal page break */
    .form-card, .header-box, .field, .undertaking, .signature {
        page-break-inside: avoid;
        page-break-after: avoid;
    }
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

<!-- SIDEBAR (same as dashboard) -->
<?php
include 'navigation.php';
?>
    

<div class="sidebar-toggle-pill" id="sidebarToggle">◀</div>

<!-- MAIN CONTENT -->
<main class="main" id="mainContent">
    <!-- Search Card -->
    <div class="search-card">
        <form method="GET">
            <input type="text" name="student_id" placeholder="Enter Student ID" value="<?php echo htmlspecialchars($student_id ?? ''); ?>" required>
            <button type="submit">Load Form</button>
        </form>
    </div>

    <?php if ($data): ?>
        <div class="form-card">
            <div class="header-box">
                <div class="logo-box">
                    <img src="uploads/logo.png" alt="Logo" onerror="this.src='uploads/placeholder.png'">
                </div>
                <div class="photo-box">
                    <img src="?show_image=<?php echo urlencode($data["profile_image"]); ?>" alt="Profile Photo">
                </div>
            </div>

            <h2>Admission Form</h2>
            <h3>Personal Information</h3>
            <div class="field"><label>Reg. No:</label><span><?php echo htmlspecialchars($data["student_id"]); ?></span></div>
            <div class="field"><label>Course Name:</label><span><?php echo htmlspecialchars($data["course_category"]); ?></span></div>
            <div class="field"><label>Date of Issue:</label><span><?php echo htmlspecialchars($data["issue_date"]); ?></span></div>
            <div class="field"><label>Name:</label><span><?php echo htmlspecialchars($data["name"]); ?></span></div>
            <div class="field"><label>Father’s Name:</label><span><?php echo htmlspecialchars($data["father_name"]); ?></span></div>
            <div class="field"><label>Mother’s Name:</label><span><?php echo htmlspecialchars($data["mother_name"]); ?></span></div>
            <div class="field"><label>DOB:</label><span><?php echo htmlspecialchars($data["dob"]); ?></span></div>
            <div class="field"><label>Gender:</label><span><?php echo ($data["gender"] === "Male") ? "☑ Male ☐ Female" : "☐ Male ☑ Female"; ?></span></div>
            <div class="field"><label>Marital Status:</label><span><?php echo htmlspecialchars($data["marital_status"]); ?></span></div>
            <div class="field"><label>Religion:</label><span><?php echo htmlspecialchars($data["religion"]); ?></span></div>

            <h3>Contact Information</h3>
            <div class="field"><label>Email:</label><span><?php echo htmlspecialchars($data["email"]); ?></span></div>
            <div class="field"><label>Phone:</label><span><?php echo htmlspecialchars($data["phone_number"]); ?></span></div>
            <div class="field"><label>Present Address:</label><span><?php echo htmlspecialchars($data["present_address"]); ?></span></div>
            <div class="field"><label>Permanent Address:</label><span><?php echo htmlspecialchars($data["permanent_address"]); ?></span></div>

            <h3>Identification</h3>
            <div class="field"><label>ID Type:</label><span><?php echo htmlspecialchars($data["id_type"]); ?></span></div>
            <div class="field"><label>NID/Birth No:</label><span><?php echo htmlspecialchars($data["nid_birth_id"]); ?></span></div>

            <div class="undertaking">
                <strong>UNDERTAKING</strong><br>
                A. I hereby declare that the information provided is true and correct.<br>
                B. I agree to abide by the rules and regulations of the institute.<br>
                C. I understand that any false information may lead to cancellation of admission.
            </div>

            <div class="signature">Signature: ____________________________</div>
            <div class="print-btn">
                <button onclick="window.print()">🖨️ Print This Form</button>
            </div>
        </div>
    <?php elseif ($student_id): ?>
        <div class="error-msg">❌ Student ID not found. Please try again.</div>
    <?php endif; ?>
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
</script>
</body>
</html>