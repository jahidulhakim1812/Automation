<?php
// --- DEBUG: Show errors (remove after fixing) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// --- Load PHPMailer using the same path as your due‑payment script ---
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Check database connection ---
if (!isset($conn) || !$conn) {
    die("Database connection not established in config.php");
}

// --- CSRF protection ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- Logging function ---
function logError($msg) {
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    file_put_contents($logDir . 'error_log.txt', date('Y-m-d H:i:s') . ' - ' . $msg . PHP_EOL, FILE_APPEND);
}

// --- Process form submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF token check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token mismatch.");
    }

    // 1. Collect all inputs
    $name             = trim($_POST["name"] ?? '');
    $father_name      = trim($_POST["father_name"] ?? '');
    $mother_name      = trim($_POST["mother_name"] ?? '');
    $dob              = $_POST["dob"] ?? '';
    $issue_date       = $_POST["issue_date"] ?? '';
    $present_address  = trim($_POST["present_address"] ?? '');
    $permanent_address= trim($_POST["permanent_address"] ?? '');
    $id_type          = $_POST["id_type"] ?? '';
    $nid_birth_id     = trim($_POST["nid_birth_id"] ?? '');
    $email            = trim($_POST["email"] ?? '');
    $course_start_date= $_POST["course_start_date"] ?? '';
    $course_end_date  = $_POST["course_end_date"] ?? '';
    $course_category  = $_POST["course_category"] ?? '';
    $course_fee       = floatval($_POST["course_fee"] ?? 0);
    $paid_fee         = floatval($_POST["paid_fee"] ?? 0);
    $phone_number     = trim($_POST["phone_number"] ?? '');
    $gender           = $_POST["gender"] ?? '';
    $marital_status   = $_POST["marital_status"] ?? '';
    $occupation       = trim($_POST["occupation"] ?? '');
    $religion         = trim($_POST["religion"] ?? '');
    $country          = trim($_POST["country"] ?? '');
    $city             = trim($_POST["city"] ?? '');
    $district         = trim($_POST["district"] ?? '');

    // 2. Validate
    $errors = [];
    if (empty($name)) $errors[] = "Full Name is required.";
    if (empty($father_name)) $errors[] = "Father's Name is required.";
    if (empty($mother_name)) $errors[] = "Mother's Name is required.";
    if (empty($dob) || !strtotime($dob)) $errors[] = "Valid Date of Birth is required.";
    if (empty($issue_date) || !strtotime($issue_date)) $errors[] = "Valid Issue Date is required.";
    if (empty($present_address)) $errors[] = "Present Address is required.";
    if (empty($permanent_address)) $errors[] = "Permanent Address is required.";
    if (!in_array($id_type, ['NID', 'Birth ID'])) $errors[] = "ID Type must be NID or Birth ID.";
    if (empty($nid_birth_id)) $errors[] = "ID Number is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid Email is required.";
    if (empty($phone_number)) $errors[] = "Phone Number is required.";
    if (empty($gender) || !in_array($gender, ['Male','Female','Other'])) $errors[] = "Gender is required.";
    if (empty($marital_status) || !in_array($marital_status, ['Single','Married','Divorced','Widowed'])) $errors[] = "Marital Status is required.";
    if (empty($occupation)) $errors[] = "Occupation is required.";
    if (empty($religion)) $errors[] = "Religion is required.";
    if (empty($country)) $errors[] = "Country is required.";
    if (empty($city)) $errors[] = "City is required.";
    if (empty($district)) $errors[] = "District is required.";
    if (empty($course_start_date) || !strtotime($course_start_date)) $errors[] = "Valid Course Start Date is required.";
    if (empty($course_end_date) || !strtotime($course_end_date)) $errors[] = "Valid Course End Date is required.";
    if (empty($course_category)) $errors[] = "Course Category is required.";
    if ($course_fee <= 0) $errors[] = "Course Fee must be a positive number.";
    if ($paid_fee < 0) $errors[] = "Paid Fee cannot be negative.";
    if (empty($_FILES['profile_image']['name'])) $errors[] = "Profile Image is required.";
    if (count($errors) > 0) {
        echo "<script>alert('" . implode("\\n", $errors) . "'); window.history.back();</script>";
        exit;
    }

    // 3. File upload
    $profile_image = $_FILES['profile_image'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024;
    if (!in_array($profile_image['type'], $allowed)) {
        echo "<script>alert('Only JPG, PNG, GIF images are allowed.'); window.history.back();</script>";
        exit;
    }
    if ($profile_image['size'] > $maxSize) {
        echo "<script>alert('Image size must be under 2 MB.'); window.history.back();</script>";
        exit;
    }
    $ext = pathinfo($profile_image['name'], PATHINFO_EXTENSION);
    $newFileName = uniqid('student_', true) . '.' . $ext;
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
    $target_file = $target_dir . $newFileName;
    if (!move_uploaded_file($profile_image['tmp_name'], $target_file)) {
        echo "<script>alert('File upload failed. Please try again.'); window.history.back();</script>";
        exit;
    }

    // 4. Generate student_id (with retry on duplicate)
    $maxRetries = 3;
    $student_id = null;
    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        $year = date('y');
        $month = date('m');
        $prefix = $year . $month;

        $stmt = $conn->prepare("SELECT MAX(student_id) AS max_id FROM students WHERE student_id LIKE ?");
        $like = $prefix . '%';
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $max_id = $row['max_id'] ?? null;
        $serial = $max_id ? intval(substr($max_id, -2)) + 1 : 1;
        $serial_padded = sprintf("%02d", $serial);
        $student_id = $prefix . $serial_padded;

        $sql = "INSERT INTO students (
            student_id, name, father_name, mother_name, dob, issue_date,
            present_address, permanent_address, id_type, nid_birth_id, email,
            course_start_date, course_end_date, course_category, course_fee, paid_fee, profile_image, phone_number,
            gender, marital_status, occupation, religion, country, city, district
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssssssssssssssssssssss",
            $student_id, $name, $father_name, $mother_name, $dob, $issue_date,
            $present_address, $permanent_address, $id_type, $nid_birth_id, $email,
            $course_start_date, $course_end_date, $course_category, $course_fee, $paid_fee, $newFileName, $phone_number,
            $gender, $marital_status, $occupation, $religion, $country, $city, $district
        );

        if ($stmt->execute()) {
            break;
        } elseif ($conn->errno == 1062) {
            continue;
        } else {
            logError("DB Insert Error: " . $conn->error);
            echo "<script>alert('Database error. Please try again.'); window.history.back();</script>";
            exit;
        }
    }
    if ($attempt > $maxRetries) {
        echo "<script>alert('Could not generate unique ID after multiple attempts. Please try again.'); window.history.back();</script>";
        exit;
    }

    // 5. Send email (with BCC copy to admin) – using the SAME SMTP as your due‑payment script
    $email_status = "";
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();

        // --- SMTP settings (copied from your working due‑payment script) ---
        $mail->Host       = 'rain.mywhiteserver.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'support@artsbd.com';
        $mail->Password   = 'SALMANKHAN017';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Optional: disable SSL verification if needed (uncomment if necessary)
        // $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));

        // --- Optional debug (remove after testing) ---
        $mail->SMTPDebug = 2;                              // 2 = client/server messages
        $mail->Debugoutput = function($str, $level) {
            $logDir = __DIR__ . '/../logs/';
            if (!is_dir($logDir)) mkdir($logDir, 0755, true);
            file_put_contents($logDir . 'smtp_debug.txt', date('H:i:s') . " [$level] $str", FILE_APPEND);
        };

        $mail->setFrom('support@artsbd.com', 'AR Tech Admin');
        $mail->addAddress($email, $name);                 // Student
        $mail->addBCC('support@artsbd.com');              // ★ BCC copy to admin (webmail)

        $mail->isHTML(true);
        $mail->Subject = 'Student Registration Confirmation';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif;'>
                <h3>Dear " . htmlspecialchars($name) . ",</h3>
                <p>Your student record has been successfully registered in our system.</p>
                <p><strong>Student ID:</strong> " . htmlspecialchars($student_id) . "</p>
                <p><strong>Course:</strong> " . htmlspecialchars($course_category) . "</p>
                <p><strong>Start Date:</strong> " . htmlspecialchars($course_start_date) . "</p>
                <p><strong>End Date:</strong> " . htmlspecialchars($course_end_date) . "</p>
                <p>Thank you for joining AR TECH SOLUTION.</p>
                <hr>
                <small>This is an automated message, please do not reply.</small>
            </div>
        ";

        $mail->send();
        $email_status = "and email sent to $email (BCC copy saved)";
    } catch (Exception $e) {
        $errorMsg = $mail->ErrorInfo;
        logError("Email sending failed: " . $errorMsg);
        $email_status = "but email failed: " . $errorMsg;
    }

    echo "<script>
        alert('Student record added successfully! ID: $student_id $email_status');
        window.location.href='insert.php';
    </script>";
    exit;
}

