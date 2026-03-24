<?php
session_start();
if (!isset($_SESSION["email"]) || $_SESSION["role"] !== "User") {
  header("Location: login.php");
  exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "freelancing";

$conn = new mysqli($servername, $username, "", $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$student = null;
$error = "";
$email = $_SESSION["email"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["search_id"])) {
  $search_id = $conn->real_escape_string(trim($_POST["search_id"]));

  // Check if this student ID belongs to the logged-in user
  $sql = "SELECT * FROM students WHERE student_id = '$search_id'";

  $result = $conn->query($sql);

  if ($result && $result->num_rows === 1) {
    $student = $result->fetch_assoc();
  } else {
    $error = "No record found for Student ID '$search_id' under your account.";
  }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>User View</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      background-color: #f5f5f5;
    }
    .navbar {
  position: relative;
  background: #333;
  color: white;
  padding: 15px 20px;
  font-size: 22px;
  text-align: center;
}

.logout-btn {
  position: absolute;
  right: 20px;
  top: 50%;
  transform: translateY(-50%);
  background: red;
  color: white;
  padding: 8px 15px;
  text-decoration: none;
  border-radius: 5px;
  font-size: 14px;
}

    .nav-links {
      background: #444;
      padding: 10px;
      text-align: center;
    }
    .nav-links a {
      color: white;
      margin: 0 15px;
      text-decoration: none;
      font-weight: bold;
    }
    .nav-links a:hover {
      text-decoration: underline;
    }
    .container {
      max-width: 700px;
      margin: 50px auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 12px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #333;
    }
    form {
      text-align: center;
      margin-bottom: 30px;
    }
    input[type="text"] {
      padding: 10px;
      width: 60%;
      font-size: 16px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    button {
      padding: 10px 20px;
      font-size: 16px;
      background: #007BFF;
      color: white;
      border: none;
      border-radius: 5px;
      margin-left: 10px;
      cursor: pointer;
    }
    button:hover {
      background: #0056b3;
    }
    .message {
      background: #ffe0e0;
      color: #c00;
      padding: 12px;
      text-align: center;
      border-radius: 5px;
      margin-bottom: 20px;
      border: 1px solid #faa;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      text-align: left;
      padding: 12px;
      border-bottom: 1px solid #ddd;
    }
    th {
      background: #333;
      color: white;
    }
  </style>
</head>
<body>

  <div class="navbar">
  <span>User Dashboard</span>
  <a href="logout.php" class="logout-btn">Logout</a>
</div>

  <div class="nav-links">
    <a href="user_dashboard.php">Dashboard</a>
    <a href="user_view.php">Search Profile</a>
  </div>

  <div class="container">
    <h2>🔎 Search Your Student ID</h2>

    <form method="POST">
      <input type="text" name="search_id" placeholder="Enter your student ID..." required />
      <button type="submit">Search</button>
    </form>

    <?php if ($error): ?>
      <div class="message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($student): ?>
      <table>
        <tr><th>Student ID</th><td><?php echo $student["student_id"]; ?></td></tr>
        <tr><th>Name</th><td><?php echo htmlspecialchars($student["name"]); ?></td></tr>
        <tr><th>Course</th><td><?php echo $student["course_category"]; ?></td></tr>
        <tr><th>Total Fee</th><td>৳ <?php echo number_format($student["course_fee"], 2); ?></td></tr>
        <tr><th>Paid Fee</th><td>৳ <?php echo number_format($student["paid_fee"], 2); ?></td></tr>
        <tr><th>Due Fee</th><td>৳ <?php echo number_format($student["course_fee"] - $student["paid_fee"], 2); ?></td></tr>
        <tr><th>Last Payment</th><td><?php echo $student["last_updated"]; ?></td></tr>
      </table>
      
    <?php endif; ?>
  </div>

</body>
</html>
