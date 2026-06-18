<?php

class Database {
    public function connect() {
        //return new PDO("mysql:host=localhost;dbname=project", "root", "");
        return new PDO("mysql:host=localhost;dbname=travel_planner1", "root", "");
    }
}