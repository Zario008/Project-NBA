<?php
set_time_limit(0);

$api_key = "O80kYq2ZvBjspxDihDrlF25Au97Tu2V2DMjruhia";  // Replace with your actual API key

$mysqli = new mysqli("localhost", "root", "", "nba_stats_db"); // Update with your DB details

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$season_year = 2024;
$season_type = "REG";

// Fetch player IDs from database
$sql = "SELECT player_id, team_id FROM players";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $player_id = $row['player_id'];
        $team_id = $row['team_id'];
        $url = "https://api.sportradar.com/nba/trial/v8/en/players/$player_id/profile.json?api_key=$api_key";

        // Fetch data with error handling
        $max_attempts = 5;
        $attempt = 0;
        $data = null;

        while ($attempt < $max_attempts) {
            $response = @file_get_contents($url);

            if ($response !== false) {
                $data = json_decode($response, true);
                break; // Exit loop if request succeeds
            } else {
                echo "Warning: Request failed for player $player_id (Attempt: $attempt). Retrying...\n";
                sleep(3);
            }

            $attempt++;
        }

        if (!$data || empty($data['seasons'])) {
            echo "Error: No stats found for player $player_id.\n";
            continue;
        }

        // Find stats for the 2024 REG season
        $season_stats = null;
        foreach ($data['seasons'] as $season) {
            if ($season['year'] == $season_year && $season['type'] == $season_type) {
                $season_stats = $season;
                break;
            }
        }

        if (!$season_stats || empty($season_stats['teams'])) {
            echo "Error: No data for player $player_id in the 2024 REG season.\n";
            continue;
        }

        // Extract averages from the correct season
        $team_stats = $season_stats['teams'][0]['average'];

        $avg_points = isset($team_stats['points']) ? (float)$team_stats['points'] : NULL;
        $avg_rebounds = isset($team_stats['rebounds']) ? (float)$team_stats['rebounds'] : NULL;
        $avg_assists = isset($team_stats['assists']) ? (float)$team_stats['assists'] : NULL;
        $avg_steals = isset($team_stats['steals']) ? (float)$team_stats['steals'] : NULL;
        $avg_blocks = isset($team_stats['blocks']) ? (float)$team_stats['blocks'] : NULL;
        $avg_turnovers = isset($team_stats['turnovers']) ? (float)$team_stats['turnovers'] : NULL;
        $avg_minutes = isset($team_stats['minutes']) ? (float)$team_stats['minutes'] : NULL;
        
        // Fix missing percentage stats
        $field_goal_pct = isset($team_stats['field_goals_pct']) ? (float) $team_stats['field_goals_pct'] : NULL;
        $three_point_pct = isset($team_stats['three_points_pct']) ? (float) $team_stats['three_points_pct'] : NULL;
        $free_throw_pct = isset($team_stats['free_throws_pct']) ? (float) $team_stats['free_throws_pct'] : NULL;

        // Insert or update stats to prevent duplicates
        $sql = "INSERT INTO player_stats (player_id, season_year, season_type, team_id, avg_points, avg_rebounds, avg_assists, avg_steals, avg_blocks, avg_turnovers, avg_minutes, field_goal_pct, three_point_pct, free_throw_pct) 
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
                free_throw_pct = VALUES(free_throw_pct)";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iisidddddddddd', $player_id, $season_year, $season_type, $team_id, $avg_points, $avg_rebounds, $avg_assists, $avg_steals, $avg_blocks, $avg_turnovers, $avg_minutes, $field_goal_pct, $three_point_pct, $free_throw_pct);

        if ($stmt->execute()) {
            echo "Inserted/Updated stats for player $player_id (2024 REG season).\n";
        } else {
            echo "Error: " . $stmt->error . "\n";
        }

        $stmt->close();

        // Reduce API rate limiting by slowing requests
        sleep(2);
    }
}

$mysqli->close();
?>
