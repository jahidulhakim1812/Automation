<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

/* ── Filter Logic ─────────────────────────────── */
$filterType = $_GET['filter'] ?? 'all';
$customFrom = $_GET['from'] ?? '';
$customTo   = $_GET['to']   ?? '';
$filterYear = $_GET['year'] ?? date('Y');
$filterMonth= $_GET['month'] ?? date('m');

function buildDateWhere($filterType, $filterYear, $filterMonth, $customFrom, $customTo, $col = 'created_at') {
    switch ($filterType) {
        case 'monthly':
            return "WHERE YEAR($col) = '$filterYear' AND MONTH($col) = '$filterMonth'";
        case 'yearly':
            return "WHERE YEAR($col) = '$filterYear'";
        case 'custom':
            if ($customFrom && $customTo)
                return "WHERE DATE($col) BETWEEN '$customFrom' AND '$customTo'";
            return "";
        default: return "";
    }
}

$studWhere    = buildDateWhere($filterType, $filterYear, $filterMonth, $customFrom, $customTo, 'created_at');
$invWhere     = buildDateWhere($filterType, $filterYear, $filterMonth, $customFrom, $customTo, 'invoice_date');
$expenseWhere = buildDateWhere($filterType, $filterYear, $filterMonth, $customFrom, $customTo, 'expense_date');

/* ── Categories (now includes Web Development) ── */
$categories = [
    'Graphic Design',
    'Video Editing',
    'Social Media Marketing',
    'Digital Marketing',
    'Office Application',
    'Web Development'          // <-- NEW category
];

$counts = [];
foreach ($categories as $cat) {
    $w = !empty($studWhere) ? str_replace('WHERE', "WHERE course_category = '".mysqli_real_escape_string($conn,$cat)."' AND", $studWhere) : "WHERE course_category = '".mysqli_real_escape_string($conn,$cat)."'";
    $r = $conn->query("SELECT COUNT(*) AS total FROM students $w");
    $counts[$cat] = (int)($r->fetch_assoc()['total'] ?? 0);
}

/* ── Fee Totals (students) ───────────────────── */
$r = $conn->query("SELECT SUM(course_fee) AS cf, SUM(paid_fee) AS pf FROM students $studWhere");
$row = $r->fetch_assoc();
$totalCourseFee = (float)($row['cf'] ?? 0);
$totalPaidFee   = (float)($row['pf'] ?? 0);
$totalDueFee    = $totalCourseFee - $totalPaidFee;

/* ── Invoice / Service Totals ────────────────── */
$r2 = $conn->query("SELECT SUM(total) AS inv_total, SUM(paid_amount) AS inv_paid FROM invoices_new $invWhere");
$row2 = $r2 ? $r2->fetch_assoc() : [];
$invTotal = (float)($row2['inv_total'] ?? 0);
$invPaid  = (float)($row2['inv_paid'] ?? 0);
$invDue   = $invTotal - $invPaid;

/* ── Combined Income & Due ───────────────────── */
$totalIncome = $totalPaidFee + $invPaid;
$totalDue    = $totalDueFee + $invDue;

/* ── Total Expenses ───────────────────────────── */
$rExp = $conn->query("SELECT SUM(amount) AS total_expense FROM expenses $expenseWhere");
$totalExpense = (float)($rExp->fetch_assoc()['total_expense'] ?? 0);

/* ── Net Profit ───────────────────────────────── */
$netProfit = $totalIncome - $totalExpense;

/* ── Total Students ───────────────────────────── */
$r3 = $conn->query("SELECT COUNT(*) AS total FROM students $studWhere");
$totalStudents = (int)($r3->fetch_assoc()['total'] ?? 0);

/* ── Previous Month Students ─────────────────── */
$prevMonth = date('m', strtotime('-1 month'));
$prevYear  = date('Y', strtotime('-1 month'));
$r4 = $conn->query("SELECT COUNT(*) AS total FROM students WHERE YEAR(created_at)='$prevYear' AND MONTH(created_at)='$prevMonth'");
$prevMonthStudents = (int)($r4->fetch_assoc()['total'] ?? 0);
$currMonthStudents_r = $conn->query("SELECT COUNT(*) AS total FROM students WHERE YEAR(created_at)='".date('Y')."' AND MONTH(created_at)='".date('m')."'");
$currMonthStudents = (int)($currMonthStudents_r->fetch_assoc()['total'] ?? 0);
$growthRate = $prevMonthStudents > 0 ? round((($currMonthStudents - $prevMonthStudents) / $prevMonthStudents) * 100, 1) : ($currMonthStudents > 0 ? 100 : 0);

