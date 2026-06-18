<?php
session_start();

require_once __DIR__ . '/../../Controllers/DBController.php';

if (!isset($_SESSION['user']['id'])) {
    die('Please login first');
}

$db = new DBController();
$conn = $db->openConnection();

$user_id = (int) $_SESSION['user']['id'];

/* ================= GET TRIPS ================= */

$stmt = $conn->prepare("
    SELECT
        t.trip_id,
        t.name,
        t.start_date,
        t.end_date,
        t.budget,

        EXISTS(
            SELECT 1
            FROM booking b
            WHERE b.trip_id = t.trip_id
            AND b.user_id = ?
            AND b.status <> 'cancelled'
        ) AS already_booked

    FROM trip t

    ORDER BY t.trip_id DESC
");

$stmt->bind_param('i', $user_id);
$stmt->execute();

$result = $stmt->get_result();

/* ================= BUILD ARRAY ================= */

$trips = [];
$tripIds = [];

while ($trip = $result->fetch_assoc()) {

    $trip['itineraries'] = [];

    $tripId = (int) $trip['trip_id'];

    $trips[$tripId] = $trip;

    $tripIds[] = $tripId;
}

/* ================= GET ITINERARIES ================= */

if (!empty($tripIds)) {

    $in = implode(',', array_fill(0, count($tripIds), '?'));
    $types = str_repeat('i', count($tripIds));

    $sql = "
        SELECT
            i.itinerary_id,
            i.trip_id,
            i.day_number,
            i.itinerary_date,
            i.title,
            s.stop_name,
            s.stop_order

        FROM itinerary i

        LEFT JOIN itinerary_stop s
        ON s.itinerary_id = i.itinerary_id

        WHERE i.trip_id IN ($in)

        ORDER BY i.trip_id, i.day_number, s.stop_order
    ";

    $stmt2 = $conn->prepare($sql);

    $stmt2->bind_param($types, ...$tripIds);

    $stmt2->execute();

    $res2 = $stmt2->get_result();

    while ($row = $res2->fetch_assoc()) {

        $tripId = (int) $row['trip_id'];
        $itineraryId = (int) $row['itinerary_id'];

        if (!isset($trips[$tripId]['itineraries'][$itineraryId])) {

            $trips[$tripId]['itineraries'][$itineraryId] = [

                'day_number' => $row['day_number'],
                'itinerary_date' => $row['itinerary_date'],
                'title' => $row['title'],
                'stops' => []

            ];
        }

        if (!empty($row['stop_name'])) {

            $trips[$tripId]['itineraries'][$itineraryId]['stops'][] = $row['stop_name'];

        }
    }
}

/* ================= EMERGENCY CONTACTS ================= */

$emergency = [];

if (!empty($tripIds)) {

    $in = implode(',', array_fill(0, count($tripIds), '?'));
    $types = str_repeat('i', count($tripIds));

    $sql = "

        SELECT
            trip_id,
            name,
            phone

        FROM emergency_contact

        WHERE trip_id IN ($in)

        ORDER BY contact_id DESC

    ";

    $stmt3 = $conn->prepare($sql);

    $stmt3->bind_param($types, ...$tripIds);

    $stmt3->execute();

    $res3 = $stmt3->get_result();

    while ($row = $res3->fetch_assoc()) {

        $emergency[$row['trip_id']][] = $row;

    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Upcoming Trips</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Segoe UI',sans-serif;
    background:linear-gradient(to bottom right,#eef4ff,#f8fbff);
    padding:30px;
    color:#1e293b;
}

.container{
    max-width:1400px;
    margin:auto;
}

.page-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:35px;
    flex-wrap:wrap;
    gap:15px;
}

.page-header h2{
    font-size:2rem;
    color:#2563eb;
    font-weight:700;
}

.page-header p{
    color:#64748b;
    margin-top:5px;
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

.top-badge{
    background:white;
    padding:12px 18px;
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,.05);
    font-weight:600;
}

.trips-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(340px,1fr));
    gap:25px;
}

