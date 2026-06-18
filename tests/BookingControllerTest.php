<?php
/**
 * ============================================================
 *  Unit Tests — BookingController
 * ============================================================
 *  Tests for: Create Booking, Cancel Booking
 * ============================================================
 */

use PHPUnit\Framework\TestCase;

class BookingControllerTest extends TestCase
{
    private $conn;
    private $userId;
    private $tripId;
    private $bookingId;

    protected function setUp(): void
    {
        $this->conn = getTestConnection();

        // Ensure user exists
        $this->userId = 9991; // Dummy user ID for tests
        $this->conn->query("INSERT IGNORE INTO users (user_id, name, email, password, role_id) VALUES ($this->userId, 'Booking User', 'booking@test.com', 'pass', 2)");

        // Ensure leader exists for trip
        $this->conn->query("INSERT IGNORE INTO leader (leader_id) VALUES ($this->userId)");

        // Ensure trip exists
        $this->conn->query("INSERT INTO trip (leader_id, name, description, budget, status, start_date, end_date) VALUES ($this->userId, 'Booking Trip', 'Desc', 1000, 'upcoming', '2026-01-01', '2026-01-10')");
        $this->tripId = $this->conn->insert_id;
    }

    protected function tearDown(): void
    {
        $this->conn->query("DELETE FROM booking WHERE user_id = {$this->userId}");
        $this->conn->query("DELETE FROM trip WHERE trip_id = {$this->tripId}");
        $this->conn->query("DELETE FROM users WHERE user_id = {$this->userId}");
        $this->conn->close();
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 1: Create Booking
     *  INPUT: user_id, trip_id, cost
     *  EXPECTED: Booking is inserted with status 'pending'
     * ───────────────────────────────────────────────────────── */
    public function testCreateBookingSuccessfully(): void
    {
        // INPUT: Simulate BookingController logic
        $stmt = $this->conn->prepare("INSERT INTO booking (user_id, trip_id, date, cost, status) VALUES (?, ?, NOW(), 1000, 'pending')");
        $stmt->bind_param("ii", $this->userId, $this->tripId);
        $result = $stmt->execute();
        $this->bookingId = $stmt->insert_id;
        $stmt->close();

        // ✅ EXPECTED
        $this->assertTrue($result, "Booking should be created successfully");
        $this->assertGreaterThan(0, $this->bookingId, "Booking ID should be > 0");

        // Verify status
        $res = $this->conn->query("SELECT status FROM booking WHERE booking_id = {$this->bookingId}");
        $row = $res->fetch_assoc();
        $this->assertEquals('pending', $row['status'], "Initial booking status should be pending");
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 2: Cancel Booking
     *  INPUT: booking_id
     *  EXPECTED: Status changes to 'cancelled'
     * ───────────────────────────────────────────────────────── */
    public function testCancelBookingSuccessfully(): void
    {
        // Setup: Create booking
        $this->conn->query("INSERT INTO booking (user_id, trip_id, date, cost, status) VALUES ($this->userId, $this->tripId, NOW(), 1000, 'pending')");
        $bookingId = $this->conn->insert_id;

        // INPUT: Cancel Booking
        $stmt = $this->conn->prepare("UPDATE booking SET status = 'cancelled' WHERE booking_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $bookingId, $this->userId);
        $result = $stmt->execute();
        $stmt->close();

        // ✅ EXPECTED
        $this->assertTrue($result, "Booking cancellation should succeed");

        // Verify status
        $res = $this->conn->query("SELECT status FROM booking WHERE booking_id = {$bookingId}");
        $row = $res->fetch_assoc();
        $this->assertEquals('cancelled', $row['status'], "Booking status should be cancelled");
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 3: Prevent duplicate bookings for same trip/user
     *  EXPECTED: If pending booking exists, logic detects it
     * ───────────────────────────────────────────────────────── */
    public function testPreventDuplicateBooking(): void
    {
        // Setup
        $this->conn->query("INSERT INTO booking (user_id, trip_id, date, cost, status) VALUES ($this->userId, $this->tripId, NOW(), 1000, 'pending')");

        // INPUT: Check if exists
        $stmt = $this->conn->prepare("SELECT booking_id FROM booking WHERE user_id = ? AND trip_id = ? AND status != 'cancelled'");
        $stmt->bind_param("ii", $this->userId, $this->tripId);
        $stmt->execute();
        $stmt->store_result();

        // ✅ EXPECTED: Should find existing active booking
        $this->assertEquals(1, $stmt->num_rows, "Duplicate active booking should be detected");
        $stmt->close();
    }
}
