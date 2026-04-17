<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

require_once 'config.php';


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
    $start = strtotime($_POST["course_start_date"]);
    $end   = strtotime($_POST["course_end_date"]);
    if ($end < $start) {
        echo "<script>alert('Course End Date cannot be earlier than Start Date.'); window.history.back();</script>";
        exit;
    }

    $fields = [
        "student_id", "name", "father_name", "mother_name",
        "dob", "issue_date", "present_address", "permanent_address",
        "id_type", "nid_birth_id", "email", "phone_number",
        "gender", "marital_status", "occupation", "religion",
        "country", "city", "district",
        "course_start_date", "course_end_date", "course_category",
        "course_fee", "paid_fee"
    ];

    $updates = [];
    foreach ($fields as $field) {
        $val = $conn->real_escape_string($_POST[$field]);
        $updates[] = "`$field` = '$val'";
    }

    $profile_image = $student['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "uploads/";
        $image_name = time() . "_" . basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $image_name;
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $profile_image = $image_name;
            if (!empty($student['profile_image']) && file_exists($target_dir . $student['profile_image'])) {
                unlink($target_dir . $student['profile_image']);
            }
            $updates[] = "`profile_image` = '" . $conn->real_escape_string($profile_image) . "'";
        }
    }

    $original_id = $conn->real_escape_string($_POST["original_student_id"]);
    $sql = "UPDATE students SET " . implode(", ", $updates) . ", last_updated = NOW() WHERE student_id = '$original_id'";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Student record updated successfully!'); window.location.href = 'report.php';</script>";
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Student — AR TECH SOLUTION</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<style>
    body {
        background-image: url('<?php echo $bg_image; ?>');
        background-size: cover;
        background-attachment: fixed;
        background-position: center;
        font-family: var(--sans);
    }
    body.dark-mode {
        --bg: rgba(0,0,0,0.9);
        --glass: rgba(0,0,0,0.5);
        --glass-border: rgba(255,255,255,0.1);
        --text: #e0e0e0;
    }
    body.dark-mode::before {
        background: rgba(0,0,0,0.85);
    }
</style>
<style>
:root {
    --bg: rgba(8,12,24,0.82);
    --glass: rgba(255,255,255,0.07);
    --glass-border: rgba(255,255,255,0.13);
    --glass-hover: rgba(255,255,255,0.13);
    --accent: #00e5c8;
    --accent2: #7b5ea7;
    --accent3: #ff6b6b;
    --accent4: #ffd166;
    --accent5: #06d6a0;
    --text: #e8eaf0;
    --muted: rgba(200,210,230,0.55);
    --card-radius: 18px;
    --sans: 'Plus Jakarta Sans', sans-serif;
    --mono: 'Space Grotesk', sans-serif;
    --nav-h: 64px;
    --sidebar-w: 230px;
    --shadow: 0 8px 32px rgba(0,0,0,0.35);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body::before {
    content: '';
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg,rgba(8,10,30,0.88) 0%,rgba(15,20,50,0.78) 50%,rgba(5,15,35,0.85) 100%);
    z-index: 0;
    pointer-events: none;
}

