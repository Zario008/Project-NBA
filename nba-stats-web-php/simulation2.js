// simulation.js
document.addEventListener("DOMContentLoaded", async () => {
    document.getElementById("trainModelBtn").addEventListener("click", trainTeamScoreModel);
   
    const teamASelect = document.getElementById("teamA");
    const teamBSelect = document.getElementById("teamB");
  
    // Fetch teams from backend
    fetch('get_teams_db.php')
    .then(res => res.json())
    .then(teams => {
      teams.forEach(team => {
        const optionA = new Option(team.name, team.team_id);
        const optionB = new Option(team.name, team.team_id);
        document.getElementById("teamA").add(optionA.cloneNode(true));
        document.getElementById("teamB").add(optionB);
      });
    })
    .catch(error => {
      console.error('Error fetching teams:', error);
      alert("Failed to load teams.");
    });
  
  
    document.getElementById("simulateForm").addEventListener("submit", async e => {
      e.preventDefault();
      const formData = new URLSearchParams();
      formData.append("teamA", teamASelect.value);
      formData.append("teamB", teamBSelect.value);
  
      const response = await fetch("team_simulations2.php", {
        method: "POST",
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
      });
  
      const raw = await response.text();
      console.log("RAW response from PHP:", raw);
  
      let data = {};
      try {
        data = JSON.parse(raw);
      } catch (err) {
        console.error("Failed to parse JSON:", err);
        return;
      }
  
      const result = await simulateWithTensorFlow(data);
      animateScoreProgression(result);
      displayResult(result);
    });
  });
  async function trainTeamScoreModel() {
    alert("Training started...");
  
    const model = tf.sequential();
  
    model.add(tf.layers.dense({ inputShape: [31], units: 128, activation: 'relu' }));
    model.add(tf.layers.dropout({ rate: 0.2 }));
    model.add(tf.layers.dense({ units: 64, activation: 'relu' }));
    model.add(tf.layers.dense({ units: 1 }));
  
    model.compile({ optimizer: 'adam', loss: 'meanSquaredError' });
  
    const trainingRaw = await fetch('get_training_data.php').then(res => res.json());
  
    const inputs = tf.tensor2d(trainingRaw.map(d => d.input));
    const labels = tf.tensor2d(trainingRaw.map(d => [d.label]));
  
    await model.fit(inputs, labels, {
      epochs: 100,
      shuffle: true,
      callbacks: {
        onEpochEnd: (epoch, logs) => console.log(`Epoch ${epoch}: loss = ${logs.loss.toFixed(4)}`)
      }
    });
  
    await model.save('localstorage://team-score-model');
    alert("Model trained and saved!");
  }
  
  async function simulateWithTensorFlow(data) {
    const { teamA_info, teamB_info, teamA_players, teamB_players } = data;
    const model = await tf.loadLayersModel("localstorage://team-score-model");
  
    const getPlayerStats = players => {
      const sorted = players.sort((a, b) => b.avg_points - a.avg_points).slice(0, 5);
      const stats = [];
      sorted.forEach(p => {
        stats.push(
          p.avg_points || 0,
          p.avg_rebounds || 0,
          p.avg_assists || 0,
          p.avg_steals || 0,
          p.avg_blocks || 0,
          p.avg_turnovers || 0
        );
      });
      while (stats.length < 30) stats.push(0);
      return stats;
    };
  
    const teamA_input = tf.tensor2d([[teamA_info.win_pct, ...getPlayerStats(teamA_players)]]);
    const teamB_input = tf.tensor2d([[teamB_info.win_pct, ...getPlayerStats(teamB_players)]]);
  
    const scoreA = Math.round((await model.predict(teamA_input).data())[0]*0.75);
    const scoreB = Math.round((await model.predict(teamB_input).data())[0]*0.75);
  
    const predictedPlayerStats = [...teamA_players, ...teamB_players].map(p => {
      const factor = Math.random() * 0.5 + 0.75;
      return {
        team_id: p.team_id,
        name: `${p.first_name} ${p.last_name}`,
        points: (p.avg_points * factor).toFixed(1),
        assists: (p.avg_assists * factor).toFixed(1),
        rebounds: (p.avg_rebounds * factor).toFixed(1)
      };
    });
  
    return {
      teamA: teamA_info.name,
      teamA_id: teamA_info.team_id,
      teamB: teamB_info.name,
      teamB_id: teamB_info.team_id,
      scoreA,
      scoreB,
      predictedPlayerStats
    };
  }
  
  function animateScoreProgression(result) {
    let currentA = 0, currentB = 0;
    const { scoreA, scoreB, teamA, teamB } = result;
    const scoreDiv = document.getElementById("simulationResult");
    scoreDiv.innerHTML = `<h2>${teamA} ${currentA} - ${currentB} ${teamB}</h2>`;
  
    const interval = setInterval(() => {
      if (currentA < scoreA) currentA += 2;
      if (currentB < scoreB) currentB += 2;
      scoreDiv.innerHTML = `<h2>${teamA} ${currentA} - ${currentB} ${teamB}</h2>`;
      if (currentA >= scoreA && currentB >= scoreB) clearInterval(interval);
    }, 100);
  }
  
  function displayResult(result) {
    const resultDiv = document.getElementById("simulationResult");
    resultDiv.innerHTML += `
      <button onclick="showTeamStats(${result.teamA_id}, '${result.teamA}')">${result.teamA} Stats</button>
      <button onclick="showTeamStats(${result.teamB_id}, '${result.teamB}')">${result.teamB} Stats</button>
    `;
    window.simulatedStats = result.predictedPlayerStats;
  }
  
  function showTeamStats(teamID, teamName) {
    const players = window.simulatedStats.filter(p => p.team_id == teamID);
    const display = players.map(p => `
      <li>${p.name}: ${p.points} pts, ${p.rebounds} reb, ${p.assists} ast</li>
    `).join("");
    document.getElementById("teamStatsDisplay").innerHTML = `<h3>${teamName}</h3><ul>${display}</ul>`;
  }