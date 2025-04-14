<?php
header('Content-Type: application/json');
include 'db_connect.php';

$term = $_GET['term'] ?? '';

if (!$term) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT player_id, first_name, last_name 
    FROM players 
    WHERE first_name LIKE ? OR last_name LIKE ? 
    LIMIT 10
");
$search = "%$term%";
$stmt->execute([$search, $search]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$suggestions = array_map(function($player) {
    return [
        'label' => $player['first_name'] . ' ' . $player['last_name'],
        'value' => $player['player_id']
    ];
}, $results);

echo json_encode($suggestions);
