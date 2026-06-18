<?php

class Expense {

    private $conn;

    public $expense_id;
    public $trip_id;
    public $amount;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {

        $stmt = $this->conn->prepare(
            "INSERT INTO expense (trip_id, amount) VALUES (?, ?)"
        );

        $stmt->bind_param("id", $this->trip_id, $this->amount);

        return $stmt->execute();
    }

    public function getByTrip() {

        $stmt = $this->conn->prepare(
            "SELECT * FROM expense WHERE trip_id = ?"
        );

        $stmt->bind_param("i", $this->trip_id);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function delete() {

        $stmt = $this->conn->prepare(
            "DELETE FROM expense WHERE expense_id = ?"
        );

        $stmt->bind_param("i", $this->expense_id);

        return $stmt->execute();
    }
}