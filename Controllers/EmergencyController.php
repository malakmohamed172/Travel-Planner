<?php

require_once __DIR__ . '/DBController.php';
require_once __DIR__ . '/../Models/EmergencyContact.php';

session_start();

$db = new DBController();
$conn = $db->openConnection();

$em = new EmergencyContact($conn);

/* CREATE */
if(isset($_POST['create'])){

    $em->trip_id = (int)$_POST['trip_id'];
    $em->name = $_POST['name'];
    $em->phone = $_POST['phone'];

    $em->create();

    header("Location: ../Views/User/emergency.php?trip_id=".$em->trip_id);
    exit;
}