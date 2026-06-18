<?php
require_once __DIR__ . '/DBController.php';

class BookingController {

    public function create(int $trip_id): void {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            die('Login first');
        }

        $db = new DBController();
        $conn = $db->openConnection();

        $user_id = (int) $_SESSION['user']['id'];

        // Get trip budget
        $stmt = $conn->prepare('SELECT budget FROM trip WHERE trip_id = ?');
        $stmt->bind_param('i', $trip_id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$data) {
            die('Trip not found');
        }

        $cost = (float) ($data['budget'] ?? 0);

        // Check existing booking
        $stmt = $conn->prepare('
            SELECT booking_id, status 
            FROM booking
            WHERE user_id = ? AND trip_id = ?
            LIMIT 1
        ');
        $stmt->bind_param('ii', $user_id, $trip_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {

            $booking_id = (int) $existing['booking_id'];
            $existing_status = (string) $existing['status'];

            // If cancelled → rebook
            if ($existing_status === 'cancelled') {

                $stmt = $conn->prepare("
                    UPDATE booking
                    SET status = 'pending',
                        cost = ?,
                        date = NOW()
                    WHERE booking_id = ? AND user_id = ?
                ");
                $stmt->bind_param('dii', $cost, $booking_id, $user_id);
                $stmt->execute();
                $stmt->close();

                $_SESSION['booking_message'] = 'Trip booked again. Complete payment from My Bookings.';

            } else {
                $_SESSION['booking_message'] = 'You already booked this trip.';
            }

        } else {

            // Create new booking
            $stmt = $conn->prepare('
                INSERT INTO booking (user_id, trip_id, date, cost, status)
                VALUES (?, ?, NOW(), ?, ?)
            ');

            $status = 'pending';
            $stmt->bind_param('iids', $user_id, $trip_id, $cost, $status);
            $stmt->execute();
            $stmt->close();

            $_SESSION['booking_message'] = 'Trip booked successfully. Complete payment from My Bookings.';
        }

        header('Location: viewBookings.php');
        exit();
    }

    public function cancel(int $booking_id): void {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            die('Login first');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die('Invalid request');
        }

        $user_id = (int) $_SESSION['user']['id'];

        $db = new DBController();
        $conn = $db->openConnection();

        $stmt = $conn->prepare("
            UPDATE booking
            SET status = 'cancelled'
            WHERE booking_id = ? 
              AND user_id = ? 
              AND status <> 'cancelled'
        ");

        $stmt->bind_param('ii', $booking_id, $user_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['booking_message'] = 'Booking cancelled.';
        header('Location: viewBookings.php');
        exit();
    }
}