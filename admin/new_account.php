<?php
$conn = new mysqli("localhost", "root", "", "freelancing");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$student_id = $_GET["student_id"] ?? null;
$payments = [];

if ($student_id) {
  $sql = "SELECT * FROM payments WHERE student_id = '$student_id' ORDER BY payment_date DESC";
  $result = $conn->query($sql);
  if ($result && $result->num_rows) {
    while ($row = $result->fetch_assoc()) {
      $payments[] = $row;
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <title>Account | Payment History</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f9f9f9;
      margin: 0;
      padding: 0;
      color: #333;
    }
    .navbar {
      background: #333;
      color: #fff;
      padding: 15px;
      font-size: 24px;
      text-align: center;
    }
    .container {
      max-width: 850px;
      margin: auto;
      padding: 30px 20px;
      background: #fff;
      margin-top: 40px;
    }
    .search-box {
      text-align: center;
      margin-bottom: 20px;
    }
    input[type="text"] {
      padding: 8px;
      font-size: 16px;
      width: 220px;
      margin-right: 10px;
    }
    button {
      padding: 8px 16px;
      font-size: 16px;
      cursor: pointer;
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
      font-size: 26px;
      text-transform: uppercase;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 10px;
      text-align: center;
    }
    th {
      background-color: #eee;
      font-weight: bold;
    }
    .no-data {
      text-align: center;
      color: red;
      margin-top: 20px;
    }
  </style>
</head>
<body>

  <div class="navbar">Account | Payment History</div>

  <div class="container">
    <div class="search-box">
      <form method="GET">
        <input type="text" name="student_id" placeholder="Enter Student ID" required />
        <button type="submit">View Payments</button>
      </form>
    </div>

    <?php if ($student_id): ?>
      <h2>Payment History for <?= htmlspecialchars($student_id) ?></h2>
      <?php if (!empty($payments)): ?>
        <table>
          <tr>
            <th>#</th>
            <th>Amount Paid</th>
            <th>Payment Date</th>
            <th>Payment Method</th>
            <th>Remarks</th>
          </tr>
          <?php foreach ($payments as $index => $payment): ?>
          <tr>
            <td><?= $index + 1 ?></td>
            <td>৳<?= number_format($payment["amount"], 2) ?></td>
            <td><?= htmlspecialchars($payment["payment_date"]) ?></td>
            <td><?= htmlspecialchars($payment["method"]) ?></td>
            <td><?= htmlspecialchars($payment["remarks"]) ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      <?php else: ?>
        <div class="no-data">No payment records found for this student.</div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

</body>
</html>
