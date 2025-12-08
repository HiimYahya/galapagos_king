<?php
require __DIR__ . '/vendor/autoload.php';

try {
    // Connexion
    $client = new MongoDB\Client("mongodb://127.0.0.1:27017");

    echo "<pre>Connexion OK ✔️\n\nBases trouvées :\n";

    foreach ($client->listDatabases() as $db) {
        echo "- " . $db->getName() . "\n";
    }

    echo "</pre>";
} catch (Exception $e) {
    echo "<pre>❌ ERREUR : " . $e->getMessage() . "</pre>";
}
