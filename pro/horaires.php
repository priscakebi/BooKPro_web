<?php
include("../includes/header.php");
require("../config/database.php");

if($_POST){
    $jour = $_POST['jour'];
    $debut = $_POST['debut'];
    $fin = $_POST['fin'];

    $pdo->prepare("INSERT INTO horaires (jour,heure_debut,heure_fin) VALUES (?,?,?)")
        ->execute([$jour,$debut,$fin]);
}
?>

<h2>Horaires</h2>

<form method="POST">
    <input type="date" name="jour">
    <input type="time" name="debut">
    <input type="time" name="fin">
    <button>Ajouter</button>
</form>