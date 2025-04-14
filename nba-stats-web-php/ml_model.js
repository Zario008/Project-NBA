let model;

async function loadTrainingData() {
  const res = await fetch('training_data.json');
  const { inputs, targets } = await res.json();

  return {
    xs: tf.tensor2d(inputs),
    ys: tf.tensor2d(targets)
  };
}

async function loadModel() {
  model = tf.sequential();
  model.add(tf.layers.dense({ inputShape: [6], units: 32, activation: 'relu' }));
  model.add(tf.layers.dense({ units: 16, activation: 'relu' }));
  model.add(tf.layers.dense({ units: 6 })); // 6 output stats
  model.compile({ loss: 'meanSquaredError', optimizer: 'adam' });

  const { xs, ys } = await loadTrainingData();

  await model.fit(xs, ys, {
    epochs: 30,
    batchSize: 32,
    callbacks: {
      onEpochEnd: (epoch, logs) => {
        console.log(`Epoch ${epoch + 1} — Loss: ${logs.loss}`);
      }
    }
  });

  console.log("✅ Model trained with realistic data");
}

function formatInputVector(data) {
  return tf.tensor2d([[
    data.points,
    data.rebounds,
    data.assists,
    data.steals,
    data.blocks,
    data.turnovers
  ]]);
}

async function predictWithML(data) {
  const input = formatInputVector(data);
  const output = model.predict(input);
  const result = await output.array();

  return {
    points: result[0][0].toFixed(2),
    rebounds: result[0][1].toFixed(2),
    assists: result[0][2].toFixed(2),
    steals: result[0][3].toFixed(2),
    blocks: result[0][4].toFixed(2),
    turnovers: result[0][5].toFixed(2)
  };
}