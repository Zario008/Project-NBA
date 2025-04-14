<?php
set_time_limit(500);
$host = 'localhost';
$dbname = 'nba_stats_db';
$user = 'root';
$pass = '';
$api_key = 'hK8vG9tqR10teJ3AsR9vQUyeT6ir7LQvqAj29HZJ';
$max_retries = 3;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all game IDs from nba_game_summary
    $stmt = $pdo->query("SELECT game_id FROM nba_game_summary");
    $gameIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($gameIds as $gameId) {
        $retries = 0;
        $success = false;
        
        // Retry mechanism for API request in case of rate limiting (HTTP 429)
        while ($retries < $max_retries && !$success) {
            $url = "https://api.sportradar.us/nba/trial/v8/en/games/{$gameId}/summary.json?api_key={$api_key}";
            $json = @file_get_contents($url);
            
            if ($json === false) {
                // If the request failed (e.g., due to rate limit), wait and retry
                $retries++;
                echo "API request failed for game $gameId. Retrying ($retries/$max_retries)...\n";
                sleep(2);  // Wait 2 seconds before retrying
                continue;
            }

            // If the request was successful, parse the data
            $data = json_decode($json, true);

            // Check for missing data
            if (isset($data['error']) && $data['error']['code'] == 429) {
                echo "Rate limit exceeded. Retrying...\n";
                $retries++;
                sleep(2);
                continue;
            }

            $success = true;  // If we reach here, the request was successful
        }

        // If we exhausted retries, skip to next game
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
                    continue;
                }

                $stats = $player['statistics'];

                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO player_game_stats (
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

    echo "Player stats successfully imported.";
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
} catch (Exception $e) {
    echo "General error: " . $e->getMessage();
}
?>