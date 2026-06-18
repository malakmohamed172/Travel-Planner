<?php
/**
 * ============================================================
 *  Travel Planner — PHPUnit Bootstrap
 * ============================================================
 *  Sets up the test environment:
 *  • Autoloads project classes
 *  • Creates a shared test DB connection (mysqli)
 *  • Provides helper functions used across all test files
 * ============================================================
 */

error_reporting(E_ALL);

/* ── Project root ─────────────────────────────────────────── */
define('PROJECT_ROOT', realpath(__DIR__ . '/..'));

/* ── Autoload Models ──────────────────────────────────────── */
require_once PROJECT_ROOT . '/Models/users.php';
require_once PROJECT_ROOT . '/Models/Trip.php';
require_once PROJECT_ROOT . '/Models/booking.php';
require_once PROJECT_ROOT . '/Models/payment.php';
require_once PROJECT_ROOT . '/Models/activity.php';
require_once PROJECT_ROOT . '/Models/expense.php';
require_once PROJECT_ROOT . '/Models/emergencyContact.php';
require_once PROJECT_ROOT . '/Models/document.php';
require_once PROJECT_ROOT . '/Models/notification.php';
require_once PROJECT_ROOT . '/Models/itinerary.php';
require_once PROJECT_ROOT . '/Models/role.php';
require_once PROJECT_ROOT . '/Models/tripMember.php';

/* ── Autoload Controllers (class-based only) ──────────────── */
require_once PROJECT_ROOT . '/Controllers/DBController.php';
// AuthController has require_once inside, so we load it carefully
// require_once PROJECT_ROOT . '/Controllers/AuthController.php';
// BookingController / PaymentController are class-based
require_once PROJECT_ROOT . '/Controllers/BookingController.php';
require_once PROJECT_ROOT . '/Controllers/PaymentController.php';

/* ── Test Database Helper ─────────────────────────────────── */
function getTestConnection(): mysqli {
    $conn = new mysqli('localhost', 'root', '', 'travel_planner1');
    if ($conn->connect_error) {
        die("Test DB Connection Failed: " . $conn->connect_error);
    }
    return $conn;
}
