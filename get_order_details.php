<?php
header("Content-Type: application/json");
require 'vendor/autoload.php';

use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\BSON\ObjectId;

$manager = new Manager("mongodb://127.0.0.1:27017");

if (!isset($_GET["id"])) {
    echo json_encode(["error" => "Missing order ID"]);
    exit;
}

$orderId = $_GET["id"];
$query = new Query(["_id" => new ObjectId($orderId)]);
$cursor = $manager->executeQuery("galapagos.orders", $query);
$order = current($cursor->toArray());

if (!$order) {
    echo json_encode(["error" => "Order not found"]);
    exit;
}

// Chargement des ports et de l’entrepôt depuis la base ou API
$portsData = json_decode(file_get_contents("api.php"), true);
$allPorts = [];
foreach ($portsData["ports"] as $p) {
    $allPorts[$p["name"]] = ["lat" => $p["lat"], "lon" => $p["lon"]];
}
$allPorts[$portsData["warehouse"]["name"]] = [
    "lat" => $portsData["warehouse"]["lat"],
    "lon" => $portsData["warehouse"]["lon"]
];

// Génère les coordonnées du trajet
$routeCoords = [];
foreach ($order->route as $name) {
    if (isset($allPorts[$name])) {
        $routeCoords[] = [$allPorts[$name]["lat"], $allPorts[$name]["lon"]];
    }
}

echo json_encode([
    "_id" => (string)$order->_id,
    "seaplaneId" => $order->seaplaneId ?? "",
    "route" => $order->route ?? [],
    "routeCoords" => $routeCoords,
    "distance" => $order->distance ?? 0,
    "fuel" => $order->fuel ?? 0,
    "status" => $order->status ?? "IN_PROGRESS"
]);
