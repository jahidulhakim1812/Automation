<?php
session_start();

// Admin session check
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}
require_once 'config.php';

// ---------- Email Configuration ----------
$email_config = [
    'method' => 'smtp', // 'smtp' or 'mail'
    'smtp_host' => 'mail.artsbd.com',
    'smtp_port' => 587,
    'smtp_user' => 'support@artsbd.com',
    'smtp_pass' => 'SALMANKHAN019',
    'smtp_secure' => 'tls',
    'from_email' => 'support@artsbd.com',
    'from_name' => 'AR TECH SOLUTION',
];

// ---------- Function to send marksheet email ----------
function sendMarksheetEmail($student_id, $conn, $email_config) {
    // Fetch student details
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $student_result = $stmt->get_result();
    if ($student_result->num_rows === 0) {
        return "Student not found.";
    }
    $student = $student_result->fetch_assoc();
    $stmt->close();

    // Fetch marks
    $marks_stmt = $conn->prepare("SELECT * FROM marks WHERE student_id = ? ORDER BY exam_name");
    $marks_stmt->bind_param("s", $student_id);
    $marks_stmt->execute();
    $marks_result = $marks_stmt->get_result();

    $subjects = [];
    $total_weighted_sum = 0;
    $total_gp_sum = 0;
    $count = 0;

    while ($row = $marks_result->fetch_assoc()) {
        $exam_marks = $row['marks'] ?? 0;
        $project_marks = $row['project_marks'] ?? 0;
        $assignment_marks = $row['assignment_marks'] ?? 0;
        $weighted_total = ($exam_marks * 0.6) + ($project_marks * 0.3) + ($assignment_marks * 0.1);
        
        // Grade mapping
        if ($weighted_total >= 90) { $grade = 'A+'; $gp = 5; }
        elseif ($weighted_total >= 80) { $grade = 'A-'; $gp = 4; }
        elseif ($weighted_total >= 70) { $grade = 'B'; $gp = 3; }
        elseif ($weighted_total >= 60) { $grade = 'C'; $gp = 2; }
        else { $grade = 'F'; $gp = 0; }

        $subjects[] = [
            'exam_name' => $row['exam_name'],
            'exam_marks' => $exam_marks,
            'project_marks' => $project_marks,
            'assignment_marks' => $assignment_marks,
            'weighted_total' => $weighted_total,
            'grade' => $grade,
            'gp' => $gp
        ];
        $total_weighted_sum += $weighted_total;
        $total_gp_sum += $gp;
        $count++;
    }
    $marks_stmt->close();

    $overall_average = ($count > 0) ? ($total_weighted_sum / $count) : 0;
    $overall_gpa = ($count > 0) ? ($total_gp_sum / $count) : 0;
    if ($overall_average >= 90) $final_grade = 'A+';
    elseif ($overall_average >= 80) $final_grade = 'A-';
    elseif ($overall_average >= 70) $final_grade = 'B';
    elseif ($overall_average >= 60) $final_grade = 'C';
    else $final_grade = 'F';
    $status = ($overall_average < 60) ? 'Fail' : 'Pass';

    // Build HTML email content
    $html = "
    <html>
    <head><style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 2px 0; }
        .student-info { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 24px; margin: 12px 0; }
        .student-info .label { font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #000; padding: 6px 4px; text-align: center; }
        th { background: #eee; font-weight: 700; }
        .total-row td { font-weight: 700; background: #f5f5f5; border-top: 2px solid #000; }
        .final-result { margin-top: 12px; padding: 10px; border: 2px solid #000; background: #f9f9f9; display: flex; justify-content: space-between; }
        .signatures { margin-top: 16px; display: flex; justify-content: center; gap: 40px; }
        .signatures .line { display: block; width: 120px; border-bottom: 1px solid #000; margin: 6px auto; }
    </style></head>
    <body>
        <div class='header'>
            <h1>RAJUK UTTARA MODEL COLLEGE</h1>
            <p>SECTOR#6, UTTARA MODEL TOWN, DHAKA</p>
            <p>WWW.RAJUKCOLLEGE.EDU.BD</p>
            <h2>Academic Transcript</h2>
            <p><strong>Academic Year: 2024</strong></p>
        </div>
        <div class='student-info'>
            <div><span class='label'>Name of Student:</span> {$student['name']}</div>
            <div><span class='label'>Student ID:</span> {$student['student_id']}</div>
            <div><span class='label'>Class:</span> {$student['course_category']}</div>
            <div><span class='label'>Email:</span> {$student['email']}</div>
        </div>
        <table>
            <thead><tr><th>Subject</th><th>Exam (60%)</th><th>Project (30%)</th><th>Assignment (10%)</th><th>Weighted Total</th><th>Grade</th><th>GP</th></tr></thead>
            <tbody>";
    $grand_total = 0;
    foreach ($subjects as $subj) {
        $grand_total += $subj['weighted_total'];
        $html .= "<tr>
            <td style='text-align:left;'>{$subj['exam_name']}</td>
            <td>" . number_format($subj['exam_marks'], 2) . "</td>
            <td>" . number_format($subj['project_marks'], 2) . "</td>
            <td>" . number_format($subj['assignment_marks'], 2) . "</td>
            <td><strong>" . number_format($subj['weighted_total'], 2) . "</strong></td>
            <td><strong>{$subj['grade']}</strong></td>
            <td>{$subj['gp']}</td>
        </tr>";
    }
    $html .= "
        <tr class='total-row'><td colspan='4' style='text-align:right;'>Total (Weighted)</td><td colspan='3'>" . number_format($grand_total, 2) . "</td></tr>
        <tr class='total-row'><td colspan='4' style='text-align:right;'>Overall Average</td><td colspan='3'>" . number_format($overall_average, 2) . "%</td></tr>
        <tr class='total-row'><td colspan='4' style='text-align:right;'>GPA</td><td colspan='3'>" . number_format($overall_gpa, 2) . "</td></tr>
        </tbody></table>
        <div class='final-result'>
            <div><strong>Status:</strong> $status</div>
            <div><strong>Final Grade:</strong> <span style='font-size:22px;'>$final_grade</span></div>
            <div><strong>Overall Average:</strong> " . number_format($overall_average, 2) . "%</div>
        </div>
        <div class='signatures'>
            <div><span class='line'></span>Teacher</div>
            <div><span class='line'></span>Vice Principal</div>
            <div><span class='line'></span>Principal</div>
        </div>
        <p style='text-align:right; font-size:12px;'>Result published on: " . date('d-m-Y') . "</p>
    </body></html>";

    // Send email
    $to = $student['email'];
    $subject = "Academic Transcript for " . $student['name'];
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $method = $email_config['method'] ?? 'mail';

    if ($method === 'smtp' && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Use PHPMailer (SMTP)
        require_once 'vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $email_config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $email_config['smtp_user'];
            $mail->Password = $email_config['smtp_pass'];
            $mail->SMTPSecure = $email_config['smtp_secure'];
            $mail->Port = $email_config['smtp_port'];
            $mail->setFrom($email_config['from_email'], $email_config['from_name']);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->send();
            return true;
        } catch (Exception $e) {
            return "SMTP Error: " . $mail->ErrorInfo;
        }
    } else {
        // Fallback to PHP mail()
        $headers .= "From: " . $email_config['from_email'] . "\r\n";
        if (mail($to, $subject, $html, $headers)) {
            return true;
        } else {
            return "Failed to send email using mail() function. Check your server's mail configuration.";
        }
    }
}

// ---------- CRUD Operations ----------
$message = '';
$message_type = '';

// Add / Update / Delete
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_mark'])) {
        $student_id = $_POST['student_id'];
        $exam_name = trim($_POST['exam_name']);
        $marks = $_POST['marks'] !== '' ? $_POST['marks'] : null;
        $project_marks = $_POST['project_marks'] !== '' ? $_POST['project_marks'] : null;
        $assignment_marks = $_POST['assignment_marks'] !== '' ? $_POST['assignment_marks'] : null;

        if (empty($student_id) || empty($exam_name)) {
            $message = "Student and Exam Name are required.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO marks (student_id, exam_name, marks, project_marks, assignment_marks) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssddd", $student_id, $exam_name, $marks, $project_marks, $assignment_marks);
            if ($stmt->execute()) {
                $message = "Mark entry added successfully.";
                $message_type = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }

    if (isset($_POST['edit_mark'])) {
        $id = $_POST['mark_id'];
        $student_id = $_POST['student_id'];
        $exam_name = trim($_POST['exam_name']);
        $marks = $_POST['marks'] !== '' ? $_POST['marks'] : null;
        $project_marks = $_POST['project_marks'] !== '' ? $_POST['project_marks'] : null;
        $assignment_marks = $_POST['assignment_marks'] !== '' ? $_POST['assignment_marks'] : null;

        if (empty($student_id) || empty($exam_name)) {
            $message = "Student and Exam Name are required.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("UPDATE marks SET student_id=?, exam_name=?, marks=?, project_marks=?, assignment_marks=? WHERE id=?");
            $stmt->bind_param("ssdddi", $student_id, $exam_name, $marks, $project_marks, $assignment_marks, $id);
            if ($stmt->execute()) {
                $message = "Mark entry updated successfully.";
                $message_type = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }

    if (isset($_POST['delete_mark'])) {
        $id = $_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM marks WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Mark entry deleted.";
            $message_type = "success";
        } else {
            $message = "Error: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }

    // ---------- Send Email ----------
    if (isset($_POST['send_email'])) {
        $student_id = $_POST['student_id'];
        $result = sendMarksheetEmail($student_id, $conn, $email_config);
        if ($result === true) {
            $message = "Marksheet sent successfully to student's email.";
            $message_type = "success";
        } else {
            $message = "Failed to send email: " . $result;
            $message_type = "error";
        }
    }
}

// ---------- Fetch data ----------
$search = "";
$sql = "SELECT m.*, s.name AS student_name 
        FROM marks m 
        JOIN students s ON m.student_id = s.student_id";

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["search"]) && !empty($_GET["search"])) {
    $search = $_GET["search"];
    $search_escaped = $conn->real_escape_string($search);
    $sql .= " WHERE s.name LIKE '%$search_escaped%' OR m.exam_name LIKE '%$search_escaped%'";
}
$sql .= " ORDER BY m.created_at DESC";

$result = $conn->query($sql);

// ---------- Get all students for dropdown ----------
$students_list = $conn->query("SELECT student_id, name FROM students ORDER BY name");

// For editing: fetch existing data if ?edit=id is present
$edit_data = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_stmt = $conn->prepare("SELECT * FROM marks WHERE id = ?");
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    if ($edit_result->num_rows > 0) {
        $edit_data = $edit_result->fetch_assoc();
    }
    $edit_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Marks — AR TECH SOLUTION</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<style>
    body {
        background-image: url('<?php echo $bg_image ?? ''; ?>');
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
}
.main.collapsed { margin-left: 0; }

/* SECTION TITLE */
.section-title {
    font-family: var(--mono); font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 2px; color: var(--accent);
    margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
}
.section-title::after { content: ''; flex: 1; height: 1px; background: var(--glass-border); }

/* MESSAGE */
.msg {
    padding: 12px 18px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-weight: 500;
}
.msg.success { background: rgba(6,214,160,0.2); border-left: 4px solid #06d6a0; color: #06d6a0; }
.msg.error { background: rgba(255,107,107,0.2); border-left: 4px solid #ff6b6b; color: #ff6b6b; }

/* SEARCH & ADD CARD */
.search-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 20px;
    margin-bottom: 28px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
}
.search-card input, .search-card select {
    flex: 1;
    padding: 12px 16px;
    background: rgba(255,255,255,0.08);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    color: var(--text);
    font-family: var(--sans);
    font-size: 14px;
    outline: none;
    min-width: 140px;
}
.search-card input:focus, .search-card select:focus {
    border-color: var(--accent);
    background: rgba(255,255,255,0.12);
}
.search-card button {
    padding: 12px 24px;
    background: linear-gradient(135deg, var(--accent), #00c9b0);
    border: none;
    border-radius: 12px;
    color: #000;
    font-weight: 700;
    cursor: pointer;
    transition: opacity .2s;
}
.search-card button:hover { opacity: .85; }

/* TABLE CARD */
.table-card {
    background: var(--glass);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: var(--card-radius);
    padding: 20px;
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
th, td {
    padding: 12px 8px;
    text-align: left;
    border-bottom: 1px solid var(--glass-border);
}
th {
    background: rgba(0,0,0,0.3);
    color: var(--accent);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 11px;
}
td {
    color: var(--text);
}
tr:hover td {
    background: rgba(255,255,255,0.03);
}
.action-btns {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.action-btns a, .action-btns form {
    display: inline-block;
}
.edit-btn, .del-btn, .marksheet-btn, .email-btn {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: opacity .2s;
}
.edit-btn {
    background: linear-gradient(135deg, var(--accent2), #9b59b6);
    color: white;
}
.del-btn {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
}
.marksheet-btn {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
}
.email-btn {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    color: white;
}
.edit-btn:hover, .del-btn:hover, .marksheet-btn:hover, .email-btn:hover { opacity: .85; }

/* FORM inside modal */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
    z-index: 2000;
    justify-content: center;
    align-items: center;
}
.modal.active { display: flex; }
.modal-content {
    background: #1a1f2e;
    padding: 30px 35px;
    border-radius: 24px;
    max-width: 500px;
    width: 90%;
    border: 1px solid var(--glass-border);
    box-shadow: var(--shadow);
}
.modal-content h3 {
    color: var(--accent);
    font-family: var(--mono);
    margin-bottom: 20px;
    font-size: 20px;
}
.modal-content label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: var(--muted);
    margin: 12px 0 4px;
}
.modal-content input, .modal-content select {
    width: 100%;
    padding: 10px 14px;
    background: rgba(255,255,255,0.07);
    border: 1px solid var(--glass-border);
    border-radius: 10px;
    color: var(--text);
    font-size: 14px;
}
.modal-content input:focus, .modal-content select:focus {
    border-color: var(--accent);
    outline: none;
}
.modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 22px;
    justify-content: flex-end;
}
.modal-actions button {
    padding: 10px 28px;
    border-radius: 30px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: opacity .2s;
}
.modal-actions .btn-submit {
    background: var(--accent);
    color: #000;
}
.modal-actions .btn-cancel {
    background: rgba(255,255,255,0.1);
    color: var(--text);
}
.modal-actions button:hover { opacity: .8; }

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
    .search-card { flex-direction: column; }
    .search-card button { width: 100%; }
    th, td { padding: 8px 6px; font-size: 11px; }
}
</style>
</head>
<body class="<?php echo $dark_mode ?? '' ? 'dark-mode' : ''; ?>">

<!-- TOP NAV -->
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

<!-- SIDEBAR -->
<?php include 'navigation.php'; ?>

<div class="sidebar-toggle-pill" id="sidebarToggle">◀</div>

<!-- MAIN CONTENT -->
<main class="main" id="mainContent">
    <div class="section-title">📊 Student Marks</div>

    <?php if ($message): ?>
        <div class="msg <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Search & Add -->
    <div class="search-card">
        <form method="GET" style="display: flex; gap: 12px; width: 100%; flex-wrap: wrap;">
            <input type="text" name="search" placeholder="Search by student or exam name" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">🔍 Search</button>
        </form>
        <button onclick="openAddModal()" style="background:linear-gradient(135deg,#f39c12,#e67e22);border:none;border-radius:12px;padding:12px 24px;color:#fff;font-weight:700;cursor:pointer;transition:opacity .2s;">+ Add Marks</button>
    </div>

    <!-- Table -->
    <div class="table-card">
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Exam Name</th>
                        <th>Marks</th>
                        <th>Project</th>
                        <th>Assignment</th>
                        <th>Action</th>
                        <th>Marksheet</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                            <td><?php echo $row['marks'] !== null ? number_format($row['marks'], 2) : '-'; ?></td>
                            <td><?php echo $row['project_marks'] !== null ? number_format($row['project_marks'], 2) : '-'; ?></td>
                            <td><?php echo $row['assignment_marks'] !== null ? number_format($row['assignment_marks'], 2) : '-'; ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="?edit=<?php echo $row['id']; ?>" class="edit-btn">Edit</a>
                                    <form method="POST" onsubmit="return confirm('Delete this entry?');" style="display:inline;">
                                        <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="delete_mark" class="del-btn">Delete</button>
                                    </form>
                                </div>
                            </td>
                            <td>
                                <a href="marksheet.php?student_id=<?php echo urlencode($row['student_id']); ?>" class="marksheet-btn">📄 View</a>
                            </td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Send marksheet to <?php echo htmlspecialchars($row['student_name']); ?>?');">
                                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($row['student_id']); ?>">
                                    <button type="submit" name="send_email" class="email-btn">📧 Send</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align:center;">No marks entries found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- ADD / EDIT MODAL -->
<div class="modal" id="markModal">
    <div class="modal-content">
        <h3 id="modalTitle">Add Marks</h3>
        <form method="POST" id="markForm">
            <input type="hidden" name="mark_id" id="mark_id" value="">

            <label for="student_id">Student *</label>
            <select name="student_id" id="student_id" required>
                <option value="">-- Select Student --</option>
                <?php if ($students_list && $students_list->num_rows > 0): ?>
                    <?php while ($s = $students_list->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($s['student_id']); ?>">
                            <?php echo htmlspecialchars($s['name']); ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>

            <label for="exam_name">Exam Name *</label>
            <input type="text" name="exam_name" id="exam_name" required placeholder="e.g. Midterm, Final, etc.">

            <label for="marks">Marks (out of 100) – 60% weight</label>
            <input type="number" name="marks" id="marks" step="0.01" min="0" max="100" placeholder="e.g. 85.5">

            <label for="project_marks">Project Marks (out of 100) – 30% weight</label>
            <input type="number" name="project_marks" id="project_marks" step="0.01" min="0" max="100" placeholder="e.g. 90">

            <label for="assignment_marks">Assignment Marks (out of 100) – 10% weight</label>
            <input type="number" name="assignment_marks" id="assignment_marks" step="0.01" min="0" max="100" placeholder="e.g. 88">

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" name="add_mark" id="submitBtn" class="btn-submit">Add</button>
            </div>
        </form>
    </div>
</div>

<div class="footer">
    &copy; <?php echo date("Y"); ?> AR TECH SOLUTION — Freelancing Student Management System
</div>

<script>
// -------- Sidebar toggles --------
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

// -------- Modal logic --------
const modal = document.getElementById('markModal');
const form = document.getElementById('markForm');
const modalTitle = document.getElementById('modalTitle');
const submitBtn = document.getElementById('submitBtn');
const markId = document.getElementById('mark_id');

<?php if ($edit_data): ?>
window.addEventListener('DOMContentLoaded', function() {
    openModal('edit');
    document.getElementById('mark_id').value = '<?php echo $edit_data['id']; ?>';
    document.getElementById('student_id').value = '<?php echo htmlspecialchars($edit_data['student_id']); ?>';
    document.getElementById('exam_name').value = '<?php echo htmlspecialchars($edit_data['exam_name']); ?>';
    document.getElementById('marks').value = '<?php echo htmlspecialchars($edit_data['marks']); ?>';
    document.getElementById('project_marks').value = '<?php echo htmlspecialchars($edit_data['project_marks']); ?>';
    document.getElementById('assignment_marks').value = '<?php echo htmlspecialchars($edit_data['assignment_marks']); ?>';
    submitBtn.name = 'edit_mark';
    submitBtn.textContent = 'Update';
});
<?php endif; ?>

function openAddModal() {
    openModal('add');
    form.reset();
    markId.value = '';
    submitBtn.name = 'add_mark';
    submitBtn.textContent = 'Add';
    if (window.history && window.history.replaceState) {
        window.history.replaceState(null, '', window.location.pathname);
    }
}

function openModal(action) {
    modalTitle.textContent = action === 'edit' ? 'Edit Marks' : 'Add Marks';
    modal.classList.add('active');
}

function closeModal() {
    modal.classList.remove('active');
}

modal.addEventListener('click', function(e) {
    if (e.target === modal) closeModal();
});

document.querySelector('.btn-cancel').addEventListener('click', closeModal);
</script>
</body>
</html>