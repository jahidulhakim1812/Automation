<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "freelancing";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle form submissions
$message = "";
$error = "";

// Add new service
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_service'])) {
    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $duration = trim($_POST['duration']);
    $fee = trim($_POST['fee']);
    
    if (!empty($service_name)) {
        $stmt = $conn->prepare("INSERT INTO services (service_name, description, category, duration, fee) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssd", $service_name, $description, $category, $duration, $fee);
        
        if ($stmt->execute()) {
            $message = "Service added successfully!";
        } else {
            $error = "Error adding service: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Service name is required!";
    }
}

// Update service
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_service'])) {
    $id = $_POST['service_id'];
    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $duration = trim($_POST['duration']);
    $fee = trim($_POST['fee']);
    
    if (!empty($service_name)) {
        $stmt = $conn->prepare("UPDATE services SET service_name=?, description=?, category=?, duration=?, fee=? WHERE id=?");
        $stmt->bind_param("ssssdi", $service_name, $description, $category, $duration, $fee, $id);
        
        if ($stmt->execute()) {
            $message = "Service updated successfully!";
        } else {
            $error = "Error updating service: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Service name is required!";
    }
}

// Delete service
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM services WHERE id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Service deleted successfully!";
    } else {
        $error = "Error deleting service: " . $conn->error;
    }
    $stmt->close();
}

// Fetch all services
$services = [];
$result = $conn->query("SELECT * FROM services ORDER BY category, service_name");
if ($result) {
    $services = $result->fetch_all(MYSQLI_ASSOC);
}

// Check if services table exists, if not create it
$table_check = $conn->query("SHOW TABLES LIKE 'services'");
if ($table_check->num_rows == 0) {
    $create_table = "CREATE TABLE services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_name VARCHAR(255) NOT NULL,
        description TEXT,
        category VARCHAR(100),
        duration VARCHAR(50),
        fee DECIMAL(10,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table)) {
        // Insert some default services
        $default_services = [
            ['Graphic Design', 'Logo design, banner creation, social media graphics', 'Graphic Design', '2 months', 8000.00],
            ['Video Editing', 'Professional video editing and motion graphics', 'Video Editing', '3 months', 12000.00],
            ['Social Media Marketing', 'Social media strategy and content management', 'Social Media Marketing', '2 months', 10000.00],
            ['Digital Marketing', 'SEO, SEM, and digital advertising', 'Digital Marketing', '4 months', 15000.00],
            ['Microsoft Office', 'Word, Excel, PowerPoint training', 'Office Application', '1 month', 5000.00]
        ];
        
        $stmt = $conn->prepare("INSERT INTO services (service_name, description, category, duration, fee) VALUES (?, ?, ?, ?, ?)");
        foreach ($default_services as $service) {
            $stmt->bind_param("ssssd", $service[0], $service[1], $service[2], $service[3], $service[4]);
            $stmt->execute();
        }
        $stmt->close();
    }
}