.trip-card{
    background:white;
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 10px 25px rgba(0,0,0,.06);
    transition:.25s;
    border:1px solid #e2e8f0;
}

.trip-card:hover{
    transform:translateY(-6px);
    box-shadow:0 14px 30px rgba(0,0,0,.08);
}

.trip-top{
    padding:22px;
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    color:white;
}

.trip-top h3{
    font-size:1.4rem;
    margin-bottom:12px;
}

.trip-info{
    display:flex;
    flex-direction:column;
    gap:8px;
    font-size:14px;
}

.trip-content{
    padding:22px;
}

.info-box{
    background:#f8fafc;
    border-radius:14px;
    padding:12px 14px;
    margin-bottom:12px;
    border:1px solid #e2e8f0;
}

.info-title{
    font-size:12px;
    color:#64748b;
    margin-bottom:5px;
    font-weight:600;
}

.info-value{
    font-weight:700;
    color:#0f172a;
}

.section-box{
    margin-top:18px;
    background:#f8fafc;
    border-radius:16px;
    padding:16px;
    border:1px solid #e2e8f0;
}

.section-title{
    font-size:15px;
    font-weight:700;
    margin-bottom:12px;
    color:#0f172a;
}

.itinerary-day{
    padding:12px;
    background:white;
    border-radius:12px;
    margin-bottom:10px;
    border:1px solid #e2e8f0;
}

.day-title{
    font-weight:700;
    color:#1e293b;
    margin-bottom:6px;
}

.day-date{
    font-size:12px;
    color:#64748b;
    margin-bottom:6px;
}

.day-desc{
    font-size:14px;
    color:#334155;
}

.stops{
    margin-top:10px;
    padding-left:18px;
}

.stops li{
    margin-bottom:4px;
    color:#475569;
    font-size:14px;
}

.activity-btn{
    display:inline-block;
    margin-top:10px;
    padding:8px 12px;
    background:#2563eb;
    color:white;
    border-radius:10px;
    text-decoration:none;
    font-size:13px;
    font-weight:600;
    transition:.2s;
}

.activity-btn:hover{
    background:#1d4ed8;
}

.emergency-contact{
    background:white;
    padding:10px;
    border-radius:12px;
    margin-bottom:8px;
    border:1px solid #e2e8f0;
    font-size:14px;
}

.add-contact-btn{
    display:inline-block;
    margin-top:10px;
    padding:10px 14px;
    background:#f59e0b;
    color:white;
    border-radius:10px;
    text-decoration:none;
    font-size:13px;
    font-weight:600;
    transition:.2s;
}

.add-contact-btn:hover{
    background:#d97706;
}

