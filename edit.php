<?php


$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "freelancing";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch existing student record
$student = null;
if (isset($_GET["student_id"])) {
    $id     = $conn->real_escape_string($_GET["student_id"]);
    $result = $conn->query("SELECT * FROM students WHERE student_id = '$id'");
    if ($result && $result->num_rows === 1) {
        $student = $result->fetch_assoc();
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["original_student_id"])) {
    // Validate course dates
    $start = strtotime($_POST["course_start_date"]);
    $end   = strtotime($_POST["course_end_date"]);
    if ($end < $start) {
        echo "<script>
                alert('Course End Date cannot be earlier than Start Date.');
                window.history.back();
              </script>";
        exit;
    }

    // Fields to update (excluding profile_image, handled separately)
    $fields = [
        "student_id", "name", "father_name", "mother_name",
        "dob", "issue_date", "present_address", "permanent_address",
        "id_type", "nid_birth_id", "email", "phone_number",
        "gender", "marital_status", "occupation", "religion",
        "country", "city", "district",
        "course_start_date", "course_end_date", "course_category",
        "course_fee", "paid_fee"
    ];

    // Start building SET clauses
    $updates = [];
    foreach ($fields as $field) {
        $val = $conn->real_escape_string($_POST[$field]);
        $updates[] = "`$field` = '$val'";
    }

    // Handle profile image upload
    $profile_image = $student['profile_image']; // keep old by default
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "uploads/";
        $image_name = time() . "_" . basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $image_name;
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $profile_image = $image_name;
            // Delete old image file if it exists and is not the default placeholder
            if (!empty($student['profile_image']) && file_exists($target_dir . $student['profile_image'])) {
                unlink($target_dir . $student['profile_image']);
            }
            // Add profile_image to the SET clause
            $updates[] = "`profile_image` = '" . $conn->real_escape_string($profile_image) . "'";
        }
    }

    // Perform update
    $original_id = $conn->real_escape_string($_POST["original_student_id"]);
    $sql         = "UPDATE `students` 
                    SET " . implode(", ", $updates) . ", `last_updated` = NOW() 
                    WHERE `student_id` = '$original_id'";

    if ($conn->query($sql) === TRUE) {
        echo "<script>
                alert('Student record updated successfully!');
                window.location.href = 'report.php';
              </script>";
        exit;
    } else {
        echo "<script>alert('Update error: " . $conn->error . "');</script>";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Student</title>
<!-- Same styles as student_list.php (copied here for standalone use) -->
<style>
* { box-sizing: border-box; }
body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f4f4; }
.navbar { background-color: #333; color: white; padding: 15px 20px; font-size: 24px; display: flex; justify-content: center; align-items: center; position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; }
.logout-btn { position: absolute; right: 20px; background: linear-gradient(135deg, #ff4d4d, #cc0000); color: white; padding: 8px 20px; text-decoration: none; border-radius: 25px; font-size: 15px; box-shadow: 0 3px 6px rgba(0,0,0,0.2); transition: all 0.3s ease; }
.logout-btn:hover { background: linear-gradient(135deg, #ff6666, #e60000); transform: scale(1.05); }
.side-nav { position: fixed; top: 60px; left: 0; width: 220px; height: calc(100% - 60px); background-color: #2c3e50; padding-top: 20px; z-index: 999; display: flex; flex-direction: column; align-items: flex-start; box-shadow: 2px 0 5px rgba(0,0,0,0.2); transition: transform 0.3s ease; overflow-y: auto; }
.side-nav.collapsed { transform: translateX(-220px); }
.side-nav a, .menu-toggle { color: white; text-decoration: none; padding: 12px 25px; width: 100%; font-weight: bold; transition: background 0.3s ease; border-left: 4px solid transparent; cursor: pointer; }
.side-nav a:hover, .menu-toggle:hover { background-color: #34495e; border-left: 4px solid #1abc9c; }
.menu-group { width: 100%; }
.submenu { display: none; flex-direction: column; background-color: #34495e; }
.submenu a { color: white; padding: 10px 40px; text-decoration: none; font-weight: normal; transition: background 0.3s ease; }
.submenu a:hover { background-color: #3d566e; }
.menu-group.active .submenu { display: flex; }
.toggle-arrow { position: fixed; top: 70px; left: 220px; background-color: #1abc9c; color: white; padding: 6px 10px; border-radius: 0 5px 5px 0; cursor: pointer; z-index: 1001; font-size: 18px; transition: left 0.3s ease; }
.toggle-arrow.collapsed { left: 0; }
.main-wrapper { margin-left: 220px; padding: 130px 30px 100px; transition: margin-left 0.3s ease; }
.footer { background-color: #1a1a1a; color: white; text-align: center; padding: 15px; position: fixed; bottom: 0; left: 0; width: 100%; font-weight: bold; box-shadow: 0 -2px 5px rgba(0,0,0,0.2); }
.edit-form { background: white; padding: 30px; border-radius: 8px; max-width: 1200px; margin: 0 auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
.form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
.form-group { display: flex; flex-direction: column; }
.form-group label { font-weight: bold; margin-bottom: 5px; }
.form-group input, .form-group select, .form-group textarea { padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
.form-group textarea { resize: vertical; }
.current-photo { max-width: 100px; margin-top: 10px; }
.submit-btn { background-color: #1abc9c; color: white; padding: 12px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; margin-top: 20px; }
.submit-btn:hover { background-color: #16a085; }
.error { color: red; margin-bottom: 15px; }
</style>
</head>
<body>
<!-- Navbar (identical to student_list.php) -->
<div class="navbar">
    <strong>AR TECH SOLUTION</strong>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<!-- Sidebar (identical to student_list.php) -->
<div class="side-nav" id="sidebar">
    <a href="dashboard.php">📊 Dashboard</a>
    <div class="menu-group">
        <div class="menu-toggle">💵 Account ▾</div>
        <div class="submenu">
            <a href="account.php">Account Overview</a>
            <a href="account_report.php">Account Report</a>
            <a href="change_password.php">Change Password</a>
        </div>
    </div>
    <div class="menu-group">
        <div class="menu-toggle">👤 Student Information ▾</div>
        <div class="submenu horizontal-submenu">
            <a href="insert.php">Add Student</a>
            <a href="student_list.php">Total Student List</a>
            <a href="form_view.php">Student Form</a>
        </div>
    </div>
    <a href="delete.php">🗑️ Delete</a>
    <a href="report.php">📄 Report</a>
    <div class="menu-group">
        <div class="menu-toggle">💵 Payment ▾</div>
        <div class="submenu horizontal-submenu">
            <a href="invoice.php">Print Invoice</a>
            <a href="view_invoice.php">Verify Invoice</a>
            <a href="input_payment.php">Add Payment</a>
        </div>
    </div>
    <div class="menu-group">
        <div class="menu-toggle">📆 Attendance▾</div>
        <div class="submenu">
            <a href="attendance.php">Take Attendance</a>
            <a href="attendance_report.php">View Attendance Report</a>
        </div>
    </div>
    <div class="menu-group">
        <div class="menu-toggle">📜Certificate▾</div>
        <div class="submenu">
            <a href="upload_certificate.php">Upload Certificate</a>
            <a href="certificate_list.php">View Certificate</a>
        </div>
    </div>
    <a href="routine_generator.php">🕒 Routine</a>
</div>

<div class="toggle-arrow" id="toggleBtn">◀</div>

<!-- Main Content: Edit Form -->
<div class="main-wrapper" id="mainWrapper">
    <div class="edit-form">
        <h2>Edit Student (ID: <?php echo htmlspecialchars($student['student_id'] ?? ''); ?>)</h2>

        <?php if ($student): ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="original_student_id" value="<?php echo htmlspecialchars($student["student_id"]); ?>" />

            <div class="form-grid">
                <!-- Student ID -->
                <div class="form-group">
                    <label>Student ID</label>
                    <input type="text" name="student_id" value="<?php echo htmlspecialchars($student["student_id"]); ?>" required>
                </div>

                <!-- Name -->
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($student["name"]); ?>" required>
                </div>

                <!-- Father's Name -->
                <div class="form-group">
                    <label>Father's Name</label>
                    <input type="text" name="father_name" value="<?php echo htmlspecialchars($student["father_name"]); ?>" required>
                </div>

                <!-- Mother's Name -->
                <div class="form-group">
                    <label>Mother's Name</label>
                    <input type="text" name="mother_name" value="<?php echo htmlspecialchars($student["mother_name"]); ?>" required>
                </div>

                <!-- Issue Date -->
                <div class="form-group">
                    <label>Issue Date</label>
                    <input type="date" name="issue_date" value="<?php echo htmlspecialchars($student["issue_date"]); ?>" required>
                </div>

                <!-- Date of Birth -->
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" value="<?php echo htmlspecialchars($student["dob"]); ?>" required>
                </div>

                <!-- Present Address -->
                <div class="form-group">
                    <label>Present Address</label>
                    <textarea name="present_address" required><?php echo htmlspecialchars($student["present_address"]); ?></textarea>
                </div>

                <!-- Permanent Address -->
                <div class="form-group">
                    <label>Permanent Address</label>
                    <textarea name="permanent_address" required><?php echo htmlspecialchars($student["permanent_address"]); ?></textarea>
                </div>

                <!-- ID Type -->
                <div class="form-group">
                    <label>ID Type</label>
                    <select name="id_type" required>
                        <option value="">Select ID Type</option>
                        <option value="NID" <?php if ($student["id_type"] === "NID") echo "selected"; ?>>NID</option>
                        <option value="Birth ID" <?php if ($student["id_type"] === "Birth ID") echo "selected"; ?>>Birth ID</option>
                    </select>
                </div>

                <!-- ID Number -->
                <div class="form-group">
                    <label>ID Number</label>
                    <input type="text" name="nid_birth_id" value="<?php echo htmlspecialchars($student["nid_birth_id"]); ?>" required>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($student["email"]); ?>" required>
                </div>

                <!-- Phone Number -->
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" value="<?php echo htmlspecialchars($student["phone_number"]); ?>" required>
                </div>

                <!-- Gender -->
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?php if ($student["gender"] === "Male") echo "selected"; ?>>Male</option>
                        <option value="Female" <?php if ($student["gender"] === "Female") echo "selected"; ?>>Female</option>
                        <option value="Other" <?php if ($student["gender"] === "Other") echo "selected"; ?>>Other</option>
                    </select>
                </div>

                <!-- Marital Status -->
                <div class="form-group">
                    <label>Marital Status</label>
                    <select name="marital_status" required>
                        <option value="">Select Marital Status</option>
                        <option value="Single" <?php if ($student["marital_status"] === "Single") echo "selected"; ?>>Single</option>
                        <option value="Married" <?php if ($student["marital_status"] === "Married") echo "selected"; ?>>Married</option>
                        <option value="Divorced" <?php if ($student["marital_status"] === "Divorced") echo "selected"; ?>>Divorced</option>
                        <option value="Widowed" <?php if ($student["marital_status"] === "Widowed") echo "selected"; ?>>Widowed</option>
                    </select>
                </div>

                <!-- Occupation -->
                <div class="form-group">
                    <label>Occupation</label>
                    <input type="text" name="occupation" value="<?php echo htmlspecialchars($student["occupation"]); ?>" required>
                </div>

                <!-- Religion -->
                <div class="form-group">
                    <label>Religion</label>
                    <input type="text" name="religion" value="<?php echo htmlspecialchars($student["religion"]); ?>" required>
                </div>

                <!-- Country -->
                <div class="form-group">
                    <label>Country</label>
                    <input type="text" name="country" value="<?php echo htmlspecialchars($student["country"]); ?>" required>
                </div>

                <!-- City -->
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" value="<?php echo htmlspecialchars($student["city"]); ?>" required>
                </div>

                <!-- District -->
                <div class="form-group">
                    <label>District</label>
                    <input type="text" name="district" value="<?php echo htmlspecialchars($student["district"]); ?>" required>
                </div>

                <!-- Course Start Date -->
                <div class="form-group">
                    <label>Course Start Date</label>
                    <input type="date" name="course_start_date" value="<?php echo htmlspecialchars($student["course_start_date"]); ?>" required>
                </div>

                <!-- Course End Date -->
                <div class="form-group">
                    <label>Course End Date</label>
                    <input type="date" name="course_end_date" value="<?php echo htmlspecialchars($student["course_end_date"]); ?>" required>
                </div>

                <!-- Course Category -->
                <div class="form-group">
                    <label>Course Category</label>
                    <select name="course_category" required>
                        <option value="">Select Course Category</option>
                        <option value="Graphic Design" <?php if ($student["course_category"] === "Graphic Design") echo "selected"; ?>>Graphic Design</option>
                        <option value="Video Editing" <?php if ($student["course_category"] === "Video Editing") echo "selected"; ?>>Video Editing</option>
                        <option value="Social Media Marketing" <?php if ($student["course_category"] === "Social Media Marketing") echo "selected"; ?>>Social Media Marketing</option>
                        <option value="Digital Marketing" <?php if ($student["course_category"] === "Digital Marketing") echo "selected"; ?>>Digital Marketing</option>
                    </select>
                </div>

                <!-- Course Fee -->
                <div class="form-group">
                    <label>Course Fee (৳)</label>
                    <input type="number" step="0.01" name="course_fee" value="<?php echo htmlspecialchars($student["course_fee"]); ?>" required>
                </div>

                <!-- Paid Fee -->
                <div class="form-group">
                    <label>Paid Fee (৳)</label>
                    <input type="number" step="0.01" name="paid_fee" value="<?php echo htmlspecialchars($student["paid_fee"]); ?>" required>
                </div>

                <!-- Profile Image -->
                <div class="form-group">
                    <label>Profile Image</label>
                    <input type="file" name="profile_image" accept="image/*">
                    <?php if (!empty($student['profile_image'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($student['profile_image']); ?>" class="current-photo">
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="submit-btn">Save Changes</button>
            <a href="student_list.php" style="margin-left: 20px;">Cancel</a>
        </form>
        <?php else: ?>
            <p style="color: red; text-align: center;">Student not found or ID missing.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Footer -->
<div class="footer">
    &copy; <?php echo date("Y"); ?> Freelancing Students Management System | All Rights Reserved
</div>

<!-- Same toggle and menu scripts as student_list.php -->
<script>
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('toggleBtn');
const mainWrapper = document.getElementById('mainWrapper');

toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    toggleBtn.classList.toggle('collapsed');
    if (sidebar.classList.contains('collapsed')) {
        mainWrapper.style.marginLeft = "0";
        toggleBtn.textContent = "▶";
    } else {
        mainWrapper.style.marginLeft = "220px";
        toggleBtn.textContent = "◀";
    }
});

document.querySelectorAll('.menu-toggle').forEach(toggle => {
    toggle.addEventListener('click', () => {
        toggle.parentElement.classList.toggle('active');
    });
});
</script>
</body>
</html>