// Get categories for dropdown
$categories_result = $conn->query("SELECT DISTINCT category FROM services ORDER BY category");
$existing_categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $existing_categories[] = $row['category'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Services - Freelancing Students</title>
<style>
* { box-sizing: border-box; }
body {
    font-family: 'Segoe UI', sans-serif;
    margin: 0;
    background-color: #ffffff;
}
.navbar {
    background-color: #1a1a1a;
    color: white;
    padding: 15px 30px;
    font-size: 22px;
    display: flex;
    justify-content: center;
    align-items: center;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}
.logout-btn {
    position: absolute;
    right: 10px;
    background: linear-gradient(135deg, #ff4d4d, #cc0000);
    color: white;
    padding: 8px 15px;
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
.side-nav {
    position: fixed;
    top: 60px;
    left: 0;
    width: 220px;
    height: calc(100% - 60px);
    background-color: #2c3e50;
    padding-top: 20px;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    transition: transform 0.3s ease;
}
.side-nav.collapsed {
    transform: translateX(-100%);
}
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
.horizontal-submenu { flex-direction: row; flex-wrap: wrap; gap: 5px; }
.horizontal-submenu a { padding: 10px 20px; white-space: nowrap; }
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
.container {
    margin-left: 240px;
    padding: 130px 30px 100px;
    transition: margin-left 0.3s ease;
    background-color: #ffffff;
}
.container.collapsed { margin-left: 20px; }
h2 { text-align: center; color: #2c3e50; margin-bottom: 40px; font-size: 28px; }

/* Services specific styles */
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    text-align: center;
}
.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.services-container {
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
}

.services-form, .services-list {
    background-color: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
}

.services-form {
    flex: 1;
    min-width: 300px;
}

.services-list {
    flex: 2;
    min-width: 300px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #2c3e50;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
    background-color: #fff;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    border-color: #1abc9c;
    outline: none;
    box-shadow: 0 0 0 2px rgba(26, 188, 156, 0.2);
}

.form-group textarea {
    height: 100px;
    resize: vertical;
}

.btn {
    background: linear-gradient(135deg, #1abc9c, #16a085);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 25px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    transition: all 0.3s ease;
    width: 100%;
}

.btn:hover {
    background: linear-gradient(135deg, #16a085, #1abc9c);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.btn-delete {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    padding: 5px 15px;
    font-size: 14px;
    width: auto;
}

.btn-delete:hover {
    background: linear-gradient(135deg, #c0392b, #e74c3c);
}

.btn-edit {
    background: linear-gradient(135deg, #3498db, #2980b9);
    padding: 5px 15px;
    font-size: 14px;
    width: auto;
    margin-right: 10px;
}

.btn-edit:hover {
    background: linear-gradient(135deg, #2980b9, #3498db);
}

.services-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.services-table th,
.services-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.services-table th {
    background-color: #f8f9fa;
    font-weight: bold;
    color: #2c3e50;
    border-top: 2px solid #1abc9c;
}

.services-table tr:hover {
    background-color: #f5f5f5;
}

.category-badge {
    background-color: #e8f4fc;
    color: #3498db;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
}

.fee-badge {
    background-color: #e8f6f3;
    color: #16a085;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
}

.actions {
    display: flex;
    gap: 5px;
}

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
    z-index: 1000;
}

@media (max-width: 768px) {
    .services-container {
        flex-direction: column;
    }
    
    .services-form,
    .services-list {
        width: 100%;
    }
    
    .container {
        margin-left: 20px;
        padding: 100px 15px 80px;
    }
    
    .side-nav {
        width: 200px;
    }
}
</style>
</head>
<body>

<div class="navbar">
    <strong>AR TECH SOLUTION</strong>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

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
            <a href="completed_students.php">Course Complete</a>
            <a href="incomplete_students.php">Course Incomplete</a>
            <a href="ongoing_students.php">Ongoing</a>
        </div>
    </div>

    <!-- Add Services link to sidebar -->
    <div class="menu-group active">
        <div class="menu-toggle">🛠️ Services ▾</div>
        <div class="submenu">
            <a href="services.php">Manage Services</a>
            <a href="service_categories.php">Service Categories</a>
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
            <a href="payment_due.php">Due Payment List</a>
        </div>
    </div>

    <div class="menu-group">
        <div class="menu-toggle">📆 Attendance ▾</div>
        <div class="submenu">
            <a href="attendance.php">Take Attendance</a>
            <a href="attendance_report.php">View attendance Report</a>
        </div>
    </div>

    <div class="menu-group">
        <div class="menu-toggle">📜 Certificate ▾</div>
        <div class="submenu">
            <a href="upload_certificate.php">Upload Certificate</a>
            <a href="certificate_list.php">View Certificate</a>
        </div>
    </div>

    <div class="menu-group">
        <div class="menu-toggle">🎬 Video ▾</div>
        <div class="submenu">
            <a href="upload_video.php">Upload Video</a>
            <a href="view_videos.php">View Videos</a>
        </div>
    </div>

    <a href="routine_generator.php">🕒 Routine</a>
</div>

<div class="toggle-arrow" id="toggleBtn">◀</div>

<div class="container" id="mainContent">
    <h2>🛠️ Manage Services</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="services-container">
        <!-- Add/Edit Service Form -->
        <div class="services-form">
            <h3 style="color: #2c3e50; margin-top: 0; border-bottom: 2px solid #1abc9c; padding-bottom: 10px;"><?php echo isset($_GET['edit']) ? 'Edit Service' : 'Add New Service'; ?></h3>
            <form method="POST" action="">
                <?php if (isset($_GET['edit'])): 
                    $edit_id = $_GET['edit'];
                    $edit_service = [];
                    foreach ($services as $service) {
                        if ($service['id'] == $edit_id) {
                            $edit_service = $service;
                            break;
                        }
                    }
                ?>
                    <input type="hidden" name="service_id" value="<?php echo $edit_id; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="service_name">Service Name *</label>
                    <input type="text" id="service_name" name="service_name" 
                           value="<?php echo isset($edit_service['service_name']) ? htmlspecialchars($edit_service['service_name']) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?php echo isset($edit_service['description']) ? htmlspecialchars($edit_service['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">Select Category</option>
                        <?php foreach ($existing_categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" 
                                <?php if (isset($edit_service['category']) && $edit_service['category'] == $cat) echo 'selected'; ?>>
                                <?php echo $cat; ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="Other">Other (New Category)</option>
                    </select>
                    <input type="text" id="new_category" name="new_category" placeholder="Enter new category" 
                           style="margin-top: 5px; display: none;">
                </div>
                
                <div class="form-group">
                    <label for="duration">Duration</label>
                    <input type="text" id="duration" name="duration" 
                           value="<?php echo isset($edit_service['duration']) ? htmlspecialchars($edit_service['duration']) : ''; ?>" 
                           placeholder="e.g., 2 months">
                </div>
                
                <div class="form-group">
                    <label for="fee">Fee (৳)</label>
                    <input type="number" id="fee" name="fee" step="0.01" 
                           value="<?php echo isset($edit_service['fee']) ? htmlspecialchars($edit_service['fee']) : ''; ?>" 
                           placeholder="0.00">
                </div>
                
                <?php if (isset($_GET['edit'])): ?>
                    <button type="submit" name="update_service" class="btn">Update Service</button>
                    <a href="services.php" class="btn" style="background: #95a5a6; margin-top: 10px; text-decoration: none; text-align: center; display: block;">Cancel</a>
                <?php else: ?>
                    <button type="submit" name="add_service" class="btn">Add Service</button>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Services List -->
        <div class="services-list">
            <h3 style="color: #2c3e50; margin-top: 0; border-bottom: 2px solid #1abc9c; padding-bottom: 10px;">Available Services (<?php echo count($services); ?>)</h3>
            
            <?php if (empty($services)): ?>
                <p style="text-align: center; color: #666; padding: 20px;">No services found. Add your first service using the form.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="services-table">
                        <thead>
                            <tr>
                                <th>Service Name</th>
                                <th>Category</th>
                                <th>Duration</th>
                                <th>Fee</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                                <tr>
                                    <td>
                                        <strong style="color: #2c3e50;"><?php echo htmlspecialchars($service['service_name']); ?></strong>
                                        <?php if (!empty($service['description'])): ?>
                                            <br><small style="color: #666;"><?php echo htmlspecialchars(substr($service['description'], 0, 50)); ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="category-badge"><?php echo htmlspecialchars($service['category']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($service['duration']); ?></td>
                                    <td>
                                        <span class="fee-badge">৳ <?php echo number_format($service['fee'], 2); ?></span>
                                    </td>
                                    <td class="actions">
                                        <a href="?edit=<?php echo $service['id']; ?>" class="btn btn-edit">Edit</a>
                                        <a href="?delete=<?php echo $service['id']; ?>" 
                                           class="btn btn-delete" 
                                           onclick="return confirm('Are you sure you want to delete this service?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="footer">
    &copy; <?php echo date("Y"); ?> Freelancing Students Management System | All Rights Reserved
</div>

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

// Show/hide new category input
const categorySelect = document.getElementById('category');
const newCategoryInput = document.getElementById('new_category');

if (categorySelect && newCategoryInput) {
    categorySelect.addEventListener('change', function() {
        if (this.value === 'Other') {
            newCategoryInput.style.display = 'block';
            newCategoryInput.required = true;
            // Rename the input for PHP processing
            newCategoryInput.name = 'category';
            categorySelect.name = 'old_category';
        } else {
            newCategoryInput.style.display = 'none';
            newCategoryInput.required = false;
            // Restore original names
            newCategoryInput.name = 'new_category';
            categorySelect.name = 'category';
        }
    });
    
    // Check if Other is selected on page load (for edit mode)
    if (categorySelect.value === 'Other') {
        newCategoryInput.style.display = 'block';
        newCategoryInput.required = true;
        newCategoryInput.name = 'category';
        categorySelect.name = 'old_category';
    }
}

// Auto-expand services menu
document.addEventListener('DOMContentLoaded', function() {
    const servicesMenu = document.querySelector('.menu-group:nth-child(4)'); // 4th menu group is Services
    if (servicesMenu) {
        servicesMenu.classList.add('active');
    }
});
</script>

</body>
</html>