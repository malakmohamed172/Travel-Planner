<?php

require_once __DIR__ . '/DBController.php';
session_start();

$db = new DBController();
$conn = $db->openConnection();

function requireLogin() {
    if (!isset($_SESSION['user']['id'])) {
        header("Location: ../Views/Auth/signin.php");
        exit();
    }
}

function currentUserId() {
    return (int)$_SESSION['user']['id'];
}

function currentUserRole() {
    return (int)($_SESSION['user']['role_id'] ?? 0);
}

function canManageTrip($conn, $trip_id) {
    if (currentUserRole() === 1) {
        return true;
    }

    $stmt = $conn->prepare("SELECT leader_id FROM trip WHERE trip_id = ? LIMIT 1");
    $stmt->bind_param("i", $trip_id);
    $stmt->execute();
    $trip = $stmt->get_result()->fetch_assoc();

    return $trip && (int)$trip['leader_id'] === currentUserId();
}

function ensureItineraryTablesExist($conn) {
    $columnExists = function ($tableName, $columnName) use ($conn) {
        $tableName = $conn->real_escape_string($tableName);
        $columnName = $conn->real_escape_string($columnName);
        $query = "
            SELECT 1
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$tableName}'
              AND COLUMN_NAME = '{$columnName}'
            LIMIT 1
        ";
        $result = $conn->query($query);
        return $result && $result->num_rows > 0;
    };

    $createItineraryTable = "
        CREATE TABLE IF NOT EXISTS itinerary (
            itinerary_id INT AUTO_INCREMENT PRIMARY KEY,
            trip_id INT NOT NULL,
            day_number INT NOT NULL,
            itinerary_date DATE NOT NULL,
            title VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_itinerary_trip
                FOREIGN KEY (trip_id) REFERENCES trip(trip_id)
                ON DELETE CASCADE
        )
    ";

    $createStopTable = "
        CREATE TABLE IF NOT EXISTS itinerary_stop (
            stop_id INT AUTO_INCREMENT PRIMARY KEY,
            itinerary_id INT NOT NULL,
            stop_order INT NOT NULL,
            stop_name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_stop_itinerary
                FOREIGN KEY (itinerary_id) REFERENCES itinerary(itinerary_id)
                ON DELETE CASCADE
        )
    ";

    if (!$conn->query($createItineraryTable)) {
        throw new Exception("Failed to create itinerary table: " . $conn->error);
    }

    if (!$columnExists('itinerary', 'day_number')) {
        if (!$conn->query("ALTER TABLE itinerary ADD COLUMN day_number INT NOT NULL DEFAULT 1 AFTER trip_id")) {
            throw new Exception("Failed to add day_number column: " . $conn->error);
        }
    }

    if (!$columnExists('itinerary', 'itinerary_date')) {
        if ($columnExists('itinerary', 'date')) {
            if (!$conn->query("ALTER TABLE itinerary ADD COLUMN itinerary_date DATE NULL AFTER day_number")) {
                throw new Exception("Failed to add itinerary_date column: " . $conn->error);
            }
            if (!$conn->query("UPDATE itinerary SET itinerary_date = `date` WHERE itinerary_date IS NULL")) {
                throw new Exception("Failed to backfill itinerary_date: " . $conn->error);
            }
            if (!$conn->query("ALTER TABLE itinerary MODIFY itinerary_date DATE NOT NULL")) {
                throw new Exception("Failed to enforce itinerary_date NOT NULL: " . $conn->error);
            }
        } else {
            if (!$conn->query("ALTER TABLE itinerary ADD COLUMN itinerary_date DATE NOT NULL AFTER day_number")) {
                throw new Exception("Failed to add itinerary_date column: " . $conn->error);
            }
        }
    }

    if (!$columnExists('itinerary', 'title')) {
        if ($columnExists('itinerary', 'destination')) {
            if (!$conn->query("ALTER TABLE itinerary ADD COLUMN title VARCHAR(255) NULL AFTER itinerary_date")) {
                throw new Exception("Failed to add title column: " . $conn->error);
            }
            if (!$conn->query("UPDATE itinerary SET title = destination WHERE title IS NULL OR title = ''")) {
                throw new Exception("Failed to backfill title: " . $conn->error);
            }
            if (!$conn->query("ALTER TABLE itinerary MODIFY title VARCHAR(255) NOT NULL")) {
                throw new Exception("Failed to enforce title NOT NULL: " . $conn->error);
            }
        } else {
            if (!$conn->query("ALTER TABLE itinerary ADD COLUMN title VARCHAR(255) NOT NULL AFTER itinerary_date")) {
                throw new Exception("Failed to add title column: " . $conn->error);
            }
        }
    }

    if (!$conn->query($createStopTable)) {
        throw new Exception("Failed to create itinerary stop table: " . $conn->error);
    }
}

