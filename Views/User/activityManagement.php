<?php
session_start();
require_once __DIR__ . '/../../Controllers/DBController.php';
require_once __DIR__ . '/../../Models/Activity.php';

if (!isset($_SESSION['user']['id'])) {
    die("Please login first");
}

$db = new DBController();
$conn = $db->openConnection();

$activity = new Activity($conn);

$itinerary_id = isset($_GET['itinerary_id']) ? (int)$_GET['itinerary_id'] : 0;

if($itinerary_id <= 0){
    die("Invalid itinerary");
}

$stmt = $conn->prepare("
    SELECT t.leader_id
    FROM itinerary i
    JOIN trip t ON i.trip_id = t.trip_id
    WHERE i.itinerary_id = ?
");
$stmt->bind_param("i", $itinerary_id);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();

if (!$trip) {
    die("Invalid itinerary");
}

$isTripLeader = (int)$trip['leader_id'] === (int)$_SESSION['user']['id'];

$activity->itinerary_id = $itinerary_id;
$activities = $activity->getByItinerary();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Activities</title>

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

.page-title{
    margin-bottom:25px;
}

.page-title h1{
    font-size:36px;
    font-weight:700;
    color:#0f172a;
}

.page-title p{
    color:#64748b;
    margin-top:8px;
    font-size:15px;
}

.card{
    background:white;
    border-radius:24px;
    padding:28px;
    margin-bottom:25px;
    box-shadow:0 10px 30px rgba(15,23,42,.08);
    border:1px solid #e2e8f0;
}

.card h2{
    font-size:24px;
    margin-bottom:20px;
    color:#0f172a;
}

.form-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:16px;
}

.input-group{
    display:flex;
    flex-direction:column;
}

.input-group label{
    font-size:14px;
    font-weight:600;
    margin-bottom:8px;
    color:#334155;
}

.input-group input{
    padding:14px;
    border-radius:14px;
    border:1px solid #cbd5e1;
    background:#f8fafc;
    outline:none;
    transition:.2s;
    font-size:14px;
}

.input-group input:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 4px rgba(37,99,235,.12);
    background:white;
}

.submit-btn{
    margin-top:22px;
    width:100%;
    padding:14px;
    border:none;
    border-radius:16px;
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    color:white;
    font-size:15px;
    font-weight:600;
    cursor:pointer;
    transition:.2s;
}

.submit-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 12px 24px rgba(37,99,235,.25);
}

.activities-grid{
    display:flex;
    flex-direction:column;
    gap:18px;
}

.activity{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:20px;
    padding:22px;
    transition:.2s;
}

.activity:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 20px rgba(0,0,0,.05);
}

.activity-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    flex-wrap:wrap;
    gap:12px;
    margin-bottom:15px;
}

.activity-title{
    font-size:20px;
    font-weight:700;
    color:#0f172a;
}

.status{
    padding:8px 14px;
    border-radius:999px;
    font-size:13px;
    font-weight:700;
    text-transform:capitalize;
}

.status-approved{
    background:#dcfce7;
    color:#166534;
}

.status-rejected{
    background:#fee2e2;
    color:#b91c1c;
}

.status-pending{
    background:#fef3c7;
    color:#92400e;
}

.activity-info{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:12px;
    margin-bottom:18px;
}

.info-box{
    background:white;
    border-radius:14px;
    padding:14px;
    border:1px solid #e2e8f0;
}

.info-label{
    font-size:12px;
    color:#64748b;
    margin-bottom:6px;
}

.info-value{
    font-size:15px;
    font-weight:600;
    color:#0f172a;
}

.actions{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-top:10px;
}

.btn{
    text-decoration:none;
    padding:10px 16px;
    border-radius:12px;
    font-size:14px;
    font-weight:600;
    transition:.2s;
    display:inline-flex;
    align-items:center;
    gap:6px;
}

.btn:hover{
    transform:translateY(-1px);
}

.btn-approve{
    background:#16a34a;
    color:white;
}

.btn-reject{
    background:#dc2626;
    color:white;
}

.btn-going{
    background:#2563eb;
    color:white;
}

.btn-notgoing{
    background:#475569;
    color:white;
}

