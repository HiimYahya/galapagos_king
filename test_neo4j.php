<?php
require __DIR__ . '/vendor/autoload.php';

use Laudis\Neo4j\ClientBuilder;

$password = 'esgiesgi';

try {
    $neo4j = ClientBuilder::create()
        ->withDriver('bolt', 'bolt://neo4j:' . $password . '@localhost:7687')
        ->build();

    $result = $neo4j->run('RETURN 1 AS test');

    echo "<pre>Connexion Neo4j OK ✔️\n";
    echo "Retour : " . $result->first()->get('test') . "</pre>";

} catch (Exception $e) {
    echo "<pre>❌ ERREUR Neo4j : " . $e->getMessage() . "</pre>";
}
