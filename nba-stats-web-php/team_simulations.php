<?php
header("Content-Type: text/html");

$api_key = "6BOFLo8cQdEDSh4UexNSfHCu6o9ePANMN23fGW6k";
$api_url = "https://api.sportradar.com/nba/trial/v8/en/seasons/2024/REG/standings.json?api_key=$api_key";

$response = file_get_contents($api_url);
$data = json_decode($response, true);

if (!isset($data['conferences'])) {
    echo "<h2>Error: No team standings available.</h2>";
    exit();
}

echo "<table border='1'>";
echo "<tr><th>Team</th><th>Wins</th><th>Losses</th><th>Win %</th></tr>";

foreach ($data['conferences'] as $conference) {
    foreach ($conference['divisions'] as $division) {
        foreach ($division['teams'] as $team) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($team['name']) . "</td>";
            echo "<td>" . htmlspecialchars($team['wins']) . "</td>";
            echo "<td>" . htmlspecialchars($team['losses']) . "</td>";
            echo "<td>" . number_format(($team['wins'] / ($team['wins'] + $team['losses'])) * 100, 2) . "%</td>";
            echo "</tr>";
        }
    }
}

echo "</table>";
?>
