<?php
include("../includes/header.php");
require("../config/database.php");

$rdvs = $pdo->query("
SELECT r.*, s.nom_service 
FROM rendez_vous r
JOIN services s ON r.service_id = s.id
")->fetchAll();
?>

<h2>Rendez-vous</h2>

<ul>
<?php foreach($rdvs as $r): ?>
<li><?= $r['date'] ?> - <?= $r['heure'] ?> (<?= $r['nom_service'] ?>)</li>
<?php endforeach; ?>
</ul>