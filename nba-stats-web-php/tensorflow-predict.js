async function fetchPlayerStatsML(playerId, numGames) {
    const response = await fetch(`predictions.php?playerId=${playerId}&numGames=${numGames}&method=ml`);
    const data = await response.json();
    return data.stats;
}

async function predictStatsWithML(playerId, numGames) {
    const stats = await fetchPlayerStatsML(playerId, numGames);
    if (!stats) return;

    const inputTensor = tf.tensor2d(stats.map(s => [s.points, s.assists, s.rebounds]));

    // Define a simple neural network model
    const model = tf.sequential();
    model.add(tf.layers.dense({ units: 3, inputShape: [3] }));
    model.add(tf.layers.dense({ units: 3 }));
    model.compile({ optimizer: 'sgd', loss: 'meanSquaredError' });

    // Train the model
    await model.fit(inputTensor, inputTensor, { epochs: 50 });

    // Predict future stats
    const prediction = model.predict(inputTensor.slice([-1]));
    const output = prediction.arraySync()[0];

    return {
        points: output[0].toFixed(2),
        assists: output[1].toFixed(2),
        rebounds: output[2].toFixed(2)
    };
}

async function displayPredictions(playerId, numGames, method) {
    let stats;
    if (method === 'ml') {
        stats = await predictStatsWithML(playerId, numGames);
    } else {
        const response = await fetch(`predictions.php?playerId=${playerId}&numGames=${numGames}&method=avg`);
        const data = await response.json();
        stats = data.stats;
    }

    document.getElementById('stats-container').innerHTML = `
        <p>Predicted Points: ${stats.points}</p>
        <p>Predicted Assists: ${stats.assists}</p>
        <p>Predicted Rebounds: ${stats.rebounds}</p>
    `;
}