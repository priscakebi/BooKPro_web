<?php

// ======================================================
// SESSION
// ======================================================

session_start();

require("../config/database.php");

// ======================================================
// AJOUT SERVICE
// ======================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nom = $_POST['nom_service'];

    $description = $_POST['description'];

    $horaires = $_POST['horaires'];

    $insert = $pdo->prepare("

        INSERT INTO services (

            user_id,
            nom_service,
            description,
            horaires

        )

        VALUES (?, ?, ?, ?)

    ");

    $insert->execute([

        $_SESSION['user_id'],
        $nom,
        $description,
        $horaires

    ]);

    header("Location: dashboard.php");
    exit();
}

?>

<form method="POST">

    <input
        type="text"
        name="nom_service"
        placeholder="Nom du service"
        required>

    <textarea
        name="description"
        placeholder="Description"
        required>
    </textarea>

    <input
        type="text"
        name="horaires"
        placeholder="Ex : Lun-Ven 8h-18h"
        required>

    <button type="submit">

        Ajouter

    </button>

</form>