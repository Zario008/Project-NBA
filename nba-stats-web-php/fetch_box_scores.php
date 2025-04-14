<?php
set_time_limit(0);
// Database credentials
$host = "localhost";
$username = "root";
$password = "";  
$dbname = "nba_stats_db";

// Sportradar API credentials
$api_key = "hK8vG9tqR10teJ3AsR9vQUyeT6ir7LQvqAj29HZJ"; // Replace with your actual API key
$season_schedule_url = "https://api.sportradar.com/nba/trial/v8/en/games/2025/REG/schedule.json?api_key=$api_key"; // Adjust year & season type as needed

// Function to fetch API data using cURL
function fetch_api_data($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 403) {
        die("Error 403: Forbidden - Check API key and permissions.");
    } elseif ($http_code != 200) {
        die("HTTP Error $http_code while fetching data.");
    }

    return $response;
}

try {
    // Database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch NBA schedule to get all game IDs
    echo "Fetching schedule data...\n";
    $schedule_response = fetch_api_data($season_schedule_url);
    $schedule_data = json_decode($schedule_response, true);

    if (!isset($schedule_data['games']) || empty($schedule_data['games'])) {
        die("No games found in schedule.");
    }

    // Prepare SQL query for inserting games while avoiding duplicates
    $sql = "INSERT INTO nba_game_summary (game_id, home_team_id, home_team_name, away_team_id, away_team_name, home_team_points, away_team_points, game_date) 
            VALUES (:game_id, :home_team_id, :home_team_name, :away_team_id, :away_team_name, :home_team_points, :away_team_points, :game_date) 
            ON DUPLICATE KEY UPDATE 
            home_team_points = VALUES(home_team_points), 
            away_team_points = VALUES(away_team_points)";

    $stmt = $pdo->prepare($sql);

    foreach ($schedule_data['games'] as $game) {
        $game_id = $game['id'];
        $game_date = $game['scheduled'];

        // Check if game already exists in the database
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM nba_game_summary WHERE game_id = :game_id");
        $check_stmt->execute([':game_id' => $game_id]);
        $exists = $check_stmt->fetchColumn();

        if ($exists) {
            echo "Skipping game ID: $game_id (Already exists)\n";
            continue;
        }

        // Fetch game summary using game ID
        $summary_url = "https://api.sportradar.com/nba/trial/v8/en/games/$game_id/summary.json?api_key=$api_key";
        echo "Fetching summary for game ID: $game_id...\n";
        $summary_response = fetch_api_data($summary_url);
        $summary_data = json_decode($summary_response, true);

        if (!isset($summary_data['home']) || !isset($summary_data['away'])) {
            echo "Invalid data for game ID: $game_id\n";
            continue;
        }

        $home_team = $summary_data['home'];
        $away_team = $summary_data['away'];

        $home_team_id = $home_team['id'];
        $home_team_name = $home_team['name'];
        $home_team_points = $home_team['points'] ?? 0;

        $away_team_id = $away_team['id'];
        $away_team_name = $away_team['name'];
        $away_team_points = $away_team['points'] ?? 0;

        // Insert or update the record
        $stmt->execute([
            ':game_id' => $game_id,
            ':home_team_id' => $home_team_id,
            ':home_team_name' => $home_team_name,
            ':away_team_id' => $away_team_id,
            ':away_team_name' => $away_team_name,
            ':home_team_points' => $home_team_points,
            ':away_team_points' => $away_team_points,
            ':game_date' => date("Y-m-d", strtotime($game_date))
        ]);

        echo "Inserted/Updated data for game ID: $game_id\n";
        sleep(1); // Prevent hitting API rate limits
    }

    echo "Data collection complete!";
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>