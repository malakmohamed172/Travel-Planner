<?php
/**
 * ============================================================
 *  Unit Tests — DBController
 * ============================================================
 *  Tests for database connection open / close functionality.
 * ============================================================
 */

use PHPUnit\Framework\TestCase;

class DBControllerTest extends TestCase
{
    /* ─────────────────────────────────────────────────────────
     *  TEST 1: openConnection() returns a valid mysqli object
     * ───────────────────────────────────────────────────────── */
    public function testOpenConnectionReturnsValidMysqli(): void
    {
        $db = new DBController();
        $conn = $db->openConnection();

        // ✅ EXPECTED: Connection object is an instance of mysqli
        $this->assertInstanceOf(mysqli::class, $conn);
        // ✅ EXPECTED: No connection error
        $this->assertNull($conn->connect_error);

        $db->closeConnection();
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 2: openConnection() returns usable connection
     * ───────────────────────────────────────────────────────── */
    public function testConnectionCanExecuteQuery(): void
    {
        $db = new DBController();
        $conn = $db->openConnection();

        // ✅ EXPECTED: A simple SELECT query should succeed
        $result = $conn->query("SELECT 1 AS test_value");
        $this->assertNotFalse($result);

        $row = $result->fetch_assoc();
        // ✅ EXPECTED: test_value = "1"
        $this->assertEquals('1', $row['test_value']);

        $db->closeConnection();
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 3: closeConnection() closes without error
     * ───────────────────────────────────────────────────────── */
    public function testCloseConnectionDoesNotThrow(): void
    {
        $db = new DBController();
        $db->openConnection();

        // ✅ EXPECTED: No exception thrown on close
        $db->closeConnection();
        $this->assertTrue(true); // reached here = pass
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 4: closeConnection() on null connection is safe
     * ───────────────────────────────────────────────────────── */
    public function testCloseConnectionWithoutOpenIsSafe(): void
    {
        $db = new DBController();

        // ✅ EXPECTED: Calling close without open should not throw
        $db->closeConnection();
        $this->assertTrue(true);
    }

    /* ─────────────────────────────────────────────────────────
     *  TEST 5: connection property is accessible after open
     * ───────────────────────────────────────────────────────── */
    public function testConnectionPropertyIsSetAfterOpen(): void
    {
        $db = new DBController();
        $db->openConnection();

        // ✅ EXPECTED: The public $connection property is not null
        $this->assertNotNull($db->connection);
        $this->assertInstanceOf(mysqli::class, $db->connection);

        $db->closeConnection();
    }
}
