<?php
session_start();
include("includes/db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// Handle review update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_review_id"])) {
    $review_id = $_POST["update_review_id"];
    $content = trim($_POST["review_content"]);
    $rating = (int) $_POST["rating"];

    $stmt = $conn->prepare("UPDATE reviews SET content = ?, rating = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("siii", $content, $rating, $review_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: reviews.php");
    exit;
}

// Handle delete
if (isset($_GET["delete"])) {
    $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $_GET["delete"], $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: reviews.php");
    exit;
}

// Fetch user's reviews
$stmt = $conn->prepare("SELECT r.id, r.character_id, r.content, r.rating, r.created_at, c.name AS character_name
                        FROM reviews r
                        JOIN characters c ON r.character_id = c.id
                        WHERE r.user_id = ?
                        ORDER BY r.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Reviews</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function toggleEdit(id) {
            document.getElementById('edit-form-' + id).style.display = 'block';
        }
    </script>
</head>
<body class="container mt-4">

<a href="profile.php" class="btn btn-secondary m-2">My Profile</a>
<a href="index.php" class="btn btn-primary m-2">Home Page</a>

<h2 class="mb-4">My Reviews</h2>

<?php if ($result->num_rows > 0): ?>
    <ul class="list-group">
        <?php while ($row = $result->fetch_assoc()): ?>
        <li class="list-group-item">
            <strong><?= htmlspecialchars($row["character_name"]) ?></strong>
            <div class="text-warning">Rating: <?= str_repeat("★", $row["rating"]) ?></div>
            <div><?= nl2br(htmlspecialchars($row["content"])) ?></div>
            <small class="text-muted">Posted on <?= $row["created_at"] ?></small><br>

            <button class="btn btn-sm btn-outline-warning mt-2" onclick="toggleEdit(<?= $row['id'] ?>)">Edit</button>
            <a href="reviews.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger mt-2" onclick="return confirm('Delete this review?')">Delete</a>

            <div id="edit-form-<?= $row['id'] ?>" style="display:none;" class="mt-3">
                <form method="post">
                    <input type="hidden" name="update_review_id" value="<?= $row['id'] ?>">
                    <label>Rating:</label>
                    <select name="rating" class="form-select mb-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= $i == $row['rating'] ? "selected" : "" ?>><?= $i ?> Star<?= $i > 1 ? 's' : '' ?></option>
                        <?php endfor; ?>
                    </select>
                    <label>Edit Content:</label>
                    <textarea name="review_content" class="form-control mb-2"><?= htmlspecialchars($row["content"]) ?></textarea>
                    <button type="submit" class="btn btn-sm btn-success">Update</button>
                </form>
            </div>
        </li>
        <?php endwhile; ?>
    </ul>
<?php else: ?>
    <p class="text-muted">You haven't written any reviews yet.</p>
<?php endif; ?>

</body>
</html>
