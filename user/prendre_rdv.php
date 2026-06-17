<?php
include("../includes/header.php");
require("../config/database.php");
require("../config/mail.php");

$services = $pdo->query("SELECT * FROM services")->fetchAll();

if($_POST){
    $user_id = $_SESSION['user_id'];
    $service = $_POST['service'];
    $date = $_POST['date'];
    $heure = $_POST['heure'];

    // Vérifie si pris
    $check = $pdo->prepare("SELECT * FROM rendez_vous WHERE date=? AND heure=?");
    $check->execute([$date,$heure]);

    if($check->rowCount() == 0){

        $pdo->prepare("INSERT INTO rendez_vous (user_id,service_id,date,heure)
                       VALUES (?,?,?,?)")
            ->execute([$user_id,$service,$date,$heure]);

        // Mail client
        sendMail($_SESSION['email'], "Confirmation RDV", "RDV confirmé le $date à $heure");

        echo "RDV confirmé !";
    } else {
        echo "Créneau déjà pris !";
    }
}
?>

<h2>Prendre RDV</h2>

<form method="POST">
    <select name="service">
        <?php foreach($services as $s): ?>
            <option value="<?= $s['id'] ?>"><?= $s['nom_service'] ?></option>
        <?php endforeach; ?>
    </select>

    <input type="date" name="date">
    <input type="time" name="heure">

    <button>Réserver</button>
</form>