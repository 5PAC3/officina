<?php
// Tipo di risposta: JSON (non HTML)
header('Content-Type: application/json');
// CORS: permette richieste da qualsiasi dominio (in produzione meglio specificare)
header('Access-Control-Allow-Origin: *');
// Metodi HTTP accettati
header('Access-Control-Allow-Methods: POST');
// Header acceptati nelle richieste
header('Access-Control-Allow-Headers: Content-Type');

require_once '../classes/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Credenziali mancanti']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT id, password, nome, cognome FROM dipendente WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (password_verify($password, $row['password'])) {
        session_start();
        $_SESSION['admin_logged'] = true;
        $_SESSION['admin_id'] = $row['id'];
        $_SESSION['admin_nome'] = $row['nome'];
        
        echo json_encode(['success' => true, 'message' => 'Login effettuato', 'nome' => $row['nome']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Password errata']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Utente non trovato']);
}

$stmt->close();
?>
