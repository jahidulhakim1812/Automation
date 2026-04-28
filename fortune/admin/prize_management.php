<?php
session_start();
require_once __DIR__ . '/../config.php';

// Authentication check
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Flash message helper
function flashMessage($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function displayFlash() {
    if (isset($_SESSION['flash'])) {
        $class = $_SESSION['flash']['type'] === 'success' ? 'message' : 'error';
        echo '<div class="' . $class . '">' . htmlspecialchars($_SESSION['flash']['msg']) . '</div>';
        unset($_SESSION['flash']);
    }
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_segment'])) {
        $label = trim($_POST['label']);
        $color = trim($_POST['color']);
        if ($label && $color) {
            $stmt = $pdo->prepare("INSERT INTO segments (label, color) VALUES (?, ?)");
            $stmt->execute([$label, $color]);
            flashMessage('success', 'Prize added successfully!');
        } else {
            flashMessage('error', 'All fields are required.');
        }
        header('Location: prize_management.php');
        exit;
    } elseif (isset($_POST['edit_segment'])) {
        $id = (int)$_POST['id'];
        $label = trim($_POST['label']);
        $color = trim($_POST['color']);
        $stmt = $pdo->prepare("UPDATE segments SET label = ?, color = ? WHERE id = ?");
        $stmt->execute([$label, $color, $id]);
        flashMessage('success', 'Prize updated.');
        header('Location: prize_management.php');
        exit;
    } elseif (isset($_POST['delete_segment'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM segments WHERE id = ?");
        $stmt->execute([$id]);
        flashMessage('success', 'Prize deleted.');
        header('Location: prize_management.php');
        exit;
    }
}

// Fetch all segments
$search = $_GET['search'] ?? '';
$whereSQL = '';
if (!empty($search)) {
    $whereSQL = "WHERE label LIKE :search";
    $stmt = $pdo->prepare("SELECT * FROM segments $whereSQL ORDER BY id ASC");
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM segments ORDER BY id ASC");
}
$segments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>SpinMaster | Prize Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Light mode (default) */
        :root {
            --bg-gradient: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            --sidebar-bg: rgba(255, 255, 255, 0.96);
            --sidebar-border: rgba(0, 0, 0, 0.08);
            --card-bg: rgba(255, 255, 255, 0.75);
            --text-primary: #1a2c3e;
            --text-secondary: #4a627a;
            --accent: #f9c74f;
            --accent-dark: #f9844a;
            --border-color: rgba(0, 0, 0, 0.08);
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
            --hover-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.1);
        }

        body.dark {
            --bg-gradient: radial-gradient(circle at 10% 20%, #0f1f2a, #050e14);
            --sidebar-bg: rgba(10, 20, 28, 0.96);
            --sidebar-border: rgba(255, 215, 100, 0.2);
            --card-bg: rgba(20, 35, 45, 0.65);
            --text-primary: #eef5ff;
            --text-secondary: #b0c4de;
            --accent: #FFD966;
            --accent-dark: #ffb347;
            --border-color: rgba(255, 215, 100, 0.2);
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            --hover-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.5);
        }

        body {
            font-family: 'Inter', 'Poppins', system-ui, sans-serif;
            background: var(--bg-gradient);
            overflow-x: hidden;
            color: var(--text-primary);
            transition: background 0.3s ease, color 0.2s ease;
            min-height: 100vh;
        }

        /* Sidebar (identical to dashboard) */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100%;
            background: var(--sidebar-bg);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--sidebar-border);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            transform: translateX(-280px);
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 20px;
        }

        .sidebar-header h2 {
            font-size: 1.8rem;
            background: linear-gradient(135deg, #FFE5A3, #FFB347);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .sidebar-header p {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .nav-menu {
            flex: 1;
            list-style: none;
        }

        .nav-item {
            margin: 8px 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 20px;
            border-radius: 60px;
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(249, 199, 79, 0.15);
            color: var(--accent);
        }

        .nav-link .icon {
            font-size: 1.3rem;
        }

        .sidebar-footer {
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
            margin-top: auto;
        }

        .logout-link {
            color: #ff8a8a;
        }

        /* Main content */
        .main-content {
            margin-left: 280px;
            transition: margin-left 0.3s;
            padding: 25px 35px;
            min-height: 100vh;
        }

        .main-content.expanded {
            margin-left: 0;
        }

        /* Top bar with toggles */
        .top-bar {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border-radius: 60px;
            padding: 12px 25px;
            margin-bottom: 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .top-bar-left h3 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .top-bar-right {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .icon-btn {
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.2s;
            color: var(--text-primary);
            width: 40px;
            height: 40px;
        }

        .icon-btn:hover {
            background: rgba(249, 199, 79, 0.2);
            transform: scale(1.05);
        }

        .sidebar-toggle-btn {
            background: var(--accent);
            color: #1e2c34;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .dark-mode-btn {
            background: rgba(128, 128, 128, 0.2);
            backdrop-filter: blur(4px);
        }

        .date {
            font-size: 0.9rem;
            color: var(--text-secondary);
            background: rgba(0, 0, 0, 0.05);
            padding: 5px 12px;
            border-radius: 40px;
        }

        /* Prize management card */
        .management-card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 28px 24px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 25px;
            gap: 15px;
        }

        .card-header h2 {
            font-size: 1.6rem;
            color: var(--accent);
        }

        .btn {
            background: linear-gradient(115deg, #F9C74F, #F9844A);
            border: none;
            padding: 10px 24px;
            border-radius: 60px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
            color: #1e2c34;
        }

        .btn-small {
            padding: 6px 16px;
            font-size: 0.8rem;
        }

        .btn-danger {
            background: #ff6b6b;
            color: white;
        }
        .btn-danger:hover {
            background: #ff4757;
        }

        .search-bar {
            margin-bottom: 25px;
        }
        .search-bar input {
            width: 100%;
            max-width: 300px;
            padding: 10px 18px;
            border-radius: 60px;
            border: none;
            background: rgba(255,255,240,0.9);
            font-size: 0.9rem;
        }

        .table-wrapper {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        th {
            color: var(--accent);
            font-weight: 600;
        }
        .color-preview {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            vertical-align: middle;
            margin-right: 8px;
            border: 1px solid rgba(0,0,0,0.2);
        }

        /* Modals */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(8px);
            justify-content: center;
            align-items: center;
            z-index: 1200;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: 48px;
            padding: 30px;
            width: 90%;
            max-width: 450px;
            border: 1px solid var(--accent);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 60px;
            border: none;
            background: rgba(255,255,240,0.95);
        }
        .message {
            background: #2d6a4f;
            padding: 12px 20px;
            border-radius: 60px;
            margin-bottom: 20px;
            color: white;
        }
        .error {
            background: #ba2d2d;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px 15px;
            }
            .sidebar {
                width: 260px;
            }
            .top-bar {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
                text-align: center;
            }
            .top-bar-right {
                justify-content: center;
            }
            .card-header {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
<button class="icon-btn sidebar-toggle-btn" id="mobileSidebarToggle" style="position: fixed; top: 15px; left: 15px; z-index: 1100; display: none;">⟫</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>🎡 SpinMaster</h2>
        <p>Prize Manager</p>
    </div>
    <ul class="nav-menu">
        <li class="nav-item"><a href="admin_dashboard.php" class="nav-link"><span class="icon">📊</span> Dashboard</a></li>
        <li class="nav-item"><a href="prize_management.php" class="nav-link active"><span class="icon">🎯</span> Prize Management</a></li>
        <li class="nav-item"><a href="admin.php" class="nav-link"><span class="icon">📜</span> Spin History</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link logout-link"><span class="icon">🚪</span> Logout</a>
    </div>
</div>

<div class="main-content" id="mainContent">
    <div class="top-bar">
        <div class="top-bar-left">
            <h3>🎯 Prize Management</h3>
        </div>
        <div class="top-bar-right">
            <button class="icon-btn dark-mode-btn" id="darkModeToggleBtn" title="Dark/Light mode">🌙</button>
            <button class="icon-btn sidebar-toggle-btn" id="desktopSidebarToggle" title="Toggle Sidebar">⟪</button>
            <div class="date">📅 <?php echo date('F j, Y'); ?></div>
        </div>
    </div>

    <?php displayFlash(); ?>

    <div class="management-card">
        <div class="card-header">
            <h2>🎁 Prize Segments</h2>
            <button class="btn" id="openAddModal">+ Add New Prize</button>
        </div>

        <div class="search-bar">
            <form method="GET">
                <input type="text" name="search" placeholder="Search prizes..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Label</th>
                        <th>Color</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($segments)): ?>
                        <tr><td colspan="4">No prizes found. Click "Add New Prize" to create one.</td></tr>
                    <?php else: foreach ($segments as $seg): ?>
                        <tr>
                            <td><?php echo $seg['id']; ?></td>
                            <td><?php echo htmlspecialchars($seg['label']); ?></td>
                            <td>
                                <span class="color-preview" style="background: <?php echo $seg['color']; ?>;"></span>
                                <?php echo $seg['color']; ?>
                            </td>
                            <td>
                                <button class="btn btn-small edit-prize" data-id="<?php echo $seg['id']; ?>" data-label="<?php echo htmlspecialchars($seg['label']); ?>" data-color="<?php echo $seg['color']; ?>">✏️ Edit</button>
                                <form method="post" style="display:inline-block;" onsubmit="return confirm('Delete this prize? This action cannot be undone.');">
                                    <input type="hidden" name="id" value="<?php echo $seg['id']; ?>">
                                    <button type="submit" name="delete_segment" class="btn btn-small btn-danger">🗑️ Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Prize Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-bottom:20px;">➕ Add New Prize</h3>
        <form method="post">
            <div class="form-group">
                <label>Prize Label *</label>
                <input type="text" name="label" required placeholder="e.g., Free Coffee, $10 Gift Card">
            </div>
            <div class="form-group">
                <label>Color *</label>
                <input type="color" name="color" value="#F94144">
            </div>
            <button type="submit" name="add_segment" class="btn" style="width:100%;">Save Prize</button>
            <button type="button" class="btn btn-danger closeModal" style="width:100%; margin-top:10px;">Cancel</button>
        </form>
    </div>
</div>

<!-- Edit Prize Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-bottom:20px;">✏️ Edit Prize</h3>
        <form method="post">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label>Prize Label *</label>
                <input type="text" name="label" id="edit_label" required>
            </div>
            <div class="form-group">
                <label>Color *</label>
                <input type="color" name="color" id="edit_color">
            </div>
            <button type="submit" name="edit_segment" class="btn" style="width:100%;">Update Prize</button>
            <button type="button" class="btn btn-danger closeModal" style="width:100%; margin-top:10px;">Cancel</button>
        </form>
    </div>
</div>

<script>
    // Sidebar toggle logic
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const desktopToggle = document.getElementById('desktopSidebarToggle');
    const mobileToggle = document.getElementById('mobileSidebarToggle');

    function toggleSidebar() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
        const collapsed = sidebar.classList.contains('collapsed');
        if (desktopToggle) desktopToggle.innerHTML = collapsed ? '⟫' : '⟪';
        if (mobileToggle) mobileToggle.innerHTML = collapsed ? '⟫' : '⟪';
    }

    if (desktopToggle) desktopToggle.addEventListener('click', toggleSidebar);
    if (mobileToggle) mobileToggle.addEventListener('click', toggleSidebar);

    function handleResponsive() {
        const isMobile = window.innerWidth <= 768;
        if (isMobile) {
            if (!sidebar.classList.contains('collapsed')) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
                if (desktopToggle) desktopToggle.innerHTML = '⟫';
                if (mobileToggle) mobileToggle.innerHTML = '⟫';
            }
            if (mobileToggle) mobileToggle.style.display = 'flex';
            if (desktopToggle) desktopToggle.style.display = 'none';
        } else {
            if (sidebar.classList.contains('collapsed')) {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
                if (desktopToggle) desktopToggle.innerHTML = '⟪';
                if (mobileToggle) mobileToggle.innerHTML = '⟪';
            }
            if (mobileToggle) mobileToggle.style.display = 'none';
            if (desktopToggle) desktopToggle.style.display = 'flex';
        }
    }
    window.addEventListener('resize', handleResponsive);
    handleResponsive();

    // Dark mode toggle
    const darkModeToggle = document.getElementById('darkModeToggleBtn');
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark');
        darkModeToggle.innerHTML = '☀️';
    } else {
        darkModeToggle.innerHTML = '🌙';
    }
    darkModeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark');
        const isDark = document.body.classList.contains('dark');
        localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
        darkModeToggle.innerHTML = isDark ? '☀️' : '🌙';
    });

    // Modals
    const addModal = document.getElementById('addModal');
    const editModal = document.getElementById('editModal');
    document.getElementById('openAddModal').addEventListener('click', () => addModal.classList.add('active'));
    document.querySelectorAll('.closeModal').forEach(btn => btn.addEventListener('click', () => {
        addModal.classList.remove('active');
        editModal.classList.remove('active');
    }));
    document.querySelectorAll('.edit-prize').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_id').value = btn.dataset.id;
            document.getElementById('edit_label').value = btn.dataset.label;
            document.getElementById('edit_color').value = btn.dataset.color;
            editModal.classList.add('active');
        });
    });
    window.onclick = (e) => {
        if (e.target === addModal) addModal.classList.remove('active');
        if (e.target === editModal) editModal.classList.remove('active');
    };
</script>
</body>
</html>