<?php
require_once '../../../Controllers/DBController.php';
session_start();

$db = new DBController();
$conn = $db->openConnection();

$trip_id = isset($_GET['trip_id']) ? intval($_GET['trip_id']) : 0;

if ($trip_id == 0) {
    die("Trip not found");
}

/* DELETE */
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    $del = $conn->prepare("DELETE FROM expense WHERE expense_id = ?");
    $del->bind_param("i", $delete_id);
    $del->execute();

    header("Location: " . $_SERVER['PHP_SELF'] . "?trip_id=" . $trip_id);
    exit();
}

// ✅ إضافة Expense + Split
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description']);
    $expense_type = trim($_POST['expense_type']);

    // 1) add expense
    $stmt = $conn->prepare("
        INSERT INTO expense (trip_id, amount, description,expense_type)
        VALUES (?, ?, ?,?)
    ");
    $stmt->bind_param("idss", $trip_id, $amount, $description,$expense_type);
    $stmt->execute();

      header("Location: " . $_SERVER['PHP_SELF'] . "?trip_id=" . $trip_id);
    exit();

    
    

}
/* FETCH */
$stmt = $conn->prepare("
    SELECT expense_id, amount, description, expense_type
    FROM expense 
    WHERE trip_id = ?
");
$stmt->bind_param("i", $trip_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Expenses</title>

<style>
body{
    font-family:Arial;
    background:#eef2f7;
    margin:0;
    padding:30px;
}

.container{
    max-width:1100px;
    margin:auto;
}

h2{
    color:#2563eb;
    margin-bottom:20px;
}

/* FORM CARD */
.form-card{
    background:white;
    padding:20px;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,.08);
    margin-bottom:30px;
}

input, select{
    width:100%;
    padding:10px;
    margin-top:10px;
    border:1px solid #cbd5e1;
    border-radius:8px;
}

button{
    margin-top:15px;
    padding:10px;
    width:100%;
    border:none;
    border-radius:8px;
    background:#2563eb;
    color:white;
    font-weight:bold;
    cursor:pointer;
}

button:hover{
    background:#1d4ed8;
}

/* GRID */
.grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:20px;
}

/* CARD */
.card{
    background:white;
    padding:20px;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,.08);
}

.card h3{
    color:#2563eb;
    margin-bottom:10px;
}

.info{
    margin:6px 0;
    color:#374151;
}

/* TYPE TAG */
.type{
    display:inline-block;
    margin-top:10px;
    padding:5px 10px;
    border-radius:5px;
    font-size:12px;
    font-weight:bold;
}

.even{ background:#22c55e; color:white; }
.noneven{ background:#f59e0b; color:white; }

/* DELETE BUTTON */
.delete-btn{
    margin-top:12px;
    display:block;
    text-align:center;
    padding:8px;
    border-radius:8px;
    background:#ef4444;
    color:white;
    text-decoration:none;
    font-size:14px;
}

.delete-btn:hover{
    background:#dc2626;
}

/* EMPTY */
.empty{
    text-align:center;
    color:#6b7280;
    margin-top:40px;
}
</style>
</head>

<body>

<div class="container">

<h2>Expenses</h2>

<!-- ================= FORM ================= -->
<div class="form-card">
<form method="POST">

    <input type="number" step="0.01" name="amount" placeholder="Amount" required>
    <input type="text" name="description" placeholder="Description" required>

    <label>Expense Type:</label>
    <select name="expense_type" required>
        <option value="Even">Even Split</option>
        <option value="Non Even">Non-Even Split</option>
    </select>

    <button type="submit">Add Expense</button>

</form>
</div>

<!-- ================= EXPENSE CARDS ================= -->
<div class="grid">

<?php if($result->num_rows > 0){ ?>

    <?php while($row = $result->fetch_assoc()) { ?>

        <div class="card">

            <h3>$<?= $row['amount'] ?></h3>

            <p><?= htmlspecialchars($row['description']) ?></p>

            <p style="margin-top:5px;">
             <strong>Type:</strong> 
             <?= htmlspecialchars($row['expense_type']) ?>
            </p>
          

            <a href="?trip_id=<?= $trip_id ?>&delete_id=<?= $row['expense_id'] ?>"
               class="delete-btn"
               onclick="return confirm('Delete this expense?')">
               Delete
            </a>

        </div>

    <?php } ?>

<?php } else { ?>

    <div class="empty">No expenses yet.</div>

<?php } ?>

</div>

</div>

</body>
</html>