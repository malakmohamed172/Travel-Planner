<?php
session_start();
require_once __DIR__ . '/../../Controllers/DBController.php';

$db = new DBController();
$conn = $db->openConnection();

$user_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
    SELECT * FROM notification
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Notifications</title>
<style>
body{font-family:Arial;padding:30px;background:#f1f5f9;}
.card{background:white;padding:15px;margin-bottom:10px;border-radius:10px;}
.unread{background:#dbeafe;}
</style>
</head>
<body>

<h2>🔔 Notifications</h2>

<?php while($n = $result->fetch_assoc()){ ?>

<div class="card <?= $n['is_read']==0 ? 'unread' : '' ?>">
    <?= htmlspecialchars($n['message']) ?><br>
    <small><?= $n['created_at'] ?></small>
</div>

<?php } ?>

</body>
</html>