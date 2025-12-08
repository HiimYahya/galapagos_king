<?php
header("Content-Type: application/json");
require 'vendor/autoload.php';

$manager = new MongoDB\Driver\Manager("mongodb://127.0.0.1:27017");
$query = new MongoDB\Driver\Query([]);
$cursor = $manager->executeQuery("galapagos.seaplanes", $query);

$seaplanes = [];
foreach ($cursor as $doc) {
    $seaplanes[] = [
        "id" => (string)$doc->id,
        "modele" => $doc->modele,
        "etat" => $doc->etat,
        "position" => [
            "lat" => $doc->lat ?? $doc->position->lat ?? 0,
            "lon" => $doc->lon ?? $doc->position->lon ?? 0
        ]
    ];
}
echo json_encode($seaplanes);
