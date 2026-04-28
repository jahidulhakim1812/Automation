<?php
require_once 'config.php';

// Create all tables (same as before)
$sql = "
CREATE TABLE IF NOT EXISTS wheel_segments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(100) NOT NULL,
    color VARCHAR(7) NOT NULL DEFAULT '#CCCCCC',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS spin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT NOT NULL,
    prize_label VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    spin_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (segment_id) REFERENCES wheel_segments(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

try {
    $pdo->exec($sql);
    echo "Tables created successfully.<br>";
    
    // Insert default admin (plain text password)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
    $stmt->execute(['admin']);
    if ($stmt->fetchColumn() == 0) {
        $plainPassword = 'admin123';   // plain text
        $insert = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $insert->execute(['admin', $plainPassword]);
        echo "Default admin created: username = admin, password = admin123<br>";
    } else {
        echo "Admin user already exists.<br>";
    }
    
    // Insert wheel segments (unchanged)...
    $stmt = $pdo->query("SELECT COUNT(*) FROM wheel_segments");
    if ($stmt->fetchColumn() == 0) {
        $segments = [
            ['SPRINT', '#FF6B6B', 1],
            ['FASTAPPAREL', '#4ECDC4', 2],
            // ... (all segments as before)
        ];
        $insert = $pdo->prepare("INSERT INTO wheel_segments (label, color, sort_order) VALUES (?, ?, ?)");
        foreach ($segments as $seg) {
            $insert->execute($seg);
        }
        echo "Sample segments inserted.<br>";
    }
    
    echo "<hr><strong>Setup complete!</strong> <a href='admin/admin_login.php'>Go to Admin Login</a>";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                