<?php
// Informations de connexion à la base de données
$host = "localhost";
$dbname = "bookpro";
$username = "root";
$password = "";

// Création de la connexion avec PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Activer les erreurs PDO (très utile pour debug)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // Message d'erreur si connexion échoue
    die("Erreur de connexion : " . $e->getMessage());
}
?>