<?php

require_once '../../Controllers/DBController.php';

$db = new DBController();
$conn = $db->openConnection();


session_start();

require_once '../../Controllers/DBController.php';

/* =========================================
   PROTECT ADMIN PAGE
========================================= */

// user not logged in
if(!isset($_SESSION['user'])){

    header("Location: ../Auth/signin.php");
    exit();

}

// allow only admins
if($_SESSION['user']['role_id'] != 1){

    header("Location: ../Auth/signin.php");
    exit();

}

$db = new DBController();
$conn = $db->openConnection();
/* ==================================================
   USERS
================================================== */

$users = [];

$usersQuery = mysqli_query($conn, "
SELECT * FROM users
");

while($row = mysqli_fetch_assoc($usersQuery)){

    $role = "member";

    if($row['role_id'] == 1){
        $role = "admin";
    }
    elseif($row['role_id'] == 2){
        $role = "leader";
    }

    $users[] = [
        "id" => $row['user_id'],
        "name" => $row['name'],
        "email" => $row['email'],
        "role" => $role
    ];
}


/* ==================================================
   BOOKINGS
================================================== */

$bookings = [];

$bookingQuery = mysqli_query($conn, "

SELECT 
booking.booking_id,
booking.date,
booking.status,
users.name AS member_name,
trip.name AS trip_name

FROM booking

LEFT JOIN users
ON booking.user_id = users.user_id

LEFT JOIN trip
ON booking.trip_id = trip.trip_id

");

while($row = mysqli_fetch_assoc($bookingQuery)){

    $bookings[] = [

        "id" => $row['booking_id'],
        "memberName" => $row['member_name'],
        "trip" => $row['trip_name'],
        "date" => $row['date'],
        "status" => strtolower($row['status'])

    ];
}


/* ==================================================
   REPORTS
================================================== */

// TOTAL TRIPS
$totalTripsQuery = mysqli_query($conn,
"SELECT COUNT(*) AS total_trips FROM trip");

$totalTripsData = mysqli_fetch_assoc($totalTripsQuery);

$totalTrips = $totalTripsData['total_trips'];


// Cancelled BOOKINGS
$cancelledQuery = mysqli_query($conn,
"SELECT COUNT(*) AS cancelled_count 
FROM booking 
WHERE status='cancelled'");

$cancelledData = mysqli_fetch_assoc($cancelledQuery);

$cancelledBookings = $cancelledData['cancelled_count'];


// PENDING BOOKINGS
$pendingQuery = mysqli_query($conn,
"SELECT COUNT(*) AS pending_count 
FROM booking 
WHERE status='pending'");

$pendingData = mysqli_fetch_assoc($pendingQuery);

$pendingBookings = $pendingData['pending_count'];


// APPROVED BOOKINGS
$approvedQuery = mysqli_query($conn,
"SELECT COUNT(*) AS approved_count 
FROM booking 
WHERE status='confirmed'");

$approvedData = mysqli_fetch_assoc($approvedQuery);

$approvedBookings = $approvedData['approved_count'];


// ACTIVE MEMBERS
$activeQuery = mysqli_query($conn,
"SELECT COUNT(*) AS active_members 
FROM users");

$activeData = mysqli_fetch_assoc($activeQuery);

$activeMembers = $activeData['active_members'];

function tableHasColumn($conn, $tableName, $columnName) {
    $stmt = $conn->prepare("
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $tableName, $columnName);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function deleteByColumn($conn, $tableName, $columnName, $id) {
    if (!tableHasColumn($conn, $tableName, $columnName)) {
        return;
    }

    $allowedTables = [
        'payment', 'booking', 'activity', 'notification', 'trip_member',
        'document', 'leader', 'member', 'admin', 'emergency_contact',
        'expense', 'itinerary_stop', 'itinerary', 'trip'
    ];
    $allowedColumns = [
        'user_id', 'leader_id', 'member_id', 'admin_id', 'trip_id', 'itinerary_id'
    ];

    if (!in_array($tableName, $allowedTables, true) || !in_array($columnName, $allowedColumns, true)) {
        return;
    }

    $stmt = $conn->prepare("DELETE FROM `$tableName` WHERE `$columnName` = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

function deleteTripTree($conn, $tripId) {
    if (tableHasColumn($conn, 'payment', 'booking_id') && tableHasColumn($conn, 'booking', 'trip_id')) {
        $stmt = $conn->prepare("
            DELETE p
            FROM payment p
            JOIN booking b ON p.booking_id = b.booking_id
            WHERE b.trip_id = ?
        ");
        $stmt->bind_param("i", $tripId);
        $stmt->execute();
    }

    deleteByColumn($conn, 'booking', 'trip_id', $tripId);
    deleteByColumn($conn, 'trip_member', 'trip_id', $tripId);
    deleteByColumn($conn, 'emergency_contact', 'trip_id', $tripId);
    deleteByColumn($conn, 'expense', 'trip_id', $tripId);
    deleteByColumn($conn, 'document', 'trip_id', $tripId);

    if (tableHasColumn($conn, 'activity', 'itinerary_id') && tableHasColumn($conn, 'itinerary', 'trip_id')) {
        $stmt = $conn->prepare("
            DELETE a
            FROM activity a
            JOIN itinerary i ON a.itinerary_id = i.itinerary_id
            WHERE i.trip_id = ?
        ");
        $stmt->bind_param("i", $tripId);
        $stmt->execute();
    }

    if (tableHasColumn($conn, 'itinerary_stop', 'itinerary_id') && tableHasColumn($conn, 'itinerary', 'trip_id')) {
        $stmt = $conn->prepare("
            DELETE s
            FROM itinerary_stop s
            JOIN itinerary i ON s.itinerary_id = i.itinerary_id
            WHERE i.trip_id = ?
        ");
        $stmt->bind_param("i", $tripId);
        $stmt->execute();
    }

    deleteByColumn($conn, 'itinerary', 'trip_id', $tripId);
    deleteByColumn($conn, 'trip', 'trip_id', $tripId);
}













if(isset($_POST['delete_user'])){

    $id = (int) $_POST['user_id'];

    if ($id === (int)$_SESSION['user']['id']) {
        header("Location: adminDashboard.php?error=self_delete");
        exit();
    }

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("SELECT trip_id FROM trip WHERE leader_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $leaderTrips = $stmt->get_result();

        while ($trip = $leaderTrips->fetch_assoc()) {
            deleteTripTree($conn, (int)$trip['trip_id']);
        }

        deleteByColumn($conn, 'payment', 'user_id', $id);
        deleteByColumn($conn, 'booking', 'user_id', $id);
        deleteByColumn($conn, 'activity', 'user_id', $id);
        deleteByColumn($conn, 'notification', 'user_id', $id);
        deleteByColumn($conn, 'trip_member', 'user_id', $id);
        deleteByColumn($conn, 'document', 'user_id', $id);
        deleteByColumn($conn, 'leader', 'leader_id', $id);
        deleteByColumn($conn, 'member', 'member_id', $id);
        deleteByColumn($conn, 'admin', 'admin_id', $id);

        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $conn->commit();
        header("Location: adminDashboard.php?deleted=1");
        exit();
    } catch (Throwable $e) {
        $conn->rollback();
        header("Location: adminDashboard.php?error=delete_failed");
        exit();
    }
}
if(isset($_POST['edit_user'])){

    $id = (int) $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role_id = $_POST['role_id'];

    $stmt = $conn->prepare("
        UPDATE users 
        SET name = ?, email = ?, role_id = ?
        WHERE user_id = ?
    ");

    $stmt->bind_param("ssii", $name, $email, $role_id, $id);
    $stmt->execute();

    header("Location: adminDashboard.php?updated=1");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Travel Planner | Admin Dashboard</title>

     

    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: #f4f7fc;
    color: #1e293b;
    padding: 24px 32px;
}

.dashboard-container {
    max-width: 1600px;
    margin: 0 auto;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
    flex-wrap: wrap;
    gap: 16px;
}

.logo-area h1 {
    font-size: 1.8rem;
    font-weight: 700;
    background: linear-gradient(135deg, #0f3b3f, #1b7e6b);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.logo-area p {
    font-size: 0.85rem;
    color: #5b6e8c;
}

.admin-badge {
    background: white;
    padding: 8px 20px;
    border-radius: 40px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    font-weight: 500;
    font-size: 0.9rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 36px;
}

.stat-card {
    background: white;
    border-radius: 28px;
    padding: 20px 18px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.03);
    border: 1px solid #e9edf2;
}

.stat-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: #5a6e85;
    margin-bottom: 12px;
}

.stat-number {
    font-size: 2.4rem;
    font-weight: 800;
    color: #0f2b2d;
}

.stat-icon {
    float: right;
    font-size: 2rem;
    color: #cbd9e6;
}

.two-col-layout {
    display: flex;
    flex-wrap: wrap;
    gap: 28px;
}

.card-panel {
    flex: 1;
    min-width: 280px;
    background: white;
    border-radius: 28px;
    overflow: hidden;
    border: 1px solid #eef2f6;
}

.panel-header {
    padding: 18px 24px;
    border-bottom: 2px solid #eef2f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-wrapper {
    overflow-x: auto;
}

.user-table, .booking-table {
    width: 100%;
    border-collapse: collapse;
}

.user-table th, .booking-table th {
    text-align: left;
    padding: 14px 16px;
    background: #f9fafc;
    font-weight: 600;
}

.user-table td, .booking-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #f0f2f5;
}

.role-badge,
.status-badge {
    border-radius: 40px;
    padding: 4px 12px;
    font-size: 0.7rem;
    font-weight: 600;
}

.role-member {
    background: #e0f2fe;
    color: #0369a1;
}

.role-leader {
    background: #fff0e0;
    color: #b45309;
}

.role-admin {
    background: #e6f7ec;
    color: #2b6e3c;
}

.status-confirmed {
    background: #dcfce7;
    color: #15803d;
}

.status-pending {
    background: #fff3e3;
    color: #b45309;
}

.status-cancelled {
    background: #ffe9e9;
    color: #b91c1c;
}

footer {
    text-align: center;
    margin-top: 40px;
    font-size: 0.75rem;
    color: #7e95b0;
}






.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    margin-right: 6px;
    text-decoration: none;
    font-size: 14px;
    transition: 0.2s;
}

.action-btn.edit {
    background: #e0f2fe;
    color: #0284c7;
}

.action-btn.edit:hover {
    background: #bae6fd;
}

.action-btn.delete {
    background: #fee2e2;
    color: #dc2626;
}

.action-btn.delete:hover {
    background: #fecaca;
}






.inline-input {
    padding: 6px 8px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 12px;
    outline: none;
}

.inline-input:focus {
    border-color: #1b7e6b;
    box-shadow: 0 0 0 2px rgba(27,126,107,0.1);
}




.logout-btn{
    display:flex;
    align-items:center;
    gap:8px;
    background:linear-gradient(135deg,#ef4444,#dc2626);
    color:white;
    text-decoration:none;
    padding:12px 18px;
    border-radius:14px;
    font-size:14px;
    font-weight:600;
    box-shadow:0 6px 16px rgba(239,68,68,0.25);
    transition:0.25s ease;
}

.logout-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 20px rgba(239,68,68,0.35);
}

.logout-btn i{
    font-size:13px;
}

</style>

</head>

<body>

<div class="dashboard-container">

    <div class="admin-header">

        <div class="logo-area">

            <h1>
                <i class="fas fa-map-marked-alt" style="color:#1e7e6c;"></i>
                Admin Dashboard
            </h1>

            <p>Orchestrate trips, members & insights</p>

        </div>

        <div class="admin-badge">
            <i class="fas fa-user-shield"></i>
            Administrator • Travel Planner
        </div>

    
<a href="../Auth/signin.php" class="logout-btn">
    <i class="fas fa-sign-out-alt"></i>
    Logout
</a>
    </div>

    <!-- REPORTS -->

    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-globe-asia"></i>
            </div>

            <div class="stat-title">
                TOTAL TRIPS
            </div>

            <div class="stat-number">
                <?php echo $totalTrips; ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-ban"></i>
            </div>

            <div class="stat-title">
                CANCELLED BOOKINGS
            </div>

            <div class="stat-number">
                <?php echo $cancelledBookings; ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>

            <div class="stat-title">
                PENDING BOOKINGS
            </div>

            <div class="stat-number">
                <?php echo $pendingBookings; ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>

            <div class="stat-title">
                APPROVED BOOKINGS
            </div>

            <div class="stat-number">
                <?php echo $approvedBookings; ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-check"></i>
            </div>

            <div class="stat-title">
                ACTIVE MEMBERS
            </div>

            <div class="stat-number">
                <?php echo $activeMembers; ?>
            </div>
        </div>

    </div>

    

    <div class="two-col-layout">

        <!-- USERS -->

        <div class="card-panel">

            <div class="panel-header">
                <h2>
                    <i class="fas fa-users"></i>
                    Manage Users
                </h2>
            </div>

            <div class="table-wrapper">

                <table class="user-table">

                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                             <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach($users as $user){ ?>

                    <tr>

                        <td>
                            <?php echo $user['name']; ?>
                        </td>

                        <td>
                            <?php echo $user['email']; ?>
                        </td>

                        <td>

                            <span class="role-badge role-<?php echo $user['role']; ?>">

                                <?php echo ucfirst($user['role']); ?>

                            </span>

                        </td>


                      <td>

    <!-- EDIT FORM -->
    <form method="POST" style="display:inline-flex; gap:5px; align-items:center;">

        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">

        <input type="text" name="name" value="<?php echo $user['name']; ?>" class="inline-input">

        <input type="email" name="email" value="<?php echo $user['email']; ?>" class="inline-input">

        <select name="role_id" class="inline-input">

            <option value="1" <?php if($user['role']=="admin") echo "selected"; ?>>Admin</option>
            <option value="2" <?php if($user['role']=="leader") echo "selected"; ?>>Leader</option>
            <option value="3" <?php if($user['role']=="member") echo "selected"; ?>>Member</option>

        </select>

        <button type="submit" name="edit_user" class="action-btn edit">
            <i class="fas fa-save"></i>
        </button>

    </form>

    <!-- DELETE -->
    <form method="POST" style="display:inline;">
        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">

        <button type="submit" name="delete_user" class="action-btn delete"
            onclick="return confirm('Delete this user?');">

            <i class="fas fa-trash"></i>

        </button>
    </form>

</td>

                    </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

        <!-- BOOKINGS -->

        <div class="card-panel">

            <div class="panel-header">

                <h2>
                    <i class="fas fa-calendar-check"></i>
                    Bookings
                </h2>

            </div>

            <div class="table-wrapper">

                <table class="booking-table">

                    <thead>

                    <tr>

                        <th>Member</th>
                        <th>Trip</th>
                        <th>Date</th>
                        <th>Status</th>

                    </tr>

                    </thead>

                    <tbody>

                    <?php foreach($bookings as $booking){ ?>

                    <tr>

                        <td>
                            <?php echo $booking['memberName']; ?>
                        </td>

                        <td>
                            <?php echo $booking['trip']; ?>
                        </td>

                        <td>
                            <?php echo $booking['date']; ?>
                        </td>

                        <td>

                            <span class="status-badge status-<?php echo $booking['status']; ?>">

                                <?php echo ucfirst($booking['status']); ?>

                            </span>

                        </td>

                    </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <footer>
        © Travel Planner Admin — real-time user & booking management
    </footer>

</div>

</body>
</html>
