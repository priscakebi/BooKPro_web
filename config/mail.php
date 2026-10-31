<?php
function sendMail($to, $subject, $message){
    // Fonction simple (PHPMailer + SMTP recommandé en production)
    $headers = "Content-Type: text/plain; charset=UTF-8\r\nFrom: BookPro <no-reply@bookpro.local>";

    // @ : sous WAMP sans SMTP, mail() échoue ; on ne bloque pas le site
    $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, $headers);

    // En local, le contenu du mail est écrit dans le log PHP
    // (C:\wamp64\logs\php_error.log) pour pouvoir tester
    if (!$ok) {
        error_log("[BookPro] Mail non envoyé à $to | $subject | $message");
    }

    return $ok;
}
?>