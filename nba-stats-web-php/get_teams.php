<?php
// Include database connection
require 'db_connect.php';

// Query to fetch all teams
$$query = "SELECT team_id, name, win_pct FROM teams ORDER BY name";
$result = $conn->query($query);

$teams = [];
while ($row = $result->fetch_assoc()) {
    $teams[] = [
        'team_id' => $row['team_id'],
        'name' => $row['name'],
        'win_pct' => $row['win_pct'] // Don't forget to include win_pct
    ];
}

header('Content-Type: application/json');
echo json_encode($teams);
?>