<?php
require_once 'db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_id = $_POST['team_id'] ?? null;
    $window = (int)($_POST['game_window'] ?? 5);

    if (!$team_id) {
        echo json_encode(["error" => "No team selected."]);
        exit;
    }

    try {
       $stmt = $pdo->prepare("
  SELECT points_scored, points_allowed, win
  FROM games
  WHERE team_id = :team_id
  ORDER BY game_date DESC
  LIMIT $window
");
$stmt->bindParam(':team_id', $team_id);
$stmt->execute();
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$games || count($games) === 0) {
            echo json_encode(["error" => "No recent games found for this team."]);
            exit;
        }

        $totalWins = 0;
        $totalPoints = 0;
        $totalAllowed = 0;

        foreach ($games as $game) {
            $totalWins += $game['win'];
            $totalPoints += $game['points_scored'];
            $totalAllowed += $game['points_allowed'];
        }

        $response = [
            "win_rate" => round(($totalWins / $window) * 100, 2),
            "avg_points" => round($totalPoints / $window, 2),
            "avg_allowed" => round($totalAllowed / $window, 2)
        ];

        echo json_encode($response);

    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
}
?>
