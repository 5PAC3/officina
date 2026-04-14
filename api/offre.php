<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../classes/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $tipo = $_GET['tipo'] ?? 'servizi';
    
    if ($tipo === 'officine') {
        $stmt = $conn->query("SELECT codice, denominazione FROM officina ORDER BY denominazione");
        $officine = [];
        while ($row = $stmt->fetch_assoc()) {
            $officine[] = $row;
        }
        echo json_encode(['success' => true, 'officine' => $officine]);
        exit;
    }
    
    if ($tipo === 'servizi') {
        $stmt = $conn->query("SELECT codice, descrizione, costo_orario FROM servizio ORDER BY codice");
        $servizi = [];
        while ($row = $stmt->fetch_assoc()) {
            $servizi[] = $row;
        }
        echo json_encode(['success' => true, 'servizi' => $servizi]);
        exit;
    }
    
    if ($tipo === 'associazioni') {
        $officina = $_GET['officina'] ?? '';
        
        $stmt = $conn->prepare("
            SELECT s.codice, s.descrizione, s.costo_orario, CASE WHEN o.officina_codice IS NOT NULL THEN 1 ELSE 0 END as associato
            FROM servizio s
            LEFT JOIN offre o ON s.codice = o.servizio_codice AND o.officina_codice = ?
            ORDER BY s.codice
        ");
        $stmt->bind_param("s", $officina);
        $stmt->execute();
        $result = $stmt->get_result();
        $servizi = [];
        while ($row = $result->fetch_assoc()) {
            $servizi[] = $row;
        }
        
        $stmt = $conn->prepare("
            SELECT p.codice, p.descrizione, p.costo_unitario, COALESCE(pp.quantita, 0) as quantita,
                   CASE WHEN pp.officina_codice IS NOT NULL THEN 1 ELSE 0 END as associato
            FROM pezzo_ricambio p
            LEFT JOIN presenza_pezzo pp ON p.codice = pp.pezzo_codice AND pp.officina_codice = ?
            ORDER BY p.codice
        ");
        $stmt->bind_param("s", $officina);
        $stmt->execute();
        $result = $stmt->get_result();
        $pezzi = [];
        while ($row = $result->fetch_assoc()) {
            $pezzi[] = $row;
        }
        
        $stmt = $conn->prepare("
            SELECT a.codice, a.descrizione, a.costo_unitario, COALESCE(pa.quantita, 0) as quantita,
                   CASE WHEN pa.officina_codice IS NOT NULL THEN 1 ELSE 0 END as associato
            FROM accessorio a
            LEFT JOIN presenza_accessorio pa ON a.codice = pa.accessorio_codice AND pa.officina_codice = ?
            ORDER BY a.codice
        ");
        $stmt->bind_param("s", $officina);
        $stmt->execute();
        $result = $stmt->get_result();
        $accessori = [];
        while ($row = $result->fetch_assoc()) {
            $accessori[] = $row;
        }
        
        echo json_encode(['success' => true, 'servizi' => $servizi, 'pezzi' => $pezzi, 'accessori' => $accessori]);
        $stmt->close();
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Tipo non riconosciuto']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if ($method === 'POST') {
    if ($action === 'associa_servizio') {
        $officina = $data['officina'] ?? '';
        $servizio = $data['codice'] ?? '';
        
        $stmt = $conn->prepare("INSERT INTO offre (officina_codice, servizio_codice) VALUES (?, ?)");
        $stmt->bind_param("ss", $officina, $servizio);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Servizio associato']);
        $stmt->close();
        exit;
    }
    
    if ($action === 'rimuovi_servizio') {
        $officina = $data['officina'] ?? '';
        $servizio = $data['codice'] ?? '';
        
        $stmt = $conn->prepare("DELETE FROM offre WHERE officina_codice = ? AND servizio_codice = ?");
        $stmt->bind_param("ss", $officina, $servizio);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Servizio rimosso']);
        $stmt->close();
        exit;
    }
    
    if ($action === 'associa_pezzo') {
        $officina = $data['officina'] ?? '';
        $pezzo = $data['codice'] ?? '';
        $quantita = intval($data['quantita'] ?? 0);
        
        $stmt = $conn->prepare("
            INSERT INTO presenza_pezzo (officina_codice, pezzo_codice, quantita)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantita = VALUES(quantita)
        ");
        $stmt->bind_param("ssi", $officina, $pezzo, $quantita);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Pezzo associato']);
        $stmt->close();
        exit;
    }
    
    if ($action === 'rimuovi_pezzo') {
        $officina = $data['officina'] ?? '';
        $pezzo = $data['codice'] ?? '';
        
        $stmt = $conn->prepare("DELETE FROM presenza_pezzo WHERE officina_codice = ? AND pezzo_codice = ?");
        $stmt->bind_param("ss", $officina, $pezzo);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Pezzo rimosso']);
        $stmt->close();
        exit;
    }
    
    if ($action === 'associa_accessorio') {
        $officina = $data['officina'] ?? '';
        $accessorio = $data['codice'] ?? '';
        $quantita = intval($data['quantita'] ?? 0);
        
        $stmt = $conn->prepare("
            INSERT INTO presenza_accessorio (officina_codice, accessorio_codice, quantita)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantita = VALUES(quantita)
        ");
        $stmt->bind_param("ssi", $officina, $accessorio, $quantita);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Accessorio associato']);
        $stmt->close();
        exit;
    }
    
    if ($action === 'rimuovi_accessorio') {
        $officina = $data['officina'] ?? '';
        $accessorio = $data['codice'] ?? '';
        
        $stmt = $conn->prepare("DELETE FROM presenza_accessorio WHERE officina_codice = ? AND accessorio_codice = ?");
        $stmt->bind_param("ss", $officina, $accessorio);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Accessorio rimosso']);
        $stmt->close();
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Azione non riconosciuta']);
?>