<?php
header('Content-Type: application/json');
include('config.php');

$teamA_id = $_POST['teamA'] ?? null;
$teamB_id = $_POST['teamB'] ?? null;

if (!$teamA_id || !$teamB_id) {
    echo json_encode(['error' => 'Missing team IDs']);
    exit;
}

function getTeamInfo($conn, $team_id) {
    $stmt = $conn->prepare("SELECT team_id, name, wins, losses FROM teams WHERE team_id = ?");
    $stmt->bind_param("s", $team_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if (!$result) return null;
    $win_pct = ($result['wins'] + $result['losses']) > 0 ? $result['wins'] / ($result['wins'] + $result['losses']) : 0;
    return [
        'team_id' => $result['team_id'],
        'name' => $result['name'],
        'win_pct' => round($win_pct, 3)
    ];
}

function getTeamPlayers($conn, $team_id) {
    $stmt = $conn->prepare("
        SELECT p.first_name, p.last_name, p.team_id, ps.avg_points, ps.avg_rebounds, ps.avg_assists, ps.avg_steals, ps.avg_blocks, ps.avg_turnovers
        FROM players p
        JOIN player_stats ps ON p.player_id = ps.player_id
        WHERE p.team_id = ? AND ps.season_year = (SELECT MAX(season_year) FROM player_stats)
    ");
    $stmt->bind_param("s", $team_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$teamA_info = getTeamInfo($conn, $teamA_id);
$teamB_info = getTeamInfo($conn, $teamB_id);
$teamA_players = getTeamPlayers($conn, $teamA_id);
$teamB_players = getTeamPlayers($conn, $teamB_id);

echo json_encode([
    'teamA_info' => $teamA_info,
    'teamB_info' => $teamB_info,
    'teamA_players' => $teamA_players,
    'teamB_players' => $teamB_players
]);
?>