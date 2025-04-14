<?php
// Include your database connection code here
include 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$playerName = $data['playerName'];

$suggestions = getPlayerSuggestions($conn, $playerName);

echo json_encode(['suggestions' => $suggestions]);

function getPlayerSuggestions($conn, $playerName) {
    $stmt = $conn->prepare("SELECT name FROM players WHERE name LIKE ? LIMIT 5");
    $searchTerm = '%' . $playerName . '%';
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row['name'];
    }
    return $suggestions;
}
<script>
document.getElementById("teamInput").addEventListener("input", async function() {
    const query = this.value;
    const suggestionsBox = document.getElementById("teamSuggestions");
    suggestionsBox.innerHTML = "";

    if (query.length < 1) return;

    try {
        const res = await fetch(`suggest_teams.php?term=${encodeURIComponent(query)}`);
        const teams = await res.json();

        teams.forEach(team => {
            const li = document.createElement("li");
            li.textContent = team.label;
            li.style.cursor = "pointer";
            li.style.background = "#fff";
            li.style.color = "#000";
            li.style.padding = "5px";
            li.onclick = () => {
                document.getElementById("teamInput").value = team.label;
                suggestionsBox.innerHTML = "";
            };
            suggestionsBox.appendChild(li);
        });
    } catch (err) {
        console.error("Autocomplete error:", err);
    }
});
</script>
