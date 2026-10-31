<?php

// ======================================================
// GESTION DES COMPTES (administrateurs uniquement)
// Recherche, filtres, suspension / réactivation, suppression
// ======================================================

require_once("../includes/auth.php");

require_login('admin');

require("../config/database.php");

// ======================================================
// ACTIONS (jamais sur un compte administrateur)
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $stmt = $pdo->prepare("SELECT role, nom FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $cible = $stmt->fetch();

    if (!$cible || $cible['role'] === 'admin') {

        flash("Action impossible sur ce compte.", "danger");

    } elseif ($action === 'suspendre') {

        $motif = trim($_POST['motif'] ?? '');
        $pdo->prepare("UPDATE users SET suspendu_at = NOW(), suspendu_motif = ? WHERE id = ?")
            ->execute([mb_substr($motif, 0, 255) ?: null, $id]);

        // Plus d'appareils notifiés pour un compte suspendu
        $pdo->prepare("DELETE FROM push_subscriptions WHERE user_id = ?")->execute([$id]);

        flash("Compte de " . $cible['nom'] . " suspendu : connexion bloquée" . ($cible['role'] === 'professionnel' ? ", page retirée des recherches." : "."), "warning");

    } elseif ($action === 'reactiver') {

        $pdo->prepare("UPDATE users SET suspendu_at = NULL, suspendu_motif = NULL WHERE id = ?")->execute([$id]);
        flash("Compte de " . $cible['nom'] . " réactivé.");

    } elseif ($action === 'supprimer') {

        $pdo->beginTransaction();

        // Pro : on supprime d'abord les réservations faites sur ses créneaux
        // (le reste — services, créneaux, avis — suit par clés étrangères)
        $pdo->prepare("
            DELETE r FROM reservations r
            JOIN disponibilites d ON r.disponibilite_id = d.id
            JOIN services s ON d.service_id = s.id
            WHERE s.user_id = ?
        ")->execute([$id]);

        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);

        $pdo->commit();

        flash("Compte de " . $cible['nom'] . " supprimé définitivement.");
    }

    redirect("/admin/users.php?" . http_build_query(array_intersect_key($_GET, array_flip(['q', 'role', 'statut', 'page']))));
}

// ======================================================
// LISTE : recherche + filtres + pagination
// ======================================================

$recherche = trim($_GET['q'] ?? '');
$filtreRole = in_array($_GET['role'] ?? '', ['client', 'professionnel', 'admin'], true) ? $_GET['role'] : '';
$filtreStatut = in_array($_GET['statut'] ?? '', ['actifs', 'suspendus'], true) ? $_GET['statut'] : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$parPage = 25;

$where = ["1"];
$params = [];

if ($recherche !== '') {
    $where[] = "(u.nom LIKE ? OR u.email LIKE ? OR u.entreprise LIKE ? OR u.ville LIKE ? OR u.telephone LIKE ?)";
    array_push($params, ...array_fill(0, 5, '%' . $recherche . '%'));
}
if ($filtreRole) {
    $where[] = "u.role = ?";
    $params[] = $filtreRole;
}
if ($filtreStatut) {
    $where[] = $filtreStatut === 'actifs' ? "u.suspendu_at IS NULL" : "u.suspendu_at IS NOT NULL";
}

$conditions = implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $conditions");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$nbPages = max(1, (int) ceil($total / $parPage));
$page = min($page, $nbPages);

$stmt = $pdo->prepare("
    SELECT u.id, u.nom, u.email, u.telephone, u.entreprise, u.siret, u.metier, u.ville, u.role, u.photo,
           u.created_at, u.suspendu_at, u.suspendu_motif, u.slug,
           CASE WHEN u.role = 'professionnel'
                THEN (SELECT COUNT(*) FROM reservations r JOIN disponibilites d ON r.disponibilite_id = d.id
                      JOIN services s ON d.service_id = s.id WHERE s.user_id = u.id AND r.statut = 'confirmee')
                ELSE (SELECT COUNT(*) FROM reservations r WHERE r.user_id = u.id AND r.statut = 'confirmee')
           END AS nb_rdv
    FROM users u
    WHERE $conditions
    ORDER BY u.created_at DESC
    LIMIT $parPage OFFSET " . (($page - 1) * $parPage)
);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Lien conservant les filtres
$url = fn(array $changes) => '?' . http_build_query(array_filter(array_merge(
    ['q' => $recherche, 'role' => $filtreRole, 'statut' => $filtreStatut, 'page' => $page], $changes
)));

$pageTitle = "Utilisateurs";
$pageSubtitle = $total . " compte" . ($total > 1 ? 's' : '') . ($recherche !== '' || $filtreRole || $filtreStatut ? ' correspondant' . ($total > 1 ? 's' : '') . ' aux filtres' : '');
$active = "users";
include("../includes/dash_header.php");

?>

