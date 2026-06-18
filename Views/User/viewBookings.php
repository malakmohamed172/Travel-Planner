<?php
session_start();
require_once __DIR__ . '/../../Controllers/DBController.php';

if (!isset($_SESSION['user']['id'])) {
    die('Please login first');
}

$db = new DBController();
$conn = $db->openConnection();

$user_id = (int) $_SESSION['user']['id'];

$booking_message = $_SESSION['booking_message'] ?? '';
unset($_SESSION['booking_message']);

$stmt = $conn->prepare('
    SELECT b.booking_id, b.status, b.cost, b.trip_id, t.name
    FROM booking b
    JOIN trip t ON b.trip_id = t.trip_id
    WHERE b.user_id = ?
    ORDER BY b.booking_id DESC
');

$stmt->bind_param('i', $user_id);
$stmt->execute();

$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Bookings</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Inter',sans-serif;
    background:linear-gradient(135deg,#eef2ff,#f8fafc);
    min-height:100vh;
    padding:40px 20px;
    color:#0f172a;
}

.container{
    max-width:1000px;
    margin:auto;
}

.page-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:16px;
    margin-bottom:30px;
}

.page-header h1{
    font-size:36px;
    font-weight:700;
    color:#0f172a;
}

.page-header p{
    margin-top:8px;
    color:#64748b;
}

.back-btn{
    text-decoration:none;
    background:white;
    color:#2563eb;
    padding:12px 18px;
    border-radius:14px;
    font-weight:600;
    border:1px solid #dbeafe;
    transition:.2s;
}

.back-btn:hover{
    background:#eff6ff;
    transform:translateY(-2px);
}

.msg{
    background:#dcfce7;
    color:#166534;
    padding:16px;
    border-radius:16px;
    margin-bottom:20px;
    font-weight:500;
    border:1px solid #bbf7d0;
}

.bookings-grid{
    display:flex;
    flex-direction:column;
    gap:22px;
}

.card{
    background:white;
    border-radius:26px;
    padding:26px;
    box-shadow:0 12px 30px rgba(15,23,42,.08);
    border:1px solid #e2e8f0;
    transition:.2s;
}

.card:hover{
    transform:translateY(-3px);
}

.card-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    flex-wrap:wrap;
    gap:14px;
    margin-bottom:22px;
}

.trip-title{
    font-size:26px;
    font-weight:700;
    color:#0f172a;
}

.status-badge{
    padding:10px 16px;
    border-radius:999px;
    font-size:13px;
    font-weight:700;
    text-transform:capitalize;
}

.status-pending{
    background:#fef3c7;
    color:#92400e;
}

.status-confirmed{
    background:#dcfce7;
    color:#166534;
}

.status-cancelled{
    background:#fee2e2;
    color:#b91c1c;
}

.info-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:14px;
    margin-bottom:22px;
}

.info-box{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:16px;
    padding:16px;
}

.info-label{
    font-size:13px;
    color:#64748b;
    margin-bottom:6px;
}

.info-value{
    font-size:18px;
    font-weight:700;
    color:#0f172a;
}

.actions{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    margin-top:8px;
}

.btn{
    text-decoration:none;
    border:none;
    padding:12px 18px;
    border-radius:14px;
    font-size:14px;
    font-weight:600;
    cursor:pointer;
    transition:.2s;
    display:inline-flex;
    align-items:center;
    gap:8px;
}

.btn:hover{
    transform:translateY(-2px);
}

.pay-btn{
    background:#16a34a;
    color:white;
}

.pay-btn:hover{
    background:#15803d;
}

.expense-btn{
    background:#2563eb;
    color:white;
}

.expense-btn:hover{
    background:#1d4ed8;
}

.cancel-btn{
    background:#dc2626;
    color:white;
}

.cancel-btn:hover{
    background:#b91c1c;
}

.invite-section{
    margin-top:24px;
    padding-top:22px;
    border-top:1px solid #e2e8f0;
}

.invite-title{
    font-size:18px;
    font-weight:700;
    margin-bottom:14px;
    color:#0f172a;
}

