<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION["user_id"];

include("includes/db.php");

// Save (add or edit) a character: only admins may do this
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["name"]) && $_SESSION["role"] === "admin") {
    $name = trim($_POST["name"]);
    $breathing_style = $_POST["breathing_style"];
    $rank = $_POST["rank"];
    $description = $_POST["description"];
    $race = $_POST["race"];
    $occupation = $_POST["occupation"];
    $status = $_POST["status"];

    // Check if a character with this name already exists
    $stmt = $conn->prepare("SELECT id FROM characters WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Name found: update the existing row in place so its id, favorites
        // and reviews are preserved instead of being wiped out
        $stmt->bind_result($existing_id);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE characters SET breathing_style = ?, rank = ?, description = ?, race = ?, occupation = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $breathing_style, $rank, $description, $race, $occupation, $status, $existing_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO characters (name, breathing_style, rank, description, race, occupation, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $name, $breathing_style, $rank, $description, $race, $occupation, $status);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: characters.php");
    exit;
}


// Fetch favorites
$favorited_ids = [];
$stmt = $conn->prepare("SELECT character_id FROM favorites WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_fav = $stmt->get_result();
while ($row = $result_fav->fetch_assoc()) {
    $favorited_ids[] = $row["character_id"];
}
$stmt->close();

// Fetch user reviews
$reviewed_ids = [];
$my_reviews = [];
$stmt = $conn->prepare("SELECT character_id, content, rating FROM reviews WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_rev = $stmt->get_result();
while ($row = $result_rev->fetch_assoc()) {
    $reviewed_ids[] = $row["character_id"];
    $my_reviews[$row["character_id"]] = $row;
}
$stmt->close();
// Handle new review
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["review_char_id"])) {
    $char_id = $_POST["review_char_id"];
    $rating = (int) $_POST["rating"];
    $content = trim($_POST["review_content"]);

    if (!in_array($char_id, $reviewed_ids) && $rating >= 1 && $rating <= 5 && !empty($content)) {
        $stmt = $conn->prepare("INSERT INTO reviews (user_id, character_id, content, rating) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisi", $user_id, $char_id, $content, $rating);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: characters.php");
    exit;
}

// Handle review update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_review_char_id"])) {
    $char_id = $_POST["update_review_char_id"];
    $rating = (int) $_POST["rating"];
    $content = trim($_POST["review_content"]);

    $stmt = $conn->prepare("UPDATE reviews SET content = ?, rating = ? WHERE user_id = ? AND character_id = ?");
    $stmt->bind_param("siii", $content, $rating, $user_id, $char_id);
    $stmt->execute();
    $stmt->close();
    header("Location: characters.php");
    exit;
}

// Handle favorites
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["favorite_char_id"])) {
    $char_id = $_POST["favorite_char_id"];
    $stmt = $conn->prepare("INSERT IGNORE INTO favorites (user_id, character_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $char_id);
    $stmt->execute();
    $stmt->close();
    header("Location: characters.php");
    exit;
}

// Admin: delete a character
if ($_SESSION["role"] === "admin" && isset($_GET["delete"])) {
    $stmt = $conn->prepare("DELETE FROM characters WHERE id = ?");
    $stmt->bind_param("i", $_GET["delete"]);
    $stmt->execute();
    $stmt->close();
    header("Location: characters.php");
    exit;
}

// Editing data (for form)
$edit_data = null;
if (isset($_GET["edit"]) && $_SESSION["role"] === "admin") {
    $stmt = $conn->prepare("SELECT * FROM characters WHERE id = ?");
    $stmt->bind_param("i", $_GET["edit"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_data = $result->fetch_assoc();
    $stmt->close();
}

// Search & filters
$where = [];
$params = [];
$types = "";

if (!empty($_GET["search"])) {
    $where[] = "(name LIKE CONCAT('%', ?, '%') OR rank LIKE CONCAT('%', ?, '%'))";
    $params[] = $_GET["search"];
    $params[] = $_GET["search"];
    $types .= "ss";
}

if (!empty($_GET["race"])) {
    $where[] = "race = ?";
    $params[] = $_GET["race"];
    $types .= "s";
}

if (!empty($_GET["status"])) {
    $where[] = "status = ?";
    $params[] = $_GET["status"];
    $types .= "s";
}

$sql = "SELECT * FROM characters";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$characters = $stmt->get_result();

// All reviews for display
$all_reviews = [];
$res = $conn->query("SELECT r.character_id, r.content, r.rating, u.username
                     FROM reviews r JOIN users u ON r.user_id = u.id
                     ORDER BY r.character_id, r.created_at DESC");
while ($r = $res->fetch_assoc()) {
    $all_reviews[$r["character_id"]][] = $r;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Demon Slayer Characters</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function toggleReviews(id) {
            const row = document.getElementById('reviews-' + id);
            row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
        }
        function toggleEditReview(id) {
            const row = document.getElementById('edit-review-form-' + id);
            row.style.display = 'table-row';
        }
        function revealStatus(id) {
            document.getElementById('status-hidden-' + id).style.display = 'none';
            document.getElementById('status-' + id).style.display = 'inline';
        }
    </script>


<script>
async function loadCharacter(id) {
    const response = await fetch(`get_character.php?id=${id}`);
    const data = await response.json();

    if (data.error) {
        alert(data.error);
        return;
    }

    document.getElementById("name").value = data.name || "";
    document.getElementById("breathing_style").value = data.breathing_style || "";
    document.getElementById("rank").value = data.rank || "";
    document.getElementById("description").value = data.description || "";
    document.getElementById("race").value = data.race || "";
    document.getElementById("occupation").value = data.occupation || "";
    document.getElementById("status").value = data.status || "";

    window.scrollTo({ top: 0, behavior: "smooth" });
}

</script>


</head>
<body class="container mt-4">

 <a href="profile.php" class="btn btn-secondary m-2">My Profile</a>
<a href="index.php" class="btn btn-primary m-2">Home Page</a>


<h2 class="mb-4">Characters</h2>

<!-- Admin Add/Edit Form -->
<?php if ($_SESSION["role"] === "admin"): ?>
    <h5><?= $edit_data ? "Edit: " . $edit_data["name"] : "Add New Character" ?></h5>
    <form method="post" class="mb-4">
       <input name="name" id="name" class="form-control mb-2" placeholder="Name" required value="<?= htmlspecialchars($edit_data["name"] ?? "") ?>">
<input name="breathing_style" id="breathing_style" class="form-control mb-2" placeholder="Breathing Style" value="<?= htmlspecialchars($edit_data["breathing_style"] ?? "") ?>">
<input name="rank" id="rank" class="form-control mb-2" placeholder="Rank" value="<?= htmlspecialchars($edit_data["rank"] ?? "") ?>">
<input name="occupation" id="occupation" class="form-control mb-2" placeholder="Occupation" value="<?= htmlspecialchars($edit_data["occupation"] ?? "") ?>">

<select name="race" id="race" class="form-select mb-2">
    <option value="Human" <?= ($edit_data["race"] ?? "") === "Human" ? "selected" : "" ?>>Human</option>
    <option value="Demon" <?= ($edit_data["race"] ?? "") === "Demon" ? "selected" : "" ?>>Demon</option>
</select>

<select name="status" id="status" class="form-select mb-2">
    <option value="Alive" <?= ($edit_data["status"] ?? "") === "Alive" ? "selected" : "" ?>>Alive</option>
    <option value="Dead" <?= ($edit_data["status"] ?? "") === "Dead" ? "selected" : "" ?>>Dead</option>
</select>

<textarea name="description" id="description" class="form-control mb-2" placeholder="Description"><?= htmlspecialchars($edit_data["description"] ?? "") ?></textarea>

      <button type="submit" class="btn btn-success">Save Character</button>



        <?php if ($edit_data): ?>
            <a href="characters.php" class="btn btn-secondary">Cancel</a>
        <?php endif; ?>
    </form>
<?php endif; ?>

<!-- Search + Filter -->
<form method="get" class="row g-2 mb-4">
    <div class="col-md-4">
        <input name="search" class="form-control" placeholder="Search by name or rank" value="<?= htmlspecialchars($_GET["search"] ?? "") ?>">
    </div>
    <div class="col-md-3">
        <select name="race" class="form-select">
            <option value="">All Races</option>
            <option value="Human" <?= ($_GET["race"] ?? "") === "Human" ? "selected" : "" ?>>Human</option>
            <option value="Demon" <?= ($_GET["race"] ?? "") === "Demon" ? "selected" : "" ?>>Demon</option>
        </select>
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="Alive" <?= ($_GET["status"] ?? "") === "Alive" ? "selected" : "" ?>>Alive</option>
            <option value="Dead" <?= ($_GET["status"] ?? "") === "Dead" ? "selected" : "" ?>>Dead</option>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">Apply</button>
    </div>
</form>

<table class="table table-bordered table-hover">
    <thead class="table-dark">
        <tr>
            <th>Name</th>
            <th>Race</th>
            <th>Occupation</th>
            <th>Status</th>
            <th>Breathing Style</th>
            <th>Rank</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
<?php while ($row = $characters->fetch_assoc()): ?>
<tr>
   <td>
    <a href="character.php?id=<?= $row['id'] ?>">
        <?= htmlspecialchars($row["name"]) ?>
    </a>
</td>

    <td><?= htmlspecialchars($row["race"]) ?></td>
    <td><?= htmlspecialchars($row["occupation"]) ?></td>
    <td>
        <span id="status-hidden-<?= $row['id'] ?>">
            <button onclick="revealStatus(<?= $row['id'] ?>)" class="btn btn-sm btn-outline-secondary">Reveal</button>
        </span>
        <span id="status-<?= $row['id'] ?>" style="display:none;">
            <?= htmlspecialchars($row["status"]) ?>
        </span>
    </td>
    <td><?= htmlspecialchars($row["breathing_style"]) ?></td>
    <td><?= htmlspecialchars($row["rank"]) ?></td>
    <td><?= htmlspecialchars($row["description"]) ?></td>
    <td>
        <?php if ($_SESSION["role"] === "admin"): ?>
<button type="button" onclick="loadCharacter(<?= $row["id"] ?>)" class="btn btn-sm btn-warning">Edit</button>
            <a href="characters.php?delete=<?= $row["id"] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this character?')">Delete</a>
        <?php endif; ?>

        <?php if (in_array($row["id"], $favorited_ids)): ?>
            <span class="text-success fw-semibold">★ Favorited</span>
        <?php else: ?>
            <form method="post" action="characters.php" style="display:inline;">
    <input type="hidden" name="favorite_char_id" value="<?= $row['id'] ?>">
    <button class="btn btn-sm btn-outline-danger">❤️ Favorite</button>
</form>

        <?php endif; ?>

        <?php if (!in_array($row["id"], $reviewed_ids)): ?>
            <button class="btn btn-sm btn-outline-primary ms-1" onclick="document.getElementById('review-form-<?= $row['id'] ?>').style.display='table-row'; this.style.display='none';">Review</button>
        <?php else: ?>
            <button class="btn btn-sm btn-outline-warning ms-1" onclick="toggleEditReview(<?= $row['id'] ?>)">Edit Review</button>
        <?php endif; ?>

        <button class="btn btn-sm btn-outline-info ms-1" onclick="toggleReviews(<?= $row['id'] ?>)">View Reviews</button>
    </td>
</tr>

<?php if (!in_array($row["id"], $reviewed_ids)): ?>
<tr id="review-form-<?= $row['id'] ?>" style="display:none;">
    <td colspan="8">
        <form method="post" action="characters.php" class="mt-2">
    <input type="hidden" name="review_char_id" value="<?= $row["id"] ?>">
    <div class="mb-2">
        <label>Rating:</label>
       <select name="rating" class="form-select mb-2" required>
    <option value="">Select rating</option>
    <option value="1">1 Star</option>
    <option value="2">2 Stars</option>
    <option value="3">3 Stars</option>
    <option value="4">4 Stars</option>
    <option value="5">5 Stars</option>
</select>

    </div>
    <textarea name="review_content" class="form-control mb-2" required></textarea>
    <button class="btn btn-primary">Submit Review</button>
</form>

    </td>
</tr>
<?php else: ?>
<tr id="edit-review-form-<?= $row['id'] ?>" style="display:none;">
    <td colspan="8">
        <form method="post" class="border p-3 bg-light rounded">
            <input type="hidden" name="update_review_char_id" value="<?= $row['id'] ?>">
            <label>Edit Rating:</label>
            <select name="rating" class="form-select mb-2" required>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>" <?= $i == $my_reviews[$row['id']]['rating'] ? "selected" : "" ?>><?= $i ?> Star<?= $i > 1 ? 's' : '' ?></option>
                <?php endfor; ?>
            </select>
            <textarea name="review_content" class="form-control mb-2" required><?= htmlspecialchars($my_reviews[$row['id']]['content']) ?></textarea>
            <button type="submit" class="btn btn-warning">Update Review</button>
        </form>
    </td>
</tr>
<?php endif; ?>

<tr id="reviews-<?= $row['id'] ?>" style="display:none;">
    <td colspan="8">
        <div class="border p-3 rounded">
            <h6>Reviews for <?= htmlspecialchars($row["name"]) ?>:</h6>
            <?php if (!empty($all_reviews[$row['id']])): ?>
                <ul class="list-group">
                    <?php foreach ($all_reviews[$row['id']] as $rev): ?>
                        <li class="list-group-item">
                            <strong><?= htmlspecialchars($rev["username"]) ?></strong> -
                            <span class="text-warning"><?= str_repeat("★", $rev["rating"]) ?></span><br>
                            <?= nl2br(htmlspecialchars($rev["content"])) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">No reviews yet.</p>
            <?php endif; ?>
        </div>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</body>
</html>
