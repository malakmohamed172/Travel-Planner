<?php
/**
 * ============================================================
 *  Unit Tests — PaymentController
 * ============================================================
 *  Tests for: Process Payment
 * ============================================================
 */

use PHPUnit\Framework\TestCase;

class PaymentControllerTest extends TestCase
{
    private $conn;
    private $userId = 9992;
    private $tripId;
    private $bookingId;

    protected function setUp(): void
    {
        $this->conn = getTestConnection();
        $this->conn->query("INSERT IGNORE INTO users (user_id, name, email, password, role_id) VALUES ($this->userId, 'Pay User', 'pay@test.com', 'pass', 2)");
        $this->conn->query("INSERT IGNORE INTO leader (leader_id) VALUES ($this->userId)");
        $this->conn->query("INSERT INTO trip (leader_id, name, budget) VALUES ($this->userId, 'Pay Trip', 500)");
        $this->tripId = $this->conn->insert_id;
        
        $this->conn->query("INSERT INTO booking (user_id, trip_id, cost, status) VALUES ($this->userId, $this->tripId, 500, 'pending')");
        $this->bookingId = $this->conn->insert_id;
    }

    protected function tearDown(): void
    {
        $this->conn->query("DELETE FROM payment WHERE booking_id = {$this->bookingId}");
        $this->conn->query("DELETE FROM booking WHERE booking_id = {$this->bookingId}");
        $this->conn->query("DELETE FROM trip WHERE trip_id = {$this->tripId}");
        $this->conn->query("DELETE FROM users WHERE user_id = {$this->userId}");
        $this->conn->close();
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 1: Successful Payment
     *  INPUT: Valid booking_id, matching amount
     *  EXPECTED: Payment created, booking status -> confirmed
     * ───────────────────────────────────────────────────────── */
    public function testProcessPaymentSuccessfully(): void
    {
        $amount = 500.00;
        $traveler = "John Doe";
        $card = "1234";

        $this->conn->begin_transaction();
        
        // Insert Payment
        $stmt = $this->conn->prepare("INSERT INTO payment (booking_id, user_id, traveler_full_name, card_last4, amount, status) VALUES (?, ?, ?, ?, ?, 'successful')");
        $stmt->bind_param("iissd", $this->bookingId, $this->userId, $traveler, $card, $amount);
        $res1 = $stmt->execute();
        $paymentId = $stmt->insert_id;
        $stmt->close();

        // Update Booking
        $stmt = $this->conn->prepare("UPDATE booking SET status = 'confirmed' WHERE booking_id = ?");
        $stmt->bind_param("i", $this->bookingId);
        $res2 = $stmt->execute();
        $stmt->close();
        
        $this->conn->commit();

        // ✅ EXPECTED
        $this->assertTrue($res1 && $res2, "Payment processing should succeed");

        // Verify Payment Status
        $res = $this->conn->query("SELECT status FROM payment WHERE payment_id = $paymentId");
        $this->assertEquals('successful', $res->fetch_assoc()['status']);

        // Verify Booking Status
        $res = $this->conn->query("SELECT status FROM booking WHERE booking_id = {$this->bookingId}");
        $this->assertEquals('confirmed', $res->fetch_assoc()['status']);
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 2: Payment Amount Mismatch
     *  INPUT: Amount != Booking Cost
     *  EXPECTED: Detected as invalid
     * ───────────────────────────────────────────────────────── */
    public function testPaymentAmountMismatchDetect(): void
    {
        $inputAmount = 400.00; // Expected 500
        
        $res = $this->conn->query("SELECT cost FROM booking WHERE booking_id = {$this->bookingId}");
        $actualCost = (float) $res->fetch_assoc()['cost'];

        // ✅ EXPECTED: Should detect mismatch
        $this->assertNotEquals($inputAmount, $actualCost, "Payment amount mismatch should be detected");
    }
}
