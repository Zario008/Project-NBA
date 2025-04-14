<?php
set_time_limit(0);

$api_key = 'wX9ra0PeFiMdXqTxMP8jePlYk7qQOoUQdw8DEo99';
$access_level = 'trial'; // Change to "production" if needed
$language_code = 'en';
$format = 'json';
$season_year = 2024; // Current season
$season_type = 'REG'; // Regular season
$team_ids = [
    "583ec825-fb46-11e1-82cb-f4ce4684ea4c",
    "583ec87d-fb46-11e1-82cb-f4ce4684ea4c",
    "583ecefd-fb46-11e1-82cb-f4ce4684ea4c",
    "583ec5fd-fb46-11e1-82cb-f4ce4684ea4c",
    "583ec773-fb46-11e1-82cb-f4ce4684ea4c",
    "583eccfa-fb46-11e1-82cb-f4ce4684ea4c",
    "583ecdfb-fb46-11e1-82cb-f4ce4684ea4c",
    "583eca88-fb46-11e1-82cb-f4ce4684ea4c",
    "583ecb8f-fb46-11e1-82cb-f4ce4684ea4c",
    "583ecea6-fb46-11e1-82cb-f4ce4684ea4c",
    "583ec97e-fb46-11e1-82cb-f4ce4684ea4c",
    "583ece50-fb46-11e1-82cb-f4ce4684ea4c",
    "583ed0ac-fb46-11e1-82cb-f4ce4684ea4c",
    "583ec70e-fb46-11e1-82cb-f4ce4684ea4c",
    "583ecae2-fb46-11e1-82cb-f4ce4684ea4c",
    "583ed157-fb46-11e1-82cb-f4ce4684ea4c",
    "583ecf50-fb46-11e1-82cb-f4ce4684ea4c",
    "583ec9d6-fb46-11e1-82cb-f4ce4684ea4c",
    "583ed102-fb46-11e1-82cb-f4ce4684ea4c",
    "583ec7cd-fb46-11e1-82cb-f4ce4684ea4c",
    "583ecc9a-fb46-11e1-82cb-f4ce4684ea4c",
    "583ec928-fb46-11e1-82cb-f4ce4684ea4c",
    "583ecda6-fb46-11e1-82cb-f4ce4684ea4c",
    "583ecb3a-fb46-11e1-82cb-f4ce4684ea4c",
    "583ecd4f-fb46-11e1-82cb-f4ce4684ea4c",
    "583ecfa8-fb46-11e1-82cb-f4ce4684ea4c",
    "583ecfff-fb46-11e1-82cb-f4ce4684ea4c",
    "583eca2f-fb46-11e1-82cb-f4ce4684ea4c",
    "583ed056-fb46-11e1-82cb-f4ce4684ea4c",
    "583ec8d4-fb46-11e1-82cb-f4ce4684ea4c", // Example ID, replace with actual team 
];

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'nba_stats_db';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function fetchAPI($url) {
    global $api_key;
    usleep(500000);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . "?api_key=" . $api_key);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Fetch NBA standings
$standings_url = "https://api.sportradar.com/nba/$access_level/v8/$language_code/seasons/$season_year/$season_type/standings.$format";
$standings_data = fetchAPI($standings_url);
$team_standings = [];
if ($standings_data && isset($standings_data['conferences'])) {
    foreach ($standings_data['conferences'] as $conference) {
        foreach ($conference['divisions'] as $division) {
            foreach ($division['teams'] as $team) {
                $team_standings[$team['id']] = [
                    'wins' => $team['wins'],
                    'losses' => $team['losses'],
                    'win_pct' => $team['win_pct']
                ];
            }
        }
    }
}

// Fetch team and player data
foreach ($team_standings as $team_id => $stats) {
    $team_url = "https://api.sportradar.com/nba/$access_level/v8/$language_code/teams/$team_id/profile.$format";
    $team_data = fetchAPI($team_url);
    if (!$team_data) continue;

    $team_name = $conn->real_escape_string($team_data['name'] ?? '');
    $market = $conn->real_escape_string($team_data['market'] ?? '');
    $alias = $conn->real_escape_string($team_data['alias'] ?? '');

    $conn->query("INSERT INTO teams (team_id, name, market, alias, wins, losses, win_pct) 
                  VALUES ('$team_id', '$team_name', '$market', '$alias', 
                    '".($stats['wins'] ?? 0)."', '".($stats['losses'] ?? 0)."', '".($stats['win_pct'] ?? 0)."')
                  ON DUPLICATE KEY UPDATE 
                  name='$team_name', market='$market', alias='$alias', wins={$stats['wins']}, losses={$stats['losses']}, win_pct={$stats['win_pct']}");

    if (isset($team_data['players']) && is_array($team_data['players'])) {
    foreach ($team_data['players'] as $player) {

        $player_id = $conn->real_escape_string($player['id']);
        $first_name = $conn->real_escape_string($player['first_name']);
        $last_name = $conn->real_escape_string($player['last_name']);
        $primary_position = $conn->real_escape_string($player['primary_position'] ?? '');
        $height = (int) $player['height'] ?? NULL;
        $weight = (int) $player['weight'] ?? NULL;
        $birth_date = $player['birthdate'] ?? NULL;

        $conn->query("INSERT INTO players (player_id, team_id, first_name, last_name, position, height, weight, birth_date) 
                      VALUES ('$player_id', '$team_id', '$first_name', '$last_name', '$primary_position', $height, $weight, '$birth_date')
                      ON DUPLICATE KEY UPDATE 
                      team_id='$team_id', first_name='$first_name', last_name='$last_name', 
                      position='$primary_position', height=$height, weight=$weight, birth_date='$birth_date'");

        // Fetch player stats

$player_url = "https://api.sportradar.com/nba/$access_level/v8/$language_code/players/$player_id/profile.$format";
$player_data = fetchAPI($player_url);
if ($player_data && 
    isset($player_data['seasons'][0]['teams'][0]['statistics']['average'])) {

    $stats = $player_data['seasons'][0]['teams'][0]['statistics']['average'];

    $stmt = $conn->prepare("
        INSERT INTO player_stats (player_id, season_year, season_type, team_id, avg_points, avg_rebounds, avg_assists, avg_steals, avg_blocks, avg_turnovers, avg_minutes, field_goal_pct, three_point_pct, free_throw_pct)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        avg_points = VALUES(avg_points), 
        avg_rebounds = VALUES(avg_rebounds), 
        avg_assists = VALUES(avg_assists), 
        avg_steals = VALUES(avg_steals),
        avg_blocks = VALUES(avg_blocks), 
        avg_turnovers = VALUES(avg_turnovers), 
        avg_minutes = VALUES(avg_minutes),
        field_goal_pct = VALUES(field_goal_pct), 
        three_point_pct = VALUES(three_point_pct), 
        free_throw_pct = VALUES(free_throw_pct)
    ");

    $stmt->bind_param("sissdddddddddd",
        $player_id, $season_year, $season_type, $team_id,
        $stats['points'], $stats['rebounds'], $stats['assists'], $stats['steals'], $stats['blocks'], 
        $stats['turnovers'], $stats['minutes'], $stats['field_goal_pct'], $stats['three_point_pct'], $stats['free_throw_pct']
    );

    $stmt->execute();
    $stmt->close();
}
    }
}
}
echo "Data fetched and inserted successfully.";
$conn->close();
?>
