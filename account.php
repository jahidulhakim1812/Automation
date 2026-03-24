<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "freelancing";

$conn = new mysqli($servername, $username, "", $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch totals
$totalCourseFee = $conn->query("SELECT SUM(course_fee) AS total_fee FROM students")->fetch_assoc()['total_fee'] ?? 0;
$totalPaidFee = $conn->query("SELECT SUM(paid_fee) AS paid_fee FROM students")->fetch_assoc()['paid_fee'] ?? 0;
$totalDueFee = $totalCourseFee - $totalPaidFee;

// Handle payment form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST["student_id"];
    $add_fee = $_POST["add_fee"];
    $update = "UPDATE students SET paid_fee = paid_fee + $add_fee WHERE student_id = '$student_id'";
    if ($conn->query($update) === TRUE) {
        echo "<script>alert('Fee added successfully!'); window.location.href='account.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Account Overview</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
}

/* HEADER */
.navbar {
    background-color: #333;
    color: white;
    padding: 15px 20px;
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
}
.logout-btn {
  position: absolute;
  right: 50px;
  background: linear-gradient(135deg, #ff4d4d, #cc0000);
  color: white;
  padding: 8px 20px;
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


/* SIDEBAR */
.side-nav {
    position: fixed;
    top: 60px;
    left: 0;
    width: 220px;
    height: calc(100% - 60px);
    background-color: #2c3e50;
    padding-top: 20px;
    z-index: 999;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    box-shadow: 2px 0 5px rgba(0,0,0,0.2);
    overflow-y: auto;
    transition: transform 0.3s ease;
}
.side-nav.collapsed {
    transform: translateX(-220px);
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
.menu-group {
    width: 100%;
}
.submenu {
    display: none;
    flex-direction: column;
    background-color: #34495e;
}
.submenu a {
    color: white;
    padding: 10px 40px;
    font-weight: normal;
}
.submenu a:hover {
    background-color: #3d566e;
}
.menu-group.active .submenu {
    display: flex;
}

/* Toggle Button */
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
.toggle-arrow.collapsed {
    left: 0;
}

/* CONTENT */
.content {
    margin-left: 240px;
    padding: 80px 20px;
}

/* STAT BOXES */
.stats-container {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.stat-box {
    flex: 1;
    background: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    min-width: 200px;
}
.stat-box h3 {
    margin-bottom: 10px;
    font-size: 18px;
}
.stat-box p {
    font-size: 22px;
    font-weight: bold;
}

/* CHART */
.chart-container {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

/* FORM */
.form-box {
    margin-top: 20px;
    background: none;
    padding: 20px;
    max-width: 400px;
}
input, button {
    width: 100%;
    padding: 10px;
    margin: 10px 0;
}
button {
    background-color: #333;
    color: white;
    border: none;
    cursor: pointer;
}
button:hover {
    background-color: #555;
}

/* FOOTER */
.footer {
    background-color: #333;
    color: white;
    text-align: center;
    padding: 15px;
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    font-weight: bold;
}
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

<!-- CONTENT -->
<div class="content" id="mainContent">
    <h2>Account Overview</h2>

    <!-- STAT BOXES -->
    <div class="stats-container">
        <div class="stat-box" style="border-top: 4px solid #27ae60;">
            <h3>Total Course Fee</h3>
            <p>৳ <?php echo number_format($totalCourseFee, 2); ?></p>
        </div>
        <div class="stat-box" style="border-top: 4px solid #2980b9;">
            <h3>Total Paid Fee</h3>
            <p>৳ <?php echo number_format($totalPaidFee, 2); ?></p>
        </div>
        <div class="stat-box" style="border-top: 4px solid #e74c3c;">
            <h3>Total Due Fee</h3>
            <p>৳ <?php echo number_format($totalDueFee, 2); ?></p>
        </div>
    </div>

    <!-- BAR CHART -->
    <div class="chart-container">
        <canvas id="feeChart"></canvas>
    </div>

    <!-- ADD FEE FORM (OUTSIDE BOX) -->
    <div class="form-box">
        <h3>Add Student Fee Payment</h3>
        <form method="POST">
            <input type="number" name="student_id" placeholder="Student ID" required>
            <input type="number" name="add_fee" placeholder="Enter Fee Amount" required>
            <button type="submit">Add Fee</button>
        </form>
    </div>
</div>

<!-- FOOTER -->
<div class="footer">
    &copy; <?php echo date("Y"); ?> Freelancing Students Management System | All Rights Reserved
</div>

<script>
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('toggleBtn');
toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    toggleBtn.classList.toggle('collapsed');
    toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '▶' : '◀';
    document.getElementById('mainContent').style.marginLeft = sidebar.classList.contains('collapsed') ? '20px' : '240px';
});

document.querySelectorAll('.menu-toggle').forEach(toggle => {
    toggle.addEventListener('click', () => {
        toggle.parentElement.classList.toggle('active');
    });
});

const ctx = document.getElementById('feeChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Total Course Fees', 'Total Paid Fees', 'Total Due Fees'],
        datasets: [{
            label: 'Fees Overview (৳)',
            data: [
                <?php echo $totalCourseFee; ?>, 
                <?php echo $totalPaidFee; ?>, 
                <?php echo $totalDueFee; ?>
            ],
            backgroundColor: ['#27ae60', '#2980b9', '#e74c3c']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

</body>
</html>
