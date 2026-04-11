<?php
// Tipo di risposta: JSON (non HTML)
header('Content-Type: application/json');
// CORS: permette richieste da qualsiasi dominio (in produzione meglio specificare)
header('Access-Control-Allow-Origin: *');

require_once '../classes/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT codice, descrizione, costo_unitario FROM accessorio ORDER BY codice");
$stmt->execute();
$result = $stmt->get_result();

$elementi = [];
while ($row = $result->fetch_assoc()) {
    $elementi[] = $row;
}

echo json_encode(['success' => true, 'elementi' => $elementi]);

$stmt->close();
?>
