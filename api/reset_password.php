<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../classes/database.php';
require_once '../classes/otp.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';

if (empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Email mancante']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT codice FROM cliente WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if (!$result->fetch_assoc()) {
    echo json_encode(['success' => false, 'error' => 'Email non registrata']);
    $stmt->close();
    exit;
}
$stmt->close();

$codice = OTP::genera();
if (OTP::invia($email, $codice, 'reset')) {
    echo json_encode(['success' => true, 'message' => 'Email inviata']);
} else {
    echo json_encode(['success' => false, 'error' => 'Invio email fallito']);
}
?>