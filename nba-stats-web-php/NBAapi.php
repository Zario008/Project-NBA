<?php
header("Content-Type: application/json");

// API URL (Fixed: Removed extra spaces)
$url = "https://api.sportradar.com/nba/trial/v8/en/league/2024/01/27/changes.json?api_key=6Q6i61DSQfIsHKPhbUzSlQ9CRIXBFT1lptK9qMcW";

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypass SSL verification if needed

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check API response
if ($http_code !== 200) {
    die(json_encode(["error" => "API request failed with status $http_code"]));
}

// Decode JSON response
$data = json_decode($response, true);

// Check if JSON decoding failed
if (json_last_error() !== JSON_ERROR_NONE) {
    die(json_encode(["error" => "JSON decoding error: " . json_last_error_msg()]));
}

// Return formatted JSON
echo json_encode($data, JSON_PRETTY_PRINT);
?>
