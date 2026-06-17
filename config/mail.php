<?php
function sendMail($to, $subject, $message){
    // Fonction simple (tu peux dire que PHPMailer est possible)
    mail($to, $subject, $message);
}
?>