<?php
// ======================================================
// DEMANDES EN ATTENTE (interrogé régulièrement par l'app pro)
// Sert de filet de sécurité si le push n'est pas disponible
// ======================================================

require __DIR__ . "/_api.php";

$demandes = demandes_en_attente($pdo, $proId);

json_out([
    'total'    => count($demandes),
    'demandes' => array_map(fn($d) => message_demande($d, $proId), $demandes),
]);
