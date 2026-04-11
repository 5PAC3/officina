<?php
// Tipo di risposta: JSON (non HTML)
header('Content-Type: application/json');
// CORS: permette richieste da qualsiasi dominio (in produzione meglio specificare)
header('Access-Control-Allow-Origin: *');
// Metodi HTTP accettati
header('Access-Control-Allow-Methods: POST, PUT, DELETE');
// Header acceptati nelle richieste
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../classes/database.php';

session_start();

if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    echo json_encode(['error' => 'Accesso non autorizzato']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);
$tipo = $data['tipo'] ?? 'servizio';

switch($method) {
    case 'POST':
        $codice = $db->escape($data['codice']);
        $descrizione = $db->escape($data['descrizione']);
        $costo = floatval($data['costo']);

        if ($tipo === 'servizio') {
            $stmt = $conn->prepare("INSERT INTO servizio (codice, descrizione, costo_orario) VALUES (?, ?, ?)");
            $stmt->bind_param("ssd", $codice, $descrizione, $costo);
        } elseif ($tipo === 'pezzo') {
            $stmt = $conn->prepare("INSERT INTO pezzo_ricambio (codice, descrizione, costo_unitario) VALUES (?, ?, ?)");
            $stmt->bind_param("ssd", $codice, $descrizione, $costo);
        } elseif ($tipo === 'accessorio') {
            $stmt = $conn->prepare("INSERT INTO accessorio (codice, descrizione, costo_unitario) VALUES (?, ?, ?)");
            $stmt->bind_param("ssd", $codice, $descrizione, $costo);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => ucfirst($tipo) . ' aggiunto']);
        } else {
            echo json_encode(['error' => 'Errore: ' . $conn->error]);
        }
        $stmt->close();
        break;

    case 'DELETE':
        $codice = $data['codice'];

        if ($tipo === 'servizio') {
            $stmt = $conn->prepare("DELETE FROM servizio WHERE codice = ?");
        } elseif ($tipo === 'pezzo') {
            $stmt = $conn->prepare("DELETE FROM pezzo_ricambio WHERE codice = ?");
        } elseif ($tipo === 'accessorio') {
            $stmt = $conn->prepare("DELETE FROM accessorio WHERE codice = ?");
        }

        $stmt->bind_param("s", $codice);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => ucfirst($tipo) . ' eliminato']);
        } else {
            echo json_encode(['error' => 'Errore: ' . $conn->error]);
        }
        $stmt->close();
        break;
}
?>
