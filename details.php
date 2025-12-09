<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Détails des livraisons</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
  <style>
    body { font-family: Arial, sans-serif; margin:0; padding:0; background:#f5f5f5; }
    h2 { text-align:center; margin-top:15px; }

    .details-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
      padding: 20px;
    }

    .panel {
      background:white;
      border-radius:8px;
      box-shadow:0 2px 6px rgba(0,0,0,0.2);
      padding:15px;
      width:420px;
      max-height:80vh;
      overflow-y:auto;
    }

    .panel h3 {
      margin-top:0;
      border-bottom:2px solid #007bff;
      padding-bottom:5px;
      color:#007bff;
    }

    .details-btn {
      position: absolute;
      top: 10px;
      left: 50%;
      transform: translateX(-50%);
      background: #007bff;
      color: white;
      border: none;
      padding: 8px 14px;
      border-radius: 5px;
      cursor: pointer;
      z-index: 10000;
      font-size: 15px;
      font-weight: bold;
    }
    .details-btn:hover {
      background: #0056b3;
    }

  </style>
</head>
<body>

  <h2>📋 Vue détaillée du système de livraison</h2>

  <button onclick="window.location.href='index.php'" class="details-btn" style="top:50px;">
    Home
  </button>

  <br>

  <div class="details-container">
    <div class="panel" id="planes-info"><h3>✈️ Hydravions</h3><div id="planes-content">Chargement...</div></div>
    <div class="panel" id="orders-info"><h3>📦 Commandes</h3><div id="orders-content">Chargement...</div></div>
  </div>

<script>
function chargerDetails() {
  fetch("api.php")
    .then(r => r.json())
    .then(data => {
      document.getElementById("planes-content").innerHTML = `
        <ul>${data.seaplanes.map(p => `<li><b>${p.modele}</b>
        <br>
        Statut : (${p.etat})
        <br>
        Capacité : ${p.capacityCrates} caisse(s).</li>`).join("")}</ul>
      `;
    });

  fetch("get_orders.php")
    .then(r => r.json())
    .then(orders => {
      if (!orders.length) {
        document.getElementById("orders-content").innerHTML = "<i>Aucune commande en cours</i>";
        return;
      }
      document.getElementById("orders-content").innerHTML = `
        <ul>
          ${orders.map(o => `
            <li>
              <b>${o.seaplaneName || o.seaplaneId}</b> — ${o.status}<br>
              <small>${o.distance.toFixed(1)} km / ${o.fuel.toFixed(1)} L</small><br>
              <u>Itinéraire :</u>
              <ul>${(o.route || []).map(p => `<li>${p}</li>`).join("")}</ul>
            </li>
          `).join("<hr>")}
        </ul>
      `;
    });
}
chargerDetails();
setInterval(chargerDetails, 10000);
</script>

</body>
</html>
