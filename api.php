<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/vendor/autoload.php';

use Laudis\Neo4j\ClientBuilder;

// 🔹 Connexion Neo4j
$neo4j_password = 'esgiesgi';
$neo4j = ClientBuilder::create()
    ->withDriver('bolt', "bolt://neo4j:$neo4j_password@localhost:7687")
    ->build();

// 🔹 Connexion MongoDB
$mongo = new MongoDB\Client("mongodb://127.0.0.1:27017");
$db = $mongo->galapagos;

// -----------------------------
// Structure de réponse globale
// -----------------------------
$response = [
    "warehouse" => null,
    "ports" => [],
    "routes" => [],
    "seaplanes" => [],
    "villes" => [],
    "positions" => [],
    "orders" => []
];

// ===================================================
// ⚓ PORTS (depuis Neo4j)
// ===================================================
try {
    $result = $neo4j->run(
        "MATCH (p:Port)
         RETURN p.id AS id,
                p.name AS name,
                p.coords.latitude AS lat,
                p.coords.longitude AS lon"
    );

    foreach ($result as $r) {
        $response["ports"][] = [
            "id" => $r->get("id"),
            "name" => $r->get("name"),
            "lat" => $r->get("lat"),
            "lon" => $r->get("lon"),
            "lockers" => []
        ];
    }
} catch (Exception $e) {
    $response["neo4j_error_ports"] = $e->getMessage();
}

// ===================================================
// 🌊 ROUTES ENTRE PORTS (depuis Neo4j)
// ===================================================
try {
    $routes = $neo4j->run(
        "MATCH (a:Port)-[r:CONNECTED_TO]->(b:Port)
         RETURN a.name AS from, b.name AS to, r.distance AS distance"
    );

    foreach ($routes as $r) {
        $response["routes"][] = [
            "from" => $r->get("from"),
            "to" => $r->get("to"),
            "distance" => $r->get("distance") ?? null
        ];
    }
} catch (Exception $e) {
    $response["neo4j_error_routes"] = $e->getMessage();
}

// ===================================================
// 📏 Génération automatique de routes si manquantes
// ===================================================
if (empty($response["routes"]) && count($response["ports"]) > 1) {
    function distance_km($lat1, $lon1, $lat2, $lon2) {
        $R = 6371; // rayon terrestre (km)
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($R * $c, 1);
    }

    foreach ($response["ports"] as $i => $from) {
        foreach ($response["ports"] as $j => $to) {
            if ($i !== $j) {
                $dist = distance_km($from["lat"], $from["lon"], $to["lat"], $to["lon"]);
                $response["routes"][] = [
                    "from" => $from["name"],
                    "to" => $to["name"],
                    "distance" => $dist
                ];
            }
        }
    }
}

// ===================================================
// 🔐 LOCKERS (depuis Neo4j)
// ===================================================
try {
    $result = $neo4j->run(
        "MATCH (p:Port)-[:HAS_LOCKER]->(l:Locker)
         RETURN p.id AS portId, l.id AS lockerId, l.status AS status"
    );

    foreach ($result as $r) {
        $portId = $r->get("portId");
        $lockerId = $r->get("lockerId");
        $status = $r->get("status") ?? "UNKNOWN";

        foreach ($response["ports"] as &$port) {
            if ($port["id"] === $portId) {
                $port["lockers"][] = [
                    "id" => $lockerId,
                    "status" => $status
                ];
                break;
            }
        }
    }
} catch (Exception $e) {
    $response["neo4j_error_lockers"] = $e->getMessage();
}

// ===================================================
// ✈️ HYDRAVIONS (depuis MongoDB)
// ===================================================
try {
    $cursor = $db->seaplanes->find([]);
    foreach ($cursor as $plane) {
        $response["seaplanes"][] = [
            "id" => (string)$plane->_id,
            "modele" => $plane->modele ?? "Inconnu",
            "etat" => $plane->etat ?? "UNKNOWN",
            "positionActuelleId" => $plane->positionActuelleId ?? null,
            "capacityCrates" => $plane->capacityCrates ?? null, // 🆕 capacité en caisses
            "fuelPerKm" => $plane->fuelPerKm ?? null // 🆕 carburant par km
        ];
    }
} catch (Exception $e) {
    $response["mongo_error_seaplanes"] = $e->getMessage();
}

// ===================================================
// 📍 POSITIONS (depuis MongoDB)
// ===================================================
try {
    $cursor = $db->positions->find([]);
    foreach ($cursor as $doc) {
        $response["positions"][] = [
            "id" => (string)$doc->_id,
            "name" => $doc->name ?? "Inconnue",
            "lat" => $doc->lat ?? null,
            "lon" => $doc->lon ?? null
        ];
    }
} catch (Exception $e) {
    $response["mongo_error_positions"] = $e->getMessage();
}

// ===================================================
// 🏠 ENTREPÔT PRINCIPAL
// ===================================================
$response["warehouse"] = [
    "name" => "Entrepôt principal - Puerto Baquerizo Moreno",
    "lat" => -0.9016,
    "lon" => -89.6158
];

// ===================================================
// 📦 COMMANDES (MongoDB)
// ===================================================
try {
    $cursor = $db->orders->find([]);
    foreach ($cursor as $doc) {
        $response["orders"][] = [
            "id" => (string)$doc->_id,
            "clientId" => $doc->client_id ?? null,
            "port" => $doc->destination_port ?? null,
            "status" => $doc->status ?? null
        ];
    }
} catch (Exception $e) {
    $response["mongo_error_orders"] = $e->getMessage();
}

// ===================================================
// ✅ Sortie finale JSON
// ===================================================
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
