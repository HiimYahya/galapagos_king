<?php
header("Content-Type: application/json");
require 'vendor/autoload.php'; // MongoDB client

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

$manager = new MongoDB\Driver\Manager("mongodb://127.0.0.1:27017/galapagos");

// 🔹 Lecture des données reçues
$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data["seaplaneId"])) {
    echo json_encode(["error" => "Invalid data"]);
    exit;
}

$seaplaneId = $data["seaplaneId"];
$route = $data["route"] ?? [];
$distance = $data["distance"] ?? 0;
$fuel = $data["fuel"] ?? 0;

// ===================================================
// 📦 Création d'une nouvelle commande
// ===================================================
$order = [
    "seaplaneId" => $seaplaneId, // ID de l’hydravion
    "seaplaneRef" => new ObjectId($seaplaneId), // lien ObjectId si même base Mongo
    "route" => $route,
    "distance" => $distance,
    "fuel" => $fuel,
    "status" => "IN_PROGRESS",
    "createdAt" => new UTCDateTime()
];

$bulk = new MongoDB\Driver\BulkWrite;
$orderId = $bulk->insert($order);
$manager->executeBulkWrite("galapagos.orders", $bulk);

// ===================================================
// ✈️ Mise à jour du statut de l’hydravion
// ===================================================
$update = new MongoDB\Driver\BulkWrite;
$update->update(
    ["_id" => new ObjectId($seaplaneId)],
    ['$set' => ["etat" => "IN_FLIGHT"]],
    ["multi" => false]
);
$manager->executeBulkWrite("galapagos.seaplanes", $update);

// ===================================================
// ✅ Réponse finale
// ===================================================
echo json_encode([
    "success" => true,
    "orderId" => (string)$orderId,
    "seaplaneId" => $seaplaneId,
    "message" => "Commande créée et hydravion mis en vol."
]);
