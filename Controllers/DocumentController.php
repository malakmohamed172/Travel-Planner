<?php

require_once __DIR__ . '/DBController.php';
session_start();

$db = new DBController();
$conn = $db->openConnection();

function isAjaxRequest(): bool {
    return (
        (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
        (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    );
}

function sendJsonResponse(int $statusCode, array $payload): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit();
}

function resolveDocumentTable(mysqli $conn): string {
    $checkDocument = $conn->query("SHOW TABLES LIKE 'document'");
    if ($checkDocument && $checkDocument->num_rows > 0) {
        return 'document';
    }

    $checkDocuments = $conn->query("SHOW TABLES LIKE 'documents'");
    if ($checkDocuments && $checkDocuments->num_rows > 0) {
        return 'documents';
    }

    return 'document';
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_id = $_SESSION['user']['id'] ?? null;

    if (!$user_id) {
        if (isAjaxRequest()) {
            sendJsonResponse(401, ['success' => false, 'message' => 'User not logged in']);
        }
        die("User not logged in");
    }

    /* =========================
       FILE UPLOAD
    ========================= */

    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        if (isAjaxRequest()) {
            sendJsonResponse(400, ['success' => false, 'message' => 'Please choose a valid file']);
        }
        die("Please choose a valid file");
    }

    $file = $_FILES['document'];

    $fileName = time() . "_" . basename($file['name']);
    $targetDir = "../uploads/";

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        $tableName = resolveDocumentTable($conn);
        $stmt = $conn->prepare("
            INSERT INTO {$tableName} (user_id, file_name, uploaded_at)
            VALUES (?, ?, NOW())
        ");

        if (!$stmt) {
            if (isAjaxRequest()) {
                sendJsonResponse(500, ['success' => false, 'message' => 'Database prepare failed']);
            }
            die("Database prepare failed");
        }

        $stmt->bind_param("is", $user_id, $fileName);
        $saved = $stmt->execute();

        if (!$saved) {
            if (file_exists($targetFile)) {
                unlink($targetFile);
            }
            if (isAjaxRequest()) {
                sendJsonResponse(500, ['success' => false, 'message' => 'Document could not be saved in database']);
            }
            die("Document could not be saved in database");
        }

        if (isAjaxRequest()) {
            sendJsonResponse(200, ['success' => true, 'message' => 'Document uploaded and saved successfully']);
        }
        header("Location: ../Views/User/uploadDocument.php?success=1");
        exit();

    } else {
        if (isAjaxRequest()) {
            sendJsonResponse(500, ['success' => false, 'message' => 'Upload failed']);
        }
        echo "Upload failed";
    }
}


?>