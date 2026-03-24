<?php
$conn = new mysqli("localhost", "root", "", "freelancing");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$student_id = $_GET["student_id"] ?? null;
$show_image = $_GET["show_image"] ?? null;
$data = null;

if ($student_id && !$show_image) {
    $sql = "SELECT * FROM students WHERE student_id = '$student_id'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows) {
        $data = $result->fetch_assoc();
    }
}

if ($show_image) {
    $filename = basename($_GET["show_image"]);
    $path = __DIR__ . "/uploads/" . $filename;
    if (file_exists($path)) {
        header("Content-Type: image/jpeg");
        readfile($path);
    } else {
        readfile(__DIR__ . "/uploads/placeholder.png");
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admission Form</title>
<style>
body { margin:0; font-family: Arial, sans-serif; background:#f4f4f4; }

/* Header */
.navbar {
  background:#333; color:white; padding:15px 20px;
  font-size:24px; display:flex; justify-content:center; align-items:center;
  position:fixed; top:0; left:0; width:100%; z-index:1000;
}
.logout-btn {
    position: absolute;
    right: 80px; /* moved left */
    background: red;
    color: white;
    padding: 8px 15px;
    text-decoration: none;
    border-radius: 4px;
    font-size: 16px;
}
.logout-btn:hover {
    background-color: #c0392b;
}

/* Sidebar */
.side-nav {
  position:fixed; top:60px; left:0; width:220px;
  height:calc(100% - 60px); background:#2c3e50; padding-top:20px;
  display:flex; flex-direction:column; box-shadow:2px 0 5px rgba(0,0,0,0.2);
  overflow-y:auto; z-index:999; transition: width 0.3s ease;
}
.side-nav.collapsed { width:0; overflow:hidden; }
.side-nav a, .menu-toggle {
  color:white; text-decoration:none; padding:12px 20px;
  width:100%; font-weight:bold; border-left:4px solid transparent;
  cursor:pointer; white-space: nowrap; overflow:hidden; text-overflow: ellipsis;
}
.side-nav a:hover, .menu-toggle:hover {
  background:#34495e; border-left:4px solid #1abc9c;
}
.menu-group { width:100%; }
.submenu { display:none; flex-direction:column; background:#34495e; }
.menu-group.active .submenu { display:flex; }
.submenu a { color:white; padding:10px 40px; font-weight:normal; }
.submenu a:hover { background:#3d566e; }

/* Toggle button */
.toggle-arrow {
  position:fixed; top:70px; left:220px; background:#1abc9c;
  color:white; padding:6px 10px; border-radius:0 5px 5px 0; cursor:pointer;
  z-index:1001; font-size:18px; transition:left 0.3s ease;
}
.toggle-arrow.collapsed { left:0; }

/* Main container */
.container { 
  margin-left:220px; 
  padding:100px 20px 70px; 
  max-width:900px; 
  transition: margin-left 0.3s ease;
  display:flex;
  flex-direction:column;
  align-items:center;  /* horizontally center content */
}
.container.collapsed { margin-left:0; }

/* Search box */
.search-box form {
  display:flex;
  justify-content:center;
  gap:10px;
  margin-bottom:20px;
}
.search-box input {
  padding:8px;
  width:250px;
  border-radius:4px;
  border:1px solid #ccc;
}
.search-box button {
  padding:8px 15px;
  border:none;
  background:#1abc9c;
  color:white;
  border-radius:4px;
  cursor:pointer;
}
.search-box button:hover { background:#159a84; }

/* Form card */
.form-container {
  background:white; padding:20px; border-radius:8px; box-shadow:0 0 10px #ccc;
  width:100%;
  max-width:900px;
}
.field { margin-bottom:10px; }
label { font-weight:bold; display:inline-block; width:240px; vertical-align:top; }

/* Header box */
.header-box { display:flex; justify-content:space-between; margin-bottom:20px; }
.logo-box, .photo-box { width:140px; height:120px; border:1px solid #aaa; padding:5px; background:#f9f9f9; }
.photo-box img, .logo-box img { width:100%; height:100%; object-fit:cover; }

/* Undertaking and signature */
.undertaking { margin-top:30px; border-top:1px dashed #aaa; padding-top:15px; font-style:italic; font-size:14px; }
.signature { text-align:right; margin-top:60px; font-weight:bold; }
.print-btn { text-align:center; margin-top:20px; }

/* Footer */
.footer {
  background:#333; color:white; text-align:center; padding:15px;
  position:fixed; bottom:0; left:0; width:100%; font-weight:bold;
}

/* Print styles for perfect A4 */
@media print {
  body, html { margin:0; padding:0; }
  .navbar, .side-nav, .toggle-arrow, .print-btn, .footer, .search-box { display:none !important; }

  .container { 
    margin:0; 
    padding:0; 
    width:210mm; 
    min-height:297mm; 
    display:block;
  }

  .form-container { 
    width:100%; 
    max-width:none;
    padding:20mm; 
    box-shadow:none; 
    border:1px solid #999; 
    page-break-inside: avoid;
    box-sizing:border-box;
  }

  h2, h3, .field { font-size:12pt; }
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

<div class="container" id="mainContent">
  <div class="search-box">
    <form method="GET">
      <input type="text" name="student_id" placeholder="Enter Student ID" required />
      <button type="submit">Load Form</button>
    </form>
  </div>

<?php if ($data): ?>
  <div class="form-container">
    <div class="header-box">
      <div class="logo-box">
        <img src="uploads/logo.png" alt="Logo" />
      </div>
      <div class="photo-box">
        <img src="?show_image=<?= urlencode($data["profile_image"]) ?>" alt="Profile Photo" />
      </div>
    </div>

    <h2>Admission Form</h2>
    <h3>Personal Information</h3>
    <div class="field"><label>Reg. No:</label> <?= htmlspecialchars($data["student_id"]) ?></div>
    <div class="field"><label>Course Name:</label> <?= htmlspecialchars($data["course_category"]) ?></div>
    <div class="field"><label>Date of Issue:</label> <?= htmlspecialchars($data["issue_date"]) ?></div>
    <div class="field"><label>Name:</label> <?= htmlspecialchars($data["name"]) ?></div>
    <div class="field"><label>Father’s Name:</label> <?= htmlspecialchars($data["father_name"]) ?></div>
    <div class="field"><label>Mother’s Name:</label> <?= htmlspecialchars($data["mother_name"]) ?></div>
    <div class="field"><label>DOB:</label> <?= htmlspecialchars($data["dob"]) ?></div>
    <div class="field"><label>Gender:</label>
      <?= $data["gender"]==="Male"?"☑ Male ☐ Female":"☐ Male ☑ Female" ?>
    </div>
    <div class="field"><label>Marital Status:</label> <?= htmlspecialchars($data["marital_status"]) ?></div>
    <div class="field"><label>Religion:</label> <?= htmlspecialchars($data["religion"]) ?></div>

    <h3>Contact Information</h3>
    <div class="field"><label>Email:</label> <?= htmlspecialchars($data["email"]) ?></div>
    <div class="field"><label>Phone:</label> <?= htmlspecialchars($data["phone_number"]) ?></div>
    <div class="field"><label>Present Address:</label> <?= htmlspecialchars($data["present_address"]) ?></div>
    <div class="field"><label>Permanent Address:</label> <?= htmlspecialchars($data["permanent_address"]) ?></div>

    <h3>Identification</h3>
    <div class="field"><label>ID Type:</label> <?= htmlspecialchars($data["id_type"]) ?></div>
    <div class="field"><label>NID/Birth No:</label> <?= htmlspecialchars($data["nid_birth_id"]) ?></div>

    <div class="undertaking">
      <strong>UNDERTAKING</strong><br>
      A. I hereby declare that the information provided is true and correct.<br>
      B. I agree to abide by the rules and regulations of the institute.<br>
      C. I understand that any false information may lead to cancellation of admission.
    </div>

    <div class="signature">Signature: ____________________________</div>
    <div class="print-btn">
      <button onclick="window.print()">Print This Form</button>
    </div>
  </div>
<?php elseif($student_id): ?>
  <p style="text-align:center; color:red;">Student ID not found. Please try again.</p>
<?php endif; ?>
</div>

<div class="footer">&copy; <?= date("Y") ?> Freelancing Student Management System | All Rights Reserved</div>

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
