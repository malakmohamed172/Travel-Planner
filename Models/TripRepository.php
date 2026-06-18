<?php

require_once __DIR__ . '/DBController.php';

class TripRepository {

    private $db;

    public function __construct() {
        $this->db = new DBController();
        $this->db->openConnection();
    }

    public function getAllTrips() {
        return $this->db->connection->query(
            "SELECT * FROM trip ORDER BY trip_id DESC"
        );
    }
}