<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../classes/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Credenziali mancanti']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT codice, password, nome, cognome FROM cliente WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (password_verify($password, $row['password'])) {
        session_start();
        $_SESSION['logged'] = true;
        $_SESSION['user_id'] = $row['codice'];
        $_SESSION['user_nome'] = $row['nome'];
        $_SESSION['user_ruolo'] = 'cliente';
        
        echo json_encode(['success' => true, 'message' => 'Login effettuato', 'nome' => $row['nome'], 'ruolo' => 'cliente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Password errata']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Utente non trovato']);
}

$stmt->close();
?>