/* TOP NAV */
.topnav {
    position: fixed; top: 0; left: 0; right: 0; height: var(--nav-h);
    background: rgba(8,10,28,0.85);
    backdrop-filter: blur(18px);
    border-bottom: 1px solid var(--glass-border);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 24px;
    z-index: 1100;
}
.topnav-brand {
    display: flex; align-items: center; gap: 12px;
    font-family: var(--mono); font-size: 18px; font-weight: 700;
    letter-spacing: 0.5px; color: #fff;
}
.topnav-brand span { color: var(--accent); }
.brand-dot { width: 8px; height: 8px; background: var(--accent); border-radius: 50%; animation: pulse 2s infinite; }
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.4)} }
.topnav-right { display: flex; align-items: center; gap: 14px; }
.topnav-time { font-family: var(--mono); font-size: 13px; color: var(--muted); }
.logout-btn {
    background: linear-gradient(135deg,#e74c3c,#c0392b);
    color: #fff; padding: 7px 20px; border-radius: 40px;
    text-decoration: none; font-size: 13px; font-weight: 700;
    transition: opacity .2s;
}
.logout-btn:hover { opacity: .85; }
.hamburger {
    background: none; border: none; color: var(--text);
    font-size: 22px; cursor: pointer; display: none; padding: 4px;
}

/* SIDEBAR */
.sidebar {
    position: fixed; top: var(--nav-h); left: 0;
    width: var(--sidebar-w); height: calc(100vh - var(--nav-h));
    background: #08121e;
    border-right: 1px solid var(--glass-border);
    overflow-y: auto;
    z-index: 1050;
    transition: transform .3s cubic-bezier(.4,0,.2,1);
    padding-bottom: 40px;
}
.sidebar.collapsed { transform: translateX(-100%); }
.sidebar a, .menu-toggle {
    display: flex; align-items: center; gap: 10px;
    color: var(--muted); text-decoration: none;
    padding: 11px 20px; font-size: 13.5px; font-weight: 500;
    border-left: 3px solid transparent;
    transition: all .2s;
    cursor: pointer;
}
.sidebar a:hover, .menu-toggle:hover { color: #fff; background: var(--glass); border-left-color: var(--accent); }
.sidebar a.active { color: var(--accent); border-left-color: var(--accent); background: rgba(0,229,200,0.07); }
.submenu { display: none; flex-direction: column; background: rgba(0,0,0,0.2); }
.submenu a { padding: 9px 20px 9px 38px; font-size: 13px; }
.menu-group.open .submenu { display: flex; }
.menu-arrow { margin-left: auto; font-size: 11px; transition: transform .25s; }
.menu-group.open .menu-arrow { transform: rotate(180deg); }
.sidebar-divider { height: 1px; background: var(--glass-border); margin: 10px 16px; }

.sidebar-toggle-pill {
    position: fixed; top: calc(var(--nav-h) + 16px); left: var(--sidebar-w);
    width: 24px; height: 44px; background: var(--accent);
    border-radius: 0 10px 10px 0;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; z-index: 1060; font-size: 13px; color: #000;
    font-weight: 900; transition: left .3s cubic-bezier(.4,0,.2,1);
}
.sidebar-toggle-pill:hover { background: #00c9b0; }
.sidebar-toggle-pill.collapsed { left: 0; }

/* MAIN CONTENT */
.main {
    margin-left: var(--sidebar-w);
    padding: calc(var(--nav-h) + 24px) 24px 80px;
    position: relative; z-index: 1;
    transition: margin-left .3s cubic-bezier(.4,0,.2,1);
    min-height: 100vh;
}
.main.collapsed { margin-left: 0; }

/* EDIT FORM CARD */
.edit-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 28px;
    max-width: 1200px;
    margin: 0 auto;
}
.edit-card h2 {
    font-family: var(--mono);
    font-size: 22px;
    color: var(--accent);
    margin-bottom: 24px;
    text-align: center;
}
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}
.form-group {
    display: flex;
    flex-direction: column;
}
.form-group label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--muted);
    margin-bottom: 6px;
}
.form-group input, .form-group select, .form-group textarea {
    width: 100%;
    padding: 10px 12px;
    background: rgba(255,255,255,0.08);
    border: 1px solid var(--glass-border);
    border-radius: 10px;
    color: var(--text);
    font-family: var(--sans);
    font-size: 14px;
    outline: none;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: var(--accent);
    background: rgba(255,255,255,0.12);
}
.form-group textarea {
    resize: vertical;
    min-height: 80px;
}
.current-photo {
    margin-top: 10px;
    max-width: 80px;
    border-radius: 10px;
    border: 1px solid var(--glass-border);
}
.form-actions {
    margin-top: 28px;
    display: flex;
    gap: 16px;
    justify-content: center;
    align-items: center;
}
.submit-btn {
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    color: #000;
    font-weight: 700;
    padding: 10px 24px;
    border: none;
    border-radius: 40px;
    font-size: 14px;
    cursor: pointer;
}
.submit-btn:hover { opacity: .85; }
.cancel-btn {
    color: var(--accent3);
    text-decoration: none;
    font-weight: 600;
}
.cancel-btn:hover { text-decoration: underline; }

