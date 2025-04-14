<?php
set_time_limit(1200);

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

        $max_attempts = 5;
        $attempt = 0;
        $data = null;

        while ($attempt < $max_attempts) {
            $response = @file_get_contents($url);

            if ($response !== false) {
                $data = json_decode($response, true);
                break;
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

        $team_stats_total = $season_stats['teams'][0]['statistics']['total'];

        $field_goal_pct = $team_stats_total['field_goals_pct'] ?? NULL;
        $three_point_pct = $team_stats_total['three_points_pct'] ?? NULL;
        $free_throw_pct = $team_stats_total['free_throws_pct'] ?? NULL;

        // Update only the percentage stats
        $update_sql = "UPDATE player_stats SET field_goal_pct = ?, three_point_pct = ?, free_throw_pct = ? WHERE player_id = ? AND season_year = ? AND season_type = ?";

        $stmt = $mysqli->prepare($update_sql);
        $stmt->bind_param('dddiss', $field_goal_pct, $three_point_pct, $free_throw_pct, $player_id, $season_year, $season_type);

        if ($stmt->execute()) {
            echo "Updated percentage stats for player $player_id.\n";
        } else {
            echo "Error updating stats for player $player_id: " . $stmt->error . "\n";
        }

        $stmt->close();
        sleep(2);
    }
} else {
    echo "No players found in the database.\n";
}

$mysqli->close();
?>
