<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// Fetch students
$students = $conn->query("SELECT student_id, name FROM students ORDER BY name ASC");

// Handle routine submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_routine"])) {
    $student_id = $_POST["student_id"];
    $start_time = $_POST["start_time"];
    $end_time = $_POST["end_time"];
    $apply_all = isset($_POST["apply_all"]) ? true : false;

    if ($apply_all) {
        $days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $inserted = 0;
        foreach ($days as $day) {
            $check = $conn->query("SELECT id FROM student_routine WHERE student_id = '$student_id' AND day = '$day'");
            if ($check->num_rows == 0) {
                $conn->query("INSERT INTO student_routine (student_id, day, start_time, end_time) VALUES ('$student_id', '$day', '$start_time', '$end_time')");
                $inserted++;
            }
        }
        $msg = $inserted > 0 ? "$inserted days added (skipped existing days)." : "All days already have routines.";
        echo "<script>alert('$msg'); window.location.href='routine_generator.php?student_id=$student_id';</script>";
    } else {
        $day = $_POST["day"];
        $check = $conn->query("SELECT id FROM student_routine WHERE student_id = '$student_id' AND day = '$day'");
        if ($check->num_rows > 0) {
            echo "<script>alert('Routine already exists for this day. Please delete it first.'); window.location.href='routine_generator.php?student_id=$student_id';</script>";
            exit;
        }
        $conn->query("INSERT INTO student_routine (student_id, day, start_time, end_time) VALUES ('$student_id', '$day', '$start_time', '$end_time')");
        echo "<script>alert('Routine added successfully'); window.location.href='routine_generator.php?student_id=$student_id';</script>";
    }
    exit;
}

// Handle delete
if (isset($_GET["delete_id"])) {
    $delete_id = intval($_GET["delete_id"]);
    $student_id = $_GET["student_id"];
    $conn->query("DELETE FROM student_routine WHERE id = $delete_id");
    echo "<script>alert('Routine deleted'); window.location.href='routine_generator.php?student_id=$student_id';</script>";
    exit;
}

$selected_student = $_GET["student_id"] ?? null;
$student_info = null;
$routine_data = [];

if ($selected_student) {
    $stmt = $conn->prepare("SELECT student_id, name, course_category FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $selected_student);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) $student_info = $result->fetch_assoc();
    $stmt->close();

    $stmt2 = $conn->prepare("SELECT id, day, start_time, end_time FROM student_routine WHERE student_id = ?");
    $stmt2->bind_param("s", $selected_student);
    $stmt2->execute();
    $rout_res = $stmt2->get_result();
    while ($row = $rout_res->fetch_assoc()) {
        $routine_data[$row["day"]] = [
            'id' => $row["id"],
            'time' => date("h:i A", strtotime($row["start_time"])) . " - " . date("h:i A", strtotime($row["end_time"]))
        ];
    }
    $stmt2->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Routine Generator — AR TECH SOLUTION</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<style>
    body {
        background-image: url('<?php echo $bg_image; ?>');
        background-size: cover;
        background-attachment: fixed;
        background-position: center;
        font-family: var(--sans);
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
    display: flex;
    justify-content: center;
}
.main.collapsed { margin-left: 0; }

/* CONTENT WRAPPER */
.content-wrapper {
    width: 100%;
    max-width: 900px;
}

/* SECTION TITLE */
.section-title {
    font-family: var(--mono); font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 2px; color: var(--accent);
    margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
}
.section-title::after { content: ''; flex: 1; height: 1px; background: var(--glass-border); }

/* CARDS */
.card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 24px;
    margin-bottom: 24px;
}
.card h2, .card h3 {
    font-family: var(--mono);
    color: var(--accent);
    margin-bottom: 20px;
    font-size: 18px;
    border-left: 3px solid var(--accent);
    padding-left: 12px;
}
.back-btn {
    margin-bottom: 16px;
}
.back-btn button {
    background: linear-gradient(135deg, #555, #333);
    color: white;
    border: none;
    border-radius: 30px;
    padding: 8px 20px;
    font-weight: 600;
    cursor: pointer;
}
.student-info {
    background: rgba(255,255,255,0.05);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
}
.student-info p {
    margin: 5px 0;
    font-size: 14px;
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
    font-family: var(--sans);
    font-size: 14px;
    outline: none;
}
.form-group input:focus, .form-group select:focus {
    border-color: var(--accent);
}
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 12px 0;
    font-size: 14px;
}
.checkbox-label input {
    width: auto;
}
.submit-btn {
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    color: #000;
    font-weight: 700;
    padding: 10px;
    border: none;
    border-radius: 40px;
    font-size: 14px;
    cursor: pointer;
    width: 100%;
    margin-top: 8px;
}
.submit-btn:hover { opacity: .85; }
.routine-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 16px;
}
.routine-table th, .routine-table td {
    padding: 12px 8px;
    text-align: left;
    border-bottom: 1px solid var(--glass-border);
}
.routine-table th {
    background: rgba(0,0,0,0.3);
    color: var(--accent);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 11px;
}
.routine-table td {
    color: var(--text);
}
.delete-btn {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    border: none;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    cursor: pointer;
}
.delete-btn:hover { opacity: .85; }
.note-text {
    margin-top: 20px;
    font-size: 12px;
    color: var(--muted);
    font-style: italic;
}
.signature {
    text-align: right;
    margin-top: 20px;
    font-weight: bold;
    color: var(--accent);
}

