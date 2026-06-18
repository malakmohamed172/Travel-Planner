<?php
/**
 * ============================================================
 *  Unit Tests — ExpenseController & Model
 * ============================================================
 */

use PHPUnit\Framework\TestCase;

class ExpenseTest extends TestCase
{
    private $conn;
    private $userId = 9994;
    private $tripId;

    protected function setUp(): void
    {
        $this->conn = getTestConnection();
        $this->conn->query("INSERT IGNORE INTO users (user_id, name, email, password, role_id) VALUES ($this->userId, 'Exp User', 'exp@test.com', 'pass', 2)");
        $this->conn->query("INSERT IGNORE INTO leader (leader_id) VALUES ($this->userId)");
        $this->conn->query("INSERT INTO trip (leader_id, name, budget) VALUES ($this->userId, 'Exp Trip', 1000)");
        $this->tripId = $this->conn->insert_id;
    }

    protected function tearDown(): void
    {
        $this->conn->query("DELETE FROM expense WHERE trip_id = {$this->tripId}");
        $this->conn->query("DELETE FROM trip WHERE trip_id = {$this->tripId}");
        $this->conn->query("DELETE FROM users WHERE user_id = {$this->userId}");
        $this->conn->close();
    }

    public function testCreateExpense(): void
    {
        $expense = new Expense($this->conn);
        $expense->trip_id = $this->tripId;
        $expense->amount = 250.00;
        
        $result = $expense->create();
        
        $this->assertTrue($result);
        
        $res = $this->conn->query("SELECT amount FROM expense WHERE trip_id = {$this->tripId} ORDER BY expense_id DESC LIMIT 1");
        $this->assertEquals(250.00, (float) $res->fetch_assoc()['amount']);
    }

    public function testDeleteExpense(): void
    {
        $this->conn->query("INSERT INTO expense (trip_id, amount) VALUES ({$this->tripId}, 100)");
        $expId = $this->conn->insert_id;

        $expense = new Expense($this->conn);
        $expense->expense_id = $expId;
        $result = $expense->delete();

        $this->assertTrue($result);

        $res = $this->conn->query("SELECT expense_id FROM expense WHERE expense_id = $expId");
        $this->assertEquals(0, $res->num_rows);
    }
}
