<?php
session_start();
require_once __DIR__ . '/../../Controllers/BookingController.php';

$booking_id = (int) ($_POST['booking_id'] ?? 0);
if ($booking_id <= 0) {
    die('Invalid booking');
}

$controller = new BookingController();
$controller->cancel($booking_id);
