<?php
// account_info.php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

// Helpful during development — remove or adjust in production
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "freelancing";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset('utf8mb4');

// Inputs
$search  = isset($_GET['search']) ? trim($_GET['search']) : '';
$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset  = ($page - 1) * $perPage;

// --- Summary queries (no user input) ---
$totalStudentsQ  = "SELECT COUNT(*) AS total FROM students";
$totalCourseFeeQ = "SELECT IFNULL(SUM(course_fee),0) AS total FROM students";
$totalPaidFeeQ   = "SELECT IFNULL(SUM(paid_fee),0) AS total FROM students";

$totalStudents  = (int) $conn->query($totalStudentsQ)->fetch_assoc()['total'] ?? 0;
$totalCourseFee = (float) $conn->query($totalCourseFeeQ)->fetch_assoc()['total'] ?? 0;
$totalPaidFee   = (float) $conn->query($totalPaidFeeQ)->fetch_assoc()['total'] ?? 0;
$totalDueFee    = $totalCourseFee - $totalPaidFee;

// --- Build search fragment and parameters ---
// According to your SQL dump the table columns are:
// student_id, name, email, phone_number, course_category, course_fee, paid_fee, created_at
$searchSql = "";
$params    = [];
$types     = "";

if ($search !== '') {
    $searchSql = "WHERE (name LIKE ? OR email LIKE ? OR phone_number LIKE ? OR student_id LIKE ?)";
    $like = "%{$search}%";
    $params = [$like, $like, $like, $search]; // student_id search uses raw search (no %), but we can also use % if desired
    $types  = "sssi"; // name,email,phone -> s; student_id -> i (if numeric). We'll attempt to cast student_id to int below.
    // If search is not numeric for student_id, we'll bind 0 for that param to avoid errors.
}

// --- Count total rows for pagination ---
$countSql = "SELECT COUNT(*) AS total FROM students $searchSql";
$stmt = $conn->prepare($countSql);
if ($search !== '') {
    // Prepare values for binding
    $bindName  = $params[0];
    $bindEmail = $params[1];
    $bindPhone = $params[2];
    // student_id binding: try to cast to int, otherwise 0 (no match)
    $bindId = is_numeric($params[3]) ? (int)$params[3] : 0;
    $stmt->bind_param("sssi", $bindName, $bindEmail, $bindPhone, $bindId);
}
$stmt->execute();
$totalRows = (int) $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

$totalPages = max(1, (int) ceil($totalRows / $perPage));

// --- Fetch rows for current page ---
// Select columns that exist in your dump
$listSql = "SELECT student_id, name, email, course_category, course_fee, paid_fee, (IFNULL(course_fee,0) - IFNULL(paid_fee,0)) AS due_fee, phone_number, created_at
            FROM students
            $searchSql
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?";

$stmt = $conn->prepare($listSql);