/* ── Monthly Enrollment (last 12 months) ─────── */
$monthlyLabels = [];
$monthlyData   = [];
for ($i = 11; $i >= 0; $i--) {
    $ts = strtotime("-$i months");
    $m  = date('m', $ts);
    $y  = date('Y', $ts);
    $monthlyLabels[] = date('M Y', $ts);
    $r5 = $conn->query("SELECT COUNT(*) AS total FROM students WHERE YEAR(created_at)='$y' AND MONTH(created_at)='$m'");
    $monthlyData[] = (int)($r5->fetch_assoc()['total'] ?? 0);
}

/* ── Monthly Revenue (last 12 months) ────────── */
$revenueData = [];
for ($i = 11; $i >= 0; $i--) {
    $ts = strtotime("-$i months");
    $m  = date('m', $ts);
    $y  = date('Y', $ts);
    $r6 = $conn->query("SELECT SUM(paid_fee) AS total FROM students WHERE YEAR(created_at)='$y' AND MONTH(created_at)='$m'");
    $sv = $conn->query("SELECT SUM(paid_amount) AS total FROM invoices_new WHERE YEAR(invoice_date)='$y' AND MONTH(invoice_date)='$m'");
    $revenueData[] = (float)($r6->fetch_assoc()['total'] ?? 0) + (float)($sv->fetch_assoc()['total'] ?? 0);
}

/* ── Status distribution (using course_status column) ── */
$statusCounts = ['completed' => 0, 'ongoing' => 0, 'incomplete' => 0];
foreach (['completed', 'ongoing', 'incomplete'] as $s) {
    $whereStatus = empty($studWhere) ? "WHERE course_status='$s'" : "$studWhere AND course_status='$s'";
    $r7 = $conn->query("SELECT COUNT(*) AS total FROM students $whereStatus");
    $statusCounts[$s] = (int)($r7 ? $r7->fetch_assoc()['total'] : 0);
}

/* ── Invoice Status ──────────────────────────── */
$invStatusCounts = [];
foreach (['paid','partial','unpaid'] as $s) {
    $whereInvStatus = empty($invWhere) ? "WHERE status='$s'" : "$invWhere AND status='$s'";
    $r8 = $conn->query("SELECT COUNT(*) AS total FROM invoices_new $whereInvStatus");
    $invStatusCounts[$s] = (int)($r8 ? $r8->fetch_assoc()['total'] : 0);
}

/* ── Top 5 Invoice Customers ─────────────────── */
$topCustomers = [];
$r9 = $conn->query("SELECT c.name, SUM(i.total) AS total FROM invoices_new i JOIN customers c ON i.customer_id=c.id $invWhere GROUP BY c.id ORDER BY total DESC LIMIT 5");
if ($r9) while ($row9 = $r9->fetch_assoc()) $topCustomers[] = $row9;

$conn->close();

/* ── JSON for charts ─────────────────────────── */
$monthlyLabelsJson  = json_encode($monthlyLabels);
$monthlyDataJson    = json_encode($monthlyData);
$revenueDataJson    = json_encode($revenueData);
$categoryLabels     = json_encode(array_keys($counts));
$categoryData       = json_encode(array_values($counts));
$statusLabels       = json_encode(['Completed','Ongoing','Incomplete']);
$statusDataJson     = json_encode(array_values($statusCounts));
$invStatusLabels    = json_encode(['Paid','Partial','Unpaid']);
$invStatusDataJson  = json_encode(array_values($invStatusCounts));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Freelancing SMS</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
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

html { scroll-behavior: smooth; }

body::before {
    content: '';
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg,rgba(8,10,30,0.88) 0%,rgba(15,20,50,0.78) 50%,rgba(5,15,35,0.85) 100%);
    z-index: 0;
    pointer-events: none;
}

/* ── TOP NAV ─────────────────────────────────── */
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

/* ── SIDEBAR ─────────────────────────────────── */
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

/* ── TOGGLE ARROW ────────────────────────────── */
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

/* ── MAIN CONTENT ────────────────────────────── */
.main {
    margin-left: var(--sidebar-w);
    padding: calc(var(--nav-h) + 24px) 24px 80px;
    position: relative; z-index: 1;
    transition: margin-left .3s cubic-bezier(.4,0,.2,1);
    min-height: 100vh;
}
.main.collapsed { margin-left: 0; }