.book-btn{
    display:block;
    width:100%;
    margin-top:20px;
    padding:14px;
    background:linear-gradient(135deg,#16a34a,#15803d);
    color:white;
    text-align:center;
    border-radius:14px;
    text-decoration:none;
    font-weight:700;
    transition:.25s;
}

.book-btn:hover{
    transform:translateY(-2px);
}

.booked-btn{
    background:#64748b;
    cursor:not-allowed;
}

.empty-box{
    background:white;
    padding:40px;
    text-align:center;
    border-radius:24px;
    box-shadow:0 8px 20px rgba(0,0,0,.05);
}

.empty-box h3{
    color:#334155;
    margin-bottom:10px;
}

.empty-box p{
    color:#64748b;
}

@media(max-width:768px){

    body{
        padding:18px;
    }

    .page-header{
        flex-direction:column;
        align-items:flex-start;
    }

    .trip-top h3{
        font-size:1.2rem;
    }

}

</style>

</head>

<body>

<div class="container">

    <div class="page-header">

        <div>

            <h2>✈ Upcoming Trips</h2>

            <p>
                Explore destinations, itineraries, emergency contacts & bookings
            </p>

        </div>

        <div class="top-badge">

            <?= count($trips) ?> Trips Available

           

        </div>


         <a class="back-btn" href="homepage.php">
            Return to Homepage
        </a>

    </div>

    <div class="trips-grid">

        <?php if(!empty($trips)){ ?>

            <?php foreach($trips as $trip){ ?>

            <div class="trip-card">

                <div class="trip-top">

                    <h3>
                        <?= htmlspecialchars($trip['name']) ?>
                    </h3>

                    <div class="trip-info">

                        <div>
                            📅 <?= $trip['start_date'] ?>
                            → <?= $trip['end_date'] ?>
                        </div>

                        <div>
                            💰 Budget: <?= $trip['budget'] ?>  EGP
                        </div>

                    </div>

                </div>

                <div class="trip-content">

                    <!-- INFO -->

                    <div class="info-box">

                        <div class="info-title">
                            Trip Duration
                        </div>

                        <div class="info-value">
                            <?= $trip['start_date'] ?>
                            → <?= $trip['end_date'] ?>
                        </div>

                    </div>

                    <div class="info-box">

                        <div class="info-title">
                            Estimated Budget
                        </div>

                        <div class="info-value">
                            <?= $trip['budget'] ?> EGP
                        </div>

                    </div>

                    <!-- ITINERARY -->

                    <?php if (!empty($trip['itineraries'])) { ?>

                    <div class="section-box">

                        <div class="section-title">
                            🗺 Itinerary Plan
                        </div>

                        <?php foreach ($trip['itineraries'] as $itId => $it) { ?>

                        <div class="itinerary-day">

                            <div class="day-title">

                                Day <?= $it['day_number'] ?>

                            </div>

                            <div class="day-date">

                                <?= $it['itinerary_date'] ?>

                            </div>

                            <div class="day-desc">

                                <?= htmlspecialchars($it['title']) ?>

                            </div>

                            <?php if (!empty($it['stops'])) { ?>

                            <ul class="stops">

                                <?php foreach ($it['stops'] as $s) { ?>

                                <li>

                                    <?= htmlspecialchars($s) ?>

                                </li>

                                <?php } ?>

                            </ul>

                            <?php } ?>

                            <a class="activity-btn"
                               href="activityManagement.php?itinerary_id=<?= $itId ?>">

                                Manage Activities

                            </a>

                        </div>

                        <?php } ?>

                    </div>

                    <?php } ?>

                    <!-- EMERGENCY CONTACTS -->

                    <div class="section-box">

                        <div class="section-title">

                            🚨 Emergency Contacts

                        </div>

                        <?php if (!empty($emergency[$trip['trip_id']])) { ?>

                            <?php foreach ($emergency[$trip['trip_id']] as $c) { ?>

                            <div class="emergency-contact">

                                👤 <?= htmlspecialchars($c['name']) ?>

                                <br>

                                📞 <?= htmlspecialchars($c['phone']) ?>

                            </div>

                            <?php } ?>

                        <?php } else { ?>

                            <div style="color:#64748b;font-size:14px;">

                                No emergency contacts available

                            </div>

                        <?php } ?>

                        <a href="emergency.php?trip_id=<?= $trip['trip_id'] ?>"
                           class="add-contact-btn">

                            + Add Emergency Contact

                        </a>

                    </div>

                    <!-- BOOKING -->

                    <?php if ((int)$trip['already_booked'] === 0) { ?>

                    <a class="book-btn"
                       href="bookTrip.php?trip_id=<?= $trip['trip_id'] ?>">

                        Book Trip 💳

                    </a>

                    <?php } else { ?>

                    <a class="book-btn booked-btn">

                        Already Booked

                    </a>

                    <?php } ?>

                </div>

            </div>

            <?php } ?>

        <?php } else { ?>

        <div class="empty-box">

            <h3>No Trips Available</h3>

            <p>
                There are currently no upcoming trips.
            </p>

        </div>

        <?php } ?>

    </div>

</div>

</body>
</html>