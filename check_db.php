<?php
require 'Controllers/DBController.php';
$db = new DBController();
$conn = $db->openConnection();
echo "--- USERS ---\n";
$res = $conn->query("SELECT * FROM users LIMIT 3");
if($res) {
    while($r = $res->fetch_assoc()) {
        echo "ID: {$r['user_id']} | Email: {$r['email']} | Role: {$r['role_id']}\n";
    }
}
echo "\n--- ACTIVITIES ---\n";
$res = $conn->query("SELECT * FROM activity LIMIT 5");
if($res) {
    while($r = $res->fetch_assoc()) {
        echo "ID: {$r['activity_id']} | User: {$r['user_id']} | Itinerary: {$r['itinerary_id']} | Desc: {$r['description']} | Status: {$r['status']}\n";
    }
}
echo "\n--- ITINERARY ---\n";
$res = $conn->query("SELECT i.*, t.leader_id FROM itinerary i JOIN trip t ON i.trip_id = t.trip_id LIMIT 3");
if($res) {
    while($r = $res->fetch_assoc()) {
        echo "ID: {$r['itinerary_id']} | Trip: {$r['trip_id']} | Leader: {$r['leader_id']}\n";
    }
}