/* ── FILTER BAR ──────────────────────────────── */
.filter-bar {
    background: var(--glass);
    backdrop-filter: blur(14px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 16px 20px;
    display: flex; align-items: center; flex-wrap: wrap; gap: 12px;
    margin-bottom: 24px;
}
.filter-bar label { font-size: 12px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.filter-btn {
    padding: 7px 18px; border-radius: 30px; border: 1px solid var(--glass-border);
    background: transparent; color: var(--text); font-family: var(--sans);
    font-size: 13px; font-weight: 600; cursor: pointer; transition: all .2s;
}
.filter-btn:hover, .filter-btn.active { background: var(--accent); color: #000; border-color: var(--accent); }
.filter-select, .filter-input {
    padding: 7px 12px; border-radius: 8px;
    border: 1px solid var(--glass-border);
    background: rgba(255,255,255,0.06); color: var(--text);
    font-family: var(--sans); font-size: 13px; outline: none;
}
.filter-select option { background: #1a2035; }
.filter-apply {
    padding: 7px 22px; border-radius: 30px;
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    color: #000; font-weight: 700; font-size: 13px; border: none; cursor: pointer;
    transition: opacity .2s;
}
.filter-apply:hover { opacity: .85; }
.filter-group { display: none; align-items: center; gap: 8px; flex-wrap: wrap; }
.filter-group.visible { display: flex; }

/* ── SECTION TITLE ───────────────────────────── */
.section-title {
    font-family: var(--mono); font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 2px; color: var(--accent);
    margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
}
.section-title::after { content: ''; flex: 1; height: 1px; background: var(--glass-border); }

/* ── KPI CARDS ───────────────────────────────── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px; margin-bottom: 28px;
}
.kpi-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 20px;
    cursor: pointer;
    transition: transform .2s, background .2s, box-shadow .2s;
    position: relative; overflow: hidden;
    animation: fadeInUp .5s ease both;
}
@keyframes fadeInUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
.kpi-card:nth-child(1){animation-delay:.05s} .kpi-card:nth-child(2){animation-delay:.1s}
.kpi-card:nth-child(3){animation-delay:.15s} .kpi-card:nth-child(4){animation-delay:.2s}
.kpi-card:nth-child(5){animation-delay:.25s} .kpi-card:nth-child(6){animation-delay:.3s}
.kpi-card:nth-child(7){animation-delay:.35s} .kpi-card:nth-child(8){animation-delay:.4s}
.kpi-card:nth-child(9){animation-delay:.45s} .kpi-card:nth-child(10){animation-delay:.5s}
.kpi-card:hover { transform: translateY(-4px); background: var(--glass-hover); box-shadow: 0 12px 40px rgba(0,0,0,0.4); }
.kpi-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: var(--card-accent, var(--accent));
}
.kpi-icon { font-size: 26px; margin-bottom: 10px; }
.kpi-label { font-size: 11.5px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
.kpi-value { font-family: var(--mono); font-size: 24px; font-weight: 700; color: #fff; line-height: 1; }
.kpi-sub { font-size: 11px; color: var(--muted); margin-top: 6px; }
.kpi-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 700;
    margin-top: 8px;
}
.badge-up { background: rgba(6,214,160,0.15); color: #06d6a0; }
.badge-down { background: rgba(255,107,107,0.15); color: #ff6b6b; }
.badge-neu { background: rgba(200,210,230,0.1); color: var(--muted); }
.profit-positive { color: #06d6a0; }
.profit-negative { color: #ff6b6b; }

/* Category Cards */
.cat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 14px; margin-bottom: 28px;
}
.cat-card {
    background: var(--glass);
    backdrop-filter: blur(14px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 18px 16px;
    cursor: pointer; text-align: center;
    transition: transform .2s, background .2s, box-shadow .2s;
    animation: fadeInUp .5s ease both;
    text-decoration: none; color: inherit;
    position: relative; overflow: hidden;
}
.cat-card:hover { transform: translateY(-4px); background: var(--glass-hover); box-shadow: var(--shadow); }
.cat-emoji { font-size: 28px; margin-bottom: 8px; }
.cat-name { font-size: 12.5px; font-weight: 600; color: var(--text); margin-bottom: 6px; line-height: 1.3; }
.cat-count { font-family: var(--mono); font-size: 28px; font-weight: 700; color: #fff; }
.cat-label { font-size: 11px; color: var(--muted); margin-top: 2px; }

/* ── CHARTS ──────────────────────────────────── */
.charts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px; margin-bottom: 28px;
}
.chart-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 22px;
    animation: fadeInUp .6s ease both;
}
.chart-card.full { grid-column: 1 / -1; }
.chart-title { font-family: var(--mono); font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 16px; }
.chart-wrap { position: relative; width: 100%; height: 240px; }

/* ── PERF METRICS ────────────────────────────── */
.perf-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px; margin-bottom: 28px;
}
.perf-card {
    background: var(--glass);
    backdrop-filter: blur(14px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 20px;
    animation: fadeInUp .5s ease both;
}
.perf-label { font-size: 11.5px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 10px; }
.perf-big { font-family: var(--mono); font-size: 32px; font-weight: 800; color: #fff; }
.perf-row { display: flex; justify-content: space-between; align-items: flex-end; }
.perf-compare { font-size: 12px; color: var(--muted); text-align: right; }

.progress-bar-wrap { height: 6px; background: rgba(255,255,255,0.08); border-radius: 4px; margin-top: 12px; overflow: hidden; }
.progress-bar-fill { height: 100%; border-radius: 4px; background: linear-gradient(90deg, var(--accent), var(--accent2)); transition: width 1s ease; }

/* ── MODAL ───────────────────────────────────── */
.modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 2000;
    background: rgba(0,0,0,0.65); backdrop-filter: blur(6px);
    align-items: center; justify-content: center;
}
.modal-overlay.open { display: flex; }
.modal-box {
    background: rgba(15,20,45,0.97);
    border: 1px solid var(--glass-border);
    border-radius: 20px; padding: 30px; width: 90%; max-width: 700px;
    max-height: 85vh; overflow-y: auto; position: relative;
    animation: modalIn .3s ease;
}
@keyframes modalIn { from{opacity:0;transform:scale(.94)} to{opacity:1;transform:scale(1)} }
.modal-close {
    position: absolute; top: 14px; right: 18px;
    background: none; border: none; color: var(--muted);
    font-size: 22px; cursor: pointer; transition: color .2s;
}
.modal-close:hover { color: var(--accent3); }
.modal-title { font-family: var(--mono); font-size: 18px; font-weight: 700; color: var(--accent); margin-bottom: 20px; }
.modal-table { width: 100%; border-collapse: collapse; }
.modal-table th { font-size: 12px; text-transform: uppercase; letter-spacing: .5px; color: var(--muted); padding: 8px 12px; text-align: left; border-bottom: 1px solid var(--glass-border); }
.modal-table td { padding: 10px 12px; font-size: 13.5px; border-bottom: 1px solid rgba(255,255,255,0.05); }
.modal-table tr:last-child td { border-bottom: none; }
.modal-stat { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.06); }
.modal-stat:last-child { border-bottom: none; }
.modal-stat-label { font-size: 14px; color: var(--muted); }
.modal-stat-value { font-family: var(--mono); font-size: 18px; font-weight: 700; color: #fff; }

/* ── FOOTER ──────────────────────────────────── */
.footer {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: rgba(6,8,20,0.9); backdrop-filter: blur(10px);
    border-top: 1px solid var(--glass-border);
    text-align: center; padding: 12px;
    font-size: 12.5px; color: var(--muted); z-index: 900;
}

/* ── RESPONSIVE ──────────────────────────────── */
@media (max-width: 900px) {
    .charts-grid { grid-template-columns: 1fr; }
    .chart-card.full { grid-column: 1; }
}
@media (max-width: 700px) {
    :root { --sidebar-w: 230px; }
    .sidebar { transform: translateX(-100%); }
    .sidebar.mobile-open { transform: translateX(0); }
    .sidebar-toggle-pill { display: none; }
    .hamburger { display: block; }
    .main { margin-left: 0 !important; padding-left: 16px; padding-right: 16px; }
    .kpi-grid { grid-template-columns: repeat(2,1fr); }
    .cat-grid { grid-template-columns: repeat(2,1fr); }
    .perf-grid { grid-template-columns: 1fr; }
}
@media (max-width: 420px) {
    .kpi-grid { grid-template-columns: 1fr; }
    .cat-grid { grid-template-columns: 1fr; }
    .filter-bar { flex-direction: column; align-items: flex-start; }
}
</style>
</head>
<body class="<?php echo $dark_mode ? 'dark-mode' : ''; ?>">

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

<!-- MAIN -->
<main class="main" id="mainContent">

    <!-- FILTER BAR -->
    <form method="GET" action="">
        <div class="filter-bar">
            <label>Filter:</label>
            <?php foreach(['all'=>'All Time','monthly'=>'Monthly','yearly'=>'Yearly','custom'=>'Custom'] as $k=>$v): ?>
            <button type="submit" name="filter" value="<?= $k ?>" class="filter-btn<?= $filterType===$k?' active':'' ?>"><?= $v ?></button>
            <?php endforeach; ?>

            <div class="filter-group<?= in_array($filterType,['monthly'])?' visible':'' ?>" id="fg-monthly">
                <select name="month" class="filter-select">
                    <?php for($m=1;$m<=12;$m++): ?>
                    <option value="<?= str_pad($m,2,'0',STR_PAD_LEFT) ?>"<?= $filterMonth==str_pad($m,2,'0',STR_PAD_LEFT)?' selected':'' ?>><?= date('F',mktime(0,0,0,$m,1)) ?></option>
                    <?php endfor; ?>
                </select>
                <select name="year" class="filter-select">
                    <?php for($y=date('Y');$y>=2020;$y--): ?>
                    <option value="<?= $y ?>"<?= $filterYear==$y?' selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="filter-group<?= $filterType==='yearly'?' visible':'' ?>" id="fg-yearly">
                <select name="year" class="filter-select">
                    <?php for($y=date('Y');$y>=2020;$y--): ?>
                    <option value="<?= $y ?>"<?= $filterYear==$y?' selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="filter-group<?= $filterType==='custom'?' visible':'' ?>" id="fg-custom">
                <input type="date" name="from" value="<?= htmlspecialchars($customFrom) ?>" class="filter-input">
                <span style="color:var(--muted)">→</span>
                <input type="date" name="to" value="<?= htmlspecialchars($customTo) ?>" class="filter-input">
                <button type="submit" class="filter-apply">Apply</button>
            </div>
        </div>
    </form>

    <!-- KPI CARDS -->
    <div class="section-title">📌 Key Metrics</div>
    <div class="kpi-grid">
        <div class="kpi-card" style="--card-accent:#00e5c8" onclick="openModal('incomeModal')">
            <div class="kpi-icon">💰</div>
            <div class="kpi-label">Total Income</div>
            <div class="kpi-value">৳<?= number_format($totalIncome,0) ?></div>
            <div class="kpi-sub">Student + Service Revenue</div>
        </div>
        <div class="kpi-card" style="--card-accent:#ff6b6b" onclick="openModal('dueModal')">
            <div class="kpi-icon">⏳</div>
            <div class="kpi-label">Total Due</div>
            <div class="kpi-value">৳<?= number_format($totalDue,0) ?></div>
            <div class="kpi-sub">Student + Service Due</div>
        </div>
        <div class="kpi-card" style="--card-accent:#7b5ea7">
            <div class="kpi-icon">🎓</div>
            <div class="kpi-label">Total Students</div>
            <div class="kpi-value"><?= $totalStudents ?></div>
            <div class="kpi-sub">Enrolled</div>
            <?php if($growthRate>0): ?>
            <span class="kpi-badge badge-up">▲ <?= $growthRate ?>% vs last month</span>
            <?php elseif($growthRate<0): ?>
            <span class="kpi-badge badge-down">▼ <?= abs($growthRate) ?>% vs last month</span>
            <?php else: ?>
            <span class="kpi-badge badge-neu">— No change</span>
            <?php endif; ?>
        </div>
        <div class="kpi-card" style="--card-accent:#ffd166">
            <div class="kpi-icon">📋</div>
            <div class="kpi-label">Course Fees Total</div>
            <div class="kpi-value">৳<?= number_format($totalCourseFee,0) ?></div>
            <div class="kpi-sub">Total enrolled fees</div>
        </div>
        <div class="kpi-card" style="--card-accent:#06d6a0">
            <div class="kpi-icon">✅</div>
            <div class="kpi-label">Paid (Courses)</div>
            <div class="kpi-value">৳<?= number_format($totalPaidFee,0) ?></div>
            <div class="kpi-sub">Collected from students</div>
        </div>
        <div class="kpi-card" style="--card-accent:#e17055">
            <div class="kpi-icon">🧾</div>
            <div class="kpi-label">Invoice Revenue</div>
            <div class="kpi-value">৳<?= number_format($invPaid,0) ?></div>
            <div class="kpi-sub">Collected via invoices</div>
        </div>
        <div class="kpi-card" style="--card-accent:#74b9ff">
            <div class="kpi-icon">📅</div>
            <div class="kpi-label">This Month</div>
            <div class="kpi-value"><?= $currMonthStudents ?></div>
            <div class="kpi-sub">New enrollments</div>
        </div>
        <div class="kpi-card" style="--card-accent:#a29bfe">
            <div class="kpi-icon">📆</div>
            <div class="kpi-label">Last Month</div>
            <div class="kpi-value"><?= $prevMonthStudents ?></div>
            <div class="kpi-sub">New enrollments</div>
        </div>
        <!-- Total Expenses -->
        <div class="kpi-card" style="--card-accent:#e74c3c">
            <div class="kpi-icon">💸</div>
            <div class="kpi-label">Total Expenses</div>
            <div class="kpi-value">৳<?= number_format($totalExpense,0) ?></div>
            <div class="kpi-sub">All expenses (filtered)</div>
        </div>
        <!-- Net Profit -->
        <div class="kpi-card" style="--card-accent:#f39c12">
            <div class="kpi-icon">📈</div>
            <div class="kpi-label">Net Profit</div>
            <div class="kpi-value <?= $netProfit >= 0 ? 'profit-positive' : 'profit-negative' ?>">৳<?= number_format($netProfit,0) ?></div>
            <div class="kpi-sub">Income - Expenses</div>
        </div>
    </div>

    <!-- PERFORMANCE METRICS -->
    <div class="section-title">📊 Performance Metrics</div>
    <div class="perf-grid">
        <div class="perf-card">
            <div class="perf-label">Student Growth Rate</div>
            <div class="perf-row">
                <div class="perf-big" style="color:<?= $growthRate>=0?'#06d6a0':'#ff6b6b' ?>"><?= ($growthRate>=0?'+':'').$growthRate ?>%</div>
                <div class="perf-compare">
                    <div style="color:#fff"><?= $currMonthStudents ?> this month</div>
                    <div><?= $prevMonthStudents ?> last month</div>
                </div>
            </div>
            <?php $pct = $prevMonthStudents > 0 ? min(100, round(($currMonthStudents/$prevMonthStudents)*100)) : 100; ?>
            <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $pct ?>%"></div></div>
        </div>
        <div class="perf-card">
            <div class="perf-label">Fee Collection Rate</div>
            <?php $collRate = $totalCourseFee > 0 ? round(($totalPaidFee/$totalCourseFee)*100,1) : 0; ?>
            <div class="perf-row">
                <div class="perf-big" style="color:#00e5c8"><?= $collRate ?>%</div>
                <div class="perf-compare">
                    <div style="color:#fff">৳<?= number_format($totalPaidFee,0) ?> collected</div>
                    <div>of ৳<?= number_format($totalCourseFee,0) ?></div>
                </div>
            </div>
            <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $collRate ?>%;background:linear-gradient(90deg,#00e5c8,#06d6a0)"></div></div>
        </div>
        <div class="perf-card">
            <div class="perf-label">Course Completion Rate</div>
            <?php $compRate = $totalStudents > 0 ? round(($statusCounts['completed']/$totalStudents)*100,1) : 0; ?>
            <div class="perf-row">
                <div class="perf-big" style="color:#ffd166"><?= $compRate ?>%</div>
                <div class="perf-compare">
                    <div style="color:#fff"><?= $statusCounts['completed'] ?> completed</div>
                    <div>of <?= $totalStudents ?> total</div>
                </div>
            </div>
            <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $compRate ?>%;background:linear-gradient(90deg,#ffd166,#fdcb6e)"></div></div>
        </div>
        <div class="perf-card">
            <div class="perf-label">Invoice Payment Rate</div>
            <?php $invTotal_all = $invTotal > 0 ? round(($invPaid/$invTotal)*100,1) : 0; ?>
            <div class="perf-row">
                <div class="perf-big" style="color:#7b5ea7"><?= $invTotal_all ?>%</div>
                <div class="perf-compare">
                    <div style="color:#fff">৳<?= number_format($invPaid,0) ?> paid</div>
                    <div>of ৳<?= number_format($invTotal,0) ?></div>
                </div>
            </div>
            <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $invTotal_all ?>%;background:linear-gradient(90deg,#7b5ea7,#a29bfe)"></div></div>
        </div>
    </div>

    <!-- CATEGORY CARDS -->
    <div class="section-title">🎓 Enrollment by Category</div>
    <div class="cat-grid">
        <?php
        $catEmojis = [
            'Graphic Design'        => '🎨',
            'Video Editing'         => '🎬',
            'Social Media Marketing'=> '📱',
            'Digital Marketing'     => '📈',
            'Office Application'    => '💼',
            'Web Development'       => '🌐'   // <-- new emoji for Web Development
        ];
        $catColors = [
            '#00e5c8',
            '#7b5ea7',
            '#ff6b6b',
            '#ffd166',
            '#06d6a0',
            '#3498db'               // <-- new color for Web Development
        ];
        $i=0;
        foreach($counts as $cat=>$count):
        ?>
        <a href="course_details.php?category=<?= urlencode($cat) ?>" class="cat-card" style="animation-delay:<?= $i*0.07 ?>s">
            <div style="position:absolute;top:0;left:0;right:0;height:3px;background:<?= $catColors[$i] ?>"></div>
            <div class="cat-emoji"><?= $catEmojis[$cat] ?? '📚' ?></div>
            <div class="cat-name"><?= htmlspecialchars($cat) ?></div>
            <div class="cat-count"><?= $count ?></div>
            <div class="cat-label">students</div>
        </a>
        <?php $i++; endforeach; ?>
    </div>

    <!-- CHARTS -->
    <div class="section-title">📈 Analytics</div>
    <div class="charts-grid">
        <div class="chart-card full">
            <div class="chart-title">📉 Monthly Enrollment — Last 12 Months</div>
            <div class="chart-wrap"><canvas id="lineChart"></canvas></div>
        </div>
        <div class="chart-card full">
            <div class="chart-title">💹 Monthly Revenue — Last 12 Months</div>
            <div class="chart-wrap"><canvas id="revenueChart"></canvas></div>
        </div>
        <div class="chart-card">
            <div class="chart-title">🎓 Enrollment by Category</div>
            <div class="chart-wrap"><canvas id="barChart"></canvas></div>
        </div>
        <div class="chart-card">
            <div class="chart-title">📊 Student Status Distribution</div>
            <div class="chart-wrap"><canvas id="pieChart"></canvas></div>
        </div>
        <div class="chart-card">
            <div class="chart-title">🧾 Invoice Status Breakdown</div>
            <div class="chart-wrap"><canvas id="doughnutChart"></canvas></div>
        </div>
        <?php if(!empty($topCustomers)): ?>
        <div class="chart-card">
            <div class="chart-title">🏆 Top Customers by Invoice Value</div>
            <div class="chart-wrap"><canvas id="topCustChart"></canvas></div>
        </div>
        <?php endif; ?>
    </div>

</main>

<!-- INCOME MODAL -->
<div class="modal-overlay" id="incomeModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('incomeModal')">✕</button>
        <div class="modal-title">💰 Total Income Breakdown</div>
        <div class="modal-stat">
            <span class="modal-stat-label">🎓 Student Paid Fees</span>
            <span class="modal-stat-value" style="color:#06d6a0">৳<?= number_format($totalPaidFee,2) ?></span>
        </div>
        <div class="modal-stat">
            <span class="modal-stat-label">🧾 Invoice / Service Revenue</span>
            <span class="modal-stat-value" style="color:#00e5c8">৳<?= number_format($invPaid,2) ?></span>
        </div>
        <div class="modal-stat" style="border-top:1px solid var(--accent);padding-top:16px;margin-top:4px">
            <span class="modal-stat-label" style="color:#fff;font-weight:700;font-size:15px">Grand Total Income</span>
            <span class="modal-stat-value" style="color:var(--accent);font-size:24px">৳<?= number_format($totalIncome,2) ?></span>
        </div>
        <div style="margin-top:20px">
            <div class="chart-wrap" style="height:200px"><canvas id="incomeModalChart"></canvas></div>
        </div>
    </div>
</div>

<!-- DUE MODAL -->
<div class="modal-overlay" id="dueModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('dueModal')">✕</button>
        <div class="modal-title">⏳ Total Due Breakdown</div>
        <div class="modal-stat">
            <span class="modal-stat-label">🎓 Student Course Due</span>
            <span class="modal-stat-value" style="color:#ffd166">৳<?= number_format($totalDueFee,2) ?></span>
        </div>
        <div class="modal-stat">
            <span class="modal-stat-label">🧾 Invoice / Service Due</span>
            <span class="modal-stat-value" style="color:#ff6b6b">৳<?= number_format($invDue,2) ?></span>
        </div>
        <div class="modal-stat" style="border-top:1px solid var(--accent3);padding-top:16px;margin-top:4px">
            <span class="modal-stat-label" style="color:#fff;font-weight:700;font-size:15px">Grand Total Due</span>
            <span class="modal-stat-value" style="color:var(--accent3);font-size:24px">৳<?= number_format($totalDue,2) ?></span>
        </div>
        <div style="margin-top:20px">
            <div class="chart-wrap" style="height:200px"><canvas id="dueModalChart"></canvas></div>
        </div>
    </div>
</div>

<div class="footer">&copy; <?= date("Y") ?> AR TECH SOLUTION — Freelancing Student Management System</div>

<script>
/* ── DATA FROM PHP ─────── */
const monthlyLabels  = <?= $monthlyLabelsJson ?>;
const monthlyData    = <?= $monthlyDataJson ?>;
const revenueData    = <?= $revenueDataJson ?>;
const categoryLabels = <?= $categoryLabels ?>;
const categoryData   = <?= $categoryData ?>;
const statusLabels   = <?= $statusLabels ?>;
const statusData     = <?= $statusDataJson ?>;
const invStatusLabels= <?= $invStatusLabels ?>;
const invStatusData  = <?= $invStatusDataJson ?>;
<?php if(!empty($topCustomers)): ?>
const topCustLabels  = <?= json_encode(array_column($topCustomers,'name')) ?>;
const topCustData    = <?= json_encode(array_map(fn($r)=>(float)$r['total'],$topCustomers)) ?>;
<?php endif; ?>

const ACCENT  = '#00e5c8';
const PURPLE  = '#7b5ea7';
const RED     = '#ff6b6b';
const YELLOW  = '#ffd166';
const GREEN   = '#06d6a0';
const BLUE    = '#74b9ff';
const ORANGE  = '#e17055';

Chart.defaults.color = 'rgba(200,210,230,0.6)';
Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
Chart.defaults.font.size = 12;

const gridColor = 'rgba(255,255,255,0.06)';

/* LINE CHART */
new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
        labels: monthlyLabels,
        datasets: [{
            label: 'Students Enrolled',
            data: monthlyData,
            borderColor: ACCENT, backgroundColor: 'rgba(0,229,200,0.08)',
            borderWidth: 2.5, pointBackgroundColor: ACCENT, pointRadius: 4,
            tension: 0.4, fill: true
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(10,15,35,0.95)', borderColor: ACCENT, borderWidth: 1 } },
        scales: {
            x: { grid: { color: gridColor }, ticks: { maxRotation: 45 } },
            y: { grid: { color: gridColor }, beginAtZero: true }
        }
    }
});

/* REVENUE LINE CHART */
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: monthlyLabels,
        datasets: [{
            label: 'Revenue (৳)',
            data: revenueData,
            borderColor: YELLOW, backgroundColor: 'rgba(255,209,102,0.07)',
            borderWidth: 2.5, pointBackgroundColor: YELLOW, pointRadius: 4,
            tension: 0.4, fill: true
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(10,15,35,0.95)', borderColor: YELLOW, borderWidth: 1, callbacks: { label: ctx => '৳ ' + ctx.parsed.y.toLocaleString() } } },
        scales: {
            x: { grid: { color: gridColor }, ticks: { maxRotation: 45 } },
            y: { grid: { color: gridColor }, beginAtZero: true, ticks: { callback: v => '৳'+v.toLocaleString() } }
        }
    }
});

/* BAR CHART — now includes Web Development color (BLUE) */
new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: categoryLabels,
        datasets: [{ label: 'Students', data: categoryData, backgroundColor: [ACCENT,PURPLE,RED,YELLOW,GREEN,BLUE], borderRadius: 8, borderSkipped: false }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(10,15,35,0.95)' } },
        scales: {
            x: { grid: { display: false } },
            y: { grid: { color: gridColor }, beginAtZero: true }
        }
    }
});

/* PIE CHART */
new Chart(document.getElementById('pieChart'), {
    type: 'pie',
    data: {
        labels: statusLabels,
        datasets: [{ data: statusData, backgroundColor: [GREEN, BLUE, RED], borderWidth: 0, hoverOffset: 8 }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } }, tooltip: { backgroundColor: 'rgba(10,15,35,0.95)' } }
    }
});

