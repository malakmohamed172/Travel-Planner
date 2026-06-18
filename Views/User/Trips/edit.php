<?php
session_start();
require_once '../../../Controllers/DBController.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: ../../Auth/signin.php");
    exit();
}

$db = new DBController();
$conn = $db->openConnection();

/* =========================
   VALIDATE ID
========================= */
if (!isset($_GET['id'])) {
    die("❌ Trip ID is missing");
}

$id = intval($_GET['id']);

/* =========================
   FETCH TRIP
========================= */
$stmt = $conn->prepare("SELECT * FROM trip WHERE trip_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("❌ Trip not found");
}

$trip = $result->fetch_assoc();

$isAdmin = (int)($_SESSION['user']['role_id'] ?? 0) === 1;
$isLeader = (int)$trip['leader_id'] === (int)$_SESSION['user']['id'];

if (!$isAdmin && !$isLeader) {
    die("Unauthorized");
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Trip</title>

<style>
body{
    font-family:Arial;
    background:#eef2f7;
    padding:40px;
}

.card{
    max-width:600px;
    margin:auto;
    background:white;
    padding:25px;
    border-radius:12px;
    box-shadow:0 10px 30px rgba(0,0,0,0.1);
}

h2{
    text-align:center;
    margin-bottom:20px;
}

input,textarea,select{
    width:100%;
    padding:12px;
    margin:8px 0;
    border:1px solid #cbd5e1;
    border-radius:8px;
}

button{
    width:100%;
    padding:12px;
    margin-top:10px;
    background:#2563eb;
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

button:hover{
    background:#1d4ed8;
}

.back{
    display:block;
    text-align:center;
    margin-top:15px;
    color:#2563eb;
    text-decoration:none;
}
</style>
</head>

<body>

<div class="card">

<h2>✏️ Edit Trip</h2>

<form method="POST" action="../../../Controllers/TripController.php">

<input type="hidden" name="id" value="<?= $trip['trip_id'] ?>">
<input type="hidden" name="action" value="update">

<label>Name</label>
<input type="text" name="name" value="<?= htmlspecialchars($trip['name']) ?>" required>

<label>Description</label>
<textarea name="description" required><?= htmlspecialchars($trip['description']) ?></textarea>

<label>Status</label>
<select name="status">
    <option value="planned" <?= $trip['status']=='planned'?'selected':'' ?>>Planned</option>
    <option value="ongoing" <?= $trip['status']=='ongoing'?'selected':'' ?>>Ongoing</option>
    <option value="completed" <?= $trip['status']=='completed'?'selected':'' ?>>Completed</option>
</select>

<label>Budget</label>
<input type="number" name="budget" value="<?= $trip['budget'] ?>" min="0" required>

<label>Start Date</label>
<input type="date" name="start_date" value="<?= $trip['start_date'] ?>">

<label>End Date</label>
<input type="date" name="end_date" value="<?= $trip['end_date'] ?>">

<button type="submit">Update Trip</button>

</form>

<a class="back" href="list.php">← Back to Trips</a>

</div>

</body>
</html>
