<?php
if(isset($_SESSION['message'])){
    echo "<div class='alert alert-" . e($_SESSION['type'] ?? 'info') . "'>" . e($_SESSION['message']) . "</div>";
    unset($_SESSION['message'], $_SESSION['type']);
}
?>