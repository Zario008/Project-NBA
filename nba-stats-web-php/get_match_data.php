<?php
header('Content-Type: application/json');

$host = 'localhost';
$db = 'nba_stats_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

$teamA = $_GET['teamA_id'];
$teamB = $_GET['teamB_id'];

// Step 1: Get all games where these two teams played each other
$sql = "
    SELECT 
        game_id,
        game_date,
        home_team_id,
        away_team_id,
        home_team_points,
        away_team_points
    FROM nba_game_summary
    WHERE 
        (home_team_id = ? AND away_team_id = ?) OR 
        (home_team_id = ? AND away_team_id = ?)
    ORDER BY game_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $teamA, $teamB, $teamB, $teamA);
$stmt->execute();
$result = $stmt->get_result();

$games = [];

while ($row = $result->fetch_assoc()) {
    $game_id = $row['game_id'];

    // Step 2: Get player stats for this game
    $playerStatsSQL = "
        SELECT 
            pgs.player_id,
            pgs.team_id,
            t.name AS team_name,
            pgs.points,
            pgs.rebounds,
            pgs.assists
        FROM player_game_stats pgs
        JOIN teams t ON t.team_id = pgs.team_id
        WHERE pgs.game_id = ?
    ";

    $stmt2 = $conn->prepare($playerStatsSQL);
    $stmt2->bind_param("s", $game_id);
    $stmt2->execute();
    $playerStatsResult = $stmt2->get_result();

    $playerStats = [];
    while ($ps = $playerStatsResult->fetch_assoc()) {
        $playerStats[] = $ps;
    }

    $row['player_stats'] = $playerStats;
    $games[] = $row;
}

echo json_encode(['games' => $games]);

$conn->close();
?>
