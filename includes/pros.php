<?php
// ======================================================
// REQUÊTES SUR LES PROFESSIONNELS (accueil, recherche, profil)
// ======================================================

// Liste des pros ayant au moins un service, avec leurs indicateurs.
// $filtres : q (texte libre), metier, ville ; $limit : nombre max
function rechercher_pros(PDO $pdo, array $filtres = [], $limit = 60)
{
    $where = ["u.role = 'professionnel'"];
    $params = [];

    if (($filtres['q'] ?? '') !== '') {
        $where[] = "(u.nom LIKE ? OR u.entreprise LIKE ? OR u.description LIKE ?
                     OR EXISTS (SELECT 1 FROM services s2 WHERE s2.user_id = u.id
                                AND (s2.nom_service LIKE ? OR s2.description LIKE ?)))";
        $like = '%' . $filtres['q'] . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }

    if (($filtres['metier'] ?? '') !== '') {
        $where[] = "u.metier = ?";
        $params[] = $filtres['metier'];
    }

    if (($filtres['ville'] ?? '') !== '') {
        $where[] = "(u.ville LIKE ? OR u.code_postal LIKE ?)";
        $params[] = '%' . $filtres['ville'] . '%';
        $params[] = $filtres['ville'] . '%';
    }

    $sql = "
        SELECT u.id, u.nom, u.entreprise, u.metier, u.ville, u.description, u.photo,
               st.nb_services, st.prix_min,
               av.note, COALESCE(av.nb_avis, 0) AS nb_avis,
               dp.prochaine_dispo
        FROM users u

        -- Services : nombre et prix de départ
        JOIN (SELECT user_id, COUNT(*) AS nb_services, MIN(prix) AS prix_min
              FROM services GROUP BY user_id) st ON st.user_id = u.id

        -- Avis : note moyenne
        LEFT JOIN (SELECT s.user_id, ROUND(AVG(a.note), 1) AS note, COUNT(*) AS nb_avis
                   FROM avis a
                   JOIN reservations r ON a.reservation_id = r.id
                   JOIN disponibilites d ON r.disponibilite_id = d.id
                   JOIN services s ON d.service_id = s.id
                   GROUP BY s.user_id) av ON av.user_id = u.id

        -- Prochain créneau libre
        LEFT JOIN (SELECT s.user_id, MIN(CONCAT(d.date_disponible, ' ', d.heure_debut)) AS prochaine_dispo
                   FROM disponibilites d
                   JOIN services s ON d.service_id = s.id
                   WHERE CONCAT(d.date_disponible, ' ', d.heure_debut) > NOW()
                     AND NOT EXISTS (SELECT 1 FROM reservations r
                                     WHERE r.disponibilite_id = d.id AND r.creneau_actif IS NOT NULL)
                   GROUP BY s.user_id) dp ON dp.user_id = u.id

        WHERE " . implode(' AND ', $where) . "
        ORDER BY dp.prochaine_dispo IS NULL, av.note DESC, u.created_at DESC
        LIMIT " . (int) $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

// "Aujourd'hui 14:00", "Demain 09:30", "lun. 15 déc. 10:00"
function libelle_dispo($datetime)
{
    $date = substr($datetime, 0, 10);
    $h = heure(substr($datetime, 11));

    if ($date === date('Y-m-d')) {
        return "Aujourd'hui $h";
    }

    if ($date === date('Y-m-d', strtotime('+1 day'))) {
        return "Demain $h";
    }

    return jour_court($date) . " $h";
}

// Horaires d'ouverture d'un pro, regroupés par jour : [1 => ['08:00 - 12:00', ...], ...]
function horaires_pro(PDO $pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT jour, heure_ouverture, heure_fermeture FROM horaires_ouverture
                           WHERE user_id = ? ORDER BY jour, heure_ouverture");
    $stmt->execute([$userId]);

    $horaires = array_fill_keys(array_keys(JOURS), []);

    foreach ($stmt->fetchAll() as $h) {
        $horaires[$h['jour']][] = heure($h['heure_ouverture']) . ' - ' . heure($h['heure_fermeture']);
    }

    return $horaires;
}
