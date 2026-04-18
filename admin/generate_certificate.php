<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$message = "";
$error = "";
$selected_student = null;
$certificate_data = null;

// Handle saving certificate
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_certificate'])) {
    $student_id = trim($_POST['student_id']);
    $certificate_number = trim($_POST['certificate_number']);
    $issue_date = $_POST['issue_date'];
    
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($student) {
        $stmt = $conn->prepare("INSERT INTO generated_certificates (student_id, certificate_number, issue_date, course_name, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $student_id, $certificate_number, $issue_date, $student['course_category'], $student['course_start_date'], $student['course_end_date']);
        if ($stmt->execute()) {
            $message = "Certificate saved successfully!";
            $selected_student = $student;
            $certificate_data = ['certificate_number' => $certificate_number, 'issue_date' => $issue_date];
        } else {
            $error = "Certificate already exists for this student/number.";
        }
        $stmt->close();
    } else {
        $error = "Student not found.";
    }
}

// Handle student selection
if (isset($_GET['student_id']) && !empty($_GET['student_id'])) {
    $student_id = trim($_GET['student_id']);
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $selected_student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($selected_student) {
        $stmt = $conn->prepare("SELECT * FROM generated_certificates WHERE student_id = ? ORDER BY generated_at DESC LIMIT 1");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $certificate_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$students_list = $conn->query("SELECT student_id, name FROM students ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Generate Certificate — AR TECH SOLUTION</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&family=Great+Vibes&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
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

/* SELECTION CARD */
.card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 24px;
    margin-bottom: 24px;
}
.form-group {
    margin-bottom: 16px;
}
.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    color: #fff;
    margin-bottom: 6px;
}
.form-group select, .form-group input {
    width: 100%;
    padding: 10px 12px;
    background: rgba(255,255,255,0.08);
    border: 1px solid var(--glass-border);
    border-radius: 10px;
    color: #f0f0f0;
    font-size: 14px;
}
.btn {
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    color: #000;
    font-weight: 700;
    padding: 10px 20px;
    border: none;
    border-radius: 30px;
    cursor: pointer;
}
.btn-secondary {
    background: linear-gradient(135deg, #6c757d, #5a6268);
    color: #fff;
}

/* CERTIFICATE DESIGN - EXACT MATCH */
.certificate-container {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}
.certificate {
    background: #fff;
    color: #2c3e50;
    font-family: 'Poppins', serif;
    width: 850px;
    max-width: 100%;
    padding: 40px 30px;
    border: 15px double #b8860b;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    text-align: center;
    position: relative;
}
.certificate:before {
    content: "★";
    font-size: 28px;
    color: #b8860b;
    position: absolute;
    top: 15px;
    left: 20px;
    opacity: 0.6;
}
.certificate:after {
    content: "★";
    font-size: 28px;
    color: #b8860b;
    position: absolute;
    bottom: 15px;
    right: 20px;
    opacity: 0.6;
}
.certificate-title {
    font-size: 38px;
    font-weight: 800;
    letter-spacing: 2px;
    color: #b8860b;
    border-bottom: 2px solid #b8860b;
    display: inline-block;
    padding-bottom: 5px;
    margin-bottom: 15px;
}
.certificate-subtitle {
    font-size: 16px;
    font-weight: 600;
    letter-spacing: 3px;
    margin-bottom: 25px;
    color: #555;
}
.certificate-presented {
    font-size: 15px;
    margin-top: 15px;
    letter-spacing: 1px;
}
.student-name {
    font-family: 'Great Vibes', cursive;
    font-size: 52px;
    font-weight: normal;
    color: #8b4513;
    margin: 10px 0 5px;
}
.parents {
    font-size: 15px;
    margin-bottom: 20px;
    font-style: italic;
}
.course-details {
    font-size: 15px;
    margin: 20px 0;
    line-height: 1.6;
    text-align: center;
}
.course-details strong {
    color: #b8860b;
}
.certificate-id {
    margin-top: 25px;
    font-size: 14px;
    border-top: 1px dashed #aaa;
    padding-top: 15px;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    text-align: left;
}
.certificate-id span {
    flex: 1;
}
.signature {
    margin-top: 40px;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    border-top: 1px solid #ccc;
    padding-top: 15px;
    font-size: 13px;
}
.signature-line {
    width: 200px;
    text-align: center;
}
.signature-line .line {
    border-top: 1px solid #000;
    width: 100%;
    margin-bottom: 5px;
}
.print-btn {
    text-align: center;
    margin-top: 20px;
}
.alert {
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 20px;
}
.alert-success {
    background: rgba(6,214,160,0.15);
    color: #06d6a0;
}
.alert-error {
    background: rgba(255,107,107,0.15);
    color: #ff6b6b;
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
    .certificate { padding: 20px; }
    .student-name { font-size: 32px; }
}
@media print {
    .topnav, .sidebar, .sidebar-toggle-pill, .footer, .card, .print-btn, .section-title {
        display: none !important;
    }
    .main {
        margin: 0 !important;
        padding: 0 !important;
        background: white;
    }
    .certificate {
        box-shadow: none;
        border: 2px solid #b8860b;
        margin: 0 auto;
    }
    @page {
        size: A4;
        margin: 1.5cm;
    }
}
</style>
</head>
<body class="<?php echo $dark_mode ? 'dark-mode' : ''; ?>">

<!-- TOP NAVIGATION -->
<nav class="topnav">
    <div style="display:flex;align-items:center;gap:14px;">
        <button class="hamburger" id="hamburgerBtn">☰</button>
        <div class="topnav-brand"><div class="brand-dot"></div><span>AR TECH</span> SOLUTION</div>
    </div>
    <div class="topnav-right">
        <div class="topnav-time" id="liveClock"></div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<!-- SIDEBAR (dynamic labels) -->
<?php include 'navigation.php'; ?>

<div class="sidebar-toggle-pill" id="sidebarToggle">◀</div>

<main class="main" id="mainContent">
    <div class="section-title">📜 Generate Certificate</div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Student Selection Card -->
    <div class="card">
        <h3 style="color:var(--accent); margin-bottom:15px;">Select Student</h3>
        <form method="GET" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
            <div class="form-group" style="flex:2;">
                <label>Student</label>
                <select name="student_id" required>
                    <option value="">-- Select Student --</option>
                    <?php foreach ($students_list as $stu): ?>
                        <option value="<?php echo htmlspecialchars($stu['student_id']); ?>" <?php echo (isset($_GET['student_id']) && $_GET['student_id'] == $stu['student_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($stu['student_id'] . " - " . $stu['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn">Load Student</button>
            </div>
        </form>
    </div>

    <?php if ($selected_student): ?>
        <!-- Certificate Details Card -->
        <div class="card">
            <h3 style="color:var(--accent); margin-bottom:15px;">Certificate Details</h3>
            <form method="POST">
                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($selected_student['student_id']); ?>">
                <div class="form-group">
                    <label>Certificate Number</label>
                    <input type="text" name="certificate_number" value="<?php echo htmlspecialchars($certificate_data['certificate_number'] ?? 'CERT-' . date('Ymd') . '-' . $selected_student['student_id']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Issue Date</label>
                    <input type="date" name="issue_date" value="<?php echo htmlspecialchars($certificate_data['issue_date'] ?? date('Y-m-d')); ?>" required>
                </div>
                <button type="submit" name="save_certificate" class="btn">💾 Save Certificate</button>
            </form>
        </div>

        <!-- Certificate Display -->
        <div class="certificate-container">
            <div class="certificate" id="certificateContent">
                <div class="certificate-title">CERTIFICATE OF TRAINING</div>
                <div class="certificate-subtitle">OF TRAINING</div>
                <div class="certificate-presented">THIS CERTIFICATE IS PROUDLY PRESENTED TO</div>
                <div class="student-name"><?php echo htmlspecialchars($selected_student['name']); ?></div>
                <div class="parents">Son of <?php echo htmlspecialchars($selected_student['father_name']); ?> & <?php echo htmlspecialchars($selected_student['mother_name']); ?></div>
                <div class="course-details">
                    has successfully completed the <strong><?php echo htmlspecialchars($selected_student['course_category']); ?></strong> Course<br>
                    held on <?php echo date('d F Y', strtotime($selected_student['course_start_date'])); ?> to <?php echo date('d F Y', strtotime($selected_student['course_end_date'])); ?> at AR Tech Solution
                </div>
                <div class="certificate-id">
                    <span><strong>ID No:</strong> <?php echo htmlspecialchars($selected_student['student_id']); ?></span>
                    <span><strong>Date of Issue:</strong> <?php echo isset($_POST['issue_date']) ? date('d/m/Y', strtotime($_POST['issue_date'])) : (isset($certificate_data['issue_date']) ? date('d/m/Y', strtotime($certificate_data['issue_date'])) : date('d/m/Y')); ?></span>
                </div>
                <div class="signature">
                    <div class="signature-line">
                        <div class="line"></div>
                        <div>DATE</div>
                    </div>
                    <div class="signature-line">
                        <div class="line"></div>
                        <div>SIGNATURE</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="print-btn">
            <button onclick="window.print()" class="btn btn-secondary">🖨️ Print Certificate</button>
        </div>
    <?php endif; ?>
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
document.querySelectorAll('.menu-toggle').forEach(t => t.addEventListener('click', (e) => { e.stopPropagation(); t.closest('.menu-group').classList.toggle('open'); }));
function updateClock() {
    const clockEl = document.getElementById('liveClock');
    if (clockEl) {
        const now = new Date();
        clockEl.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
}
updateClock();
setInterval(updateClock, 1000);
</script>
</body>
</html>