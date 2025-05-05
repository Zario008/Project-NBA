<?php
set_time_limit(0);
$host = 'localhost';
$dbname = 'nba_stats_db';
$user = 'root';
$pass = '';
$api_key = 'hK8vG9tqR10teJ3AsR9vQUyeT6ir7LQvqAj29HZJ';
$max_retries = 3;

// Create log directory
if (!file_exists("logs")) {
    mkdir("logs", 0777, true);
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all game IDs from player_game_stats to avoid re-importing
    $stmt = $pdo->query("SELECT DISTINCT game_id FROM player_game_stats");
    $existingGameIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    $stmt = $pdo->query("SELECT game_id FROM nba_game_summary");
    $gameIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($gameIds as $gameId) {
        // Skip if this game has already been imported
        if (in_array($gameId, $existingGameIds)) {
            echo "Game $gameId already imported. Skipping.\n";
            continue;
        }

        $retries = 0;
        $success = false;

        while ($retries < $max_retries && !$success) {
            $url = "https://api.sportradar.us/nba/trial/v8/en/games/{$gameId}/summary.json?api_key={$api_key}";
            $opts = ["http" => ["ignore_errors" => true]];
            $context = stream_context_create($opts);
            $json = file_get_contents($url, false, $context);

            if ($json === false || strpos($http_response_header[0], "200") === false) {
                echo "API request failed for game $gameId (HTTP: {$http_response_header[0]}). Retrying ($retries/$max_retries)...\n";
                $retries++;
                sleep(2);
                continue;
            }

            $data = json_decode($json, true);
            if (isset($data['error']['code']) && $data['error']['code'] == 429) {
                echo "Rate limit exceeded. Retrying...\n";
                $retries++;
                sleep(2);
                continue;
            }

            // Optional: Save raw API response for debugging
            file_put_contents("logs/response_{$gameId}.json", json_encode($data, JSON_PRETTY_PRINT));

            $success = true;
        }

        if (!$success) {
            echo "Failed to fetch data for game $gameId after $max_retries retries. Skipping.\n";
            continue;
        }

        foreach (['home', 'away'] as $side) {
            $team_id = $data[$side]['id'] ?? null;
            $players = $data[$side]['players'] ?? [];
            $is_home_team = ($side === 'home') ? 1 : 0;

            if ($team_id === null) {
                echo "Warning: Missing team ID for $side team in game $gameId. Skipping.\n";
                continue;
            }

            foreach ($players as $player) {
                if (!isset($player['statistics'])) {
                    echo "No stats for player {$player['id']} in game $gameId — skipping.\n";
                    continue;
                }

                $stats = $player['statistics'];

                echo "Saving stats for game: $gameId, player: {$player['id']}\n";

                $stmt = $pdo->prepare("
                    INSERT INTO player_game_stats (
                        game_id, player_id, team_id, is_home_team,
                        minutes_played, points, rebounds, assists,
                        steals, blocks, turnovers, fg_attempts, fg_made,
                        three_pt_attempts, three_pt_made,
                        free_throw_attempts, free_throw_made
                    ) VALUES (
                        :game_id, :player_id, :team_id, :is_home_team,
                        :minutes_played, :points, :rebounds, :assists,
                        :steals, :blocks, :turnovers, :fg_attempts, :fg_made,
                        :three_pt_attempts, :three_pt_made,
                        :free_throw_attempts, :free_throw_made
                    )
                    ON DUPLICATE KEY UPDATE
                        minutes_played = VALUES(minutes_played),
                        points = VALUES(points),
                        rebounds = VALUES(rebounds),
                        assists = VALUES(assists),
                        steals = VALUES(steals),
                        blocks = VALUES(blocks),
                        turnovers = VALUES(turnovers),
                        fg_attempts = VALUES(fg_attempts),
                        fg_made = VALUES(fg_made),
                        three_pt_attempts = VALUES(three_pt_attempts),
                        three_pt_made = VALUES(three_pt_made),
                        free_throw_attempts = VALUES(free_throw_attempts),
                        free_throw_made = VALUES(free_throw_made)
                ");

                $stmt->execute([
                    ':game_id' => $gameId,
                    ':player_id' => $player['id'],
                    ':team_id' => $team_id,
                    ':is_home_team' => $is_home_team,
                    ':minutes_played' => $stats['minutes'] ?? null,
                    ':points' => $stats['points'] ?? null,
                    ':rebounds' => $stats['rebounds'] ?? null,
                    ':assists' => $stats['assists'] ?? null,
                    ':steals' => $stats['steals'] ?? null,
                    ':blocks' => $stats['blocks'] ?? null,
                    ':turnovers' => $stats['turnovers'] ?? null,
                    ':fg_attempts' => $stats['field_goals_att'] ?? null,
                    ':fg_made' => $stats['field_goals_made'] ?? null,
                    ':three_pt_attempts' => $stats['three_points_att'] ?? null,
                    ':three_pt_made' => $stats['three_points_made'] ?? null,
                    ':free_throw_attempts' => $stats['free_throws_att'] ?? null,
                    ':free_throw_made' => $stats['free_throws_made'] ?? null
                ]);
            }
        }
    }

    echo "Player stats successfully imported.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General error: " . $e->getMessage() . "\n";
}
?>
