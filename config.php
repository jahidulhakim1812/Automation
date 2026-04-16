<?php
// config.php - Load application settings
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "freelancing");
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
}

// Fetch all settings
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM app_settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Dark mode
$dark_mode = isset($settings['dark_mode']) && $settings['dark_mode'] == '1';

// Background image
$bg_image = isset($settings['background_image']) && !empty($settings['background_image']) 
            ? $settings['background_image'] 
            : 'uploads/banner.jpg';

// Sidebar labels
$sidebar_labels = isset($settings['sidebar_labels']) ? json_decode($settings['sidebar_labels'], true) : [];

function get_sidebar_label($key, $default) {
    global $sidebar_labels;
    return isset($sidebar_labels[$key]) && !empty($sidebar_labels[$key]) 
           ? htmlspecialchars($sidebar_labels[$key]) 
           : $default;
}
?>