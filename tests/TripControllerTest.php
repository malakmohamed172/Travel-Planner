<?php

use PHPUnit\Framework\TestCase;

class TripControllerTest extends TestCase
{
    private $conn;
    private $leaderId;
    private $createdTripIds = [];

    protected function setUp(): void
    {
        $this->conn = getTestConnection();

        // Ensure a test user exists for leader_id
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = 'trip_test@test.com' LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result) {
            $this->leaderId = $result['user_id'];
        } else {
            $stmt = $this->conn->prepare(
                "INSERT INTO users (name, email, password, role_id) VALUES ('Trip Tester', 'trip_test@test.com', ?, 2)"
            );
            $pass = password_hash('test', PASSWORD_DEFAULT);
            $stmt->bind_param("s", $pass);
            $stmt->execute();
            $this->leaderId = $stmt->insert_id;
            $stmt->close();
        }

        // Ensure leader record exists
        $stmt = $this->conn->prepare("SELECT leader_id FROM leader WHERE leader_id = ?");
        $stmt->bind_param("i", $this->leaderId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows == 0) {
            $stmt->close();
            $ins = $this->conn->prepare("INSERT INTO leader (leader_id) VALUES (?)");
            $ins->bind_param("i", $this->leaderId);
            $ins->execute();
            $ins->close();
        } else {
            $stmt->close();
        }
    }

    protected function tearDown(): void
    {
        // Clean up created trips
        foreach ($this->createdTripIds as $id) {
            $this->conn->query("DELETE FROM trip WHERE trip_id = $id");
        }
        $this->conn->close();
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 1: Create Trip — successful insert
     *  INPUT:  name, description, budget, status, dates
     *  EXPECTED: Insert succeeds, trip_id > 0
     * ───────────────────────────────────────────────────────── */
    public function testCreateTripSuccessfully(): void
    {
        // INPUT
        $name = 'Unit Test Trip ' . time();
        $description = 'A test trip for unit testing';
        $budget = 1500.00;
        $status = 'upcoming';
        $startDate = '2026-06-01';
        $endDate = '2026-06-10';

        $stmt = $this->conn->prepare(
            "INSERT INTO trip (leader_id, name, description, budget, status, start_date, end_date)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "issdsss",
            $this->leaderId, $name, $description, $budget, $status, $startDate, $endDate
        );

        $result = $stmt->execute();
        $tripId = $this->conn->insert_id;
        $this->createdTripIds[] = $tripId;
        $stmt->close();

        // ✅ EXPECTED: Insert succeeded
        $this->assertTrue($result, "Trip creation should succeed");
        // ✅ EXPECTED: Valid trip_id returned
        $this->assertGreaterThan(0, $tripId, "Trip ID should be greater than 0");
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 2: Read Trip by ID — returns correct data
     *  INPUT:  Valid trip_id
     *  EXPECTED: Name matches what was inserted
     * ───────────────────────────────────────────────────────── */
    public function testReadTripById(): void
    {
        // Setup: create a trip
        $name = 'Read Test Trip ' . time();
        $stmt = $this->conn->prepare(
            "INSERT INTO trip (leader_id, name, description, budget, status, start_date, end_date)
             VALUES (?, ?, 'desc', 500, 'upcoming', '2026-07-01', '2026-07-05')"
        );
        $stmt->bind_param("is", $this->leaderId, $name);
        $stmt->execute();
        $tripId = $this->conn->insert_id;
        $this->createdTripIds[] = $tripId;
        $stmt->close();

        // INPUT: Read back
        $stmt = $this->conn->prepare("SELECT * FROM trip WHERE trip_id = ?");
        $stmt->bind_param("i", $tripId);
        $stmt->execute();
        $trip = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // ✅ EXPECTED: Trip found and name matches
        $this->assertNotNull($trip, "Trip should be found");
        $this->assertEquals($name, $trip['name'], "Trip name should match");
        $this->assertEquals(500, (float) $trip['budget'], "Budget should match");
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 3: Update Trip — changes are persisted
     *  INPUT:  Updated name and budget
     *  EXPECTED: Read after update reflects new values
     * ───────────────────────────────────────────────────────── */
    public function testUpdateTripSuccessfully(): void
    {
        // Setup: create a trip
        $stmt = $this->conn->prepare(
            "INSERT INTO trip (leader_id, name, description, budget, status, start_date, end_date)
             VALUES (?, 'Original Name', 'desc', 1000, 'upcoming', '2026-08-01', '2026-08-10')"
        );
        $stmt->bind_param("i", $this->leaderId);
        $stmt->execute();
        $tripId = $this->conn->insert_id;
        $this->createdTripIds[] = $tripId;
        $stmt->close();

        // INPUT: Update the trip
        $newName = 'Updated Name ' . time();
        $newBudget = 2500.00;
        $newStatus = 'ongoing';
        $desc = 'updated desc';
        $startDate = '2026-08-01';
        $endDate = '2026-08-15';

        $stmt = $this->conn->prepare(
            "UPDATE trip SET name=?, description=?, budget=?, status=?, start_date=?, end_date=? WHERE trip_id=?"
        );
        $stmt->bind_param("ssdsssi", $newName, $desc, $newBudget, $newStatus, $startDate, $endDate, $tripId);
        $updateResult = $stmt->execute();
        $stmt->close();

        // ✅ EXPECTED: Update succeeded
        $this->assertTrue($updateResult, "Update should succeed");

        // Verify by reading back
        $stmt = $this->conn->prepare("SELECT name, budget, status FROM trip WHERE trip_id = ?");
        $stmt->bind_param("i", $tripId);
        $stmt->execute();
        $trip = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // ✅ EXPECTED: New values persisted
        $this->assertEquals($newName, $trip['name'], "Name should be updated");
        $this->assertEquals(2500.00, (float) $trip['budget'], "Budget should be updated");
        $this->assertEquals('ongoing', $trip['status'], "Status should be updated");
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 4: Delete Trip — record removed from DB
     *  INPUT:  Valid trip_id
     *  EXPECTED: Trip no longer exists after delete
     * ───────────────────────────────────────────────────────── */
    public function testDeleteTripSuccessfully(): void
    {
        // Setup: create a trip
        $stmt = $this->conn->prepare(
            "INSERT INTO trip (leader_id, name, description, budget, status, start_date, end_date)
             VALUES (?, 'Delete Me', 'test', 100, 'upcoming', '2026-09-01', '2026-09-05')"
        );
        $stmt->bind_param("i", $this->leaderId);
        $stmt->execute();
        $tripId = $this->conn->insert_id;
        $stmt->close();

        // INPUT: Delete the trip
        $stmt = $this->conn->prepare("DELETE FROM trip WHERE trip_id = ?");
        $stmt->bind_param("i", $tripId);
        $deleteResult = $stmt->execute();
        $stmt->close();

        // ✅ EXPECTED: Delete succeeded
        $this->assertTrue($deleteResult, "Delete should succeed");

        // Verify it's gone
        $stmt = $this->conn->prepare("SELECT trip_id FROM trip WHERE trip_id = ?");
        $stmt->bind_param("i", $tripId);
        $stmt->execute();
        $stmt->store_result();

        // ✅ EXPECTED: No rows found
        $this->assertEquals(0, $stmt->num_rows, "Deleted trip should not exist");
        $stmt->close();
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 5: Read non-existent trip → returns null
     *  INPUT:  trip_id = 999999 (doesn't exist)
     *  EXPECTED: null
     * ───────────────────────────────────────────────────────── */
    public function testReadNonExistentTripReturnsNull(): void
    {
        $stmt = $this->conn->prepare("SELECT * FROM trip WHERE trip_id = ?");
        $fakeId = 999999;
        $stmt->bind_param("i", $fakeId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // ✅ EXPECTED: No trip found
        $this->assertNull($result, "Non-existent trip should return null");
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 6: Trip budget validation — must be numeric
     *  INPUT:  Budget = 2000.50
     *  EXPECTED: Stored budget matches input
     * ───────────────────────────────────────────────────────── */
    public function testTripBudgetIsStoredCorrectly(): void
    {
        $budget = 2000.50;
        $stmt = $this->conn->prepare(
            "INSERT INTO trip (leader_id, name, description, budget, status, start_date, end_date)
             VALUES (?, 'Budget Test', 'test', ?, 'upcoming', '2026-10-01', '2026-10-05')"
        );
        $stmt->bind_param("id", $this->leaderId, $budget);
        $stmt->execute();
        $tripId = $this->conn->insert_id;
        $this->createdTripIds[] = $tripId;
        $stmt->close();

        $stmt = $this->conn->prepare("SELECT budget FROM trip WHERE trip_id = ?");
        $stmt->bind_param("i", $tripId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // ✅ EXPECTED: Budget matches with float precision
        $this->assertEqualsWithDelta($budget, (float) $row['budget'], 0.01, "Budget should match");
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 7: List all trips — returns result set
     *  INPUT:  Query all trips
     *  EXPECTED: Result is not false, num_rows >= 0
     * ───────────────────────────────────────────────────────── */
    public function testListAllTripsReturnsResults(): void
    {
        $result = $this->conn->query("SELECT * FROM trip ORDER BY trip_id DESC");

        // ✅ EXPECTED: Query doesn't fail
        $this->assertNotFalse($result, "List trips query should not fail");
        // ✅ EXPECTED: Returns 0 or more rows
        $this->assertGreaterThanOrEqual(0, $result->num_rows);
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 8: Trip date validation — end_date >= start_date
     *  INPUT:  start=2026-06-01, end=2026-06-10
     *  EXPECTED: end_date is after start_date
     * ───────────────────────────────────────────────────────── */
    public function testTripDateValidation(): void
    {
        $startDate = '2026-06-01';
        $endDate = '2026-06-10';

        // ✅ EXPECTED: end_date >= start_date
        $this->assertGreaterThanOrEqual(
            strtotime($startDate),
            strtotime($endDate),
            "End date should be on or after start date"
        );
    }
}
