<?php


header('Content-Type: application/json');
require_once __DIR__ . '/../db_connect.php';

$sql = "
    SELECT
        b.book_id,
        b.title,
        CONCAT(a.first_name, ' ', a.last_name) AS author,
        c.name AS category,
        br.name AS branch,
        b.total_copies,
        (b.total_copies - COALESCE(active.active_count, 0)) AS available_copies
    FROM Books b
    LEFT JOIN Authors a     ON b.author_id = a.author_id
    LEFT JOIN Categories c  ON b.category_id = c.category_id
    LEFT JOIN Branches br   ON b.branch_id = br.branch_id
    LEFT JOIN (
        SELECT book_id, COUNT(*) AS active_count
        FROM Loans
        WHERE return_date IS NULL
        GROUP BY book_id
    ) active ON b.book_id = active.book_id
";

$conditions = [];
$params = [];
$types = "";


if (!empty($_GET['q'])) {
    $conditions[] = "(b.title LIKE ? OR CONCAT(a.first_name, ' ', a.last_name) LIKE ?)";
    $like = "%" . $_GET['q'] . "%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

if (!empty($_GET['category'])) {
    $conditions[] = "c.name = ?";
    $params[] = $_GET['category'];
    $types .= "s";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY b.title ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$books = [];
while ($row = $result->fetch_assoc()) {
    $books[] = [
        "book_id"          => (int) $row["book_id"],
        "title"            => $row["title"],
        "author"           => $row["author"] ?? "Unknown",
        "Subject"          => $row["Subject"] ?? "Uncategorized",
        "branch"           => $row["branch"],
        "total_copies"     => (int) $row["total_copies"],
        "available_copies" => (int) $row["available_copies"],
        "available"        => ((int) $row["available_copies"] > 0) ? 1 : 0,
    ];
}

echo json_encode($books);

$stmt->close();
$conn->close();
?>
