<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include("includes/db.php");
$user_id = $_SESSION["user_id"];

// Handle remove from favorites
if (isset($_GET["remove_fav"])) {
    $remove_id = $_GET["remove_fav"];
    $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND character_id = ?");
    $stmt->bind_param("ii", $user_id, $remove_id);
    $stmt->execute();
    $stmt->close();
    header("Location: profile.php");
    exit;
}

// Fetch user's reviews
$reviews = $conn->prepare("SELECT r.content, r.rating, r.created_at, c.name AS character_name
                           FROM reviews r
                           JOIN characters c ON r.character_id = c.id
                           WHERE r.user_id = ?
                           ORDER BY r.created_at DESC");
$reviews->bind_param("i", $user_id);
$reviews->execute();
$reviews_result = $reviews->get_result();

// Fetch user's favorites
$favs = $conn->prepare("SELECT c.id, c.name FROM favorites f JOIN characters c ON f.character_id = c.id WHERE f.user_id = ?");
$favs->bind_param("i", $user_id);
$favs->execute();
$favs_result = $favs->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

 <a href="index.php" class="btn btn-primary m-2">Home Page</a>


<h2 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION["username"]) ?>!</h2>

<!-- Favorite Characters (view only) -->
<h4 class="mt-4">My Favorite Characters</h4>
<?php if ($favs_result->num_rows > 0): ?>
    <ul class="list-group mb-5">
        <?php while ($fav = $favs_result->fetch_assoc()): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= htmlspecialchars($fav["name"]) ?>
                <a href="profile.php?remove_fav=<?= $fav['id'] ?>" class="btn btn-sm btn-danger">Remove</a>
            </li>
        <?php endwhile; ?>
    </ul>
<?php else: ?>
    <p class="text-muted">No favorites yet.</p>
<?php endif; ?>

<!-- User Reviews -->
<h4 class="mt-4">My Reviews</h4>
<?php if ($reviews_result->num_rows > 0): ?>
    <ul class="list-group mb-5">
        <?php while ($rev = $reviews_result->fetch_assoc()): ?>
            <li class="list-group-item">
                <strong><?= htmlspecialchars($rev["character_name"]) ?></strong><br>
                <div class="text-warning">Rating: <?= str_repeat("★", $rev["rating"]) ?></div>
                <div><?= nl2br(htmlspecialchars($rev["content"])) ?></div>
                <small class="text-muted">Posted on <?= $rev["created_at"] ?></small>
            </li>
        <?php endwhile; ?>
    </ul>
<?php else: ?>
    <p class="text-muted">You haven’t written any reviews yet.</p>
<?php endif; ?>

</body>
</html>