/* DOUGHNUT CHART */
new Chart(document.getElementById('doughnutChart'), {
    type: 'doughnut',
    data: {
        labels: invStatusLabels,
        datasets: [{ data: invStatusData, backgroundColor: [GREEN, YELLOW, RED], borderWidth: 0, hoverOffset: 8, cutout: '65%' }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } }, tooltip: { backgroundColor: 'rgba(10,15,35,0.95)' } }
    }
});

/* TOP CUSTOMERS CHART */
<?php if(!empty($topCustomers)): ?>
new Chart(document.getElementById('topCustChart'), {
    type: 'bar',
    data: {
        labels: topCustLabels,
        datasets: [{ label: 'Revenue (৳)', data: topCustData, backgroundColor: [PURPLE,'#9b7fd4','#c4a8f0',BLUE,'#a8d8ff'], borderRadius: 8, borderSkipped: false }]
    },
    options: {
        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(10,15,35,0.95)', callbacks: { label: ctx => '৳ '+ctx.parsed.x.toLocaleString() } } },
        scales: {
            x: { grid: { color: gridColor }, ticks: { callback: v => '৳'+v.toLocaleString() } },
            y: { grid: { display: false } }
        }
    }
});
<?php endif; ?>

/* INCOME MODAL CHART */
let incomeModalChartInst = null;
document.getElementById('incomeModal').addEventListener('transitionend', () => {});
function openModal(id) {
    document.getElementById(id).classList.add('open');
    if (id === 'incomeModal' && !incomeModalChartInst) {
        incomeModalChartInst = new Chart(document.getElementById('incomeModalChart'), {
            type: 'doughnut',
            data: {
                labels: ['Student Fees', 'Service/Invoice'],
                datasets: [{ data: [<?= $totalPaidFee ?>, <?= $invPaid ?>], backgroundColor: [GREEN, ACCENT], borderWidth: 0, cutout: '65%' }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } } } }
        });
    }
    if (id === 'dueModal' && !window.dueModalChartInst) {
        window.dueModalChartInst = new Chart(document.getElementById('dueModalChart'), {
            type: 'doughnut',
            data: {
                labels: ['Course Due', 'Invoice Due'],
                datasets: [{ data: [<?= $totalDueFee ?>, <?= $invDue ?>], backgroundColor: [YELLOW, RED], borderWidth: 0, cutout: '65%' }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } } } }
        });
    }
}
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); }));

/* SIDEBAR TOGGLE */
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const mainContent = document.getElementById('mainContent');
sidebarToggle.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    sidebarToggle.classList.toggle('collapsed');
    mainContent.classList.toggle('collapsed');
    sidebarToggle.textContent = sidebar.classList.contains('collapsed') ? '▶' : '◀';
});

/* HAMBURGER (mobile) */
document.getElementById('hamburgerBtn').addEventListener('click', () => {
    sidebar.classList.toggle('mobile-open');
});

/* SUBMENU TOGGLE */
document.querySelectorAll('.menu-toggle').forEach(t => {
    t.addEventListener('click', () => t.closest('.menu-group').classList.toggle('open'));
});

/* LIVE CLOCK */
function updateClock() {
    const now = new Date();
    document.getElementById('liveClock').textContent = now.toLocaleTimeString('en-US', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
updateClock();
setInterval(updateClock, 1000);

/* FILTER BAR SHOW/HIDE SUB-GROUPS */
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', e => {
        // handled by PHP on reload - but also handle dynamically for UX
    });
});
</script>
</body>
</html>