<?php
// ======================================================
// CONGÉS ET FERMETURES EXCEPTIONNELLES
// ======================================================

// Condition SQL "ce créneau (alias d) n'est pas pendant un congé de son pro (alias s.user_id)"
const SQL_HORS_CONGES = "NOT EXISTS (SELECT 1 FROM conges cg
                                     WHERE cg.user_id = s.user_id
                                       AND d.date_disponible BETWEEN cg.date_debut AND cg.date_fin)";

// Congés d'un pro qui touchent une période
function conges_periode(PDO $pdo, $proId, $du, $au)
{
    $stmt = $pdo->prepare("
        SELECT * FROM conges
        WHERE user_id = ? AND date_fin >= ? AND date_debut <= ?
        ORDER BY date_debut
    ");
    $stmt->execute([$proId, $du, $au]);

    return $stmt->fetchAll();
}

// La date tombe-t-elle pendant un congé ? Renvoie le congé ou null
function conge_du_jour(array $conges, $date)
{
    foreach ($conges as $c) {
        if ($date >= $c['date_debut'] && $date <= $c['date_fin']) {
            return $c;
        }
    }

    return null;
}

// "du lun. 2 nov. au ven. 6 nov." ou "le lun. 2 nov."
function libelle_conge(array $c)
{
    return $c['date_debut'] === $c['date_fin']
        ? 'le ' . jour_court($c['date_debut'])
        : 'du ' . jour_court($c['date_debut']) . ' au ' . jour_court($c['date_fin']);
}
