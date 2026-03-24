<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "freelancing";

$conn = new mysqli($servername, $username, "", $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$studentData = null;
$percentage = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST["student_id"];
    $sql = "SELECT * FROM students WHERE student_id = '$student_id'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $studentData = $result->fetch_assoc();
        $percentage = ($studentData['paid_fee'] / $studentData['course_fee']) * 100;
    } else {
        $error = "No student found with this ID.";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Student Report</title>
<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
    }
    /* Header */
.navbar {
  background-color: #333;
  color: white;
  padding: 15px 20px;
  font-size: 24px;
  display: flex;
  justify-content: center;
  align-items: center;
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


    /* Sidebar */
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
  transition: transform 0.3s ease;
  overflow-y: auto;
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
  text-decoration: none;
  font-weight: normal;
  transition: background 0.3s ease;
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

    /* Main container */
    .container {
        margin-left: 220px;
        padding: 100px 20px 70px;
        max-width: 900px;
    }
    /* Cards */
    .card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0px 0px 10px #ccc;
        margin-bottom: 20px;
    }
    /* Stats bar */
    .stats-bar {
        height: 20px;
        border-radius: 5px;
        background-color: #ddd;
        overflow: hidden;
        margin: 5px 0;
    }
    .stats-fill {
        height: 100%;
        background-color: #4CAF50;
        width: 0%;
    }
    /* Payment done button */
    .payment-done {
        display: inline-block;
        margin-top: 10px;
        padding: 10px 15px;
        background: #28a745;
        color: white;
        font-weight: bold;
        border-radius: 5px;
        text-decoration: none;
    }
    /* Footer */
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

<!-- Main Content -->
<div class="container">
    <div class="card">
        <h2>Search Student Account</h2>
        <form method="POST">
            <input type="number" name="student_id" placeholder="Enter Student ID" required style="padding: 10px; width: 80%;">
            <button type="submit" style="padding: 10px;">Search</button>
        </form>
    </div>

    <?php if (isset($error)): ?>
        <div class="card" style="color: red;"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($studentData): ?>
        <div class="card">
            <h3>Student Details</h3>
            <p><strong>ID:</strong> <?php echo $studentData['student_id']; ?></p>
            <p><strong>Name:</strong> <?php echo $studentData['name']; ?></p>
            <p><strong>Father's Name:</strong> <?php echo $studentData['father_name']; ?></p>
            <p><strong>Course Name:</strong> <?php echo $studentData['course_category']; ?></p>
            <p><strong>Present Address:</strong> <?php echo $studentData['present_address']; ?></p>
            <p><strong>Course Fee:</strong> ৳ <?php echo number_format($studentData['course_fee'], 2); ?></p>
            <p><strong>Paid Fee:</strong> ৳ <?php echo number_format($studentData['paid_fee'], 2); ?></p>
            <p><strong>Due Fee:</strong> ৳ <?php echo number_format($studentData['course_fee'] - $studentData['paid_fee'], 2); ?></p>

            <h4>Payment Progress</h4>
            <div class="stats-bar">
                <div class="stats-fill" style="width: <?php echo $percentage; ?>%;"></div>
            </div>
            <p><strong><?php echo round($percentage, 2); ?>%</strong> of payment completed</p>

            <?php if ($studentData['course_fee'] - $studentData['paid_fee'] == 0): ?>
                <a href="#" class="payment-done">✅ Payment Done</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Footer -->
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
  });

  document.querySelectorAll('.menu-toggle').forEach(toggle => {
    toggle.addEventListener('click', () => {
      toggle.parentElement.classList.toggle('active');
    });
  });
</script>

</body>
</html>
