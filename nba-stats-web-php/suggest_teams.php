<?php
require_once 'db_connect.php';

if (!isset($_GET['term'])) {
    echo json_encode([]);
    exit;
}

$term = $_GET['term'] . '%';
$stmt = $pdo->prepare("SELECT DISTINCT team_name FROM games WHERE team_name LIKE ?");
$stmt->execute([$term]);
$teams = $stmt->fetchAll(PDO::FETCH_COLUMN);

$suggestions = array_map(function($team) {
    return ['label' => $team];
}, $teams);

echo json_encode($suggestions);
?>
