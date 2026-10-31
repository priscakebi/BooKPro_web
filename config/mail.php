<?php
function sendMail($to, $subject, $message){
    // Fonction simple (PHPMailer + SMTP recommandé en production)
    $headers = "Content-Type: text/plain; charset=UTF-8\r\nFrom: BookPro <no-reply@bookpro.local>";

    // Serveur mail joignable ? (testé une fois par requête, en 0,3 s max)
    // Sous WAMP sans SMTP, mail() bloquerait ~2 s avant d'échouer
    static $smtpOk = null;

    if ($smtpOk === null) {
        $socket = @fsockopen(ini_get('SMTP') ?: 'localhost', (int) (ini_get('smtp_port') ?: 25), $errno, $errstr, 0.3);
        $smtpOk = (bool) $socket;
        if ($socket) {
            fclose($socket);
        }
    }

    $ok = $smtpOk && @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, $headers);

    // En local, le contenu du mail est écrit dans le log PHP
    // (C:\wamp64\logs\php_error.log) pour pouvoir tester
    if (!$ok) {
        error_log("[BookPro] Mail non envoyé à $to | $subject | $message");
    }

    return $ok;
}
?>