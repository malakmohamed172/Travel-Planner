<?php
require_once '../Models/TripMember.php';
require_once '../Models/users.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['trip_id']) || !isset($_POST['email'])) {
        die("Missing data");
    }

    $trip_id = (int) $_POST['trip_id'];
    $email = trim($_POST['email']);

    // 🔥 نجيب user من email
    $userModel = new User();
    $user = $userModel->getUserByEmail($email);

    if (!$user) {
        die("User not found");
    }

    $user_id = $user['user_id'];

    $tripMember = new TripMember();

    if ($tripMember->addMember($trip_id, $user_id)) {
        header("Location: ../Views/User/Members/list.php?trip_id=$trip_id");
        exit();
    } else {
        die("Error adding member");
    }
}