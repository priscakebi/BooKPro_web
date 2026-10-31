<?php
if(isset($_SESSION['message'])){
    $type = $_SESSION['type'] ?? 'info';
    $icones = ['success' => 'bi-check-circle-fill', 'danger' => 'bi-exclamation-octagon-fill', 'warning' => 'bi-exclamation-triangle-fill', 'info' => 'bi-info-circle-fill'];

    echo "<div class='alert alert-" . e($type) . " d-flex align-items-center gap-2'>"
        . "<i class='bi " . ($icones[$type] ?? $icones['info']) . "'></i>"
        . e($_SESSION['message']) . "</div>";

    unset($_SESSION['message'], $_SESSION['type']);
}
?>