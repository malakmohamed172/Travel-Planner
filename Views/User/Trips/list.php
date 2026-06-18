<?php
session_start();
require_once '../../../Controllers/DBController.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: ../../Auth/signin.php");
    exit();
}

$db = new DBController();
$conn = $db->openConnection();

$user_id = (int)$_SESSION['user']['id'];
$isAdmin = (int)($_SESSION['user']['role_id'] ?? 0) === 1;

if ($isAdmin) {
    $result = $conn->query("SELECT * FROM trip ORDER BY trip_id DESC");
} else {
    $stmt = $conn->prepare("SELECT * FROM trip WHERE leader_id = ? ORDER BY trip_id DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Trips</title>

<style>
body{font-family:Arial;background:#eef2f7;padding:30px}
.container{max-width:1000px;margin:auto}

table{width:100%;background:white;border-collapse:collapse;border-radius:10px;overflow:hidden}
th{background:#2563eb;color:white;padding:12px}
td{padding:12px;border-bottom:1px solid #ddd}

a{padding:6px 10px;border-radius:6px;text-decoration:none;color:white}
.edit{background:#f59e0b}
.delete{background:#ef4444}

.success{background:#dcfce7;padding:10px;margin-bottom:10px;border-radius:8px}
</style>
</head>

<body>

<div class="container">

<h2>Your Trips</h2>

<?php if(isset($_GET['success'])) echo "<div class='success'>Trip created</div>"; ?>
<?php if(isset($_GET['deleted'])) echo "<div class='success'>Trip deleted</div>"; ?>
<?php if(isset($_GET['updated'])) echo "<div class='success'>Trip updated</div>"; ?>

<table>

<tr>
<th>Name</th>
<th>Description</th>
<th>Status</th>
<th>Budget</th>
<th>Start Date</th>
<th>End Date</th>
<th>Actions</th>
</tr>

<?php if($result && $result->num_rows > 0) { ?>
<?php while($row = $result->fetch_assoc()) { ?>
<tr>

<td><?= htmlspecialchars($row['name']) ?></td>
<td><?= htmlspecialchars($row['description']) ?></td>
<td><?= htmlspecialchars($row['status']) ?></td>
<td><?= htmlspecialchars($row['budget']) ?></td>
<td><?= htmlspecialchars($row['start_date']) ?></td>
<td><?= htmlspecialchars($row['end_date']) ?></td>

<td>
<a class="edit" href="edit.php?id=<?= $row['trip_id'] ?>">Edit</a>
<a class="delete" href="../../../Controllers/TripController.php?action=delete&id=<?= $row['trip_id'] ?>" onclick="return confirm('Delete?')">Delete</a>
</td>

</tr>
<?php } ?>
<?php } else { ?>
<tr>
<td colspan="7">No trips found.</td>
</tr>
<?php } ?>

</table>

</div>

</body>
</html>
