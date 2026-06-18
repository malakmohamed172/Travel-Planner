<?php

class EmergencyContact {

    private $conn;

    public $trip_id;
    public $name;
    public $phone;

    public function __construct($db){
        $this->conn = $db;
    }

    public function create(){

        $stmt = $this->conn->prepare("
            INSERT INTO emergency_contact (trip_id, name, phone)
            VALUES (?, ?, ?)
        ");

        $stmt->bind_param("iss",
            $this->trip_id,
            $this->name,
            $this->phone
        );

        return $stmt->execute();
    }

    public function getByTrip(){

        $stmt = $this->conn->prepare("
            SELECT * FROM emergency_contact
            WHERE trip_id = ?
        ");

        $stmt->bind_param("i", $this->trip_id);
        $stmt->execute();

        return $stmt->get_result();
    }
}