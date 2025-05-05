<?php
// Include your database connection code here
include 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$playerName = $data['playerName'];

$suggestions = getPlayerSuggestions($conn, $playerName);

echo json_encode(['suggestions' => $suggestions]);

function getPlayerSuggestions($conn, $playerName) {
    $stmt = $conn->prepare("SELECT name FROM players WHERE name LIKE ? LIMIT 5");
    $searchTerm = '%' . $playerName . '%';
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row['name'];
    }
    return $suggestions;
}
?>