.rsvp-box{
    margin-top:16px;
    padding:12px;
    background:#eff6ff;
    border:1px solid #bfdbfe;
    border-radius:12px;
    color:#1e3a8a;
    font-size:14px;
}

.empty{
    text-align:center;
    padding:40px;
    color:#64748b;
    font-size:16px;
}

@media(max-width:768px){

    body{
        padding:20px 14px;
    }

    .card{
        padding:20px;
    }

    .page-title h1{
        font-size:28px;
    }

    .activity-top{
        flex-direction:column;
        align-items:flex-start;
    }
}

</style>
</head>

<body>

<div class="container">

    <div class="page-title">
        <h1>📋 Activities Management</h1>
        
    </div>

    <!-- PROPOSE ACTIVITY -->
    <div class="card">

        <h2>➕ Propose New Activity</h2>

        <form method="POST" action="../../Controllers/ActivityController.php">

            <input type="hidden" name="itinerary_id" value="<?= $itinerary_id ?>">

            <div class="form-grid">

                <div class="input-group">
                    <label>Activity Title</label>
                    <input type="text" name="description" placeholder="Enter activity title" required>
                </div>

                <div class="input-group">
                    <label>Cost</label>
                    <input type="number" name="cost" placeholder="Enter cost" required>
                </div>

                <div class="input-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" required>
                </div>

            </div>

            <button class="submit-btn" name="create">
                Submit Activity
            </button>

        </form>

    </div>

    <!-- ACTIVITIES LIST -->
    <div class="card">

        <h2>🗂 Activities List</h2>

        <div class="activities-grid">

        <?php if($activities && $activities->num_rows > 0){ ?>

            <?php while($act = $activities->fetch_assoc()){ ?>

                <div class="activity">

                    <div class="activity-top">

                        <div class="activity-title">
                            <?= htmlspecialchars($act['description']) ?>
                        </div>

                        <div class="status status-<?= $act['status'] ?>">
                            <?= htmlspecialchars($act['status']) ?>
                        </div>

                    </div>

                    <div class="activity-info">

                        <div class="info-box">
                            <div class="info-label">💰 Cost</div>
                            <div class="info-value">
                                <?= htmlspecialchars($act['cost']) ?>
                            </div>
                        </div>

                        <div class="info-box">
                            <div class="info-label">📅 Start Date</div>
                            <div class="info-value">
                                <?= htmlspecialchars($act['start_date']) ?>
                            </div>
                        </div>

                    </div>

                    <?php if($isTripLeader && $act['status'] == 'pending'){ ?>

                    <div class="actions">

                        <a 
                        class="btn btn-approve"
                        href="../../Controllers/ActivityController.php?approve=<?= $act['activity_id']?>&itinerary_id=<?= $itinerary_id ?>">
                        ✅ Approve
                        </a>

                        <a 
                        class="btn btn-reject"
                        href="../../Controllers/ActivityController.php?reject=<?= $act['activity_id']?>&itinerary_id=<?= $itinerary_id ?>">
                        ❌ Reject
                        </a>

                    </div>

                    <?php } ?>

                    <div class="actions" style="margin-top:14px;">

                        <a 
                        class="btn btn-going"
                        href="../../Controllers/ActivityController.php?going=<?= $act['activity_id'] ?>&itinerary_id=<?= $itinerary_id ?>">
                        👍 Going
                        </a>

                        <a 
                        class="btn btn-notgoing"
                        href="../../Controllers/ActivityController.php?notgoing=<?= $act['activity_id'] ?>&itinerary_id=<?= $itinerary_id ?>">
                        👎 Not Going
                        </a>

                    </div>

                    <?php if(!empty($act['rsvp_status'])){ ?>

                    <div class="rsvp-box">
                        RSVP Status:
                        <strong>
                            <?= htmlspecialchars($act['rsvp_status']) ?>
                        </strong>
                    </div>

                    <?php } ?>

                </div>

            <?php } ?>

        <?php } else { ?>

            <div class="empty">
                No activities available yet.
            </div>

        <?php } ?>

        </div>

    </div>

</div>

</body>
</html>
