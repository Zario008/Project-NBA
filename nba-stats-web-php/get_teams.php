<?php
// Include database connection
require 'config.php';

// Query to fetch all teams
$query = "SELECT team_id, name FROM teams ORDER BY name";
$result = $conn->query($query);

// Initialize an empty array to store team data
$teams = [];

// Fetch data and store in the array
while ($row = $result->fetch_assoc()) {
    $teams[] = $row;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($teams);

// Close the database connection
$conn->close();
?>