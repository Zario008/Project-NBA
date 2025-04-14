<?php
$api_url = "https://www.balldontlie.io/api/v1/players?page=1&per_page=25";

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check if request was successful (HTTP 200)
if ($http_code !== 200) {
    die("Error: API returned HTTP code " . $http_code);
}

// Decode JSON response
$data = json_decode($response, true);

// Check for JSON errors
if (json_last_error() !== JSON_ERROR_NONE) {
    die("JSON Error: " . json_last_error_msg());
}

// Check if 'data' key exists
if (!isset($data['data'])) {
    die("Error: API response does not contain expected 'data' key.");
}

// Display player names
foreach ($data['data'] as $player) {
    echo "Player: " . $player['first_name'] . " " . $player['last_name'] . "<br>";
}
?>
