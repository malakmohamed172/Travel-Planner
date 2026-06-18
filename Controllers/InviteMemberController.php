<?php
session_start();

require_once __DIR__ . '/DBController.php';

$db = new DBController();
$conn = $db->openConnection();

function notify($conn, $user_id, $message){

    $stmt = $conn->prepare("
        INSERT INTO notification(user_id, message)
        VALUES(?, ?)
    ");

    $stmt->bind_param("is", $user_id, $message);
    $stmt->execute();
}

if(isset($_POST['invite'])){

    $trip_id = (int)$_POST['trip_id'];
    $email = trim($_POST['email']);

   
    $stmt = $conn->prepare("
        SELECT user_id
        FROM users
        WHERE email = ?
    ");


    

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    if(!$user){
        die("User not found");
    }

    $user_id = $user['user_id'];

    //to prove that he is not member already //
  $stmt = $conn->prepare(" SELECT * FROM trip_member WHERE trip_id = ? AND user_id = ? ");
   $stmt->bind_param("ii", $trip_id, $user_id); 
   $stmt->execute(); 
  if($stmt->get_result()->num_rows > 0){
    echo "<script>
            alert('User is already invited to this trip');
            window.history.back();
          </script>";
    exit;
}
    //invite member/
$stmt = $conn->prepare("
    INSERT INTO trip_member(trip_id, user_id)
    VALUES(?, ?)
");

$stmt->bind_param("ii", $trip_id, $user_id);
$stmt->execute();


$stmt = $conn->prepare("
    SELECT name
    FROM trip
    WHERE trip_id = ?
");

$stmt->bind_param("i", $trip_id);
$stmt->execute();

$result = $stmt->get_result();
$trip = $result->fetch_assoc();

$trip_name = $trip['name'] ?? 'Unknown Trip';

/* notification */
notify(
    $conn,
    $user_id,
    "You were invited to join trip: " . $trip_name ." trip"
);

    header("Location: ../Views/User/viewBookings.php");
    exit;
}