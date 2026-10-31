<?php
// ======================================================
// FICHES CLIENTS D'UN PRO
// Un pro ne voit que les clients ayant réservé chez lui
// ======================================================

// Indicateurs par client (une ligne par client du pro)
const SQL_STATS_CLIENTS = "
    SELECT u.id, u.nom, u.email, u.telephone, u.created_at,
           COUNT(*) AS nb_resa,
           SUM(r.statut = 'confirmee' AND d.date_disponible < CURDATE()) AS visites,
           SUM(CASE WHEN r.statut = 'confirmee' AND d.date_disponible < CURDATE() THEN s.prix ELSE 0 END) AS total,
           SUM(r.statut = 'annulee' AND r.annule_par = 'client') AS annulations,
           MAX(CASE WHEN r.statut = 'confirmee' AND d.date_disponible < CURDATE() THEN d.date_disponible END) AS derniere_visite,
           MIN(CASE WHEN r.statut IN ('en_attente', 'confirmee') AND CONCAT(d.date_disponible, ' ', d.heure_debut) > NOW()
                    THEN CONCAT(d.date_disponible, ' ', d.heure_debut) END) AS prochain,
           MAX(n.note) AS note
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    JOIN users u ON r.user_id = u.id
    LEFT JOIN notes_clients n ON n.pro_id = s.user_id AND n.client_id = u.id
    WHERE s.user_id = ?
";

// Liste des clients, avec recherche (nom, email, téléphone, notes)
function clients_du_pro(PDO $pdo, $proId, $recherche = '')
{
    $sql = SQL_STATS_CLIENTS;
    $params = [$proId];

    if ($recherche !== '') {
        $sql .= " AND (u.nom LIKE ? OR u.email LIKE ? OR u.telephone LIKE ? OR n.note LIKE ?)";
        array_push($params, ...array_fill(0, 4, '%' . $recherche . '%'));
    }

    $sql .= " GROUP BY u.id ORDER BY prochain IS NULL, prochain, derniere_visite DESC, u.nom";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

// Fiche d'un client : null s'il n'a jamais réservé chez ce pro
function fiche_client(PDO $pdo, $proId, $clientId)
{
    $stmt = $pdo->prepare(SQL_STATS_CLIENTS . " AND u.id = ? GROUP BY u.id");
    $stmt->execute([$proId, $clientId]);

    return $stmt->fetch() ?: null;
}

// Étiquette selon la fidélité
function badge_fidelite(array $c)
{
    if ($c['visites'] >= 3) {
        return '<span class="tag tag-success"><i class="bi bi-award"></i> Fidèle</span>';
    }

    if ($c['visites'] == 0) {
        return '<span class="tag"><i class="bi bi-stars"></i> Nouveau</span>';
    }

    return '';
}