/* FOOTER */
.footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(6,8,20,0.9);
    backdrop-filter: blur(10px);
    border-top: 1px solid var(--glass-border);
    text-align: center;
    padding: 12px;
    font-size: 12.5px;
    color: var(--muted);
    z-index: 900;
}

/* RESPONSIVE */
@media (max-width: 700px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.mobile-open { transform: translateX(0); }
    .sidebar-toggle-pill { display: none; }
    .hamburger { display: block; }
    .main { margin-left: 0 !important; padding-left: 16px; padding-right: 16px; }
    .form-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body class="<?php echo $dark_mode ? 'dark-mode' : ''; ?>">

<!-- TOP NAVIGATION -->
<nav class="topnav">
    <div style="display:flex;align-items:center;gap:14px;">
        <button class="hamburger" id="hamburgerBtn">☰</button>
        <div class="topnav-brand">
            <div class="brand-dot"></div>
            <span>AR TECH</span> SOLUTION
        </div>
    </div>
    <div class="topnav-right">
        <div class="topnav-time" id="liveClock"></div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<!-- SIDEBAR (dynamic labels from config) -->
<?php
include 'navigation.php';
?>
<div class="sidebar-toggle-pill" id="sidebarToggle">◀</div>

<!-- MAIN CONTENT -->
<main class="main" id="mainContent">
    <div class="edit-card">
        <h2>✏️ Edit Student (ID: <?php echo htmlspecialchars($student['student_id'] ?? ''); ?>)</h2>
        <?php if ($student): ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="original_student_id" value="<?php echo htmlspecialchars($student["student_id"]); ?>">
            <div class="form-grid">
                <div class="form-group"><label>Student ID</label><input type="text" name="student_id" value="<?php echo htmlspecialchars($student["student_id"]); ?>" required></div>
                <div class="form-group"><label>Name</label><input type="text" name="name" value="<?php echo htmlspecialchars($student["name"]); ?>" required></div>
                <div class="form-group"><label>Father's Name</label><input type="text" name="father_name" value="<?php echo htmlspecialchars($student["father_name"]); ?>" required></div>
                <div class="form-group"><label>Mother's Name</label><input type="text" name="mother_name" value="<?php echo htmlspecialchars($student["mother_name"]); ?>" required></div>
                <div class="form-group"><label>Issue Date</label><input type="date" name="issue_date" value="<?php echo htmlspecialchars($student["issue_date"]); ?>" required></div>
                <div class="form-group"><label>Date of Birth</label><input type="date" name="dob" value="<?php echo htmlspecialchars($student["dob"]); ?>" required></div>
                <div class="form-group"><label>Present Address</label><textarea name="present_address" required><?php echo htmlspecialchars($student["present_address"]); ?></textarea></div>
                <div class="form-group"><label>Permanent Address</label><textarea name="permanent_address" required><?php echo htmlspecialchars($student["permanent_address"]); ?></textarea></div>
                <div class="form-group"><label>ID Type</label><select name="id_type" required><option value="">Select ID Type</option><option value="NID" <?php if ($student["id_type"] === "NID") echo "selected"; ?>>NID</option><option value="Birth ID" <?php if ($student["id_type"] === "Birth ID") echo "selected"; ?>>Birth ID</option></select></div>
                <div class="form-group"><label>ID Number</label><input type="text" name="nid_birth_id" value="<?php echo htmlspecialchars($student["nid_birth_id"]); ?>" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($student["email"]); ?>" required></div>
                <div class="form-group"><label>Phone Number</label><input type="text" name="phone_number" value="<?php echo htmlspecialchars($student["phone_number"]); ?>" required></div>
                <div class="form-group"><label>Gender</label><select name="gender" required><option value="">Select Gender</option><option value="Male" <?php if ($student["gender"] === "Male") echo "selected"; ?>>Male</option><option value="Female" <?php if ($student["gender"] === "Female") echo "selected"; ?>>Female</option><option value="Other" <?php if ($student["gender"] === "Other") echo "selected"; ?>>Other</option></select></div>
                <div class="form-group"><label>Marital Status</label><select name="marital_status" required><option value="">Select Marital Status</option><option value="Single" <?php if ($student["marital_status"] === "Single") echo "selected"; ?>>Single</option><option value="Married" <?php if ($student["marital_status"] === "Married") echo "selected"; ?>>Married</option><option value="Divorced" <?php if ($student["marital_status"] === "Divorced") echo "selected"; ?>>Divorced</option><option value="Widowed" <?php if ($student["marital_status"] === "Widowed") echo "selected"; ?>>Widowed</option></select></div>
                <div class="form-group"><label>Occupation</label><input type="text" name="occupation" value="<?php echo htmlspecialchars($student["occupation"]); ?>" required></div>
                <div class="form-group"><label>Religion</label><input type="text" name="religion" value="<?php echo htmlspecialchars($student["religion"]); ?>" required></div>
                <div class="form-group"><label>Country</label><input type="text" name="country" value="<?php echo htmlspecialchars($student["country"]); ?>" required></div>
                <div class="form-group"><label>City</label><input type="text" name="city" value="<?php echo htmlspecialchars($student["city"]); ?>" required></div>
                <div class="form-group"><label>District</label><input type="text" name="district" value="<?php echo htmlspecialchars($student["district"]); ?>" required></div>
                <div class="form-group"><label>Course Start Date</label><input type="date" name="course_start_date" value="<?php echo htmlspecialchars($student["course_start_date"]); ?>" required></div>
                <div class="form-group"><label>Course End Date</label><input type="date" name="course_end_date" value="<?php echo htmlspecialchars($student["course_end_date"]); ?>" required></div>
                <div class="form-group"><label>Course Category</label><select name="course_category" required><option value="">Select Course Category</option><option value="Graphic Design" <?php if ($student["course_category"] === "Graphic Design") echo "selected"; ?>>Graphic Design</option><option value="Video Editing" <?php if ($student["course_category"] === "Video Editing") echo "selected"; ?>>Video Editing</option><option value="Social Media Marketing" <?php if ($student["course_category"] === "Social Media Marketing") echo "selected"; ?>>Social Media Marketing</option><option value="Digital Marketing" <?php if ($student["course_category"] === "Digital Marketing") echo "selected"; ?>>Digital Marketing</option><option value="Office Application" <?php if ($student["course_category"] === "Office Application") echo "selected"; ?>>Office Application</option></select></div>
                <div class="form-group"><label>Course Fee (৳)</label><input type="number" step="0.01" name="course_fee" value="<?php echo htmlspecialchars($student["course_fee"]); ?>" required></div>
                <div class="form-group"><label>Paid Fee (৳)</label><input type="number" step="0.01" name="paid_fee" value="<?php echo htmlspecialchars($student["paid_fee"]); ?>" required></div>
                <div class="form-group"><label>Profile Image</label><input type="file" name="profile_image" accept="image/*"><?php if (!empty($student['profile_image'])): ?><img src="uploads/<?php echo htmlspecialchars($student['profile_image']); ?>" class="current-photo"><?php endif; ?></div>
            </div>
            <div class="form-actions">
                <button type="submit" class="submit-btn">💾 Save Changes</button>
                <a href="student_list.php" class="cancel-btn">Cancel</a>
            </div>
        </form>
        <?php else: ?>
            <p style="color: var(--accent3); text-align:center;">Student not found or ID missing.</p>
        <?php endif; ?>
    </div>
</main>

<div class="footer">
    &copy; <?php echo date("Y"); ?> AR TECH SOLUTION — Freelancing Student Management System
</div>

<script>
// Sidebar toggle
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');
const mainContent = document.getElementById('mainContent');
if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        toggleBtn.classList.toggle('collapsed');
        mainContent.classList.toggle('collapsed');
        toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '▶' : '◀';
    });
}
const hamburger = document.getElementById('hamburgerBtn');
if (hamburger) {
    hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('mobile-open');
    });
}
document.querySelectorAll('.menu-toggle').forEach(toggle => {
    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const group = toggle.closest('.menu-group');
        if (group) group.classList.toggle('open');
    });
});
function updateClock() {
    const clockEl = document.getElementById('liveClock');
    if (clockEl) {
        const now = new Date();
        clockEl.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
}
updateClock();
setInterval(updateClock, 1000);
</script>
</body>
</html>