.invite-form{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.invite-form input{
    flex:1;
    min-width:240px;
    padding:14px;
    border-radius:14px;
    border:1px solid #cbd5e1;
    background:#f8fafc;
    outline:none;
    transition:.2s;
}

.invite-form input:focus{
    border-color:#2563eb;
    background:white;
    box-shadow:0 0 0 4px rgba(37,99,235,.12);
}

.invite-btn{
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    color:white;
}

.invite-btn:hover{
    box-shadow:0 10px 20px rgba(37,99,235,.25);
}

.empty{
    background:white;
    border-radius:24px;
    padding:50px;
    text-align:center;
    color:#64748b;
    box-shadow:0 8px 24px rgba(15,23,42,.06);
}

.empty h3{
    margin-top:12px;
    font-size:24px;
    color:#0f172a;
}

.empty p{
    margin-top:8px;
}

@media(max-width:768px){

    body{
        padding:20px 14px;
    }

    .page-header h1{
        font-size:28px;
    }

    .card{
        padding:20px;
    }

    .trip-title{
        font-size:22px;
    }

    .invite-form{
        flex-direction:column;
    }

    .invite-form input{
        width:100%;
    }

}

</style>
</head>

<body>

<div class="container">

    <div class="page-header">

        <div>
            <h1>📋 My Bookings</h1>
            <p>Manage your bookings, payments, expenses, and trip invitations.</p>
        </div>

        <a class="back-btn" href="viewTrips.php">
            ← Upcoming Trips
        </a>

         <a class="back-btn" href="homepage.php">
            Return to Homepage
        </a>

    </div>

    <?php if ($booking_message !== '') { ?>

        <div class="msg">
            <?= htmlspecialchars($booking_message) ?>
        </div>

    <?php } ?>

    <?php if (count($bookings) > 0) { ?>

    <div class="bookings-grid">

    <?php foreach ($bookings as $row) { ?>

        <div class="card">

            <div class="card-top">

                <div class="trip-title">
                    🌍 <?= htmlspecialchars($row['name']) ?>
                </div>

                <div class="status-badge status-<?= htmlspecialchars($row['status']) ?>">
                    <?= htmlspecialchars($row['status']) ?>
                </div>

            </div>

            <div class="info-grid">

              

                <div class="info-box">
                    <div class="info-label">Trip Cost</div>
                    <div class="info-value">
                        💰 <?= htmlspecialchars((string)$row['cost']) ?>
                    </div>
                </div>

            </div>

            <div class="actions">

                <a class="btn expense-btn"
                href="http://localhost/Travel_Planner/Views/User/Expenses/list.php?trip_id=<?= (int)$row['trip_id'] ?>">
                    📊 View Expenses
                </a>

                <?php if ($row['status'] === 'pending') { ?>

                    <a class="btn pay-btn"
                    href="pay.php?booking_id=<?= (int)$row['booking_id'] ?>">
                        💳 Pay Now
                    </a>

                <?php } ?>

                <?php if ($row['status'] !== 'cancelled') { ?>

                <form method="POST"
                      action="cancelBooking.php"
                      style="display:inline;"
                      onsubmit="return confirm('Are you sure you want to cancel this booking?');">

                    <input type="hidden"
                           name="booking_id"
                           value="<?= (int)$row['booking_id'] ?>">

                    <button type="submit" class="btn cancel-btn">
                        ❌ Cancel Booking
                    </button>

                </form>

                <?php } ?>

            </div>

            <?php if ($row['status'] !== 'cancelled') { ?>

            <div class="invite-section">

                <div class="invite-title">
                    ✉️ Invite Member
                </div>

                <form method="POST"
                      action="../../Controllers/InviteMemberController.php"
                      class="invite-form">

                    <input type="hidden"
                           name="trip_id"
                           value="<?= (int)$row['trip_id'] ?>">

                    <input type="email"
                           name="email"
                           placeholder="Enter member email address"
                           required>

                    <button type="submit"
                            name="invite"
                            class="btn invite-btn">

                        Send Invitation

                    </button>

                </form>

            </div>

            <?php } ?>

        </div>

    <?php } ?>

    </div>

    <?php } else { ?>

    <div class="empty">

        <div style="font-size:60px;">🧳</div>

        <h3>No Bookings Yet</h3>

        <p>You haven't booked any trips yet.</p>

    </div>

    <?php } ?>

</div>

</body>
</html>