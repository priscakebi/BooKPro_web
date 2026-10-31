<?php
// ======================================================
// CONNEXION (pages client et pro)
// ======================================================

// Vérifie les identifiants. Renvoie [utilisateur, null] ou [null, message d'erreur]
// $rolesAcceptes : rôles autorisés sur cette page (null = tous)
function tenter_connexion(PDO $pdo, $email, $motDePasse, ?array $rolesAcceptes = null)
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($motDePasse, $user['password'])) {
        // Ralentit les tentatives de mot de passe en rafale
        sleep(1);
        return [null, "Email ou mot de passe incorrect."];
    }

    if ($user['suspendu_at']) {
        return [null, "Ce compte a été suspendu. Contactez le support BookPro."];
    }

    if ($rolesAcceptes !== null && !in_array($user['role'], $rolesAcceptes, true)) {
        return [null, $user['role'] === 'client'
            ? "Ce compte est un compte client : utilisez la connexion client."
            : "Ce compte n'est pas un compte client : utilisez la connexion professionnelle."];
    }

    login_user($user);

    return [$user, null];
}
