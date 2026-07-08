<?php
session_start();
require 'config.php';
require 'vendor/autoload.php'; // PHPMailer via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 🛡️ Session & role check
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'Admin') {
  header('Location: login.php');
  exit();
}

$msg = "";

// 📨 Send email
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $to = $_POST['to_email'];
  $subject = $_POST['subject'];
  $body = $_POST['body'];

  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'artechsolution.online@gmail.com';
    $mail->Password   = 'giwr wrcr mnyi lkpf';  // App password for Gmail
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('artechsolution.online@gmail.com', 'Austro-Asian Admin');
    $mail->addAddress($to);
    $mail->Subject = $subject;
    $mail->Body    = $body;

    $mail->send();
    $msg = "✅ Email sent to $to";
  } catch (Exception $e) {
    $msg = "❌ Could not send email: {$mail->ErrorInfo}";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Email Editor/Journalist</title>
  <style>
    body { font-family: Arial; background: #f2f2f2; padding: 40px; }
    .container {
      max-width: 600px; margin: auto; background: white;
      padding: 30px; border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    select, input, textarea {
      width: 100%; margin-bottom: 15px;
      padding: 10px; border: 1px solid #ccc; border-radius: 5px;
    }
    button {
      background-color: #007BFF; color: white;
      padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;
    }
    button:hover { background-color: #0056b3; }
    .msg { margin-bottom: 20px; font-weight: bold; text-align: center; color: green; }
  </style>
</head>
<body>
  <div class="container">
    <h2>📧 Send Message to Team Member</h2>
    <?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="POST">
      <label>Select Recipient:</label>
      <select name="to_email" required>
        <option value="">-- Choose email --</option>
        <?php
        $query = "SELECT email FROM users WHERE role IN ('Editor', 'Journalist') ORDER BY email ASC";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
          $email = htmlspecialchars($row['email']);
          echo "<option value=\"$email\">$email</option>";
        }
        ?>
      </select>

      <label>Subject:</label>
      <input type="text" name="subject" placeholder="Email subject" required>

      <label>Message:</label>
      <textarea name="body" rows="6" placeholder="Type your message..." required></textarea>

      <button type="submit">Send Email</button>
    </form>
  </div>
</body>
</html>
