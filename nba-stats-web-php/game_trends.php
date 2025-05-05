<?php
// Database connection
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


$gamesPerPage = 5;

// Get the current page number from the URL, default to 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Calculate the OFFSET
$offset = ($page - 1) * $gamesPerPage;

// Fetch paginated game data
$query = "
    SELECT game_id, home_team_id, home_team_name, home_team_points, 
           away_team_id, away_team_name, away_team_points, game_date 
    FROM nba_game_summary 
    ORDER BY game_date DESC 
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $gamesPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$games = $stmt->fetchAll();

function getLogoPath($teamName) {
    $filename = strtolower(str_replace(' ', '_', $teamName)) . '.png';
    return "Images/teams/" . $filename;
}

// Output game cards
foreach ($games as $game): ?>
    <div class="game-card">
        <div class="game-row">
            <!-- Home Team -->
            <div class="team-side">
                <img src="<?= getLogoPath($game['home_team_name']) ?>" alt="<?= $game['home_team_name'] ?> Logo" class="team-logo">
                <div class="team-name"><?= htmlspecialchars($game['home_team_name']) ?></div>
            </div>

            <!-- Center: Score and Date -->
            <div class="center-info">
                <div class="score">
                    <?= $game['home_team_points'] ?> - <?= $game['away_team_points'] ?>
                </div>
                <div class="game-date">
                    <?= date("F j, Y", strtotime($game['game_date'])) ?>
                </div>
            </div>

            <!-- Away Team -->
            <div class="team-side">
                <img src="<?= getLogoPath($game['away_team_name']) ?>" alt="<?= $game['away_team_name'] ?> Logo" class="team-logo">
                <div class="team-name"><?= htmlspecialchars($game['away_team_name']) ?></div>
            </div>
        </div>
    </div>
<?php endforeach; ?>