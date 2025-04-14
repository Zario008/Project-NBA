<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';

$query = "SELECT team_id, name, win_pct FROM teams ORDER BY name";
$result = $conn->query($query);

if ($result) {
    $teams = [];
    while ($row = $result->fetch_assoc()) {
        $teams[] = [
            'team_id' => $row['team_id'],
            'name' => $row['name'],
            'win_pct' => $row['win_pct']
        ];
    }

    // Return the result as JSON
    header('Content-Type: application/json');
    echo json_encode($teams);
} else {
    // If there's an error with the query, return an error message as JSON
    http_response_code(500); // Internal server error
    echo json_encode(['error' => 'Database query failed']);
}
?>