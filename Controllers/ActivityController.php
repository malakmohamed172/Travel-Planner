<?php
require_once __DIR__ . '/DBController.php';
require_once __DIR__ . '/../Models/Activity.php';

session_start();

$db = new DBController();
$conn = $db->openConnection();

if (!isset($_SESSION['user']['id'])) {
    header("Location: ../Views/Auth/signin.php");
    exit;
}

function notify($conn, $user_id, $message){
    $stmt = $conn->prepare("
        INSERT INTO notification (user_id, message)
        VALUES (?, ?)
    ");
    $stmt->bind_param("is", $user_id, $message);
    $stmt->execute();
}

$activity = new Activity($conn);

function getTripLeader($conn, $activity_id){
    $stmt = $conn->prepare("
        SELECT t.leader_id
        FROM activity a
        JOIN itinerary i ON a.itinerary_id = i.itinerary_id
        JOIN trip t ON i.trip_id = t.trip_id
        WHERE a.activity_id = ?
    ");
    $stmt->bind_param("i", $activity_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['leader_id'] ?? null;
}

function activityMatchesItinerary($conn, $activity_id, $itinerary_id){
    $stmt = $conn->prepare("
        SELECT activity_id
        FROM activity
        WHERE activity_id = ? AND itinerary_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $activity_id, $itinerary_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows === 1;
}

/* ================= CREATE ================= */
if(isset($_POST['create'])){

    $activity->user_id = $_SESSION['user']['id'];
    $activity->itinerary_id = (int)$_POST['itinerary_id'];
    $activity->description = $_POST['description'];
    $activity->cost = $_POST['cost'];
    $activity->start_date = $_POST['start_date'];

    $activity->create();

    // 🔔 ADD NOTIFICATION (leader)
    $stmt = $conn->prepare("
        SELECT t.leader_id
        FROM itinerary i
        JOIN trip t ON i.trip_id = t.trip_id
        WHERE i.itinerary_id = ?
    ");
    $stmt->bind_param("i", $activity->itinerary_id);
    $stmt->execute();
    $leader = $stmt->get_result()->fetch_assoc()['leader_id'];

    notify($conn, $leader, "New activity proposed");

    header("Location: ../Views/User/activityManagement.php?itinerary_id=".$activity->itinerary_id);
    exit;
}

/* ================= CHECK OWNER ================= */
function isOwner($conn, $activity_id, $user_id){
    $stmt = $conn->prepare("
        SELECT t.leader_id
        FROM activity a
        JOIN itinerary i ON a.itinerary_id = i.itinerary_id
        JOIN trip t ON i.trip_id = t.trip_id
        WHERE a.activity_id = ?
    ");
    $stmt->bind_param("i", $activity_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res && $res['leader_id'] == $user_id;
}

/* ================= APPROVE ================= */
if(isset($_GET['approve'])){

   $activity_id = (int)$_GET['approve'];
   $itinerary_id = (int)($_GET['itinerary_id'] ?? 0);

   $leader_id = getTripLeader($conn, $activity_id);

   if($leader_id != $_SESSION['user']['id'] || !activityMatchesItinerary($conn, $activity_id, $itinerary_id)){
       die("Unauthorized");
   }

   // 🔔 GET OWNER
   $stmt = $conn->prepare("SELECT user_id FROM activity WHERE activity_id=?");
   $stmt->bind_param("i", $activity_id);
   $stmt->execute();
   $owner = $stmt->get_result()->fetch_assoc()['user_id'];

   $activity->activity_id = $activity_id;
   $activity->approve();

   // 🔔 NOTIFY OWNER
   notify($conn, $owner, "Your activity was approved ✅");

   if(!$itinerary_id){
       die("Missing itinerary id");
   }

   header("Location: ../Views/User/activityManagement.php?itinerary_id=".$itinerary_id);
   exit;
}

/* ================= REJECT ================= */
if(isset($_GET['reject'])){

    $activity_id = (int)$_GET['reject'];
    $itinerary_id = (int)($_GET['itinerary_id'] ?? 0);

    $leader_id = getTripLeader($conn, $activity_id);

    if($leader_id != $_SESSION['user']['id'] || !activityMatchesItinerary($conn, $activity_id, $itinerary_id)){
        die("Unauthorized");
    }

    // 🔔 GET OWNER
    $stmt = $conn->prepare("SELECT user_id FROM activity WHERE activity_id=?");
    $stmt->bind_param("i", $activity_id);
    $stmt->execute();
    $owner = $stmt->get_result()->fetch_assoc()['user_id'];

    $activity->activity_id = $activity_id;
    $activity->reject();

    // 🔔 NOTIFY OWNER
    notify($conn, $owner, "Your activity was rejected ❌");

    if(!$itinerary_id){
        die("Missing itinerary id");
    }

    header("Location: ../Views/User/activityManagement.php?itinerary_id=".$itinerary_id);
    exit;
}
/* ================= GOING ================= */
if(isset($_GET['going'])){

    $activity_id = (int)$_GET['going'];
    $itinerary_id = (int)($_GET['itinerary_id'] ?? 0);

    if (!activityMatchesItinerary($conn, $activity_id, $itinerary_id)) {
        die("Unauthorized");
    }

    $stmt = $conn->prepare("
        UPDATE activity
        SET rsvp_status='going'
        WHERE activity_id=?
    ");

    $stmt->bind_param("i", $activity_id);
    $stmt->execute();

    header("Location: ../Views/User/activityManagement.php?itinerary_id=".$itinerary_id);
    exit;
}

/* ================= NOT GOING ================= */
if(isset($_GET['notgoing'])){

    $activity_id = (int)$_GET['notgoing'];
    $itinerary_id = (int)($_GET['itinerary_id'] ?? 0);

    if (!activityMatchesItinerary($conn, $activity_id, $itinerary_id)) {
        die("Unauthorized");
    }

    $stmt = $conn->prepare("
        UPDATE activity
        SET rsvp_status='not_going'
        WHERE activity_id=?
    ");

    $stmt->bind_param("i", $activity_id);
    $stmt->execute();

    header("Location: ../Views/User/activityManagement.php?itinerary_id=".$itinerary_id);
    exit;
}
