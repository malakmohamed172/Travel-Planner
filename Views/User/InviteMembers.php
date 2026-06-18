<?php
require_once __DIR__ . '/../../Controllers/DBController.php';

class InviteMembers {

    private $conn;

    public function __construct() {
        $db = new DBController();
        $this->conn = $db->openConnection();
    }

    public function inviteMember($email, $booking_id, $inviter_id) {

        // check if user exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            return false; // user not found
        }

        $user = $result->fetch_assoc();
        $user_id = $user['id'];

        // insert invitation
        $stmt = $this->conn->prepare("
            INSERT INTO invitations (booking_id, inviter_id, invited_user_id, status)
            VALUES (?, ?, ?, 'pending')
        ");

        $stmt->bind_param("iii", $booking_id, $inviter_id, $user_id);

        return $stmt->execute();
    }
}