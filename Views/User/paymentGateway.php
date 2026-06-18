<?php
/**
 * Same flow as pay.php — use pay.php from the app; this file kept in sync for alternate links.
 */
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
<title>Payment</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#eef2f7;margin:0;padding:30px;}
.wrap{max-width:520px;margin:0 auto;background:#fff;padding:28px;border-radius:12px;box-shadow:0 5px 15px rgba(0,0,0,.08);}
h2{color:#2563eb;}
.err{background:#fee2e2;color:#b91c1c;padding:12px;border-radius:8px;margin-bottom:16px;font-size:14px;}
label{display:block;font-size:13px;margin:12px 0 4px;}
input{width:100%;padding:10px;box-sizing:border-box;border-radius:8px;border:1px solid #cbd5e1;}
button{margin-top:16px;padding:12px 20px;background:#2563eb;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:bold;}
</style>
</head>
<body>
<div class="wrap">
<h2>Payment gateway</h2>
<p>Trip: <strong><?= htmlspecialchars($data['name']) ?></strong></p>
<p>Amount: <strong>$<?= htmlspecialchars((string) $data['cost']) ?></strong></p>

<?php if ($payment_error !== '') { ?>
<div class="err"><?= htmlspecialchars($payment_error) ?></div>
<?php } ?>

<form method="POST" action="doPayment.php">
    <input type="hidden" name="booking_id" value="<?= (int) $booking_id ?>">
    <input type="hidden" name="amount" value="<?= htmlspecialchars((string) $data['cost']) ?>">

    <label>Traveler full name</label>
    <input name="traveler_full_name" required maxlength="255">

    <label>Card holder name</label>
    <input name="cardholder_name" required maxlength="255">

    <label>Card number</label>
    <input name="card_number" inputmode="numeric" required pattern="\d{16}" minlength="16" maxlength="16">

    <label>Expiry (MM/YY)</label>
    <input name="expiry_date" required maxlength="5" placeholder="12/28">

    <label>CVV</label>
    <input name="cvv" inputmode="numeric" required maxlength="4">

    <button type="submit">Pay</button>
</form>
</div>
</body>
</html>
