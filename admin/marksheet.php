<?php
session_start();

// Admin session check
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}
require_once 'config.php';

// Get student_id from URL
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
if (empty($student_id)) {
    header("Location: marks.php");
    exit();
}

// Fetch student details
$student_stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$student_stmt->bind_param("s", $student_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
if ($student_result->num_rows === 0) {
    die("Student not found.");
}
$student = $student_result->fetch_assoc();
$student_stmt->close();

// Fetch all marks for this student
$marks_stmt = $conn->prepare("SELECT * FROM marks WHERE student_id = ? ORDER BY exam_name");
$marks_stmt->bind_param("s", $student_id);
$marks_stmt->execute();
$marks_result = $marks_stmt->get_result();

// Weighted calculation and grade mapping
function getGradeAndGP($weighted) {
    if ($weighted >= 90) return ['grade' => 'A+', 'gp' => 5];
    elseif ($weighted >= 80) return ['grade' => 'A-', 'gp' => 4];
    elseif ($weighted >= 70) return ['grade' => 'B', 'gp' => 3];
    elseif ($weighted >= 60) return ['grade' => 'C', 'gp' => 2];
    else return ['grade' => 'F', 'gp' => 0];
}

$subjects = [];
$total_weighted_sum = 0;
$total_gp_sum = 0;
$count = 0;

while ($row = $marks_result->fetch_assoc()) {
    $exam_marks = $row['marks'] ?? 0;
    $project_marks = $row['project_marks'] ?? 0;
    $assignment_marks = $row['assignment_marks'] ?? 0;
    // Weighted: exam 60%, project 30%, assignment 10%
    $weighted_total = ($exam_marks * 0.6) + ($project_marks * 0.3) + ($assignment_marks * 0.1);
    $grade_info = getGradeAndGP($weighted_total);
    
    $subjects[] = [
        'exam_name' => $row['exam_name'],
        'exam_marks' => $exam_marks,
        'assignment_marks' => $assignment_marks,
        'project_marks' => $project_marks,
        'weighted_total' => $weighted_total,
        'grade' => $grade_info['grade'],
        'gp' => $grade_info['gp']
    ];
    
    $total_weighted_sum += $weighted_total;
    $total_gp_sum += $grade_info['gp'];
    $count++;
}

$overall_average = ($count > 0) ? ($total_weighted_sum / $count) : 0;
$overall_gpa = ($count > 0) ? ($total_gp_sum / $count) : 0;
$final_grade_info = getGradeAndGP($overall_average);
$final_grade = $final_grade_info['grade'];
$status = ($overall_average < 60) ? 'Fail' : 'Pass';

// College details
$college_name = "RAJUK UTTARA MODEL COLLEGE";
$college_address = "SECTOR#6, UTTARA MODEL TOWN, DHAKA";
$college_website = "WWW.RAJUKCOLLEGE.EDU.BD";
$academic_year = "2024";
$class_name = $student['course_category'] ?? 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Academic Transcript — <?php echo htmlspecialchars($student['name']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<style>
    /* ===== STANDARD DARK THEME (same as other pages) ===== */
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

/* ===== TRANSCRIPT CARD (glass) ===== */
.transcript-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 30px;
    margin-bottom: 28px;
    color: var(--text);
}

/* ----- Inner transcript styling (dark theme) ----- */
.transcript-inner {
    background: rgba(255,255,255,0.05);
    border-radius: 12px;
    padding: 24px;
    border: 1px solid var(--glass-border);
}
.transcript-header {
    text-align: center;
    border-bottom: 2px solid var(--accent);
    padding-bottom: 12px;
    margin-bottom: 20px;
}
.transcript-header h1 {
    font-family: var(--mono);
    font-size: 26px;
    font-weight: 800;
    color: var(--accent);
    letter-spacing: 2px;
}
.transcript-header p {
    color: var(--muted);
    font-size: 14px;
    margin: 2px 0;
}

.student-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px 30px;
    margin-bottom: 20px;
    font-size: 14px;
    background: rgba(0,0,0,0.2);
    padding: 12px 16px;
    border-radius: 12px;
}
.student-info .label {
    color: var(--muted);
}
.student-info .value {
    font-weight: 600;
    color: var(--text);
}

