<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../login.php");
    exit();
}

require_once 'config.php';

// Set defaults if config doesn't provide them (prevents undefined variable warnings)
$bg_image = $bg_image ?? 'https://www.transparenttextures.com/patterns/cubes.png';
$dark_mode = $dark_mode ?? false;

$matched_student = null;
$error = '';
$confidence = '';

// ─── HANDLE FORM SUBMISSION WITH LOCAL FACE MATCHER ───────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['search_photo'])) {
    $file = $_FILES['search_photo'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload error. Please try again.';
    } else {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $detectedMime = mime_content_type($file['tmp_name']);

        if (!in_array($detectedMime, $allowedMimes)) {
            $error = 'Please upload a valid image file (JPG, PNG, WEBP, or GIF).';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $error = 'Image size must be under 10 MB.';
        } else {
            // ── Call Python face matcher directly ──
            $projectRoot  = dirname(__DIR__);
            $pythonScript = $projectRoot . '/face_matcher.py';

            if (!file_exists($pythonScript)) {
                $error = 'face_matcher.py not found at: ' . $pythonScript;
            } else {
                // ⚠️ Adjust Python path to your environment (Windows example below)
                $pythonPath = 'C:\\Users\\JAHID1\\AppData\\Local\\Programs\\Python\\Python310\\python.exe';
                // For Linux: '/usr/bin/python3'

                $tmpImage = $projectRoot . '/tmp_face_' . uniqid() . '.jpg';
                copy($file['tmp_name'], $tmpImage);

                $command = 'cd ' . escapeshellarg($projectRoot) . ' && '
                         . escapeshellarg($pythonPath) . ' '
                         . escapeshellarg($pythonScript) . ' '
                         . escapeshellarg($tmpImage);

                $output = shell_exec($command . ' 2>&1');
                $output = trim($output);

                if (file_exists($tmpImage)) unlink($tmpImage);

                if (strpos($output, 'MATCH:') === 0) {
                    $parts      = explode(':', $output);
                    $student_id = $parts[1];
                    $confidence = isset($parts[2]) ? floatval($parts[2]) : 0;
                    $confidence = round($confidence * 100, 1);

                    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
                    $stmt->bind_param("s", $student_id);
                    $stmt->execute();
                    $matched_student = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if (!$matched_student) {
                        $error = 'Student found in face match but not in database.';
                    } else {
                        if (function_exists('logAdminActivity')) {
                            logAdminActivity('face_search', 'Matched student: ' . $matched_student['student_id'] . ' - ' . $matched_student['name']);
                        }
                    }
                } elseif ($output === 'NO_MATCH') {
                    $error = 'No matching student found. Try a clearer photo.';
                } else {
                    $error = 'Face service error: ' . htmlspecialchars($output);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Face Search — AR TECH SOLUTION</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
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
    --muted: #8892a4;
    --sans: 'Plus Jakarta Sans', sans-serif;
    --nav-h: 58px;
    --sidebar-w: 230px;
    --shadow: 0 8px 32px rgba(0,0,0,0.35);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    background-image: url('<?php echo $bg_image; ?>');
    background-size: cover;
    background-attachment: fixed;
    background-position: center;
    font-family: var(--sans);
    color: var(--text);
    min-height: 100vh;
}
body::before {
    content: '';
    position: fixed; inset: 0;
    background: rgba(8,12,24,0.78);
    z-index: 0;
}
body.dark-mode::before { background: rgba(0,0,0,0.88); }

/* TOP NAV */
.topnav {
    position: fixed; top: 0; left: 0; right: 0; height: var(--nav-h);
    background: rgba(8,15,30,0.97);
    border-bottom: 1px solid var(--glass-border);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 20px; z-index: 2000;
}
.topnav-brand { font-family: 'Space Grotesk', sans-serif; font-size: 20px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 8px; }
.brand-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--accent); box-shadow: 0 0 8px var(--accent); }
.topnav-right { display: flex; align-items: center; gap: 14px; }
.topnav-time { color: var(--muted); font-size: 13px; }
.hamburger { background: none; border: none; color: var(--text); font-size: 22px; cursor: pointer; display: none; }
.logout-btn { background: var(--accent3); color: #fff; padding: 6px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; }

/* SIDEBAR */
.sidebar {
    position: fixed; top: var(--nav-h); left: 0;
    width: var(--sidebar-w); height: calc(100vh - var(--nav-h));
    background: #08121e; border-right: 1px solid var(--glass-border);
    overflow-y: auto; overflow-x: hidden; z-index: 1050;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-thumb { background: var(--glass-border); border-radius: 4px; }
.sidebar.collapsed { transform: translateX(-100%); }
.sidebar a, .menu-toggle {
    display: flex; align-items: center; gap: 10px; color: var(--muted);
    text-decoration: none; padding: 11px 20px; font-size: 13.5px; font-weight: 500;
    border-left: 3px solid transparent; transition: all 0.2s; cursor: pointer;
}
.sidebar a:hover, .menu-toggle:hover { color: #fff; background: var(--glass); border-left-color: var(--accent); }
.sidebar a.active { color: var(--accent); border-left-color: var(--accent); background: rgba(0,229,200,0.07); }
.sidebar-divider { border-top: 1px solid var(--glass-border); margin: 6px 0; }
.submenu { display: none; flex-direction: column; background: rgba(0,0,0,0.2); }
.submenu a { padding: 9px 20px 9px 38px; font-size: 13px; }
.menu-group.open .submenu { display: flex; }
.menu-arrow { margin-left: auto; font-size: 11px; transition: transform 0.25s; }
.menu-group.open .menu-arrow { transform: rotate(180deg); }

/* SIDEBAR TOGGLE PILL (rectangle style, matching attendance.php) */
.sidebar-toggle-pill {
    position: fixed;
    top: calc(var(--nav-h) + 16px);
    left: var(--sidebar-w);
    width: 24px;
    height: 44px;
    background: var(--accent);
    border-radius: 0 10px 10px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 1060;
    font-size: 13px;
    color: #000;
    font-weight: 900;
    transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1), background 0.2s;
}
.sidebar-toggle-pill:hover { background: #00c9b0; }
.sidebar-toggle-pill.collapsed { left: 0; }

/* MAIN */
.main {
    margin-left: var(--sidebar-w);
    padding-top: calc(var(--nav-h) + 28px);
    min-height: 100vh;
    position: relative;
    z-index: 1;
    padding-left: 32px;
    padding-right: 32px;
    padding-bottom: 40px;
    transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.main.collapsed { margin-left: 0; }

.section-title {
    font-size: 22px; font-weight: 700; color: var(--accent);
    margin-bottom: 24px; letter-spacing: 0.5px;
}

/* GLASS CARD */
.glass-card {
    background: var(--glass); border: 1px solid var(--glass-border);
    border-radius: 18px; padding: 28px; backdrop-filter: blur(12px);
    box-shadow: var(--shadow);
}

/* UPLOAD ZONE */
.upload-zone {
    border: 2px dashed var(--accent);
    border-radius: 16px; padding: 50px 30px; text-align: center;
    cursor: pointer; transition: all 0.25s;
    background: rgba(0,229,200,0.04);
    position: relative;
}
.upload-zone:hover, .upload-zone.dragover {
    background: rgba(0,229,200,0.10); border-color: var(--accent5);
}
.upload-zone input[type="file"] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
.upload-icon { font-size: 52px; margin-bottom: 14px; }
.upload-title { font-size: 17px; font-weight: 700; color: var(--accent); margin-bottom: 6px; }
.upload-sub { color: var(--muted); font-size: 13px; }

/* PREVIEW */
#previewWrap { display: none; text-align: center; margin-top: 20px; }
#previewWrap img {
    max-height: 240px; max-width: 100%; border-radius: 14px;
    border: 2px solid var(--accent); box-shadow: 0 0 30px rgba(0,229,200,0.25);
}
#previewWrap .preview-label { margin-top: 8px; color: var(--muted); font-size: 13px; }

/* SEARCH BTN */
.btn-search {
    display: inline-flex; align-items: center; gap: 10px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #000; font-weight: 700; font-size: 15px;
    border: none; border-radius: 12px; padding: 13px 32px;
    cursor: pointer; margin-top: 24px; width: 100%; justify-content: center;
    transition: all 0.2s; box-shadow: 0 4px 20px rgba(0,229,200,0.3);
}
.btn-search:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,229,200,0.45); }
.btn-search:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

/* SPINNER */
.spinner {
    display: none; width: 20px; height: 20px; border-radius: 50%;
    border: 3px solid rgba(0,0,0,0.2); border-top-color: #000;
    animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ALERTS */
.alert {
    padding: 16px 20px; border-radius: 12px; margin-bottom: 24px;
    font-size: 14px; font-weight: 500;
}
.alert-error { background: rgba(255,107,107,0.15); border: 1px solid rgba(255,107,107,0.4); color: #ff9b9b; }
.alert-success { background: rgba(0,229,200,0.12); border: 1px solid rgba(0,229,200,0.35); color: var(--accent); }

/* RESULT CARD */
.result-card {
    background: rgba(0,229,200,0.06); border: 1px solid rgba(0,229,200,0.3);
    border-radius: 18px; padding: 0; overflow: hidden; margin-top: 28px;
}
.result-header {
    background: linear-gradient(135deg, rgba(0,229,200,0.25), rgba(123,94,167,0.2));
    padding: 20px 28px; display: flex; align-items: center; gap: 20px;
    flex-wrap: wrap;
}
.result-photo {
    width: 90px; height: 90px; border-radius: 50%; object-fit: cover;
    border: 3px solid var(--accent); box-shadow: 0 0 20px rgba(0,229,200,0.4);
}
.result-photo-placeholder {
    width: 90px; height: 90px; border-radius: 50%; background: var(--glass);
    border: 3px solid var(--accent); display: flex; align-items: center;
    justify-content: center; font-size: 36px;
}
.result-name { font-size: 22px; font-weight: 800; color: #fff; }
.result-id { font-size: 13px; color: var(--accent); font-weight: 600; margin-top: 3px; }
.match-badge {
    margin-left: auto; background: var(--accent); color: #000;
    padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700;
    display: flex; align-items: center; gap: 6px;
}
.result-body { padding: 24px 28px; }
.result-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
.field-label { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.7px; margin-bottom: 4px; }
.field-value { font-size: 14px; color: var(--text); font-weight: 600; }
.field-value.highlight { color: var(--accent4); }
.field-value.danger { color: var(--accent3); }
.divider { border: none; border-top: 1px solid var(--glass-border); margin: 20px 0; }

/* HOW IT WORKS */
.how-card { margin-top: 32px; }
.how-steps { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 16px; }
.how-step { flex: 1; min-width: 160px; background: var(--glass); border: 1px solid var(--glass-border); border-radius: 14px; padding: 18px; text-align: center; }
.how-step-num { font-size: 26px; margin-bottom: 8px; }
.how-step-text { font-size: 12px; color: var(--muted); line-height: 1.5; }

/* ACTION LINKS */
.action-links { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 20px; padding: 0 28px 24px; }
.action-link {
    padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 600;
    text-decoration: none; display: inline-flex; align-items: center; gap: 7px; transition: all 0.2s;
}
.action-link.primary { background: var(--accent); color: #000; }
.action-link.secondary { background: var(--glass); border: 1px solid var(--glass-border); color: var(--text); }
.action-link:hover { transform: translateY(-1px); opacity: 0.9; }

/* RESPONSIVE */
@media (max-width: 768px) {
    .hamburger { display: block; }
    .sidebar { transform: translateX(-100%); }
    .sidebar.collapsed { transform: translateX(-100%); }
    .sidebar.mobile-open { transform: translateX(0); }
    .main { margin-left: 0; padding-left: 16px; padding-right: 16px; }
    .main.collapsed { margin-left: 0; }
    .sidebar-toggle-pill { display: none; }
    .result-grid { grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body class="<?php echo $dark_mode ? 'dark-mode' : ''; ?>">

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
    <div class="section-title">🤖 AI Face Search</div>

    <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($matched_student): ?>
    <!-- ── MATCH FOUND ── -->
    <div class="alert alert-success">✅ Student identified successfully! <?php if ($confidence) echo "Confidence: {$confidence}%"; ?></div>

    <div class="result-card">
        <div class="result-header">
            <?php
            $photoPath = __DIR__ . '/uploads/' . $matched_student['profile_image'];
            if (!empty($matched_student['profile_image']) && file_exists($photoPath)):
            ?>
            <img src="uploads/<?php echo htmlspecialchars($matched_student['profile_image']); ?>"
                 class="result-photo" alt="Student Photo">
            <?php else: ?>
            <div class="result-photo-placeholder">👤</div>
            <?php endif; ?>

            <div>
                <div class="result-name"><?php echo htmlspecialchars($matched_student['name']); ?></div>
                <div class="result-id">Student ID: <?php echo htmlspecialchars($matched_student['student_id']); ?></div>
                <div style="margin-top:6px;font-size:13px;color:var(--muted);">
                    <?php echo htmlspecialchars($matched_student['course_category'] ?? ''); ?>
                </div>
            </div>

            <div class="match-badge">✓ MATCHED</div>
        </div>

        <div class="result-body">
            <div class="result-grid">
                <div class="result-field">
                    <div class="field-label">Full Name</div>
                    <div class="field-value"><?php echo htmlspecialchars($matched_student['name']); ?></div>
                </div>
                <div class="result-field">
                    <div class="field-label">Student ID</div>
                    <div class="field-value"><?php echo htmlspecialchars($matched_student['student_id']); ?></div>
                </div>
                <div class="result-field">
                    <div class="field-label">Email</div>
                    <div class="field-value"><?php echo htmlspecialchars($matched_student['email'] ?? '—'); ?></div>
                </div>
                <div class="result-field">
                    <div class="field-label">Phone</div>
                    <div class="field-value"><?php echo htmlspecialchars($matched_student['phone_number'] ?? '—'); ?></div>
                </div>
                <div class="result-field">
                    <div class="field-label">Father's Name</div>
                    <div class="field-value"><?php echo htmlspecialchars($matched_student['father_name'] ?? '—'); ?></div>
                </div>
                <div class="result-field">
                    <div class="field-label">Mother's Name</div>
                    <div class="field-value"><?php echo htmlspecialchars($matched_student['mother_name'] ?? '—'); ?></div>
                </div>
                <div class="result-field">
                    <div class="field-label">Date of Birth</div>
                    <div class="field-value"><?php echo htmlspecialchars($matched_student['dob'] ?? '—'); ?></div>
                </div>
                <div class="result-field">
                    <div class="field-label">Gender</div>
                    <div class="field-value"><?php echo htmlspecialchars($matched_student['gender'] ?? '—'); ?></div>
                </div>
            </div>

            <hr class="divider">

            <div class="result-grid">
                <div class="result-field">
                    <div class="field-label">Course</div>
                    <div class="field-value"><?php echo htmlspecialchars($matched_student['course_category'] ?? '—'); ?></div>
                </div>
                <div class="result-field">
                    <div class="field-label">Course Start</div>
                    <div class="field-value"><?php echo htmlspecialchars($matched_student['course_start_date'] ?? '—'); ?></div>
                </div>
                <div class="result-field">
                    <div class="field-label">Course End</div>
                    <div class="field-value"><?php echo htmlspecialchars($matched_student['course_end_date'] ?? '—'); ?></div>
                </div>
                <div class="result-field">
                    <div class="field-label">Total Fee</div>
                    <div class="field-value">৳ <?php echo number_format((float)($matched_student['course_fee'] ?? 0), 2); ?></div>
                </div>
                <div class="result-field">
                    <div class="field-label">Paid</div>
                    <div class="field-value highlight">৳ <?php echo number_format((float)($matched_student['paid_fee'] ?? 0), 2); ?></div>
                </div>
                <div class="result-field">
                    <div class="field-label">Due</div>
                    <?php $due = (float)($matched_student['course_fee'] ?? 0) - (float)($matched_student['paid_fee'] ?? 0); ?>
                    <div class="field-value <?php echo $due > 0 ? 'danger' : ''; ?>">
                        ৳ <?php echo number_format($due, 2); ?>
                    </div>
                </div>
            </div>

            <hr class="divider">

            <div class="result-grid">
                <div class="result-field">
                    <div class="field-label">Present Address</div>
                    <div class="field-value"><?php echo htmlspecialchars($matched_student['present_address'] ?? '—'); ?></div>
                </div>
                <div class="result-field">
                    <div class="field-label">NID / Birth ID</div>
                    <div class="field-value"><?php echo htmlspecialchars($matched_student['nid_birth_id'] ?? '—'); ?></div>
                </div>
                <div class="result-field">
                    <div class="field-label">District</div>
                    <div class="field-value"><?php echo htmlspecialchars($matched_student['district'] ?? '—'); ?></div>
                </div>
                <div class="result-field">
                    <div class="field-label">Occupation</div>
                    <div class="field-value"><?php echo htmlspecialchars($matched_student['occupation'] ?? '—'); ?></div>
                </div>
            </div>
        </div>

        <div class="action-links">
            <a href="edit.php?student_id=<?php echo urlencode($matched_student['student_id']); ?>" class="action-link primary">✏️ Edit Student</a>
            <a href="form_view.php?student_id=<?php echo urlencode($matched_student['student_id']); ?>" class="action-link secondary">📋 View Form</a>
            <a href="invoice.php?student_id=<?php echo urlencode($matched_student['student_id']); ?>" class="action-link secondary">🧾 Invoice</a>
            <a href="face_search.php" class="action-link secondary">🔁 Search Again</a>
        </div>
    </div>

    <?php else: ?>
    <!-- ── SEARCH FORM ── -->
    <div style="display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:start;" id="layoutGrid">

        <!-- Upload Card -->
        <div class="glass-card">
            <h3 style="font-size:16px;font-weight:700;margin-bottom:6px;color:var(--accent);">📷 Upload a Photo to Search</h3>
            <p style="color:var(--muted);font-size:13px;margin-bottom:24px;">
                Upload any clear photo of a person. The AI will identify them from the student database — even if the photo differs in angle, lighting, or age.
            </p>

            <form method="POST" enctype="multipart/form-data" id="searchForm">
                <div class="upload-zone" id="uploadZone">
                    <input type="file" name="search_photo" id="photoInput" accept="image/*" required>
                    <div class="upload-icon">📸</div>
                    <div class="upload-title">Click or drag a photo here</div>
                    <div class="upload-sub">Supports JPG, PNG, WEBP · Max 10 MB</div>
                </div>

                <div id="previewWrap">
                    <img id="previewImg" src="" alt="Preview">
                    <div class="preview-label" id="previewName"></div>
                    <div style="margin-top:10px;">
                        <label style="cursor:pointer;color:var(--accent);font-size:13px;font-weight:600;" for="photoInput">
                            🔄 Change photo
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-search" id="searchBtn">
                    <span class="spinner" id="btnSpinner"></span>
                    <span id="btnText">🔍 Search with AI</span>
                </button>

                <p style="font-size:11px;color:var(--muted);text-align:center;margin-top:12px;">
                    Self‑hosted face recognition · No external API
                </p>
            </form>
        </div>

        <!-- How It Works Card -->
        <div>
            <div class="glass-card">
                <h3 style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:16px;">⚡ How It Works</h3>
                <div class="how-steps" style="flex-direction:column;">
                    <div class="how-step" style="min-width:unset;">
                        <div class="how-step-num">📤</div>
                        <div style="font-size:13px;font-weight:600;margin-bottom:4px;">Upload Photo</div>
                        <div class="how-step-text">Upload any photo of the person you want to find — mobile selfie, ID photo, or any image.</div>
                    </div>
                    <div class="how-step" style="min-width:unset;">
                        <div class="how-step-num">🧠</div>
                        <div style="font-size:13px;font-weight:600;margin-bottom:4px;">AI Analyzes</div>
                        <div class="how-step-text">Local face recognition model compares facial features against all student photos in the database.</div>
                    </div>
                    <div class="how-step" style="min-width:unset;">
                        <div class="how-step-num">✅</div>
                        <div style="font-size:13px;font-weight:600;margin-bottom:4px;">Get Results</div>
                        <div class="how-step-text">Full student profile, fees, course info, and contact details are shown instantly.</div>
                    </div>
                </div>
            </div>

            <div class="glass-card" style="margin-top:20px;">
                <h3 style="font-size:14px;font-weight:700;color:var(--accent4);margin-bottom:10px;">💡 Tips for Best Results</h3>
                <ul style="color:var(--muted);font-size:12px;line-height:2;padding-left:18px;">
                    <li>Use a clear, front-facing photo</li>
                    <li>Good lighting improves accuracy</li>
                    <li>Avoid heavily blurred images</li>
                    <li>Single person per photo works best</li>
                    <li>Works with selfies, ID cards, or any photo</li>
                </ul>
            </div>
        </div>

    </div>
    <?php endif; ?>

</main>

<script>
// Live clock
function updateClock() {
    const now = new Date();
    const clockEl = document.getElementById('liveClock');
    if (clockEl) {
        clockEl.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
}
setInterval(updateClock, 1000);
updateClock();

// ----- SIDEBAR TOGGLE (exactly as in attendance.php) -----
const sidebar = document.querySelector('.sidebar');
const main = document.getElementById('mainContent');
const toggleBtn = document.getElementById('sidebarToggle');
const hamburger = document.getElementById('hamburgerBtn');

if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        toggleBtn.classList.toggle('collapsed');
        main.classList.toggle('collapsed');
        toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '▶' : '◀';
    });
}

// Mobile hamburger
if (hamburger) {
    hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('mobile-open');
    });
}

// Submenu toggles (using closest for safety)
document.querySelectorAll('.menu-toggle').forEach(toggle => {
    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const group = toggle.closest('.menu-group');
        if (group) group.classList.toggle('open');
    });
});

// Initial state for narrow screens (same as attendance.php)
if (window.innerWidth < 900) {
    sidebar.classList.add('collapsed');
    main.classList.add('collapsed');
    if (toggleBtn) {
        toggleBtn.classList.add('collapsed');
        toggleBtn.textContent = '▶';
    }
}

// Photo preview (unchanged)
const photoInput = document.getElementById('photoInput');
const uploadZone = document.getElementById('uploadZone');
const previewWrap = document.getElementById('previewWrap');
const previewImg = document.getElementById('previewImg');
const previewName = document.getElementById('previewName');

if (photoInput) {
    photoInput.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            previewImg.src = e.target.result;
            previewName.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
            if (uploadZone) uploadZone.style.display = 'none';
            if (previewWrap) previewWrap.style.display = 'block';
        };
        reader.readAsDataURL(file);
    });
}

// Drag & drop
if (uploadZone) {
    ['dragenter', 'dragover'].forEach(ev => {
        uploadZone.addEventListener(ev, e => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
    });
    ['dragleave', 'drop'].forEach(ev => {
        uploadZone.addEventListener(ev, () => {
            uploadZone.classList.remove('dragover');
        });
    });
    uploadZone.addEventListener('drop', e => {
        e.preventDefault();
        photoInput.files = e.dataTransfer.files;
        photoInput.dispatchEvent(new Event('change'));
    });
}

// Form submit spinner
const searchForm = document.getElementById('searchForm');
if (searchForm) {
    searchForm.addEventListener('submit', () => {
        const btn = document.getElementById('searchBtn');
        const spinner = document.getElementById('btnSpinner');
        const txt = document.getElementById('btnText');
        if (btn) btn.disabled = true;
        if (spinner) spinner.style.display = 'inline-block';
        if (txt) txt.textContent = 'Searching… this may take 5-15 seconds';
    });
}
</script>
</body>
</html>