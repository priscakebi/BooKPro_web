<?php
// Informations de connexion à la base de données
$host = "localhost";
$dbname = "bookpro";
$username = "root";
$password = "";

// Création de la connexion avec PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    // Activer les erreurs PDO (très utile pour debug)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Résultats sous forme de tableaux associatifs
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Même fuseau horaire que PHP (NOW(), CURDATE())
    date_default_timezone_set('Europe/Paris');
    $pdo->exec("SET time_zone = '" . date('P') . "'");

} catch (PDOException $e) {
    // Le détail va dans les logs, jamais à l'écran
    error_log("Erreur BDD : " . $e->getMessage());
    die("Service momentanément indisponible.");
}
?>