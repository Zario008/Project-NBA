<?php
include 'config.php'; // Ensure this file connects to your database

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json'); // Ensure JSON response

$team_id = isset($_GET['team_id']) && $_GET['team_id'] !== '' ? $_GET['team_id'] : null;
$position = isset($_GET['position']) && $_GET['position'] !== '' ? $_GET['position'] : null;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'avg_points';
$order = (isset($_GET['order']) && $_GET['order'] === 'desc') ? 'DESC' : 'ASC';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// ✅ Initialize query & parameters properly
$query = "SELECT players.*, teams.name AS team_name, 
                 player_stats.avg_points, player_stats.avg_rebounds, 
                 player_stats.avg_assists, player_stats.avg_steals, player_stats.avg_blocks
          FROM players 
          JOIN teams ON players.team_id = teams.team_id 
          JOIN player_stats ON players.player_id = player_stats.player_id 
          WHERE 1=1";

$params = [];
$types = '';

// ✅ Fix Team Filter (UUID Handling)
if (!empty($team_id)) { 
    $query .= " AND players.team_id = ?";
    $params[] = $team_id;
    $types .= 's';  // Use 's' for string (UUID)
}

// ✅ Fix Position Filter
if (!empty($position)) {
    $query .= " AND players.position = ?";
    $params[] = $position;
    $types .= 's';
}

// ✅ Sorting (Ensure Only Valid Columns Are Used)
$valid_sort_columns = [
    'avg_points', 'avg_rebounds', 'avg_assists', 'avg_steals', 'avg_blocks', 
    'first_name', 'last_name', 'team_name'
];

if (!in_array($sort_by, $valid_sort_columns)) {
    $sort_by = 'avg_points'; // Default sorting column
} else {
    $sort_by = "player_stats." . $conn->real_escape_string($sort_by);
}

$query .= " ORDER BY $sort_by $order LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// ✅ Debugging Log (Check XAMPP Logs)
error_log("Final SQL Query: " . $query);
error_log("Parameters: " . json_encode($params));

// ✅ Prepare & Execute Query Safely
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$players = [];
while ($row = $result->fetch_assoc()) {
    $players[] = $row;
}

// ✅ Get Total Player Count for Pagination
$countQuery = "SELECT COUNT(*) as total FROM players 
               JOIN player_stats ON players.player_id = player_stats.player_id 
               WHERE 1=1";

// ✅ Apply Team Filter in Count Query
if (!empty($team_id)) {
    $countQuery .= " AND players.team_id = ?";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param('s', $team_id); // UUID is a string
} else {
    $countStmt = $conn->prepare($countQuery);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$totalPlayers = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalPlayers / $limit);

// ✅ Send JSON Response
echo json_encode(['players' => $players, 'total_pages' => $totalPages]);

$stmt->close();
$countStmt->close();
$conn->close();