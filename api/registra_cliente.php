<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../classes/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$nome = $data['nome'] ?? '';
$cognome = $data['cognome'] ?? '';
$email = $data['email'] ?? '';
$telefono = $data['telefono'] ?? '';
$password = $data['password'] ?? '';

if (empty($nome) || empty($cognome) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Tutti i campi sono obbligatori']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Genera codice cliente univoco
$codice = 'C' . strtoupper(substr(uniqid(), -7));

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO cliente (codice, nome, cognome, email, telefono, password) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $codice, $nome, $cognome, $email, $telefono, $hash);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Registrazione effettuata', 'codice' => $codice]);
} else {
    echo json_encode(['success' => false, 'error' => 'Errore: ' . $conn->error]);
}

$stmt->close();
?>