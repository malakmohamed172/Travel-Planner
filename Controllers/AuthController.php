<?php

require_once '../../Models/users.php';
require_once '../../Controllers/DBController.php';

class AuthController {

    private $db;

    public function __construct() {
        $this->db = new DBController();
        $this->db->openConnection();
    }

    // ================= LOGIN =================
    public function signin(User $user) {

        $email = strtolower(trim($user->email));
        $password = $user->password;

        // SECURE prepared statement
        $stmt = $this->db->connection->prepare(
            "SELECT user_id, name, email, password, role_id FROM users WHERE email = ? LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {

            $row = $result->fetch_assoc();

            // Verify password (hashed OR plain for old data)
            if (password_verify($password, $row['password']) || $password === $row['password']) {

                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                // Secure session fixation protection
                session_regenerate_id(true);

                $_SESSION['user'] = [
                    "id"      => $row['user_id'],
                    "name"    => $row['name'],
                    "email"   => $row['email'],
                    "role_id" => $row['role_id']
                ];

                return true;
            }
        }

        return false;
    }

    // ================= REGISTER =================
    public function register(User $user) {

        $name = trim($user->name);
        $email = strtolower(trim($user->email));
        $rawPassword = (string)$user->password;

        if (strlen($rawPassword) < 8) {
            return "weak_password";
        }

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "error";
        }

        $password = password_hash($rawPassword, PASSWORD_DEFAULT);

        // Check if email exists
        $stmt = $this->db->connection->prepare(
            "SELECT user_id FROM users WHERE email = ? LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            return "exists";
        }

        $stmt->close();

        // Insert new user (default role_id = 3: member)
        $stmt = $this->db->connection->prepare(
            "INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, 3)"
        );

        $stmt->bind_param("sss", $name, $email, $password);

        if ($stmt->execute()) {
            return "success";
        }

        return "error";
    }

    // ================= LOGOUT =================
    public function logout() {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Unset all session data
        $_SESSION = [];

        // Destroy session
        session_destroy();

        return true;
    }
    public function getUserByEmail($email) {

    $db = new DBController();
    $conn = $db->openConnection();

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}
}

?>
