<?php
header('Content-Type: application/json');

// Replace this with your own DB connection setup
$pdo = new PDO("mysql:host=localhost;dbname=nba_stats_db", "root", "");

// Get selected team IDs
$teamA = $_GET['teamA'] ?? '';
$teamB = $_GET['teamB'] ?? '';

if (!$teamA || !$teamB || $teamA === $teamB) {
    echo json_encode(['error' => 'Invalid teams']);
    exit;
}

// Get team names for display
$teams = [$teamA, $teamB];
$teamStmt = $pdo->prepare("SELECT team_id, name FROM teams WHERE team_id IN (?, ?)");
$teamStmt->execute([$teamA, $teamB]);
$teamNames = [];
while ($row = $teamStmt->fetch(PDO::FETCH_ASSOC)) {
    $teamNames[$row['team_id']] = $row['name'];
}

// Get past game data between these teams
$gameStmt = $pdo->prepare("
    SELECT * FROM nba_game_summary 
    WHERE (home_team_id = ? AND away_team_id = ?) 
       OR (home_team_id = ? AND away_team_id = ?)
    LIMIT 10
");
$gameStmt->execute([$teamA, $teamB, $teamB, $teamA]);
$games = $gameStmt->fetchAll(PDO::FETCH_ASSOC);

// Get player averages for both teams
$playerStmt = $pdo->prepare("
    SELECT 
        p.player_id,
        p.team_id,
        CONCAT(p.first_name, ' ', p.last_name) AS player_name,
        p.position,
        AVG(ps.avg_points) AS points,
        AVG(ps.avg_rebounds) AS rebounds,
        AVG(ps.avg_assists) AS assists,
        AVG(ps.avg_steals) AS steals,
        AVG(ps.avg_blocks) AS blocks,
        AVG(ps.avg_turnovers) AS turnovers,
        AVG(ps.avg_minutes) AS minutes
    FROM players p
    JOIN player_stats ps ON p.player_id = ps.player_id
    WHERE p.team_id IN (?, ?)
      AND ps.season_year = 2024
      AND ps.season_type = 'REG'
    GROUP BY p.player_id
");
$playerStmt->execute([$teamA, $teamB]);

$playerAverages = [];
while ($row = $playerStmt->fetch(PDO::FETCH_ASSOC)) {
    $playerAverages[] = [
        'player_id' => $row['player_id'],
        'team_id' => $row['team_id'],
        'player_name' => $row['player_name'],
        'position' => $row['position'],
        'points' => floatval($row['points']),
        'rebounds' => floatval($row['rebounds']),
        'assists' => floatval($row['assists']),
        'steals' => floatval($row['steals']),
        'blocks' => floatval($row['blocks']),
        'turnovers' => floatval($row['turnovers']),
        'minutes' => floatval($row['minutes']),
    ];
}

// Return JSON response
echo json_encode([
    'teams' => $teamNames,
    'games' => $games,
    'player_averages' => $playerAverages
]);
