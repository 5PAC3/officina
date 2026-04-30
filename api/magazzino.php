<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../classes/database.php';

session_start();

// Allow access to magazziniere and admin roles
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true || 
    !in_array($_SESSION['user_ruolo'], ['magazziniere', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Accesso non autorizzato']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $officina_codice = $_GET['officina'] ?? null;
    
    if ($officina_codice) {
        $stmt = $conn->prepare("
            SELECT 'pezzo' as tipo, p.codice, p.descrizione, p.costo_unitario, COALESCE(pp.quantita, 0) as quantita
            FROM pezzo_ricambio p
            LEFT JOIN presenza_pezzo pp ON p.codice = pp.pezzo_codice AND pp.officina_codice = ?
            ORDER BY p.codice
        ");
        $stmt->bind_param("s", $officina_codice);
        $stmt->execute();
        $result = $stmt->get_result();
        $pezzi = [];
        while ($row = $result->fetch_assoc()) {
            $pezzi[] = $row;
        }
        
        $stmt = $conn->prepare("
            SELECT 'accessorio' as tipo, a.codice, a.descrizione, a.costo_unitario, COALESCE(pa.quantita, 0) as quantita
            FROM accessorio a
            LEFT JOIN presenza_accessorio pa ON a.codice = pa.accessorio_codice AND pa.officina_codice = ?
            ORDER BY a.codice
        ");
        $stmt->bind_param("s", $officina_codice);
        $stmt->execute();
        $result = $stmt->get_result();
        $accessori = [];
        while ($row = $result->fetch_assoc()) {
            $accessori[] = $row;
        }
        
        echo json_encode(['success' => true, 'pezzi' => $pezzi, 'accessori' => $accessori]);
    } else {
        $stmt = $conn->query("
            SELECT o.codice, o.denominazione 
            FROM officina o
            ORDER BY o.denominazione
        ");
        $officine = [];
        while ($row = $stmt->fetch_assoc()) {
            $officine[] = $row;
        }
        
        foreach ($officine as &$off) {
            $stmt = $conn->prepare("
                SELECT SUM(COALESCE(pp.quantita, 0)) as tot_pezzi
                FROM presenza_pezzo pp 
                WHERE pp.officina_codice = ?
            ");
            $stmt->bind_param("s", $off['codice']);
            $stmt->execute();
            $r = $stmt->get_result()->fetch_assoc();
            $off['tot_pezzi'] = $r['tot_pezzi'] ?? 0;
            
            $stmt = $conn->prepare("
                SELECT SUM(COALESCE(pa.quantita, 0)) as tot_accessori
                FROM presenza_accessorio pa 
                WHERE pa.officina_codice = ?
            ");
            $stmt->bind_param("s", $off['codice']);
            $stmt->execute();
            $r = $stmt->get_result()->fetch_assoc();
            $off['tot_accessori'] = $r['tot_accessori'] ?? 0;
        }
        
        echo json_encode(['success' => true, 'officine' => $officine]);
    }
    
    $stmt->close();
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if ($method === 'POST' && $action === 'aggiorna_pezzo') {
    $officina_codice = $data['officina'] ?? '';
    $pezzo_codice = $data['codice'] ?? '';
    $quantita = intval($data['quantita'] ?? 0);
    
    $stmt = $conn->prepare("
        INSERT INTO presenza_pezzo (officina_codice, pezzo_codice, quantita)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantita = VALUES(quantita)
    ");
    $stmt->bind_param("ssi", $officina_codice, $pezzo_codice, $quantita);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Pezzo aggiornato']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Errore interno']);
        error_log('DB Error: ' . $conn->error);
    }
    $stmt->close();
    exit;
}

if ($method === 'POST' && $action === 'aggiorna_accessorio') {
    $officina_codice = $data['officina'] ?? '';
    $accessorio_codice = $data['codice'] ?? '';
    $quantita = intval($data['quantita'] ?? 0);
    
    $stmt = $conn->prepare("
        INSERT INTO presenza_accessorio (officina_codice, accessorio_codice, quantita)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantita = VALUES(quantita)
    ");
    $stmt->bind_param("ssi", $officina_codice, $accessorio_codice, $quantita);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Accessorio aggiornato']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Errore interno']);
        error_log('DB Error: ' . $conn->error);
    }
    $stmt->close();
    exit;
}

if ($method === 'POST' && $action === 'associa_pezzo') {
    $officina_codice = $data['officina'] ?? '';
    $pezzo_codice = $data['codice'] ?? '';
    $quantita = intval($data['quantita'] ?? 0);
    
    $stmt = $conn->prepare("
        INSERT INTO presenza_pezzo (officina_codice, pezzo_codice, quantita)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("ssi", $officina_codice, $pezzo_codice, $quantita);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Pezzo associato']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Errore interno']);
        error_log('DB Error: ' . $conn->error);
    }
    $stmt->close();
    exit;
}

if ($method === 'POST' && $action === 'associa_accessorio') {
    $officina_codice = $data['officina'] ?? '';
    $accessorio_codice = $data['codice'] ?? '';
    $quantita = intval($data['quantita'] ?? 0);
    
    $stmt = $conn->prepare("
        INSERT INTO presenza_accessorio (officina_codice, accessorio_codice, quantita)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("ssi", $officina_codice, $accessorio_codice, $quantita);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Accessorio associato']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Errore interno']);
        error_log('DB Error: ' . $conn->error);
    }
    $stmt->close();
    exit;
}

if ($method === 'DELETE') {
    $tipo = $data['tipo'] ?? '';
    $codice = $data['codice'] ?? '';
    $officina = $data['officina'] ?? '';
    
    if ($tipo === 'pezzo') {
        $stmt = $conn->prepare("DELETE FROM presenza_pezzo WHERE officina_codice = ? AND pezzo_codice = ?");
        $stmt->bind_param("ss", $officina, $codice);
    } else {
        $stmt = $conn->prepare("DELETE FROM presenza_accessorio WHERE officina_codice = ? AND accessorio_codice = ?");
        $stmt->bind_param("ss", $officina, $codice);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Associazione rimossa']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Errore interno']);
        error_log('DB Error: ' . $conn->error);
    }
    $stmt->close();
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Azione non riconosciuta']);
?>