/* =========================
   CREATE TRIP
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['action'])) {
    requireLogin();

    if (!in_array(currentUserRole(), [1, 2], true)) {
        die("Unauthorized");
    }

    // ✅ get logged-in user
    $leader_id = currentUserId();

    if ((float)($_POST['budget'] ?? 0) < 0) {
        die("Budget cannot be negative");
    }

    // 🔥 ENSURE LEADER EXISTS (FIX FOREIGN KEY ERROR)
    $checkLeader = $conn->prepare("SELECT leader_id FROM leader WHERE leader_id = ?");
    $checkLeader->bind_param("i", $leader_id);
    $checkLeader->execute();
    $checkLeader->store_result();

    if ($checkLeader->num_rows == 0) {
        $insertLeader = $conn->prepare("INSERT INTO leader (leader_id) VALUES (?)");
        $insertLeader->bind_param("i", $leader_id);
        $insertLeader->execute();
    }

    try {
        ensureItineraryTablesExist($conn);
        $conn->begin_transaction();

        // ✅ prepare insert trip
        $stmt = $conn->prepare("
            INSERT INTO trip 
            (leader_id, name, description, budget, status, start_date, end_date)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "issdsss",
            $leader_id,
            $_POST['name'],
            $_POST['description'],
            $_POST['budget'],
            $_POST['status'],
            $_POST['start_date'],
            $_POST['end_date']
        );

        if (!$stmt->execute()) {
            throw new Exception("Error creating trip: " . $conn->error);
        }

        $trip_id = (int) $conn->insert_id;
        $itineraryDays = $_POST['itinerary'] ?? [];

        $insertItineraryStmt = $conn->prepare("
            INSERT INTO itinerary (trip_id, day_number, itinerary_date, title)
            VALUES (?, ?, ?, ?)
        ");
        $insertStopStmt = $conn->prepare("
            INSERT INTO itinerary_stop (itinerary_id, stop_order, stop_name)
            VALUES (?, ?, ?)
        ");

        foreach ($itineraryDays as $dayIndex => $dayData) {
            $itineraryDate = trim($dayData['date'] ?? '');
            $title = trim($dayData['title'] ?? '');
            $stops = $dayData['stops'] ?? [];

            if ($itineraryDate === '' || $title === '') {
                continue;
            }

            $dayNumber = $dayIndex + 1;
            $insertItineraryStmt->bind_param("iiss", $trip_id, $dayNumber, $itineraryDate, $title);

            if (!$insertItineraryStmt->execute()) {
                throw new Exception("Failed to save itinerary day: " . $conn->error);
            }

            $itineraryId = (int) $conn->insert_id;
            $stopOrder = 1;

            foreach ($stops as $stopName) {
                $stopName = trim($stopName);
                if ($stopName === '') {
                    continue;
                }

                $insertStopStmt->bind_param("iis", $itineraryId, $stopOrder, $stopName);
                if (!$insertStopStmt->execute()) {
                    throw new Exception("Failed to save itinerary stop: " . $conn->error);
                }
                $stopOrder++;
            }
        }

        $conn->commit();
        header("Location: ../Views/User/Trips/list.php?success=1");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die($e->getMessage());
    }
}

/* =========================
   DELETE TRIP
========================= */
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    requireLogin();

    $id = intval($_GET['id']);

    if (!canManageTrip($conn, $id)) {
        die("Unauthorized");
    }

    $stmt = $conn->prepare("DELETE FROM trip WHERE trip_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: ../Views/User/Trips/list.php?deleted=1");
        exit();
    } else {
        die("Error deleting trip");
    }
}

/* =========================
   UPDATE TRIP
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" 
    && isset($_POST['action']) 
    && $_POST['action'] === 'update') {
    requireLogin();

    $trip_id = (int)$_POST['id'];

    if (!canManageTrip($conn, $trip_id)) {
        die("Unauthorized");
    }

    if ((float)($_POST['budget'] ?? 0) < 0) {
        die("Budget cannot be negative");
    }

    $stmt = $conn->prepare("
        UPDATE trip 
        SET name=?, description=?, budget=?, status=?, start_date=?, end_date=?
        WHERE trip_id=?
    ");

    $stmt->bind_param(
        "ssdsssi",
        $_POST['name'],
        $_POST['description'],
        $_POST['budget'],
        $_POST['status'],
        $_POST['start_date'],
        $_POST['end_date'],
        $trip_id
    );

    if ($stmt->execute()) {
        header("Location: ../Views/User/Trips/list.php?updated=1");
        exit();
    } else {
        die("Error updating trip");
    }
}
?>
