<?php
header('Content-Type: application/json');
require 'config.php'; // Update path if needed

$season = 2024; // You can make this dynamic later

$query = "SELECT team_id, name, wins, losses FROM teams";
$result = $conn->query($query);
$trainingData = [];

while ($team = $result->fetch_assoc()) {
    $team_id = $team['team_id'];
    $win_pct = ($team['wins'] + $team['losses']) > 0 ? $team['wins'] / ($team['wins'] + $team['losses']) : 0;

    // Get top 5 player stats
    $stmt = $conn->prepare("
        SELECT avg_points, avg_rebounds, avg_assists, avg_steals, avg_blocks, avg_turnovers
        FROM players p
        JOIN player_stats ps ON p.player_id = ps.player_id
        WHERE p.team_id = ? AND ps.season_year = ?
        ORDER BY ps.avg_points DESC
        LIMIT 5
    ");
    $stmt->bind_param("ii", $team_id, $season);
    $stmt->execute();
    $playerResult = $stmt->get_result();

    $features = [];
    while ($row = $playerResult->fetch_assoc()) {
        foreach (['avg_points', 'avg_rebounds', 'avg_assists', 'avg_steals', 'avg_blocks', 'avg_turnovers'] as $stat) {
            $features[] = floatval($row[$stat]);
        }
    }

    // Pad to 30 if fewer players
    while (count($features) < 30) {
        $features[] = 0;
    }

    $input = array_merge([round($win_pct, 3)], $features);
    $label = array_sum(array_slice($features, 0, 30)); // Approximate score from top 5 avg_points

    $trainingData[] = [
        'input' => $input,
        'label' => round($label, 1)
    ];
}

echo json_encode($trainingData);