<?php
require_once 'classes/otp.php';

$email = $_GET['email'] ?? '';
$codice = $_GET['codice'] ?? '';

if (!$email || !$codice) {
    die("Parametri mancanti");
}

if (OTP::verifica($email, $codice)) {
    OTP::attiva($email);
    echo "Email verificata! <a href='login_cliente.html'>Accedi</a>";
} else {
    echo "Codice non valido o scaduto";
}
?>