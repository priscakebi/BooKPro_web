<?php
// ======================================================
// CONFIRMER / REFUSER EN UN GESTE (bouton d'une notification)
// POST { id, action, jeton }
// Le jeton signé (HMAC) remplace le jeton CSRF : il ne peut être
// produit que par le serveur, pour cette réservation et ce pro.
// ======================================================

require __DIR__ . "/_api.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['erreur' => 'methode'], 405);
}

$id = (int) ($entree['id'] ?? 0);
$action = $entree['action'] ?? '';

if (!in_array($action, ['confirmer', 'refuser'], true) || !jeton_action_valide($entree['jeton'] ?? null, $id, $action, $proId)) {
    json_out(['erreur' => 'jeton_invalide'], 403);
}

[$ok, $message] = changer_statut_pro($pdo, $proId, $id, $action);

json_out(['ok' => $ok, 'message' => $message], $ok ? 200 : 409);
