<?php
if(isset($_SESSION['message'])){
    echo "<div class='alert ".$_SESSION['type']."'>".$_SESSION['message']."</div>";
    unset($_SESSION['message']);
}
?>