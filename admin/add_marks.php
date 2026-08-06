<?php
session_start();

// Admin session check
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}
require_once 'config.php';

// ---------- Handle Add Marks POST ----------
$message = '';
$message_type = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_mark'])) {
    $student_id = $_POST['student_id'];
    $exam_name = trim($_POST['exam_name']);
    $marks = $_POST['marks'] !== '' ? $_POST['marks'] : null;
    $project_marks = $_POST['project_marks'] !== '' ? $_POST['project_marks'] : null;
    $assignment_marks = $_POST['assignment_marks'] !== '' ? $_POST['assignment_marks'] : null;

    if (empty($student_id) || empty($exam_name)) {
        $message = "Student and Exam Name are required.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO marks (student_id, exam_name, marks, project_marks, assignment_marks) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddd", $student_id, $exam_name, $marks, $project_marks, $assignment_marks);
        if ($stmt->execute()) {
            $message = "Mark entry added successfully.";
            $message_type = "success";
        } else {
            $message = "Error: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

// ---------- Get distinct courses ----------
$courses_result = $conn->query("SELECT DISTINCT course_category FROM students WHERE course_category IS NOT NULL AND course_category != '' ORDER BY course_category");

// ---------- Get filters ----------
$selected_course = isset($_GET['course']) ? $_GET['course'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// ---------- Build dynamic query ----------
$conditions = [];
$params = [];
$types = "";

if ($selected_course) {
    $conditions[] = "course_category = ?";
    $params[] = $selected_course;
    $types .= "s";
}
if ($search) {
    $search_term = "%$search%";
    $conditions[] = "(name LIKE ? OR email LIKE ? OR student_id LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$sql = "SELECT student_id, name, email, phone_number FROM students";
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY name";

$students = [];
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
} else {
    // No filters: show all students
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Marks — AR TECH SOLUTION</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<style>
    /* Same base styles as previous pages */
    body {
        background-image: url('<?php echo $bg_image ?? ''; ?>');
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

/* FILTER CARD */
.filter-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 20px;
    margin-bottom: 28px;
}
.filter-form {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
}
.filter-form select, .filter-form input {
    padding: 12px 16px;
    background: rgba(255,255,255,0.08);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    color: var(--text);
    font-family: var(--sans);
    font-size: 14px;
    outline: none;
    min-width: 180px;
    flex: 1 1 180px;
}
.filter-form select:focus, .filter-form input:focus {
    border-color: var(--accent);
    background: rgba(255,255,255,0.12);
}
.filter-form button {
    padding: 12px 24px;
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    border: none;
    border-radius: 12px;
    color: #000;
    font-weight: 700;
    cursor: pointer;
    transition: opacity .2s;
    white-space: nowrap;
}
.filter-form button:hover { opacity: .85; }
.filter-form .clear-btn {
    background: rgba(255,255,255,0.1);
    color: var(--text);
}
.filter-form .clear-btn:hover { background: rgba(255,255,255,0.2); }

/* TABLE CARD */
.table-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 20px;
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
th, td {
    padding: 12px 8px;
    text-align: left;
    border-bottom: 1px solid var(--glass-border);
}
th {
    background: rgba(0,0,0,0.3);
    color: var(--accent);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 11px;
}
td {
    color: var(--text);
}
tr:hover td {
    background: rgba(255,255,255,0.03);
}
.add-btn {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    color: white;
    padding: 6px 16px;
    border-radius: 20px;
    border: none;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity .2s;
}
.add-btn:hover { opacity: .85; }

/* MESSAGE */
.msg {
    padding: 12px 18px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-weight: 500;
}
.msg.success { background: rgba(6,214,160,0.2); border-left: 4px solid #06d6a0; color: #06d6a0; }
.msg.error { background: rgba(255,107,107,0.2); border-left: 4px solid #ff6b6b; color: #ff6b6b; }

/* MODAL */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
    z-index: 2000;
    justify-content: center;
    align-items: center;
}
.modal.active { display: flex; }
.modal-content {
    background: #1a1f2e;
    padding: 30px 35px;
    border-radius: 24px;
    max-width: 500px;
    width: 90%;
    border: 1px solid var(--glass-border);
    box-shadow: var(--shadow);
}
.modal-content h3 {
    color: var(--accent);
    font-family: var(--mono);
    margin-bottom: 20px;
    font-size: 20px;
}
.modal-content label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: var(--muted);
    margin: 12px 0 4px;
}
.modal-content input {
    width: 100%;
    padding: 10px 14px;
    background: rgba(255,255,255,0.07);
    border: 1px solid var(--glass-border);
    border-radius: 10px;
    color: var(--text);
    font-size: 14px;
}
.modal-content input:focus {
    border-color: var(--accent);
    outline: none;
}
.modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 22px;
    justify-content: flex-end;
}
.modal-actions button {
    padding: 10px 28px;
    border-radius: 30px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: opacity .2s;
}
.modal-actions .btn-submit {
    background: var(--accent);
    color: #000;
}
.modal-actions .btn-cancel {
    background: rgba(255,255,255,0.1);
    color: var(--text);
}
.modal-actions button:hover { opacity: .8; }

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
    .filter-form { flex-direction: column; align-items: stretch; }
    .filter-form button { width: 100%; }
    th, td { padding: 8px 6px; font-size: 11px; }
}
</style>
</head>
<body class="<?php echo $dark_mode ?? '' ? 'dark-mode' : ''; ?>">

<!-- TOP NAV -->
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

<!-- SIDEBAR -->
<?php include 'navigation.php'; ?>

<div class="sidebar-toggle-pill" id="sidebarToggle">◀</div>

<!-- MAIN CONTENT -->
<main class="main" id="mainContent">
    <div class="section-title">➕ Add Marks</div>

    <?php if ($message): ?>
        <div class="msg <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Filter: Course + Search -->
    <div class="filter-card">
        <form method="GET" class="filter-form">
            <select name="course">
                <option value="">All Courses</option>
                <?php 
                $courses_result->data_seek(0);
                while ($row = $courses_result->fetch_assoc()): 
                ?>
                    <option value="<?php echo htmlspecialchars($row['course_category']); ?>" 
                        <?php echo ($selected_course == $row['course_category']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($row['course_category']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <input type="text" name="search" placeholder="Search by ID, Name, or Email" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">🔍 Filter</button>
            <a href="add_marks.php" class="clear-btn" style="padding:12px 24px; background:rgba(255,255,255,0.1); border-radius:12px; color:var(--text); text-decoration:none; font-weight:700; white-space:nowrap;">Clear Filters</a>
        </form>
    </div>

    <!-- Student List -->
    <div class="table-card">
        <div style="overflow-x: auto;">
            <?php if (count($students) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($s['name']); ?></td>
                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                <td><?php echo htmlspecialchars($s['phone_number']); ?></td>
                                <td>
                                    <button class="add-btn" 
                                        data-student-id="<?php echo htmlspecialchars($s['student_id']); ?>"
                                        data-student-name="<?php echo htmlspecialchars($s['name']); ?>"
                                        onclick="openAddModal(this)">
                                        Add Marks
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color:var(--muted); padding:20px 0;">No students found matching your filters.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- ADD MARKS MODAL -->
<div class="modal" id="markModal">
    <div class="modal-content">
        <h3 id="modalTitle">Add Marks</h3>
        <form method="POST" id="markForm">
            <input type="hidden" name="student_id" id="student_id">
            <div style="background:rgba(0,0,0,0.2); padding:10px 14px; border-radius:10px; margin-bottom:16px;">
                <strong style="color:var(--accent);">Student:</strong> 
                <span id="studentNameDisplay" style="color:var(--text);"></span>
            </div>

            <label for="exam_name">Exam Name *</label>
            <input type="text" name="exam_name" id="exam_name" required placeholder="e.g. Midterm, Final, etc.">

            <label for="marks">Marks (out of 100) – 60% weight</label>
            <input type="number" name="marks" id="marks" step="0.01" min="0" max="100" placeholder="e.g. 85.5">

            <label for="project_marks">Project Marks (out of 100) – 30% weight</label>
            <input type="number" name="project_marks" id="project_marks" step="0.01" min="0" max="100" placeholder="e.g. 90">

            <label for="assignment_marks">Assignment Marks (out of 100) – 10% weight</label>
            <input type="number" name="assignment_marks" id="assignment_marks" step="0.01" min="0" max="100" placeholder="e.g. 88">

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" name="add_mark" class="btn-submit">Add Marks</button>
            </div>
        </form>
    </div>
</div>

<div class="footer">
    &copy; <?php echo date("Y"); ?> AR TECH SOLUTION — Freelancing Student Management System
</div>

<script>
// Sidebar toggles
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

// ---------- Modal Logic ----------
const modal = document.getElementById('markModal');
const form = document.getElementById('markForm');

function openAddModal(btn) {
    const studentId = btn.getAttribute('data-student-id');
    const studentName = btn.getAttribute('data-student-name');
    document.getElementById('student_id').value = studentId;
    document.getElementById('studentNameDisplay').textContent = studentName + ' (' + studentId + ')';
    // Reset form fields (keep student hidden)
    document.getElementById('exam_name').value = '';
    document.getElementById('marks').value = '';
    document.getElementById('project_marks').value = '';
    document.getElementById('assignment_marks').value = '';
    modal.classList.add('active');
}

function closeModal() {
    modal.classList.remove('active');
}

// Close modal on outside click
modal.addEventListener('click', function(e) {
    if (e.target === modal) closeModal();
});

// Also close with Cancel button (already handled by onclick)
</script>
</body>
</html>