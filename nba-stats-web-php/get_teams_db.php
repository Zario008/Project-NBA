<?php
header('Content-Type: application/json');

// DB connection
$host = "localhost";
$db = "nba_stats_db"; // <-- adjust if your DB name is different
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $db);

// Check for connection error
if ($conn->connect_error) {
  http_response_code(500);
  echo json_encode(["error" => "Database connection failed"]);
  exit();
}

// Fetch teams
$sql = "SELECT name, team_id FROM teams ORDER BY name ASC";
$result = $conn->query($sql);

$teams = [];
while ($row = $result->fetch_assoc()) {
  $teams[] = $row;
}

echo json_encode($teams);
?>
