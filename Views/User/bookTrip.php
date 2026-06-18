<?php
session_start();
require_once __DIR__ . '/../../Controllers/BookingController.php';

if (!isset($_GET['trip_id'])) {
    die('Trip not found');
}

$trip_id = (int) $_GET['trip_id'];

$controller = new BookingController();
$controller->create($trip_id);
