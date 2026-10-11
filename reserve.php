<?php
// api/reserve.php
// Handles a student reserving a book from the portal.
// Expects a JSON POST body: { book_id, first_name, last_name, email }
//
// Logic:
//   1. Find the Member by email, or create one if they're new.
//   2. Check the book actually still has a copy free (re-checked here,
//      not just trusted from the page, in case two people reserve at once).
//   3. Insert a Loans row — that IS the "reservation" in this schema.

header('Content-Type: application/json');
require_once __DIR__ . '/../db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);

$book_id    = isset($input['book_id']) ? (int) $input['book_id'] : 0;
$first_name = trim($input['first_name'] ?? '');
$last_name  = trim($input['last_name'] ?? '');
$email      = trim($input['email'] ?? '');

if (!$book_id || !$first_name || !$last_name || !$email) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["error" => "Please enter a valid email address."]);
    exit;
}

// --- Step 1: find or create the member ---
$stmt = $conn->prepare("SELECT member_id, branch_id FROM Members WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $member_id = $row['member_id'];
} else {
    // New student — look up the book's branch so the member is registered there
    $branchStmt = $conn->prepare("SELECT branch_id FROM Books WHERE book_id = ?");
    $branchStmt->bind_param("i", $book_id);
    $branchStmt->execute();
    $branchResult = $branchStmt->get_result();
    $branchRow = $branchResult->fetch_assoc();
    $branch_id = $branchRow ? $branchRow['branch_id'] : 1;
    $branchStmt->close();

    $insertMember = $conn->prepare(
        "INSERT INTO Members (first_name, last_name, email, member_type, branch_id) VALUES (?, ?, ?, 'student', ?)"
    );
    $insertMember->bind_param("sssi", $first_name, $last_name, $email, $branch_id);
    $insertMember->execute();
    $member_id = $insertMember->insert_id;
    $insertMember->close();
}
$stmt->close();

// --- Step 2: re-check availability ---
$availStmt = $conn->prepare("
    SELECT
        b.total_copies,
        (b.total_copies - COALESCE(active.active_count, 0)) AS available_copies
    FROM Books b
    LEFT JOIN (
        SELECT book_id, COUNT(*) AS active_count
        FROM Loans
        WHERE return_date IS NULL
        GROUP BY book_id
    ) active ON b.book_id = active.book_id
    WHERE b.book_id = ?
");
$availStmt->bind_param("i", $book_id);
$availStmt->execute();
$availResult = $availStmt->get_result();
$availRow = $availResult->fetch_assoc();
$availStmt->close();

if (!$availRow || $availRow['available_copies'] <= 0) {
    http_response_code(409);
    echo json_encode(["error" => "Sorry, no copies of this book are available right now."]);
    exit;
}

// --- Step 3: create the loan (the "reservation") ---
$loan_date = date('Y-m-d');
$due_date  = date('Y-m-d', strtotime('+14 days'));

$insertLoan = $conn->prepare(
    "INSERT INTO Loans (book_id, member_id, loan_date, due_date, return_date, sync_status) VALUES (?, ?, ?, ?, NULL, 'local_only')"
);
$insertLoan->bind_param("iiss", $book_id, $member_id, $loan_date, $due_date);
$insertLoan->execute();

echo json_encode([
    "success"   => true,
    "loan_id"   => $insertLoan->insert_id,
    "due_date"  => $due_date,
]);

$insertLoan->close();
$conn->close();
?>