/* ========== FORCE WHITE TEXT FOR ALL REGULAR CONTENT ========== */
.main, .card, .student-info, .student-info p, .routine-table td,
.note-text, .signature, .form-group label, .checkbox-label,
.back-btn button, .card p, .card .student-info p {
    color: #ffffff !important;
}
/* Keep accent colors for headings and special elements */
.card h2, .card h3, .routine-table th, .signature, .section-title {
    color: var(--accent) !important;
}
/* Make dropdown option text black for better readability */
.form-group select option {
    color: #000000 !important;
    background-color: #ffffff;
}
/* Keep input and select text readable (semi-white) */
.form-group input, .form-group select {
    color: #f0f0f0 !important;
}
/* Ensure the delete button text remains white */
.delete-btn {
    color: #ffffff !important;
}
/* Keep the error message red */
.card[style*="color: var(--accent3)"] {
    color: var(--accent3) !important;
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
    .content-wrapper { max-width: 100%; }
    .routine-table th, .routine-table td { font-size: 11px; padding: 8px 4px; }
}

@media print {
    .topnav, .sidebar, .sidebar-toggle-pill, .back-btn, .card:first-of-type, .footer {
        display: none !important;
    }
    .main { margin: 0; padding: 20px; background: white; }
    .card { background: white; backdrop-filter: none; border: 1px solid #ccc; }
    .routine-table th { background: #ddd; color: black; }
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
    <div class="content-wrapper">
        <div class="back-btn">
            <a href="dashboard.php"><button>🔙 Back to Dashboard</button></a>
        </div>

        <!-- Student Selection Card -->
        <div class="card">
            <h2>📅 Routine Generator</h2>
            <form method="GET">
                <div class="form-group">
                    <label>Select Student</label>
                    <select name="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php while ($row = $students->fetch_assoc()): ?>
                            <option value="<?php echo $row["student_id"]; ?>" <?php echo ($selected_student == $row["student_id"]) ? 'selected' : ''; ?>>
                                <?php echo $row["student_id"]; ?> - <?php echo htmlspecialchars($row["name"]); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="submit-btn">Load Student & Routine</button>
            </form>
        </div>

        <?php if ($selected_student && $student_info): ?>
            <!-- Add Routine Card -->
            <div class="card">
                <h3>➕ Add Class Time for <?php echo htmlspecialchars($student_info["name"]); ?></h3>
                <form method="POST">
                    <input type="hidden" name="student_id" value="<?php echo $selected_student; ?>">
                    
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="start_time" id="start_time" required onchange="setEndTime()">
                    </div>
                    <div class="form-group">
                        <label>End Time (auto 1 hour later)</label>
                        <input type="time" name="end_time" id="end_time" required readonly>
                    </div>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="apply_all" id="apply_all" onclick="toggleDaySelect()"> ✅ Apply to all days (Saturday to Friday)
                    </label>
                    
                    <div id="single_day_section">
                        <div class="form-group">
                            <label>Day (if not applying to all)</label>
                            <select name="day" id="day_select">
                                <?php foreach (['Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday'] as $day): ?>
                                    <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" name="submit_routine" class="submit-btn">➕ Add Routine</button>
                </form>
            </div>

            <!-- Routine Display Card -->
            <div class="card">
                <div class="student-info">
                    <p><strong>Student Name:</strong> <?php echo htmlspecialchars($student_info["name"]); ?></p>
                    <p><strong>ID:</strong> <?php echo htmlspecialchars($student_info["student_id"]); ?></p>
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($student_info["course_category"]); ?></p>
                </div>
                <h3>📖 Class Routine</h3>
                <div style="overflow-x: auto;">
                    <table class="routine-table">
                        <thead>
                            <tr><th>Day</th><th>Time</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $daysOfWeek = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                            foreach ($daysOfWeek as $day):
                                $hasRoutine = isset($routine_data[$day]);
                                $timeDisplay = $hasRoutine ? $routine_data[$day]['time'] : 'Close';
                                $deleteLink = $hasRoutine ? "<a href='?student_id=$selected_student&delete_id={$routine_data[$day]['id']}' onclick='return confirm(\"Delete this routine?\")'><button class='delete-btn' type='button'>🗑️ Delete</button></a>" : '';
                            ?>
                                <tr>
                                    <td><?php echo $day; ?></td>
                                    <td><?php echo $timeDisplay; ?></td>
                                    <td><?php echo $deleteLink; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="note-text">
                    * This Routine can be changed if any emergency occurs. Students must have to follow this routine.<br>
                    If course instructor does any changes in the routine he will inform you.
                </div>
                <div class="signature">
                    Md. Jahidul Hakim<br>Instructor<br>AR Tech Solution
                </div>
            </div>
        <?php elseif ($selected_student && !$student_info): ?>
            <div class="card" style="text-align:center; color: var(--accent3);">
                ❌ Student ID not found. Please select a valid student.
            </div>
        <?php endif; ?>
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

// Routine functions
function setEndTime() {
    const startInput = document.getElementById('start_time');
    const endInput = document.getElementById('end_time');
    if (startInput.value) {
        let [hour, minute] = startInput.value.split(":").map(Number);
        hour = (hour + 1) % 24;
        endInput.value = hour.toString().padStart(2, '0') + ":" + minute.toString().padStart(2, '0');
    }
}
function toggleDaySelect() {
    const applyAll = document.getElementById('apply_all');
    const daySelect = document.getElementById('day_select');
    if (daySelect) daySelect.disabled = applyAll.checked;
}
// Initial call
if (typeof toggleDaySelect === 'function') toggleDaySelect();
</script>
</body>
</html>