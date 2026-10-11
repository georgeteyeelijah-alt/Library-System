<?php


$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";              
$DB_NAME = "library_db";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

$conn->set_charset("utf8mb4");
?>
