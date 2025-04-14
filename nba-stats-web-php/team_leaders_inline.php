<?php
// Database connection
$host = 'localhost';
$db = 'nba_stats_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get all teams
$teamsStmt = $pdo->query("SELECT team_id, team_name FROM teams ORDER BY team_name");
$teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get top player in a category for a team
function getTopPlayer(PDO $pdo, $teamId, $statField) {
    $sql = "
        SELECT p.player_name, ps.$statField
        FROM player_stats ps
        JOIN players p ON ps.player_id = p.player_id
        WHERE ps.team_id = :team_id
        ORDER BY ps.$statField DESC
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['team_id' => $teamId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NBA Teams with Leaders</title>
    <style>
        body {
            background-color: #0E2240;
            color: white;
            font-family: Arial, sans-serif;
            padding: 30px;
        }
        .team-row {
            display: flex;
            align-items: center;
            background-color: #122B4C;
            margin-bottom: 12px;
            padding: 12px 20px;
            border-radius: 8px;
        }
        .team-logo {
            width: 40px;
            height: 40px;
            margin-right: 15px;
        }
        .team-name {
            font-weight: bold;
            font-size: 18px;
            margin-right: 25px;
            width: 180px;
        }
        .player-info {
            font-size: 14px;
            margin-right: 25px;
        }
    </style>
</head>
<body>
    <h1>🏀 NBA Teams with Stat Leaders</h1>

    <?php foreach ($teams as $team): ?>
        <?php
            $teamId = $team['team_id'];
            $teamName = $team['team_name'];
            $pointsLeader = getTopPlayer($pdo, $teamId, 'avg_points');
            $reboundsLeader = getTopPlayer($pdo, $teamId, 'avg_rebounds');
            $assistsLeader = getTopPlayer($pdo, $teamId, 'avg_assists');
            $logoPath = "images/teams/" . strtolower(str_replace([' ', '.'], ['-', ''], $teamName)) . ".png";
        ?>

        <div class="team-row">
            <img src="<?= $logoPath ?>" class="team-logo" alt="<?= $teamName ?>">
            <div class="team-name"><?= $teamName ?></div>

            <div class="player-info">
                <strong>Points:</strong> <?= $pointsLeader['player_name'] ?? 'N/A' ?> (<?= $pointsLeader['avg_points'] ?? '0.0' ?>)
            </div>
            <div class="player-info">
                <strong>Rebounds:</strong> <?= $reboundsLeader['player_name'] ?? 'N/A' ?> (<?= $reboundsLeader['avg_rebounds'] ?? '0.0' ?>)
            </div>
            <div class="player-info">
                <strong>Assists:</strong> <?= $assistsLeader['player_name'] ?? 'N/A' ?> (<?= $assistsLeader['avg_assists'] ?? '0.0' ?>)
            </div>
        </div>
    <?php endforeach; ?>
</body>
</html>