<!-- ======================================================
FILTRES
====================================================== -->
<form method="GET" class="d-flex flex-wrap gap-2 mb-3">
    <div class="input-icon flex-grow-1" style="min-width: 240px">
        <i class="bi bi-search"></i>
        <input type="search" name="q" class="form-control" placeholder="Nom, email, entreprise, ville, téléphone..." value="<?= e($recherche) ?>">
    </div>
    <select name="role" class="form-select w-auto" data-autosubmit>
        <option value="">Tous les rôles</option>
        <?php foreach (['client' => 'Clients', 'professionnel' => 'Professionnels', 'admin' => 'Admins'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= $filtreRole === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>
    <select name="statut" class="form-select w-auto" data-autosubmit>
        <option value="">Tous les statuts</option>
        <option value="actifs" <?= $filtreStatut === 'actifs' ? 'selected' : '' ?>>Actifs</option>
        <option value="suspendus" <?= $filtreStatut === 'suspendus' ? 'selected' : '' ?>>Suspendus</option>
    </select>
    <button class="btn btn-primary">Rechercher</button>
</form>

<div class="bp-card">

    <?php if (!$users): ?>
        <div class="empty-state"><i class="bi bi-person-x"></i>Aucun compte ne correspond.</div>
    <?php else: ?>

    <div class="table-responsive">
    <table class="table table-modern align-middle mb-0">

        <thead>
            <tr><th>Compte</th><th>Rôle</th><th>Infos</th><th>RDV</th><th>Inscrit le</th><th class="text-end">Actions</th></tr>
        </thead>

        <tbody>

        <?php foreach ($users as $u): ?>

            <tr class="<?= $u['suspendu_at'] ? 'table-warning' : '' ?>">

                <td>
                    <div class="d-flex align-items-center gap-2">
                        <?= avatar($u, 36) ?>
                        <div class="min-w-0">
                            <strong><?= e($u['entreprise'] ?: $u['nom']) ?></strong>
                            <span class="d-block small text-muted text-truncate" style="max-width: 240px"><?= e($u['email']) ?></span>
                            <?php if ($u['suspendu_at']): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-slash-circle"></i> Suspendu le <?= date_fr($u['suspendu_at']) ?></span>
                                <?php if ($u['suspendu_motif']): ?><span class="d-block small text-muted">Motif : <?= e($u['suspendu_motif']) ?></span><?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>

                <td><span class="tag <?= $u['role'] === 'client' ? 'bg-light text-dark' : '' ?>"><?= ['client' => 'Client', 'professionnel' => 'Pro', 'admin' => 'Admin'][$u['role']] ?></span></td>

                <td class="small">
                    <?php if ($u['role'] === 'professionnel'): ?>
                        <?= e($u['metier']) ?> · <?= e($u['ville']) ?><br>
                        <span class="text-muted">SIRET <?= e($u['siret']) ?></span>
                    <?php else: ?>
                        <?= e($u['telephone']) ?>
                    <?php endif; ?>
                </td>

                <td><?= (int) $u['nb_rdv'] ?></td>

                <td class="small text-nowrap"><?= date_fr($u['created_at']) ?></td>

                <td class="text-end text-nowrap">
                    <?php if ($u['role'] === 'professionnel' && !$u['suspendu_at']): ?>
                        <a href="<?= BASE_URL ?>/user/pro.php?id=<?= (int) $u['id'] ?>" target="_blank" class="btn btn-soft btn-sm" title="Voir sa page publique"><i class="bi bi-eye"></i></a>
                    <?php endif; ?>

                    <?php if ($u['role'] !== 'admin'): ?>

                        <?php if ($u['suspendu_at']): ?>
                            <form method="POST" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                <input type="hidden" name="action" value="reactiver">
                                <button class="btn btn-sm btn-success" title="Réactiver"><i class="bi bi-arrow-counterclockwise"></i> Réactiver</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" class="d-inline" onsubmit="const m = prompt('Motif de la suspension (facultatif, visible des admins) :'); if (m === null) return false; this.motif.value = m;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                <input type="hidden" name="action" value="suspendre">
                                <input type="hidden" name="motif" value="">
                                <button class="btn btn-sm btn-outline-warning" title="Suspendre"><i class="bi bi-slash-circle"></i></button>
                            </form>
                        <?php endif; ?>

                        <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer DÉFINITIVEMENT ce compte et toutes ses données (réservations, avis...) ?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                            <input type="hidden" name="action" value="supprimer">
                            <button class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                        </form>

                    <?php endif; ?>
                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>
    </div>

    <!-- Pagination -->
    <?php if ($nbPages > 1): ?>
        <nav class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">Page <?= $page ?> sur <?= $nbPages ?></small>
            <div class="d-flex gap-2">
                <a href="<?= e($url(['page' => $page - 1])) ?>" class="btn btn-soft btn-sm <?= $page <= 1 ? 'disabled' : '' ?>"><i class="bi bi-chevron-left"></i></a>
                <a href="<?= e($url(['page' => $page + 1])) ?>" class="btn btn-soft btn-sm <?= $page >= $nbPages ? 'disabled' : '' ?>"><i class="bi bi-chevron-right"></i></a>
            </div>
        </nav>
    <?php endif; ?>

    <?php endif; ?>

</div>

<?php include("../includes/dash_footer.php"); ?>
