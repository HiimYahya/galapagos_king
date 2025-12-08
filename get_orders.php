<?php
header("Content-Type: application/json");
require 'vendor/autoload.php';

use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;

try {
    $manager = new Manager("mongodb://127.0.0.1:27017");

    // 🔹 On récupère toutes les commandes (IN_PROGRESS ou autres)
    $query = new Query([]);
    $cursor = $manager->executeQuery("galapagos.orders", $query);
    $orders = $cursor->toArray();

    if (!$orders) {
        echo json_encode([]);
        exit;
    }

    // Récupération des ports et warehouse depuis l'API
    $apiResponse = @file_get_contents("http://localhost/projet/nosql/galapagos2/api.php");
    if (!$apiResponse) {
        throw new Exception("Impossible de charger api.php");
    }

    $apiData = json_decode($apiResponse, true);
    if (!$apiData || !isset($apiData["ports"])) {
        throw new Exception("Réponse invalide de api.php");
    }

    // Construction du dictionnaire des positions
    $locations = [];
    foreach ($apiData["ports"] as $p) {
        $locations[$p["name"]] = [
            "lat" => $p["lat"],
            "lon" => $p["lon"]
        ];
    }
    $locations[$apiData["warehouse"]["name"]] = [
        "lat" => $apiData["warehouse"]["lat"],
        "lon" => $apiData["warehouse"]["lon"]
    ];

    // Traitement des commandes
    $result = [];
    foreach ($orders as $o) {
        $route = isset($o->route) && is_array($o->route) ? $o->route : [];
        $routeCoords = [];

        foreach ($route as $name) {
            if (isset($locations[$name])) {
                $routeCoords[] = [
                    "name" => $name,
                    "lat" => $locations[$name]["lat"],
                    "lon" => $locations[$name]["lon"]
                ];
            }
        }

        $result[] = [
            "_id" => (string)($o->_id ?? ""),
            "seaplaneId" => $o->seaplaneId ?? "",
            "seaplaneName" => $o->seaplaneName ?? "",
            "distance" => (float)($o->distance ?? 0),
            "fuel" => (float)($o->fuel ?? 0),
            "status" => $o->status ?? "IN_PROGRESS",
            "createdAt" => $o->createdAt ?? "",
            "route" => $route,
            "routeCoords" => $routeCoords
        ];
    }

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur serveur : " . $e->getMessage()]);
}
