<?php
$category = $_GET['category'] ?? '';

$conn = new mysqli("localhost", "root", "", "freelancing");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$sql = "SELECT * FROM students WHERE course_category = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $category);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($category); ?> Students</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f4f4; }
    .navbar {
      background-color: #333;
      color: white;
      padding: 15px;
      text-align: center;
      font-size: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background-color: white;
      margin: 20px 0;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 10px;
      text-align: center;
    }
    th {
      background-color: #333;
      color: white;
    }
    .container {
      padding: 20px;
    }
    a.button {
      text-decoration: none;
      padding: 10px 20px;
      background-color: #444;
      color: white;
      border-radius: 5px;
      margin: 20px;
      display: inline-block;
    }
    a.button:hover {
      background-color: #222;
    }
  </style>
</head>
<body>

  <div class="navbar"><?php echo htmlspecialchars($category); ?> Students</div>

  <div class="container">
    <a href="dashboard.php" class="button">← Back to Dashboard</a>
    <?php if ($result->num_rows > 0): ?>
      <table>
        <tr>
          <th>Student ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Paid Fee</th>
          <th>Due Fee</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?php echo htmlspecialchars($row["student_id"]); ?></td>
            <td><?php echo htmlspecialchars($row["name"]); ?></td>
            <td><?php echo htmlspecialchars($row["email"]); ?></td>
            <td>৳ <?php echo number_format($row["paid_fee"], 2); ?></td>
            <td>৳ <?php echo number_format($row["course_fee"] - $row["paid_fee"], 2); ?></td>
          </tr>
        <?php endwhile; ?>
      </table>
    <?php else: ?>
      <p>No students enrolled in <?php echo htmlspecialchars($category); ?>.</p>
    <?php endif; ?>
  </div>

</body>
</html>