if ($search !== '') {
    // bind search params then limit and offset
    $bindName  = $params[0];
    $bindEmail = $params[1];
    $bindPhone = $params[2];
    $bindId    = is_numeric($params[3]) ? (int)$params[3] : 0;
    // types: sssii (three strings, then two integers for limit/offset)
    $stmt->bind_param("sssii", $bindName, $bindEmail, $bindPhone, $perPage, $offset);
} else {
    // types: ii
    $stmt->bind_param("ii", $perPage, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Account Overview</title>
<style>
* { box-sizing: border-box; }
body {
    font-family: 'Segoe UI', sans-serif;
    margin: 0;
    background: url('uploads/banner.jpg') no-repeat center center fixed;
    background-size: cover;
}
.navbar {
    background-color: #1a1a1a;
    color: white;
    padding: 15px 30px;
    font-size: 22px;
    display: flex;
    justify-content: center;
    align-items: center;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}
.logout-btn {
    position: absolute;
    right: 10px;
    background: linear-gradient(135deg, #ff4d4d, #cc0000);
    color: white;
    padding: 8px 15px;
    text-decoration: none;
    border-radius: 25px;
    font-size: 15px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}
.logout-btn:hover {
    background: linear-gradient(135deg, #ff6666, #e60000);
    transform: scale(1.05);
}
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
.container {
    margin-left: 240px;
    padding: 130px 30px 100px;
    transition: margin-left 0.3s ease;
}
.container.collapsed { margin-left: 20px; }
.header-row { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; }
.summary { display:flex; gap:20px; flex-wrap:wrap; margin-bottom:20px; }
.summary .card {
    background: rgba(255,255,255,0.95);
    padding:18px 22px;
    border-radius:10px;
    min-width:180px;
    box-shadow: 2px 2px 12px rgba(0,0,0,0.12);
    text-align:center;
}
.summary .card h3 { margin:0; color:#2c3e50; font-size:18px; }
.summary .card p { margin:8px 0 0; color:#333; font-weight:bold; font-size:16px; }

.search-form { display:flex; gap:8px; align-items:center; }
.search-form input[type="text"] {
    padding:8px 12px; border-radius:6px; border:1px solid #ccc; min-width:220px;
}
.search-form button { padding:8px 12px; border-radius:6px; border:none; background:#1abc9c; color:#fff; cursor:pointer; }

.table-wrap {
    background: rgba(255,255,255,0.95);
    padding: 18px;
    border-radius: 10px;
    box-shadow: 2px 2px 12px rgba(0,0,0,0.12);
    overflow-x:auto;
}
table { width:100%; border-collapse:collapse; }
th, td { padding:10px 12px; text-align:left; border-bottom:1px solid #e6e6e6; }
th { background:#f7f7f7; color:#333; font-weight:700; }
.badge-due { color:#fff; background:#e74c3c; padding:6px 8px; border-radius:6px; font-weight:bold; }
.badge-paid { color:#fff; background:#27ae60; padding:6px 8px; border-radius:6px; font-weight:bold; }

.pagination { margin-top:12px; display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
.page-link {
    padding:8px 10px; background:#fff; border:1px solid #ddd; border-radius:6px; text-decoration:none; color:#333;
}
.page-link.active { background:#1abc9c; color:#fff; border-color:#1abc9c; }

.footer {
    background-color: #1a1a1a;
    color: white;
    text-align: center;
    padding: 15px;
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    font-weight: bold;
    box-shadow: 0 -2px 5px rgba(0,0,0,0.2);
}
@media (max-width:800px) {
    .container { padding: 110px 16px 120px; margin-left: 10px; }
    .summary { flex-direction:column; }
}
</style>
</head>
<body>

<div class="navbar">
    <strong>AR TECH SOLUTION</strong>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="side-nav" id="sidebar">
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="account_info.php">💵 Account</a>
    <a href="insert.php">➕ Add Student</a>
    <a href="student_list.php">👥 Students</a>
    <a href="report.php">📄 Report</a>
    <a href="routine_generator.php">🕒 Routine</a>
</div>

<div class="toggle-arrow" id="toggleBtn">◀</div>

<div class="container" id="mainContent">
    <div class="header-row">
        <h2 style="color:#fff; text-shadow:1px 1px 2px #000; margin:0;">💵 Account Overview</h2>
        <form class="search-form" method="GET" action="account_info.php">
            <input type="text" name="search" placeholder="Search by name, email, phone or ID" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <div class="summary" style="margin-top:18px;">
        <div class="card">
            <h3>Total Students</h3>
            <p><?php echo (int)$totalStudents; ?></p>
        </div>
        <div class="card">
            <h3>Total Course Fees</h3>
            <p>৳ <?php echo number_format($totalCourseFee, 2); ?></p>
        </div>
        <div class="card">
            <h3>Total Paid</h3>
            <p>৳ <?php echo number_format($totalPaidFee, 2); ?></p>
        </div>
        <div class="card">
            <h3>Total Due</h3>
            <p>৳ <?php echo number_format($totalDueFee, 2); ?></p>
        </div>
    </div>

    <div class="table-wrap" style="margin-top:18px;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Category</th>
                    <th>Course Fee</th>
                    <th>Paid</th>
                    <th>Due</th>
                    <th>Phone</th>
                    <th>Joined</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sr = $offset + 1;
                if ($result->num_rows === 0) {
                    echo '<tr><td colspan="11" style="text-align:center; padding:20px;">No records found</td></tr>';
                } else {
                    while ($row = $result->fetch_assoc()) {
                        $due = (float)$row['due_fee'];
                        $dueBadge = $due > 0 ? '<span class="badge-due">৳ '.number_format($due,2).'</span>' : '<span class="badge-paid">Paid</span>';
                        echo '<tr>';
                        echo '<td>'.($sr++).'</td>';
                        echo '<td>'.htmlspecialchars($row['student_id'], ENT_QUOTES, 'UTF-8').'</td>';
                        echo '<td>'.htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8').'</td>';
                        echo '<td>'.htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8').'</td>';
                        echo '<td>'.htmlspecialchars($row['course_category'], ENT_QUOTES, 'UTF-8').'</td>';
                        echo '<td>৳ '.number_format((float)$row['course_fee'],2).'</td>';
                        echo '<td>৳ '.number_format((float)$row['paid_fee'],2).'</td>';
                        echo '<td>'.$dueBadge.'</td>';
                        echo '<td>'.htmlspecialchars($row['phone_number'], ENT_QUOTES, 'UTF-8').'</td>';
                        echo '<td>'.htmlspecialchars(date("Y-m-d", strtotime($row['created_at'])), ENT_QUOTES, 'UTF-8').'</td>';
                        echo '<td>
                                <a href="view_student.php?id='.urlencode($row['student_id']).'" class="page-link" style="padding:6px 8px;">View</a>
                                <a href="edit_payment.php?id='.urlencode($row['student_id']).'" class="page-link" style="padding:6px 8px;">Edit</a>
                              </td>';
                        echo '</tr>';
                    }
                }
                $stmt->close();
                ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php
            // Build base URL preserving search if present
            $baseUrl = 'account_info.php?';
            if ($search !== '') {
                $baseUrl .= 'search=' . urlencode($search) . '&';
            }
            for ($p = 1; $p <= $totalPages; $p++) {
                $active = $p === $page ? 'active' : '';
                echo '<a class="page-link '.$active.'" href="'.$baseUrl.'page='.$p.'">'.$p.'</a>';
            }
            ?>
        </div>
    </div>
</div>

<div class="footer">
    &copy; <?php echo date("Y"); ?> Freelancing Students Management System | All Rights Reserved
</div>

<script>
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('toggleBtn');
const mainContent = document.getElementById('mainContent');

toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    toggleBtn.classList.toggle('collapsed');
    mainContent.classList.toggle('collapsed');
    toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '▶' : '◀';
});
</script>

</body>
</html>
<?php
// Close connection
$conn->close();
?>
