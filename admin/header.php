<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Freelancing Students Dashboard</title>
<style>
.navbar {
    background-color: #333;
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


</style>
</head>
<body>

<div class="navbar">
    <strong>AR TECH SOLUTION</strong>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>
</body>
