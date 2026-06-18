<?php
session_start();
require_once __DIR__ . '/../../Controllers/DBController.php';

if (!isset($_SESSION['user']['id'])) {
    die('Please login first');
}

$user_id = (int) $_SESSION['user']['id'];

if (!isset($_GET['booking_id'])) {
    die('Booking not found');
}

$booking_id = (int) $_GET['booking_id'];

$db = new DBController();
$conn = $db->openConnection();

$stmt = $conn->prepare('
    SELECT b.booking_id, b.cost, b.status, t.name
    FROM booking b
    JOIN trip t ON b.trip_id = t.trip_id
    WHERE b.booking_id = ? AND b.user_id = ?
    LIMIT 1
');

$stmt->bind_param('ii', $booking_id, $user_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    die('Booking not found or access denied');
}

$payment_error = $_SESSION['payment_error'] ?? '';
unset($_SESSION['payment_error']);

if ($data['status'] === 'confirmed') {
    header('Location: viewBookings.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment</title>

<style>
body{
    font-family:Arial,Helvetica,sans-serif;
    background:#eef2f7;
    margin:0;
    padding:30px;
}
.wrap{
    max-width:520px;
    margin:0 auto;
    background:#fff;
    padding:28px;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,.08);
}


.top-bar{
    max-width:520px;
    margin:0 auto 18px;
    display:flex;
    justify-content:flex-end;
}


.back-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:12px 18px;
    margin-right: 4px;
    background:white;
    color:#2563eb;
    text-decoration:none;
    border-radius:12px;
    font-weight:600;
    box-shadow:0 6px 18px rgba(0,0,0,.05);
    transition:.25s;
    border:1px solid #dbeafe;
}

.back-btn:hover{
    transform:translateY(-2px);
    background:#eff6ff;
}

h2{color:#2563eb;margin-top:0;}
.meta{color:#374151;margin:8px 0 18px;}
.err{
    background:#fee2e2;
    color:#b91c1c;
    padding:12px;
    border-radius:8px;
    margin-bottom:16px;
    font-size:14px;
}
label{
    display:block;
    font-size:13px;
    color:#334155;
    margin:12px 0 4px;
}
input{
    width:100%;
    padding:10px 12px;
    border:1px solid #cbd5e1;
    border-radius:8px;
    font-size:15px;
    box-sizing:border-box;
}
button{
    margin-top:18px;
    width:100%;
    padding:12px;
    border:none;
    border-radius:8px;
    background:#2563eb;
    color:#fff;
    font-weight:bold;
    font-size:16px;
    cursor:pointer;
}
button:hover{
    background:#1d4ed8;
}
.hint{
    font-size:12px;
    color:#64748b;
    margin-top:14px;
}
a{color:#2563eb;}
</style>
</head>

<div class="top-bar">

    <a href="viewBookings.php" class="back-btn">

        ← My Bookings

    </a>


    
    <a href="homepage.php" class="back-btn">

         Return to Homepage

    </a>

</div>

<body>
<div class="wrap">

<h2>Payment</h2>

<p class="meta">Trip: <strong><?= htmlspecialchars($data['name']) ?></strong></p>
<p class="meta">Amount: <strong><?= htmlspecialchars((string)$data['cost']) ?> EGP</strong></p>

<?php if ($payment_error !== '') { ?>
    <div class="err"><?= htmlspecialchars($payment_error) ?></div>
<?php } ?>

<form method="POST" action="doPayment.php" autocomplete="off">

    <input type="hidden" name="booking_id" value="<?= (int)$booking_id ?>">
    <input type="hidden" name="amount" value="<?= htmlspecialchars((string)$data['cost']) ?>">

    <label for="traveler_full_name">Traveler full name</label>
    <input id="traveler_full_name" name="traveler_full_name" required maxlength="255">

    <label for="cardholder_name">Card holder name</label>
    <input id="cardholder_name" name="cardholder_name" required maxlength="255">

    <label for="card_number">Card number</label>
    <input id="card_number" name="card_number"
           inputmode="numeric"
           autocomplete="cc-number"
           required
           pattern="\d{16}"
           minlength="16"
           maxlength="16"
           placeholder="Enter valid card number">

    <label for="expiry_date">Expiry (MM/YY)</label>
    <input id="expiry_date" name="expiry_date"
           required maxlength="5"
           placeholder="MM/YY">

    <label for="cvv">CVV</label>
    <input id="cvv" name="cvv"
           inputmode="numeric"
           autocomplete="cc-csc"
           required maxlength="4"
           placeholder="123">

    <button type="submit">Pay now</button>

</form>



</div>
</body>
</html>