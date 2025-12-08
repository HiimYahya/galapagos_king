<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Carte Galapagos</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
  <style>
    #map { height: 100vh; }

    .delivery-planner {
      position: absolute;
      top: 10px;
      right: 10px;
      background: white;
      padding: 10px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.3);
      font-family: Arial, sans-serif;
      z-index: 9999;
      width: 270px;
    }

    .escale {
      display: flex;
      align-items: center;
      margin-bottom: 4px;
    }

    .escale select { flex: 1; margin-right: 5px; }
    .escale button {
      background: transparent;
      border: none;
      color: red;
      font-weight: bold;
      cursor: pointer;
    }

    .trajet-result {
      position: absolute;
      bottom: 10px;
      left: 10px;
      background: white;
      padding: 10px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.3);
      font-family: Arial, sans-serif;
      z-index: 99999;
      width: 320px;
      max-height: 45vh;
      overflow-y: auto;
    }

    .planes-panel {
      position: absolute;
      bottom: 10px;
      right: 10px;
      background: white;
      padding: 10px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.3);
      font-family: Arial, sans-serif;
      z-index: 9999;
      width: 300px;
      max-height: 45vh;
      overflow-y: auto;
    }

    /* 📦 Commandes en cours */
    .orders-panel {
      position: absolute;
      top: 10px;
      left: 10px;
      background: white;
      padding: 10px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.3);
      font-family: Arial, sans-serif;
      z-index: 9999;
      width: 320px;
      max-height: 45vh;
      overflow-y: auto;
    }

    @keyframes blink {
      0% { stroke-opacity: 1; }
      50% { stroke-opacity: 0.5; }
      100% { stroke-opacity: 1; }
    }
  </style>
</head>
<body>

<div id="map"></div>

<div class="delivery-planner">
  <h4>🚚 Planifier une livraison</h4>
  <div id="escales-container">
    <div class="escale">
      <select class="port-select"></select>
      <input type="number" class="crate-input" min="1" value="1" style="width:60px;">
      <button class="remove-stop" title="Supprimer">❌</button>
    </div>
  </div>
  <button id="add-stop">➕ Ajouter une escale</button>
  <button id="calculate">📍 Calculer le trajet optimal</button>
</div>

<div id="trajet-info" class="trajet-result" style="display:none;"></div>
<div id="planes-info" class="planes-panel" style="display:none;"></div>
<div id="orders-info" class="orders-panel" style="display:block;"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
const map = L.map('map').setView([-0.9, -90.3], 7);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

let ports = [], routes = [], seaplanes = [], warehouse = null;
let trajetLayer = null;
let hydravionMarkers = {};

// 🟢 Chargement initial
fetch("api.php")
  .then(r => r.json())
  .then(data => {
    ports = data.ports;
    routes = data.routes;
    seaplanes = data.seaplanes;
    warehouse = data.warehouse;
    afficherCarte(data);
    remplirSelectPorts();
    afficherHydravions(data.seaplanes);
    chargerOrders();
  });

// 🗺️ Ports + lockers
function afficherCarte(data) {
  const portIcon = L.divIcon({ html: '⚓', iconSize: [24, 24], iconAnchor: [12, 12] });
  const warehouseIcon = L.divIcon({ html: '🏭', iconSize: [28, 28], iconAnchor: [14, 14] });

  const offsetLat = data.warehouse.lat + 0.03;
  const offsetLon = data.warehouse.lon + 0.02;

  L.marker([offsetLat, offsetLon], { icon: warehouseIcon })
    .addTo(map)
    .bindPopup(`<b>${data.warehouse.name}</b><br><i>Entrepôt principal</i>`);

  data.ports.forEach(p => {
    const lockersHTML = p.lockers?.length
      ? `<ul style="padding-left:15px; margin-top:5px;">
          ${p.lockers.map(l => `
            <li>
              <span style="
                display:inline-block;
                width:10px;
                height:10px;
                border-radius:50%;
                background:${l.status === 'EMPTY' ? 'limegreen' : 'red'};
                margin-right:5px;
              "></span>
              Locker ${l.id} — ${l.status === 'EMPTY' ? 'Disponible' : 'Occupé'}
            </li>
          `).join('')}
        </ul>`
      : `<i>Aucun locker</i>`;

    L.marker([p.lat, p.lon], { icon: portIcon })
      .addTo(map)
      .bindPopup(`<b>${p.name}</b><br><b>Lockers :</b><br>${lockersHTML}`);
  });
}

// 🛩️ Hydravions
function afficherHydravions(seaplanes) {
  const div = document.getElementById("planes-info");
  div.style.display = "block";
  div.innerHTML = `
    <h4>🛩️ Hydravions disponibles</h4>
    <ul style="padding-left: 20px; margin:0;">
      ${seaplanes.map(a => `
        <li><b>${a.modele}</b><br>
          Capacité : ${a.capacityCrates} caisses<br>
          ⛽ ${a.fuelPerKm} L/km<br>
          Statut : ${a.etat}
        </li>`).join("<hr>")}
    </ul>
  `;
}

