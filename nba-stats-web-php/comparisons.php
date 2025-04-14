<?php
include 'config.php';

if (isset($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $stmt = $conn->prepare("SELECT player_id, first_name, last_name, position, height, weight FROM players WHERE first_name LIKE ? OR last_name LIKE ? LIMIT 10");
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    $players = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($players);
    exit();
}

if (isset($_GET['player_id'])) {
    $player_id = $_GET['player_id'];
    $stmt = $conn->prepare("SELECT season_year, season_type, avg_points, avg_rebounds, avg_assists, avg_steals, avg_blocks, avg_turnovers, avg_minutes FROM player_stats WHERE player_id = ? ORDER BY season_year DESC LIMIT 5");
    $stmt->bind_param("s", $player_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($stats);
    exit();
}



?>