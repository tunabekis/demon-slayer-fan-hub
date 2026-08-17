<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

if (!isset($_GET["id"])) {
    echo json_encode(["error" => "Missing ID"]);
    exit;
}

include("includes/db.php");
$id = (int) $_GET["id"];

$stmt = $conn->prepare("SELECT * FROM characters WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["error" => "Character not found"]);
} else {
    echo json_encode($result->fetch_assoc());
}

$stmt->close();
?>
