<?php

// ======================================================
// VALIDATION COMMUNE : renvoie [données, erreur|null]
// ======================================================
function validate_service($post)
{
    $data = [
        'nom_service' => trim($post['nom_service'] ?? ""),
        'description' => trim($post['description'] ?? ""),
        'prix'        => $post['prix'] ?? "",
        'duree'       => ($post['duree'] ?? "") !== "" ? (int) $post['duree'] : null,
    ];

    if ($data['nom_service'] === "" || $data['description'] === "") {
        return [$data, "Le nom et la description sont obligatoires."];
    }

    if (!is_numeric($data['prix']) || $data['prix'] < 0) {
        return [$data, "Le prix doit être un nombre positif."];
    }

    if ($data['duree'] !== null && $data['duree'] < 1) {
        return [$data, "La durée doit être d'au moins 1 minute."];
    }

    return [$data, null];
}
