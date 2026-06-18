<?php
class Activity {

    private $conn;

    public $activity_id;
    public $user_id;
    public $itinerary_id;
    public $description;
    public $cost;
    public $start_date;
    public $status;

    public function __construct($db){
        $this->conn = $db;
    }

    // ➜ Propose Activity
    public function create(){
        $stmt = $this->conn->prepare("
            INSERT INTO activity (user_id, itinerary_id, description, cost, start_date, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");

        $stmt->bind_param(
            "iisds",
            $this->user_id,
            $this->itinerary_id,
            $this->description,
            $this->cost,
            $this->start_date
        );

        return $stmt->execute();
    }

    // ➜ Get Activities by itinerary
    public function getByItinerary(){
        $stmt = $this->conn->prepare("
            SELECT * FROM activity
            WHERE itinerary_id = ?
            ORDER BY activity_id DESC
        ");

        $stmt->bind_param("i", $this->itinerary_id);
        $stmt->execute();

        return $stmt->get_result();
    }

    // ➜ Approve
    public function approve(){
        $stmt = $this->conn->prepare("
            UPDATE activity SET status='approved'
            WHERE activity_id = ?
        ");

        $stmt->bind_param("i", $this->activity_id);
        return $stmt->execute();
    }

    // ➜ Reject
    public function reject(){
        $stmt = $this->conn->prepare("
            UPDATE activity SET status='rejected'
            WHERE activity_id = ?
        ");

        $stmt->bind_param("i", $this->activity_id);
        return $stmt->execute();
    }
}