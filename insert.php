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

// Insert logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST["student_id"];
    $name = $_POST["name"];
    $father_name = $_POST["father_name"];
    $mother_name = $_POST["mother_name"];
    $dob = $_POST["dob"];
    $issue_date = $_POST["issue_date"];
    $present_address = $_POST["present_address"];
    $permanent_address = $_POST["permanent_address"];
    $id_type = $_POST["id_type"] ?? '';
    $nid_birth_id = $_POST["nid_birth_id"];
    $email = $_POST["email"];
    $course_start_date = $_POST["course_start_date"];
    $course_end_date = $_POST["course_end_date"];
    $course_category = $_POST["course_category"];
    $course_fee = $_POST["course_fee"];
    $paid_fee = $_POST["paid_fee"];
    $phone_number = $_POST["phone_number"];
    $gender = $_POST["gender"];
    $marital_status = $_POST["marital_status"];
    $occupation = $_POST["occupation"];
    $religion = $_POST["religion"];
    $country = $_POST["country"];
    $city = $_POST["city"];
    $district = $_POST["district"];

    // File upload
    $profile_image = $_FILES["profile_image"]["name"];
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($profile_image);
    move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file);

    $sql = "INSERT INTO students (
        student_id, name, father_name, mother_name, dob, issue_date,
        present_address, permanent_address, id_type, nid_birth_id, email,
        course_start_date, course_end_date, course_category, course_fee, paid_fee, profile_image, phone_number,
        gender, marital_status, occupation, religion, country, city, district
    ) VALUES (
        '$student_id', '$name', '$father_name', '$mother_name', '$dob', '$issue_date',
        '$present_address', '$permanent_address', '$id_type', '$nid_birth_id', '$email',
        '$course_start_date', '$course_end_date', '$course_category', '$course_fee', '$paid_fee', '$profile_image', '$phone_number',
        '$gender', '$marital_status', '$occupation', '$religion', '$country', '$city', '$district'
    )";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Student record added successfully!'); window.location.href='insert.php';</script>";
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
<title>Insert Student</title>
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

    /* Container */
    .main-wrapper {
      display: flex;
      justify-content: center;
      padding-top: 140px;
      padding-bottom: 120px;
      width: 100%;
    }
    .container {
      width: 700px;
      background-color: white;
      border-radius: 8px;
      box-shadow: 2px 2px 10px rgba(0,0,0,0.1);
      padding: 20px;
      transition: margin-left 0.3s ease;
    }
    .container.collapsed { margin-left: 0; }

    form {
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    input, select, textarea, button {
      width: 90%;
      margin: 10px 0;
      padding: 10px;
    }
    button {
      background-color: #333;
      color: white;
      border: none;
      cursor: pointer;
    }
    button:hover { background-color: #555; }

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
<div class="main-wrapper">
  <div class="container" id="mainContent">
    <h2 style="text-align: center;">Insert Student Record</h2>
    <form action="insert.php" method="POST" enctype="multipart/form-data">
      <input type="number" name="student_id" placeholder="Student ID" required />
      <input type="text" name="name" placeholder="Name" required />
      <input type="text" name="father_name" placeholder="Father's Name" required />
      <input type="text" name="mother_name" placeholder="Mother's Name" required />
      <label>Issue Date</label>
      <input type="date" name="issue_date" required />
      <label>Date Of Birth</label>
      <input type="date" name="dob" required />
      <textarea name="present_address" placeholder="Present Address" required></textarea>
      <textarea name="permanent_address" placeholder="Permanent Address" required></textarea>
      <label><input type="radio" name="id_type" value="NID" required /> NID</label>
      <label><input type="radio" name="id_type" value="Birth ID" required /> Birth ID</label>
      <input type="text" name="nid_birth_id" placeholder="Enter ID Number" required />
      <input type="email" name="email" placeholder="Email" required />
      <input type="text" name="phone_number" placeholder="Phone Number" required />
      <select name="gender" required>
        <option value="">Select Gender</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Other">Other</option>
      </select>
      <select name="marital_status" required>
        <option value="">Select Marital Status</option>
        <option value="Single">Single</option>
        <option value="Married">Married</option>
        <option value="Divorced">Divorced</option>
        <option value="Widowed">Widowed</option>
      </select>
      <input type="text" name="occupation" placeholder="Occupation" required />
      <input type="text" name="religion" placeholder="Religion" required />
      <input type="text" name="country" placeholder="Country" required />
      <input type="text" name="city" placeholder="City" required />
      <input type="text" name="district" placeholder="District" required />
      <label>Course Start Date</label>
      <input type="date" name="course_start_date" required />
      <label>Course End Date</label>
      <input type="date" name="course_end_date" required />
      <select name="course_category" required>
        <option value="">Select Course Category</option>
        <option value="Graphic Design">Graphic Design</option>
        <option value="Video Editing">Video Editing</option>
        <option value="Social Media Marketing">Social Media Marketing</option>
        <option value="Digital Marketing">Digital Marketing</option>
        <option value="Office Application">Office Application</option>
      </select>
      <input type="number" name="course_fee" placeholder="Course Fee" required />
      <input type="number" name="paid_fee" placeholder="Paid Fee" required />
      <input type="file" name="profile_image" required />
      <button type="submit">Insert Student</button>
    </form>
  </div>
</div>

<!-- Footer -->
<div class="footer">
  &copy; <?php echo date("Y"); ?> Freelancing Students Management System | All Rights Reserved
</div>

<!-- Script -->
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
