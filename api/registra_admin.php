<?php
// Tipo di risposta: JSON (non HTML)
header('Content-Type: application/json');
// CORS: permette richieste da qualsiasi dominio (in produzione meglio specificare)
header('Access-Control-Allow-Origin: *');
// Metodi HTTP accettati
header('Access-Control-Allow-Methods: POST');

require_once '../classes/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$username = $data['username'] ?? '';
$password = $data['password'] ?? '';
$nome = $data['nome'] ?? '';
$cognome = $data['cognome'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Username e password obbligatori']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO dipendente (username, password, nome, cognome) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $username, $hash, $nome, $cognome);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Admin registrato con successo']);
} else {
    echo json_encode(['success' => false, 'error' => 'Errore: ' . $conn->error]);
}

$stmt->close();
?>
