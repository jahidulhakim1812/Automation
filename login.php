<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "freelancing";

$conn = new mysqli($servername, $username, "", $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $role = $_POST["role"];
  $email = $_POST["email"];
  $pass = $_POST["password"];

  $table = ($role == "Admin") ? "admins" : "users";
  $sql = "SELECT * FROM $table WHERE email='$email' AND password='$pass'";
  $result = $conn->query($sql);

 if ($result && $result->num_rows === 1) {
  $_SESSION["email"] = $email;
  $_SESSION["role"] = $role;

  if ($role === "Admin") {
    header("Location: dashboard.php");
  } else {
    header("Location: user_dashboard.php");
  }

  exit();
}


}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Login - Freelancing System</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #e3e3e3;
    }

    .navbar {
      background: #333;
      color: white;
      padding: 15px;
      text-align: center;
      font-size: 24px;
    }

    .login-box {
      max-width: 400px;
      margin: 80px auto;
      background: white;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    .form-group {
      width: 100%;
      margin: 10px 0;
    }

    input, select, button {
      width: 100%;
      padding: 10px;
      box-sizing: border-box;
    }

    button {
      background: #333;
      color: white;
      border: none;
    }

    button:hover {
      background: #555;
    }

    .error {
      color: red;
      text-align: center;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

  <div class="navbar">Freelancing Management System</div>

  <div class="login-box">
    <h2>Login</h2>
    <?php if ($message): ?>
      <p class="error"><?php echo $message; ?></p>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <select name="role" required>
          <option value="">Select Role</option>
          <option value="Admin">Admin</option>
          <option value="User">User</option>
        </select>
      </div>
      <div class="form-group">
        <input type="email" name="email" placeholder="Email" required />
      </div>
      <div class="form-group">
        <input type="password" name="password" placeholder="Password" required />
      </div>
      <div class="form-group">
        <button type="submit">Login</button>
      </div>
    </form>
  </div>

</body>
</html>