// --- Generate new CSRF token for the form ---
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// --- Default background if not defined ---
$bg_image = $bg_image ?? 'default-bg.jpg';
$dark_mode = $dark_mode ?? false;

// --- HTML (unchanged) ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Student — AR TECH SOLUTION</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<style>
    /* === All your existing styles (unchanged) === */
    body {
        background-image: url('<?php echo htmlspecialchars($bg_image); ?>');
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
        transition: opacity .2s; border: none; cursor: pointer;
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
        overflow-y: auto; overflow-x: hidden;
        z-index: 1050;
        transition: transform .3s cubic-bezier(.4,0,.2,1);
        padding-bottom: 40px;
    }
    .sidebar::-webkit-scrollbar { width: 4px; }
    .sidebar::-webkit-scrollbar-track { background: transparent; }
    .sidebar::-webkit-scrollbar-thumb { background: var(--glass-border); border-radius: 4px; }
    .sidebar.collapsed { transform: translateX(-100%); }
    .sidebar a, .menu-toggle {
        display: flex; align-items: center; gap: 10px;
        color: var(--muted); text-decoration: none;
        padding: 11px 20px; font-size: 13.5px; font-weight: 500;
        border-left: 3px solid transparent;
        transition: all .2s; cursor: pointer; user-select: none;
        white-space: nowrap;
    }
    .sidebar a:hover, .menu-toggle:hover { color: #fff; background: var(--glass); border-left-color: var(--accent); }
    .sidebar a.active { color: var(--accent); border-left-color: var(--accent); background: rgba(0,229,200,0.07); }
    .submenu { display: none; flex-direction: column; background: rgba(0,0,0,0.2); }
    .submenu a { padding: 9px 20px 9px 38px; font-size: 13px; }
    .menu-group.open .submenu { display: flex; }
    .menu-arrow { margin-left: auto; font-size: 11px; transition: transform .25s; }
    .menu-group.open .menu-arrow { transform: rotate(180deg); }
    .sidebar-divider { height: 1px; background: var(--glass-border); margin: 10px 16px; }
    /* SIDEBAR TOGGLE PILL */
    .sidebar-toggle-pill {
        position: fixed; top: calc(var(--nav-h) + 16px); left: var(--sidebar-w);
        width: 24px; height: 44px; background: var(--accent);
        border-radius: 0 10px 10px 0;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; z-index: 1060; font-size: 13px; color: #000;
        font-weight: 900; transition: left .3s cubic-bezier(.4,0,.2,1), background .2s;
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
        display: flex;
        justify-content: center;
    }
    .main.collapsed { margin-left: 0; }
    /* FORM CARD */
    .form-card {
        background: var(--glass);
        backdrop-filter: blur(16px);
        border: 1px solid var(--glass-border);
        border-radius: var(--card-radius);
        padding: 28px;
        max-width: 800px;
        width: 100%;
        animation: fadeInUp 0.5s ease;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .form-card h2 {
        font-family: var(--mono);
        font-size: 22px;
        color: var(--accent);
        text-align: center;
        margin-bottom: 24px;
    }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 16px;
    }
    .form-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .form-field label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--muted);
    }
    .form-field input,
    .form-field select,
    .form-field textarea {
        background: rgba(255,255,255,0.08);
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        padding: 12px 14px;
        color: var(--text);
        font-family: var(--sans);
        font-size: 14px;
        outline: none;
        transition: all 0.2s;
    }
    .form-field input:focus,
    .form-field select:focus,
    .form-field textarea:focus {
        border-color: var(--accent);
        background: rgba(255,255,255,0.12);
    }
    .form-field textarea {
        resize: vertical;
        min-height: 70px;
    }
    .radio-group {
        display: flex;
        gap: 20px;
        align-items: center;
        margin-top: 6px;
    }
    .radio-group label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: normal;
        text-transform: none;
        color: var(--text);
    }
    .radio-group input {
        width: auto;
        margin: 0;
    }
    .full-width {
        grid-column: 1 / -1;
    }
    .submit-btn {
        background: linear-gradient(135deg, var(--accent), #00c9b0);
        color: #000;
        font-weight: 700;
        padding: 14px;
        border: none;
        border-radius: 40px;
        font-size: 16px;
        cursor: pointer;
        transition: opacity 0.2s;
        margin-top: 20px;
        width: 100%;
    }
    .submit-btn:hover { opacity: 0.85; }
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

<!-- SIDEBAR (same as dashboard) -->
<?php include 'navigation.php'; ?>

<div class="sidebar-toggle-pill" id="sidebarToggle">◀</div>

<!-- MAIN CONTENT -->
<main class="main" id="mainContent">
    <div class="form-card">
        <h2>➕ Insert Student Record</h2>
        <form action="insert.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="form-grid">
                <div class="form-field"><input type="text" name="name" placeholder="Full Name" required></div>
                <div class="form-field"><input type="text" name="father_name" placeholder="Father's Name" required></div>
                <div class="form-field"><input type="text" name="mother_name" placeholder="Mother's Name" required></div>
                <div class="form-field"><label>Issue Date</label><input type="date" name="issue_date" required></div>
                <div class="form-field"><label>Date of Birth</label><input type="date" name="dob" required></div>
                <div class="form-field full-width"><label>Present Address</label><textarea name="present_address" required></textarea></div>
                <div class="form-field full-width"><label>Permanent Address</label><textarea name="permanent_address" required></textarea></div>
                <div class="form-field"><label>ID Type</label>
                    <div class="radio-group">
                        <label><input type="radio" name="id_type" value="NID" required> NID</label>
                        <label><input type="radio" name="id_type" value="Birth ID" required> Birth ID</label>
                    </div>
                </div>
                <div class="form-field"><input type="text" name="nid_birth_id" placeholder="ID Number" required></div>
                <div class="form-field"><input type="email" name="email" placeholder="Email" required></div>
                <div class="form-field"><input type="text" name="phone_number" placeholder="Phone Number" required></div>
                <div class="form-field">
                    <select name="gender" required><option value="">Gender</option><option>Male</option><option>Female</option><option>Other</option></select>
                </div>
                <div class="form-field">
                    <select name="marital_status" required><option value="">Marital Status</option><option>Single</option><option>Married</option><option>Divorced</option><option>Widowed</option></select>
                </div>
                <div class="form-field"><input type="text" name="occupation" placeholder="Occupation" required></div>
                <div class="form-field"><input type="text" name="religion" placeholder="Religion" required></div>
                <div class="form-field"><input type="text" name="country" placeholder="Country" required></div>
                <div class="form-field"><input type="text" name="city" placeholder="City" required></div>
                <div class="form-field"><input type="text" name="district" placeholder="District" required></div>
                <div class="form-field"><label>Course Start Date</label><input type="date" name="course_start_date" required></div>
                <div class="form-field"><label>Course End Date</label><input type="date" name="course_end_date" required></div>
                <div class="form-field">
                    <select name="course_category" required>
                        <option value="">Course Category</option>
                        <option>Graphic Design</option><option>Video Editing</option>
                        <option>Social Media Marketing</option><option>Digital Marketing</option>
                        <option>Office Application</option>
                        <option>Web Development</option>
                    </select>
                </div>
                <div class="form-field"><input type="number" step="0.01" name="course_fee" placeholder="Course Fee" required></div>
                <div class="form-field"><input type="number" step="0.01" name="paid_fee" placeholder="Paid Fee" required></div>
                <div class="form-field"><label>Profile Image</label><input type="file" name="profile_image" accept="image/*" required></div>
            </div>
            <button type="submit" class="submit-btn">Insert Student</button>
        </form>
    </div>
</main>

<div class="footer">
    &copy; <?php echo date("Y"); ?> AR TECH SOLUTION — Freelancing Student Management System
</div>

<script>
// Sidebar toggle (desktop)
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

// Hamburger (mobile)
const hamburger = document.getElementById('hamburgerBtn');
if (hamburger) {
    hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('mobile-open');
    });
}

// Submenu toggles
document.querySelectorAll('.menu-toggle').forEach(toggle => {
    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const group = toggle.closest('.menu-group');
        if (group) group.classList.toggle('open');
    });
});

// Live clock
function updateClock() {
    const clockEl = document.getElementById('liveClock');
    if (clockEl) {
        const now = new Date();
        clockEl.textContent = now.toLocaleTimeString('en-US', {
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
    }
}
updateClock();
setInterval(updateClock, 1000);
</script>
</body>
</html>