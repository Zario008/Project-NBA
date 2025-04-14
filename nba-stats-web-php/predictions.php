<?php
header('Content-Type: application/json');
include 'db_connect.php';

$player_id = $_GET['player_id'] ?? '';
$count = intval($_GET['count']) ?? 5;

if (!$player_id) {
    echo json_encode(["error" => "Missing player_id"]);
    exit;
}

// Step 1: Get the latest N games for the player
$count = (int)$count; // cast explicitly to integer for safety

$stmt = $pdo->prepare("
    SELECT points, rebounds, assists, steals, blocks, turnovers
    FROM player_game_stats
    WHERE player_id = ? AND minutes_played > 0
    ORDER BY id DESC
    LIMIT $count
");
$stmt->execute([$player_id]);

$games = $stmt->fetchAll();

if (count($games) === 0) {
    echo json_encode(["error" => "No recent games found"]);
    exit;
}

// Step 2: Calculate average stats over those games
$totals = [
    'points' => 0,
    'rebounds' => 0,
    'assists' => 0,
    'steals' => 0,
    'blocks' => 0,
    'turnovers' => 0
];

foreach ($games as $game) {
    foreach ($totals as $stat => $_) {
        $totals[$stat] += $game[$stat];
    }
}

$averages = array_map(function($val) use ($count) {
    return round($val / $count, 2);
}, $totals);

// Output JSON for TensorFlow.js to use
echo json_encode($averages);