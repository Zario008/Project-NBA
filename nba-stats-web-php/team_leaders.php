<?php
// Connect to the database
$host = 'localhost';
$db   = 'nba_stats_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch all teams dynamically
$teams = $pdo->query("SELECT team_id, name FROM teams")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch top players for each category in a single query
$categories = [
    'avg_points'   => 'Points',
    'avg_rebounds' => 'Rebounds',
    'avg_assists'  => 'Assists'
];

$query = "
    SELECT 
        ps.team_id, ps.player_id, players.first_name, players.last_name,
        ps.avg_points, ps.avg_rebounds, ps.avg_assists
    FROM player_stats ps
    JOIN players ON ps.player_id = players.player_id
    WHERE ps.season_year = 2024 AND ps.season_type = 'REG'
";

$stats = $pdo->query($query)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NBA Game Trends</title>
    <style>
        body {
            background-color: #1D428A;
            color: white;
            font-family: Arial, sans-serif;
        }
        .team-box {
            background-color: #0E2240;
            border: 1px solid orange;
            border-radius: 8px;
            margin: 20px auto;
            width: 80%;
            padding: 15px;
        }
        h1, h2 { color: #FDB927; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #FDB927;
            text-align: center;
        }
        th {
            background-color: #FDB927;
            color: #0E2240;
        }
    </style>
</head>
<body>

<?php foreach ($teams as $teamId => $teamName): ?>
    <div class="team-box">
        <h2><?php echo htmlspecialchars($teamName); ?></h2>

        <table>
            <tr>
                <th>Category</th>
                <th>Player Name</th>
                <th>Stat</th>
            </tr>
            <?php if (isset($stats[$teamId])): ?>
                <?php 
                foreach ($categories as $column => $label):
                    $leader = array_reduce($stats[$teamId], function ($top, $player) use ($column) {
                        return ($top === null || $player[$column] > $top[$column]) ? $player : $top;
                    }, null);
                ?>
                    <tr>
                        <td><?php echo $label; ?></td>
                        <td><?php echo htmlspecialchars($leader['first_name'] . ' ' . $leader['last_name'] ?? 'N/A'); ?></td>
                        <td><?php echo $leader[$column] ?? '0'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No data available</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
<?php endforeach; ?>

</body>
</html>