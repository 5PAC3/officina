<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../classes/database.php';

session_start();

if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true || 
    !in_array($_SESSION['user_ruolo'], ['magazziniere', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Accesso non autorizzato']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_ruolo'];

if ($user_role === 'magazziniere') {
    $stmt = $conn->prepare("SELECT officina_codice FROM dipendente WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $dipendente = $result->fetch_assoc();
    $assigned_officina = $dipendente['officina_codice'] ?? null;
    $stmt->close();

    if (!$assigned_officina) {
        http_response_code(403);
        echo json_encode(['error' => 'Nessuna officina assegnata']);
        exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $officina_codice = $_GET['officina'] ?? null;
    $action = $_GET['action'] ?? '';

    if ($action === 'storico') {
        $officina = $user_role === 'magazziniere' ? $assigned_officina : ($officina_codice ?? '');
        $limit = intval($_GET['limit'] ?? 50);

        if (!$officina) {
            echo json_encode(['success' => true, 'storico' => []]);
            exit;
        }

        $stmt = $conn->prepare("
            SELECT sm.*, 
                   COALESCE(pr.descrizione, a.descrizione) as descrizione_articolo,
                   d.nome as nome_operatore,
                   d.cognome as cognome_operatore
            FROM storico_movimenti sm
            LEFT JOIN pezzo_ricambio pr ON sm.tipo = 'pezzo' AND sm.codice = pr.codice
            LEFT JOIN accessorio a ON sm.tipo = 'accessorio' AND sm.codice = a.codice
            LEFT JOIN dipendente d ON sm.eseguito_da = d.id
            WHERE sm.officina_codice = ?
            ORDER BY sm.data_movimento DESC
            LIMIT ?
        ");
        $stmt->bind_param("si", $officina, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $storico = [];
        while ($row = $result->fetch_assoc()) {
            $storico[] = $row;
        }
        $stmt->close();
        echo json_encode(['success' => true, 'storico' => $storico]);
        exit;
    }

    if ($officina_codice) {
        if ($user_role === 'magazziniere' && $officina_codice !== $assigned_officina) {
            http_response_code(403);
            echo json_encode(['error' => 'Non hai accesso a questa officina']);
            exit;
        }

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
        if ($user_role === 'magazziniere') {
            $stmt = $conn->prepare("
                SELECT o.codice, o.denominazione
                FROM officina o
                WHERE o.codice = ?
            ");
            $stmt->bind_param("s", $assigned_officina);
            $stmt->execute();
            $result = $stmt->get_result();
            $officine = [];
            while ($row = $result->fetch_assoc()) {
                $officine[] = $row;
            }
            $stmt->close();
        } else {
            $result = $conn->query("
                SELECT o.codice, o.denominazione
                FROM officina o
                ORDER BY o.denominazione
            ");
            $officine = [];
            while ($row = $result->fetch_assoc()) {
                $officine[] = $row;
            }
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
            $stmt->close();
            
            $stmt = $conn->prepare("
                SELECT SUM(COALESCE(pa.quantita, 0)) as tot_accessori
                FROM presenza_accessorio pa 
                WHERE pa.officina_codice = ?
            ");
            $stmt->bind_param("s", $off['codice']);
            $stmt->execute();
            $r = $stmt->get_result()->fetch_assoc();
            $off['tot_accessori'] = $r['tot_accessori'] ?? 0;
            $stmt->close();
        }
        
        echo json_encode(['success' => true, 'officine' => $officine]);
    }
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

function registraMovimento($conn, $officina, $tipo, $codice, $quantita, $operazione, $userId, $nota = '') {
    $stmt = $conn->prepare("
        INSERT INTO storico_movimenti (officina_codice, tipo, codice, quantita, operazione, eseguito_da, nota)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssisis", $officina, $tipo, $codice, $quantita, $operazione, $userId, $nota);
    $stmt->execute();
    $stmt->close();
}

function getQuantitaAttuale($conn, $tipo, $codice, $officina) {
    if ($tipo === 'pezzo') {
        $stmt = $conn->prepare("SELECT quantita FROM presenza_pezzo WHERE officina_codice = ? AND pezzo_codice = ?");
        $stmt->bind_param("ss", $officina, $codice);
    } else {
        $stmt = $conn->prepare("SELECT quantita FROM presenza_accessorio WHERE officina_codice = ? AND accessorio_codice = ?");
        $stmt->bind_param("ss", $officina, $codice);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? intval($row['quantita']) : 0;
}

if ($method === 'POST' && $action === 'aggiorna_pezzo') {
    $officina_codice = $data['officina'] ?? '';
    $pezzo_codice = $data['codice'] ?? '';
    $nuova_quantita = intval($data['quantita'] ?? 0);
    $nota = $data['nota'] ?? '';
    $operazione = $data['operazione'] ?? ($nuova_quantita > 0 ? 'carico' : 'scarico');

    if ($user_role === 'magazziniere' && $officina_codice !== $assigned_officina) {
        http_response_code(403);
        echo json_encode(['error' => 'Non hai accesso a questa officina']);
        exit;
    }

    $quantita_vecchia = getQuantitaAttuale($conn, 'pezzo', $pezzo_codice, $officina_codice);
    $differenza = $nuova_quantita - $quantita_vecchia;

    $stmt = $conn->prepare("
        INSERT INTO presenza_pezzo (officina_codice, pezzo_codice, quantita)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantita = VALUES(quantita)
    ");
    $stmt->bind_param("ssi", $officina_codice, $pezzo_codice, $nuova_quantita);
    
    if ($stmt->execute()) {
        if ($differenza != 0) {
            registraMovimento($conn, $officina_codice, 'pezzo', $pezzo_codice, 
                            abs($differenza), $differenza > 0 ? 'carico' : 'scarico', $user_id, $nota);
        }
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
    $nuova_quantita = intval($data['quantita'] ?? 0);
    $nota = $data['nota'] ?? '';

    if ($user_role === 'magazziniere' && $officina_codice !== $assigned_officina) {
        http_response_code(403);
        echo json_encode(['error' => 'Non hai accesso a questa officina']);
        exit;
    }

    $quantita_vecchia = getQuantitaAttuale($conn, 'accessorio', $accessorio_codice, $officina_codice);
    $differenza = $nuova_quantita - $quantita_vecchia;

    $stmt = $conn->prepare("
        INSERT INTO presenza_accessorio (officina_codice, accessorio_codice, quantita)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantita = VALUES(quantita)
    ");
    $stmt->bind_param("ssi", $officina_codice, $accessorio_codice, $nuova_quantita);
    
    if ($stmt->execute()) {
        if ($differenza != 0) {
            registraMovimento($conn, $officina_codice, 'accessorio', $accessorio_codice, 
                            abs($differenza), $differenza > 0 ? 'carico' : 'scarico', $user_id, $nota);
        }
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
    $nota = $data['nota'] ?? '';

    if ($user_role === 'magazziniere' && $officina_codice !== $assigned_officina) {
        http_response_code(403);
        echo json_encode(['error' => 'Non hai accesso a questa officina']);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO presenza_pezzo (officina_codice, pezzo_codice, quantita)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("ssi", $officina_codice, $pezzo_codice, $quantita);
    
    if ($stmt->execute()) {
        registraMovimento($conn, $officina_codice, 'pezzo', $pezzo_codice, 
                        $quantita, 'carico', $user_id, $nota);
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
    $nota = $data['nota'] ?? '';

    if ($user_role === 'magazziniere' && $officina_codice !== $assigned_officina) {
        http_response_code(403);
        echo json_encode(['error' => 'Non hai accesso a questa officina']);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO presenza_accessorio (officina_codice, accessorio_codice, quantita)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("ssi", $officina_codice, $accessorio_codice, $quantita);
    
    if ($stmt->execute()) {
        registraMovimento($conn, $officina_codice, 'accessorio', $accessorio_codice, 
                        $quantita, 'carico', $user_id, $nota);
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

    if ($user_role === 'magazziniere' && $officina !== $assigned_officina) {
        http_response_code(403);
        echo json_encode(['error' => 'Non hai accesso a questa officina']);
        exit;
    }

    $quantita_attuale = getQuantitaAttuale($conn, $tipo, $codice, $officina);

    if ($tipo === 'pezzo') {
        $stmt = $conn->prepare("DELETE FROM presenza_pezzo WHERE officina_codice = ? AND pezzo_codice = ?");
        $stmt->bind_param("ss", $officina, $codice);
    } else {
        $stmt = $conn->prepare("DELETE FROM presenza_accessorio WHERE officina_codice = ? AND accessorio_codice = ?");
        $stmt->bind_param("ss", $officina, $codice);
    }
    
    if ($stmt->execute()) {
        if ($quantita_attuale > 0) {
            registraMovimento($conn, $officina, $tipo, $codice, 
                            $quantita_attuale, 'scarico', $user_id, 'Rimozione dal magazzino');
        }
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
