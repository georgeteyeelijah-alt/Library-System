<?php
// api/staff_loans.php
// Returns every loan (past and current) with the book title and the
// student who made the reservation, newest first. This is what powers
// the staff dashboard.

header('Content-Type: application/json');
require_once __DIR__ . '/../db_connect.php';

$sql = "
    SELECT
        l.loan_id,
        b.title,
        CONCAT(m.first_name, ' ', m.last_name) AS student_name,
        m.email,
        l.loan_date,
        l.due_date,
        l.return_date
    FROM Loans l
    JOIN Books b   ON l.book_id = b.book_id
    JOIN Members m ON l.member_id = m.member_id
    ORDER BY l.loan_date DESC, l.loan_id DESC
";

$result = $conn->query($sql);

$loans = [];
while ($row = $result->fetch_assoc()) {
    $isReturned = $row['return_date'] !== null;
    $isOverdue  = !$isReturned && strtotime($row['due_date']) < strtotime(date('Y-m-d'));

    $loans[] = [
        "loan_id"      => (int) $row["loan_id"],
        "title"        => $row["title"],
        "student_name" => $row["student_name"],
        "email"        => $row["email"],
        "loan_date"    => $row["loan_date"],
        "due_date"     => $row["due_date"],
        "return_date"  => $row["return_date"],
        "status"       => $isReturned ? "returned" : ($isOverdue ? "overdue" : "active"),
    ];
}

echo json_encode($loans);

$conn->close();
?>
