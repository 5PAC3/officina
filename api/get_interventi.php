<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

session_start();

if (!isset($_SESSION['logged']) || $_SESSION['user_ruolo'] !== 'tecnico') {
    echo json_encode(['success' => false, 'error' => 'Accesso negato']);
    exit;
}

require_once '../classes/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("
    SELECT i.codice, i.data, i.descrizione, i.officina_codice, 
           o.denominazione as officina_nome, a.targa, a.modello as veicolo_modello,
           c.nome as cliente_nome, c.cognome as cliente_cognome
    FROM intervento i
    LEFT JOIN officina o ON i.officina_codice = o.codice
    LEFT JOIN autoveicolo a ON i.autoveicolo_targa = a.targa
    LEFT JOIN cliente c ON i.cliente_codice = c.codice
    ORDER BY i.data DESC
");
$stmt->execute();
$result = $stmt->get_result();

$interventi = [];
while ($row = $result->fetch_assoc()) {
    $interventi[] = $row;
}

echo json_encode(['success' => true, 'interventi' => $interventi]);

$stmt->close();
?>