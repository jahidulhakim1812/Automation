<?php
$conn = new mysqli("localhost","root","","freelancing");
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$result = $conn->query("SELECT * FROM videos ORDER BY uploaded_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
<title>View Videos</title>
<style>
body{font-family: Arial; background:#f4f4f4; padding:20px;}
.container{max-width:800px; margin:auto;}
.video-box{background:white; padding:15px; margin:15px 0; border-radius:5px; box-shadow:0 0 8px #ccc;}
video{width:100%; max-height:400px; margin-top:10px;}
</style>
</head>
<body>

<div class="container">
<h2>All Videos</h2>

<?php if($result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
        <div class="video-box">
            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
            <p><?php echo htmlspecialchars($row['description']); ?></p>
            <video controls>
                <source src="uploads/<?php echo htmlspecialchars($row['filename']); ?>" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p>No videos uploaded yet.</p>
<?php endif; ?>

</div>
</body>
</html>
