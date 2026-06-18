<?php
/**
 * ============================================================
 *  Unit Tests — ActivityController & Model
 * ============================================================
 *  Tests for: Propose, Approve, Reject Activity
 * ============================================================
 */

use PHPUnit\Framework\TestCase;

class ActivityTest extends TestCase
{
    private $conn;
    private $userId = 9993;
    private $tripId;
    private $itineraryId;

    protected function setUp(): void
    {
        $this->conn = getTestConnection();
        $this->conn->query("INSERT IGNORE INTO users (user_id, name, email, password, role_id) VALUES ($this->userId, 'Act User', 'act@test.com', 'pass', 2)");
        $this->conn->query("INSERT IGNORE INTO leader (leader_id) VALUES ($this->userId)");
        $this->conn->query("INSERT INTO trip (leader_id, name, budget) VALUES ($this->userId, 'Act Trip', 500)");
        $this->tripId = $this->conn->insert_id;

        $this->conn->query("INSERT INTO itinerary (trip_id, day_number, itinerary_date, title) VALUES ($this->tripId, 1, '2026-01-01', 'Day 1')");
        $this->itineraryId = $this->conn->insert_id;
    }

    protected function tearDown(): void
    {
        $this->conn->query("DELETE FROM activity WHERE user_id = {$this->userId}");
        $this->conn->query("DELETE FROM itinerary WHERE trip_id = {$this->tripId}");
        $this->conn->query("DELETE FROM trip WHERE trip_id = {$this->tripId}");
        $this->conn->query("DELETE FROM users WHERE user_id = {$this->userId}");
        $this->conn->close();
    }

    public function testCreateActivity(): void
    {
        $activity = new Activity($this->conn);
        $activity->user_id = $this->userId;
        $activity->itinerary_id = $this->itineraryId;
        $activity->description = "Visit Museum";
        $activity->cost = 50.00;
        $activity->start_date = "2026-01-01 10:00:00";

        $result = $activity->create();
        
        $this->assertTrue($result);
        
        $res = $this->conn->query("SELECT status FROM activity WHERE itinerary_id = {$this->itineraryId} ORDER BY activity_id DESC LIMIT 1");
        $this->assertEquals('pending', $res->fetch_assoc()['status']);
    }

    public function testApproveActivity(): void
    {
        $this->conn->query("INSERT INTO activity (user_id, itinerary_id, description, status) VALUES ($this->userId, $this->itineraryId, 'Test', 'pending')");
        $actId = $this->conn->insert_id;

        $activity = new Activity($this->conn);
        $activity->activity_id = $actId;
        $result = $activity->approve();

        $this->assertTrue($result);
        
        $res = $this->conn->query("SELECT status FROM activity WHERE activity_id = $actId");
        $this->assertEquals('approved', $res->fetch_assoc()['status']);
    }

    public function testRejectActivity(): void
    {
        $this->conn->query("INSERT INTO activity (user_id, itinerary_id, description, status) VALUES ($this->userId, $this->itineraryId, 'Test', 'pending')");
        $actId = $this->conn->insert_id;

        $activity = new Activity($this->conn);
        $activity->activity_id = $actId;
        $result = $activity->reject();

        $this->assertTrue($result);
        
        $res = $this->conn->query("SELECT status FROM activity WHERE activity_id = $actId");
        $this->assertEquals('rejected', $res->fetch_assoc()['status']);
    }
}
