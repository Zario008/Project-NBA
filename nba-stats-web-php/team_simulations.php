<?php
header('Content-Type: application/json');

$teamA = $_GET['teamA'] ?? null;
$teamB = $_GET['teamB'] ?? null;

if (!$teamA || !$teamB || $teamA === $teamB) {
    echo json_encode(['error' => 'Invalid teams selected.']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=nba_stats_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get team names
    $stmt = $pdo->prepare("SELECT team_id, name FROM teams WHERE team_id IN (?, ?)");
    $stmt->execute([$teamA, $teamB]);
    $teams = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [team_id => name]

    // Get past head-to-head games
    $stmt = $pdo->prepare("
        SELECT * FROM nba_game_summary 
        WHERE (home_team_id = :teamA AND away_team_id = :teamB) 
           OR (home_team_id = :teamB AND away_team_id = :teamA)
    ");
    $stmt->execute(['teamA' => $teamA, 'teamB' => $teamB]);
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all relevant game_ids
    $game_ids = array_column($games, 'game_id');
    $player_stats = [];

    if (count($game_ids) > 0) {
        $inQuery = implode(',', array_fill(0, count($game_ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM player_game_stats WHERE game_id IN ($inQuery)");
        $stmt->execute($game_ids);
        $player_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ Get player season averages with names + team + position
    $stmt = $pdo->query("
        SELECT 
            ps.player_id,
            ps.team_id,
            ps.avg_points, ps.avg_rebounds, ps.avg_assists, ps.avg_steals, ps.avg_blocks, ps.avg_turnovers,
            p.first_name, p.last_name, p.position
        FROM player_stats ps
        JOIN players p ON ps.player_id = p.player_id
    ");

    $averages_map = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['player_name'] = $row['first_name'] . ' ' . $row['last_name'];
        $averages_map[$row['player_id']] = $row;
    }

    echo json_encode([
        'games' => $games,
        'teams' => $teams,
        'player_stats' => $player_stats,
        'player_averages' => $averages_map
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}