.grade-legend {
    font-size: 12px;
    margin: 6px 0 12px;
    color: var(--muted);
}
.grade-legend span {
    display: inline-block;
    margin-right: 12px;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    margin-top: 8px;
}
th, td {
    padding: 8px 6px;
    text-align: center;
    border: 1px solid var(--glass-border);
}
th {
    background: rgba(0,0,0,0.4);
    color: var(--accent);
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
td {
    color: var(--text);
}
tr:hover td {
    background: rgba(255,255,255,0.03);
}

.total-row td {
    font-weight: 700;
    background: rgba(0,229,200,0.08);
    border-top: 2px solid var(--accent);
}

.final-result {
    margin-top: 16px;
    padding: 12px 18px;
    background: rgba(0,0,0,0.3);
    border-radius: 10px;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    border-left: 4px solid var(--accent);
}
.final-result .item {
    font-weight: 600;
    color: var(--text);
}
.final-result .item span {
    font-weight: 400;
    color: var(--muted);
}
.final-result .grade-big {
    font-size: 24px;
    font-weight: 800;
    color: var(--accent);
}

.signatures {
    margin-top: 20px;
    display: flex;
    justify-content: center;
    gap: 40px;
    font-size: 14px;
    font-weight: 600;
    color: var(--muted);
    padding-top: 12px;
    border-top: 1px solid var(--glass-border);
}
.signatures div {
    text-align: center;
}
.signatures .line {
    display: block;
    width: 140px;
    border-bottom: 1px solid var(--glass-border);
    margin: 6px auto;
}

.print-btn {
    background: linear-gradient(135deg, #3498db, #2980b9);
    border: none;
    color: #fff;
    padding: 10px 28px;
    border-radius: 30px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity .2s;
    font-size: 14px;
}
.print-btn:hover { opacity: .8; }

.back-link {
    display: inline-block;
    margin-top: 16px;
    color: var(--accent);
    text-decoration: none;
    font-weight: 600;
    transition: opacity .2s;
}
.back-link:hover { opacity: .7; }

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

/* ===== RESPONSIVE ===== */
@media (max-width: 700px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.mobile-open { transform: translateX(0); }
    .sidebar-toggle-pill { display: none; }
    .hamburger { display: block; }
    .main { margin-left: 0 !important; padding-left: 16px; padding-right: 16px; }
    .student-info { grid-template-columns: 1fr; }
    .final-result { flex-direction: column; gap: 8px; }
    .signatures { flex-direction: column; gap: 12px; }
    .transcript-inner { padding: 12px; }
}

/* ===== PRINT STYLES ===== */
@media print {
    /* Page setup – exact A4 with clean margins */
    @page {
        size: A4;
        margin: 12mm 10mm;
    }

    /* Hide all navigation, buttons, and sidebars */
    .topnav, .sidebar, .sidebar-toggle-pill, .footer, .no-print,
    .hamburger, .topnav-right, .brand-dot, .section-title::after,
    .section-title, .back-link, .print-btn {
        display: none !important;
    }

    /* Reset main container */
    .main {
        margin: 0 !important;
        padding: 0 !important;
        background: none !important;
        min-height: auto !important;
    }
    body {
        background: #fff !important;
        font-family: 'Times New Roman', Times, serif !important;
        padding: 0 !important;
    }
    body::before {
        display: none !important;
    }

    /* Transcript card – white, no glass, no borders */
    .transcript-card {
        background: #fff !important;
        backdrop-filter: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }
    .transcript-inner {
        background: #fff !important;
        border: none !important;
        padding: 0 !important;
        border-radius: 0 !important;
    }

    /* All text black on white */
    .transcript-header h1,
    .transcript-header p,
    .transcript-header h2,
    .student-info .label,
    .student-info .value,
    .grade-legend,
    .grade-legend span,
    table, th, td,
    .final-result, .final-result .item,
    .final-result .item span,
    .final-result .grade-big,
    .signatures, .signatures div,
    .signatures .line {
        color: #000 !important;
        border-color: #000 !important;
        background: transparent !important;
        background-color: transparent !important;
    }

    /* Table borders */
    th, td {
        border: 1px solid #000 !important;
    }
    th {
        background: #eee !important;
        color: #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .total-row td {
        background: #f5f5f5 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        border-top: 2px solid #000 !important;
    }

    /* Final result box */
    .final-result {
        border-left: 4px solid #000 !important;
        background: #f9f9f9 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Signatures line */
    .signatures .line {
        border-bottom: 1px solid #000 !important;
    }

    /* Ensure text contrast */
    .transcript-header {
        border-bottom: 2px solid #000 !important;
    }
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
    <div class="section-title">📄 Academic Transcript</div>

    <div class="transcript-card" id="printArea">
        <div class="transcript-inner">
            <!-- Header -->
            <div class="transcript-header">
                <h1><?php echo $college_name; ?></h1>
                <p><?php echo $college_address; ?></p>
                <p><?php echo $college_website; ?></p>
                <h2 style="margin-top:8px; font-size:20px; font-weight:700;">Academic Transcript</h2>
                <p style="margin-top:4px;"><strong>Academic Year: <?php echo $academic_year; ?></strong></p>
            </div>

            <!-- Student Info -->
            <div class="student-info">
                <div><span class="label">Name of Student:</span> <span class="value"><?php echo htmlspecialchars($student['name']); ?></span></div>
                <div><span class="label">Student ID:</span> <span class="value"><?php echo htmlspecialchars($student['student_id']); ?></span></div>
                <div><span class="label">Class:</span> <span class="value"><?php echo htmlspecialchars($class_name); ?></span></div>
                <div><span class="label">Email:</span> <span class="value"><?php echo htmlspecialchars($student['email']); ?></span></div>
                <div><span class="label">Phone:</span> <span class="value"><?php echo htmlspecialchars($student['phone_number']); ?></span></div>
            </div>

            <!-- Grade Legend -->
            <div class="grade-legend">
                <span><strong>A+</strong> (90-100)</span>
                <span><strong>A-</strong> (80-89)</span>
                <span><strong>B</strong> (70-79)</span>
                <span><strong>C</strong> (60-69)</span>
                <span><strong>F</strong> (0-59)</span>
            </div>

            <!-- Marks Table -->
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Exam (60%)</th>
                            <th>Project (30%)</th>
                            <th>Assignment (10%)</th>
                            <th>Weighted Total</th>
                            <th>Grade</th>
                            <th>GP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_total = 0;
                        $total_gp = 0;
                        foreach ($subjects as $subj):
                            $grand_total += $subj['weighted_total'];
                            $total_gp += $subj['gp'];
                        ?>
                        <tr>
                            <td style="text-align:left; font-weight:500;"><?php echo htmlspecialchars($subj['exam_name']); ?></td>
                            <td><?php echo number_format($subj['exam_marks'], 2); ?></td>
                            <td><?php echo number_format($subj['project_marks'], 2); ?></td>
                            <td><?php echo number_format($subj['assignment_marks'], 2); ?></td>
                            <td><strong><?php echo number_format($subj['weighted_total'], 2); ?></strong></td>
                            <td><strong><?php echo $subj['grade']; ?></strong></td>
                            <td><?php echo $subj['gp']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <!-- Summary rows -->
                        <tr class="total-row">
                            <td colspan="4" style="text-align:right;">Total (Sum of Weighted)</td>
                            <td colspan="3" style="font-size:15px;"><?php echo number_format($grand_total, 2); ?></td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="4" style="text-align:right;">Overall Average</td>
                            <td colspan="3" style="font-size:15px;"><?php echo number_format($overall_average, 2); ?>%</td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="4" style="text-align:right;">GPA</td>
                            <td colspan="3" style="font-size:15px;"><?php echo number_format($overall_gpa, 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Final Result -->
            <div class="final-result">
                <div class="item">📌 Status: <span><?php echo $status; ?></span></div>
                <div class="item">🏆 Final Grade: <span class="grade-big"><?php echo $final_grade; ?></span></div>
                <div class="item">📊 Overall Average: <span><?php echo number_format($overall_average, 2); ?>%</span></div>
            </div>

            <!-- Signatures -->
            <div class="signatures">
                <div>
                    <span class="line"></span>
                    Authorities
                </div>
            </div>
            <div style="text-align:right; font-size:12px; color:var(--muted); margin-top:8px;">
                Result published on: <?php echo date('d-m-Y'); ?>
            </div>
        </div>
    </div>

    <div class="no-print" style="display:flex; gap:16px; flex-wrap:wrap; margin-top:10px;">
        <button class="print-btn" onclick="window.print()">🖨️ Print / PDF</button>
        <a href="marks.php" class="back-link">← Back to Marks List</a>
    </div>
</main>

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