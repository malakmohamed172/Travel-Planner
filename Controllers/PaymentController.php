<?php
require_once __DIR__ . '/DBController.php';

class PaymentController {

    public function pay(): void {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            die('Login first');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die('Invalid request');
        }

        $user_id    = (int) $_SESSION['user']['id'];
        $booking_id = (int) ($_POST['booking_id'] ?? 0);
        $amount_in  = (float) ($_POST['amount'] ?? 0);

        $traveler_name   = trim((string) ($_POST['traveler_full_name'] ?? ''));
        $cardholder_name = trim((string) ($_POST['cardholder_name'] ?? ''));
        $card_number     = preg_replace('/\D/', '', (string) ($_POST['card_number'] ?? ''));
        $expiry          = trim((string) ($_POST['expiry_date'] ?? ''));
        $cvv             = preg_replace('/\D/', '', (string) ($_POST['cvv'] ?? ''));

        if ($booking_id <= 0) {
            $_SESSION['payment_error'] = 'Invalid booking.';
            header('Location: pay.php');
            exit();
        }

        $errors = [];
        if ($traveler_name === '' || strlen($traveler_name) < 2) {
            $errors[] = 'Enter the traveler full name.';
        }
        if ($cardholder_name === '' || strlen($cardholder_name) < 2) {
            $errors[] = 'Enter the card holder name.';
        }
        if (!preg_match('/^\d{16}$/', $card_number)) {
            $errors[] = 'Card number must be exactly 16 digits.';
        }
        if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
            $errors[] = 'Expiry must be MM/YY.';
        }
        if (strlen($cvv) < 3 || strlen($cvv) > 4) {
            $errors[] = 'Enter a valid CVV (3 or 4 digits).';
        }

        if ($errors) {
            $_SESSION['payment_error'] = implode(' ', $errors);
            header('Location: pay.php?booking_id=' . $booking_id);
            exit();
        }

        $db = new DBController();
        $conn = $db->openConnection();

        $stmt = $conn->prepare('
            SELECT b.booking_id, b.user_id, b.cost, b.status
            FROM booking b
            WHERE b.booking_id = ?
            LIMIT 1
        ');
        $stmt->bind_param('i', $booking_id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking || (int) $booking['user_id'] !== $user_id) {
            $_SESSION['payment_error'] = 'Booking not found or access denied.';
            header('Location: pay.php?booking_id=' . $booking_id);
            exit();
        }

        $cost = (float) $booking['cost'];
        if (abs($cost - $amount_in) > 0.009) {
            $_SESSION['payment_error'] = 'Amount does not match booking total.';
            header('Location: pay.php?booking_id=' . $booking_id);
            exit();
        }

        $stmt = $conn->prepare("
            SELECT payment_id FROM payment
            WHERE booking_id = ? AND status IN ('paid','successful')
            LIMIT 1
        ");
        $stmt->bind_param('i', $booking_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            header('Location: viewBookings.php');
            exit();
        }
        $stmt->close();

        $card_last4 = substr($card_number, -4);

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare('
                INSERT INTO payment (
                    booking_id, user_id, traveler_full_name, cardholder_name,
                    card_last4, card_expiry, date, amount, status
                ) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)
            ');
            $status = 'successful';
            $stmt->bind_param(
                'iissssds',
                $booking_id,
                $user_id,
                $traveler_name,
                $cardholder_name,
                $card_last4,
                $expiry,
                $cost,
                $status
            );
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("
                UPDATE booking SET status = 'confirmed' WHERE booking_id = ?
            ");
            $stmt->bind_param('i', $booking_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            $_SESSION['payment_error'] = 'Payment could not be completed. Please try again.';
            header('Location: pay.php?booking_id=' . $booking_id);
            exit();
        }

        unset($_SESSION['payment_error']);
        header('Location: viewBookings.php');
        exit();
    }
}
