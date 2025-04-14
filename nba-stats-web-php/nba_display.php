<?php
header("Content-Type: text/html");

// API URL (Replace with the correct endpoint)
$url = "https://api.sportradar.com/nba/trial/v8/en/league/2024/01/27/changes.json?api_key=6Q6i61DSQfIsHKPhbUzSlQ9CRIXBFT1lptK9qMcW";

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypass SSL verification if needed
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check if API request was successful
if ($http_code !== 200) {
    die("<h2>Error: API request failed with status $http_code</h2>");
}

// Decode JSON response
$data = json_decode($response, true);

// Validate JSON data
if (json_last_error() !== JSON_ERROR_NONE) {
    die("<h2>Error: JSON decoding failed - " . json_last_error_msg() . "</h2>");
}

// Extract Data
$league_name = $data['league']['name'] ?? 'N/A';
$schedule = $data['schedule'] ?? [];
$results = $data['results'] ?? [];
$standings = $data['standings'] ?? [];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NBA Data Display</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1, h2 {
            color: #007bff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

<h1>NBA League: <?php echo htmlspecialchars($league_name); ?></h1>

<!-- Game Schedule -->
<h2>Game Schedule</h2>
<table>
    <tr>
        <th>Game ID</th>
        <th>Home Team</th>
        <th>Away Team</th>
        <th>Last Modified</th>
    </tr>
    <?php foreach ($schedule as $game) { ?>
        <tr>
            <td><?php echo htmlspecialchars($game['id']); ?></td>
            <td><?php echo htmlspecialchars($game['home_team']['name'] ?? $game['teams'][0]['name'] ?? 'Unknown'); ?></td>
            <td><?php echo htmlspecialchars($game['away_team']['name'] ?? $game['teams'][1]['name'] ?? 'Unknown'); ?></td>
            <td><?php echo htmlspecialchars($game['last_modified']); ?></td>
        </tr>
    <?php } ?>
</table>

<!-- Game Results -->
<h2>Game Results</h2>
<table>
    <tr>
        <th>Game ID</th>
        <th>Home Team</th>
        <th>Away Team</th>
        <th>Home Score</th>
        <th>Away Score</th>
        <th>Last Modified</th>
    </tr>
    <?php foreach ($results as $result) { ?>
        <tr>
            <td><?php echo htmlspecialchars($result['id']); ?></td>
            <td><?php echo htmlspecialchars($result['home_team']['name'] ?? $result['teams'][0]['name'] ?? 'Unknown'); ?></td>
            <td><?php echo htmlspecialchars($result['away_team']['name'] ?? $result['teams'][1]['name'] ?? 'Unknown'); ?></td>
            <td><?php echo htmlspecialchars($result['home_team_score'] ?? $result['scores'][0] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($result['away_team_score'] ?? $result['scores'][1] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($result['last_modified']); ?></td>
        </tr>
    <?php } ?>
</table>

<!-- Team Standings -->
<h2>Team Standings</h2>
<table>
    <tr>
        <th>Rank</th>
        <th>Team Name</th>
        <th>City</th>
    </tr>
    <?php
    $rank = 1; // Assigning rank manually since API may not provide it
    foreach ($standings as $team) { ?>
        <tr>
            <td><?php echo $rank++; ?></td>
            <td><?php echo htmlspecialchars($team['name'] ?? 'Unknown'); ?></td>
            <td><?php echo htmlspecialchars($team['market'] ?? 'Unknown'); ?></td>
        </tr>
    <?php } ?>
</table>

</body>
</html>
