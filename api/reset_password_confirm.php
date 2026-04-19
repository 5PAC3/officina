<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../classes/otp.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$codice = $data['codice'] ?? '';
$password = $data['password'] ?? '';

if (empty($email) || empty($codice) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Parametri mancanti']);
    exit;
}

if (!OTP::verifica($email, $codice)) {
    echo json_encode(['success' => false, 'error' => 'Codice non valido o scaduto']);
    exit;
}

if (OTP::aggiornaPassword($email, $password)) {
    echo json_encode(['success' => true, 'message' => 'Password aggiornata']);
} else {
    echo json_encode(['success' => false, 'error' => 'Errore nel salvataggio']);
}
?>