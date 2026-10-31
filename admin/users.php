<?php

// ======================================================
// SESSION + SECURITE (administrateurs uniquement)
// ======================================================

require_once("../includes/auth.php");

require_login('admin');

require("../config/database.php");

// ======================================================
// SUPPRESSION D'UN UTILISATEUR (jamais un admin)
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $role = $stmt->fetchColumn();

    if (!$role || $role === 'admin') {

        flash("Suppression impossible.", "danger");

    } else {

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

        flash("Utilisateur supprimé.");
    }

    redirect("/admin/users.php");
}

// ======================================================
// LISTE (filtre par rôle)
// ======================================================

$filtre = $_GET['role'] ?? '';

if (!in_array($filtre, ['client', 'professionnel', 'admin'], true)) {
    $filtre = '';
}

$stmt = $pdo->prepare("

    SELECT id, nom, email, telephone, entreprise, siret, metier, ville, role, created_at
    FROM users
    WHERE ? = '' OR role = ?
    ORDER BY created_at DESC

");
$stmt->execute([$filtre, $filtre]);
$users = $stmt->fetchAll();

$pageTitle = "Utilisateurs";
$active = "users";
include("../includes/dash_header.php");

?>

<div class="mb-3">
    <?php foreach (['' => 'Tous', 'client' => 'Clients', 'professionnel' => 'Professionnels', 'admin' => 'Admins'] as $val => $label): ?>
        <a href="?role=<?= $val ?>" class="btn btn-sm <?= $filtre === $val ? 'btn-primary' : 'btn-outline-primary' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<div class="bp-card">

    <table class="table table-modern align-middle mb-0">

        <thead>
            <tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Infos pro</th><th>Inscrit le</th><th></th></tr>
        </thead>

        <tbody>

        <?php foreach ($users as $u): ?>

            <tr>
                <td><?= e($u['nom']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= e($u['role']) ?></td>
                <td>
                    <?php if ($u['role'] === 'professionnel'): ?>
                        <small>
                            <?= e($u['entreprise']) ?> · <?= e($u['metier']) ?> · <?= e($u['ville']) ?><br>
                            SIRET <?= e($u['siret']) ?> · <?= e($u['telephone']) ?>
                        </small>
                    <?php endif; ?>
                </td>
                <td><?= $u['created_at'] ? date_fr($u['created_at']) : '' ?></td>
                <td class="text-end">
                    <?php if ($u['role'] !== 'admin'): ?>
                        <form method="POST" onsubmit="return confirm('Supprimer ce compte et toutes ses données ?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                            <button class="btn btn-outline-danger btn-sm">Supprimer</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

</div>

<?php include("../includes/dash_footer.php"); ?>
