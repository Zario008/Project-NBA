<?php

$api_key = "O80kYq2ZvBjspxDihDrlF25Au97Tu2V2DMjruhia";  // Replace with your actual API key
$team_ids = ["583ec825-fb46-11e1-82cb-f4ce4684ea4c",
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
    "583ec8d4-fb46-11e1-82cb-f4ce4684ea4c"]; // Your predefined team IDs

$mysqli = new mysqli("localhost", "root", "", "nba_stats_db"); // Update with your DB details

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

foreach ($team_ids as $team_id) {
    $url = "https://api.sportradar.com/nba/trial/v8/en/teams/$team_id/profile.json?api_key=$api_key";

    // Fetch data with retry logic
    $max_attempts = 5;
    $attempt = 0;
    $data = null;

    while ($attempt < $max_attempts) {
        $response = @file_get_contents($url);

        if ($response !== false) {
            $data = json_decode($response, true);
            break; // Exit loop if request succeeds
        } else {
            echo "Warning: Request failed (Attempt: $attempt). Retrying...\n";
            sleep(3); // Wait before retrying
        }

        $attempt++;
    }

    if (!$data || empty($data['players'])) {
        echo "Error: Failed to fetch data for team ID $team_id.\n";
        continue;
    }

    foreach ($data['players'] as $player) {
        $player_id = $mysqli->real_escape_string($player['id']);
        $first_name = $mysqli->real_escape_string($player['first_name']);
        $last_name = $mysqli->real_escape_string($player['last_name']);
        $position = isset($player['primary_position']) ? $mysqli->real_escape_string($player['primary_position']) : NULL;
        $height = isset($player['height']) ? (int)$player['height'] : NULL;
        $weight = isset($player['weight']) ? (int)$player['weight'] : NULL;
        $birth_date = isset($player['birth_date']) ? $mysqli->real_escape_string($player['birth_date']) : NULL;

        // Insert or update player data
        $sql = "INSERT INTO players (player_id, team_id, first_name, last_name, position, height, weight, birth_date) 
                VALUES ('$player_id', '$team_id', '$first_name', '$last_name', '$position', '$height', '$weight', '$birth_date')
                ON DUPLICATE KEY UPDATE 
                team_id = VALUES(team_id),
                first_name = VALUES(first_name),
                last_name = VALUES(last_name),
                position = VALUES(position),
                height = VALUES(height),
                weight = VALUES(weight),
                birth_date = VALUES(birth_date)";

        if ($mysqli->query($sql) === TRUE) {
            echo "Inserted/Updated: $first_name $last_name ($player_id) \n";
        } else {
            echo "Error: " . $mysqli->error . "\n";
        }
    }

    // Add a delay to prevent rate limiting
    sleep(2); // Wait 2 seconds between requests
}

$mysqli->close();
?>