// 📦 Commandes en cours
function chargerOrders() {
  fetch("get_orders.php")
    .then(r => r.json())
    .then(orders => {
      console.log("📦 Commandes reçues :", orders);
      const div = document.getElementById("orders-info");

      if (!orders.length) {
        div.innerHTML = "<h4>📦 Aucune commande en cours</h4>";
        return;
      }

      div.innerHTML = `
        <h4>📦 Commandes en cours (${orders.length})</h4>
        <ul style="padding-left: 15px;">
          ${orders.map(o => `
            <li style="margin-bottom:10px;">
              <b>ID :</b> ${o._id}<br>
              <b>Hydravion :</b> ${o.seaplaneName || o.seaplaneId}<br>
              <b>Distance :</b> ${o.distance.toFixed(1)} km<br>
              <b>Carburant :</b> ${o.fuel.toFixed(1)} L<br>
              <b>Statut :</b> ${o.status}<br>
            </li>
          `).join("<hr>")}
        </ul>
      `;
    })
    .catch(err => {
      console.error("Erreur chargement commandes :", err);
      document.getElementById("orders-info").innerHTML =
        "<h4>❌ Erreur lors du chargement des commandes</h4>";
    });
}
setInterval(chargerOrders, 10000);

// ➕ Ajouter une escale
document.getElementById("add-stop").addEventListener("click", () => {
  const div = document.createElement("div");
  div.classList.add("escale");
  div.innerHTML = `
    <select class="port-select"></select>
    <input type="number" class="crate-input" min="1" value="1" style="width:60px;">
    <button class="remove-stop" title="Supprimer">❌</button>
  `;
  document.getElementById("escales-container").appendChild(div);
  remplirSelectPorts();
  div.querySelector(".remove-stop").addEventListener("click", () => div.remove());
});

function remplirSelectPorts() {
  document.querySelectorAll(".port-select").forEach(select => {
    const currentValue = select.value;
    select.innerHTML = ports.map(p => `<option value="${p.id}">${p.name}</option>`).join("");
    if (currentValue && ports.find(p => p.id === currentValue)) select.value = currentValue;
  });
}

// 📍 Calcul du trajet optimal
document.getElementById("calculate").addEventListener("click", () => {
  const escales = Array.from(document.querySelectorAll(".escale")).map(e => ({
    portId: e.querySelector(".port-select").value,
    crates: parseInt(e.querySelector(".crate-input").value)
  }));

  const totalCrates = escales.reduce((sum, e) => sum + e.crates, 0);
  const selectedPlane = seaplanes.find(p => p.capacityCrates >= totalCrates && p.etat === "AT_PORT");

  if (!selectedPlane) {
    alert("Aucun hydravion disponible avec la capacité suffisante.");
    return;
  }

  const portsChoisis = escales.map(e => ({
    port: ports.find(p => p.id === e.portId),
    crates: e.crates
  })).filter(p => p.port);

  const itineraire = calculerItineraireOptimise(portsChoisis.map(p => p.port), routes, warehouse);
  const totalDistance = calculerDistanceTotale(itineraire, routes);
  const consommation = totalDistance * selectedPlane.fuelPerKm;

  afficherTrajet(itineraire);
  afficherResultat(itineraire, portsChoisis, selectedPlane, totalDistance, consommation);

  fetch("create_order.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      seaplaneId: selectedPlane.id,
      route: itineraire.map(p => p.name),
      distance: totalDistance,
      fuel: consommation
    })
  })
    .then(r => r.json())
    .then(() => chargerOrders());
});

// 🔴 Trace le trajet
function afficherTrajet(itineraire) {
  if (trajetLayer) map.removeLayer(trajetLayer);
  const points = itineraire.map(p => [p.lat, p.lon]);
  trajetLayer = L.polyline(points, { color: "red", weight: 4 }).addTo(map);
  map.fitBounds(trajetLayer.getBounds(), { padding: [30, 30] });
}

function afficherResultat(itineraire, portsChoisis, avion, distance, fuel) {
  const div = document.getElementById("trajet-info");
  div.style.display = "block";
  div.innerHTML = `
    <h4>🛩️ Hydravion : ${avion.modele}</h4>
    <p>Capacité : ${avion.capacityCrates} caisses<br>⛽ ${avion.fuelPerKm} L/km</p>
    <hr>
    <ul>${portsChoisis.map(e => `<li>${e.port.name} — ${e.crates} caisses</li>`).join("")}</ul>
    <p><b>Distance :</b> ${distance.toFixed(1)} km<br><b>Carburant :</b> ${fuel.toFixed(1)} L</p>
  `;
}

// 🔄 Calcul itinéraires
function calculerItineraireOptimise(portsChoisis, routes, start) {
  const chemin = [start];
  const restants = [...portsChoisis];
  let courant = start;
  while (restants.length) {
    let plusProche = null, minDist = Infinity;
    for (const port of restants) {
      const d = getDistanceEntre(courant, port, routes);
      if (d < minDist) { minDist = d; plusProche = port; }
    }
    chemin.push(plusProche);
    restants.splice(restants.indexOf(plusProche), 1);
    courant = plusProche;
  }
  chemin.push(start);
  return chemin;
}

function calculerDistanceTotale(itineraire, routes) {
  let total = 0;
  for (let i = 0; i < itineraire.length - 1; i++) total += getDistanceEntre(itineraire[i], itineraire[i+1], routes);
  return total;
}

function getDistanceEntre(a, b, routes) {
  const route = routes.find(r =>
    (r.from === a.name && r.to === b.name) || (r.to === a.name && r.from === b.name)
  );
  if (route) return route.distance;
  const R = 6371, dLat = (b.lat - a.lat) * Math.PI / 180, dLon = (b.lon - a.lon) * Math.PI / 180;
  const aa = Math.sin(dLat/2)**2 + Math.cos(a.lat*Math.PI/180)*Math.cos(b.lat*Math.PI/180)*Math.sin(dLon/2)**2;
  return R * 2 * Math.atan2(Math.sqrt(aa), Math.sqrt(1 - aa));
}
</script>
</body>
</html>