<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "freelancing";

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";

// Only process if form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = isset($_POST['title']) ? $conn->real_escape_string($_POST['title']) : '';
    $description = isset($_POST['description']) ? $conn->real_escape_string($_POST['description']) : '';

    if (!empty($title) && isset($_FILES['video']) && $_FILES['video']['error'] === 0) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

        $filename = time() . "_" . basename($_FILES['video']['name']);
        $targetFilePath = $targetDir . $filename;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        $allowedTypes = ['mp4','webm','ogg'];
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['video']['tmp_name'], $targetFilePath)) {
                $stmt = $conn->prepare("INSERT INTO videos (title, description, filename) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $title, $description, $filename);
                if ($stmt->execute()) {
                    $message = "Video uploaded successfully!";
                } else {
                    $message = "Database error: Could not save video info.";
                }
                $stmt->close();
            } else {
                $message = "Error uploading video file.";
            }
        } else {
            $message = "Invalid file type. Only mp4, webm, ogg allowed.";
        }
    } else {
        $message = "Please enter a title and select a video file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Upload Video</title>
<style>
* { box-sizing: border-box; margin:0; padding:0; font-family: Arial, sans-serif; }

/* HEADER */
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
  right: 20px;
  background: linear-gradient(135deg,#ff4d4d,#cc0000);
  color: white;
  padding: 8px 20px;
  border-radius: 25px;
  text-decoration: none;
  font-size: 15px;
  transition: all 0.3s ease;
}
.logout-btn:hover { background: #e60000; transform: scale(1.05); }

/* SIDE NAVBAR */
.side-nav {
  position: fixed;
  top: 60px;
  left: 0;
  width: 220px;
  height: calc(100% - 60px);
  background-color: #2c3e50;
  padding-top: 20px;
  flex-direction: column;
  z-index: 999;
  overflow-y: auto;
  transition: transform 0.3s ease;
}
.side-nav.collapsed { transform: translateX(-220px); }
.side-nav a, .menu-toggle {
  color: white;
  text-decoration: none;
  padding: 12px 25px;
  display:block;
}
.side-nav a:hover { background-color: #34495e; }

/* TOGGLE BUTTON */
.toggle-arrow {
  position: fixed;
  top: 70px;
  left: 220px;
  background-color: #1abc9c;
  color: white;
  padding: 6px 12px;
  cursor:pointer;
  border-radius:0 5px 5px 0;
  z-index:1001;
  transition:left 0.3s ease;
}
.toggle-arrow.collapsed { left:0; }

/* MAIN CONTAINER */
.container {
  width: 600px;
  background: white;
  padding: 30px;
  border-radius: 10px;
  box-shadow:0 0 15px rgba(0,0,0,0.2);
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  transition: left 0.3s ease, transform 0.3s ease;
}
.container.collapsed { left: 50%; transform: translate(-50%, -50%); }

input, textarea, button { width:100%; padding:10px; margin:10px 0; border-radius:5px; border:1px solid #ccc; }
button{background:#1abc9c; color:white; border:none; cursor:pointer;}
button:hover{background:#16a085;}
.message{color:green; text-align:center; margin-bottom:10px; }

/* FOOTER */
.footer {
  background:#333;
  color:white;
  text-align:center;
  padding:15px;
  position: fixed;
  bottom:0;
  left:0;
  width:100vw;
  font-weight:bold;
}
</style>
</head>
<body>

<!-- HEADER -->
<div class="navbar">
  <strong>AR TECH SOLUTION</strong>
  <a href="logout.php" class="logout-btn">Logout</a>
</div>

<!-- SIDE NAVBAR -->
<div class="side-nav" id="sidebar">
  <a href="dashboard.php">📊 Dashboard</a>
  <a href="upload_video.php">🎬 Upload Video</a>
  <a href="view_videos.php">📺 View Videos</a>
</div>

<div class="toggle-arrow" id="toggleBtn">◀</div>

<!-- MAIN CONTENT -->
<div class="container" id="mainContent">
  <h2>Upload Video</h2>
  <?php if($message) echo "<p class='message'>$message</p>"; ?>
  <form method="POST" enctype="multipart/form-data">
      <input type="text" name="title" placeholder="Video Title" required>
      <textarea name="description" placeholder="Video Description"></textarea>
      <input type="file" name="video" accept="video/*" required>
      <button type="submit">Upload Video</button>
  </form>
</div>

<!-- FOOTER -->
<div class="footer">© 2025 AR TECH SOLUTION</div>

<script>
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('toggleBtn');
const mainContent = document.getElementById('mainContent');

toggleBtn.addEventListener('click', ()=>{
    sidebar.classList.toggle('collapsed');
    toggleBtn.classList.toggle('collapsed');
    mainContent.classList.toggle('collapsed');
    toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '▶' : '◀';
});
</script>

</body>
</html>
