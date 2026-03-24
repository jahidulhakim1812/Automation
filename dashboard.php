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

// Categories including Office Applications
$categories = [
    'Graphic Design',
    'Video Editing',
    'Social Media Marketing',
    'Digital Marketing',
    'Office Application'
];

$counts = [];
foreach ($categories as $category) {
    $sql = "SELECT COUNT(*) AS total FROM students WHERE course_category = '$category'";
    $result = $conn->query($sql);
    $counts[$category] = $result->fetch_assoc()['total'];
}

// Totals
$totalCourseFee = $conn->query("SELECT SUM(course_fee) AS total FROM students")->fetch_assoc()['total'] ?? 0;
$totalPaidFee   = $conn->query("SELECT SUM(paid_fee) AS total FROM students")->fetch_assoc()['total'] ?? 0;
$totalDueFee    = $totalCourseFee - $totalPaidFee;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Freelancing Students Dashboard</title>
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
.side-nav.collapsed {
    transform: translateX(-100%);
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
.menu-group { width: 100%; }
.submenu {
    display: none;
    flex-direction: column;
    background-color: #34495e;
}
.submenu a {
    color: white;
    padding: 10px 40px;
    text-decoration: none;
    font-weight: normal;
    transition: background 0.3s ease;
}
.submenu a:hover { background-color: #3d566e; }
.menu-group.active .submenu { display: flex; }
.horizontal-submenu { flex-direction: row; flex-wrap: wrap; gap: 5px; }
.horizontal-submenu a { padding: 10px 20px; white-space: nowrap; }
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
.toggle-arrow.collapsed { left: 0; }
.container {
    margin-left: 240px;
    padding: 130px 30px 100px;
    transition: margin-left 0.3s ease;
}
.container.collapsed { margin-left: 20px; }
h2 { text-align: center; color: #fff; text-shadow: 1px 1px 2px #000; margin-bottom: 40px; }
.stats { display: flex; flex-wrap: wrap; justify-content: center; gap: 30px; opacity: 0.95; }
.card-button {
    background-color: white;
    border: none;
    border-radius: 10px;
    box-shadow: 2px 2px 12px rgba(0,0,0,0.15);
    width: 250px;
    padding: 25px;
    text-align: center;
    transition: transform 0.2s ease, background 0.3s ease;
    cursor: pointer;
}
.card-button:hover { transform: scale(1.03); background-color: #f9f9f9; }
.card-button h3 { margin: 0; color: #2c3e50; font-size: 20px; }
.card-button p { margin-top: 10px; font-size: 16px; color: #555; }
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
form { margin: 0; }
</style>
</head>
<body>

<div class="navbar">
    <strong>AR TECH SOLUTION</strong>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<?php
include 'navigation.php';
?>

<div class="toggle-arrow" id="toggleBtn">◀</div>

<div class="container" id="mainContent">
    <h2>📈 Statistics Overview</h2>
    <div class="stats">
        <?php foreach ($counts as $category => $count) { ?>
            <form method="GET" action="course_details.php">
                <input type="hidden" name="category" value="<?php echo $category; ?>">
                <button type="submit" class="card-button">
                    <h3><?php echo $category; ?></h3>
                    <p>Total Students: <?php echo $count; ?></p>
                </button>
            </form>
        <?php } ?>
        <button class="card-button" disabled>
            <h3>Total Course Fees</h3>
            <p>৳ <?php echo number_format($totalCourseFee, 2); ?></p>
        </button>
        <button class="card-button" disabled>
            <h3>Total Due Fees</h3>
            <p>৳ <?php echo number_format($totalDueFee, 2); ?></p>
        </button>
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

document.querySelectorAll('.menu-toggle').forEach(toggle => {
    toggle.addEventListener('click', () => {
        toggle.parentElement.classList.toggle('active');
    });
});
</script>

</body>
</html>
