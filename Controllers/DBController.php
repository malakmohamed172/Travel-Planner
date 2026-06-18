<?php

class DBController {

    private $dbHost = "localhost";
    private $dbUser = "root";
    private $dbPassword = "";
    private $dbName = "travel_planner1";

    public $connection;

    public function openConnection() {

        $this->connection = new mysqli(
            $this->dbHost,
            $this->dbUser,
            $this->dbPassword,
            $this->dbName
        );

        if ($this->connection->connect_error) {
            die("Connection Failed: " . $this->connection->connect_error);
        }

        return $this->connection;
    }

    public function closeConnection() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}


?>