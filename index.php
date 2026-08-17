<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Demon Slayer Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h1 class="text-center mb-4">Welcome to Demon Slayer Hub</h1>

    <p class="lead text-center">Hello, <strong><?= htmlspecialchars($_SESSION["username"]) ?></strong>!</p>

    <div class="text-center mt-4">
        <a href="characters.php" class="btn btn-primary m-2">Characters</a>
        <a href="profile.php" class="btn btn-secondary m-2">My Profile</a>
        <a href="reviews.php" class="btn btn-info m-2">My Reviews</a>
        <a href="logout.php" class="btn btn-danger m-2">Logout</a>
    </div>
</body>
</html>
