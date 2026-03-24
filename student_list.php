<?php
session_start();

// ✅ 1. Admin session check
if (!isset($_SESSION["email"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "freelancing";

$conn = new mysqli($servername, $username, "", $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$search = "";
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["search"])) {
    $search = $_GET["search"];
    $sql = "SELECT * FROM students WHERE name LIKE '%$search%' OR email LIKE '%$search%' OR student_id LIKE '%$search%'";
} else {
    $sql = "SELECT * FROM students";
}

$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student List</title>
<style>
* { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f4f4; }

    /* Navbar */
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
    .side-nav.collapsed { transform: translateX(-220px); }
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
    .toggle-arrow.collapsed { left: 0; }

/* Menu Group */
.menu-group { width: 100%; }
.submenu { display: none; flex-direction: column; background-color: #34495e; }
.submenu a { color: white; padding: 10px 40px; font-weight: normal; }
.menu-group.active .submenu { display: flex; }

/* Horizontal submenu */
.horizontal-submenu { flex-direction: row; flex-wrap: wrap; gap: 5px; }

/* Main content */
.main-wrapper {
  margin-left: 220px; /* space for sidebar */
  padding: 130px 30px 100px;
}

/* Search box */
.search-box { margin-bottom: 20px; text-align: center; }
.search-box input { padding: 10px; width: 40%; border-radius: 5px; border: 1px solid #ccc; }
.search-box button { padding: 10px 15px; border: none; background-color: #333; color: white; border-radius: 5px; cursor: pointer; }
.search-box button:hover { background-color: #555; }

/* Table */
table { width: 100%; border-collapse: collapse; background-color: white; margin-top: 20px; }
th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
th { background-color: #333; color: white; }
img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }

/* Edit button */
.edit-btn {
  background-color: #1abc9c;
  color: white;
  padding: 5px 12px;
  text-decoration: none;
  border-radius: 4px;
  font-size: 14px;
  transition: 0.3s;
  display: inline-block;
}
.edit-btn:hover {
  background-color: #16a085;
}

/* Footer */
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
    <a href="routine_generator.php">🕒 Routine</a>
  </div>

  <div class="toggle-arrow" id="toggleBtn">◀</div>

<!-- Main content -->
<div class="main-wrapper" id="mainWrapper">
  <div class="search-box">
    <form method="GET">
      <input type="text" name="search" placeholder="Search by ID, Name, or Email" value="<?php echo htmlspecialchars($search); ?>">
      <button type="submit">Search</button>
    </form>
  </div>

  <h2>Student List</h2>
  <table>
    <tr>
      <th>Student ID</th>
      <th>Photo</th>
      <th>Date of Issue</th>
      <th>Course Start</th>
      <th>Course End</th>
      <th>Name</th>
      <th>Father's Name</th>
      <th>Mother's Name</th>
      <th>Date of Birth</th>
      <th>Phone</th>
      <th>Gender</th>
      <th>Marital Status</th>
      <th>Occupation</th>
      <th>Religion</th>
      <th>Country</th>
      <th>Present Address</th>
      <th>Permanent Address</th>
      <th>City</th>
      <th>District</th>
      <th>ID Type</th>
      <th>ID Number</th>
      <th>Course Category</th>
      <th>Course Fee</th>
      <th>Paid Fee</th>
      <th>Due Fee</th>
      <th>Email</th>
      <th>Action</th> <!-- NEW COLUMN -->
    </tr>
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row["student_id"]) . "</td>";
            echo "<td><img src='uploads/" . htmlspecialchars($row["profile_image"]) . "' alt='Profile'></td>";
            echo "<td>" . htmlspecialchars($row["issue_date"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["course_start_date"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["course_end_date"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["father_name"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["mother_name"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["dob"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["phone_number"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["gender"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["marital_status"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["occupation"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["religion"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["country"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["present_address"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["permanent_address"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["city"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["district"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["id_type"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["nid_birth_id"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["course_category"]) . "</td>";
            echo "<td>৳ " . number_format($row["course_fee"], 2) . "</td>";
            echo "<td>৳ " . number_format($row["paid_fee"], 2) . "</td>";
            echo "<td>৳ " . number_format($row["course_fee"] - $row["paid_fee"], 2) . "</td>";
            echo "<td>" . htmlspecialchars($row["email"]) . "</td>";
            // Edit button with student_id parameter
            echo "<td><a href='edit.php?student_id=" . urlencode($row["student_id"]) . "' class='edit-btn'>Edit</a></td>";
            echo "</tr>";
        }
    } else {
        // colspan increased by 1 (from 26 to 27) because we added Action column
        echo "<tr><td colspan='27'>No student records found.</td></tr>";
    }


    ?>
  </table>
</div>

<!-- Footer -->
<div class="footer">
  &copy; <?php echo date("Y"); ?> Freelancing Students Management System | All Rights Reserved
</div>

<!-- Script -->
<script>
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('toggleBtn');
const mainWrapper = document.getElementById('mainWrapper');

toggleBtn.addEventListener('click', () => {
  sidebar.classList.toggle('collapsed');
  toggleBtn.classList.toggle('collapsed');

  // Adjust main content smoothly
  if (sidebar.classList.contains('collapsed')) {
    mainWrapper.style.marginLeft = "0";
    toggleBtn.textContent = "▶";
  } else {
    mainWrapper.style.marginLeft = "220px";
    toggleBtn.textContent = "◀";
  }
});

// Menu toggle (expand/collapse submenu)
document.querySelectorAll('.menu-toggle').forEach(toggle => {
  toggle.addEventListener('click', () => {
    toggle.parentElement.classList.toggle('active');
  });
});


</script>


</body>
</html>