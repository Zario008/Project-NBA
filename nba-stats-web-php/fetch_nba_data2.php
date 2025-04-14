<?php
set_time_limit(1200);
include 'config.php';
function fetchAPI($url) {
    $response = @file_get_contents($url);
    if ($response !== FALSE) {
        sleep(2); // Wait 2 seconds between every request
        return json_decode($response, true);
    }

    echo "Error fetching data from API: $url\n";
    return null;
}



$access_level = "trial";
$language_code = "en";
$season_year = 2024;
$season_type = "REG";
$format = "json";
$api_key = "wX9ra0PeFiMdXqTxMP8jePlYk7qQOoUQdw8DEo99";

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
    "583ec8d4-fb46-11e1-82cb-f4ce4684ea4c", 
];

$batch_size = 5;  // Process 5 teams at a time
$wait_time = 30;  // Wait 30 seconds after every batch

for ($i = 0; $i < count($team_ids); $i += $batch_size) {
    $batch = array_slice($team_ids, $i, $batch_size);
    
    foreach ($batch as $team_id) {
        $url = "https://api.sportradar.com/nba/trial/v8/en/teams/$team_id/profile.json?api_key=$api_key";
        $data = fetchAPI($url);
        if ($data) {
            echo "Fetched data for team $team_id\n";
        }
    }

    if ($i + $batch_size < count($team_ids)) {
        echo "Waiting $wait_time seconds before next batch...\n";
        sleep($wait_time);
    }
}
// **Step 1: Fetch Player IDs from Team Profiles**
foreach ($team_ids as $team_id) {
    sleep(2); // Prevent API rate limit
    $team_url = "https://api.sportradar.com/nba/$access_level/v8/$language_code/teams/$team_id/profile.$format?api_key=$api_key";
    $team_data = fetchAPI($team_url);

    if ($team_data && isset($team_data['players'])) {
        foreach ($team_data['players'] as $player) {
            $player_ids[] = $player['id'];
        }
    }
}

// **Step 2: Fetch Player Stats and Insert into Database**
foreach ($player_ids as $player_id) { 
    sleep(2); // Prevent API rate limit
    $player_url = "https://api.sportradar.com/nba/$access_level/v8/$language_code/players/$player_id/profile.$format?api_key=$api_key";
    $player_data = fetchAPI($player_url);

    if ($player_data && isset($player_data['seasons'])) {
        foreach ($player_data['seasons'] as $season) {
            if ($season['year'] == $season_year && $season['type'] == $season_type) {
                foreach ($season['teams'] as $team) {
                    if (isset($team['statistics']['average'])) {
                        $stats = $team['statistics']['average'];

                        $team_id = $conn->real_escape_string($team['id']);
                        $points = $stats['points'] ?? 0;
                        $rebounds = $stats['rebounds'] ?? 0;
                        $assists = $stats['assists'] ?? 0;
                        $steals = $stats['steals'] ?? 0;
                        $blocks = $stats['blocks'] ?? 0;
                        $turnovers = $stats['turnovers'] ?? 0;
                        $minutes = $stats['minutes'] ?? 0;
                        $fg_pct = $stats['field_goal_pct'] ?? 0;
                        $three_pt_pct = $stats['three_point_pct'] ?? 0;
                        $ft_pct = $stats['free_throw_pct'] ?? 0;

                        // Check if the record already exists
                        $result = $conn->query("
                            SELECT 1 FROM player_stats 
                            WHERE player_id = '$player_id' 
                              AND season_year = '$season_year' 
                              AND season_type = '$season_type' 
                              AND team_id = '$team_id'
                        ");

                        if ($result->num_rows === 0) { // Insert only if the record doesn't exist
                            $conn->query("
                                INSERT INTO player_stats (player_id, season_year, season_type, team_id, avg_points, avg_rebounds, avg_assists, avg_steals, avg_blocks, avg_turnovers, avg_minutes, field_goal_pct, three_point_pct, free_throw_pct)
                                VALUES ('$player_id', '$season_year', '$season_type', '$team_id', $points, $rebounds, $assists, $steals, $blocks, $turnovers, $minutes, $fg_pct, $three_pt_pct, $ft_pct)
                            ");
                        }
                    }
                }
            }
        }
    }
}

    if ($player_data && isset($player_data['statistics']['total'])) {
        $stats = $player_data['statistics']['total'];
        
        $fg_pct = $stats['field_goal_pct'] ?? 0;
        $three_pt_pct = $stats['three_point_pct'] ?? 0;
        $ft_pct = $stats['free_throw_pct'] ?? 0;

        $conn->query(" 
            UPDATE player_stats 
            SET field_goal_pct = $fg_pct, three_point_pct = $three_pt_pct, free_throw_pct = $ft_pct
            WHERE player_id = '$player_id' AND season_year = '$season_year' AND season_type = '$season_type'
        ");
    }

?>