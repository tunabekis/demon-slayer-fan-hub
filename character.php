<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include("includes/db.php");

$char_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

// Handle admin updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_SESSION["role"] === "admin") {
    $stmt = $conn->prepare("UPDATE characters SET name=?, race=?, occupation=?, status=?, breathing_style=?, rank=?, description=?, image_url=?, debut_episode=?, gender=?, age=? WHERE id=?");
    $stmt->bind_param("sssssssssssi",
        $_POST["name"], $_POST["race"], $_POST["occupation"], $_POST["status"],
        $_POST["breathing_style"], $_POST["rank"], $_POST["description"],
        $_POST["image_url"], $_POST["debut_episode"], $_POST["gender"], $_POST["age"], $char_id
    );
    $stmt->execute();
    $stmt->close();
    header("Location: character.php?id=$char_id");
    exit;
}

// Fetch character
$stmt = $conn->prepare("SELECT * FROM characters WHERE id = ?");
$stmt->bind_param("i", $char_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Character not found.";
    exit;
}

$character = $result->fetch_assoc();
$stmt->close();

// Fetch reviews
$reviews = [];
$stmt = $conn->prepare("SELECT r.content, r.rating, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.character_id = ? ORDER BY r.created_at DESC");
$stmt->bind_param("i", $char_id);
$stmt->execute();
$review_result = $stmt->get_result();
while ($row = $review_result->fetch_assoc()) {
    $reviews[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($character["name"]) ?> - Character Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

<h2><?= htmlspecialchars($character["name"]) ?></h2>

<?php if (!empty($character["image_url"])): ?>
    <img src="<?= htmlspecialchars($character["image_url"]) ?>" alt="Character Image" class="img-fluid mb-3" style="max-height: 300px;">
<?php endif; ?>

<ul class="list-group mb-4">
    <li class="list-group-item"><strong>Race:</strong> <?= htmlspecialchars($character["race"]) ?></li>
    <li class="list-group-item"><strong>Occupation:</strong> <?= htmlspecialchars($character["occupation"]) ?></li>
    <li class="list-group-item"><strong>Status:</strong> <?= htmlspecialchars($character["status"]) ?></li>
    <li class="list-group-item"><strong>Breathing Style:</strong> <?= htmlspecialchars($character["breathing_style"]) ?></li>
    <li class="list-group-item"><strong>Rank:</strong> <?= htmlspecialchars($character["rank"]) ?></li>
    <li class="list-group-item"><strong>Gender:</strong> <?= htmlspecialchars($character["gender"]) ?></li>
    <li class="list-group-item"><strong>Age:</strong> <?= htmlspecialchars($character["age"]) ?></li>
    <li class="list-group-item"><strong>Debut Episode:</strong> <?= htmlspecialchars($character["debut_episode"]) ?></li>
    <li class="list-group-item"><strong>Description:</strong><br><?= nl2br(htmlspecialchars($character["description"])) ?></li>
</ul>

<?php if ($_SESSION["role"] === "admin"): ?>
<h4>Edit Character</h4>
<form method="post" class="border p-3 mb-5">
    <input name="name" class="form-control mb-2" value="<?= htmlspecialchars($character["name"]) ?>" required>
    <input name="image_url" class="form-control mb-2" placeholder="Image URL" value="<?= htmlspecialchars($character["image_url"]) ?>">
    <input name="race" class="form-control mb-2" value="<?= htmlspecialchars($character["race"]) ?>">
    <input name="occupation" class="form-control mb-2" value="<?= htmlspecialchars($character["occupation"]) ?>">
    <input name="status" class="form-control mb-2" value="<?= htmlspecialchars($character["status"]) ?>">
    <input name="breathing_style" class="form-control mb-2" value="<?= htmlspecialchars($character["breathing_style"]) ?>">
    <input name="rank" class="form-control mb-2" value="<?= htmlspecialchars($character["rank"]) ?>">
    <input name="gender" class="form-control mb-2" value="<?= htmlspecialchars($character["gender"]) ?>">
    <input name="age" class="form-control mb-2" value="<?= htmlspecialchars($character["age"]) ?>">
    <input name="debut_episode" class="form-control mb-2" value="<?= htmlspecialchars($character["debut_episode"]) ?>">
    <textarea name="description" class="form-control mb-2" rows="4"><?= htmlspecialchars($character["description"]) ?></textarea>
    <button type="submit" class="btn btn-primary">Save Changes</button>
</form>
<?php endif; ?>

<h4>Reviews</h4>
<?php if (count($reviews) > 0): ?>
    <ul class="list-group">
        <?php foreach ($reviews as $review): ?>
        <li class="list-group-item">
            <strong><?= htmlspecialchars($review["username"]) ?></strong> -
            <span class="text-warning"><?= str_repeat("★", $review["rating"]) ?></span><br>
            <?= nl2br(htmlspecialchars($review["content"])) ?>
        </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p class="text-muted">No reviews yet for this character.</p>
<?php endif; ?>

<a href="characters.php" class="btn btn-secondary mt-4">← Back to Characters</a>

</body>
</html>
