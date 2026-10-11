<?php
// api/return.php
// Staff marks a loan as returned. Expects JSON POST: { loan_id }

header('Content-Type: application/json');
require_once __DIR__ . '/../db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$loan_id = isset($input['loan_id']) ? (int) $input['loan_id'] : 0;

if (!$loan_id) {
    http_response_code(400);
    echo json_encode(["error" => "Missing loan_id."]);
    exit;
}

$stmt = $conn->prepare("UPDATE Loans SET return_date = ?, sync_status = 'synced' WHERE loan_id = ? AND return_date IS NULL");
$today = date('Y-m-d');
$stmt->bind_param("si", $today, $loan_id);
$stmt->execute();

echo json_encode(["success" => true, "return_date" => $today]);

$stmt->close();
$conn->close();
?>
